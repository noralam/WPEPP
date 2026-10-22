<?php
/**
 * Security score engine — weighted checklist across all modules.
 *
 * Single source of truth for the dashboard score ring and setup wizard.
 * Pro modules extend the checklist through the documented filter
 * (Layer 3 of the free/pro bridge):
 *
 *   add_filter( 'wpepp/security/score_items', function ( $items ) {
 *       $items[] = [
 *           'id' => 'firewall_extended', 'label' => ..., 'weight' => 10,
 *           'category' => 'firewall', 'done' => $my_check, 'route' => '/firewall',
 *       ];
 *       return $items;
 *   } );
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Security_Score
 */
class WPEPP_Security_Score {

	/**
	 * Grade thresholds.
	 *
	 * @var array
	 */
	const GRADES = [
		'A' => 90,
		'B' => 75,
		'C' => 60,
		'D' => 40,
	];

	/**
	 * Get the decoded security settings.
	 *
	 * @return array
	 */
	private static function security_settings() {
		$settings = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Get the decoded site-access settings.
	 *
	 * @return array
	 */
	private static function site_access_settings() {
		$settings = json_decode( (string) get_option( 'wpepp_site_access_settings', '{}' ), true );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Build the weighted checklist.
	 *
	 * Each item: id, label, weight (int), category, done (bool), route (SPA path).
	 *
	 * @return array[]
	 */
	public static function get_items() {
		$s = self::security_settings();
		$sa = self::site_access_settings();

		$bool = static function ( $key ) use ( $s ) {
			return ! empty( $s[ $key ] );
		};

		// Default-on helpers treat unset as ON (matches runtime defaults).
		$default_on = static function ( $key ) use ( $s ) {
			return ! isset( $s[ $key ] ) || ! empty( $s[ $key ] );
		};

		$items = [
			/* ── Login protection ── */
			[
				'id'       => 'login_limiter',
				'label'    => __( 'Login attempt limiter active', 'wp-edit-password-protected' ),
				'weight'   => 12,
				'category' => 'login',
				'done'     => $default_on( 'login_limit_enabled' ),
				'route'    => '/logins/protection',
			],
			[
				'id'       => 'honeypot',
				'label'    => __( 'Login honeypot enabled', 'wp-edit-password-protected' ),
				'weight'   => 6,
				'category' => 'login',
				'done'     => $default_on( 'honeypot_enabled' ),
				'route'    => '/logins/protection',
			],
			[
				'id'       => 'two_factor_admin',
				'label'    => __( 'Two-factor authentication enabled', 'wp-edit-password-protected' ),
				'weight'   => 10,
				'category' => 'login',
				'done'     => $bool( 'two_factor_enabled' ),
				'route'    => '/logins/two-factor',
			],

			/* ── Shielding ── */
			[
				'id'       => 'username_shield',
				'label'    => __( 'Username discovery blocked (?author=N, oEmbed)', 'wp-edit-password-protected' ),
				'weight'   => 8,
				'category' => 'shield',
				'done'     => $bool( 'username_shield_enabled' ),
				'route'    => '/logins/protection',
			],
			[
				'id'       => 'rest_users_off',
				'label'    => __( 'REST user enumeration disabled', 'wp-edit-password-protected' ),
				'weight'   => 6,
				'category' => 'shield',
				'done'     => $bool( 'disable_rest_users' ),
				'route'    => '/logins/protection',
			],
			[
				'id'       => 'ai_crawler_block',
				'label'    => __( 'AI crawler blocker enabled', 'wp-edit-password-protected' ),
				'weight'   => 5,
				'category' => 'shield',
				'done'     => $bool( 'ai_crawler_blocker_enabled' ),
				'route'    => '/ai-crawler',
			],

			/* ── Hardening ── */
			[
				'id'       => 'xmlrpc_off',
				'label'    => __( 'XML-RPC disabled', 'wp-edit-password-protected' ),
				'weight'   => 8,
				'category' => 'harden',
				'done'     => $bool( 'disable_xmlrpc' ),
				'route'    => '/settings/general',
			],
			[
				'id'       => 'hide_version',
				'label'    => __( 'WordPress version hidden', 'wp-edit-password-protected' ),
				'weight'   => 4,
				'category' => 'harden',
				'done'     => $bool( 'hide_wp_version' ),
				'route'    => '/settings/general',
			],
			[
				'id'       => 'hide_login',
				'label'    => __( 'Login page hidden from visitors', 'wp-edit-password-protected' ),
				'weight'   => 8,
				'category' => 'harden',
				'done'     => $bool( 'hide_login_page' ) || ! empty( $s['custom_login_url'] ),
				'route'    => '/logins/custom-url',
			],
			[
				'id'       => 'file_edit_disabled',
				'label'    => __( 'Plugin/theme file editing disabled (DISALLOW_FILE_EDIT)', 'wp-edit-password-protected' ),
				'weight'   => 7,
				'category' => 'harden',
				'done'     => class_exists( 'WPEPP_File_Hardening' ) ? (bool) WPEPP_File_Hardening::status()['file_edit_disabled'] : ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ),
				'route'    => '/settings/general',
			],

			/* ── Access control ── */
			[
				'id'       => 'https_admin',
				'label'    => __( 'Admin area served over HTTPS', 'wp-edit-password-protected' ),
				'weight'   => 10,
				'category' => 'access',
				'done'     => is_ssl(),
				'route'    => '/settings/general',
			],
			[
				'id'       => 'site_password_or_admin_only',
				'label'    => __( 'Site-wide protection configured (password or admin-only)', 'wp-edit-password-protected' ),
				'weight'   => 6,
				'category' => 'access',
				'done'     => ! empty( $sa['site_password_enabled'] ) || ! empty( $sa['admin_only_enabled'] ),
				'route'    => '/site-access',
			],

			/* ── Monitoring ── */
			[
				'id'       => 'registration_protection',
				'label'    => __( 'Registration rate limiting enabled', 'wp-edit-password-protected' ),
				'weight'   => 5,
				'category' => 'monitor',
				'done'     => $default_on( 'reg_rate_limit_enabled' ),
				'route'    => '/logins/registration',
			],
			[
				'id'       => 'alerts_email_set',
				'label'    => __( 'Security alerts email address set', 'wp-edit-password-protected' ),
				'weight'   => 5,
				'category' => 'monitor',
				'done'     => ! empty( $s['alert_email'] ),
				'route'    => '/tools/log',
			],
		];

		/**
		 * Extend/modify the security checklist.
		 *
		 * @param array[] $items Checklist items.
		 */
		$items = apply_filters( 'wpepp/security/score_items', $items ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- documented public API.

		// Defensive normalization — third-party items must carry required keys.
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item )
				|| empty( $item['id'] )
				|| ! isset( $item['done'], $item['weight'] )
				|| (int) $item['weight'] <= 0 ) {
				unset( $items[ $i ] );
				continue;
			}

			$items[ $i ] = [
				'id'       => sanitize_key( $item['id'] ),
				'label'    => sanitize_text_field( (string) ( $item['label'] ?? $item['id'] ) ),
				'weight'   => (int) $item['weight'],
				'category' => sanitize_key( $item['category'] ?? 'general' ),
				'done'     => (bool) $item['done'],
				'route'    => isset( $item['route'] ) ? '#' . ltrim( sanitize_text_field( (string) $item['route'] ), '#' ) : '#/',
			];
		}

		return array_values( $items );
	}

	/**
	 * Compute the score summary.
	 *
	 * @return array {score:int, grade:string, passed:int, total:int, max:int, earned:int, items:array[]}
	 */
	public static function get_summary() {
		$items  = self::get_items();
		$max    = 0;
		$earned = 0;
		$passed = 0;

		foreach ( $items as $item ) {
			$max += (int) $item['weight'];
			if ( $item['done'] ) {
				$earned += (int) $item['weight'];
				$passed++;
			}
		}

		$score = $max > 0 ? (int) round( ( $earned / $max ) * 100 ) : 0;

		return [
			'score'  => $score,
			'grade'  => self::grade_for( $score ),
			'passed' => $passed,
			'total'  => count( $items ),
			'max'    => $max,
			'earned' => $earned,
			'items'  => $items,
		];
	}

	/**
	 * Letter grade for a numeric score.
	 *
	 * @param int $score 0–100.
	 * @return string A|B|C|D|F
	 */
	public static function grade_for( $score ) {
		foreach ( self::GRADES as $grade => $min ) {
			if ( $score >= $min ) {
				return $grade;
			}
		}
		return 'F';
	}

	/**
	 * Wizard step presets — FREE-tier keys only.
	 *
	 * Applied server-side through a fixed whitelist; the request never
	 * carries user-controlled setting values.
	 *
	 * @return array[] step => ['label','desc','settings'=>key=>value]
	 */
	public static function wizard_presets() {
		$admin_email = get_option( 'admin_email', '' );

		$presets = [
			'logins'  => [
				'label'    => __( 'Protect logins', 'wp-edit-password-protected' ),
				'settings' => [
					'login_limit_enabled'  => true,
					'honeypot_enabled'     => true,
					'reg_honeypot_enabled' => true,
					'reg_rate_limit_enabled' => true,
					'max_attempts'         => 5,
					'lockout_duration'     => 15,
				],
			],
			'shield'  => [
				'label'    => __( 'Shield usernames', 'wp-edit-password-protected' ),
				'settings' => [
					'username_shield_enabled'      => true,
					'username_shield_mask_errors'  => true,
					'disable_rest_users'           => true,
				],
			],
			'harden'  => [
				'label'    => __( 'Harden the site', 'wp-edit-password-protected' ),
				'settings' => [
					'disable_xmlrpc'               => true,
					'hide_wp_version'              => true,
					'hide_login_page'              => true,
					'hide_login_exempt_logged_in'  => true,
				],
			],
			'monitor' => [
				'label'    => __( 'Enable monitoring', 'wp-edit-password-protected' ),
				'settings' => [
					'ai_crawler_blocker_enabled'   => true,
					'ai_crawler_block_ua'          => true,
					'login_log_retention_days'     => 30,
				],
			],
			'alerts'  => [
				'label'    => __( 'Set up alerts', 'wp-edit-password-protected' ),
				'settings' => [
					'alert_email'                  => is_email( $admin_email ) ? $admin_email : '',
					'alert_lockout'                => true,
				],
			],
		];

		/**
		 * Filter available wizard steps (pro may add scanning/firewall steps).
		 *
		 * @param array[] $presets Wizard step presets.
		 */
		return apply_filters( 'wpepp/security/wizard_presets', $presets ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- documented public API.
	}

	/**
	 * Apply one wizard step preset to the saved security settings.
	 *
	 * Only whitelisted preset steps are accepted; values come exclusively
	 * from wizard_presets() — never from request input.
	 *
	 * @param string $step Step key.
	 * @return true|\WP_Error
	 */
	public static function apply_wizard_step( $step ) {
		$step   = sanitize_key( (string) $step );
		$presets = self::wizard_presets();

		if ( ! isset( $presets[ $step ] ) || ! is_array( $presets[ $step ]['settings'] ?? null ) ) {
			return new \WP_Error(
				'wpepp_unknown_step',
				__( 'Unknown setup step.', 'wp-edit-password-protected' ),
				[ 'status' => 400 ]
			);
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$settings = is_array( $settings ) ? $settings : [];

		// Never overwrite an explicitly-set secret-bearing or list value here.
		$skip_keys = [ 'recaptcha_secret_key', 'recaptcha_site_key', 'trusted_proxies', 'ip_blocklist', 'ip_allowlist' ];

		foreach ( $presets[ $step ]['settings'] as $key => $value ) {
			if ( in_array( $key, $skip_keys, true ) ) {
				continue;
			}

			if ( is_bool( $value ) ) {
				$settings[ $key ] = $value;
				continue;
			}

			if ( is_int( $value ) ) {
				$settings[ $key ] = absint( $value );
				continue;
			}

			$settings[ $key ] = sanitize_text_field( (string) $value );
		}

		update_option( 'wpepp_security_settings', wp_json_encode( $settings ) );

		do_action( 'wpepp/settings/saved', 'security' );

		return true;
	}

	/**
	 * Generate a comprehensive Site Health & Security Audit Report.
	 *
	 * @return array
	 */
	public static function generate_audit_report() {
		global $wp_version;

		// Summary & checklist
		$summary = self::get_summary();
		$items   = self::get_items();

		// Scanner info
		$scan_state = class_exists( 'WPEPP_Scan_Engine' ) ? WPEPP_Scan_Engine::state() : [];
		$last_scan  = (string) get_option( 'wpepp_last_scan', '' );
		$new_issues = class_exists( 'WPEPP_Issues' ) ? WPEPP_Issues::count_new() : 0;
		$sigs_info  = class_exists( 'WPEPP_Signatures' ) ? WPEPP_Signatures::get_sync_info() : [];

		// Firewall info
		$fw_status = [];
		if ( class_exists( 'WPEPP_Firewall' ) ) {
			$fw_s      = WPEPP_Firewall::settings();
			$fw_status = [
				'basic_enabled'    => ! empty( $fw_s['basic_enabled'] ),
				'extended_enabled' => WPEPP_Firewall::extended_installed(),
				'learning_mode'    => ! empty( $fw_s['learning_mode'] ),
				'rule_count'       => count( WPEPP_Firewall::get_rules() ),
				'blocked_total'    => (int) get_option( WPEPP_Firewall::COUNTER_KEY, 0 ),
			];
		}

		// File permissions & hardening
		$hardening = class_exists( 'WPEPP_File_Hardening' ) ? WPEPP_File_Hardening::status() : [];

		// SMTP & Alerts
		$smtp_info = class_exists( 'WPEPP_Alerts' ) ? WPEPP_Alerts::detect_smtp() : [];
		$sec_s     = self::security_settings();
		$alerts_to = sanitize_email( $sec_s['alert_email'] ?? '' ) ?: get_option( 'admin_email' );

		// Proxy info
		$proxy_info = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::inspect_proxy_status() : [];

		// Activity stats
		$stats = class_exists( 'WPEPP_Logger' ) ? WPEPP_Logger::stats( 7 ) : [];

		return [
			'generated_at'     => current_time( 'mysql' ),
			'generated_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'site'             => [
				'name'         => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'url'          => home_url(),
				'wp_version'   => (string) $wp_version,
				'php_version'  => PHP_VERSION,
				'server'       => sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ) ),
				'active_theme' => wp_get_theme()->get( 'Name' ) . ' v' . wp_get_theme()->get( 'Version' ),
			],
			'score'            => $summary,
			'checklist'        => $items,
			'firewall'         => $fw_status,
			'scanner'          => [
				'last_scan'  => $last_scan,
				'new_issues' => $new_issues,
				'state'      => $scan_state,
				'signatures' => $sigs_info,
			],
			'hardening'        => $hardening,
			'smtp'             => array_merge( $smtp_info, [ 'recipient' => $alerts_to ] ),
			'proxy'            => $proxy_info,
			'stats_7d'         => $stats,
		];
	}
}
