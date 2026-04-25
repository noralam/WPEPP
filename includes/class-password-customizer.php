<?php
/**
 * Password form customizer — CSS generator and form renderer.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Password_Customizer
 */
class WPEPP_Password_Customizer {

	/**
	 * Render the custom password form, replacing the default WordPress form.
	 *
	 * @param string $output Default WP password form HTML.
	 * @return string
	 */
	public static function render_form( $output ) {
		global $post;

		$raw      = get_option( 'wpepp_password_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = [];
		}

		$style      = sanitize_text_field( $settings['active_style'] ?? 'one' );
		$allowed    = [ 'one', 'two', 'three', 'four' ];
		if ( ! in_array( $style, $allowed, true ) ) {
			$style = 'four';
		}

		// Styles 3-4 require Pro.
		if ( in_array( $style, [ 'three', 'four' ], true ) && ! wpepp_has_pro_check() ) {
			$style = 'one';
		}

		$form_label  = esc_html( $settings['form_label'] ?? __( 'Password', 'wp-edit-password-protected' ) );
		$label_type  = sanitize_text_field( $settings['form_label_type'] ?? 'label' );
		// Inline / card layouts always use placeholder — label above breaks alignment.
		if ( in_array( $style, [ 'two', 'three', 'four' ], true ) ) {
			$label_type = 'placeholder';
		}
		$btn_text    = esc_html( $settings['form_btn_text'] ?? __( 'Submit', 'wp-edit-password-protected' ) );
		$top_header  = esc_html( $settings['top_header'] ?? __( 'This content is password protected for members only', 'wp-edit-password-protected' ) );
		$top_content = wp_kses_post( $settings['top_content'] ?? __( 'For more public resources check out our followed link.', 'wp-edit-password-protected' ) );
		$bot_header  = esc_html( $settings['bottom_header'] ?? '' );
		$bot_content = wp_kses_post( $settings['bottom_content'] ?? '' );
		$error_text  = esc_html( $settings['form_errortext'] ?? '' );
		$error_pos   = sanitize_text_field( $settings['error_text_position'] ?? 'top' );
		$show_top    = 'off' !== ( $settings['show_top_text'] ?? 'on' );
		$show_bottom = 'on' === ( $settings['show_bottom_text'] ?? 'off' );
		$show_social = 'on' === ( $settings['show_social'] ?? 'off' );
		$social_pos  = esc_attr( $settings['icons_vposition'] ?? 'top' );
		$social_align = esc_attr( $settings['icons_alignment'] ?? 'right' );
		$icon_shape  = sanitize_html_class( $settings['icons_style'] ?? 'square' );

		$post_id = $post ? $post->ID : 0;

		// Check if wrong password was entered.
		$has_error = isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) && post_password_required( $post_id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		?>
		<div class="wpepp-password-form wpepp-style-<?php echo esc_attr( $style ); ?>">
			<?php self::render_logo( $settings ); ?>

			<?php if ( $has_error && ! empty( $error_text ) && 'top' === $error_pos ) : ?>
				<div class="wpepp-error-message"><?php echo esc_html( $error_text ); ?></div>
			<?php endif; ?>

			<?php if ( $show_social && 'top' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_top && ( ! empty( $top_header ) || ! empty( $top_content ) ) ) : ?>
				<div class="wpepp-password-top-text" style="text-align:<?php echo esc_attr( $settings['top_text_align'] ?? 'center' ); ?>;">
					<?php if ( ! empty( $top_header ) ) : ?>
						<h3><?php echo $top_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $top_content ) ) : ?>
						<div class="wpepp-top-content"><?php echo $top_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_social && 'middle' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-social-middle wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>

			<form class="wpepp-password-form-inner" action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" method="post">
				<p>
					<?php if ( 'placeholder' === $label_type ) : ?>
						<input name="post_password" id="pwbox-<?php echo esc_attr( $post_id ); ?>" type="password" size="20" autocomplete="off" placeholder="<?php echo esc_attr( $form_label ); ?>">
					<?php else : ?>
						<label for="pwbox-<?php echo esc_attr( $post_id ); ?>"><?php echo $form_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></label>
						<input name="post_password" id="pwbox-<?php echo esc_attr( $post_id ); ?>" type="password" size="20" autocomplete="off">
					<?php endif; ?>
				</p>
				<p class="wpepp-submit">
					<input type="submit" name="Submit" value="<?php echo esc_attr( $btn_text ); ?>">
				</p>
			</form>

			<?php if ( $has_error && ! empty( $error_text ) && 'bottom' === $error_pos ) : ?>
				<div class="wpepp-error-message"><?php echo esc_html( $error_text ); ?></div>
			<?php endif; ?>

			<?php if ( $show_bottom && ( ! empty( $bot_header ) || ! empty( $bot_content ) ) ) : ?>
				<div class="wpepp-password-bottom-text" style="text-align:<?php echo esc_attr( $settings['bottom_text_align'] ?? 'left' ); ?>;">
					<?php if ( ! empty( $bot_header ) ) : ?>
						<h3><?php echo $bot_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $bot_content ) ) : ?>
						<div class="wpepp-bottom-content"><?php echo $bot_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_social && 'bottom' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the custom password form for the site-wide password page.
	 *
	 * Uses the same styling/layout as the per-post password form but with
	 * site-password-specific form fields (nonce, redirect, field name).
	 *
	 * @param bool   $has_error   Whether the visitor submitted a wrong password.
	 * @param string $redirect_url URL to redirect after successful password entry.
	 * @return string HTML output.
	 */
	public static function render_site_form( $has_error = false, $redirect_url = '' ) {
		$raw      = get_option( 'wpepp_password_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return '';
		}

		$style   = sanitize_text_field( $settings['active_style'] ?? 'one' );
		$allowed = [ 'one', 'two', 'three', 'four' ];
		if ( ! in_array( $style, $allowed, true ) ) {
			$style = 'four';
		}

		if ( in_array( $style, [ 'three', 'four' ], true ) && ! wpepp_has_pro_check() ) {
			$style = 'one';
		}

		$form_label   = esc_html( $settings['form_label'] ?? __( 'Password', 'wp-edit-password-protected' ) );
		$label_type   = sanitize_text_field( $settings['form_label_type'] ?? 'label' );
		// Inline and Vertical Card layouts always use placeholder.
		if ( in_array( $style, [ 'two', 'three', 'four' ], true ) ) {
			$label_type = 'placeholder';
		}
		$btn_text     = esc_html( $settings['form_btn_text'] ?? __( 'Submit', 'wp-edit-password-protected' ) );
		$top_header   = esc_html( $settings['top_header'] ?? '' );
		$top_content  = wp_kses_post( $settings['top_content'] ?? '' );
		$bot_header   = esc_html( $settings['bottom_header'] ?? '' );
		$bot_content  = wp_kses_post( $settings['bottom_content'] ?? '' );
		$error_text   = esc_html( $settings['form_errortext'] ?? '' );
		$error_pos    = sanitize_text_field( $settings['error_text_position'] ?? 'top' );
		$show_top     = 'on' === ( $settings['show_top_text'] ?? 'off' );
		$show_bottom  = 'on' === ( $settings['show_bottom_text'] ?? 'off' );
		$show_social  = 'on' === ( $settings['show_social'] ?? 'off' );
		$social_pos   = esc_attr( $settings['icons_vposition'] ?? 'top' );
		$social_align = esc_attr( $settings['icons_alignment'] ?? 'right' );
		$icon_shape   = sanitize_html_class( $settings['icons_style'] ?? 'square' );

		ob_start();
		?>
		<div class="wpepp-password-form wpepp-style-<?php echo esc_attr( $style ); ?>">
			<?php self::render_logo( $settings ); ?>

			<?php if ( $has_error && ! empty( $error_text ) && 'top' === $error_pos ) : ?>
				<div class="wpepp-error-message"><?php echo esc_html( $error_text ); ?></div>
			<?php endif; ?>

			<?php if ( $show_social && 'top' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_top && ( ! empty( $top_header ) || ! empty( $top_content ) ) ) : ?>
				<div class="wpepp-password-top-text" style="text-align:<?php echo esc_attr( $settings['top_text_align'] ?? 'center' ); ?>;">
					<?php if ( ! empty( $top_header ) ) : ?>
						<h3><?php echo $top_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $top_content ) ) : ?>
						<div class="wpepp-top-content"><?php echo $top_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_social && 'middle' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-social-middle wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>

			<form class="wpepp-password-form-inner" action="" method="post">
				<?php wp_nonce_field( 'wpepp_site_password', 'wpepp_site_password_nonce' ); ?>
				<input type="hidden" name="wpepp_site_redirect" value="<?php echo esc_url( $redirect_url ); ?>">
				<p>
					<?php if ( 'placeholder' === $label_type ) : ?>
						<input name="wpepp_site_password" id="wpepp-site-pw" type="password" autocomplete="off" autofocus placeholder="<?php echo esc_attr( $form_label ); ?>">
					<?php else : ?>
						<label for="wpepp-site-pw"><?php echo $form_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></label>
						<input name="wpepp_site_password" id="wpepp-site-pw" type="password" autocomplete="off" autofocus>
					<?php endif; ?>
				</p>
				<p class="wpepp-submit">
					<input type="submit" name="Submit" value="<?php echo esc_attr( $btn_text ); ?>">
				</p>
			</form>

			<?php if ( $has_error && ! empty( $error_text ) && 'bottom' === $error_pos ) : ?>
				<div class="wpepp-error-message"><?php echo esc_html( $error_text ); ?></div>
			<?php endif; ?>

			<?php if ( $show_bottom && ( ! empty( $bot_header ) || ! empty( $bot_content ) ) ) : ?>
				<div class="wpepp-password-bottom-text" style="text-align:<?php echo esc_attr( $settings['bottom_text_align'] ?? 'left' ); ?>;">
					<?php if ( ! empty( $bot_header ) ) : ?>
						<h3><?php echo $bot_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $bot_content ) ) : ?>
						<div class="wpepp-bottom-content"><?php echo $bot_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_social && 'bottom' === $social_pos ) : ?>
				<div class="wpepp-social-icons wpepp-social-<?php echo esc_attr( $social_align ); ?> wpepp-icon-shape-<?php echo esc_attr( $icon_shape ); ?>">
					<?php self::render_social_icons( $settings ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render social icons as inline SVG.
	 *
	 * @param array $settings Password settings.
	 */
	public static function render_social_icons( $settings ) {
		$networks = [
			'facebook'  => [ 'url' => $settings['link_facebook'] ?? '',  'path' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ],
			'twitter'   => [ 'url' => $settings['link_twitter'] ?? '',   'path' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>' ],
			'youtube'   => [ 'url' => $settings['link_youtube'] ?? '',   'path' => '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>' ],
			'instagram' => [ 'url' => $settings['link_instagram'] ?? '', 'path' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>' ],
			'linkedin'  => [ 'url' => $settings['link_linkedin'] ?? '',  'path' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>' ],
			'pinterest' => [ 'url' => $settings['link_pinterest'] ?? '', 'path' => '<path d="M12 2C6.48 2 2 6.48 2 12c0 4.25 2.67 7.9 6.44 9.34-.09-.78-.17-1.99.04-2.85.19-.78 1.22-5.16 1.22-5.16s-.31-.62-.31-1.54c0-1.45.84-2.53 1.89-2.53.89 0 1.32.67 1.32 1.47 0 .9-.57 2.24-.87 3.48-.25 1.05.52 1.9 1.55 1.9 1.86 0 3.29-1.96 3.29-4.79 0-2.51-1.8-4.26-4.38-4.26-2.98 0-4.74 2.24-4.74 4.55 0 .9.35 1.87.78 2.39.09.1.1.19.07.3-.08.33-.26 1.04-.29 1.18-.05.19-.15.23-.35.14-1.31-.61-2.13-2.52-2.13-4.06 0-3.31 2.41-6.35 6.94-6.35 3.65 0 6.48 2.6 6.48 6.07 0 3.62-2.28 6.54-5.46 6.54-1.07 0-2.07-.55-2.41-1.21l-.66 2.5c-.24.91-.88 2.05-1.31 2.75A10 10 0 0 0 22 12c0-5.52-4.48-10-10-10z"/>' ],
			'tumblr'    => [ 'url' => $settings['link_tumblr'] ?? '',    'path' => '<path d="M14.5 2H9.5v5H7v3h2.5v5.5a5 5 0 0 0 5 5h2.5v-3h-2.5a2 2 0 0 1-2-2V10h4V7h-4V2z"/>' ],
		];

		$custom_url = $settings['link_custom'] ?? '';

		$icon_style = sanitize_html_class( $settings['icons_style'] ?? 'square' );

		foreach ( $networks as $name => $data ) {
			if ( empty( $data['url'] ) ) {
				continue;
			}
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" class="wpepp-social-icon wpepp-social-%s wpepp-icon-%s" aria-label="%s">'
					. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">%s</svg>'
				. '</a>',
				esc_url( $data['url'] ),
				esc_attr( $name ),
				esc_attr( $icon_style ),
				/* translators: %s: Social network name */
				esc_attr( sprintf( __( 'Visit our %s page', 'wp-edit-password-protected' ), ucfirst( $name ) ) ),
				$data['path'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG path data, no user input.
			);
		}

		if ( ! empty( $custom_url ) ) {
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" class="wpepp-social-icon wpepp-social-custom wpepp-icon-%s" aria-label="%s">'
					. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
						. '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>'
						. '<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'
					. '</svg>'
				. '</a>',
				esc_url( $custom_url ),
				esc_attr( $icon_style ),
				esc_attr__( 'Visit our website', 'wp-edit-password-protected' )
			);
		}
	}

	/**
	 * Render the logo at the top of the password form.
	 *
	 * @param array $settings Password settings.
	 */
	private static function render_logo( $settings ) {
		$type = sanitize_text_field( $settings['logo_type'] ?? 'none' );

		if ( 'none' === $type ) {
			return;
		}

		$width  = absint( $settings['logo_width'] ?? 120 );
		$height = absint( $settings['logo_height'] ?? 60 );

		echo '<div class="wpepp-form-logo">';

		if ( 'site' === $type ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			if ( $custom_logo_id ) {
				$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
				if ( $logo_url ) {
					printf(
						'<img src="%s" alt="%s" style="width:%dpx;height:%dpx;object-fit:contain;">',
						esc_url( $logo_url ),
						esc_attr( get_bloginfo( 'name' ) ),
						$width,
						$height
					);
				}
			} else {
				printf(
					'<span class="wpepp-form-logo-text" style="font-size:24px;color:#1e1e1e;">%s</span>',
					esc_html( get_bloginfo( 'name' ) )
				);
			}
		} elseif ( 'custom' === $type ) {
			$image = esc_url( $settings['logo_image'] ?? '' );
			if ( $image ) {
				printf(
					'<img src="%s" alt="%s" style="width:%dpx;height:%dpx;object-fit:contain;">',
					$image,
					esc_attr( get_bloginfo( 'name' ) ),
					$width,
					$height
				);
			}
		} elseif ( 'text' === $type ) {
			$text      = esc_html( $settings['logo_text'] ?? '' );
			$font_size = absint( $settings['logo_text_font_size'] ?? 24 );
			$color     = self::sanitize_color( $settings['logo_text_color'] ?? '#1e1e1e' );
			if ( $text ) {
				printf(
					'<span class="wpepp-form-logo-text" style="font-size:%dpx;color:%s;">%s</span>',
					$font_size,
					$color,
					$text
				);
			}
		}

		echo '</div>';
	}

	/**
	 * Sanitize a CSS color value (hex, rgb, rgba, hsl, hsla, named).
	 *
	 * @param string $color The color value.
	 * @return string Sanitized color or empty string.
	 */
	private static function sanitize_color( $color ) {
		$color = trim( wp_strip_all_tags( $color ) );
		if ( empty( $color ) ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-fA-F]{3,8})$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[\d.,\s%\/]+\)$/i', $color ) ) {
			return $color;
		}
		return '';
	}

	/**
	 * Convert a dimension value (array or scalar) to CSS shorthand.
	 *
	 * @param mixed  $val  Dimension value — array with top/right/bottom/left or scalar.
	 * @param string $unit CSS unit.
	 * @return string
	 */
	private static function dim_has_value( $val ) {
		if ( is_array( $val ) ) {
			return ( $val['top'] ?? 0 ) || ( $val['right'] ?? 0 ) || ( $val['bottom'] ?? 0 ) || ( $val['left'] ?? 0 );
		}
		return (bool) $val;
	}

	private static function dim_to_css( $val, $unit = 'px' ) {
		if ( is_array( $val ) ) {
			return sprintf(
				'%d%s %d%s %d%s %d%s',
				absint( $val['top'] ?? 0 ), $unit,
				absint( $val['right'] ?? 0 ), $unit,
				absint( $val['bottom'] ?? 0 ), $unit,
				absint( $val['left'] ?? 0 ), $unit
			);
		}
		return absint( $val ) . $unit;
	}

	/**
	 * Generate dynamic CSS for the password form.
	 *
	 * @param array $settings Password settings.
	 * @return string
	 */
	public static function generate_css( $settings ) {
		$css = '';

		// Scoped body selector — targets only password-protected post pages
		// and the site-wide password page to avoid conflicts with other pages.
		$body = 'body.password-protected-enabled,body.wpepp-site-password-body';

		// Page background.
		$bg_type = $settings['page_background_type'] ?? 'color';
		if ( $bg_type === 'color' && ! empty( $settings['page_background_color'] ) ) {
			$css .= $body . '{background-color:' . self::sanitize_color( $settings['page_background_color'] ) . ';}';
		} elseif ( $bg_type === 'image' && ! empty( $settings['page_background_image'] ) ) {
			$image = esc_url( $settings['page_background_image'] );
			$pos   = sanitize_text_field( $settings['page_background_position'] ?? 'center center' );
			$size  = sanitize_text_field( $settings['page_background_size'] ?? 'cover' );
			$css .= $body . '{background-image:url(' . $image . ');background-position:' . $pos . ';background-size:' . $size . ';background-repeat:no-repeat;background-attachment:fixed;}';
		} elseif ( $bg_type === 'gradient' && ! empty( $settings['page_background_gradient'] ) ) {
			$css .= $body . '{background:' . wp_strip_all_tags( $settings['page_background_gradient'] ) . ';}';
		} elseif ( $bg_type === 'video' && ! empty( $settings['page_background_color'] ) ) {
			$css .= $body . '{background-color:' . self::sanitize_color( $settings['page_background_color'] ) . ';}';
		}

		// Form outer wrapper.
		if ( ! empty( $settings['form_outer_background'] ) || isset( $settings['form_outer_border_radius'] ) || isset( $settings['form_outer_padding'] ) ) {
			$css .= 'html body .wpepp-password-form{';
			if ( ! empty( $settings['form_outer_background'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $settings['form_outer_background'] ) . ';';
			}
			if ( isset( $settings['form_outer_border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $settings['form_outer_border_radius'] ) . ';';
			}
			if ( isset( $settings['form_outer_padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $settings['form_outer_padding'] ) . ';';
			}
			$css .= '}';
		}

		// Form container.
		if ( ! empty( $settings['form_background'] ) || isset( $settings['form_border_radius'] ) || isset( $settings['form_padding'] ) || ! empty( $settings['form_text_color'] ) ) {
			$css .= 'html body .wpepp-password-form form.wpepp-password-form-inner{';
			if ( ! empty( $settings['form_background'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $settings['form_background'] ) . ';';
			}
			if ( isset( $settings['form_border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $settings['form_border_radius'] ) . ';';
			}
			if ( isset( $settings['form_padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $settings['form_padding'] ) . ';';
			}
			if ( ! empty( $settings['form_text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $settings['form_text_color'] ) . ';';
			}
			$css .= '}';
		}

		$shadow = $settings['form_shadow'] ?? '';
		$shadow_map = [
			'small'  => '0 1px 3px rgba(0,0,0,0.12)',
			'medium' => '0 4px 6px rgba(0,0,0,0.1)',
			'large'  => '0 10px 25px rgba(0,0,0,0.15)',
		];
		if ( isset( $shadow_map[ $shadow ] ) ) {
			$css .= 'html body .wpepp-password-form{box-shadow:' . $shadow_map[ $shadow ] . ';}';
		}

		// Heading.
		if ( ! empty( $settings['heading_color'] ) || ! empty( $settings['heading_font_size'] ) ) {
			$css .= '.wpepp-password-form h3,.wpepp-password-top-text h3{';
			if ( ! empty( $settings['heading_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $settings['heading_color'] ) . ';';
			}
			if ( ! empty( $settings['heading_font_size'] ) ) {
				$css .= 'font-size:' . absint( $settings['heading_font_size'] ) . 'px;';
			}
			$css .= '}';
		}

		// Heading background overlay.
		if ( ! empty( $settings['heading_show_background'] ) ) {
			$css .= '.wpepp-password-form h3,.wpepp-password-top-text h3{';
			if ( ! empty( $settings['heading_background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $settings['heading_background_color'] ) . ';';
			}
			if ( isset( $settings['heading_padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $settings['heading_padding'] ) . ';';
			}
			if ( isset( $settings['heading_border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $settings['heading_border_radius'] ) . ';';
			}
			$css .= 'display:inline-block;}';
		}

		// Input fields.
		if ( ! empty( $settings['input_background'] ) || ! empty( $settings['input_text_color'] ) || ! empty( $settings['input_border_color'] ) || isset( $settings['input_border_radius'] ) || isset( $settings['input_padding'] ) ) {
			$css .= '.wpepp-password-form input[type="password"]{';
			if ( ! empty( $settings['input_background'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $settings['input_background'] ) . ';';
			}
			if ( ! empty( $settings['input_text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $settings['input_text_color'] ) . ';';
			}
			if ( ! empty( $settings['input_border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $settings['input_border_color'] ) . ';';
			}
			if ( isset( $settings['input_border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $settings['input_border_radius'] ) . ';';
			}
			if ( isset( $settings['input_padding'] ) && self::dim_has_value( $settings['input_padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $settings['input_padding'] ) . ';';
			}
			$css .= '}';
		}

		// Button.
		if ( ! empty( $settings['button_color'] ) || ! empty( $settings['button_text_color'] ) || isset( $settings['button_border_radius'] ) || ! empty( $settings['button_font_size'] ) || isset( $settings['button_padding'] ) ) {
			$css .= 'html body .wpepp-password-form .wpepp-submit input[type="submit"]{';
			if ( ! empty( $settings['button_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $settings['button_color'] ) . ';border-color:' . self::sanitize_color( $settings['button_color'] ) . ';';
			}
			if ( ! empty( $settings['button_text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $settings['button_text_color'] ) . ';';
			}
			if ( isset( $settings['button_border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $settings['button_border_radius'] ) . ';';
			}
			if ( ! empty( $settings['button_font_size'] ) ) {
				$css .= 'font-size:' . absint( $settings['button_font_size'] ) . 'px;';
			}
			if ( isset( $settings['button_padding'] ) && self::dim_has_value( $settings['button_padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $settings['button_padding'] ) . ';';
			}
			$css .= '}';
		}

		if ( ! empty( $settings['label_font_size'] ) || ! empty( $settings['label_color'] ) ) {
			$css .= '.wpepp-password-form label{';
			if ( ! empty( $settings['label_font_size'] ) ) {
				$css .= 'font-size:' . absint( $settings['label_font_size'] ) . 'px;';
			}
			if ( ! empty( $settings['label_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $settings['label_color'] ) . ';';
			}
			$css .= '}';
		}

		if ( ! empty( $settings['custom_css'] ) ) {
			$css .= wp_strip_all_tags( $settings['custom_css'] );
		}

		// Social icons styling.
		if ( ! empty( $settings['icons_color'] ) ) {
			$css .= '.wpepp-social-icons a.wpepp-social-icon{background:' . self::sanitize_color( $settings['icons_color'] ) . ';}';
		}
		if ( ! empty( $settings['icons_size'] ) ) {
			$size    = absint( $settings['icons_size'] );
			$svgSize = round( $size * 0.5 );
			$css .= '.wpepp-social-icons a.wpepp-social-icon{width:' . $size . 'px;height:' . $size . 'px;}';
			$css .= '.wpepp-social-icons svg{width:' . $svgSize . 'px;height:' . $svgSize . 'px;}';
		}
		if ( isset( $settings['icons_gap'] ) && '' !== $settings['icons_gap'] ) {
			$css .= '.wpepp-social-icons{gap:' . absint( $settings['icons_gap'] ) . 'px;}';
		}
		if ( ! empty( $settings['icons_padding'] ) && is_array( $settings['icons_padding'] ) ) {
			$ip = $settings['icons_padding'];
			if ( ! empty( $ip['top'] ) || ! empty( $ip['right'] ) || ! empty( $ip['bottom'] ) || ! empty( $ip['left'] ) ) {
				$css .= '.wpepp-social-icons{padding:' . self::dim_to_css( $ip ) . ';}';
			}
		}

		return $css;
	}
}
