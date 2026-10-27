<?php
/**
 * Security features â€” login limiter, honeypot, XML-RPC, REST enum, reCAPTCHA, custom login URL, logging.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Security
 */
class WPEPP_Security {

	/**
	 * Initialise security features based on saved settings.
	 *
	 * @param array $settings Decoded security settings array.
	 */
	public static function init( $settings ) {
		/* ─── Emergency Safe Mode Notice ─── */
		add_action( 'admin_notices', [ __CLASS__, 'render_safe_mode_notice' ] );

		/* ─── Block Center (FREE) ─── */
		add_action( 'init', [ __CLASS__, 'check_blocked_table' ], 1 );
		WPEPP_Blocks::schedule_cron();

		/* â”€â”€â”€ Self-service unlock (FREE) â”€â”€â”€ */
		if ( ! empty( $settings['login_limit_enabled'] ) ) {
			add_action( 'init', [ __CLASS__, 'handle_unlock_request' ], 2 );
			add_action( 'init', [ __CLASS__, 'handle_unlock_token' ], 2 );
			add_action( 'login_form', [ __CLASS__, 'render_unlock_link' ] );
			add_filter( 'login_message', [ __CLASS__, 'render_unlock_notice' ] );
		}

		/* â”€â”€â”€ Rate Limiting (FREE) â”€â”€â”€ */
		if ( ! empty( $settings['rate_limit_enabled'] ) ) {
			WPEPP_Rate_Limit::init();
		}

		/* â”€â”€â”€ Login limiter (free) â”€â”€ */
		if ( ! empty( $settings['login_limit_enabled'] ) ) {
			add_filter( 'authenticate', [ __CLASS__, 'check_login_lockout' ], 30, 3 );
			add_action( 'wp_login_failed', [ __CLASS__, 'record_failed_login' ] );
			add_action( 'wp_login', [ __CLASS__, 'record_successful_login' ], 10, 2 );
		}

		// Login log retention cron (B18) â€” runs whenever logging is active.
		if ( ! empty( $settings['login_limit_enabled'] ) || ! empty( $settings['login_log_enabled'] ) ) {
			if ( ! wp_next_scheduled( 'wpepp_prune_login_log' ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpepp_prune_login_log' );
			}
			add_action( 'wpepp_prune_login_log', [ __CLASS__, 'prune_login_log' ] );
		}

		// Honeypot field (free).
		if ( ! empty( $settings['honeypot_enabled'] ) ) {
			add_action( 'login_form', [ __CLASS__, 'render_honeypot_field' ] );
			add_filter( 'authenticate', [ __CLASS__, 'check_honeypot' ], 25, 1 );
		}

		// Disable XML-RPC (free).
		if ( ! empty( $settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', [ __CLASS__, 'remove_xmlrpc_header' ] );
		}

		// Hide WP version (free).
		if ( ! empty( $settings['hide_wp_version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}

		// Disable REST user enumeration (free).
		if ( ! empty( $settings['disable_rest_users'] ) ) {
			add_filter( 'rest_endpoints', [ __CLASS__, 'disable_user_endpoints' ] );
		}

		/* â”€â”€â”€ CAPTCHA (Pro) â€” unified provider layer â”€â”€â”€ */
		if ( wpepp_has_pro_check()
			&& ! empty( $settings['captcha_enabled'] ?? $settings['recaptcha_enabled'] )
			&& '' !== WPEPP_Captcha::provider() ) {

			add_action( 'login_enqueue_scripts', [ 'WPEPP_Captcha', 'render_widget' ] ); // scripts only enqueue on login screen.
			add_action( 'login_form', [ __CLASS__, 'render_captcha_widget' ] );
			add_filter( 'authenticate', [ __CLASS__, 'verify_captcha_on_login' ], 20, 1 );

			if ( ! empty( $settings['reg_recaptcha_enabled'] ?? $settings['reg_captcha_enabled'] ) ) {
				add_action( 'register_form', [ __CLASS__, 'render_captcha_widget' ] );
				add_filter( 'registration_errors', [ __CLASS__, 'verify_captcha_on_register' ], 10, 3 );
			}
		}

		/* ─── Custom Login URL (Free) ─── */
		if ( ! empty( $settings['custom_login_url'] ) ) {
			add_action( 'init', [ __CLASS__, 'handle_custom_login_url' ] );
			add_filter( 'site_url', [ __CLASS__, 'filter_login_url' ], 10, 4 );
			add_filter( 'wp_redirect', [ __CLASS__, 'filter_login_redirect' ], 10, 2 );
		}

		/* â”€â”€â”€ Login logging. â”€â”€â”€ */
		if ( wpepp_has_pro_check() && ! empty( $settings['login_log_enabled'] ) ) {
			if ( empty( $settings['login_limit_enabled'] ) ) {
				add_action( 'wp_login_failed', [ __CLASS__, 'record_failed_login' ] );
				add_action( 'wp_login', [ __CLASS__, 'record_successful_login' ], 10, 2 );
			}
		}

		// Hide login page (free).
		if ( ! empty( $settings['hide_login_page'] ) ) {
			add_action( 'init', [ __CLASS__, 'handle_hide_login_page' ], 1 );
		}

		// After login redirect (Pro).
		if ( wpepp_has_pro_check() && ! empty( $settings['after_login_redirect'] ) ) {
			add_filter( 'login_redirect', [ __CLASS__, 'filter_after_login_redirect' ], 99, 3 );
			add_filter( 'allowed_redirect_hosts', [ __CLASS__, 'allow_after_login_redirect_host' ] );
		}

		// Auto-login token handler (free).
		add_action( 'init', [ __CLASS__, 'handle_auto_login_token' ] );

		/* â”€â”€â”€ IP Management (Pro) â”€â”€â”€ */
		if ( wpepp_has_pro_check() ) {
			if ( ! empty( $settings['ip_blocklist'] ) || ! empty( $settings['ip_allowlist'] ) ) {
				add_action( 'init', [ __CLASS__, 'check_ip_blocklist' ], 1 );
				add_filter( 'authenticate', [ __CLASS__, 'check_ip_blocklist_on_login' ], 5, 3 );
			}
		}

		/* â”€â”€â”€ AI Crawler Blocker (Free) â”€â”€â”€ */
		if ( ! empty( $settings['ai_crawler_blocker_enabled'] ) ) {
			// Block via robots.txt.
			add_filter( 'robots_txt', [ __CLASS__, 'filter_robots_txt' ], 100, 2 );

			// Block via HTTP user-agent (403).
			if ( ! empty( $settings['ai_crawler_block_ua'] ) ) {
				add_action( 'init', [ __CLASS__, 'block_ai_crawlers_by_ua' ], 0 );
			}
		}

		/* â”€â”€â”€ Two-Factor Authentication (FREE as of 3.0 â€” tier move) â”€â”€â”€ */
		if ( ! empty( $settings['two_factor_enabled'] ) ) {
			add_action( 'login_form', [ __CLASS__, 'render_2fa_field' ] );
			add_filter( 'authenticate', [ __CLASS__, 'verify_2fa_on_login' ], 99, 3 );
			add_action( 'show_user_profile', [ __CLASS__, 'render_2fa_user_profile' ] );
			add_action( 'edit_user_profile', [ __CLASS__, 'render_2fa_user_profile' ] );
			add_action( 'personal_options_update', [ __CLASS__, 'save_2fa_user_profile' ] );
			add_action( 'edit_user_profile_update', [ __CLASS__, 'save_2fa_user_profile' ] );

			// Bundled QR renderer â€” secrets never leave the browser (B3).
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_qr_library' ] );
		}

		/* â”€â”€â”€ Username Shield (FREE) â”€â”€â”€ */
		if ( ! empty( $settings['username_shield_enabled'] ) ) {
			WPEPP_Username_Shield::init( $settings );
		}

		/* â”€â”€â”€ Registration Protection â”€â”€â”€ */

		// Registration honeypot (free).
		if ( ! empty( $settings['reg_honeypot_enabled'] ) ) {
			add_action( 'register_form', [ __CLASS__, 'render_reg_honeypot_field' ] );
			add_filter( 'registration_errors', [ __CLASS__, 'check_reg_honeypot' ], 10, 3 );
		}

		// Registration rate limiter (free).
		if ( ! empty( $settings['reg_rate_limit_enabled'] ) ) {
			add_filter( 'registration_errors', [ __CLASS__, 'check_reg_rate_limit' ], 10, 3 );
		}

		// Pro-only registration features.
		if ( wpepp_has_pro_check() ) {
			// CAPTCHA on registration is wired by the unified provider layer above.

			// Disposable email blocker.
			if ( ! empty( $settings['reg_block_disposable_emails'] ) ) {
				add_filter( 'registration_errors', [ __CLASS__, 'check_disposable_email' ], 10, 3 );
			}

			// Email domain whitelist/blacklist.
			if ( ! empty( $settings['reg_email_domain_mode'] ) && 'off' !== $settings['reg_email_domain_mode'] ) {
				add_filter( 'registration_errors', [ __CLASS__, 'check_email_domain_list' ], 10, 3 );
			}

			// Admin approval for new users.
			if ( ! empty( $settings['reg_admin_approval'] ) ) {
				add_action( 'user_register', [ __CLASS__, 'set_pending_role' ] );
				add_filter( 'wp_login_errors', [ __CLASS__, 'show_pending_message' ] );
				add_filter( 'authenticate', [ __CLASS__, 'block_pending_users' ], 99, 2 );
			}
		}
	}

	/* â”€â”€â”€ Block Center â”€â”€â”€ */

	/**
	 * Front-end enforcement of wpepp_blocks entries (init prio 1).
	 */
	public static function check_blocked_table() {
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		if ( self::is_safe_mode() ) {
			return;
		}

		$ip = self::get_client_ip();

		$block = WPEPP_Blocks::find_active_block( $ip );

		if ( null === $block || ! is_object( $block ) ) {
			return;
		}

		WPEPP_Blocks::bump_hit_count( (int) $block->id );

		status_header( 403 );
		nocache_headers();
		wp_die(
			esc_html__( 'Your IP address has been blocked from accessing this site.', 'wp-edit-password-protected' ),
			esc_html__( 'Access Denied', 'wp-edit-password-protected' ),
			[ 'response' => 403 ]
		);
	}

	/* â”€â”€â”€ Self-service unlock â”€â”€â”€ */

	/**
	 * Render the "locked out?" link under the login form.
	 */
	public static function render_unlock_link() {
		$s = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );

		if ( empty( $s['unlock_emails_enabled'] ) || ! self::is_ip_locked( self::get_client_ip() ) ) {
			return;
		}

		$target = admin_url( 'admin-post.php' );

		?>
		<div style="margin-top:12px;text-align:center;">
			<form method="post" action="<?php echo esc_url( $target ); ?>">
				<?php wp_nonce_field( 'wpepp_unlock', 'wpepp_unlock_nonce' ); ?>
				<input type="hidden" name="action" value="wpepp_send_unlock" />
				<label for="wpepp-unlock-email" class="screen-reader-text"><?php esc_html_e( 'Your email address', 'wp-edit-password-protected' ); ?></label>
				<input type="email" id="wpepp-unlock-email" name="wpepp_unlock_email" size="24"
					placeholder="<?php esc_attr_e( 'Your email address', 'wp-edit-password-protected' ); ?>" required />
				<button type="submit" class="button button-secondary">
					<?php esc_html_e( 'Email me an unlock link', 'wp-edit-password-protected' ); ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Show the confirmation notice after a successful unlock-email request.
	 *
	 * @param string|\WP_Error $message Login messages.
	 * @return string
	 */
	public static function render_unlock_notice( $message ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag set by our own redirect.
		if ( isset( $_GET['wpepp_unlock_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['wpepp_unlock_sent'] ) ) ) {
			$message .= '<p class="message">' . esc_html__( 'If that address matches, an unlock link has been sent. Check your inbox.', 'wp-edit-password-protected' ) . '</p>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wpepp_unlocked'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['wpepp_unlocked'] ) ) ) {
			$message .= '<p class="message">' . esc_html__( 'You have been unlocked. You may log in now.', 'wp-edit-password-protected' ) . '</p>';
		}

		return $message;
	}

	/**
	 * Handle "send unlock link" submissions (admin-post.php).
	 */
	public static function handle_unlock_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below.
		if ( ! isset( $_POST['wpepp_unlock_nonce'], $_POST['action'] )
			|| 'wpepp_send_unlock' !== sanitize_key( wp_unslash( $_POST['action'] ) ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_unlock_nonce'] ) ), 'wpepp_unlock' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', 'wp-edit-password-protected' ),
				esc_html__( 'Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		$email = isset( $_POST['wpepp_unlock_email'] )
			? sanitize_email( wp_unslash( $_POST['wpepp_unlock_email'] ) )
			: '';

		$redirect = add_query_arg( 'wpepp_unlock_sent', '1', wp_login_url() );

		$s = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );

		if ( empty( $s['unlock_emails_enabled'] ) || ! is_email( $email ) ) {
			wp_safe_redirect( $redirect );
			exit;
		}

		// Only the configured alert/admin address may request unlocks.
		$allowed = array_filter( [
			get_option( 'admin_email', '' ),
			is_array( $s ) ? ( $s['alert_email'] ?? '' ) : '',
		] );

		if ( ! in_array( strtolower( $email ), array_map( 'strtolower', $allowed ), true ) ) {
			wp_safe_redirect( $redirect ); // Same response â€” no oracle.
			exit;
		}

		// Rate limit: 3 requests per hour per IP.
		$rl_key = 'wpepp_unlock_rl_' . md5( self::get_client_ip() );
		$n      = (int) get_transient( $rl_key );

		if ( $n >= 3 ) {
			wp_safe_redirect( $redirect );
			exit;
		}

		set_transient( $rl_key, $n + 1, HOUR_IN_SECONDS );

		// Single-use token, valid 15 minutes.
		$token = wp_generate_password( 32, false, false );
		set_transient( 'wpepp_unlock_token_' . $token, self::get_client_ip(), 15 * MINUTE_IN_SECONDS );

		$link = add_query_arg( 'wpepp_unlock', $token, wp_login_url() );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Unlock your access', 'wp-edit-password-protected' ),
			get_bloginfo( 'name' )
		);

		$text = sprintf(
			/* translators: %s: unlock link */
			__( "Someone requested an unlock link for your IP on %1\$s.\n\nOpen this single-use link within 15 minutes:\n%2\$s\n\nIf you did not request this, ignore this email.", 'wp-edit-password-protected' ),
			get_bloginfo( 'name' ),
			$link
		);

		wp_mail( $email, $subject, $text );

		do_action( 'wpepp/unlock/link_sent', $email, self::get_client_ip() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Consume a unlock token â€” clears failed rows for the requesting IP.
	 */
	public static function handle_unlock_token() {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bearer token validated below.
		$token = isset( $_GET['wpepp_unlock'] ) ? sanitize_text_field( wp_unslash( $_GET['wpepp_unlock'] ) ) : '';

		if ( '' === $token || strlen( $token ) > 64 || false !== strpos( $token, '&' ) ) {
			return;
		}

		$key   = 'wpepp_unlock_token_' . $token;
		$value = get_transient( $key );

		delete_transient( $key ); // Single use regardless of outcome.

		if ( false === $value || '' === $value ) {
			return;
		}

		$ip = (string) $value;

		if ( self::login_log_table_exists() ) {
			$table = $wpdb->prefix . 'wpepp_login_log';

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM %i WHERE ip_address = %s AND status = 'failed'",
					$table,
					sanitize_text_field( $ip )
				)
			);
		}

		// Clear limiter guard transients so the next attempt starts fresh.
		delete_transient( 'wpepp_lockout_row_' . md5( $ip ) );

		wp_cache_delete( 'wpepp_dashboard_stats', 'wpepp' );

		do_action( 'wpepp/unlock/consumed', $ip ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.

		wp_safe_redirect( add_query_arg( 'wpepp_unlocked', '1', wp_login_url() ) );
		exit;
	}

	/* â”€â”€â”€ Login Limiter â”€â”€â”€ */

	/**
	 * Check if the IP is locked out before authenticating.
	 *
	 * @param \WP_User|\WP_Error|null $user     User or error.
	 * @param string                  $username  Username.
	 * @param string                  $password  Password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function check_login_lockout( $user, $username, $password ) {
		if ( empty( $username ) || self::is_safe_mode() ) {
			return $user;
		}

		$ip = self::get_client_ip();
		if ( self::is_ip_locked( $ip ) ) {
			self::log_lockout_once( $ip, $username );
			return new \WP_Error(
				'wpepp_lockout',
				__( 'Too many failed login attempts. Please try again later.', 'wp-edit-password-protected' )
			);
		}

		return $user;
	}

	/**
	 * Record a single 'lockout' log row per IP per lockout window so the
	 * dashboard can distinguish lockouts from plain failures (B11).
	 *
	 * @param string $ip       Locked IP.
	 * @param string $username Attempted username.
	 */
	private static function log_lockout_once( $ip, $username = '' ) {
		$guard = 'wpepp_lockout_row_' . md5( (string) $ip );

		if ( get_transient( $guard ) ) {
			return;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = is_array( json_decode( $raw, true ) ) ? json_decode( $raw, true ) : [];
		$duration = max( 1, absint( $settings['lockout_duration'] ?? 15 ) );

		set_transient( $guard, 1, $duration * MINUTE_IN_SECONDS );

		self::log_attempt( sanitize_user( $username ), 'lockout' );

		WPEPP_Logger::log(
			'lockout',
			sprintf( 'Locked out %s after repeated failures (%s)', (string) $ip, $username ),
			WPEPP_Logger::SEV_WARN,
			[ 'duration_min' => $duration ]
		);

		if ( class_exists( 'WPEPP_Alerts' ) ) {
			WPEPP_Alerts::on_lockout( (string) $ip, (string) $username );
		}
	}

	/**
	 * Daily cron: prune old rows from the login log table (B18).
	 */
	public static function prune_login_log() {
		global $wpdb;

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = is_array( json_decode( $raw, true ) ) ? json_decode( $raw, true ) : [];
		$retention = absint( $settings['login_log_retention_days'] ?? 30 );

		if ( $retention <= 0 ) {
			return; // 0 = keep forever.
		}

		if ( ! self::login_log_table_exists() ) {
			return;
		}

		$table = $wpdb->prefix . 'wpepp_login_log';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $retention * DAY_IN_SECONDS ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'DELETE FROM %i WHERE created_at < %s',
				$table,
				$cutoff
			)
		);

		wp_cache_delete( 'wpepp_login_log_exists', 'wpepp' );
	}

	/**
	 * Record a failed login attempt.
	 *
	 * @param string $username Username attempted.
	 */
	public static function record_failed_login( $username ) {
		self::log_attempt( sanitize_user( $username ), 'failed' );
	}

	/**
	 * Record a successful login.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 */
	public static function record_successful_login( $user_login, $user ) {
		self::log_attempt( sanitize_user( $user_login ), 'success' );
	}

	/**
	 * Insert a login log entry.
	 *
	 * @param string $username Login name.
	 * @param string $status   'success', 'failed', or 'lockout'.
	 */
	private static function log_attempt( $username, $status ) {
		global $wpdb;

		if ( ! in_array( $status, [ 'success', 'failed', 'lockout' ], true ) ) {
			return;
		}

		$table = $wpdb->prefix . 'wpepp_login_log';

		if ( ! self::login_log_table_exists( true ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'user_login' => $username,
				'ip_address' => self::get_client_ip(),
				'status'     => $status,
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
					? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
					: '',
			],
			[ '%s', '%s', '%s', '%s' ]
		);

		// Keep dashboard counters fresh under persistent object caches.
		wp_cache_delete( 'wpepp_dashboard_stats', 'wpepp' );
		wp_cache_delete( 'wpepp_login_log_100', 'wpepp' );
		wp_cache_delete( 'wpepp_login_log_50', 'wpepp' );
	}

	/**
	 * Emergency Safe Mode Check.
	 *
	 * Allows administrators locked out of their site to temporarily bypass all
	 * login lockouts, rate limiting, and firewall blocks by:
	 * 1. Defining constant WPEPP_SAFE_MODE in wp-config.php, OR
	 * 2. Creating an empty file named wpepp-safe-mode.txt (or wpepp-safe-mode) in wp-content/ or ABSPATH.
	 *
	 * In Safe Mode:
	 * - IP blocking and login limiter lockouts are bypassed.
	 * - Firewall inspection is bypassed.
	 *
	 * @param bool $force_refresh Force re-check bypassing static request cache.
	 * @return bool
	 */
	public static function is_safe_mode( $force_refresh = false ) {
		static $is_safe = null;
		if ( null !== $is_safe && ! $force_refresh ) {
			return $is_safe;
		}

		if ( defined( 'WPEPP_SAFE_MODE' ) && WPEPP_SAFE_MODE ) {
			$is_safe = true;
			return true;
		}

		$files = [
			( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' ) . '/wpepp-safe-mode.txt',
			( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' ) . '/wpepp-safe-mode',
			ABSPATH . 'wpepp-safe-mode.txt',
		];

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				$is_safe = true;
				return true;
			}
		}

		$is_safe = false;
		return false;
	}

	/**
	 * Display an emergency Safe Mode alert in the admin dashboard.
	 */
	public static function render_safe_mode_notice() {
		if ( ! self::is_safe_mode() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-warning" style="border-left-color: #d63638; background: #fff8f8; padding: 12px 14px;">' .
			'<p style="margin: 0; font-size: 13px;">' .
			'<strong>' . esc_html__( '⚠️ WPEPP Emergency Safe Mode is Active!', 'wp-edit-password-protected' ) . '</strong> ' .
			esc_html__( 'All firewall, IP blocking, and login lockout restrictions are temporarily bypassed. Delete wpepp-safe-mode.txt (or unset WPEPP_SAFE_MODE) to restore normal security protection.', 'wp-edit-password-protected' ) .
			'</p></div>';
	}

	/**
	 * Check if an IP is currently locked out.
	 *
	 * Uses per-request memory caching and respects emergency safe mode.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	public static function is_ip_locked( $ip ) {
		if ( self::is_safe_mode() ) {
			return false;
		}

		static $locked_cache = [];
		if ( isset( $locked_cache[ $ip ] ) ) {
			return $locked_cache[ $ip ];
		}

		global $wpdb;

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		$max      = absint( $settings['max_attempts'] ?? 5 );
		$duration = absint( $settings['lockout_duration'] ?? 15 );

		$table = $wpdb->prefix . 'wpepp_login_log';

		if ( ! self::login_log_table_exists( true ) ) {
			$locked_cache[ $ip ] = false;
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				 WHERE ip_address = %s AND status = 'failed'
				 AND created_at > DATE_SUB( NOW(), INTERVAL %d MINUTE )",
				$table,
				sanitize_text_field( (string) $ip ),
				$duration
			)
		);

		$locked_cache[ $ip ] = ( $count >= $max );
		return $locked_cache[ $ip ];
	}

	/**
	 * Check whether the login log table exists, optionally creating plugin tables.
	 *
	 * @param bool $create Whether to try creating missing tables.
	 * @return bool
	 */
	private static function login_log_table_exists( $create = false ) {
		global $wpdb;

		$table        = $wpdb->prefix . 'wpepp_login_log';
		$cache_key    = 'wpepp_login_log_exists';
		$table_exists = wp_cache_get( $cache_key, 'wpepp' );

		if ( false === $table_exists ) {
			$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			wp_cache_set( $cache_key, $table_exists ?: 'no', 'wpepp' );
		}

		if ( $table_exists && 'no' !== $table_exists ) {
			return true;
		}

		if ( ! $create || ! class_exists( 'WPEPP_Activator' ) ) {
			return false;
		}

		WPEPP_Activator::create_tables();
		wp_cache_delete( $cache_key, 'wpepp' );

		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
		wp_cache_set( $cache_key, $table_exists ?: 'no', 'wpepp' );

		return (bool) $table_exists;
	}

	/**
	 * Get client IP.
	 *
	 * Delegates to the core IP service: forwarded headers are honoured ONLY
	 * when REMOTE_ADDR is a registered trusted proxy (spoof-proof default).
	 * Register proxies via security setting `trusted_proxies` or the
	 * `wpepp_trusted_proxies` filter.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		return WPEPP_Core_Ip::get_client_ip();
	}

	/* â”€â”€â”€ Honeypot â”€â”€â”€ */

	/**
	 * Render an invisible honeypot field on the login form.
	 */
	public static function render_honeypot_field() {
		echo '<p style="position:absolute;left:-9999px;" aria-hidden="true">';
		echo '<label for="wpepp_hp_field">' . esc_html__( 'Leave empty', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="wpepp_hp_field" id="wpepp_hp_field" value="" tabindex="-1" autocomplete="off">';
		echo '</p>';
	}

	/**
	 * Check the honeypot field â€” if filled, reject.
	 *
	 * @param \WP_User|\WP_Error|null $user Auth result.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function check_honeypot( $user ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Login form uses WP's own nonce.
		if ( isset( $_POST['wpepp_hp_field'] ) && '' !== $_POST['wpepp_hp_field'] ) {
			return new \WP_Error(
				'wpepp_honeypot',
				__( 'Authentication failed.', 'wp-edit-password-protected' )
			);
		}
		return $user;
	}

	/* â”€â”€â”€ XML-RPC â”€â”€â”€ */

	/**
	 * Remove X-Pingback header.
	 *
	 * @param array $headers HTTP headers.
	 * @return array
	 */
	public static function remove_xmlrpc_header( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/* â”€â”€â”€ REST User Enumeration â”€â”€â”€ */

	/**
	 * Remove /wp/v2/users list/detail endpoints for non-admins while keeping
	 * `/wp/v2/users/me` functional for authenticated subscribers (B14).
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public static function disable_user_endpoints( $endpoints ) {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}

		foreach ( $endpoints as $route => $data ) {
			// Keep the /users/me endpoint â€” plugins/themes and app flows rely on it.
			if ( preg_match( '#^/wp/v2/users/me$#', $route ) ) {
				continue;
			}

			if ( preg_match( '#/wp/v2/users#', $route ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Enqueue the bundled QR code library on profile screens.
	 *
	 * Loaded only where 2FA setup can render â€” never on frontend.
	 */
	public static function enqueue_qr_library( $hook ) {
		if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
			return;
		}

		wp_enqueue_script(
			'wpepp-qrcode',
			WPEPP_URL . '/assets/js/qrcode.min.js',
			[],
			'1.0.0',
			true
		);
	}

	/* â”€â”€â”€ CAPTCHA â€” unified provider delegates â”€â”€â”€ */

	/**
	 * Render the active captcha widget on a public form.
	 */
	public static function render_captcha_widget() {
		WPEPP_Captcha::render_widget();
	}

	/**
	 * Verify the login-form challenge.
	 *
	 * @param \WP_User|\WP_Error|null $user Auth result.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function verify_captcha_on_login( $user ) {
		$result = WPEPP_Captcha::verify();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $user;
	}

	/**
	 * Verify the registration-form challenge.
	 *
	 * @param \WP_Error $errors             Errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email.
	 * @return \WP_Error
	 */
	public static function verify_captcha_on_register( $errors, $sanitized_user_login, $user_email ) {
		unset( $sanitized_user_login, $user_email );

		$result = WPEPP_Captcha::verify();

		if ( is_wp_error( $result ) ) {
			$errors->add(
				'wpepp_reg_captcha',
				esc_html( $result->get_error_message() )
			);
		}

		return $errors;
	}

	/**
	 * Legacy reCAPTCHA render â€” deprecated delegate to the unified layer.
	 *
	 * @deprecated 3.1.0 Use WPEPP_Captcha::render_widget().
	 */
	public static function render_recaptcha() {
		WPEPP_Captcha::render_widget();
	}

	/**
	 * Legacy enqueue â€” deprecated no-op; scripts are handled by the provider.
	 *
	 * @deprecated 3.1.0
	 */
	public static function enqueue_recaptcha() {
		// Intentionally empty â€” WPEPP_Captcha enqueues per-provider.
	}

	/**
	 * Legacy verification â€” delegates to the unified layer.
	 *
	 * @deprecated 3.1.0 Use WPEPP_Captcha::verify().
	 *
	 * @param \WP_User|\WP_Error|null $user Auth result.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function verify_recaptcha( $user ) {
		$result = WPEPP_Captcha::verify();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $user;
	}

	/* â”€â”€â”€ Custom Login URL (Pro) â”€â”€â”€ */

	/**
	 * Cookie name for the login-access challenge.
	 *
	 * @var string
	 */
	const GATE_COOKIE = 'wpepp_gate';

	/**
	 * Handle custom login URL â€” redirect default wp-login.php to 404.
	 */
	public static function handle_custom_login_url() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$slug     = sanitize_title( $settings['custom_login_url'] ?? '' );

		if ( empty( $slug ) ) {
			return;
		}

		// Handle the custom slug.
		$request = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		$path = trim( wp_parse_url( $request, PHP_URL_PATH ), '/' );

		// Strip the home path prefix (e.g. 'shop/') for subdirectory installs.
		$home_path = trim( wp_parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
		if ( '' !== $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
			$path = substr( $path, strlen( $home_path ) + 1 );
		}

		// If visiting the custom login slug, show login page.
		if ( $path === $slug ) {
			self::send_gate_cookie();

			// Initialize variables that wp-login.php expects.
			global $user_login, $error, $interim_login, $action;
			$user_login   = '';
			$error        = '';
			$interim_login = false;
			$action       = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			require_once ABSPATH . 'wp-login.php';
			exit;
		}

		// Block direct access to wp-login.php (except gated POST / required actions).
		if ( 'wp-login.php' === basename( $path ) ) {
			if ( self::allow_wp_login_request() ) {
				return;
			}

			// wp-login.php never reaches template_redirect â€” die here.
			status_header( 404 );
			nocache_headers();
			wp_die(
				esc_html__( 'Page not found.', 'wp-edit-password-protected' ),
				esc_html__( '404 Not Found', 'wp-edit-password-protected' ),
				[ 'response' => 404 ]
			);
		}
	}

	/**
	 * Whether a request hitting wp-login.php directly should be allowed while
	 * the login page is hidden/renamed (B6).
	 *
	 * Rules:
	 *   - Required workflow actions (postpass/logout/reset/etc.) always pass.
	 *   - Plain POST logins must carry the challenge cookie issued on a prior
	 *     visit to the real login URL â€” blocks off-site brute force bots.
	 *   - Everything else falls through to the caller's 404 handling.
	 *
	 * @return bool True when the caller must NOT 404 this request.
	 */
	private static function allow_wp_login_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public login workflows use WP's own nonces.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: 'GET';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Actions that must always work regardless of hiding.
		$allowed_actions = [ 'postpass', 'logout', 'rp', 'resetpass', 'confirmaction' ];
		if ( '' !== $action && in_array( $action, $allowed_actions, true ) ) {
			return true;
		}

		if ( 'POST' === $method ) {
			// Bare login POST without the challenge cookie â†’ treat as bot traffic.
			return self::gate_cookie_valid();
		}

		return false;
	}

	/**
	 * Issue the login-gate challenge cookie (30 days).
	 */
	private static function send_gate_cookie() {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::GATE_COOKIE,
			self::gate_token(),
			time() + 30 * DAY_IN_SECONDS,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		// Make it readable on this very request too.
		$_COOKIE[ self::GATE_COOKIE ] = self::gate_token(); // phpcs:ignore WordPress.SuperGlobalInputUsage.AccessDetected -- setting our own plugin cookie.
	}

	/**
	 * Stateless gate token value â€” derived from site salts, not forgeable
	 * without AUTH_KEY/LOGGED_IN_SALT access.
	 *
	 * @return string
	 */
	private static function gate_token() {
		return wp_hash( 'wpepp-login-gate-v1|' . home_url() );
	}

	/**
	 * Whether the current request carries a valid gate cookie.
	 *
	 * @return bool
	 */
	private static function gate_cookie_valid() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- challenge marker, not a nonce.
		if ( empty( $_COOKIE[ self::GATE_COOKIE ] ) ) {
			return false;
		}

		$value = sanitize_text_field( wp_unslash( $_COOKIE[ self::GATE_COOKIE ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return hash_equals( self::gate_token(), $value );
	}

	/**
	 * Filter site_url to replace wp-login.php with custom slug.
	 *
	 * @param string      $url     The complete site URL.
	 * @param string      $path    Path relative to site URL.
	 * @param string|null $scheme  Scheme to use.
	 * @param int|null    $blog_id Blog ID, or null for current.
	 * @return string
	 */
	public static function filter_login_url( $url, $path, $scheme, $blog_id ) {
		if ( false === strpos( $path, 'wp-login.php' ) ) {
			return $url;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$slug     = sanitize_title( $settings['custom_login_url'] ?? '' );

		if ( empty( $slug ) ) {
			return $url;
		}

		return str_replace( 'wp-login.php', $slug, $url );
	}

	/**
	 * Filter redirects to replace wp-login.php with custom slug.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $status   HTTP status code.
	 * @return string
	 */
	public static function filter_login_redirect( $location, $status ) {
		if ( false === strpos( $location, 'wp-login.php' ) ) {
			return $location;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$slug     = sanitize_title( $settings['custom_login_url'] ?? '' );

		if ( empty( $slug ) ) {
			return $location;
		}

		return str_replace( 'wp-login.php', $slug, $location );
	}

	/* â”€â”€â”€ Hide Login Page (Free) â”€â”€â”€ */

	/**
	 * Hide wp-login.php and wp-admin â€” return 404 for direct access.
	 * When a Custom Login URL is set, only that slug can access the login form.
	 */
	public static function handle_hide_login_page() {
		// Emergency Safe Mode bypass
		if ( self::is_safe_mode() ) {
			return;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$settings = is_array( $settings ) ? $settings : [];

		// If no custom login slug is configured, do not hide wp-login.php (prevents accidental total lockout).
		$custom_slug = sanitize_title( $settings['custom_login_url'] ?? '' );
		if ( empty( $custom_slug ) ) {
			return;
		}

		// Skip for logged-in users — they need wp-admin access.
		// Configurable via `hide_login_exempt_logged_in` (default: true).
		if ( is_user_logged_in() ) {
			$exempt_logged_in = array_key_exists( 'hide_login_exempt_logged_in', $settings )
				? filter_var( $settings['hide_login_exempt_logged_in'], FILTER_VALIDATE_BOOLEAN )
				: true;

			if ( $exempt_logged_in || current_user_can( 'manage_options' ) ) {
				return;
			}
		}

		$request = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		$path = trim( wp_parse_url( $request, PHP_URL_PATH ), '/' );

		// Strip the home path prefix for subdirectory installs.
		$home_path = trim( wp_parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
		if ( '' !== $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
			$relative = substr( $path, strlen( $home_path ) + 1 );
		} else {
			$relative = $path;
		}

		$is_login = ( 'wp-login.php' === basename( $relative ) );
		$is_admin = ( 'wp-admin' === $relative || 0 === strpos( $relative, 'wp-admin/' ) );

		if ( ! $is_login && ! $is_admin ) {
			return;
		}

		// Gate POST + workflow actions through the shared challenge logic (B6).
		if ( $is_login && self::allow_wp_login_request() ) {
			return;
		}

		// Allow AJAX and admin-post.php inside wp-admin.
		if ( $is_admin ) {
			$admin_allowed = [ 'admin-ajax.php', 'admin-post.php' ];
			if ( in_array( basename( $relative ), $admin_allowed, true ) ) {
				return;
			}
		}

		// Return 404.
		// For wp-login.php we must die now (it never reaches template_redirect).
		// For wp-admin we defer to template_redirect so the theme 404 loads
		// after plugins (WooCommerce, etc.) are fully initialised.
		if ( $is_login ) {
			status_header( 404 );
			nocache_headers();
			wp_die(
				esc_html__( 'Page not found.', 'wp-edit-password-protected' ),
				esc_html__( '404 Not Found', 'wp-edit-password-protected' ),
				[ 'response' => 404 ]
			);
		}

		// wp-admin: schedule a themed 404 at a safe point.
		add_action( 'template_redirect', static function () {
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}, 0 );
	}

	/* â”€â”€â”€ After Login Redirect (Free) â”€â”€â”€ */

	/**
	 * Redirect users to a custom URL after login.
	 *
	 * @param string           $redirect_to           Default redirect.
	 * @param string           $requested_redirect_to Requested redirect.
	 * @param \WP_User|\WP_Error $user                User object.
	 * @return string
	 */
	public static function filter_after_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		// Don't override if an explicit redirect_to was provided (not the default admin_url).
		if ( ! empty( $requested_redirect_to ) && admin_url() !== $requested_redirect_to ) {
			return $redirect_to;
		}

		// Only redirect successful logins.
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$url      = $settings['after_login_redirect'] ?? '';

		if ( empty( $url ) ) {
			return $redirect_to;
		}

		// Sanitize and validate.
		$url = esc_url_raw( $url );
		if ( empty( $url ) ) {
			return $redirect_to;
		}

		return $url;
	}

	/**
	 * Allow the after-login redirect host in wp_safe_redirect().
	 *
	 * @param array $hosts Allowed redirect hosts.
	 * @return array
	 */
	public static function allow_after_login_redirect_host( $hosts ) {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$url      = $settings['after_login_redirect'] ?? '';

		if ( ! empty( $url ) ) {
			$host = wp_parse_url( esc_url_raw( $url ), PHP_URL_HOST );
			if ( $host ) {
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	/* â”€â”€â”€ Auto Login Token (Free) â”€â”€â”€ */

	/**
	 * Handle auto-login token â€” validate and log user in.
	 */
	public static function handle_auto_login_token() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpepp_autologin'] ) ) {
			return;
		}

		// Don't process if already logged in.
		if ( is_user_logged_in() ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['wpepp_autologin'] ) );
		if ( empty( $token ) ) {
			wp_die(
				esc_html__( 'Invalid auto-login link.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Rate limit token guessing â€” max 10 attempts per IP per 15 minutes.
		$rl_key   = 'wpepp_autologin_rl_' . md5( self::get_client_ip() );
		$attempts = (int) get_transient( $rl_key );
		if ( $attempts >= 10 ) {
			wp_die(
				esc_html__( 'Too many auto-login attempts. Please try again later.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 429 ]
			);
		}
		set_transient( $rl_key, $attempts + 1, 15 * MINUTE_IN_SECONDS );

		$links = get_option( 'wpepp_auto_login_links', [] );
		if ( ! is_array( $links ) ) {
			$links = [];
		}

		// Prune expired links so the option does not grow unbounded.
		$now     = time();
		$pruned  = false;
		foreach ( $links as $i => $l ) {
			if ( ! empty( $l['expires_at'] ) && $now > (int) $l['expires_at'] ) {
				unset( $links[ $i ] );
				$pruned = true;
			}
		}
		if ( $pruned ) {
			$links = array_values( $links );
			update_option( 'wpepp_auto_login_links', $links, false );
		}

		$token_hash  = hash( 'sha256', $token );
		$found       = null;
		$found_index = null;
		$needs_save  = false;

		foreach ( $links as $index => $link ) {
			if ( ! empty( $link['token_hash'] ) && hash_equals( $link['token_hash'], $token_hash ) ) {
				$found       = $link;
				$found_index = $index;
				break;
			}
			if ( ! empty( $link['token'] ) && hash_equals( $link['token'], $token ) ) {
				$found       = $link;
				$found_index = $index;
				// Migrate legacy plaintext token to hash + encrypted.
				$links[ $index ]['token_hash']      = $token_hash;
				$links[ $index ]['token_encrypted'] = self::encrypt_token( $token );
				unset( $links[ $index ]['token'] );
				$needs_save = true;
				break;
			}
		}

		if ( $needs_save ) {
			update_option( 'wpepp_auto_login_links', $links, false );
		}

		if ( ! $found ) {
			wp_die(
				esc_html__( 'Invalid or expired auto-login link.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Check expiration (Pro condition â€” stored at creation time).
		if ( ! empty( $found['expires_at'] ) && time() > (int) $found['expires_at'] ) {
			wp_die(
				esc_html__( 'This auto-login link has expired.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Check max uses (Pro condition â€” stored at creation time).
		if ( ! empty( $found['max_uses'] ) && (int) $found['use_count'] >= (int) $found['max_uses'] ) {
			wp_die(
				esc_html__( 'This auto-login link has reached its maximum number of uses.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Verify user exists.
		$user = get_user_by( 'id', (int) $found['user_id'] );
		if ( ! $user ) {
			wp_die(
				esc_html__( 'The user associated with this link no longer exists.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Check role restriction (Pro condition â€” stored at creation time).
		if ( ! empty( $found['role_restriction'] ) ) {
			if ( ! in_array( $found['role_restriction'], (array) $user->roles, true ) ) {
				wp_die(
					esc_html__( 'This auto-login link is restricted to a specific role that your account does not have.', 'wp-edit-password-protected' ),
					esc_html__( 'Login Error', 'wp-edit-password-protected' ),
					[ 'response' => 403 ]
				);
			}
		}

		// Increment use count.
		$links[ $found_index ]['use_count'] = ( (int) $found['use_count'] ) + 1;
		update_option( 'wpepp_auto_login_links', $links, false );

		// Log the user in.
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Trigger core WordPress login hook.
		do_action( 'wp_login', $user->user_login, $user );

		// Redirect.
		$redirect = ! empty( $found['redirect_url'] ) ? esc_url_raw( $found['redirect_url'] ) : home_url();
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Get the secret 256-bit encryption key derived from WordPress salt.
	 *
	 * @return string Binary 32-byte key.
	 */
	private static function get_encryption_key() {
		$salt = function_exists( 'wp_salt' )
			? wp_salt( 'auth' )
			: ( defined( 'AUTH_SALT' ) ? AUTH_SALT : 'wpepp_default_token_encryption_salt' );
		return hash( 'sha256', (string) $salt, true );
	}

	/**
	 * Encrypt a sensitive token using the WordPress auth salt.
	 *
	 * @param string $token Plaintext token.
	 * @return string Encrypted token string.
	 */
	public static function encrypt_token( $token ) {
		if ( empty( $token ) ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'b64:' . base64_encode( $token );
		}
		$key = self::get_encryption_key();
		$iv  = openssl_random_pseudo_bytes( 16 );
		$enc = openssl_encrypt( $token, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return 'enc:' . base64_encode( $iv . $enc );
	}

	/**
	 * Decrypt an encrypted token.
	 *
	 * @param string $ciphertext Encrypted string.
	 * @return string Decrypted plaintext token.
	 */
	public static function decrypt_token( $ciphertext ) {
		if ( empty( $ciphertext ) || ! is_string( $ciphertext ) ) {
			return '';
		}
		if ( 0 === strpos( $ciphertext, 'b64:' ) ) {
			return (string) base64_decode( substr( $ciphertext, 4 ), true );
		}
		if ( 0 === strpos( $ciphertext, 'enc:' ) ) {
			$raw = base64_decode( substr( $ciphertext, 4 ), true );
			if ( ! $raw || strlen( $raw ) < 17 || ! function_exists( 'openssl_decrypt' ) ) {
				return '';
			}
			$iv  = substr( $raw, 0, 16 );
			$enc = substr( $raw, 16 );
			$key = self::get_encryption_key();
			$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
			return false !== $dec ? $dec : '';
		}
		return $ciphertext; // Legacy raw token fallback.
	}

	/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
	 *  Registration Protection
	 * â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

	/* â”€â”€â”€ Registration Honeypot (Free) â”€â”€â”€ */

	/**
	 * Render honeypot field on the registration form.
	 */
	public static function render_reg_honeypot_field() {
		echo '<p style="position:absolute;left:-9999px;" aria-hidden="true">';
		echo '<label for="wpepp_reg_hp">' . esc_html__( 'Leave empty', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="wpepp_reg_hp" id="wpepp_reg_hp" value="" tabindex="-1" autocomplete="off">';
		echo '</p>';
	}

	/**
	 * Reject registration if honeypot field is filled.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email address.
	 * @return \WP_Error
	 */
	public static function check_reg_honeypot( $errors, $sanitized_user_login, $user_email ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP registration form handles nonce.
		if ( isset( $_POST['wpepp_reg_hp'] ) && '' !== $_POST['wpepp_reg_hp'] ) {
			$errors->add(
				'wpepp_reg_honeypot',
				__( '<strong>Error:</strong> Registration blocked.', 'wp-edit-password-protected' )
			);
		}
		return $errors;
	}

	/* â”€â”€â”€ Registration Rate Limiter (Free) â”€â”€â”€ */

	/**
	 * Limit registration attempts per IP.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email address.
	 * @return \WP_Error
	 */
	public static function check_reg_rate_limit( $errors, $sanitized_user_login, $user_email ) {
		$ip  = self::get_client_ip();
		$raw = get_option( 'wpepp_security_settings', '{}' );
		$s   = json_decode( $raw, true );

		$max_regs = absint( $s['reg_rate_limit_max'] ?? 3 );
		$window   = absint( $s['reg_rate_limit_window'] ?? 60 );

		$transient_key = 'wpepp_reg_' . md5( $ip );
		$attempts      = (int) get_transient( $transient_key );

		if ( $attempts >= $max_regs ) {
			$errors->add(
				'wpepp_reg_rate_limit',
				__( '<strong>Error:</strong> Too many registration attempts. Please try again later.', 'wp-edit-password-protected' )
			);
			return $errors;
		}

		set_transient( $transient_key, $attempts + 1, $window * MINUTE_IN_SECONDS );
		return $errors;
	}


	/* â”€â”€â”€ Disposable Email Blocker (Pro) â”€â”€â”€ */

	/**
	 * Block registration from disposable/temporary email domains.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email address.
	 * @return \WP_Error
	 */
	public static function check_disposable_email( $errors, $sanitized_user_login, $user_email ) {
		$domain = strtolower( substr( strrchr( $user_email, '@' ), 1 ) );

		if ( empty( $domain ) ) {
			return $errors;
		}

		$disposable_domains = self::get_disposable_email_domains();

		if ( in_array( $domain, $disposable_domains, true ) ) {
			$errors->add(
				'wpepp_disposable_email',
				__( '<strong>Error:</strong> Registration with disposable email addresses is not allowed. Please use a permanent email address.', 'wp-edit-password-protected' )
			);
		}

		return $errors;
	}

	/**
	 * Get list of known disposable email domains.
	 *
	 * @return array
	 */
	public static function get_disposable_email_domains() {
		return [
			'mailinator.com', 'guerrillamail.com', 'guerrillamail.info', 'guerrillamail.net',
			'guerrillamail.org', 'guerrillamail.de', 'grr.la', 'guerrillamailblock.com',
			'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'throwaway.email',
			'throwaway.email', 'fakeinbox.com', 'sharklasers.com', 'guerrillamailblock.com',
			'yopmail.com', 'yopmail.fr', 'cool.fr.nf', 'jetable.fr.nf', 'courriel.fr.nf',
			'moncourrier.fr.nf', 'monemail.fr.nf', 'monmail.fr.nf', 'nospam.ze.tc',
			'dispostable.com', 'trashmail.com', 'trashmail.me', 'trashmail.net',
			'trashmail.org', 'trashymail.com', 'trashymail.net',
			'10minutemail.com', '10minutemail.net', '10minutemail.org',
			'tempinbox.com', 'tempr.email', 'tempmailaddress.com',
			'maildrop.cc', 'discard.email', 'discardmail.com', 'discardmail.de',
			'mailnesia.com', 'mailcatch.com', 'mailmoat.com', 'mytemp.email',
			'spamgourmet.com', 'safetymail.info', 'getairmail.com',
			'mailscrap.com', 'mailexpire.com', 'temporarymail.com',
			'harakirimail.com', 'bugmenot.com', 'mailzilla.com',
			'filzmail.com', 'dodgeit.com', 'dodgit.com',
			'mintemail.com', 'emailondeck.com', 'emailfake.com',
			'binkmail.com', 'devnullmail.com', 'nomail.xl.cx',
			'spamfree24.org', 'mailnull.com', 'spaml.com',
			'mailsac.com', 'mohmal.com', 'getnada.com', 'tempail.com',
			'burnermail.io', 'inboxkitten.com', 'anonbox.net',
			'mytrashmail.com', 'mailhazard.com', 'mailhazard.us',
			'mailforspam.com', 'spamavert.com', 'spambox.us',
			'spamcero.com', 'spamfighter.cf', 'spamfighter.ga',
			'spamfighter.gq', 'spamfighter.ml', 'spamfighter.tk',
			'trashmail.at', 'wegwerfmail.de', 'wegwerfmail.net',
			'wegwerfmail.org', 'bund.us', 'anonymbox.com',
			'plasticemail.com', 'proxymail.eu', 'rcpt.at',
			'rmqkr.net', 'royal.net', 'shieldedmail.com',
			'sogetthis.com', 'soodonims.com', 'spam4.me',
			'spamarrest.com', 'spambob.com', 'spambob.net',
			'spambob.org', 'spambog.com', 'spambog.de',
			'spambog.ru', 'spamcannon.com', 'spamcannon.net',
			'spamcon.org', 'spamcorptastic.com', 'spamcowboy.com',
			'spamcowboy.net', 'spamcowboy.org', 'spamday.com',
			'spamex.com', 'spamfree.eu', 'spamhole.com',
			'spamify.com', 'spaminator.de', 'spamkill.info',
			'spamoff.de', 'spamobox.com', 'spamspot.com',
			'spamstack.net', 'spamthis.co.uk', 'spamtrail.com',
			'spamtrap.ro', 'superrito.com', 'suremail.info',
			'teleworm.us', 'tempemail.co.za', 'tempemail.net',
			'tempmaildemo.com', 'tempmailer.com', 'tempomail.fr',
			'thankyou2010.com', 'thisisnotmyrealemail.com',
			'trash2009.com', 'trashdevil.com', 'trashdevil.de',
			'trashemail.de', 'trbvm.com', 'trbvn.com',
			'tutanota.com', 'uggsrock.com', 'upliftnow.com',
			'venompen.com', 'veryreallycheap.org', 'viditag.com',
			'viewcastmedia.com', 'viewcastmedia.net', 'vomoto.com',
			'vpn.st', 'vsimcard.com', 'vubby.com',
			'wetrainbayarea.com', 'wetrainbayarea.org',
			'wh4f.org', 'whatiaas.com', 'whatpaas.com',
			'wuzupmail.net', 'xagloo.com', 'xemaps.com',
			'xents.com', 'xjoi.com', 'xyzfree.net',
			'yogamaven.com', 'zetmail.com', 'zippymail.info',
			'zoaxe.com', 'zoemail.org',
		];
	}

	/* â”€â”€â”€ Email Domain Whitelist / Blacklist (Pro) â”€â”€â”€ */

	/**
	 * Check email domain against whitelist or blacklist.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email address.
	 * @return \WP_Error
	 */
	public static function check_email_domain_list( $errors, $sanitized_user_login, $user_email ) {
		$domain = strtolower( substr( strrchr( $user_email, '@' ), 1 ) );

		if ( empty( $domain ) ) {
			return $errors;
		}

		$raw  = get_option( 'wpepp_security_settings', '{}' );
		$s    = json_decode( $raw, true );
		$mode = $s['reg_email_domain_mode'] ?? 'off';
		$list = $s['reg_email_domain_list'] ?? '';

		if ( empty( $list ) || 'off' === $mode ) {
			return $errors;
		}

		// Parse the comma/newline-separated domain list.
		$domains = array_filter( array_map( 'trim', preg_split( '/[,\n\r]+/', strtolower( $list ) ) ) );

		if ( 'whitelist' === $mode ) {
			if ( ! in_array( $domain, $domains, true ) ) {
				$errors->add(
					'wpepp_email_domain',
					__( '<strong>Error:</strong> Registration is only allowed from approved email domains.', 'wp-edit-password-protected' )
				);
			}
		} elseif ( 'blacklist' === $mode ) {
			if ( in_array( $domain, $domains, true ) ) {
				$errors->add(
					'wpepp_email_domain',
					__( '<strong>Error:</strong> Registration from this email domain is not allowed.', 'wp-edit-password-protected' )
				);
			}
		}

		return $errors;
	}

	/* â”€â”€â”€ Admin Approval for New Users (Pro) â”€â”€â”€ */

	/**
	 * Set new users to a pending role until admin approves.
	 *
	 * @param int $user_id New user ID.
	 */
	public static function set_pending_role( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		// Don't apply to admins creating users from the admin panel.
		if ( is_admin() && current_user_can( 'create_users' ) ) {
			return;
		}

		// Store original default role, then set to subscriber with pending meta.
		update_user_meta( $user_id, '_wpepp_pending_approval', '1' );
		update_user_meta( $user_id, '_wpepp_pending_since', current_time( 'mysql' ) );

		// Send admin notification email.
		self::send_pending_user_notification( $user );
	}

	/**
	 * Show a "pending approval" message on the login page after registration.
	 *
	 * @param \WP_Error $errors Login error messages.
	 * @return \WP_Error
	 */
	public static function show_pending_message( $errors ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['checkemail'] ) && 'registered' === $_GET['checkemail'] ) {
			$errors->add(
				'wpepp_pending',
				__( 'Your account is pending approval by an administrator. You will receive an email once approved.', 'wp-edit-password-protected' ),
				'message'
			);
		}
		return $errors;
	}

	/**
	 * Block login for users pending admin approval.
	 *
	 * @param \WP_User|\WP_Error|null $user     Auth result.
	 * @param string                  $username  Username.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function block_pending_users( $user, $username ) {
		if ( is_wp_error( $user ) || ! ( $user instanceof \WP_User ) ) {
			return $user;
		}

		$pending = get_user_meta( $user->ID, '_wpepp_pending_approval', true );
		if ( '1' === $pending ) {
			return new \WP_Error(
				'wpepp_pending_approval',
				__( '<strong>Error:</strong> Your account is pending approval by an administrator.', 'wp-edit-password-protected' )
			);
		}

		return $user;
	}

	/**
	 * Send email notification to admin about a new pending user.
	 *
	 * @param \WP_User $user The new user.
	 */
	private static function send_pending_user_notification( $user ) {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$approve_url = add_query_arg( [
			'page' => 'wpepp-settings',
		], admin_url( 'admin.php' ) ) . '#/security/registration';

		$subject = sprintf(
			/* translators: %s: Site name */
			__( '[%s] New user registration pending approval', 'wp-edit-password-protected' ),
			$site_name
		);

		$message = sprintf(
			/* translators: 1: Site name, 2: Username, 3: Email, 4: Approve URL */
			__( "A new user has registered on %1\$s and is pending your approval.\n\nUsername: %2\$s\nEmail: %3\$s\n\nTo approve or reject this user, visit:\n%4\$s", 'wp-edit-password-protected' ),
			$site_name,
			$user->user_login,
			$user->user_email,
			$approve_url
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
	 *  Two-Factor Authentication (Pro)
	 * â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

	/**
	 * Render the 2FA code field on the login form.
	 */
	public static function render_2fa_field() {
		?>
		<p id="wpepp-2fa-field" style="display:none;">
			<label for="wpepp_2fa_code"><?php esc_html_e( 'Authentication Code', 'wp-edit-password-protected' ); ?></label>
			<input type="text" name="wpepp_2fa_code" id="wpepp_2fa_code" class="input" size="20" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" placeholder="<?php esc_attr_e( '6-digit code or recovery code', 'wp-edit-password-protected' ); ?>">
		</p>
		<script>
		(function(){
			var field = document.getElementById('wpepp-2fa-field');
			if(field) field.style.display = '';
		})();
		</script>
		<?php
	}

	/**
	 * Verify 2FA code during authentication.
	 *
	 * This runs at priority 99 â€” after WordPress has validated username/password.
	 *
	 * @param \WP_User|\WP_Error|null $user     Auth result.
	 * @param string                  $username  Username.
	 * @param string                  $password  Password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function verify_2fa_on_login( $user, $username, $password ) {
		// Only check if authentication succeeded (user object returned).
		if ( ! ( $user instanceof \WP_User ) ) {
			return $user;
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		// Check if 2FA is required for this user's role.
		if ( ! WPEPP_TOTP::is_required_for_user( $user, $settings ) ) {
			return $user;
		}

		// If the user hasn't set up 2FA yet, check enforcement rules.
		if ( ! WPEPP_TOTP::is_user_enabled( $user->ID ) ) {
			$enforce_strict = ! empty( $settings['two_factor_strict'] );
			$grace_days     = isset( $settings['two_factor_grace_days'] ) ? absint( $settings['two_factor_grace_days'] ) : 0;

			if ( $enforce_strict || $grace_days > 0 ) {
				$registered = strtotime( $user->user_registered );
				$deadline   = $registered + ( $grace_days * DAY_IN_SECONDS );
				if ( $enforce_strict || time() > $deadline ) {
					return new \WP_Error(
						'wpepp_2fa_setup_required',
						__( '<strong>Error:</strong> Two-Factor Authentication is required for your role. Please contact an administrator to complete setup.', 'wp-edit-password-protected' )
					);
				}
			}
			return $user;
		}

		// Trusted device cookie — skip the code challenge entirely.
		if ( WPEPP_TOTP::has_valid_remember( $user->ID ) ) {
			return $user;
		}

		// Throttle TOTP guesses per account (max 5 failed attempts per 15 min).
		$rl_key   = 'wpepp_totp_fail_' . $user->ID;
		$attempts = (int) get_transient( $rl_key );
		if ( $attempts >= 5 ) {
			return new \WP_Error(
				'wpepp_2fa_locked',
				__( '<strong>Error:</strong> Too many failed two-factor authentication attempts. Please try again in 15 minutes.', 'wp-edit-password-protected' )
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Login form uses WP's own nonce.
		$code = isset( $_POST['wpepp_2fa_code'] )
			? sanitize_text_field( wp_unslash( $_POST['wpepp_2fa_code'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $code ) ) {
			return new \WP_Error(
				'wpepp_2fa_required',
				__( '<strong>Error:</strong> Please enter your two-factor authentication code.', 'wp-edit-password-protected' )
			);
		}

		$secret = WPEPP_TOTP::get_user_secret( $user->ID );

		// Try TOTP code first.
		if ( WPEPP_TOTP::verify_code( $secret, $code ) ) {
			delete_transient( $rl_key );
			WPEPP_TOTP::issue_remember_token( $user->ID );
			return $user;
		}

		// Try recovery code.
		if ( WPEPP_TOTP::verify_recovery_code( $user->ID, $code ) ) {
			delete_transient( $rl_key );
			WPEPP_TOTP::issue_remember_token( $user->ID );
			return $user;
		}

		// Failed attempt — record hit.
		set_transient( $rl_key, $attempts + 1, 15 * MINUTE_IN_SECONDS );

		return new \WP_Error(
			'wpepp_2fa_invalid',
			__( '<strong>Error:</strong> Invalid authentication code. Please try again.', 'wp-edit-password-protected' )
		);
	}

	/**
	 * Render 2FA setup section on the user profile page.
	 *
	 * @param \WP_User $user User being edited.
	 */
	public static function render_2fa_user_profile( $user ) {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( empty( $settings['two_factor_enabled'] ) ) {
			return;
		}

		$is_enabled = WPEPP_TOTP::is_user_enabled( $user->ID );
		$pending    = get_user_meta( $user->ID, '_wpepp_2fa_pending_secret', true );
		?>
		<h2><?php esc_html_e( 'Two-Factor Authentication', 'wp-edit-password-protected' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'wp-edit-password-protected' ); ?></th>
				<td>
					<?php if ( $is_enabled ) : ?>
						<span style="color:green;font-weight:bold;">&#10003; <?php esc_html_e( 'Enabled', 'wp-edit-password-protected' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'Two-factor authentication is active on your account.', 'wp-edit-password-protected' ); ?>
						</p>
						<br>
						<label>
							<input type="checkbox" name="wpepp_disable_2fa" value="1">
							<?php esc_html_e( 'Disable two-factor authentication', 'wp-edit-password-protected' ); ?>
						</label>
					<?php else : ?>
						<span style="color:#999;">&#10007; <?php esc_html_e( 'Not configured', 'wp-edit-password-protected' ); ?></span>
						<?php
						if ( WPEPP_TOTP::is_required_for_user( $user, $settings ) ) {
							echo '<p class="description" style="color:#d63638;">';
							esc_html_e( 'Two-factor authentication is required for your account. Please set it up below.', 'wp-edit-password-protected' );
							echo '</p>';
						}

						// Generate (or reuse) a pending secret for setup.
						if ( empty( $pending ) ) {
							$pending = WPEPP_TOTP::generate_secret();
							update_user_meta( $user->ID, '_wpepp_2fa_pending_secret', $pending );
						}

						$uri = WPEPP_TOTP::get_provisioning_uri( $pending, $user->user_email );
						?>
						<br>
						<h3><?php esc_html_e( 'Setup', 'wp-edit-password-protected' ); ?></h3>
						<p><?php esc_html_e( '1. Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):', 'wp-edit-password-protected' ); ?></p>
						<div id="wpepp-2fa-qr" style="border: 1px solid #ddd; padding: 8px; width: fit-content; background: #fff;" data-uri="<?php echo esc_attr( $uri ); ?>"></div>
						<script>
						(function(){
							function wpeppDraw2FAQR(){
								var el=document.getElementById('wpepp-2fa-qr');
								if(!el||!window.QRCode){setTimeout(wpeppDraw2FAQR,150);return;}
								el.innerHTML='';
								new QRCode(el,{text:el.getAttribute('data-uri'),width:200,height:200,correctLevel:QRCode.CorrectLevel.M});
							}
							wpeppDraw2FAQR();
						})();
						</script>
						<p><strong><?php esc_html_e( 'Manual key:', 'wp-edit-password-protected' ); ?></strong> <code><?php echo esc_html( $pending ); ?></code></p>
						<p><?php esc_html_e( '2. Enter the 6-digit code from your authenticator app to verify:', 'wp-edit-password-protected' ); ?></p>
						<input type="text" name="wpepp_2fa_verify_code" value="" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" size="10" placeholder="<?php esc_attr_e( '6-digit code', 'wp-edit-password-protected' ); ?>">
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Handle 2FA setup/disable when the user profile is saved.
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_2fa_user_profile( $user_id ) {
		// Check capability.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WP profile form handles nonce via _wpnonce.

		// Disable 2FA.
		if ( ! empty( $_POST['wpepp_disable_2fa'] ) ) {
			WPEPP_TOTP::disable_for_user( $user_id );
			return;
		}

		// Enable 2FA â€” verify the setup code.
		if ( ! empty( $_POST['wpepp_2fa_verify_code'] ) ) {
			$code   = sanitize_text_field( wp_unslash( $_POST['wpepp_2fa_verify_code'] ) );
			$secret = get_user_meta( $user_id, '_wpepp_2fa_pending_secret', true );

			if ( empty( $secret ) ) {
				return;
			}

			if ( WPEPP_TOTP::verify_code( $secret, $code ) ) {
				WPEPP_TOTP::enable_for_user( $user_id, $secret );
				delete_user_meta( $user_id, '_wpepp_2fa_pending_secret' );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
	 *  IP Management (Pro)
	 * â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

	/**
	 * Check if the current IP is blocked (runs on init, priority 1).
	 * Also checks allowlist â€” if IP is on allowlist, skip all security checks.
	 */
	public static function check_ip_blocklist() {
		// Don't block background WP-Cron or CLI tasks.
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		$ip       = self::get_client_ip();
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		// Check allowlist first â€” if IP is allowed, let everything through.
		if ( self::is_ip_in_list( $ip, $settings['ip_allowlist'] ?? '' ) ) {
			return;
		}

		// Check blocklist.
		if ( self::is_ip_in_list( $ip, $settings['ip_blocklist'] ?? '' ) ) {
			status_header( 403 );
			wp_die(
				esc_html__( 'Your IP address has been blocked from accessing this site.', 'wp-edit-password-protected' ),
				esc_html__( 'Access Denied', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Block login attempts from blocked IPs (runs before other auth checks).
	 *
	 * @param \WP_User|\WP_Error|null $user     Auth result.
	 * @param string                  $username  Username.
	 * @param string                  $password  Password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function check_ip_blocklist_on_login( $user, $username, $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip       = self::get_client_ip();
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		// Allow if on allowlist.
		if ( self::is_ip_in_list( $ip, $settings['ip_allowlist'] ?? '' ) ) {
			return $user;
		}

		// Block if on blocklist.
		if ( self::is_ip_in_list( $ip, $settings['ip_blocklist'] ?? '' ) ) {
			return new \WP_Error(
				'wpepp_ip_blocked',
				__( 'Your IP address has been blocked.', 'wp-edit-password-protected' )
			);
		}

		return $user;
	}

	/**
	 * Check if an IP is in a line-separated list (supports CIDR notation).
	 *
	 * @param string $ip   IP address to check.
	 * @param string $list Newline/comma-separated list of IPs or CIDR ranges.
	 * @return bool
	 */
	private static function is_ip_in_list( $ip, $list ) {
		if ( empty( $list ) ) {
			return false;
		}

		$entries = array_filter( array_map( 'trim', preg_split( '/[\n\r,]+/', $list ) ) );

		foreach ( $entries as $entry ) {
			// Remove any inline comment (e.g. "1.2.3.4 # spam bot").
			$entry = trim( preg_replace( '/#.*$/', '', $entry ) );
			if ( empty( $entry ) ) {
				continue;
			}

			// Exact match.
			if ( $ip === $entry ) {
				return true;
			}

			// CIDR range match.
			if ( false !== strpos( $entry, '/' ) && self::ip_in_cidr( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an IP falls within a CIDR range.
	 *
	 * @param string $ip   IP address.
	 * @param string $cidr CIDR notation (e.g. 192.168.1.0/24).
	 * @return bool
	 */
	private static function ip_in_cidr( $ip, $cidr ) {
		if ( class_exists( 'WPEPP_Core_Ip' ) ) {
			return WPEPP_Core_Ip::ip_in_cidr( $ip, $cidr );
		}

		list( $range_ip, $prefix ) = array_pad( explode( '/', $cidr, 2 ), 2, '32' );

		$ip_long    = ip2long( $ip );
		$range_long = ip2long( $range_ip );
		$prefix     = (int) $prefix;

		if ( false === $ip_long || false === $range_long || $prefix < 0 || $prefix > 32 ) {
			return false;
		}

		$mask = -1 << ( 32 - $prefix );
		return ( $ip_long & $mask ) === ( $range_long & $mask );
	}

	/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
	   AI Crawler Blocker
	   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

	/**
	 * Batch-increment the AI-block counter.
	 *
	 * Hits accumulate in an object-cache/transient bucket and flush to the
	 * option every 25 blocks or 10 minutes â€” whichever comes first. Keeps bot
	 * floods from writing to wp_options on every request.
	 */
	private static function bump_ai_blocked_count() {
		$bucket_key = 'wpepp_ai_block_bucket';

		$bucket = wp_cache_get( $bucket_key, 'wpepp' );
		if ( false === $bucket ) {
			$bucket = get_transient( $bucket_key );
			if ( false === $bucket || ! is_array( $bucket ) ) {
				$bucket = [ 'count' => 0, 'started' => time() ];
			}
		}

		$bucket['count']++;

		if ( $bucket['count'] >= 25 || ( time() - (int) $bucket['started'] ) >= 10 * MINUTE_IN_SECONDS ) {
			$total = (int) get_option( 'wpepp_ai_crawler_blocked_count', 0 );
			update_option( 'wpepp_ai_crawler_blocked_count', $total + (int) $bucket['count'], false );

			wp_cache_delete( $bucket_key, 'wpepp' );
			delete_transient( $bucket_key );
			return;
		}

		set_transient( $bucket_key, $bucket, 10 * MINUTE_IN_SECONDS );
		wp_cache_set( $bucket_key, $bucket, 'wpepp', 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Known AI crawler bot identifiers.
	 *
	 * @return array Associative array: key => label.
	 */
	public static function get_known_ai_bots() {
		return [
			'GPTBot'            => 'GPTBot (OpenAI)',
			'ChatGPT-User'      => 'ChatGPT-User (OpenAI)',
			'OAI-SearchBot'     => 'OAI-SearchBot (OpenAI)',
			'CCBot'             => 'CCBot (Common Crawl)',
			'Google-Extended'   => 'Google-Extended (Gemini)',
			'GoogleOther'       => 'GoogleOther (Google)',
			'ClaudeBot'         => 'ClaudeBot (Anthropic)',
			'anthropic-ai'      => 'anthropic-ai (Anthropic)',
			'Claude-Web'        => 'Claude-Web (Anthropic)',
			'Bytespider'        => 'Bytespider (ByteDance)',
			'Amazonbot'         => 'Amazonbot (Amazon)',
			'FacebookBot'       => 'FacebookBot (Meta)',
			'Meta-ExternalAgent' => 'Meta-ExternalAgent (Meta)',
			'PerplexityBot'     => 'PerplexityBot (Perplexity)',
			'YouBot'            => 'YouBot (You.com)',
			'Applebot-Extended' => 'Applebot-Extended (Apple)',
			'cohere-ai'         => 'cohere-ai (Cohere)',
			'Diffbot'           => 'Diffbot',
			'Timpibot'          => 'Timpibot (Timpi)',
			'Omgilibot'         => 'Omgilibot (Webz.io)',
			'img2dataset'       => 'img2dataset (LAION)',
		];
	}

	/**
	 * Get the list of bots that the admin chose to block.
	 *
	 * Falls back to ALL known bots when the saved list is empty.
	 *
	 * @return array Bot UA identifiers.
	 */
	private static function get_blocked_ai_bots() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		$selected = isset( $settings['ai_crawler_bots'] ) ? $settings['ai_crawler_bots'] : null;

		// null = never configured â†’ block all known bots.
		if ( ! is_array( $selected ) ) {
			return array_keys( self::get_known_ai_bots() );
		}

		// Empty array = user explicitly deselected all.
		if ( empty( $selected ) ) {
			return [];
		}

		// Sanitise â€” only allow values that match known keys.
		$known = array_keys( self::get_known_ai_bots() );
		return array_values( array_intersect( $selected, $known ) );
	}

	/**
	 * Append Disallow rules for AI bots to robots.txt output.
	 *
	 * @param string $output  Existing robots.txt content.
	 * @param bool   $public  Whether the site is public.
	 * @return string
	 */
	public static function filter_robots_txt( $output, $public ) {
		// If the site is not public, WP already disallows everything.
		if ( ! $public ) {
			return $output;
		}

		$bots = self::get_blocked_ai_bots();
		if ( empty( $bots ) ) {
			return $output;
		}

		$output .= "\n# AI Crawler Blocker â€” added by WPEPP\n";

		foreach ( $bots as $bot ) {
			$bot     = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $bot ); // Sanitise for robots.txt.
			$output .= "User-agent: {$bot}\nDisallow: /\n\n";
		}

		return $output;
	}

	/**
	 * Block AI crawlers by HTTP User-Agent â€” returns 403.
	 *
	 * Hooked to `init` at priority 0.
	 */
	public static function block_ai_crawlers_by_ua() {
		// Skip admin, AJAX, cron, REST (to avoid blocking legitimate admin usage).
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( empty( $ua ) ) {
			return;
		}

		$bots = self::get_blocked_ai_bots();
		if ( empty( $bots ) ) {
			return;
		}

		// Also include any custom user-agent strings the admin added.
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$custom   = $settings['ai_crawler_custom_ua'] ?? '';

		if ( ! empty( $custom ) ) {
			$lines = array_filter( array_map( 'trim', explode( "\n", $custom ) ) );
			$bots  = array_merge( $bots, $lines );
		}

		$ua_lower = strtolower( $ua );

		foreach ( $bots as $bot ) {
			$bot_safe = preg_quote( strtolower( trim( $bot ) ), '/' );
			if ( ! empty( $bot_safe ) && preg_match( '/' . $bot_safe . '/i', $ua_lower ) ) {
				// Batch the block counter â€” no DB write per blocked hit (B12).
				self::bump_ai_blocked_count();

				status_header( 403 );
				nocache_headers();
				header( 'X-Robots-Tag: noai, noimageai' );
				wp_die(
					esc_html__( 'Access denied.', 'wp-edit-password-protected' ),
					esc_html__( 'Forbidden', 'wp-edit-password-protected' ),
					[ 'response' => 403 ]
				);
			}
		}
	}
}
