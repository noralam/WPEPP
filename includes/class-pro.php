<?php
/**
 * Pro lock helper functions.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if Pro features are unlocked.
 *
 * @return bool
 */
function wpepp_has_pro_check() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'wpepp-pro/wpepp-pro.php' ) && 'yes' === get_option( 'wpepp_has_pro', 'no' );
}

/**
 * Get available conditional display conditions based on tier.
 *
 * @return array
 */
function wpepp_get_available_conditions() {
	$free_conditions = [
		'user_logged_in'  => __( 'User is logged in', 'wp-edit-password-protected' ),
		'user_logged_out' => __( 'User is logged out', 'wp-edit-password-protected' ),
	];

	if ( ! wpepp_has_pro_check() ) {
		return $free_conditions;
	}

	return array_merge( $free_conditions, [
		'user_role'       => __( 'User role', 'wp-edit-password-protected' ),
		'device_type'     => __( 'Device type', 'wp-edit-password-protected' ),
		'day_of_week'     => __( 'Day of week', 'wp-edit-password-protected' ),
		'time_range'      => __( 'Time range', 'wp-edit-password-protected' ),
		'date_range'      => __( 'Date range', 'wp-edit-password-protected' ),
		'recurring'       => __( 'Recurring schedule', 'wp-edit-password-protected' ),
		'post_type'       => __( 'Post type', 'wp-edit-password-protected' ),
		'browser_type'    => __( 'Browser type', 'wp-edit-password-protected' ),
		'url_parameter'   => __( 'URL parameter', 'wp-edit-password-protected' ),
		'referrer_source' => __( 'Referrer source', 'wp-edit-password-protected' ),
	] );
}

/**
 * Enforce Pro restrictions on settings before save.
 *
 * @param array  $settings The submitted settings.
 * @param string $section  The settings section.
 * @return array Filtered settings.
 */
function wpepp_enforce_pro_settings( $settings, $section ) {
	if ( wpepp_has_pro_check() ) {
		return $settings;
	}

	switch ( $section ) {
		case 'password':
			$allowed_styles = [ 'style_one', 'style_two', 'one', 'two', 'four' ];
			if ( isset( $settings['active_style'] ) && ! in_array( $settings['active_style'], $allowed_styles, true ) ) {
				$settings['active_style'] = 'one';
			}
			unset( $settings['custom_css'] );
			break;

		case 'login':
			unset( $settings['custom_css'], $settings['form']['font_family'] );
			break;

		case 'register':
		case 'lostpassword':
			return [];

		case 'site_access':
			// Login popup is Pro only — force redirect for free users.
			if ( isset( $settings['admin_only_action'] ) && 'popup' === $settings['admin_only_action'] ) {
				$settings['admin_only_action'] = 'redirect';
			}
			break;

		case 'member_template':
			// Popup (Glassdoor) mode is Pro only — force login for free users.
			if ( isset( $settings['mode'] ) && 'popup' === $settings['mode'] ) {
				$settings['mode'] = 'login';
			}
			break;

		case 'security':
			unset(
				$settings['recaptcha_enabled'],
				$settings['recaptcha_site_key'],
				$settings['recaptcha_secret_key'],
				$settings['custom_login_url'],
				$settings['login_log_enabled'],
				$settings['reg_recaptcha_enabled'],
				$settings['reg_block_disposable_emails'],
				$settings['reg_email_domain_mode'],
				$settings['reg_email_domain_list'],
				$settings['reg_admin_approval'],
				$settings['two_factor_enabled'],
				$settings['two_factor_roles'],
				$settings['ip_blocklist'],
				$settings['ip_allowlist'],
				$settings['ai_crawler_custom_ua']
			);
			break;
	}

	return $settings;
}

/**
 * Block Pro-only REST endpoints for Free users.
 *
 * @return true|\WP_Error
 */
function wpepp_check_pro_permission() {
	if ( ! wpepp_has_pro_check() ) {
		return new WP_Error(
			'wpepp_pro_required',
			__( 'This feature requires WPEPP Pro.', 'wp-edit-password-protected' ),
			[ 'status' => 403 ]
		);
	}
	return true;
}
