<?php
/**
 * Member-only gate page template.
 *
 * Renders the login gate for pages using the "Member Only (Login Required)" template.
 * Uses the same card / form styling as Content Lock for consistency.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * Read settings from the new JSON option saved by the React admin.
 * Falls back to sensible defaults.
 */
$wpepp_raw      = get_option( 'wpepp_member_template', '{}' );
$wpepp_settings = json_decode( $wpepp_raw, true );

if ( empty( $wpepp_settings ) || ! is_array( $wpepp_settings ) ) {
	$wpepp_settings = [];
}

$wpepp_mode       = sanitize_text_field( $wpepp_settings['mode'] ?? 'login' );
$wpepp_info_title = $wpepp_settings['infotitle'] ?? __( 'This content is password protected for members only', 'wp-edit-password-protected' );
$wpepp_title_tag  = sanitize_text_field( $wpepp_settings['titletag'] ?? 'h2' );
$wpepp_text       = $wpepp_settings['text'] ?? __( 'This content is password protected for members only. If you want to see this content please login.', 'wp-edit-password-protected' );
$wpepp_text_align = sanitize_text_field( $wpepp_settings['text_align'] ?? 'center' );
$wpepp_login_mode = sanitize_text_field( $wpepp_settings['login_mode'] ?? 'form' );
$wpepp_btn_text   = $wpepp_settings['btntext'] ?? __( 'Login', 'wp-edit-password-protected' );
$wpepp_btn_class  = $wpepp_settings['btnclass'] ?? 'btn button';
$wpepp_form_head  = $wpepp_settings['form_head'] ?? __( 'Login Form', 'wp-edit-password-protected' );

$wpepp_allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span' ];
if ( ! in_array( $wpepp_title_tag, $wpepp_allowed_tags, true ) ) {
	$wpepp_title_tag = 'h2';
}

/* Determine if the user should see the content. */
$wpepp_show_content = is_user_logged_in() && ! is_customize_preview();

/* Enqueue the same stylesheet used by Content Lock. */
wp_enqueue_style(
	'wpepp-content-lock',
	WPEPP_URL . '/assets/css/frontend-content-lock.css',
	[],
	defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
);

get_header();
?>

<?php if ( $wpepp_show_content ) : ?>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
		if ( comments_open() || get_comments_number() ) :
			comments_template();
		endif;
	endwhile;
	?>

<?php elseif ( 'popup' === $wpepp_mode ) : ?>
	<?php /* ── Glassdoor popup: blurred page content + overlay modal ── */ ?>
	<div class="wpepp-popup-lock-wrapper">
		<div class="wpepp-popup-lock-blur" aria-hidden="true">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					</header>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<div class="wpepp-popup-lock-overlay">
			<div class="wpepp-popup-lock-modal">
				<div class="wpepp-popup-lock-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
					</svg>
				</div>

				<?php if ( ! empty( $wpepp_info_title ) ) : ?>
					<h3 class="wpepp-popup-lock-title"><?php echo esc_html( $wpepp_info_title ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( $wpepp_text ) ) : ?>
					<p class="wpepp-popup-lock-message"><?php echo wp_kses_post( $wpepp_text ); ?></p>
				<?php endif; ?>

				<div class="wpepp-popup-lock-form">
					<?php if ( ! empty( $wpepp_form_head ) ) : ?>
						<h4 style="margin:0 0 0.75em;font-weight:600;"><?php echo esc_html( $wpepp_form_head ); ?></h4>
					<?php endif; ?>
					<?php
					wp_login_form( [
						'echo'           => true,
						'redirect'       => get_permalink(),
						'form_id'        => 'wpepp-lock-login-form',
						'label_username' => esc_html( $wpepp_settings['user_placeholder'] ?? __( 'Username', 'wp-edit-password-protected' ) ),
						'label_password' => esc_html( $wpepp_settings['password_placeholder'] ?? __( 'Password', 'wp-edit-password-protected' ) ),
						'label_remember' => esc_html( $wpepp_settings['remember_text'] ?? __( 'Remember Me', 'wp-edit-password-protected' ) ),
						'label_log_in'   => esc_html( $wpepp_settings['formbtn_text'] ?? __( 'Login', 'wp-edit-password-protected' ) ),
						'remember'       => 'on' === ( $wpepp_settings['form_remember'] ?? 'on' ),
					] );
					?>
				</div>

				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wpepp-popup-lock-home">
					&larr; <?php esc_html_e( 'Go to Homepage', 'wp-edit-password-protected' ); ?>
				</a>
			</div>
		</div>
	</div>
<?php else : ?>

	<div class="wpepp-content-locked" style="text-align:<?php echo esc_attr( $wpepp_text_align ); ?>;">
		<div class="wpepp-lock-icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
				<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
			</svg>
		</div>

		<?php if ( ! empty( $wpepp_info_title ) ) : ?>
			<<?php echo esc_html( $wpepp_title_tag ); ?> class="wpepp-lock-message" style="font-size:1.25em;font-weight:700;">
				<?php echo esc_html( $wpepp_info_title ); ?>
			</<?php echo esc_html( $wpepp_title_tag ); ?>>
		<?php endif; ?>

		<?php if ( ! empty( $wpepp_text ) ) : ?>
			<div class="wpepp-lock-message"><?php echo wp_kses_post( $wpepp_text ); ?></div>
		<?php endif; ?>

		<?php if ( 'info' !== $wpepp_mode ) : // Show login option only in login mode. ?>

			<?php if ( 'button' === $wpepp_login_mode ) : ?>
				<?php
				$wpepp_login_url = ! empty( $wpepp_settings['login_url'] )
					? esc_url( $wpepp_settings['login_url'] )
					: esc_url( wp_login_url( get_permalink() ) );
				?>
				<a href="<?php echo esc_url( $wpepp_login_url ); ?>" class="wpepp-lock-login-link">
					<?php echo esc_html( $wpepp_btn_text ); ?>
				</a>
			<?php else : ?>
				<?php if ( ! empty( $wpepp_form_head ) ) : ?>
					<h3 style="margin:1.5em 0 0.5em;font-weight:700;"><?php echo esc_html( $wpepp_form_head ); ?></h3>
				<?php endif; ?>
				<?php
				wp_login_form( [
					'echo'           => true,
					'redirect'       => get_permalink(),
					'form_id'        => 'wpepp-lock-login-form',
					'label_username' => esc_html( $wpepp_settings['user_placeholder'] ?? __( 'Username', 'wp-edit-password-protected' ) ),
					'label_password' => esc_html( $wpepp_settings['password_placeholder'] ?? __( 'Password', 'wp-edit-password-protected' ) ),
					'label_remember' => esc_html( $wpepp_settings['remember_text'] ?? __( 'Remember Me', 'wp-edit-password-protected' ) ),
					'label_log_in'   => esc_html( $wpepp_settings['formbtn_text'] ?? __( 'Login', 'wp-edit-password-protected' ) ),
					'remember'       => 'on' === ( $wpepp_settings['form_remember'] ?? 'on' ),
				] );
				?>
			<?php endif; ?>

		<?php endif; ?>
	</div>

<?php endif; ?>

<?php
get_footer();
