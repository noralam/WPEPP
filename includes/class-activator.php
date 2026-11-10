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
	 * Upgrade routine for plugin updates applied without reactivation.
	 *
	 * Runs once per version bump (plugins_loaded) — creates missing tables,
	 * fills missing defaults and re-runs the v1 migration guard.
	 */
	public static function maybe_upgrade() {
		WPEPP_Migration::migrate();
		if ( get_option( 'wpepp_version' ) === WPEPP_VERSION ) {
			return;
		}

		self::create_tables();
		self::set_defaults();

		// Migration only bumps the version from <2.0 — keep it current here.
		update_option( 'wpepp_version', WPEPP_VERSION );
	}

	/**
	 * Create custom database tables.
	 */
	public static function create_tables() {
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

						if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::create_table();
		}

		if ( class_exists( 'WPEPP_Blocks' ) ) {
			WPEPP_Blocks::create_table();
		}

		if ( class_exists( 'WPEPP_Issues' ) ) {
			WPEPP_Issues::create_table();
		}
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
			'wpepp_security_settings'    => wp_json_encode( [
				'login_limit_enabled'             => true,
				'max_attempts'                    => 5,
				'lockout_duration'                => 15,
				'remember_me_auto_check'          => true,
				'honeypot_enabled'                => true,
				'disable_xmlrpc'                  => true,
				'hide_wp_version'                 => true,
				'disable_rest_users'              => true,
				'recaptcha_enabled'               => false,
				'recaptcha_site_key'              => '',
				'recaptcha_secret_key'            => '',
				'custom_login_url'                => '',
				'hide_login_page'                 => false,
				'after_login_redirect'            => '',
				'login_log_enabled'               => true,
				'auto_prune_logs_enabled'         => true,
				'events_retention_days'           => 30,
				'login_log_retention_days'        => 30,
				'slow_queries_retention_days'     => 7,
				'max_log_rows_cap'                => 5000,
				// Registration protection
				'reg_honeypot_enabled'            => true,
				'reg_rate_limit_enabled'          => false,
				'reg_rate_limit_max'              => 3,
				'reg_rate_limit_window'           => 60,
				'reg_recaptcha_enabled'           => false,
				'reg_block_disposable_emails'     => false,
				'reg_email_domain_mode'           => 'off',
				'reg_email_domain_list'           => '',
				'reg_admin_approval'              => false,
				// Two-Factor Authentication
				'two_factor_enabled'              => false,
				'two_factor_roles'                => [ 'administrator' ],
				'two_fa_remember_days'            => 0,
				'passkeys_enabled'                => false,
				// Password policy
				'pw_policy_enabled'               => false,
				'pw_min_length'                   => 12,
				'pw_require_mixed'                => true,
				'pw_require_symbol'               => false,
				'pw_block_common'                 => true,
				'pw_breach_check'                 => false,
				// Alerts & activity
				'alerts_enabled'                  => true,
				'alert_email'                     => '',
				'alert_lockout'                   => true,
				'alert_scan_criticals'            => true,
				'alert_admin_login'               => false,
				'alert_firewall_burst'            => false,
				'alert_firewall_burst_min'        => 25,
				'alert_weekly_report'             => false,
				// Hardening headers
				'hdr_x_frame'                     => 'SAMEORIGIN',
				'hdr_nosniff'                     => true,
				'hdr_referrer'                    => 'strict-origin-when-cross-origin',
				'hdr_permissions_policy'          => '',
				'hdr_hsts'                        => false,
				'hdr_hsts_subdomains'             => true,
				'hdr_hsts_preload'                => false,
				'hdr_csp_report_only'             => '',
				// Unified CAPTCHA layer
				'captcha_enabled'                 => false,
				'captcha_provider'                => '',
				'captcha_threshold'               => 0.5,
				'turnstile_site_key'              => '',
				'turnstile_secret_key'            => '',
				'recaptcha_test_mode'             => false,
				'reg_captcha_enabled'             => false,
				// Anti-Spam & WooCommerce Protection
				'anti_spam_enabled'               => true,
				'wc_spam_protection_enabled'      => true,
				'wc_honeypot_enabled'             => true,
				'wc_disposable_email_block'       => true,
				'wc_rate_limit_enabled'           => true,
				'wc_captcha_enabled'              => false,
				'comment_spam_protection_enabled' => true,
				'comment_honeypot_enabled'        => true,
				'comment_timegate_enabled'        => true,
				'comment_captcha_enabled'         => false,
				'lostpassword_honeypot_enabled'   => true,
				'lostpassword_captcha_enabled'    => false,
				// Scanner schedule
				'scan_schedule_enabled'           => false,
				// Live Traffic
				'live_traffic_enabled'            => false,
				// IP Management
				'ip_blocklist'                    => '',
				'ip_allowlist'                    => '',
				// AI Crawler Blocker
				'ai_crawler_blocker_enabled'      => false,
				'ai_crawler_block_ua'             => false,
				'ai_crawler_bots'                 => null,
				'ai_crawler_custom_ua'            => '',
				// Username shield
				'username_shield_enabled'         => true,
				'username_shield_mask_errors'     => true,
			] ),
			'wpepp_firewall_settings'    => wp_json_encode( [
				'basic_enabled'    => true,
				'extended_enabled' => false,
				'learning_mode'    => false,
			] ),
			'wpepp_site_access_settings' => wp_json_encode( [
				'admin_only_enabled'             => false,
				'admin_only_target'              => 'logged_in',
				'admin_only_action'              => 'redirect',
				'admin_only_header'              => 'Login Required',
				'admin_only_message'             => 'This site is for members only. Please log in to continue.',
				'site_password_enabled'          => false,
				'site_password'                  => '',
				'site_password_message'          => 'This site is password protected. Please enter the password to continue.',
				'site_password_cookie_days'      => 7,
				'site_password_bypass_logged_in' => true,
				'country_restriction_enabled'    => false,
				'country_restriction_mode'       => 'block',
				'country_restriction_list'       => [],
				'country_restriction_title'      => 'Access Restricted',
				'country_restriction_message'    => 'This site is not available in your region.',
			] ),
			'wpepp_general_settings'     => wp_json_encode( [
				'cookie_expiration'        => 10,
				'delete_data_on_uninstall' => false,
				'custom_css'               => '',
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
