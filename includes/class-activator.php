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

		$login_log_table = $wpdb->prefix . 'wpepp_login_log';

		$sql = "CREATE TABLE {$login_log_table} (
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

		$slow_queries_table = $wpdb->prefix . 'wpepp_slow_queries';

		$sql .= "CREATE TABLE {$slow_queries_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			query_sql LONGTEXT NOT NULL,
			exec_time FLOAT NOT NULL DEFAULT 0,
			call_stack TEXT,
			recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_exec_time (exec_time),
			INDEX idx_recorded (recorded_at)
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
			'wpepp_password_settings' => wp_json_encode( [
				'active_style'              => 'one',
				'logo_type'                 => 'none',
				'logo_image'                => '',
				'logo_width'                => 120,
				'logo_height'               => 60,
				'logo_text'                 => '',
				'logo_text_font_size'       => 24,
				'logo_text_color'           => '#1e1e1e',
				'page_background_type'      => 'color',
				'page_background_color'     => '#f0f0f1',
				'page_background_image'     => '',
				'page_background_position'  => 'center center',
				'page_background_size'      => 'cover',
				'page_background_gradient'  => '',
				'page_background_video'     => '',
				'show_top_text'             => 'on',
				'top_header'                => 'This content is password protected for members only',
				'top_content'               => 'For more public resources check out our followed link.',
				'top_text_align'            => 'center',
				'form_label'                => 'Password',
				'form_label_type'           => 'label',
				'form_btn_text'             => 'Submit',
				'form_errortext'            => 'The password you have entered is invalid',
				'error_text_position'       => 'top',
				'form_outer_background'     => '',
				'form_background'           => '#ffffff',
				'form_text_color'           => '#1e1e1e',
				'input_background'          => '#ffffff',
				'input_text_color'          => '#1e1e1e',
				'input_border_color'        => '#8c8f94',
				'button_color'              => '#42276A',
				'button_text_color'         => '#ffffff',
				'button_font_size'          => 14,
				'heading_color'             => '#1e1e1e',
				'heading_font_size'         => 20,
				'heading_show_background'   => false,
				'heading_background_color'  => 'rgba(0,0,0,0.45)',
				'label_font_size'           => 14,
				'label_color'               => '#1e1e1e',
				'show_social'               => 'on',
				'icons_vposition'           => 'top',
				'icons_alignment'           => 'center',
				'icons_style'               => 'square',
				'icons_color'               => '',
				'icons_size'                => 36,
				'icons_gap'                 => 10,
				'show_bottom_text'          => 'off',
				'bottom_header'             => '',
				'bottom_content'            => '',
				'bottom_text_align'         => 'left',
				'custom_css'                => '',
			] ),
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}
}
