<?php
/**
 * Frontend rendering — preview, locked messages.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Frontend
 */
class WPEPP_Frontend {

	/**
	 * Render a locked content message.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_locked_message( $post_id ) {
		$message = get_post_meta( $post_id, '_wpepp_content_lock_message', true );
		if ( empty( $message ) ) {
			$message = __( 'This content is for members only. Please log in to view.', 'wp-edit-password-protected' );
		}

		$action = get_post_meta( $post_id, '_wpepp_content_lock_action', true ) ?: 'link';

		$lock_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
			. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
			. '</svg>';

		$html = '<div class="wpepp-content-locked">';
		$html .= '<div class="wpepp-lock-icon">' . $lock_svg . '</div>';
		$html .= '<p class="wpepp-lock-message">' . wp_kses_post( $message ) . '</p>';

		if ( 'link' === $action ) {
			$html .= sprintf(
				'<a href="%s" class="wpepp-lock-login-link">%s</a>',
				esc_url( wp_login_url( get_permalink( $post_id ) ) ),
				esc_html__( 'Log In', 'wp-edit-password-protected' )
			);
		} elseif ( 'form' === $action ) {
			$html .= self::mini_login_form( $post_id );
		} elseif ( 'redirect' === $action ) {
			$redirect_url = get_post_meta( $post_id, '_wpepp_content_lock_redirect', true );
			if ( ! empty( $redirect_url ) ) {
				$html .= sprintf(
					'<a href="%s" class="wpepp-lock-login-link">%s</a>',
					esc_url( $redirect_url ),
					esc_html__( 'Log In', 'wp-edit-password-protected' )
				);
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render popup locked content — blurred original content with login overlay.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Original post content.
	 * @return string
	 */
	public static function get_popup_locked_content( $post_id, $content ) {
		$message = get_post_meta( $post_id, '_wpepp_content_lock_message', true );
		if ( empty( $message ) ) {
			$message = __( 'This content is for members only. Please log in to view.', 'wp-edit-password-protected' );
		}

		$header = get_post_meta( $post_id, '_wpepp_content_lock_header', true );
		if ( empty( $header ) ) {
			$header = __( 'Members Only', 'wp-edit-password-protected' );
		}

		$lock_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
			. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
			. '</svg>';

		$form = self::mini_login_form( $post_id );

		$home_url = esc_url( home_url( '/' ) );

		$html  = '<div class="wpepp-popup-lock-wrapper">';
		// Blurred content behind the overlay.
		$html .= '<div class="wpepp-popup-lock-blur" aria-hidden="true">' . $content . '</div>';
		// Fixed fullscreen overlay with login form.
		$html .= '<div class="wpepp-popup-lock-overlay">';
		$html .= '<div class="wpepp-popup-lock-modal">';
		$html .= '<div class="wpepp-popup-lock-icon">' . $lock_svg . '</div>';
		$html .= '<h3 class="wpepp-popup-lock-title">' . esc_html( $header ) . '</h3>';
		$html .= '<p class="wpepp-popup-lock-message">' . wp_kses_post( $message ) . '</p>';
		$html .= '<div class="wpepp-popup-lock-form">' . $form . '</div>';
		$html .= '<a href="' . $home_url . '" class="wpepp-popup-lock-home">&larr; ' . esc_html__( 'Go to Homepage', 'wp-edit-password-protected' ) . '</a>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a mini login form for content lock.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function mini_login_form( $post_id ) {
		$args = [
			'echo'           => false,
			'redirect'       => get_permalink( $post_id ),
			'form_id'        => 'wpepp-lock-login-form',
			'label_username' => __( 'Username', 'wp-edit-password-protected' ),
			'label_password' => __( 'Password', 'wp-edit-password-protected' ),
			'label_remember' => __( 'Remember Me', 'wp-edit-password-protected' ),
			'label_log_in'   => __( 'Log In', 'wp-edit-password-protected' ),
		];

		return wp_login_form( $args );
	}

	/**
	 * Render a preview page for the admin iframe.
	 *
	 * @param string $type Preview type (login, register, password, lostpassword).
	 */
	public static function render_preview( $type ) {
		$allowed = [ 'login', 'register', 'password', 'lostpassword' ];
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'login';
		}

		// Enqueue styles for preview.
		if ( 'login' === $type || 'register' === $type || 'lostpassword' === $type ) {
			wp_enqueue_style( 'login' );
			wp_enqueue_style( 'buttons' );
			wp_enqueue_style( 'dashicons' );
		}
		if ( 'password' === $type ) {
			wp_enqueue_style(
				'wpepp-frontend-password-form',
				WPEPP_URL . '/assets/css/frontend-password-form.css',
				[],
				defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0'
			);
		}
		wp_register_style( 'wpepp-preview-base', false, [], defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0' );
		wp_enqueue_style( 'wpepp-preview-base' );
		wp_add_inline_style( 'wpepp-preview-base', 'body{margin:0;padding:20px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}' );

		// Minimal HTML shell for preview iframe.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html__( 'Preview', 'wp-edit-password-protected' ); ?></title>
			<?php wp_print_styles(); ?>
			<style id="wpepp-preview-css"></style>
		</head>
		<body class="login wpepp-preview-body wpepp-preview-<?php echo esc_attr( $type ); ?>">
			<?php
			switch ( $type ) {
				case 'login':
					self::render_login_preview();
					break;
				case 'register':
					self::render_register_preview();
					break;
				case 'lostpassword':
					self::render_lostpassword_preview();
					break;
				case 'password':
					self::render_password_preview();
					break;
			}
			?>
			<script>
			window.addEventListener( 'message', function( event ) {
				if ( event.data && event.data.type === 'wpepp_preview_css' ) {
					document.getElementById( 'wpepp-preview-css' ).textContent = event.data.css;
				}
				if ( event.data && event.data.type === 'wpepp_preview_settings' ) {
					var s = event.data.settings;
					if ( ! s ) return;
					var form = document.getElementById( 'wpepp-pw-preview' );
					if ( ! form ) return;

					/* Style class — instant switch, no iframe reload */
					var allowed = [ 'one', 'two', 'three', 'four' ];
					var style = allowed.indexOf( s.active_style ) !== -1 ? s.active_style : 'one';
					form.className = 'wpepp-password-form wpepp-style-' + style;

					/* Top text */
					var topText = document.getElementById( 'wpepp-top-text' );
					if ( topText ) {
						topText.style.display = s.show_top_text === 'on' ? '' : 'none';
						topText.style.textAlign = s.top_text_align || 'center';
					}
					var topH = document.getElementById( 'wpepp-top-header' );
					if ( topH ) topH.textContent = s.top_header || '';
					var topC = document.getElementById( 'wpepp-top-content' );
					if ( topC ) topC.innerHTML = s.top_content || '';

					/* Bottom text */
					var botText = document.getElementById( 'wpepp-bottom-text' );
					if ( botText ) {
						botText.style.display = s.show_bottom_text === 'on' ? '' : 'none';
						botText.style.textAlign = s.bottom_text_align || 'left';
					}
					var botH = document.getElementById( 'wpepp-bottom-header' );
					if ( botH ) botH.textContent = s.bottom_header || '';
					var botC = document.getElementById( 'wpepp-bottom-content' );
					if ( botC ) botC.innerHTML = s.bottom_content || '';

					/* Form label & button */
					var lbl = document.getElementById( 'wpepp-form-label' );
					if ( lbl ) lbl.textContent = s.form_label || 'Password';
					var btn = document.getElementById( 'wpepp-form-submit' );
					if ( btn ) btn.value = s.form_btn_text || 'Submit';

					/* Error text */
					var errTop = document.getElementById( 'wpepp-error-top' );
					var errBot = document.getElementById( 'wpepp-error-bottom' );
					var errTxt = s.form_errortext || '';
					if ( errTop ) {
						errTop.textContent = errTxt;
						errTop.style.display = errTxt && s.error_text_position !== 'bottom' ? '' : 'none';
					}
					if ( errBot ) {
						errBot.textContent = errTxt;
						errBot.style.display = errTxt && s.error_text_position === 'bottom' ? '' : 'none';
					}

					/* Social icons */
					var showSocial = s.show_social === 'on';
					var pos = s.icons_vposition || 'top';
					var align = s.icons_alignment || 'center';
					var shape = s.icons_style || 'square';

					var sTop = document.getElementById( 'wpepp-social-top' );
					var sMid = document.getElementById( 'wpepp-social-middle' );
					var sBot = document.getElementById( 'wpepp-social-bottom' );

					if ( sTop ) {
						sTop.style.display = ( showSocial && pos === 'top' ) ? '' : 'none';
						sTop.className = 'wpepp-social-icons wpepp-social-' + align + ' wpepp-icon-shape-' + shape;
					}
					if ( sMid ) {
						sMid.style.display = ( showSocial && pos === 'middle' ) ? '' : 'none';
						sMid.className = 'wpepp-social-icons wpepp-social-' + align + ' wpepp-social-middle wpepp-icon-shape-' + shape;
					}
					if ( sBot ) {
						sBot.style.display = ( showSocial && pos === 'bottom' ) ? '' : 'none';
						sBot.className = 'wpepp-social-icons wpepp-social-' + align + ' wpepp-icon-shape-' + shape;
					}

					/* Show/hide individual social icons based on URL */
					var iconMap = { facebook: 'link_facebook', twitter: 'link_twitter', youtube: 'link_youtube', instagram: 'link_instagram', linkedin: 'link_linkedin', pinterest: 'link_pinterest', tumblr: 'link_tumblr', custom: 'link_custom' };
					[ sTop, sMid, sBot ].forEach( function( container ) {
						if ( ! container ) return;
						for ( var name in iconMap ) {
							var icon = container.querySelector( '.wpepp-social-' + name );
							if ( icon ) {
								icon.style.display = s[ iconMap[ name ] ] ? '' : 'none';
							}
						}
					});
				}
				if ( event.data && event.data.type === 'wpepp_preview_video' ) {
					var existing = document.getElementById( 'wpepp-video-bg' );
					if ( existing ) { existing.remove(); }
					var video = event.data.video;
					if ( ! video || ! video.embedUrl ) { return; }
					var wrapper = document.createElement( 'div' );
					wrapper.id = 'wpepp-video-bg';
					wrapper.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;overflow:hidden;';
					if ( video.type === 'mp4' ) {
						var vid = document.createElement( 'video' );
						vid.src = video.embedUrl;
						vid.autoplay = true;
						vid.muted = true;
						vid.loop = true;
						vid.playsInline = true;
						vid.style.cssText = 'width:100%;height:100%;object-fit:cover;';
						wrapper.appendChild( vid );
					} else {
						var iframe = document.createElement( 'iframe' );
						iframe.src = video.embedUrl;
						iframe.frameBorder = '0';
						iframe.allow = 'autoplay; fullscreen';
						iframe.style.cssText = 'position:absolute;top:50%;left:50%;width:100vw;height:56.25vw;min-height:100vh;min-width:177.78vh;transform:translate(-50%,-50%);border:0;pointer-events:none;';
						wrapper.appendChild( iframe );
					}
					document.body.insertBefore( wrapper, document.body.firstChild );
					/* Push login above the video */
					var login = document.getElementById( 'login' );
					if ( login ) { login.style.position = 'relative'; login.style.zIndex = '1'; }
				}
			} );
			</script>
		</body>
		</html>
		<?php
	}

	/**
	 * Render login form preview HTML.
	 */
	private static function render_login_preview() {
		?>
		<div id="login">
			<h1><a href="#"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a></h1>
			<div class="wpepp-login-heading"></div>
			<form name="loginform" id="loginform" method="post">
				<p>
					<label for="user_login"><?php echo esc_html__( 'Username or Email Address', 'wp-edit-password-protected' ); ?></label>
					<input type="text" name="log" id="user_login" class="input" size="20" autocapitalize="off" autocomplete="username">
				</p>
				<p>
					<label for="user_pass"><?php echo esc_html__( 'Password', 'wp-edit-password-protected' ); ?></label>
					<input type="password" name="pwd" id="user_pass" class="input" size="20" autocomplete="current-password">
				</p>
				<p class="forgetmenot">
					<label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever"> <?php echo esc_html__( 'Remember Me', 'wp-edit-password-protected' ); ?></label>
				</p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Log In', 'wp-edit-password-protected' ); ?>">
				</p>
			</form>
			<p id="nav">
				<a href="#"><?php echo esc_html__( 'Lost your password?', 'wp-edit-password-protected' ); ?></a>
			</p>
			<p id="backtoblog">
				<a href="#">&larr; <?php echo esc_html__( 'Go to site', 'wp-edit-password-protected' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render register form preview HTML.
	 */
	private static function render_register_preview() {
		?>
		<div id="login">
			<h1><a href="#"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a></h1>
			<div class="wpepp-login-heading"></div>
			<form name="registerform" id="registerform" method="post">
				<p>
					<label for="user_login"><?php echo esc_html__( 'Username', 'wp-edit-password-protected' ); ?></label>
					<input type="text" name="user_login" id="user_login" class="input" size="20" autocapitalize="off" autocomplete="username">
				</p>
				<p>
					<label for="user_email"><?php echo esc_html__( 'Email', 'wp-edit-password-protected' ); ?></label>
					<input type="email" name="user_email" id="user_email" class="input" size="25" autocomplete="email">
				</p>
				<p id="reg_passmail"><?php echo esc_html__( 'Registration confirmation will be emailed to you.', 'wp-edit-password-protected' ); ?></p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Register', 'wp-edit-password-protected' ); ?>">
				</p>
			</form>
			<p id="nav">
				<a href="#"><?php echo esc_html__( 'Log in', 'wp-edit-password-protected' ); ?></a>
			</p>
			<p id="backtoblog">
				<a href="#">&larr; <?php echo esc_html__( 'Go to site', 'wp-edit-password-protected' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render lost password form preview HTML.
	 */
	private static function render_lostpassword_preview() {
		?>
		<div id="login">
			<h1><a href="#"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a></h1>
			<div class="wpepp-login-heading"></div>
			<form name="lostpasswordform" id="lostpasswordform" method="post">
				<p><?php echo esc_html__( 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.', 'wp-edit-password-protected' ); ?></p>
				<p>
					<label for="user_login"><?php echo esc_html__( 'Username or Email Address', 'wp-edit-password-protected' ); ?></label>
					<input type="text" name="user_login" id="user_login" class="input" size="20" autocapitalize="off" autocomplete="username">
				</p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Get New Password', 'wp-edit-password-protected' ); ?>">
				</p>
			</form>
			<p id="nav">
				<a href="#"><?php echo esc_html__( 'Log in', 'wp-edit-password-protected' ); ?></a>
			</p>
			<p id="backtoblog">
				<a href="#">&larr; <?php echo esc_html__( 'Go to site', 'wp-edit-password-protected' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render password form preview HTML.
	 */
	private static function render_password_preview() {
		// Render a full skeleton — every element always in the DOM.
		// The React editor sends live settings via postMessage to update text/classes.
		$raw      = get_option( 'wpepp_password_settings', '{}' );
		$settings = json_decode( $raw, true );

		$style       = sanitize_text_field( $settings['active_style'] ?? 'one' );
		$form_label  = esc_html( $settings['form_label'] ?? __( 'Password', 'wp-edit-password-protected' ) );
		$btn_text    = esc_html( $settings['form_btn_text'] ?? __( 'Submit', 'wp-edit-password-protected' ) );
		$top_header  = esc_html( $settings['top_header'] ?? '' );
		$top_content = wp_kses_post( $settings['top_content'] ?? '' );
		$bot_header  = esc_html( $settings['bottom_header'] ?? '' );
		$bot_content = wp_kses_post( $settings['bottom_content'] ?? '' );
		$error_text  = esc_html( $settings['form_errortext'] ?? '' );
		?>
		<div class="wpepp-password-form wpepp-style-<?php echo esc_attr( $style ); ?>" id="wpepp-pw-preview">
			<!-- Error top -->
			<div class="wpepp-error-message" id="wpepp-error-top" style="display:none;"><?php echo $error_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>

			<!-- Social top -->
			<div class="wpepp-social-icons wpepp-social-center wpepp-icon-shape-square" id="wpepp-social-top" style="display:none;">
				<?php self::render_preview_social_icons(); ?>
			</div>

			<!-- Top text -->
			<div class="wpepp-password-top-text" id="wpepp-top-text" style="display:none;text-align:center;">
				<h3 id="wpepp-top-header"><?php echo $top_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
				<div class="wpepp-top-content" id="wpepp-top-content"><?php echo $top_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
			</div>

			<!-- Social middle -->
			<div class="wpepp-social-icons wpepp-social-center wpepp-social-middle wpepp-icon-shape-square" id="wpepp-social-middle" style="display:none;">
				<?php self::render_preview_social_icons(); ?>
			</div>

			<!-- Form -->
			<form class="wpepp-password-form-inner" method="post" onsubmit="return false;">
				<p>
					<label for="pwbox-preview" id="wpepp-form-label"><?php echo $form_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></label>
					<input name="post_password" id="pwbox-preview" type="password" size="20">
				</p>
				<p class="wpepp-submit">
					<input type="submit" name="Submit" id="wpepp-form-submit" value="<?php echo esc_attr( $btn_text ); ?>">
				</p>
			</form>

			<!-- Error bottom -->
			<div class="wpepp-error-message wpepp-error-bottom" id="wpepp-error-bottom" style="display:none;"><?php echo $error_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>

			<!-- Bottom text -->
			<div class="wpepp-password-bottom-text" id="wpepp-bottom-text" style="display:none;">
				<h3 id="wpepp-bottom-header"><?php echo $bot_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
				<div class="wpepp-bottom-content" id="wpepp-bottom-content"><?php echo $bot_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
			</div>

			<!-- Social bottom -->
			<div class="wpepp-social-icons wpepp-social-center wpepp-icon-shape-square" id="wpepp-social-bottom" style="display:none;">
				<?php self::render_preview_social_icons(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sample social icons for the preview (non-functional links).
	 */
	private static function render_preview_social_icons() {
		$icons = [
			'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
			'twitter'   => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
			'youtube'   => '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>',
			'instagram' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>',
			'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
			'pinterest' => '<path d="M12 2C6.48 2 2 6.48 2 12c0 4.25 2.67 7.9 6.44 9.34-.09-.78-.17-1.99.04-2.85.19-.78 1.22-5.16 1.22-5.16s-.31-.62-.31-1.54c0-1.45.84-2.53 1.89-2.53.89 0 1.32.67 1.32 1.47 0 .9-.57 2.24-.87 3.48-.25 1.05.52 1.9 1.55 1.9 1.86 0 3.29-1.96 3.29-4.79 0-2.51-1.8-4.26-4.38-4.26-2.98 0-4.74 2.24-4.74 4.55 0 .9.35 1.87.78 2.39.09.1.1.19.07.3-.08.33-.26 1.04-.29 1.18-.05.19-.15.23-.35.14-1.31-.61-2.13-2.52-2.13-4.06 0-3.31 2.41-6.35 6.94-6.35 3.65 0 6.48 2.6 6.48 6.07 0 3.62-2.28 6.54-5.46 6.54-1.07 0-2.07-.55-2.41-1.21l-.66 2.5c-.24.91-.88 2.05-1.31 2.75A10 10 0 0 0 22 12c0-5.52-4.48-10-10-10z"/>',
			'tumblr'    => '<path d="M14.5 2H9.5v5H7v3h2.5v5.5a5 5 0 0 0 5 5h2.5v-3h-2.5a2 2 0 0 1-2-2V10h4V7h-4V2z"/>',
			'custom'    => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" fill="none" stroke="currentColor" stroke-width="2"/>',
		];
		foreach ( $icons as $name => $path ) {
			printf(
				'<a href="#" class="wpepp-social-icon wpepp-social-%s" aria-label="%s" onclick="return false;">'
					. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">%s</svg>'
				. '</a>',
				esc_attr( $name ),
				esc_attr( ucfirst( $name ) ),
				$path // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG path data, no user input.
			);
		}
	}
}
