<?php
/**
 * Security features — login limiter, honeypot, XML-RPC, REST enum, reCAPTCHA, custom login URL, logging.
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
		// Login limiter (free).
		if ( ! empty( $settings['login_limit_enabled'] ) ) {
			add_filter( 'authenticate', [ __CLASS__, 'check_login_lockout' ], 30, 3 );
			add_action( 'wp_login_failed', [ __CLASS__, 'record_failed_login' ] );
			add_action( 'wp_login', [ __CLASS__, 'record_successful_login' ], 10, 2 );
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

		// Pro-only features.
		if ( wpepp_has_pro_check() ) {
			// reCAPTCHA.
			if ( ! empty( $settings['recaptcha_enabled'] ) && ! empty( $settings['recaptcha_site_key'] ) ) {
				add_action( 'login_enqueue_scripts', [ __CLASS__, 'enqueue_recaptcha' ] );
				add_action( 'login_form', [ __CLASS__, 'render_recaptcha' ] );
				add_filter( 'authenticate', [ __CLASS__, 'verify_recaptcha' ], 20, 1 );
			}

			// Custom login URL.
			if ( ! empty( $settings['custom_login_url'] ) ) {
				add_action( 'init', [ __CLASS__, 'handle_custom_login_url' ] );
				add_filter( 'site_url', [ __CLASS__, 'filter_login_url' ], 10, 4 );
				add_filter( 'wp_redirect', [ __CLASS__, 'filter_login_redirect' ], 10, 2 );
			}

			// Login logging.
			if ( ! empty( $settings['login_log_enabled'] ) ) {
				if ( empty( $settings['login_limit_enabled'] ) ) {
					add_action( 'wp_login_failed', [ __CLASS__, 'record_failed_login' ] );
					add_action( 'wp_login', [ __CLASS__, 'record_successful_login' ], 10, 2 );
				}
			}
		}

		// Hide login page (free).
		if ( ! empty( $settings['hide_login_page'] ) ) {
			add_action( 'init', [ __CLASS__, 'handle_hide_login_page' ], 1 );
		}

		// After login redirect (free).
		if ( ! empty( $settings['after_login_redirect'] ) ) {
			add_filter( 'login_redirect', [ __CLASS__, 'filter_after_login_redirect' ], 99, 3 );
			add_filter( 'allowed_redirect_hosts', [ __CLASS__, 'allow_after_login_redirect_host' ] );
		}

		// Auto-login token handler (free).
		add_action( 'init', [ __CLASS__, 'handle_auto_login_token' ] );

		/* ─── IP Management (Pro) ─── */
		if ( wpepp_has_pro_check() ) {
			if ( ! empty( $settings['ip_blocklist'] ) || ! empty( $settings['ip_allowlist'] ) ) {
				add_action( 'init', [ __CLASS__, 'check_ip_blocklist' ], 1 );
				add_filter( 'authenticate', [ __CLASS__, 'check_ip_blocklist_on_login' ], 5, 3 );
			}
		}

		/* ─── AI Crawler Blocker (Free) ─── */
		if ( ! empty( $settings['ai_crawler_blocker_enabled'] ) ) {
			// Block via robots.txt.
			add_filter( 'robots_txt', [ __CLASS__, 'filter_robots_txt' ], 100, 2 );

			// Block via HTTP user-agent (403).
			if ( ! empty( $settings['ai_crawler_block_ua'] ) ) {
				add_action( 'init', [ __CLASS__, 'block_ai_crawlers_by_ua' ], 0 );
			}
		}

		/* ─── Two-Factor Authentication (Pro) ─── */
		if ( wpepp_has_pro_check() && ! empty( $settings['two_factor_enabled'] ) ) {
			add_action( 'login_form', [ __CLASS__, 'render_2fa_field' ] );
			add_filter( 'authenticate', [ __CLASS__, 'verify_2fa_on_login' ], 99, 3 );
			add_action( 'show_user_profile', [ __CLASS__, 'render_2fa_user_profile' ] );
			add_action( 'edit_user_profile', [ __CLASS__, 'render_2fa_user_profile' ] );
			add_action( 'personal_options_update', [ __CLASS__, 'save_2fa_user_profile' ] );
			add_action( 'edit_user_profile_update', [ __CLASS__, 'save_2fa_user_profile' ] );
		}

		/* ─── Registration Protection ─── */

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
			// reCAPTCHA on registration (reuses existing keys).
			if ( ! empty( $settings['reg_recaptcha_enabled'] ) && ! empty( $settings['recaptcha_site_key'] ) ) {
				add_action( 'register_form', [ __CLASS__, 'render_recaptcha' ] );
				add_filter( 'registration_errors', [ __CLASS__, 'check_reg_recaptcha' ], 10, 3 );
				// Ensure reCAPTCHA script is loaded on registration page too.
				if ( empty( $settings['recaptcha_enabled'] ) ) {
					add_action( 'login_enqueue_scripts', [ __CLASS__, 'enqueue_recaptcha' ] );
				}
			}

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

	/* ─── Login Limiter ─── */

	/**
	 * Check if the IP is locked out before authenticating.
	 *
	 * @param \WP_User|\WP_Error|null $user     User or error.
	 * @param string                  $username  Username.
	 * @param string                  $password  Password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function check_login_lockout( $user, $username, $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip = self::get_client_ip();
		if ( self::is_ip_locked( $ip ) ) {
			return new \WP_Error(
				'wpepp_lockout',
				__( 'Too many failed login attempts. Please try again later.', 'wp-edit-password-protected' )
			);
		}

		return $user;
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

		$table = $wpdb->prefix . 'wpepp_login_log';

		// If table doesn't exist, skip silently.
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
	}

	/**
	 * Check if an IP is currently locked out.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private static function is_ip_locked( $ip ) {
		global $wpdb;

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		$max      = absint( $settings['max_attempts'] ?? 5 );
		$duration = absint( $settings['lockout_duration'] ?? 15 );

		$table = $wpdb->prefix . 'wpepp_login_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				 WHERE ip_address = %s AND status = 'failed'
				 AND created_at > DATE_SUB( NOW(), INTERVAL %d MINUTE )",
				$table,
				sanitize_text_field( $ip ),
				$duration
			)
		);

		return $count >= $max;
	}

	/**
	 * Get client IP — checks proxy headers then falls back to REMOTE_ADDR.
	 *
	 * Priority: HTTP_CF_CONNECTING_IP (Cloudflare) → HTTP_X_FORWARDED_FOR
	 * → HTTP_X_REAL_IP → REMOTE_ADDR.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',  // Most reverse proxies.
			'HTTP_X_REAL_IP',        // Nginx.
			'REMOTE_ADDR',           // Direct connection.
		];

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

			// X-Forwarded-For may contain a comma-separated list; take the first (client) IP.
			if ( 'HTTP_X_FORWARDED_FOR' === $header && false !== strpos( $value, ',' ) ) {
				$value = trim( explode( ',', $value )[0] );
			}

			if ( filter_var( $value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $value;
			}
		}

		// Fallback — allow private/reserved IPs (localhost, LAN, etc.).
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/* ─── Honeypot ─── */

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
	 * Check the honeypot field — if filled, reject.
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

	/* ─── XML-RPC ─── */

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

	/* ─── REST User Enumeration ─── */

	/**
	 * Remove /wp/v2/users endpoints for non-admins.
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public static function disable_user_endpoints( $endpoints ) {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}

		foreach ( $endpoints as $route => $data ) {
			if ( preg_match( '#/wp/v2/users#', $route ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	/* ─── reCAPTCHA (Pro) ─── */

	/**
	 * Enqueue reCAPTCHA JS on login page.
	 */
	public static function enqueue_recaptcha() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		$site_key = sanitize_text_field( $settings['recaptcha_site_key'] ?? '' );
		if ( empty( $site_key ) ) {
			return;
		}

		wp_enqueue_script(
			'wpepp-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			[],
			null,
			true
		);
	}

	/**
	 * Render reCAPTCHA widget in login form.
	 */
	public static function render_recaptcha() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$site_key = sanitize_text_field( $settings['recaptcha_site_key'] ?? '' );

		if ( ! empty( $site_key ) ) {
			echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
		}
	}

	/**
	 * Verify reCAPTCHA response.
	 *
	 * @param \WP_User|\WP_Error|null $user Auth result.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function verify_recaptcha( $user ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Login form uses WP's own nonce.
		if ( ! isset( $_POST['g-recaptcha-response'] ) || empty( $_POST['g-recaptcha-response'] ) ) {
			return new \WP_Error(
				'wpepp_recaptcha',
				__( 'Please complete the reCAPTCHA challenge.', 'wp-edit-password-protected' )
			);
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$secret   = sanitize_text_field( $settings['recaptcha_secret_key'] ?? '' );

		if ( empty( $secret ) ) {
			return $user;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$response = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

		$result = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
			'body' => [
				'secret'   => $secret,
				'response' => $response,
				'remoteip' => self::get_client_ip(),
			],
		] );

		if ( is_wp_error( $result ) ) {
			return $user; // Fail open on network error.
		}

		$body = json_decode( wp_remote_retrieve_body( $result ), true );
		if ( empty( $body['success'] ) ) {
			return new \WP_Error(
				'wpepp_recaptcha',
				__( 'reCAPTCHA verification failed.', 'wp-edit-password-protected' )
			);
		}

		return $user;
	}

	/* ─── Custom Login URL (Pro) ─── */

	/**
	 * Handle custom login URL — redirect default wp-login.php to 404.
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
			// Initialize variables that wp-login.php expects.
			global $user_login, $error, $interim_login, $action;
			$user_login   = '';
			$error        = '';
			$interim_login = false;
			$action       = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			require_once ABSPATH . 'wp-login.php';
			exit;
		}

		// Block direct access to wp-login.php (except POST for login processing).
		if ( 'wp-login.php' === basename( $path ) ) {
			$method = isset( $_SERVER['REQUEST_METHOD'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
				: 'GET';

			// Allow POST requests (form submissions) and requests with action=postpass (password form).
			if ( 'POST' === $method ) {
				return;
			}

			// Allow specific actions that need to work.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			$allowed_actions = [ 'postpass', 'logout', 'rp', 'resetpass', 'confirmaction' ];
			if ( in_array( $action, $allowed_actions, true ) ) {
				return;
			}

			// Defer 404 to template_redirect so theme templates load
			// after wp_loaded (avoids WooCommerce get_cart errors).
			add_action( 'template_redirect', static function () {
				status_header( 404 );
				nocache_headers();
				include get_query_template( '404' );
				exit;
			}, 0 );
		}
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

	/* ─── Hide Login Page (Free) ─── */

	/**
	 * Hide wp-login.php and wp-admin — return 404 for direct access.
	 * When a Custom Login URL is set, only that slug can access the login form.
	 */
	public static function handle_hide_login_page() {
		// Skip for logged-in users — they need wp-admin access.
		if ( is_user_logged_in() ) {
			return;
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

		// Allow POST to wp-login.php (form submissions from our custom slug).
		if ( $is_login ) {
			$method = isset( $_SERVER['REQUEST_METHOD'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
				: 'GET';

			if ( 'POST' === $method ) {
				return;
			}

			// Allow specific actions that need wp-login.php.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action          = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			$allowed_actions = [ 'postpass', 'logout', 'rp', 'resetpass', 'confirmaction' ];
			if ( in_array( $action, $allowed_actions, true ) ) {
				return;
			}
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

	/* ─── After Login Redirect (Free) ─── */

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

	/* ─── Auto Login Token (Free) ─── */

	/**
	 * Handle auto-login token — validate and log user in.
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

		$links = get_option( 'wpepp_auto_login_links', [] );
		if ( ! is_array( $links ) ) {
			$links = [];
		}

		$found = null;
		$found_index = null;
		foreach ( $links as $index => $link ) {
			if ( hash_equals( $link['token'], $token ) ) {
				$found       = $link;
				$found_index = $index;
				break;
			}
		}

		if ( ! $found ) {
			wp_die(
				esc_html__( 'Invalid or expired auto-login link.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Check expiration (Pro condition — stored at creation time).
		if ( ! empty( $found['expires_at'] ) && time() > (int) $found['expires_at'] ) {
			wp_die(
				esc_html__( 'This auto-login link has expired.', 'wp-edit-password-protected' ),
				esc_html__( 'Login Error', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Check max uses (Pro condition — stored at creation time).
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

		// Check role restriction (Pro condition — stored at creation time).
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
		do_action( 'wp_login', $user->user_login, $user );

		// Redirect.
		$redirect = ! empty( $found['redirect_url'] ) ? esc_url_raw( $found['redirect_url'] ) : home_url();
		wp_safe_redirect( $redirect );
		exit;
	}

	/* ═══════════════════════════════════════════════════════════════
	 *  Registration Protection
	 * ═══════════════════════════════════════════════════════════════ */

	/* ─── Registration Honeypot (Free) ─── */

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

	/* ─── Registration Rate Limiter (Free) ─── */

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

	/* ─── reCAPTCHA on Registration (Pro) ─── */

	/**
	 * Verify reCAPTCHA on the registration form.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email address.
	 * @return \WP_Error
	 */
	public static function check_reg_recaptcha( $errors, $sanitized_user_login, $user_email ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['g-recaptcha-response'] ) || empty( $_POST['g-recaptcha-response'] ) ) {
			$errors->add(
				'wpepp_reg_recaptcha',
				__( '<strong>Error:</strong> Please complete the reCAPTCHA challenge.', 'wp-edit-password-protected' )
			);
			return $errors;
		}

		$raw    = get_option( 'wpepp_security_settings', '{}' );
		$s      = json_decode( $raw, true );
		$secret = sanitize_text_field( $s['recaptcha_secret_key'] ?? '' );

		if ( empty( $secret ) ) {
			return $errors;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$response = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

		$result = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
			'body' => [
				'secret'   => $secret,
				'response' => $response,
				'remoteip' => self::get_client_ip(),
			],
		] );

		if ( is_wp_error( $result ) ) {
			return $errors; // Fail open on network error.
		}

		$body = json_decode( wp_remote_retrieve_body( $result ), true );
		if ( empty( $body['success'] ) ) {
			$errors->add(
				'wpepp_reg_recaptcha',
				__( '<strong>Error:</strong> reCAPTCHA verification failed.', 'wp-edit-password-protected' )
			);
		}

		return $errors;
	}

	/* ─── Disposable Email Blocker (Pro) ─── */

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
	private static function get_disposable_email_domains() {
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

	/* ─── Email Domain Whitelist / Blacklist (Pro) ─── */

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

	/* ─── Admin Approval for New Users (Pro) ─── */

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

	/* ═══════════════════════════════════════════════════════════════
	 *  Two-Factor Authentication (Pro)
	 * ═══════════════════════════════════════════════════════════════ */

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
	 * This runs at priority 99 — after WordPress has validated username/password.
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

		// If the user hasn't set up 2FA yet, allow login but they should set it up.
		if ( ! WPEPP_TOTP::is_user_enabled( $user->ID ) ) {
			return $user;
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
			return $user;
		}

		// Try recovery code.
		if ( WPEPP_TOTP::verify_recovery_code( $user->ID, $code ) ) {
			return $user;
		}

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

						$uri    = WPEPP_TOTP::get_provisioning_uri( $pending, $user->user_email );
						$qr_url = WPEPP_TOTP::get_qr_code_url( $uri );
						?>
						<br>
						<h3><?php esc_html_e( 'Setup', 'wp-edit-password-protected' ); ?></h3>
						<p><?php esc_html_e( '1. Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):', 'wp-edit-password-protected' ); ?></p>
						<img src="<?php echo esc_url( $qr_url ); ?>" alt="<?php esc_attr_e( '2FA QR Code', 'wp-edit-password-protected' ); ?>" width="200" height="200" style="border: 1px solid #ddd; padding: 8px;">
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
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			return;
		}

		// Enable 2FA — verify the setup code.
		if ( ! empty( $_POST['wpepp_2fa_verify_code'] ) ) {
			$code   = sanitize_text_field( wp_unslash( $_POST['wpepp_2fa_verify_code'] ) );
			$secret = get_user_meta( $user_id, '_wpepp_2fa_pending_secret', true );

			if ( empty( $secret ) ) {
				return;
			}

			if ( WPEPP_TOTP::verify_code( $secret, $code ) ) {
				$recovery_codes = WPEPP_TOTP::enable_for_user( $user_id, $secret );
				delete_user_meta( $user_id, '_wpepp_2fa_pending_secret' );

				// Store recovery codes temporarily so we can show them once.
				set_transient(
					'wpepp_2fa_recovery_' . $user_id,
					$recovery_codes,
					5 * MINUTE_IN_SECONDS
				);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/* ═══════════════════════════════════════════════════════════════
	 *  IP Management (Pro)
	 * ═══════════════════════════════════════════════════════════════ */

	/**
	 * Check if the current IP is blocked (runs on init, priority 1).
	 * Also checks allowlist — if IP is on allowlist, skip all security checks.
	 */
	public static function check_ip_blocklist() {
		// Don't block admin-ajax, REST, or cron requests.
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$ip       = self::get_client_ip();
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		// Check allowlist first — if IP is allowed, let everything through.
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

	/* ═══════════════════════════════════════════════════════════════
	   AI Crawler Blocker
	   ═══════════════════════════════════════════════════════════════ */

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

		// null = never configured → block all known bots.
		if ( ! is_array( $selected ) ) {
			return array_keys( self::get_known_ai_bots() );
		}

		// Empty array = user explicitly deselected all.
		if ( empty( $selected ) ) {
			return [];
		}

		// Sanitise — only allow values that match known keys.
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

		$output .= "\n# AI Crawler Blocker — added by WPEPP\n";

		foreach ( $bots as $bot ) {
			$bot     = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $bot ); // Sanitise for robots.txt.
			$output .= "User-agent: {$bot}\nDisallow: /\n\n";
		}

		return $output;
	}

	/**
	 * Block AI crawlers by HTTP User-Agent — returns 403.
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
				// Increment block counter for dashboard stats.
				$count = (int) get_option( 'wpepp_ai_crawler_blocked_count', 0 );
				update_option( 'wpepp_ai_crawler_blocked_count', $count + 1, false );

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
