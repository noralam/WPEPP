/**
 * Default settings values — used as fallbacks when merging with saved settings.
 */

/**
 * Deep merge defaults with saved settings.
 * Saved values override defaults; nested objects are merged recursively.
 *
 * @param {Object} defaults Default values.
 * @param {Object} saved    Saved settings (may be sparse).
 * @return {Object} Merged settings.
 */
export function mergeDefaults( defaults, saved ) {
	if ( ! saved || typeof saved !== 'object' ) {
		return { ...defaults };
	}
	const result = { ...defaults };
	for ( const key of Object.keys( saved ) ) {
		if (
			saved[ key ] !== null &&
			typeof saved[ key ] === 'object' &&
			! Array.isArray( saved[ key ] ) &&
			typeof defaults[ key ] === 'object' &&
			defaults[ key ] !== null &&
			! Array.isArray( defaults[ key ] )
		) {
			result[ key ] = { ...defaults[ key ], ...saved[ key ] };
		} else {
			result[ key ] = saved[ key ];
		}
	}
	return result;
}

export const LOGIN_DEFAULTS = {
	page: {
		background_type: 'color',
		background_color: '#f0f0f1',
		background_gradient: '',
		background_image: '',
		background_position: 'center center',
		background_size: 'cover',
		background_video: '',
	},
	logo: {
		type: 'default',
		url: '',
		image: '',
		width: 84,
		height: 84,
		text: '',
		text_font_size: 24,
		text_color: '#333333',
		show_background: false,
		background_color: 'rgba(0,0,0,0.45)',
		padding: { top: 12, right: 16, bottom: 12, left: 16 },
		border_radius: { top: 8, right: 8, bottom: 8, left: 8 },
	},
	form: {
		background_color: '#ffffff',
		border_radius: { top: 8, right: 8, bottom: 8, left: 8 },
		width: 320,
		padding: { top: 24, right: 24, bottom: 24, left: 24 },
		border_color: '#c3c4c7',
	},
	heading: {
		show: false,
		text: '',
		color: '#333333',
		font_size: 20,
		show_background: false,
		background_color: 'rgba(0,0,0,0.45)',
		padding: { top: 8, right: 16, bottom: 8, left: 16 },
		border_radius: { top: 6, right: 6, bottom: 6, left: 6 },
	},
	labels: {
		color: '#1e1e1e',
		font_size: 14,
	},
	fields: {
		background_color: '#ffffff',
		text_color: '#1e1e1e',
		border_color: '#8c8f94',
		border_radius: { top: 4, right: 4, bottom: 4, left: 4 },
		padding: { top: 6, right: 6, bottom: 6, left: 6 },
	},
	button: {
		background_color: '#2271b1',
		text_color: '#ffffff',
		border_radius: { top: 4, right: 4, bottom: 4, left: 4 },
		font_size: 14,
		padding: { top: 8, right: 16, bottom: 8, left: 16 },
	},
	links: {
		color: '#50575e',
		show_lost_password: true,
		show_back_to_site: true,
	},
	custom_css: '',
};

export const PASSWORD_DEFAULTS = {
	active_style: 'one',
	page_background_type: 'color',
	page_background_color: '#f0f0f1',
	page_background_image: '',
	page_background_position: 'center center',
	page_background_size: 'cover',
	page_background_gradient: '',
	page_background_video: '',
	show_top_text: 'on',
	top_header: 'This content is password protected for members only',
	top_content: 'For more public resources check out our followed link.',
	top_text_align: 'center',
	form_label: 'Password',
	form_btn_text: 'Submit',
	form_errortext: 'The password you have entered is invalid',
	error_text_position: 'top',
	form_outer_background: '',
	form_outer_border_radius: { top: 8, right: 8, bottom: 8, left: 8 },
	form_outer_padding: { top: 24, right: 24, bottom: 24, left: 24 },
	form_background: '#ffffff',
	form_border_radius: { top: 8, right: 8, bottom: 8, left: 8 },
	form_padding: { top: 24, right: 24, bottom: 24, left: 24 },
	form_text_color: '#1e1e1e',
	input_background: '#ffffff',
	input_text_color: '#1e1e1e',
	input_border_color: '#8c8f94',
	input_border_radius: { top: 4, right: 4, bottom: 4, left: 4 },
	input_padding: { top: 8, right: 12, bottom: 8, left: 12 },
	button_color: '#42276A',
	button_text_color: '#ffffff',
	button_border_radius: { top: 4, right: 4, bottom: 4, left: 4 },
	button_font_size: 14,
	button_padding: { top: 8, right: 20, bottom: 8, left: 20 },
	heading_color: '#1e1e1e',
	heading_font_size: 20,
	heading_show_background: false,
	heading_background_color: 'rgba(0,0,0,0.45)',
	heading_padding: { top: 8, right: 16, bottom: 8, left: 16 },
	heading_border_radius: { top: 6, right: 6, bottom: 6, left: 6 },
	label_font_size: 14,
	label_color: '#1e1e1e',
	show_social: 'on',
	icons_vposition: 'top',
	icons_alignment: 'center',
	icons_style: 'square',
	icons_color: '',
	icons_size: 36,
	icons_gap: 10,
	icons_padding: { top: 0, right: 0, bottom: 0, left: 0 },
	link_facebook: '',
	link_twitter: '',
	link_youtube: '',
	link_instagram: '',
	link_linkedin: '',
	link_pinterest: '',
	link_tumblr: '',
	link_custom: '',
	show_bottom_text: 'off',
	bottom_header: '',
	bottom_content: '',
	bottom_text_align: 'left',
	custom_css: '',
};

export const MEMBER_TEMPLATE_DEFAULTS = {
	infotitle: 'Members Only',
	titletag: 'h2',
	text: 'This content is restricted to site members. If you are an existing user, please log in. New users may register below.',
	text_align: 'center',
	mode: 'login',
	login_mode: 'form',
	btntext: 'Login',
};

export const SECURITY_DEFAULTS = {
	login_limit_enabled: true,
	max_attempts: 5,
	lockout_duration: 15,
	honeypot_enabled: true,
	disable_xmlrpc: false,
	hide_wp_version: false,
	disable_rest_users: false,
	recaptcha_enabled: false,
	recaptcha_site_key: '',
	recaptcha_secret_key: '',
	custom_login_url: '',
	login_log_enabled: true,
	hide_login_page: false,
	after_login_redirect: '',
	// Registration protection.
	reg_honeypot_enabled: true,
	reg_rate_limit_enabled: false,
	reg_rate_limit_max: 3,
	reg_rate_limit_window: 60,
	reg_recaptcha_enabled: false,
	reg_block_disposable_emails: false,
	reg_email_domain_mode: 'off',
	reg_email_domain_list: '',
	reg_admin_approval: false,
	// Two-Factor Authentication (Pro).
	two_factor_enabled: false,
	two_factor_roles: [ 'administrator' ],
	// IP Management (Pro).
	ip_blocklist: '',
	ip_allowlist: '',
	// AI Crawler Blocker.
	ai_crawler_blocker_enabled: true,
	ai_crawler_block_ua: true,
	ai_crawler_bots: null,
	ai_crawler_custom_ua: '',
};

export const GENERAL_DEFAULTS = {
	cookie_expiration: 10,
	delete_data_on_uninstall: false,
	custom_css: '',
};

export const SITE_ACCESS_DEFAULTS = {
	admin_only_enabled: false,
	admin_only_action: 'redirect',
	admin_only_header: 'Login Required',
	admin_only_message: 'This site is for members only. Please log in to continue.',
	site_password_enabled: false,
	site_password: '123',
	site_password_message: 'This site is password protected. Please enter the password to continue.',
	site_password_cookie_days: 7,
	site_password_bypass_logged_in: true,
};
