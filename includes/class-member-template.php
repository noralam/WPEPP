<?php
/**
 * Member-only page template.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Member_Template
 *
 * Registers a custom page template ("Member Only") without requiring
 * an actual template file in the theme. Uses the template_include filter
 * to load our own template when a page selects it.
 */
class WPEPP_Member_Template {

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	const TEMPLATE_SLUG = 'wpepp-member-only';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_filter( 'theme_page_templates', [ $this, 'add_template' ] );
		add_filter( 'template_include', [ $this, 'load_template' ] );
	}

	/**
	 * Add our template to the page template dropdown.
	 *
	 * @param array $templates Existing page templates.
	 * @return array
	 */
	public function add_template( $templates ) {
		$templates[ self::TEMPLATE_SLUG ] = __( 'Member Only (Login Required)', 'wp-edit-password-protected' );
		return $templates;
	}

	/**
	 * Intercept template loading for pages using our template.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function load_template( $template ) {
		if ( ! is_page() ) {
			return $template;
		}

		$page_template = get_page_template_slug();
		if ( self::TEMPLATE_SLUG !== $page_template ) {
			return $template;
		}

		// If user is logged in, show normal content via the theme's page.php.
		if ( is_user_logged_in() ) {
			return $template;
		}

		// User is logged out — show the member-only gate page.
		return $this->get_gate_template();
	}

	/**
	 * Get the path to our gate template file, or generate inline.
	 *
	 * @return string
	 */
	private function get_gate_template() {
		// Use the bundled template file if it exists.
		$file = WPEPP_PATH . 'admin/admin-only-template.php';
		if ( file_exists( $file ) ) {
			return $file;
		}

		// Fallback: output inline gate page via an anonymous template.
		add_filter( 'template_include', function() {
			self::render_gate_page();
			exit;
		}, 999 );

		return $template ?? '';
	}

	/**
	 * Render the member-only gate page.
	 */
	public static function render_gate_page() {
		$raw      = get_option( 'wpepp_member_template', '{}' );
		$settings = json_decode( $raw, true );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = [];
		}

		$mode       = sanitize_text_field( $settings['mode'] ?? 'login' );
		$style      = sanitize_text_field( $settings['style'] ?? 's1' );
		$info_title = esc_html( $settings['infotitle'] ?? __( 'Members Only', 'wp-edit-password-protected' ) );
		$title_tag  = sanitize_text_field( $settings['titletag'] ?? 'h2' );
		$text       = wp_kses_post( $settings['text'] ?? '' );
		$text_align = sanitize_text_field( $settings['text_align'] ?? 'center' );
		$login_mode = sanitize_text_field( $settings['login_mode'] ?? 'form' );
		$btn_text   = esc_html( $settings['btntext'] ?? __( 'Login', 'wp-edit-password-protected' ) );
		$btn_class  = esc_attr( $settings['btnclass'] ?? 'btn button' );
		$form_head  = esc_html( $settings['form_head'] ?? __( 'Login Form', 'wp-edit-password-protected' ) );

		$allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span' ];
		if ( ! in_array( $title_tag, $allowed_tags, true ) ) {
			$title_tag = 'h2';
		}

		get_header();

		/* Enqueue the Content Lock stylesheet for consistent styling. */
		wp_enqueue_style(
			'wpepp-content-lock',
			WPEPP_URL . '/assets/css/frontend-content-lock.css',
			[],
			defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
		);

		if ( 'popup' === $mode ) :
			/* ── Glassdoor popup: blurred placeholder + overlay modal ── */
			?>
			<div class="wpepp-popup-lock-wrapper">
				<div class="wpepp-popup-lock-blur" aria-hidden="true">
					<article class="page type-page">
						<header class="entry-header">
							<h1 class="entry-title"><?php echo $info_title; // phpcs:ignore ?></h1>
						</header>
						<div class="entry-content">
							<p><?php echo $text; // phpcs:ignore ?></p>
						</div>
					</article>
				</div>

				<div class="wpepp-popup-lock-overlay">
					<div class="wpepp-popup-lock-modal">
						<div class="wpepp-popup-lock-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
								<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
							</svg>
						</div>

						<?php if ( ! empty( $info_title ) ) : ?>
							<h3 class="wpepp-popup-lock-title"><?php echo $info_title; // phpcs:ignore ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $text ) ) : ?>
							<p class="wpepp-popup-lock-message"><?php echo $text; // phpcs:ignore ?></p>
						<?php endif; ?>

						<div class="wpepp-popup-lock-form">
							<?php if ( ! empty( $form_head ) ) : ?>
								<h4 style="margin:0 0 0.75em;font-weight:600;"><?php echo $form_head; // phpcs:ignore ?></h4>
							<?php endif; ?>
							<?php
							wp_login_form( [
								'redirect'       => get_permalink(),
								'form_id'        => 'wpepp-lock-login-form',
								'label_username' => esc_html( $settings['user_placeholder'] ?? __( 'Username', 'wp-edit-password-protected' ) ),
								'label_password' => esc_html( $settings['password_placeholder'] ?? __( 'Password', 'wp-edit-password-protected' ) ),
								'label_remember' => esc_html( $settings['remember_text'] ?? __( 'Remember Me', 'wp-edit-password-protected' ) ),
								'label_log_in'   => esc_html( $settings['formbtn_text'] ?? __( 'Login', 'wp-edit-password-protected' ) ),
								'remember'       => 'on' === ( $settings['form_remember'] ?? 'on' ),
							] );
							?>
						</div>

						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wpepp-popup-lock-home">
							&larr; <?php esc_html_e( 'Go to Homepage', 'wp-edit-password-protected' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		else :
		?>
		<div class="wpepp-content-locked" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
			<div class="wpepp-lock-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
				</svg>
			</div>

			<?php if ( ! empty( $info_title ) ) : ?>
				<<?php echo esc_html( $title_tag ); ?> class="wpepp-lock-message" style="font-size:1.25em;font-weight:700;">
					<?php echo esc_html( $info_title ); ?>
				</<?php echo esc_html( $title_tag ); ?>>
			<?php endif; ?>

			<?php if ( ! empty( $text ) ) : ?>
				<div class="wpepp-lock-message"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd above ?></div>
			<?php endif; ?>

			<?php if ( 'info' !== $mode ) : // Show login option only in login mode. ?>

				<?php if ( 'button' === $login_mode ) : ?>
					<?php
					$login_url = ! empty( $settings['login_url'] )
						? esc_url( $settings['login_url'] )
						: esc_url( wp_login_url( get_permalink() ) );
					?>
					<a href="<?php echo $login_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>" class="wpepp-lock-login-link">
						<?php echo $btn_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>
					</a>
				<?php else : ?>
					<?php if ( ! empty( $form_head ) ) : ?>
						<h3 style="margin:1.5em 0 0.5em;font-weight:700;"><?php echo $form_head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
					<?php endif; ?>
					<?php
					wp_login_form( [
						'redirect'       => get_permalink(),
						'form_id'        => 'wpepp-lock-login-form',
						'label_username' => esc_html( $settings['user_placeholder'] ?? __( 'Username', 'wp-edit-password-protected' ) ),
						'label_password' => esc_html( $settings['password_placeholder'] ?? __( 'Password', 'wp-edit-password-protected' ) ),
						'label_remember' => esc_html( $settings['remember_text'] ?? __( 'Remember Me', 'wp-edit-password-protected' ) ),
						'label_log_in'   => esc_html( $settings['formbtn_text'] ?? __( 'Login', 'wp-edit-password-protected' ) ),
						'remember'       => 'on' === ( $settings['form_remember'] ?? 'on' ),
					] );
					?>
				<?php endif; ?>

			<?php endif; ?>
		</div>
		<?php
		endif;

		get_footer();
	}
}
