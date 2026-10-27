<?php
/**
 * Firewall orchestrator — basic (in-WP) and extended (early-boot) modes.
 *
 * Basic mode:   rules evaluated at plugins_loaded priority 9999.
 * Extended:     auto_prepend_file bootstrap in wp-content/wpepp-fw/ runs the
 *               same pure engine BEFORE WordPress loads. Opt-in, fail-safe:
 *               any crash renames the bootstrap to .disabled automatically.
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Firewall
 */
class WPEPP_Firewall {

	/**
	 * Settings keys.
	 */
	const OPT_SETTINGS = 'wpepp_firewall_settings';

	/**
	 * Marker for the batched match counter.
	 *
	 * @var string
	 */
	const COUNTER_KEY = 'wpepp_fw_blocked_count';

	/**
	 * Boot — called once from the plugin constructor.
	 */
	public static function boot() {
		$s = self::settings();

		if ( empty( $s['basic_enabled'] ) ) {
			add_filter( 'wpepp/security/score_items', [ __CLASS__, 'score_item' ] );
			return;
		}

		// Run at init priority 0: still before all content/template logic,
		// but AFTER WordPress core is fully loaded so translations,
		// current_user, and is_admin() are reliable (avoids WP 6.7
		// _load_textdomain_just_in_time warnings and admin false positives).
		add_action( 'init', [ __CLASS__, 'run_basic' ], 0 );

		// Self-heal: regenerate an outdated extended-mode bootstrap so
		// existing installs pick up exemption fixes without manual steps.
		if ( ! empty( $s['extended_enabled'] ) && self::extended_installed() ) {
			$bootstrap = @file_get_contents( self::runtime_dir() . '/bootstrap.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

			if ( is_string( $bootstrap ) && false === strpos( $bootstrap, 'wordpress_logged_in_' ) ) {
				self::enable_extended();
			}
		}
	}

	/**
	 * Whether this request should be exempt from basic-mode inspection.
	 *
	 * Authenticated administrators (manage_options), CLI/cron, and configured
	 * allowlisted IPs are exempt so site administration is never disrupted.
	 * Unauthenticated and non-admin requests — including REST API (/wp-json/)
	 * and XML-RPC — are inspected to stop exploits.
	 *
	 * @param array $request Normalized request.
	 * @return bool True = skip inspection.
	 */
	private static function is_exempt( array $request ) {
		// CLI / WP-CLI / Cron.
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return true;
		}

		// Emergency Safe Mode active?
		if ( class_exists( 'WPEPP_Security' ) && WPEPP_Security::is_safe_mode() ) {
			return true;
		}

		// Authorized webhooks and payment gateways (Stripe, PayPal, WooCommerce, etc.)
		if ( self::is_webhook_exempt( $request ) ) {
			return true;
		}

		// Authenticated administrators are exempt so their admin workflows
		// and REST/AJAX operations are never blocked.
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check configured IP allowlist.
		$raw_sec  = get_option( 'wpepp_security_settings', '{}' );
		$sec_data = json_decode( (string) $raw_sec, true );
		if ( is_array( $sec_data ) && ! empty( $sec_data['ip_allowlist'] ) ) {
			if ( class_exists( 'WPEPP_Security' ) && method_exists( 'WPEPP_Security', 'is_ip_in_list' ) ) {
				if ( WPEPP_Security::is_ip_in_list( $request['ip'], $sec_data['ip_allowlist'] ) ) {
					return true;
				}
			}
		}

		// Admin area requests by logged-in users with edit_posts (e.g. Editors/Authors).
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
		if ( 'admin-post.php' === $script && function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the request is an authorized third-party webhook or payment gateway callback.
	 *
	 * Prevents false-positive firewall blocks on payment gateways (Stripe, PayPal,
	 * Razorpay, Mollie, Square, Paystack, etc.) and WooCommerce endpoints.
	 *
	 * @param array $request Normalized request.
	 * @return bool True = webhook exempt from firewall blocking.
	 */
	public static function is_webhook_exempt( array $request ) {
		$uri = strtolower( (string) ( $request['uri'] ?? $request['path'] ?? '' ) );
		if ( empty( $uri ) ) {
			return false;
		}

		$defaults = [
			'wc-api',                  // WooCommerce legacy API / webhooks (PayPal IPN, etc.)
			'wp-json/wc/',             // WooCommerce REST API Webhooks
			'wp-json/stripe/',         // Stripe webhooks
			'wp-json/paypal/',         // PayPal webhooks
			'wp-json/razorpay/',       // Razorpay webhooks
			'wp-json/mollie/',         // Mollie webhooks
			'wp-json/square/',         // Square webhooks
			'wp-json/paystack/',       // Paystack webhooks
			'wp-json/contact-form-7/', // Contact Form 7 REST submissions
			'wc-ajax',                 // WooCommerce frontend AJAX
			'paypal-ipn',              // Common PayPal IPN custom endpoints
			'edd-listener',            // Easy Digital Downloads IPN / webhooks
		];

		/**
		 * Filter the list of URI substrings/endpoints exempt from firewall inspection.
		 *
		 * @param string[] $endpoints Whitelisted URI substrings.
		 * @param array    $request   Current normalized request.
		 */
		$endpoints = apply_filters( 'wpepp_firewall_whitelist_endpoints', $defaults, $request );

		if ( ! is_array( $endpoints ) ) {
			$endpoints = (array) $endpoints;
		}

		// Merge user-configured excluded paths from firewall settings
		$raw_fw = get_option( self::OPT_SETTINGS, '{}' );
		$fw_s   = json_decode( (string) $raw_fw, true );
		if ( ! empty( $fw_s['firewall_excluded_paths'] ) && is_array( $fw_s['firewall_excluded_paths'] ) ) {
			$endpoints = array_merge( $endpoints, $fw_s['firewall_excluded_paths'] );
		}

		foreach ( $endpoints as $endpoint ) {
			$endpoint = trim( strtolower( (string) $endpoint ) );
			if ( '' !== $endpoint && false !== strpos( $uri, $endpoint ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * BASIC MODE — inspect the current request inside WP.
	 */
	public static function run_basic() {
		try {
			$request = WPEPP_FW_Engine::capture_request();

			if ( self::is_exempt( $request ) ) {
				return;
			}

			$rule = WPEPP_FW_Engine::inspect( self::get_rules(), $request );
		} catch ( \Throwable $e ) {
			error_log( '[WPEPP FW] engine error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		if ( null === $rule ) {
			return;
		}

		self::handle_match( $rule, $request );
	}

	/**
	 * Record recent matches (ring buffer, max 12) for admin debugging.
	 *
	 * Makes false positives diagnosable from the Firewall tab instead of
	 * mysterious 403 pages.
	 *
	 * @param string $rule_id Rule ID.
	 * @param bool   $blocked Whether it resulted in a block.
	 * @param string $path    Request path.
	 */
	private static function record_match( $rule_id, $blocked, $path ) {
		$log   = get_transient( 'wpepp_fw_recent' );
		$log   = is_array( $log ) ? $log : [];
		$log[] = [
			'id'      => sanitize_key( (string) $rule_id ),
			'blocked' => (bool) $blocked,
			'path'    => substr( sanitize_text_field( (string) $path ), 0, 190 ),
			'time'    => time(),
		];

		set_transient( 'wpepp_fw_recent', array_slice( $log, -12 ), DAY_IN_SECONDS );
	}

	/**
	 * Checklist item for the security dashboard (Layer 3 integration).
	 *
	 * @param array[] $items Existing items.
	 * @return array[]
	 */
	public static function score_item( $items ) {
		$items[] = [
			'id'       => 'firewall_enabled',
			'label'    => __( 'Firewall enabled', 'wp-edit-password-protected' ),
			'weight'   => 12,
			'category' => 'firewall',
			'done'     => false,
			'route'    => '/firewall',
		];

		return $items;
	}

	/**
	 * Effective settings with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$raw = get_option( self::OPT_SETTINGS, '{}' );
		$s   = json_decode( (string) $raw, true );

		if ( ! is_array( $s ) ) {
			$s = [];
		}

		return [
			'basic_enabled'    => ! isset( $s['basic_enabled'] ) || ! empty( $s['basic_enabled'] ),
			'extended_enabled' => ! empty( $s['extended_enabled'] ),
			'learning_mode'    => ! empty( $s['learning_mode'] ),
		];
	}

	/**
	 * Load + merge effective rules (starter pack + filter extensions).
	 *
	 * Result cached per request; JSON parsed at most once per hour via cache.
	 *
	 * @return array[]
	 */
	public static function get_rules() {
		static $rules = null;

		if ( null !== $rules ) {
			return $rules;
		}

		$rules = wp_cache_get( 'wpepp_fw_rules', 'wpepp' );

		if ( ! is_array( $rules ) ) {
			$file  = WPEPP_PATH . 'assets/firewall/starter-rules.json';
			$rules = [];

			if ( file_exists( $file ) ) {
				$decoded = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local static asset.

				if ( is_array( $decoded ) && ! empty( $decoded['rules'] ) && is_array( $decoded['rules'] ) ) {
					$rules = $decoded['rules'];
				}
			}

			wp_cache_set( 'wpepp_fw_rules', $rules, 'wpepp', HOUR_IN_SECONDS );
		}

		/**
		 * Extend/replace firewall rules (pro rule packs hook here).
		 *
		 * @param array[] $rules Effective rules.
		 */
		$rules = apply_filters( 'wpepp/firewall/rules', $rules ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- documented public API.

		// Drop malformed rows defensively.
		return array_values(
			array_filter(
				is_array( $rules ) ? $rules : [],
				static function ( $r ) {
					return is_array( $r ) && ! empty( $r['id'] ) && ! empty( $r['matchers'] ) && is_array( $r['matchers'] );
				}
			)
		);
	}

	/**
	 * Handle a matched rule — block or log depending on action + learning mode.
	 *
	 * @param array $rule    Matched rule.
	 * @param array $request Request data.
	 */
	public static function handle_match( $rule, $request ) {
		$should_block = ( $rule['action'] ?? 'block' ) === 'block' && ! self::settings()['learning_mode'];

		do_action( 'wpepp/firewall/match', $rule, $request, $should_block ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.

		self::bump_counter( $rule['id'], $should_block ? 'blocked' : 'logged' );
		self::record_match( $rule['id'], $should_block, (string) ( $request['path'] ?? '' ) );

		if ( ! $should_block ) {
			return;
		}

		$rule_id = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $rule['id'] );

		if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::log(
				'firewall_blocked',
				sprintf( 'Firewall blocked request on %s (Rule: %s)', $request['path'] ?? '/', $rule_id ),
				WPEPP_Logger::SEV_CRITICAL,
				[
					'rule'    => $rule_id,
					'path'    => (string) ( $request['path'] ?? '' ),
					'ip'      => (string) ( $request['ip'] ?? '' ),
					'match'   => (string) ( $rule['_match'] ?? '' ),
				]
			);
		}

		status_header( 403 );
		nocache_headers();
		header( 'X-WPEPP-FW: ' . $rule_id );
		wp_die(
			sprintf(
				/* translators: %s: firewall rule identifier */
				esc_html__( 'Your request has been blocked by the site firewall. Rule: %s', 'wp-edit-password-protected' ),
				'<code>' . esc_html( $rule_id ) . '</code>'
			),
			esc_html__( 'Blocked', 'wp-edit-password-protected' ),
			[ 'response' => 403 ]
		);
		exit;
	}

	/**
	 * Batch-increment match counters (25-batch flush like the AI counter).
	 *
	 * @param string $rule_id Rule ID.
	 * @param string $kind    blocked|logged.
	 */
	private static function bump_counter( $rule_id, $kind ) {
		$key = sprintf( 'wpepp_fw_hits_%s_%s_%d', md5( (string) $rule_id ), $kind, (int) ( time() / 3600 ) );

		$n = (int) get_transient( $key );
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		// Cheap total for dashboard widgets.
		$total_bucket = 'wpepp_fw_total_bucket';
		$bucket       = get_transient( $total_bucket );

		if ( ! is_array( $bucket ) ) {
			$bucket = [ 'n' => 0, 'started' => time() ];
		}

		$bucket['n']++;

		if ( $bucket['n'] >= 25 || ( time() - (int) $bucket['started'] ) >= 10 * MINUTE_IN_SECONDS ) {
			update_option( self::COUNTER_KEY, (int) get_option( self::COUNTER_KEY, 0 ) + $bucket['n'], false );
			delete_transient( $total_bucket );
			return;
		}

		set_transient( $total_bucket, $bucket, 10 * MINUTE_IN_SECONDS );
	}

	/* ─── Extended Mode ─── */

	/**
	 * Absolute path of the early-boot runtime directory.
	 *
	 * @return string
	 */
	public static function runtime_dir() {
		return WP_CONTENT_DIR . '/wpepp-fw';
	}

	/**
	 * Whether the extended-mode files are installed and healthy.
	 *
	 * @return bool
	 */
	public static function extended_installed() {
		return file_exists( self::runtime_dir() . '/bootstrap.php' )
			&& ! file_exists( self::runtime_dir() . '/bootstrap.php.disabled' );
	}

	/**
	 * Enable extended mode: write runtime files + prepend directives.
	 *
	 * @return true|\WP_Error
	 */
	public static function enable_extended() {
		global $is_apache;

		try {
			$dir = self::runtime_dir();

			if ( ! wp_mkdir_p( $dir ) ) {
				return new \WP_Error( 'wpepp_fw_dir', __( 'Cannot create the firewall runtime directory.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
			}

			// 1. Rules snapshot (plain PHP include — no eval, fast parse).
			$rules_php = "<?php\n/* Generated by WPEPP — do not edit. */\nreturn " . var_export( self::get_rules(), true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			file_put_contents( "{$dir}/rules.php", $rules_php ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			// 2. Early bootstrap.
			$engine_path = WPEPP_PATH . 'includes/core/class-fw-engine.php';
			$ip_path     = WPEPP_PATH . 'includes/core/class-ip.php';

			$bootstrap = <<<'PHP'
<?php
/**
 * WPEPP Firewall early bootstrap (generated). Fail-safe by design:
 * any fatal renames this file so the next request runs unprotected
 * instead of breaking the site.
 */

if ( ! defined( 'WPEPP_FW_BOOT' ) ) {
	define( 'WPEPP_FW_BOOT', __DIR__ );
}

// Early boot has no WordPress yet: give the engine's direct-access guard
// the ABSPATH it expects (best-effort detection of the WP root).
if ( ! defined( 'ABSPATH' ) ) {
	foreach ( array( dirname( __DIR__, 2 ), dirname( __DIR__ ) ) as $__fw_root ) {
		if ( file_exists( rtrim( $__fw_root, '/\\' ) . '/wp-config.php' ) ) {
			define( 'ABSPATH', rtrim( $__fw_root, '/\\' ) . '/' );
			break;
		}
	}
}

$__fw_self      = __FILE__;
$__fw_disabled  = $__fw_self . '.disabled';
$__fw_settings  = __DIR__ . '/settings.json';
$__fw_learning  = false;

try {

	if ( file_exists( $__fw_disabled ) || ! is_readable( $__fw_self ) ) {
		return; // Someone disabled us or we are mid-write.
	}

	// Refuse to run during CLI, cron, or WP-CLI health checks.
	if ( PHP_SAPI === 'cli' || defined( 'WP_CLI' ) || isset( $_SERVER['argv'] ) && in_array( 'wp', (array) $_SERVER['argv'], true ) ) {
		return;
	}

	// Emergency safe mode check:
	if ( ( defined( 'WPEPP_SAFE_MODE' ) && WPEPP_SAFE_MODE )
		|| file_exists( dirname( __DIR__ ) . '/wpepp-safe-mode.txt' )
		|| file_exists( dirname( __DIR__ ) . '/wpepp-safe-mode' )
		|| ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wpepp-safe-mode.txt' ) ) ) {
		return;
	}

	$__fw_settings_data = is_readable( $__fw_settings )
		? json_decode( (string) file_get_contents( $__fw_settings ), true )
		: [];
	$__fw_learning = ! empty( $__fw_settings_data['learning_mode'] );

	require_once %IP_CLASS%;
	require_once %ENGINE%;

	$__fw_request = WPEPP_FW_Engine::capture_request();

	// Exempt internal/administrative endpoints from early bootstrap firewall.
	if ( '' === $__fw_request['ip']
		|| preg_match( '#^/(wp-admin|wp-json|wp-login\\.php|xmlrpc\\.php|wp-cron\\.php|feed)#i', $__fw_request['path'] )
		|| preg_match( '#(admin-ajax|admin-post)\\.php$#i', $__fw_request['path'] ) ) {
		return;
	}

	$__fw_rules = require __DIR__ . '/rules.php';
	$__fw_rule  = WPEPP_FW_Engine::inspect( is_array( $__fw_rules ) ? $__fw_rules : [], $__fw_request );

	if ( null !== $__fw_rule && ( $__fw_rule['action'] ?? 'block' ) === 'block' && ! $__fw_learning ) {

		$__fw_id = preg_replace( '/[^a-z0-9_-]/i', '', (string) $__fw_rule['id'] );

		http_response_code( 403 );
		header( 'X-WPEPP-FW: ' . $__fw_id );
		header( 'Content-Type: text/html; charset=utf-8' );

		echo '<!DOCTYPE html><html><head><meta name="robots" content="noindex"><title>403 Blocked</title></head>'
			. '<body style="font-family:sans-serif;text-align:center;padding:60px 20px;">'
			. '<h1>403 &mdash; Blocked</h1>'
			. '<p>Your request has been blocked by the site firewall.</p>'
			. '<p style="color:#888;font-size:12px;">Rule: ' . htmlspecialchars( $__fw_id ) . '</p>'
			. '</body></html>';
		exit;
	}
} catch ( \Throwable $__fw_e ) {

	// Fail-safe: never take the site down. Disable ourselves quietly.
	@rename( $__fw_self, $__fw_disabled ); // phpcs:ignore
	error_log( '[WPEPP FW] extended bootstrap disabled after error: ' . $__fw_e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	return;
}
PHP;

			$bootstrap = str_replace(
				[ '%IP_CLASS%', '%ENGINE%' ],
				[ var_export( $ip_path, true ), var_export( $engine_path, true ) ], // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				$bootstrap
			);

			file_put_contents( "{$dir}/bootstrap.php", $bootstrap ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			// 3. settings marker (learning flag readable pre-boot).
			file_put_contents( "{$dir}/settings.json", wp_json_encode( [ 'learning_mode' => self::settings()['learning_mode'] ] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			// 4. Prepend directives.
			$abs = "{$dir}/bootstrap.php";
			self::write_user_ini( $abs );
			self::write_htaccess( $abs );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wpepp_fw_enable_failed', $e->getMessage(), [ 'status' => 500 ] );
		}

		return true;
	}

	/**
	 * Disable extended mode: remove directives + rename bootstrap away.
	 */
	public static function disable_extended() {
		$abs = self::runtime_dir() . '/bootstrap.php';

		self::write_user_ini( null );
		self::write_htaccess( null );

		if ( file_exists( $abs ) ) {
			@rename( $abs, $abs . '.disabled' ); // phpcs:ignore
		}
	}

	/**
	 * Write/remove the .user.ini auto_prepend directive (CGI/FPM).
	 *
	 * @param string|null $path Bootstrap path or null to remove.
	 */
	private static function write_user_ini( $path ) {
		$file = ABSPATH . '.user.ini';

		$content = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents -- no direct FS creds context.

		$content = preg_replace(
			'/;?WPEPP-FW-BEGIN.*?;?WPEPP-FW-END[\r\n]?/s',
			'',
			$content
		);

		if ( null !== $path ) {
			$content .= "\n;WPEPP-FW-BEGIN\nauto_prepend_file = \"{$path}\"\n;WPEPP-FW-END\n";
		}

		$content = trim( $content ) . "\n";
		@file_put_contents( $file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions, PluginCheck.CodeAnalysis.WriteFile.ABSPATHDetected -- WAF bootstrap writes to .user.ini.
	}

	/**
	 * Write/remove mod_php prepend block in .htaccess.
	 *
	 * @param string|null $path Bootstrap path or null to remove.
	 */
	private static function write_htaccess( $path ) {
		$file = ABSPATH . '.htaccess';

		if ( ! file_exists( $file ) && null === $path ) {
			return;
		}

		$content = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

		$content = preg_replace(
			'/# BEGIN WPEPP-FW.*?# END WPEPP-FW\s*/s',
			'',
			$content
		);

		if ( null !== $path && false === strpos( $content, 'auto_prepend_file' ) ) {
			$content .= "\n# BEGIN WPEPP-FW\n"
				. "<IfModule mod_php7.c>\n php_value auto_prepend_file \"{$path}\"\n</IfModule>\n"
				. "<IfModule mod_php.c>\n php_value auto_prepend_file \"{$path}\"\n</IfModule>\n"
				. "# END WPEPP-FW\n";
		}

		@file_put_contents( $file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions, PluginCheck.CodeAnalysis.WriteFile.ABSPATHDetected -- WAF bootstrap writes to .htaccess.
	}
}
