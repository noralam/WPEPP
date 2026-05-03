<?php
/**
 * Admin notices — menu "NEW" badge + Pro upgrade banner.
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
	const DISMISS_OPTION = 'wpepp_pro_notice_dismissed';

	/**
	 * Option key for the 5-star review notice dismiss.
	 */
	const REVIEW_DISMISS_OPTION = 'wpepp_review_dismissed';

	/**
	 * Days after activation before showing the review notice.
	 */
	const REVIEW_DAYS = 7;

	/**
	 * Option key for dismiss count.
	 */
	const DISMISS_COUNT_OPTION = 'wpepp_pro_notice_dismiss_count';

	/**
	 * Option key for "NEW" badge dismiss.
	 */
	const NEW_BADGE_OPTION = 'wpepp_new_badge_dismissed';

	/**
	 * Boot.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_new_badge' ], 999 );
		add_action( 'admin_footer', [ $this, 'render_menu_tooltip' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_notice_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_pro_notice' ] );
		add_action( 'admin_notices', [ $this, 'render_review_notice' ] );
		add_action( 'wp_ajax_wpepp_dismiss_pro_notice', [ $this, 'ajax_dismiss_pro_notice' ] );
		add_action( 'wp_ajax_wpepp_dismiss_new_badge', [ $this, 'ajax_dismiss_new_badge' ] );
		add_action( 'wp_ajax_wpepp_dismiss_review_notice', [ $this, 'ajax_dismiss_review_notice' ] );
	}

	/* ─── 1. Menu "NEW" badge ───────────────────────────────────── */

	/**
	 * Append a pulsing "NEW" badge to the WPEPP menu label.
	 */
	public function add_menu_new_badge() {
		if ( $this->is_new_badge_dismissed() ) {
			return;
		}

		global $menu;

		foreach ( $menu as &$item ) {
			if ( isset( $item[2] ) && 'wpepp-settings' === $item[2] ) {
				$item[0] .= ' <span class="wpepp-menu-new-badge">NEW</span>';
				break;
			}
		}
	}

	/**
	 * Check if the NEW badge should be hidden.
	 * It shows for 14 days after install, then auto-hides.
	 * Users can also click it to dismiss early.
	 *
	 * @return bool
	 */
	private function is_new_badge_dismissed() {
		// Already dismissed by user.
		if ( get_option( self::NEW_BADGE_OPTION ) ) {
			return true;
		}

		// Auto-hide after 14 days of install.
		$install_date = get_option( 'wpepp_install_date' );
		if ( $install_date && strtotime( $install_date ) < strtotime( '-14 days' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * AJAX: dismiss the NEW badge.
	 */
	public function ajax_dismiss_new_badge() {
		check_ajax_referer( 'wpepp_notice_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		update_option( self::NEW_BADGE_OPTION, 1 );
		wp_send_json_success();
	}

	/**
	 * Render the tooltip popup next to the WPEPP menu item.
	 */
	public function render_menu_tooltip() {
		if ( $this->is_new_badge_dismissed() ) {
			return;
		}
		?>
		<div class="wpepp-menu-tooltip" id="wpepp-menu-tooltip">
			<div class="wpepp-menu-tooltip__arrow"></div>
			<div class="wpepp-menu-tooltip__header">
				<span class="wpepp-menu-tooltip__icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				</span>
				<strong><?php esc_html_e( 'WPEPP just got a big update!', 'wp-edit-password-protected' ); ?></strong>
			</div>
			<p class="wpepp-menu-tooltip__desc">
				<?php esc_html_e( 'New dashboard, login customizer, AI crawler blocker, 2FA and more. Explore the new features now!', 'wp-edit-password-protected' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>"><?php esc_html_e( 'Learn more', 'wp-edit-password-protected' ); ?></a>
			</p>
			<div class="wpepp-menu-tooltip__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>" class="wpepp-menu-tooltip__btn-got-it" id="wpepp-tooltip-got-it">
					<?php esc_html_e( 'Got it', 'wp-edit-password-protected' ); ?>
				</a>
				<button type="button" class="wpepp-menu-tooltip__btn-dismiss" id="wpepp-tooltip-dismiss">
					&#10005; <?php esc_html_e( 'Dismiss', 'wp-edit-password-protected' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/* ─── 2. Pro upgrade notice ─────────────────────────────────── */

	/**
	 * Should the Pro upgrade banner show?
	 *
	 * @return bool
	 */
	private function should_show_pro_notice() {
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
	 * Render the Pro upgrade notice.
	 */
	public function render_pro_notice() {
		if ( ! $this->should_show_pro_notice() ) {
			return;
		}

		$pro_url     = 'https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing';
		$coupon_code = 'wpepp100';
		?>
		<div class="wpepp-pro-notice" id="wpepp-pro-notice">

			<div class="wpepp-pro-notice__top-bar"></div>

			<div class="wpepp-pro-notice__body">

				<div class="wpepp-pro-notice__header">
					<span class="wpepp-pro-notice__badge">PRO</span>
					<div class="wpepp-pro-notice__header-text">
						<h3><?php esc_html_e( 'Unlock the Full Power of WPEPP Pro – Essential Security, Password Protect & Login Page Customizer ', 'wp-edit-password-protected' ); ?></h3>
						<p><?php esc_html_e( 'Take your site security & password protection to the next level — advanced login customizer, conditional content, role-based locks, TOTP 2FA and more!', 'wp-edit-password-protected' ); ?></p>
					</div>
				</div>

				<div class="wpepp-pro-notice__features">
					<span>✦ <?php esc_html_e( 'Advanced Login Customizer', 'wp-edit-password-protected' ); ?></span>
					<span>✦ <?php esc_html_e( 'Role-Based Content Lock', 'wp-edit-password-protected' ); ?></span>
					<span>✦ <?php esc_html_e( 'TOTP Two-Factor Auth', 'wp-edit-password-protected' ); ?></span>
					<span>✦ <?php esc_html_e( 'Conditional Display Rules', 'wp-edit-password-protected' ); ?></span>
					<span>✦ <?php esc_html_e( 'Device & Schedule Targeting', 'wp-edit-password-protected' ); ?></span>
					<span>✦ <?php esc_html_e( 'Priority Support', 'wp-edit-password-protected' ); ?></span>
				</div>

				<div class="wpepp-pro-notice__offer-bar">
					<div class="wpepp-pro-notice__offer-left">
						<span class="wpepp-pro-notice__limited-badge"><?php esc_html_e( 'LIMITED OFFER', 'wp-edit-password-protected' ); ?></span>
						<span class="wpepp-pro-notice__offer-text">
							<?php
								esc_html_e( 'Early bird price $21 for the first 100 customers — Increase your site security. Price will increase soon.', 'wp-edit-password-protected' );
							
							?>
						</span>
					</div>

					<div class="wpepp-pro-notice__offer-actions">
						<a href="<?php echo esc_url( $pro_url ); ?>" class="wpepp-pro-notice__btn-upgrade" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Upgrade Now — $21', 'wp-edit-password-protected' ); ?> →
						</a>
						<a href="<?php echo esc_url( $pro_url ); ?>" class="wpepp-pro-notice__btn-details" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View Details', 'wp-edit-password-protected' ); ?>
						</a>
					</div>
				</div>

			</div><!-- .body -->

			<div class="wpepp-pro-notice__footer">
				<button type="button" class="wpepp-pro-notice__dismiss" id="wpepp-dismiss-pro-notice">
					<?php esc_html_e( 'Dismiss', 'wp-edit-password-protected' ); ?>
				</button>
			</div>

		</div><!-- .wpepp-pro-notice -->
		<?php
	}

	/**
	 * AJAX: dismiss the Pro notice.
	 * First dismiss → 30 days, every subsequent dismiss → 3 days.
	 */
	public function ajax_dismiss_pro_notice() {
		check_ajax_referer( 'wpepp_notice_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		// Always hide for 30 days (1 month).
		$days = 30;

		update_option( self::DISMISS_OPTION, time() + ( $days * DAY_IN_SECONDS ) );

		wp_send_json_success( [ 'hidden_for_days' => $days ] );
	}

	/* ─── 3. 5-star review notice ──────────────────────────────── */

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

		$review_url = 'https://wordpress.org/support/plugin/wp-edit-password-protected/reviews/?filter=5/#new-post';
		?>
		<div class="wpepp-review-notice" id="wpepp-review-notice">
			<div class="wpepp-review-notice__stars" aria-hidden="true">★★★★★</div>
			<div class="wpepp-review-notice__content">
				<p class="wpepp-review-notice__title">
				<?php esc_html_e( 'Thank you for using WPEPP – Essential Security, Password Protect & Login Page Customizer!', 'wp-edit-password-protected' ); ?>
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
	 * Enqueue notice CSS + JS on all admin pages.
	 */
	public function enqueue_notice_assets() {
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
