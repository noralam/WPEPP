<?php
/**
 * Site Access — Admin Only mode & Site-wide password protection.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Site_Access
 */
class WPEPP_Site_Access {

	/**
	 * Cached settings.
	 *
	 * @var array
	 */
	private $settings = [];

	/**
	 * Login error from init-phase popup handler.
	 *
	 * @var string
	 */
	private $admin_login_error = '';

	/**
	 * Cookie name for site password.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'wpepp_site_access';

	/**
	 * Constructor — load settings and hook into WordPress.
	 */
	public function __construct() {
		$raw            = get_option( 'wpepp_site_access_settings', '{}' );
		$this->settings = json_decode( $raw, true );

		if ( ! is_array( $this->settings ) ) {
			$this->settings = [];
		}

		// Nothing enabled — bail early.
		if ( empty( $this->settings['admin_only_enabled'] ) && empty( $this->settings['site_password_enabled'] ) ) {
			return;
		}

		// Handle login/password form submissions on init (before headers are sent).
		add_action( 'init', [ $this, 'handle_site_password_submit' ] );
		add_action( 'init', [ $this, 'handle_admin_only_login_submit' ] );

		// Main enforcement — priority 0 to run before other template_redirect hooks.
		add_action( 'template_redirect', [ $this, 'enforce_access' ], 0 );

		// Prevent server-level & CDN page caching when access restrictions are active.
		add_action( 'send_headers', [ $this, 'send_no_cache_headers' ] );
	}

	/**
	 * Send no-cache headers on frontend to prevent server/CDN caching
	 * from bypassing access enforcement.
	 */
	public function send_no_cache_headers() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		nocache_headers();
		header( 'Vary: Cookie' );
	}

	/**
	 * Check if current request should be skipped (admin, login, REST, AJAX, cron, feed).
	 *
	 * @return bool
	 */
	private function should_skip() {
		// Admin area.
		if ( is_admin() ) {
			return true;
		}

		// WP-CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		// AJAX requests.
		if ( wp_doing_ajax() ) {
			return true;
		}

		// Cron.
		if ( wp_doing_cron() ) {
			return true;
		}

		// REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// wp-login.php and wp-signup.php.
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
		if ( in_array( $script, [ 'wp-login.php', 'wp-signup.php', 'wp-cron.php', 'xmlrpc.php' ], true ) ) {
			return true;
		}

		// Plugin preview requests.
		if ( isset( $_GET['wpepp_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	/**
	 * Enforce access restrictions.
	 */
	public function enforce_access() {
		if ( $this->should_skip() ) {
			return;
		}

		// --- Admin Only Mode ---
		if ( ! empty( $this->settings['admin_only_enabled'] ) ) {
			$this->enforce_admin_only();
			return; // Admin Only takes priority; don't also run site password.
		}

		// --- Site Password ---
		if ( ! empty( $this->settings['site_password_enabled'] ) && ! empty( $this->settings['site_password'] ) ) {
			$this->enforce_site_password();
		}
	}

	/**
	 * Handle Admin Only popup login form submission on init (before headers are sent).
	 * This ensures auth cookies can be set reliably.
	 */
	public function handle_admin_only_login_submit() {
		if ( empty( $this->settings['admin_only_enabled'] ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return;
		}

		$action = $this->settings['admin_only_action'] ?? 'redirect';
		if ( 'popup' !== $action ) {
			return;
		}

		// Only process POST requests with the popup login nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified below.
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' )
			|| ! isset( $_POST['wpepp_admin_login_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_admin_login_nonce'] ) ), 'wpepp_admin_login' ) ) {
			// Store error for later display in template_redirect.
			$this->admin_login_error = __( 'Security check failed. Please try again.', 'wp-edit-password-protected' );
			return;
		}

		$creds = [
			'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
			'user_password' => isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- password must not be sanitized.
			'remember'      => ! empty( $_POST['rememberme'] ),
		];

		$user = wp_signon( $creds, is_ssl() );

		if ( ! is_wp_error( $user ) ) {
			// Login succeeded — redirect to same URL (now logged in).
			wp_safe_redirect( esc_url_raw( remove_query_arg( 'wpepp_login_error' ) ) );
			exit;
		}

		// Store error for later display in template_redirect.
		$this->admin_login_error = __( 'Invalid username or password. Please try again.', 'wp-edit-password-protected' );
	}

	/**
	 * Enforce Admin Only mode — require login.
	 */
	private function enforce_admin_only() {
		// Logged-in users pass.
		if ( is_user_logged_in() ) {
			return;
		}

		$action = $this->settings['admin_only_action'] ?? 'redirect';

		if ( 'popup' === $action ) {
			// Get any login error from the init-phase handler.
			$error = $this->admin_login_error ?? '';

			$this->render_admin_only_popup_page( $error );
			exit;
		}

		// Default: redirect to login page.
		$current_url = home_url( add_query_arg( [] ) );
		wp_safe_redirect( wp_login_url( $current_url ) );
		exit;
	}

	/**
	 * Render a full-page login popup overlay (blurred placeholder + modal login form).
	 *
	 * @param string $error Optional error message from a failed login attempt.
	 */
	private function render_admin_only_popup_page( $error = '' ) {
		$message = $this->settings['admin_only_message'] ?? __( 'This site is for members only. Please log in to continue.', 'wp-edit-password-protected' );
		$header  = $this->settings['admin_only_header'] ?? __( 'Login Required', 'wp-edit-password-protected' );

		$lock_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
			. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
			. '</svg>';

		$site_name = get_bloginfo( 'name' );

		// Enqueue the content lock stylesheet so wp_head() prints it.
		wp_enqueue_style(
			'wpepp-content-lock',
			WPEPP_URL . '/assets/css/frontend-content-lock.css',
			[],
			defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
		);

		wp_enqueue_style(
			'wpepp-admin-only-popup',
			WPEPP_URL . '/assets/css/admin-only-popup.css',
			[],
			defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
		);

		// Preserve the username on failed login.
		$last_username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $site_name ); ?> &mdash; <?php esc_html_e( 'Login Required', 'wp-edit-password-protected' ); ?></title>
			<?php wp_head(); ?>
		</head>
		<body>
			<div class="wpepp-popup-lock-wrapper">
				<div class="wpepp-popup-lock-blur" aria-hidden="true"></div>
				<div class="wpepp-popup-lock-overlay">
					<div class="wpepp-popup-lock-modal">
						<div class="wpepp-popup-lock-icon"><?php echo $lock_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></div>
						<h3 class="wpepp-popup-lock-title"><?php echo esc_html( $header ); ?></h3>
						<p class="wpepp-popup-lock-message"><?php echo wp_kses_post( $message ); ?></p>
						<?php if ( ! empty( $error ) ) : ?>
							<div class="wpepp-popup-lock-error"><?php echo esc_html( $error ); ?></div>
						<?php endif; ?>
						<div class="wpepp-popup-lock-form">
							<form method="post" action="">
								<?php wp_nonce_field( 'wpepp_admin_login', 'wpepp_admin_login_nonce' ); ?>
								<label for="wpepp-user-login"><?php esc_html_e( 'Username', 'wp-edit-password-protected' ); ?></label>
								<input type="text" name="log" id="wpepp-user-login" autocomplete="username" value="<?php echo esc_attr( $last_username ); ?>" required />
								<label for="wpepp-user-pass"><?php esc_html_e( 'Password', 'wp-edit-password-protected' ); ?></label>
								<input type="password" name="pwd" id="wpepp-user-pass" autocomplete="current-password" required />
								<p class="wpepp-remember">
									<label><input type="checkbox" name="rememberme" value="forever" /> <?php esc_html_e( 'Remember Me', 'wp-edit-password-protected' ); ?></label>
								</p>
								<input type="submit" value="<?php esc_attr_e( 'Log In', 'wp-edit-password-protected' ); ?>" />
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Enforce site-wide password.
	 */
	private function enforce_site_password() {
		// Administrators always bypass.
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return;
		}

		// If bypass for all logged-in users is enabled.
		$bypass = $this->settings['site_password_bypass_logged_in'] ?? true;
		if ( $bypass && is_user_logged_in() ) {
			return;
		}

		// Check cookie.
		if ( $this->has_valid_site_password_cookie() ) {
			return;
		}

		// Show the password form and stop.
		$this->render_site_password_page();
		exit;
	}

	/**
	 * Check if the visitor has a valid site password cookie.
	 *
	 * @return bool
	 */
	private function has_valid_site_password_cookie() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$stored_password = $this->settings['site_password'] ?? '';
		if ( empty( $stored_password ) ) {
			return false;
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$expected     = wp_hash( $stored_password );

		return hash_equals( $expected, $cookie_value );
	}

	/**
	 * Handle site password form submission.
	 */
	public function handle_site_password_submit() {
		if ( empty( $this->settings['site_password_enabled'] ) || empty( $this->settings['site_password'] ) ) {
			return;
		}

		if ( ! isset( $_POST['wpepp_site_password_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_site_password_nonce'] ) ), 'wpepp_site_password' ) ) {
			return;
		}

		$submitted = isset( $_POST['wpepp_site_password'] )
			? sanitize_text_field( wp_unslash( $_POST['wpepp_site_password'] ) )
			: '';

		$stored = $this->settings['site_password'] ?? '';

		if ( $submitted === $stored ) {
			$days   = max( 1, intval( $this->settings['site_password_cookie_days'] ?? 7 ) );
			$expire = time() + ( $days * DAY_IN_SECONDS );
			$value  = wp_hash( $stored );

			setcookie( self::COOKIE_NAME, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

			$redirect = isset( $_POST['wpepp_site_redirect'] )
				? esc_url_raw( wp_unslash( $_POST['wpepp_site_redirect'] ) )
				: home_url( '/' );

			wp_safe_redirect( $redirect );
			exit;
		}

		// Wrong password — set flag for the template.
		add_filter( 'wpepp_site_password_error', '__return_true' );
	}

	/**
	 * Render the site password form page.
	 */
	private function render_site_password_page() {
		$message = $this->settings['site_password_message'] ?? __( 'This site is password protected. Please enter the password to continue.', 'wp-edit-password-protected' );
		$error   = apply_filters( 'wpepp_site_password_error', false );

		$current_url = home_url( add_query_arg( [] ) );

		// Enqueue the password form stylesheet so wp_head() prints it.
		wp_enqueue_style(
			'wpepp-password-form',
			WPEPP_URL . '/assets/css/frontend-password-form.css',
			[],
			defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
		);

		// Generate custom CSS from saved password settings.
		$custom_css = '';
		if ( class_exists( 'WPEPP_Password_Customizer' ) ) {
			$raw      = get_option( 'wpepp_password_settings', '{}' );
			$pw_settings = json_decode( $raw, true );
			if ( ! empty( $pw_settings ) && is_array( $pw_settings ) ) {
				$custom_css = WPEPP_Password_Customizer::generate_css( $pw_settings );
			}
		}

		wp_enqueue_style(
			'wpepp-site-password',
			WPEPP_URL . '/assets/css/site-password.css',
			[],
			defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
		);

		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'wpepp-site-password', wp_strip_all_tags( $custom_css ) );
		}

		$lock_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
			. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
			. '</svg>';

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> &mdash; <?php esc_html_e( 'Protected', 'wp-edit-password-protected' ); ?></title>
			<?php wp_head(); ?>
		</head>
		<body class="wpepp-site-password-body">
			<div class="wpepp-site-password-wrap">
				<div class="wpepp-site-password-icon"><?php echo $lock_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></div>
				<h1 class="wpepp-site-password-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<p class="wpepp-site-password-message"><?php echo wp_kses_post( $message ); ?></p>

				<?php if ( $error ) : ?>
					<div class="wpepp-site-password-error">
						<?php esc_html_e( 'Incorrect password. Please try again.', 'wp-edit-password-protected' ); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="wpepp-site-password-form">
					<?php wp_nonce_field( 'wpepp_site_password', 'wpepp_site_password_nonce' ); ?>
					<input type="hidden" name="wpepp_site_redirect" value="<?php echo esc_url( $current_url ); ?>">
					<label for="wpepp-site-pw"><?php esc_html_e( 'Password', 'wp-edit-password-protected' ); ?></label>
					<input type="password" id="wpepp-site-pw" name="wpepp_site_password" required autocomplete="off" autofocus>
					<button type="submit"><?php esc_html_e( 'Enter Site', 'wp-edit-password-protected' ); ?></button>
				</form>

				<div class="wpepp-site-password-footer">
					<?php
					printf(
						/* translators: %s: site name */
						esc_html__( '&copy; %s. All rights reserved.', 'wp-edit-password-protected' ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
}
