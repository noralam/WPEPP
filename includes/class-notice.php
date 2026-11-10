<?php
/**
 * Admin notices — Pro upgrade & 3.0 massive update banner + 5-star review notice.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Notice
 */
final class WPEPP_Notice {

	/**
	 * Option key for the Pro banner dismiss timestamp.
	 */
	const DISMISS_OPTION = 'wpepp_v3_massive_update_dismissed';

	/**
	 * Option key for the 5-star review notice dismiss.
	 */
	const REVIEW_DISMISS_OPTION = 'wpepp_review_dismissed';

	/**
	 * Days after activation before showing the review notice.
	 */
	const REVIEW_DAYS = 7;

	/**
	 * Boot.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_notice_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_pro_notice' ] );
		add_action( 'admin_notices', [ $this, 'render_review_notice' ] );
		add_action( 'wp_ajax_wpepp_dismiss_pro_notice', [ $this, 'ajax_dismiss_pro_notice' ] );
		add_action( 'wp_ajax_wpepp_dismiss_review_notice', [ $this, 'ajax_dismiss_review_notice' ] );
	}

	/* ─── 1. Pro upgrade & 3.0 update notice ─────────────────────── */

	/**
	 * Should the Pro upgrade banner show?
	 *
	 * @return bool
	 */
	private function should_show_pro_notice() {
		// Allow admin preview via query parameter for testing/development (e.g. ?wpepp_preview_notice=1).
		if ( isset( $_GET['wpepp_preview_notice'] ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Never for Pro users.
		if ( wpepp_has_pro_check() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$dismissed = get_option( self::DISMISS_OPTION );
		if ( ! $dismissed ) {
			return true;
		}

		return time() > (int) $dismissed;
	}

	/**
	 * Render the Pro upgrade & 3.0 massive update notice.
	 */
	public function render_pro_notice() {
		if ( ! $this->should_show_pro_notice() ) {
			return;
		}

		$pro_url = 'https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing';
		?>
		<div class="wpepp-pro-notice" id="wpepp-pro-notice">

			<div class="wpepp-pro-notice__top-bar"></div>

			<div class="wpepp-pro-notice__body">

				<div class="wpepp-pro-notice__header">
					<span class="wpepp-pro-notice__badge">🚀 3.0 MASSIVE UPDATE</span>
					<div class="wpepp-pro-notice__header-text">
						<h3><?php esc_html_e( 'WPEPP 3.0 is Here – Your Complete All-In-One WordPress Security Suite!', 'wp-edit-password-protected' ); ?></h3>
						<p><?php esc_html_e( 'We’ve evolved WPEPP into a full-scale cybersecurity powerhouse! The Free version alone now provides enterprise-grade protection with a built-in Web Application Firewall (WAF), Deep Malware Scanner, Anti-Spam Bot Shield, and Rate Limiting — more than enough to keep your website completely secure!', 'wp-edit-password-protected' ); ?></p>
					</div>
				</div>

				<div class="wpepp-pro-notice__features">
					<span>🛡️ <?php esc_html_e( '30+ Core WAF Rules (SQLi, XSS, RCE, LFI)', 'wp-edit-password-protected' ); ?></span>
					<span>🔍 <?php esc_html_e( 'Deep Malware Scanner & Checksums', 'wp-edit-password-protected' ); ?></span>
					<span>🤖 <?php esc_html_e( 'WooCommerce & Comment Bot Shield', 'wp-edit-password-protected' ); ?></span>
					<span>🔐 <?php esc_html_e( 'Free Two-Factor Auth (TOTP 2FA)', 'wp-edit-password-protected' ); ?></span>
					<span>⚡ <?php esc_html_e( 'Accurate Multi-Core CPU Monitor', 'wp-edit-password-protected' ); ?></span>
					<span>🛑 <?php esc_html_e( 'IPv4/IPv6 CIDR & 404 Auto-Defense', 'wp-edit-password-protected' ); ?></span>
				</div>

				<div class="wpepp-pro-notice__offer-bar">
					<div class="wpepp-pro-notice__offer-left">
						<span class="wpepp-pro-notice__limited-badge"><?php esc_html_e( 'ENTERPRISE PRO', 'wp-edit-password-protected' ); ?></span>
						<span class="wpepp-pro-notice__offer-text">
							<?php esc_html_e( 'Need extended defense? Upgrade to WPEPP Pro for Early-Boot Extended Firewall, Biometric Passkeys (Touch/Face ID), and Breached Password Audits.', 'wp-edit-password-protected' ); ?>
						</span>
					</div>

					<div class="wpepp-pro-notice__offer-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>" class="wpepp-pro-notice__btn-details">
							<?php esc_html_e( 'Explore 3.0 Dashboard', 'wp-edit-password-protected' ); ?> →
						</a>
						<a href="<?php echo esc_url( $pro_url ); ?>" class="wpepp-pro-notice__btn-upgrade" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Upgrade to Pro', 'wp-edit-password-protected' ); ?> →
						</a>
					</div>
				</div>

			</div><!-- .body -->

			<div class="wpepp-pro-notice__footer">
				<button type="button" class="wpepp-pro-notice__dismiss" id="wpepp-dismiss-pro-notice">
					<?php esc_html_e( 'Dismiss for 28 days', 'wp-edit-password-protected' ); ?>
				</button>
			</div>

		</div><!-- .wpepp-pro-notice -->
		<?php
	}

	/**
	 * AJAX: dismiss the Pro notice for 30 days.
	 */
	public function ajax_dismiss_pro_notice() {
		check_ajax_referer( 'wpepp_notice_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$days = 28;
		update_option( self::DISMISS_OPTION, time() + ( $days * DAY_IN_SECONDS ) );

		wp_send_json_success( [ 'hidden_for_days' => $days ] );
	}

	/* ─── 2. 5-star review notice ──────────────────────────────── */

	/**
	 * Should the review notice be shown?
	 *
	 * @return bool
	 */
	private function should_show_review_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$dismissed = get_option( self::REVIEW_DISMISS_OPTION );

		// Permanently dismissed ('done') or snoozed until a future timestamp.
		if ( 'done' === $dismissed ) {
			return false;
		}

		if ( $dismissed && time() < (int) $dismissed ) {
			return false;
		}

		$install_date = get_option( 'wpepp_install_date' );
		if ( ! $install_date ) {
			return false;
		}

		return strtotime( $install_date ) < strtotime( '-' . self::REVIEW_DAYS . ' days' );
	}

	/**
	 * Render the 5-star review notice.
	 */
	public function render_review_notice() {
		if ( ! $this->should_show_review_notice() ) {
			return;
		}

		$review_url = 'https://wordpress.org/support/plugin/wp-edit-password-protected/reviews/';
		?>
		<div class="wpepp-review-notice" id="wpepp-review-notice">
			<div class="wpepp-review-notice__stars" aria-hidden="true">★★★★★</div>
			<div class="wpepp-review-notice__content">
				<p class="wpepp-review-notice__title">
				<?php esc_html_e( 'Thank you for using WPEPP – All In One WordPress Security!', 'wp-edit-password-protected' ); ?>
				</p>
				<p class="wpepp-review-notice__body">
					<?php esc_html_e( 'If the plugin has been useful, a quick 5-star review on WordPress.org means a lot to us and helps other site owners discover it. It takes less than a minute.', 'wp-edit-password-protected' ); ?>
				</p>
				<div class="wpepp-review-notice__actions">
					<a href="<?php echo esc_url( $review_url ); ?>" class="wpepp-review-notice__btn-rate" target="_blank" rel="noopener noreferrer" id="wpepp-review-rate">
						★★★★★ <?php esc_html_e( 'Leave a 5-star review', 'wp-edit-password-protected' ); ?>
					</a>
					<button type="button" class="wpepp-review-notice__btn-remind" id="wpepp-review-remind">
						<?php esc_html_e( 'Maybe later', 'wp-edit-password-protected' ); ?>
					</button>
					<button type="button" class="wpepp-review-notice__btn-done" id="wpepp-review-done">
						<?php esc_html_e( 'I already left a review', 'wp-edit-password-protected' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: dismiss the review notice.
	 *
	 * POST param `snooze` = 1  → remind after 30 days
	 * POST param `snooze` = 0  → permanent (already did / rated)
	 */
	public function ajax_dismiss_review_notice() {
		check_ajax_referer( 'wpepp_notice_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$snooze = isset( $_POST['snooze'] ) && '1' === $_POST['snooze'];

		if ( $snooze ) {
			update_option( self::REVIEW_DISMISS_OPTION, time() + ( 30 * DAY_IN_SECONDS ) );
		} else {
			update_option( self::REVIEW_DISMISS_OPTION, 'done' );
		}

		wp_send_json_success();
	}

	/* ─── Assets ────────────────────────────────────────────────── */

	/**
	 * Whether any notice UI is currently active and needs its assets.
	 *
	 * Prevents loading notice CSS/JS on every wp-admin page forever —
	 * once notices are dismissed, nothing loads.
	 *
	 * @return bool
	 */
	private function needs_notice_assets() {
		if ( $this->should_show_pro_notice() ) {
			return true;
		}

		return $this->should_show_review_notice();
	}

	/**
	 * Enqueue notice CSS + JS — only while notice UI is actually present.
	 */
	public function enqueue_notice_assets() {
		if ( ! $this->needs_notice_assets() ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-notice',
			WPEPP_URL . '/assets/css/notice.css',
			[],
			WPEPP_VERSION
		);

		wp_enqueue_script(
			'wpepp-notice',
			WPEPP_URL . '/assets/js/notice.js',
			[],
			WPEPP_VERSION,
			true
		);

		wp_localize_script( 'wpepp-notice', 'wpeppNotice', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpepp_notice_nonce' ),
		] );
	}
}
