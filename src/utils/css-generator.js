/**
 * CSS generators for live preview — produces CSS from JS settings objects.
 * These mirror the PHP generators in class-login-customizer.php and class-password-customizer.php.
 */

/**
 * Convert a dimension value (object or number) to CSS shorthand.
 */
function dimToCss( val, unit = 'px' ) {
	if ( typeof val === 'object' && val !== null ) {
		return `${ val.top || 0 }${ unit } ${ val.right || 0 }${ unit } ${ val.bottom || 0 }${ unit } ${ val.left || 0 }${ unit }`;
	}
	return typeof val === 'number' ? `${ val }${ unit }` : '0' + unit;
}

/**
 * Generate login page CSS from settings.
 *
 * @param {Object} s Login settings.
 * @return {string} CSS string.
 */
export function generateLoginCss( s ) {
	let css = '';

	// Page background.
	const page = s.page || {};
	const bgType = page.background_type || 'color';
	css += 'body.login{';
	if ( bgType === 'color' && page.background_color ) {
		css += `background-color:${ page.background_color };`;
	} else if ( bgType === 'gradient' && page.background_gradient ) {
		css += `background:${ page.background_gradient };`;
	} else if ( bgType === 'image' && page.background_image ) {
		css += `background-image:url(${ page.background_image });`;
		css += `background-position:${ page.background_position || 'center center' };`;
		css += `background-size:${ page.background_size || 'cover' };`;
		css += 'background-repeat:no-repeat;';
	} else if ( bgType === 'video' && page.background_color ) {
		css += `background-color:${ page.background_color };`;
	}
	css += '}';

	// Logo.
	const logo = s.logo || {};
	const logoType = logo.type || 'default';
	if ( logoType === 'hide' ) {
		css += '#login h1{display:none;}';
	} else if ( logoType === 'custom' && logo.image ) {
		const w = logo.width || 84;
		const h = logo.height || 84;
		css += `#login h1 a{background-image:url(${ logo.image });width:${ w }px;height:${ h }px;`;
		css += 'background-size:contain;background-repeat:no-repeat;background-position:center;}';
	} else if ( logoType === 'text' ) {
		css += '#login h1 a{background-image:none;text-indent:0;font-size:0;width:auto;height:auto;}';
		css += `#login h1 a::after{content:"${ ( logo.text || '' ).replace( /"/g, '\\"' ) }";`;
		css += `font-size:${ logo.text_font_size || 24 }px;`;
		if ( logo.text_color ) {
			css += `color:${ logo.text_color };`;
		}
		css += '}';
	}
	if ( logoType !== 'hide' && logo.show_background ) {
		css += '#login h1{';
		if ( logo.background_color ) {
			css += `background-color:${ logo.background_color };`;
		}
		if ( logo.padding !== undefined ) {
			css += `padding:${ dimToCss( logo.padding ) };`;
		}
		if ( logo.border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( logo.border_radius ) };`;
		}
		css += '}';
	}

	// Form container.
	const form = s.form || {};
	css += '#loginform,#registerform,#lostpasswordform{';
	if ( form.background_color ) {
		css += `background-color:${ form.background_color };`;
	}
	if ( form.border_radius !== undefined ) {
		css += `border-radius:${ dimToCss( form.border_radius ) };`;
	}
	if ( form.width ) {
		css += `max-width:${ form.width }px;`;
	}
	if ( form.padding !== undefined ) {
		css += `padding:${ dimToCss( form.padding ) };`;
	}
	if ( form.border_color ) {
		css += `border-color:${ form.border_color };`;
	}
	css += '}';
	if ( form.width ) {
		css += `#login{width:${ form.width }px;}`;
	}

	// Heading.
	const heading = s.heading || {};
	if ( heading.show === false ) {
		css += '.wpepp-login-heading{display:none;}';
	} else {
		css += '.wpepp-login-heading{';
		if ( heading.color ) {
			css += `color:${ heading.color };`;
		}
		if ( heading.font_size ) {
			css += `font-size:${ heading.font_size }px;`;
		}
		if ( heading.show_background && heading.background_color ) {
			css += `background-color:${ heading.background_color };`;
		}
		if ( heading.show_background && heading.padding !== undefined ) {
			css += `padding:${ dimToCss( heading.padding ) };`;
		}
		if ( heading.show_background && heading.border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( heading.border_radius ) };`;
		}
		css += 'text-align:center;}';
		if ( heading.text ) {
			css += `.wpepp-login-heading::before{content:"${ heading.text.replace( /\\/g, '\\\\' ).replace( /"/g, '\\"' ) }";display:block;margin-bottom:10px;font-weight:700;}`;
		}
	}

	// Labels.
	const labels = s.labels || {};
	if ( labels.color || labels.font_size ) {
		css += '.login form label{';
		if ( labels.color ) {
			css += `color:${ labels.color };`;
		}
		if ( labels.font_size ) {
			css += `font-size:${ labels.font_size }px;`;
		}
		css += '}';
	}

	// Fields.
	const fields = s.fields || {};
	css += '.login form .input,#loginform input[type="text"],#loginform input[type="password"],#registerform input[type="text"],#registerform input[type="email"],#lostpasswordform input[type="text"]{';
	if ( fields.background_color ) {
		css += `background-color:${ fields.background_color };`;
	}
	if ( fields.text_color ) {
		css += `color:${ fields.text_color };`;
	}
	if ( fields.border_color ) {
		css += `border-color:${ fields.border_color };`;
	}
	if ( fields.border_radius !== undefined ) {
		css += `border-radius:${ dimToCss( fields.border_radius ) };`;
	}
	if ( fields.padding !== undefined ) {
		css += `padding:${ dimToCss( fields.padding ) };`;
	}
	css += '}';

	// Button.
	const button = s.button || {};
	css += '#wp-submit,.login .button-primary{';
	if ( button.background_color ) {
		css += `background-color:${ button.background_color };border-color:${ button.background_color };`;
	}
	if ( button.text_color ) {
		css += `color:${ button.text_color };`;
	}
	if ( button.border_radius !== undefined ) {
		css += `border-radius:${ dimToCss( button.border_radius ) };`;
	}
	if ( button.font_size ) {
		css += `font-size:${ button.font_size }px;`;
	}
	if ( button.padding !== undefined ) {
		css += `padding:${ dimToCss( button.padding ) };`;
	}
	css += '}';

	// Links.
	const links = s.links || {};
	if ( links.color ) {
		css += `.login #nav a,.login #backtoblog a{color:${ links.color };}`;
	}
	if ( links.show_lost_password === false ) {
		css += '.login #nav{display:none;}';
	}
	if ( links.show_back_to_site === false ) {
		css += '.login #backtoblog{display:none;}';
	}

	// Custom CSS.
	if ( s.custom_css ) {
		css += s.custom_css;
	}

	return css;
}

/**
 * Generate password form CSS from settings.
 *
 * @param {Object} s Password settings.
 * @return {string} CSS string.
 */
export function generatePasswordCss( s ) {
	let css = '';

	// Page background.
	const bgType = s.page_background_type || 'color';
	if ( bgType === 'color' && s.page_background_color ) {
		css += `body{background-color:${ s.page_background_color };}`;
	} else if ( bgType === 'image' && s.page_background_image ) {
		css += `body{background-image:url(${ s.page_background_image });background-position:${ s.page_background_position || 'center center' };background-size:${ s.page_background_size || 'cover' };background-repeat:no-repeat;background-attachment:fixed;}`;
	} else if ( bgType === 'gradient' && s.page_background_gradient ) {
		css += `body{background:${ s.page_background_gradient };}`;
	} else if ( bgType === 'video' && s.page_background_color ) {
		css += `body{background-color:${ s.page_background_color };}`;
	}

	// Form outer wrapper.
	if ( s.form_outer_background || s.form_outer_border_radius !== undefined || s.form_outer_padding !== undefined ) {
		css += '.wpepp-password-form{';
		if ( s.form_outer_background ) {
			css += `background-color:${ s.form_outer_background };`;
		}
		if ( s.form_outer_border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( s.form_outer_border_radius ) };`;
		}
		if ( s.form_outer_padding !== undefined ) {
			css += `padding:${ dimToCss( s.form_outer_padding ) };`;
		}
		css += '}';
	}

	// Form container.
	if ( s.form_background || s.form_border_radius !== undefined || s.form_padding !== undefined || s.form_text_color ) {
		css += '.wpepp-password-form form.wpepp-password-form-inner{';
		if ( s.form_background ) {
			css += `background-color:${ s.form_background };`;
		}
		if ( s.form_border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( s.form_border_radius ) };`;
		}
		if ( s.form_padding !== undefined ) {
			css += `padding:${ dimToCss( s.form_padding ) };`;
		}
		if ( s.form_text_color ) {
			css += `color:${ s.form_text_color };`;
		}
		css += '}';
	}

	// Form shadow.
	const shadowMap = { small: '0 1px 3px rgba(0,0,0,0.12)', medium: '0 4px 6px rgba(0,0,0,0.1)', large: '0 10px 25px rgba(0,0,0,0.15)' };
	if ( s.form_shadow && shadowMap[ s.form_shadow ] ) {
		css += `.wpepp-password-form{box-shadow:${ shadowMap[ s.form_shadow ] };}`;
	}

	// Heading.
	if ( s.heading_color || s.heading_font_size ) {
		css += '.wpepp-password-form h3,.wpepp-password-top-text h3{';
		if ( s.heading_color ) {
			css += `color:${ s.heading_color };`;
		}
		if ( s.heading_font_size ) {
			css += `font-size:${ s.heading_font_size }px;`;
		}
		css += '}';
	}

	// Heading background overlay.
	if ( s.heading_show_background ) {
		css += '.wpepp-password-form h3,.wpepp-password-top-text h3{';
		if ( s.heading_background_color ) {
			css += `background-color:${ s.heading_background_color };`;
		}
		if ( s.heading_padding !== undefined ) {
			css += `padding:${ dimToCss( s.heading_padding ) };`;
		}
		if ( s.heading_border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( s.heading_border_radius ) };`;
		}
		css += 'display:inline-block;}';
	}

	// Input fields.
	if ( s.input_background || s.input_text_color || s.input_border_color || s.input_border_radius !== undefined || s.input_padding !== undefined ) {
		css += '.wpepp-password-form input[type="password"]{';
		if ( s.input_background ) {
			css += `background-color:${ s.input_background };`;
		}
		if ( s.input_text_color ) {
			css += `color:${ s.input_text_color };`;
		}
		if ( s.input_border_color ) {
			css += `border-color:${ s.input_border_color };`;
		}
		if ( s.input_border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( s.input_border_radius ) };`;
		}
		if ( s.input_padding !== undefined ) {
			css += `padding:${ dimToCss( s.input_padding ) };`;
		}
		css += '}';
	}

	// Button.
	if ( s.button_color || s.button_text_color || s.button_border_radius !== undefined || s.button_font_size || s.button_padding !== undefined ) {
		css += '.wpepp-submit input[type="submit"]{';
		if ( s.button_color ) {
			css += `background-color:${ s.button_color };border-color:${ s.button_color };`;
		}
		if ( s.button_text_color ) {
			css += `color:${ s.button_text_color };`;
		}
		if ( s.button_border_radius !== undefined ) {
			css += `border-radius:${ dimToCss( s.button_border_radius ) };`;
		}
		if ( s.button_font_size ) {
			css += `font-size:${ s.button_font_size }px;`;
		}
		if ( s.button_padding !== undefined ) {
			css += `padding:${ dimToCss( s.button_padding ) };`;
		}
		css += '}';
	}

	// Top text alignment.
	if ( s.top_text_align ) {
		css += `.wpepp-password-top-text{text-align:${ s.top_text_align };}`;
	}

	// Bottom text alignment.
	if ( s.bottom_text_align ) {
		css += `.wpepp-password-bottom-text,.wpepp-password-bottom-text p{text-align:${ s.bottom_text_align };}`;
	}

	// Label font size and color.
	if ( s.label_font_size || s.label_color ) {
		css += '.wpepp-password-form label{';
		if ( s.label_font_size ) {
			css += `font-size:${ s.label_font_size }px;`;
		}
		if ( s.label_color ) {
			css += `color:${ s.label_color };`;
		}
		css += '}';
	}

	// Social icons styling.
	if ( s.icons_color ) {
		css += `.wpepp-social-icons a.wpepp-social-icon{background:${ s.icons_color };}`;
	}
	if ( s.icons_size ) {
		css += `.wpepp-social-icons a.wpepp-social-icon{width:${ s.icons_size }px;height:${ s.icons_size }px;}`;
		const svgSize = Math.round( s.icons_size * 0.5 );
		css += `.wpepp-social-icons svg{width:${ svgSize }px;height:${ svgSize }px;}`;
	}
	if ( s.icons_gap !== undefined ) {
		css += `.wpepp-social-icons{gap:${ s.icons_gap }px;}`;
	}
	if ( s.icons_padding !== undefined ) {
		const ip = s.icons_padding;
		if ( ip && ( ip.top || ip.right || ip.bottom || ip.left ) ) {
			css += `.wpepp-social-icons{padding:${ dimToCss( ip ) };}`;
		}
	}

	// Custom CSS.
	if ( s.custom_css ) {
		css += s.custom_css;
	}

	return css;
}
