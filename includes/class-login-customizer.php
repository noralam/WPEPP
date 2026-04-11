<?php
/**
 * Login page CSS generator.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Login_Customizer
 */
class WPEPP_Login_Customizer {

	/**
	 * Sanitize a CSS color value (hex, rgb, rgba, hsl, hsla, named colors).
	 *
	 * @param string $color The color value.
	 * @return string Sanitized color or empty string.
	 */
	private static function sanitize_color( $color ) {
		$color = trim( wp_strip_all_tags( $color ) );

		if ( empty( $color ) ) {
			return '';
		}

		// Allow hex colors (#fff, #ffffff, #ffffffff).
		if ( preg_match( '/^#([0-9a-fA-F]{3,8})$/', $color ) ) {
			return $color;
		}

		// Allow rgb/rgba/hsl/hsla functional notation.
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[\d.,\s%\/]+\)$/i', $color ) ) {
			return $color;
		}

		// Allow CSS named colors.
		$named = [
			'transparent', 'currentcolor', 'inherit', 'initial', 'unset',
			'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure', 'beige', 'bisque', 'black',
			'blanchedalmond', 'blue', 'blueviolet', 'brown', 'burlywood', 'cadetblue', 'chartreuse',
			'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan', 'darkblue',
			'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen', 'darkkhaki', 'darkmagenta',
			'darkolivegreen', 'darkorange', 'darkorchid', 'darkred', 'darksalmon', 'darkseagreen',
			'darkslateblue', 'darkslategray', 'darkturquoise', 'darkviolet', 'deeppink', 'deepskyblue',
			'dimgray', 'dodgerblue', 'firebrick', 'floralwhite', 'forestgreen', 'fuchsia', 'gainsboro',
			'ghostwhite', 'gold', 'goldenrod', 'gray', 'green', 'greenyellow', 'honeydew', 'hotpink',
			'indianred', 'indigo', 'ivory', 'khaki', 'lavender', 'lavenderblush', 'lawngreen',
			'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgray',
			'lightgreen', 'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue', 'lightslategray',
			'lightsteelblue', 'lightyellow', 'lime', 'limegreen', 'linen', 'magenta', 'maroon',
			'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple', 'mediumseagreen',
			'mediumslateblue', 'mediumspringgreen', 'mediumturquoise', 'mediumvioletred', 'midnightblue',
			'mintcream', 'mistyrose', 'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive', 'olivedrab',
			'orange', 'orangered', 'orchid', 'palegoldenrod', 'palegreen', 'paleturquoise',
			'palevioletred', 'papayawhip', 'peachpuff', 'peru', 'pink', 'plum', 'powderblue', 'purple',
			'rebeccapurple', 'red', 'rosybrown', 'royalblue', 'saddlebrown', 'salmon', 'sandybrown',
			'seagreen', 'seashell', 'sienna', 'silver', 'skyblue', 'slateblue', 'slategray', 'snow',
			'springgreen', 'steelblue', 'tan', 'teal', 'thistle', 'tomato', 'turquoise', 'violet',
			'wheat', 'white', 'whitesmoke', 'yellow', 'yellowgreen',
		];
		if ( in_array( strtolower( $color ), $named, true ) ) {
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
	 * Generate CSS string from login page settings.
	 *
	 * @param array $settings Login settings array.
	 * @return string
	 */
	public static function generate_css( $settings ) {
		$css = '';

		// Page / Body.
		$page = $settings['page'] ?? [];
		if ( ! empty( $page ) ) {
			$css .= 'body.login {';
			if ( 'color' === ( $page['background_type'] ?? '' ) && ! empty( $page['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $page['background_color'] ) . ';';
			}
			if ( 'gradient' === ( $page['background_type'] ?? '' ) && ! empty( $page['background_gradient'] ) ) {
				$css .= 'background:' . wp_strip_all_tags( $page['background_gradient'] ) . ';';
			}
			if ( 'image' === ( $page['background_type'] ?? '' ) && ! empty( $page['background_image'] ) ) {
				$css .= 'background-image:url(' . esc_url( $page['background_image'] ) . ');';
				$css .= 'background-position:' . esc_attr( $page['background_position'] ?? 'center center' ) . ';';
				$css .= 'background-size:' . esc_attr( $page['background_size'] ?? 'cover' ) . ';';
				$css .= 'background-repeat:no-repeat;';
			}
			if ( 'video' === ( $page['background_type'] ?? '' ) && ! empty( $page['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $page['background_color'] ) . ';';
			}
			$css .= '}';

			if ( ! empty( $page['background_overlay'] ) && 'image' === ( $page['background_type'] ?? '' ) ) {
				$opacity = floatval( $page['background_overlay_opacity'] ?? 0.5 );
				$css .= 'body.login::before{content:"";position:fixed;top:0;left:0;width:100%;height:100%;';
				$css .= 'background-color:' . self::sanitize_color( $page['background_overlay'] ) . ';';
				$css .= 'opacity:' . max( 0, min( 1, $opacity ) ) . ';z-index:0;}';
				$css .= '#login{position:relative;z-index:1;}';
			}
		}

		// Logo.
		$logo = $settings['logo'] ?? [];
		if ( ! empty( $logo ) ) {
			$logo_type = $logo['type'] ?? 'default';
			if ( 'hide' === $logo_type ) {
				$css .= '#login h1{display:none;}';
			} elseif ( 'custom' === $logo_type && ! empty( $logo['image'] ) ) {
				$w = absint( $logo['width'] ?? 84 );
				$h = absint( $logo['height'] ?? 84 );
				$css .= '#login h1 a{';
				$css .= 'background-image:url(' . esc_url( $logo['image'] ) . ');';
				$css .= 'width:' . $w . 'px;height:' . $h . 'px;';
				$css .= 'background-size:contain;background-repeat:no-repeat;background-position:center;}';
			} elseif ( 'text' === $logo_type ) {
				$css .= '#login h1 a{background-image:none;text-indent:0;font-size:0;width:auto;height:auto;}';
				$css .= '#login h1 a::after{content:"' . esc_attr( $logo['text'] ?? '' ) . '";';
				$css .= 'font-size:' . absint( $logo['text_font_size'] ?? 24 ) . 'px;';
				if ( ! empty( $logo['text_color'] ) ) {
					$css .= 'color:' . self::sanitize_color( $logo['text_color'] ) . ';';
				}
				if ( ! empty( $logo['text_font_family'] ) ) {
					$css .= 'font-family:"' . esc_attr( $logo['text_font_family'] ) . '",sans-serif;';
				}
				$css .= '}';
			}
			if ( 'hide' !== ( $logo['type'] ?? 'default' ) && ! empty( $logo['show_background'] ) ) {
				$css .= '#login h1{';
				if ( ! empty( $logo['background_color'] ) ) {
					$css .= 'background-color:' . self::sanitize_color( $logo['background_color'] ) . ';';
				}
				if ( isset( $logo['padding'] ) ) {
					$css .= 'padding:' . self::dim_to_css( $logo['padding'] ) . ';';
				}
				if ( isset( $logo['border_radius'] ) ) {
					$css .= 'border-radius:' . self::dim_to_css( $logo['border_radius'] ) . ';';
				}
				$css .= '}';
			}
		}

		// Form container.
		$form = $settings['form'] ?? [];
		if ( ! empty( $form ) ) {
			$css .= '#loginform,#registerform,#lostpasswordform{';
			if ( ! empty( $form['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $form['background_color'] ) . ';';
			}
			if ( isset( $form['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $form['border_radius'] ) . ';';
			}
			if ( ! empty( $form['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $form['border_color'] ) . ';';
			}
			if ( ! empty( $form['border_width'] ) ) {
				$css .= 'border-width:' . absint( $form['border_width'] ) . 'px;';
				$css .= 'border-style:' . esc_attr( $form['border_style'] ?? 'solid' ) . ';';
				if ( ! empty( $form['border_color'] ) ) {
					$css .= 'border-color:' . self::sanitize_color( $form['border_color'] ) . ';';
				}
			}
			if ( ! empty( $form['width'] ) ) {
				$css .= 'max-width:' . absint( $form['width'] ) . 'px;';
			}
			if ( isset( $form['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $form['padding'] ) . ';';
			}
			$shadow = $form['shadow'] ?? 'none';
			$shadow_map = [
				'small'  => '0 1px 3px rgba(0,0,0,0.12)',
				'medium' => '0 4px 6px rgba(0,0,0,0.1)',
				'large'  => '0 10px 25px rgba(0,0,0,0.15)',
			];
			if ( 'custom' === $shadow && ! empty( $form['shadow_custom'] ) ) {
				$css .= 'box-shadow:' . wp_strip_all_tags( $form['shadow_custom'] ) . ';';
			} elseif ( isset( $shadow_map[ $shadow ] ) ) {
				$css .= 'box-shadow:' . $shadow_map[ $shadow ] . ';';
			} elseif ( 'none' === $shadow ) {
				$css .= 'box-shadow:none;';
			}
			$css .= '}';

			if ( ! empty( $form['width'] ) ) {
				$css .= '#login{width:' . absint( $form['width'] ) . 'px;}';
			}
		}

		// Heading.
		$heading = $settings['heading'] ?? [];
		if ( ! empty( $heading['show'] ) ) {
			$css .= '.wpepp-login-heading{';
			if ( ! empty( $heading['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $heading['color'] ) . ';';
			}
			$css .= 'font-size:' . absint( $heading['font_size'] ?? 22 ) . 'px;';
			$css .= 'font-weight:' . esc_attr( $heading['font_weight'] ?? '600' ) . ';';
			$css .= 'text-align:' . esc_attr( $heading['text_align'] ?? 'center' ) . ';';
			$css .= 'margin-bottom:' . absint( $heading['margin_bottom'] ?? 20 ) . 'px;';
			if ( ! empty( $heading['show_background'] ) && ! empty( $heading['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $heading['background_color'] ) . ';';
			}
			if ( ! empty( $heading['show_background'] ) && isset( $heading['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $heading['padding'] ) . ';';
			}
			if ( ! empty( $heading['show_background'] ) && isset( $heading['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $heading['border_radius'] ) . ';';
			}
			$css .= '}';
		}

		// Labels.
		$labels = $settings['labels'] ?? [];
		if ( ! empty( $labels ) ) {
			$css .= '#loginform label,#registerform label,#lostpasswordform label{';
			if ( ! empty( $labels['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $labels['color'] ) . ';';
			}
			$css .= 'font-size:' . absint( $labels['font_size'] ?? 14 ) . 'px;';
			$css .= '}';
		}

		// Input fields.
		$fields = $settings['fields'] ?? [];
		if ( ! empty( $fields ) ) {
			$css .= '#loginform input[type="text"],#loginform input[type="password"],';
			$css .= '#registerform input[type="text"],#registerform input[type="email"],';
			$css .= '#lostpasswordform input[type="text"]{';
			if ( ! empty( $fields['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $fields['background_color'] ) . ';';
			}
			if ( ! empty( $fields['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $fields['text_color'] ) . ';';
			}
			if ( ! empty( $fields['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $fields['border_color'] ) . ';';
			}
			if ( isset( $fields['border_width'] ) ) {
				$css .= 'border-width:' . absint( $fields['border_width'] ) . 'px;';
			}
			if ( isset( $fields['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $fields['border_radius'] ) . ';';
			}
			if ( isset( $fields['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $fields['padding'] ) . ';';
			}
			if ( isset( $fields['height'] ) ) {
				$css .= 'height:' . absint( $fields['height'] ) . 'px;';
			}
			$css .= 'font-size:' . absint( $fields['font_size'] ?? 14 ) . 'px;';
			$css .= '}';

			if ( ! empty( $fields['focus_border_color'] ) ) {
				$css .= '#loginform input:focus,#registerform input:focus,#lostpasswordform input:focus{';
				$css .= 'border-color:' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= 'box-shadow:0 0 0 1px ' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= '}';
			}
		}

		// Button.
		$button = $settings['button'] ?? [];
		if ( ! empty( $button ) ) {
			$css .= '#wp-submit{';
			if ( ! empty( $button['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $button['background_color'] ) . ';border-color:' . self::sanitize_color( $button['background_color'] ) . ';';
			}
			if ( ! empty( $button['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $button['text_color'] ) . ';';
			}
			$css .= 'font-size:' . absint( $button['font_size'] ?? 14 ) . 'px;';
			$css .= 'font-weight:' . esc_attr( $button['font_weight'] ?? '600' ) . ';';
			if ( isset( $button['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $button['border_radius'] ) . ';';
			}
			if ( isset( $button['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $button['padding'] ) . ';';
			}
			if ( ! empty( $button['width'] ) ) {
				$css .= 'width:' . esc_attr( $button['width'] ) . ';';
			}
			if ( ! empty( $button['height'] ) ) {
				$css .= 'height:' . absint( $button['height'] ) . 'px;';
			}
			$css .= '}';

			if ( ! empty( $button['hover_background'] ) || ! empty( $button['hover_text_color'] ) ) {
				$css .= '#wp-submit:hover,#wp-submit:focus{';
				if ( ! empty( $button['hover_background'] ) ) {
					$css .= 'background-color:' . self::sanitize_color( $button['hover_background'] ) . ';border-color:' . self::sanitize_color( $button['hover_background'] ) . ';';
				}
				if ( ! empty( $button['hover_text_color'] ) ) {
					$css .= 'color:' . self::sanitize_color( $button['hover_text_color'] ) . ';';
				}
				$css .= '}';
			}
		}

		// Links.
		$links = $settings['links'] ?? [];
		if ( ! empty( $links ) ) {
			$css .= '#login #nav a,#login #backtoblog a{';
			if ( ! empty( $links['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $links['color'] ) . ';';
			}
			$css .= 'font-size:' . absint( $links['font_size'] ?? 13 ) . 'px;';
			$css .= '}';
			if ( ! empty( $links['hover_color'] ) ) {
				$css .= '#login #nav a:hover,#login #backtoblog a:hover{';
				$css .= 'color:' . self::sanitize_color( $links['hover_color'] ) . ';';
				$css .= '}';
			}
		}

		// Remember me.
		$rm = $settings['remember_me'] ?? [];
		if ( isset( $rm['show'] ) && false === $rm['show'] ) {
			$css .= '.forgetmenot{display:none;}';
		}

		// Error message styling.
		$error = $settings['error'] ?? [];
		if ( ! empty( $error ) ) {
			$css .= '#login_error{';
			if ( ! empty( $error['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $error['background_color'] ) . ';border-left-color:' . self::sanitize_color( $error['background_color'] ) . ';';
			}
			if ( ! empty( $error['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $error['text_color'] ) . ';';
			}
			if ( isset( $error['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $error['border_radius'] ) . ';';
			}
			$css .= '}';
		}

		// Custom CSS.
		if ( ! empty( $settings['custom_css'] ) ) {
			$css .= wp_strip_all_tags( $settings['custom_css'] );
		}

		return $css;
	}

	/**
	 * Generate CSS for popup login forms using login page settings.
	 *
	 * Maps login customizer settings to popup-specific selectors so the
	 * popup login form inherits the same visual style with glassmorphism.
	 *
	 * @param array $settings Login settings array.
	 * @return string
	 */
	public static function generate_popup_css( $settings ) {
		$css = '';

		// Page background → popup blur layer + overlay.
		$page = $settings['page'] ?? [];
		if ( ! empty( $page ) ) {
			$bg_type = $page['background_type'] ?? '';
			if ( 'color' === $bg_type && ! empty( $page['background_color'] ) ) {
				$css .= '.wpepp-popup-lock-blur{background:' . self::sanitize_color( $page['background_color'] ) . ';opacity:.5;}';
				$css .= '.wpepp-popup-lock-overlay{background:' . self::sanitize_color( $page['background_color'] ) . '80;}'; // 50% alpha via hex.
			} elseif ( 'gradient' === $bg_type && ! empty( $page['background_gradient'] ) ) {
				$css .= '.wpepp-popup-lock-blur{background:' . wp_strip_all_tags( $page['background_gradient'] ) . ';opacity:.5;}';
			} elseif ( 'image' === $bg_type && ! empty( $page['background_image'] ) ) {
				$css .= '.wpepp-popup-lock-blur{';
				$css .= 'background-image:url(' . esc_url( $page['background_image'] ) . ');';
				$css .= 'background-position:' . esc_attr( $page['background_position'] ?? 'center center' ) . ';';
				$css .= 'background-size:' . esc_attr( $page['background_size'] ?? 'cover' ) . ';';
				$css .= 'background-repeat:no-repeat;filter:blur(12px);opacity:.6;}';
				if ( ! empty( $page['background_overlay'] ) ) {
					$opacity = floatval( $page['background_overlay_opacity'] ?? 0.5 );
					$css .= '.wpepp-popup-lock-overlay{background-color:' . self::sanitize_color( $page['background_overlay'] ) . ';';
					$css .= 'opacity:' . max( 0, min( 1, $opacity ) ) . ';}';
				}
			} elseif ( 'video' === $bg_type && ! empty( $page['background_color'] ) ) {
				$css .= '.wpepp-popup-lock-blur{background:' . self::sanitize_color( $page['background_color'] ) . ';opacity:.5;}';
			}
		}

		// Form container → popup modal.
		$form = $settings['form'] ?? [];
		if ( ! empty( $form ) ) {
			$css .= '.wpepp-popup-lock-modal{';
			if ( ! empty( $form['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $form['background_color'] ) . ';';
			}
			if ( isset( $form['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $form['border_radius'] ) . ';';
			}
			if ( ! empty( $form['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $form['border_color'] ) . ';';
			}
			if ( ! empty( $form['border_width'] ) ) {
				$css .= 'border-width:' . absint( $form['border_width'] ) . 'px;';
				$css .= 'border-style:' . esc_attr( $form['border_style'] ?? 'solid' ) . ';';
			}
			if ( ! empty( $form['width'] ) ) {
				$css .= 'max-width:' . absint( $form['width'] ) . 'px;';
			}
			if ( isset( $form['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $form['padding'] ) . ';';
			}
			$shadow     = $form['shadow'] ?? 'none';
			$shadow_map = [
				'small'  => '0 1px 3px rgba(0,0,0,0.12)',
				'medium' => '0 4px 6px rgba(0,0,0,0.1)',
				'large'  => '0 10px 25px rgba(0,0,0,0.15)',
			];
			if ( 'custom' === $shadow && ! empty( $form['shadow_custom'] ) ) {
				$css .= 'box-shadow:' . wp_strip_all_tags( $form['shadow_custom'] ) . ';';
			} elseif ( isset( $shadow_map[ $shadow ] ) ) {
				$css .= 'box-shadow:' . $shadow_map[ $shadow ] . ';';
			}
			$css .= '}';
		}

		// Heading → popup title.
		$heading = $settings['heading'] ?? [];
		if ( ! empty( $heading ) ) {
			$css .= '.wpepp-popup-lock-title{';
			if ( ! empty( $heading['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $heading['color'] ) . ';';
			}
			if ( ! empty( $heading['font_size'] ) ) {
				$css .= 'font-size:' . absint( $heading['font_size'] ) . 'px;';
			}
			$css .= '}';
		}

		// Labels.
		$labels = $settings['labels'] ?? [];
		if ( ! empty( $labels ) ) {
			$css .= '.wpepp-popup-lock-form label{';
			if ( ! empty( $labels['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $labels['color'] ) . ';';
			}
			if ( ! empty( $labels['font_size'] ) ) {
				$css .= 'font-size:' . absint( $labels['font_size'] ) . 'px;';
			}
			$css .= '}';
		}

		// Input fields.
		$fields = $settings['fields'] ?? [];
		if ( ! empty( $fields ) ) {
			$css .= '.wpepp-popup-lock-form input[type="text"],.wpepp-popup-lock-form input[type="password"]{';
			if ( ! empty( $fields['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $fields['background_color'] ) . ';';
			}
			if ( ! empty( $fields['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $fields['text_color'] ) . ';';
			}
			if ( ! empty( $fields['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $fields['border_color'] ) . ';';
			}
			if ( isset( $fields['border_width'] ) ) {
				$css .= 'border-width:' . absint( $fields['border_width'] ) . 'px;';
			}
			if ( isset( $fields['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $fields['border_radius'] ) . ';';
			}
			if ( isset( $fields['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $fields['padding'] ) . ';';
			}
			if ( isset( $fields['height'] ) ) {
				$css .= 'height:' . absint( $fields['height'] ) . 'px;';
			}
			if ( ! empty( $fields['font_size'] ) ) {
				$css .= 'font-size:' . absint( $fields['font_size'] ) . 'px;';
			}
			$css .= '}';

			if ( ! empty( $fields['focus_border_color'] ) ) {
				$css .= '.wpepp-popup-lock-form input:focus{';
				$css .= 'border-color:' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= 'box-shadow:0 0 0 1px ' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= '}';
			}
		}

		// Button.
		$button = $settings['button'] ?? [];
		if ( ! empty( $button ) ) {
			$css .= '.wpepp-popup-lock-form input[type="submit"]{';
			if ( ! empty( $button['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $button['background_color'] ) . ';';
				$css .= 'border-color:' . self::sanitize_color( $button['background_color'] ) . ';';
			}
			if ( ! empty( $button['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $button['text_color'] ) . ';';
			}
			if ( ! empty( $button['font_size'] ) ) {
				$css .= 'font-size:' . absint( $button['font_size'] ) . 'px;';
			}
			if ( isset( $button['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $button['border_radius'] ) . ';';
			}
			if ( isset( $button['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $button['padding'] ) . ';';
			}
			if ( ! empty( $button['width'] ) ) {
				$css .= 'width:' . esc_attr( $button['width'] ) . ';';
			}
			$css .= '}';

			if ( ! empty( $button['hover_background'] ) || ! empty( $button['hover_text_color'] ) ) {
				$css .= '.wpepp-popup-lock-form input[type="submit"]:hover,.wpepp-popup-lock-form input[type="submit"]:focus{';
				if ( ! empty( $button['hover_background'] ) ) {
					$css .= 'background-color:' . self::sanitize_color( $button['hover_background'] ) . ';';
					$css .= 'border-color:' . self::sanitize_color( $button['hover_background'] ) . ';';
				}
				if ( ! empty( $button['hover_text_color'] ) ) {
					$css .= 'color:' . self::sanitize_color( $button['hover_text_color'] ) . ';';
				}
				$css .= '}';
			}
		}

		return $css;
	}

	/**
	 * Generate CSS for content lock inline login form using login page settings.
	 *
	 * Maps login customizer settings to .wpepp-content-locked selectors.
	 *
	 * @param array $settings Login settings array.
	 * @return string
	 */
	public static function generate_content_lock_css( $settings ) {
		$css = '';
		$p   = '.wpepp-content-locked';
		$f   = $p . ' #wpepp-lock-login-form';

		// Form container → locked card.
		$form = $settings['form'] ?? [];
		if ( ! empty( $form ) ) {
			$css .= $p . '{';
			if ( ! empty( $form['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $form['background_color'] ) . ';';
			}
			if ( isset( $form['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $form['border_radius'] ) . ';';
			}
			if ( ! empty( $form['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $form['border_color'] ) . ';';
			}
			if ( ! empty( $form['border_width'] ) ) {
				$css .= 'border-width:' . absint( $form['border_width'] ) . 'px;';
				$css .= 'border-style:' . esc_attr( $form['border_style'] ?? 'solid' ) . ';';
			}
			if ( isset( $form['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $form['padding'] ) . ';';
			}
			$shadow     = $form['shadow'] ?? 'none';
			$shadow_map = [
				'small'  => '0 1px 3px rgba(0,0,0,0.12)',
				'medium' => '0 4px 6px rgba(0,0,0,0.1)',
				'large'  => '0 10px 25px rgba(0,0,0,0.15)',
			];
			if ( 'custom' === $shadow && ! empty( $form['shadow_custom'] ) ) {
				$css .= 'box-shadow:' . wp_strip_all_tags( $form['shadow_custom'] ) . ';';
			} elseif ( isset( $shadow_map[ $shadow ] ) ) {
				$css .= 'box-shadow:' . $shadow_map[ $shadow ] . ';';
			}
			$css .= '}';
		}

		// Heading → lock message text.
		$heading = $settings['heading'] ?? [];
		if ( ! empty( $heading ) ) {
			$css .= $p . ' .wpepp-lock-message{';
			if ( ! empty( $heading['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $heading['color'] ) . ';';
			}
			if ( ! empty( $heading['font_size'] ) ) {
				$css .= 'font-size:' . absint( $heading['font_size'] ) . 'px;';
			}
			$css .= '}';
		}

		// Labels.
		$labels = $settings['labels'] ?? [];
		if ( ! empty( $labels ) ) {
			$css .= $f . ' label{';
			if ( ! empty( $labels['color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $labels['color'] ) . ';';
			}
			if ( ! empty( $labels['font_size'] ) ) {
				$css .= 'font-size:' . absint( $labels['font_size'] ) . 'px;';
			}
			$css .= '}';
		}

		// Input fields.
		$fields = $settings['fields'] ?? [];
		if ( ! empty( $fields ) ) {
			$css .= $f . ' input[type="text"],' . $f . ' input[type="password"]{';
			if ( ! empty( $fields['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $fields['background_color'] ) . ';';
			}
			if ( ! empty( $fields['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $fields['text_color'] ) . ';';
			}
			if ( ! empty( $fields['border_color'] ) ) {
				$css .= 'border-color:' . self::sanitize_color( $fields['border_color'] ) . ';';
			}
			if ( isset( $fields['border_width'] ) ) {
				$css .= 'border-width:' . absint( $fields['border_width'] ) . 'px;';
			}
			if ( isset( $fields['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $fields['border_radius'] ) . ';';
			}
			if ( isset( $fields['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $fields['padding'] ) . ';';
			}
			if ( isset( $fields['height'] ) ) {
				$css .= 'height:' . absint( $fields['height'] ) . 'px;';
			}
			if ( ! empty( $fields['font_size'] ) ) {
				$css .= 'font-size:' . absint( $fields['font_size'] ) . 'px;';
			}
			$css .= '}';

			if ( ! empty( $fields['focus_border_color'] ) ) {
				$css .= $f . ' input[type="text"]:focus,' . $f . ' input[type="password"]:focus{';
				$css .= 'border-color:' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= 'box-shadow:0 0 0 1px ' . self::sanitize_color( $fields['focus_border_color'] ) . ';';
				$css .= '}';
			}
		}

		// Button.
		$button = $settings['button'] ?? [];
		if ( ! empty( $button ) ) {
			$css .= $f . ' input[type="submit"]{';
			if ( ! empty( $button['background_color'] ) ) {
				$css .= 'background-color:' . self::sanitize_color( $button['background_color'] ) . ';';
				$css .= 'border-color:' . self::sanitize_color( $button['background_color'] ) . ';';
			}
			if ( ! empty( $button['text_color'] ) ) {
				$css .= 'color:' . self::sanitize_color( $button['text_color'] ) . ';';
			}
			if ( ! empty( $button['font_size'] ) ) {
				$css .= 'font-size:' . absint( $button['font_size'] ) . 'px;';
			}
			if ( isset( $button['border_radius'] ) ) {
				$css .= 'border-radius:' . self::dim_to_css( $button['border_radius'] ) . ';';
			}
			if ( isset( $button['padding'] ) ) {
				$css .= 'padding:' . self::dim_to_css( $button['padding'] ) . ';';
			}
			if ( ! empty( $button['width'] ) ) {
				$css .= 'width:' . esc_attr( $button['width'] ) . ';';
			}
			$css .= '}';

			if ( ! empty( $button['hover_background'] ) || ! empty( $button['hover_text_color'] ) ) {
				$css .= $f . ' input[type="submit"]:hover,' . $f . ' input[type="submit"]:focus{';
				if ( ! empty( $button['hover_background'] ) ) {
					$css .= 'background-color:' . self::sanitize_color( $button['hover_background'] ) . ';';
					$css .= 'border-color:' . self::sanitize_color( $button['hover_background'] ) . ';';
				}
				if ( ! empty( $button['hover_text_color'] ) ) {
					$css .= 'color:' . self::sanitize_color( $button['hover_text_color'] ) . ';';
				}
				$css .= '}';
			}
		}

		return $css;
	}

	/**
	 * Maybe enqueue a Google Font on the login page.
	 *
	 * @param string $font_family Font family name.
	 */
	public static function maybe_enqueue_google_font( $font_family ) {
		if ( empty( $font_family ) || 'default' === $font_family ) {
			return;
		}

		$font_url = add_query_arg( [
			'family'  => rawurlencode( $font_family . ':wght@400;600;700' ),
			'display' => 'swap',
		], 'https://fonts.googleapis.com/css2' );

		wp_enqueue_style(
			'wpepp-google-font-' . sanitize_title( $font_family ),
			esc_url_raw( $font_url ),
			[],
			null
		);
	}
}
