<?php
/**
 * Plugin activator — activation and deactivation hooks.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Activator
 */
class WPEPP_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		self::set_defaults();

		// Run migration from v1 settings.
		WPEPP_Migration::migrate();
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . 'wpepp_login_log';

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_login VARCHAR(255) NOT NULL,
			ip_address VARCHAR(45) NOT NULL,
			status ENUM('success','failed','lockout') NOT NULL,
			user_agent TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_ip (ip_address),
			INDEX idx_status (status),
			INDEX idx_created (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Set default option values if they do not exist.
	 */
	private static function set_defaults() {
		if ( false === get_option( 'wpepp_version' ) ) {
			update_option( 'wpepp_version', WPEPP_VERSION );
		}

		if ( false === get_option( 'wpepp_install_date' ) ) {
			update_option( 'wpepp_install_date', current_time( 'mysql' ) );
		}

		$defaults = [
			'wpepp_security_settings' => wp_json_encode( [
				'login_limit_enabled'  => true,
				'max_attempts'         => 5,
				'lockout_duration'     => 15,
				'disable_xmlrpc'       => true,
				'hide_wp_version'      => true,
				'disable_rest_users'   => true,
				'honeypot_enabled'     => true,
				'recaptcha_enabled'    => false,
				'recaptcha_site_key'   => '',
				'recaptcha_secret_key' => '',
				'custom_login_url'     => '',
				'login_log_enabled'    => true,
			] ),
			'wpepp_general_settings'  => wp_json_encode( [
				'cookie_expiration'        => 10,
				'delete_data_on_uninstall' => false,
			] ),
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}
}
