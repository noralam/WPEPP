/**
 * Pro feature definitions — which features and conditions require Pro.
 */

export const PRO_FEATURES = {
	register_form: true,
	lostpassword_form: true,
	password_style_three: true,
	password_style_four: true,
	content_lock: true,
	custom_login_url: true,
	login_log: true,
	custom_css: true,
	recaptcha: true,
};

export const PRO_CONDITIONS = [
	'user_role',
	'specific_users',
	'date_range',
	'time_range',
	'day_of_week',
	'url_parameter',
	'referrer',
	'cookie',
	'ip_address',
	'device_type',
];

export const FREE_CONDITIONS = [
	'user_logged_in',
	'user_logged_out',
];
