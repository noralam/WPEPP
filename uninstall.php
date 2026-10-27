<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Deletes ALL plugin data — options, post meta, user meta, transients,
 * cron events, and custom tables — but only when the admin enabled
 * "delete data on uninstall". Otherwise everything is preserved.
 *
 * @package wpepp
 * @since   2.0.0
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$wpepp_general = json_decode( (string) get_option( 'wpepp_general_settings', '{}' ), true );
if ( empty( $wpepp_general['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

/* ─── Options ──────────────────────────────────────────────────────── */

$wpepp_options = [
	// Section settings.
	'wpepp_login_settings',
	'wpepp_register_settings',
	'wpepp_password_settings',
	'wpepp_lostpassword_settings',
	'wpepp_security_settings',
	'wpepp_general_settings',
	'wpepp_member_template',
	'wpepp_site_access_settings',

	// State / counters.
	'wpepp_has_pro',
	'wpepp_version',
	'wpepp_db_version',
	'wpepp_cpu_db_version',
	'wpepp_install_date',
	'wpepp_active_template',
	'wpepp_auto_login_links',
	'wpepp_ai_crawler_blocked_count',

	// Notice / review state.
	'wpepp_pro_notice_dismissed',
	'wpepp_pro_notice_dismiss_count',
	'wpepp_review_dismissed',
	'wpepp_new_badge_dismissed',

	// Pro license options.
	'WpEditPasswordProtectedPro_lic_email',
	'WpEditPasswordProtectedPro_lic_Key',
];

foreach ( $wpepp_options as $wpepp_option ) {
	delete_option( $wpepp_option );
}

// Delete any remaining wpepp_* option (covers future/missed keys, autoload-safe).
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'wpepp_' ) . '%'
	)
);

/* ─── Post meta ────────────────────────────────────────────────────── */

$wpepp_meta_keys = [
	// Content lock.
	'_wpepp_content_lock_enabled',
	'_wpepp_content_lock_message',
	'_wpepp_content_lock_action',
	'_wpepp_content_lock_redirect',
	'_wpepp_content_lock_header',
	'_wpepp_content_lock_expiry',
	'_wpepp_content_lock_show_excerpt',
	'_wpepp_content_lock_excerpt_text',
	'_wpepp_content_lock_roles',

	// Conditional display.
	'_wpepp_conditional_display_enable',
	'_wpepp_conditional_display_condition',
	'_wpepp_conditional_action',
	'_wpepp_conditional_control_title',
	'_wpepp_conditional_control_featured_image',
	'_wpepp_conditional_control_comments',
	'_wpepp_conditional_notice_enable',
	'_wpepp_conditional_notice_text',
	'_wpepp_conditional_user_role',
	'_wpepp_conditional_device_type',
	'_wpepp_conditional_day_of_week',
	'_wpepp_conditional_time_start',
	'_wpepp_conditional_time_end',
	'_wpepp_conditional_date_start',
	'_wpepp_conditional_date_end',
	'_wpepp_conditional_recurring_days',
	'_wpepp_conditional_recurring_time_start',
	'_wpepp_conditional_recurring_time_end',
	'_wpepp_conditional_post_type',
	'_wpepp_conditional_browser_type',
	'_wpepp_conditional_url_parameter_key',
	'_wpepp_conditional_url_parameter_value',
	'_wpepp_conditional_referrer_source',
];

foreach ( $wpepp_meta_keys as $wpepp_key ) {
	delete_post_meta_by_key( $wpepp_key );
}

/* ─── User meta ────────────────────────────────────────────────────── */

$wpepp_user_meta_keys = [
	'_wpepp_2fa_secret',
	'_wpepp_2fa_enabled',
	'_wpepp_2fa_recovery_codes',
	'_wpepp_2fa_pending_secret',
	'_wpepp_pending_approval',
	'_wpepp_pending_since',
	'_wpepp_passkey_credentials',
];

foreach ( $wpepp_user_meta_keys as $wpepp_user_key ) {
	delete_metadata( 'user', 0, $wpepp_user_key, '', true );
}

/* ─── Custom tables ────────────────────────────────────────────────── */

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_login_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_slow_queries" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_cpu_slow_queries" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_blocks" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_scan_issues" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

/* ─── Transients & object cache ────────────────────────────────────── */

wp_cache_flush_group( 'wpepp' );

$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wpepp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wpepp_' ) . '%',
		$wpdb->esc_like( '_site_transient_wpepp_' ) . '%',
		$wpdb->esc_like( '_site_transient_timeout_wpepp_' ) . '%'
	)
);

/* ─── Cron events ──────────────────────────────────────────────────── */

wp_clear_scheduled_hook( 'wpepp_prune_login_log' );
wp_clear_scheduled_hook( 'wpepp_prune_blocks' );
wp_clear_scheduled_hook( 'wpepp_prune_rl_buckets' );
wp_clear_scheduled_hook( 'wpepp_scan_daily' );
wp_clear_scheduled_hook( 'wpepp_scan_watchdog' );
wp_clear_scheduled_hook( 'wpepp_prune_events' );
wp_clear_scheduled_hook( 'wpepp_weekly_report' );
wp_clear_scheduled_hook( 'wpepp_cpu_prune_queries' );

/* ─── Firewall extended-mode artifacts ─────────────────────────────── */

$wpepp_fw_dir = WP_CONTENT_DIR . '/wpepp-fw';

// Remove prepend directives from ABSPATH config files.
foreach ( [ ABSPATH . '.user.ini', ABSPATH . '.htaccess' ] as $wpepp_cfg_file ) {
	if ( ! file_exists( $wpepp_cfg_file ) ) {
		continue;
	}

	$wpepp_cfg_content = (string) file_get_contents( $wpepp_cfg_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

	$wpepp_cfg_content = preg_replace(
		'/;?WPEPP-FW-BEGIN.*?;?WPEPP-FW-END[\r\n]?/s',
		'',
		$wpepp_cfg_content
	);
	$wpepp_cfg_content = preg_replace(
		'/# BEGIN WPEPP-FW.*?# END WPEPP-FW\s*/s',
		'',
		$wpepp_cfg_content
	);

	file_put_contents( $wpepp_cfg_file, $wpepp_cfg_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

// Remove the runtime directory.
if ( is_dir( $wpepp_fw_dir ) ) {
	foreach ( (array) glob( $wpepp_fw_dir . '/*' ) as $wpepp_fw_file ) {
		wp_delete_file( $wpepp_fw_file ); // Best-effort cleanup.
	}
	global $wp_filesystem;
	if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
		$wp_filesystem->rmdir( $wpepp_fw_dir );
	}
}

/* ─── Legacy v1 options (if migration didn't clean them) ───────────── */

$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( 'wppasspro_' ) . '%',
		$wpdb->esc_like( 'wpe_adpage_' ) . '%'
	)
);

delete_option( 'pp_basic_settings' );
delete_option( 'pp_admin_page' );
