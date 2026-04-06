<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package wpepp
 * @since   2.0.0
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$wpepp_general = json_decode( get_option( 'wpepp_general_settings', '{}' ), true );
if ( empty( $wpepp_general['delete_data_on_uninstall'] ) ) {
	return;
}

// Delete all plugin options.
$wpepp_options = [
	'wpepp_login_settings',
	'wpepp_register_settings',
	'wpepp_password_settings',
	'wpepp_lostpassword_settings',
	'wpepp_security_settings',
	'wpepp_general_settings',
	'wpepp_member_template',
	'wpepp_has_pro',
	'wpepp_db_version',
];

foreach ( $wpepp_options as $wpepp_option ) {
	delete_option( $wpepp_option );
}

// Delete post meta.
global $wpdb;
$wpepp_meta_keys = [
	'_wpepp_content_lock',
	'_wpepp_content_lock_message',
	'_wpepp_conditional_display',
];

foreach ( $wpepp_meta_keys as $wpepp_key ) {
	$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $wpepp_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// Drop custom table.
$wpepp_table = $wpdb->prefix . 'wpepp_login_log';
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpepp_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Clear transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM %i WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->options,
		'_transient_wpepp_%',
		'_transient_timeout_wpepp_%'
	)
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Legacy v1 options (if migration didn't clean them).
$wpepp_legacy = [
	'wppasspro_passwords',
	'wppasspro_font',
	'wppasspro_style',
	'wpe_adpage_infotitle',
	'wpe_adpage_titletag',
	'wpe_adpage_text',
	'wpe_adpage_text_align',
	'wpe_adpage_mode',
	'wpe_adpage_login_mode',
	'wpe_adpage_btntext',
];

foreach ( $wpepp_legacy as $wpepp_option ) {
	delete_option( $wpepp_option );
}
