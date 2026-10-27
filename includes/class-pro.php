<?php
/**
 * Pro lock helper functions.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minimum WPEPP Pro version this free version integrates with.
 *
 * Older pro copies keep working via the legacy fallback in
 * wpepp_has_pro_check(), but only pro builds at or above this constant get
 * module registration through the wpepp/pro/init bridge.
 */
define( 'WPEPP_REQUIRED_PRO_VERSION', '3.0.0' );

/**
 * Check if Pro features are unlocked.
 *
 * Three-layer contract:
 *   1. License flag option 'wpepp_has_pro' must be 'yes' (set by pro license flow).
 *   2. Pro plugin must be ACTIVE.
 *   3. Version handshake — pro build >= WPEPP_REQUIRED_PRO_VERSION gets the
 *      full module bridge; older pro copies fall back to legacy gating.
 *
 * @return bool
 */
if ( ! function_exists( 'wpepp_has_pro_check' ) ) {
	function wpepp_has_pro_check() {
		if ( 'yes' !== get_option( 'wpepp_has_pro', 'no' ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'wpepp-pro/wpepp-pro.php' ) ) {
			return false;
		}

		// New-style pro declares its version and passes the handshake.
		if ( defined( 'WPEPP_PRO_VERSION' ) ) {
			return version_compare( WPEPP_PRO_VERSION, WPEPP_REQUIRED_PRO_VERSION, '>=' );
		}

		// Legacy pro (pre-3.0) has no version constant — still honoured.
		return true;
	}
}

/**
 * Feature tier map — single source of truth for what is free vs pro.
 *
 * Modules and UI read tiers through this filter instead of hardcoding
 * capability checks, so retiering a feature is a one-line change here
 * (plus optional pro-side registration).
 *
 * @param string $feature Feature slug.
 * @return string 'free'|'pro'
 */
if ( ! function_exists( 'wpepp_feature_tier' ) ) {
	function wpepp_feature_tier( $feature ) {
		$tiers = [
			// Login security.
			'login_limiter'        => 'free',
			'honeypot'             => 'free',
			'two_factor_totp'      => 'free',
			'passkeys'             => 'pro',
			'recaptcha'            => 'pro',
			'custom_login_url'     => 'free',
			'login_log'            => 'pro',
			'auto_login_advanced'  => 'pro',
			'after_login_redirect' => 'pro',

			// Hardening / shielding.
			'username_shield'      => 'free',
			'security_headers'     => 'free',
			'ai_crawler_blocker'   => 'free',

			// Scanner.
			'integrity_scan'       => 'free',
			'malware_basic'        => 'free',
			'malware_full'         => 'pro',
			'file_watcher_realtime' => 'pro',
			'vuln_daily_feed'      => 'pro',

			// Firewall.
			'firewall_basic'       => 'free',
			'firewall_extended'    => 'pro',

			// Blocking.
			'ip_single_entries'    => 'free',
			'ip_cidr_import'       => 'pro',
			'country_block_2'      => 'free',
			'country_block_unlimited' => 'pro',
		];

		$tiers = apply_filters( 'wpepp/features/tiers', $tiers ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- documented public API.

		return isset( $tiers[ $feature ] ) ? $tiers[ $feature ] : 'pro';
	}
}

if ( ! function_exists( 'wpepp_get_available_conditions' ) ) {
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
}

if ( ! function_exists( 'wpepp_enforce_pro_settings' ) ) {
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
			// Country restriction — free tier capped at 2 countries.
			if ( isset( $settings['country_restriction_list'] ) && is_array( $settings['country_restriction_list'] ) ) {
				$codes = [];
				foreach ( $settings['country_restriction_list'] as $code ) {
					$code = strtoupper( trim( (string) $code ) );
					if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
						$codes[] = $code;
					}
				}
				$settings['country_restriction_list'] = array_slice( array_values( array_unique( $codes ) ), 0, 2 );
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
				$settings['login_log_enabled'],
				$settings['after_login_redirect'],
				$settings['reg_recaptcha_enabled'],
				$settings['reg_block_disposable_emails'],
				$settings['reg_email_domain_mode'],
				$settings['reg_email_domain_list'],
				$settings['reg_admin_approval'],
				$settings['ai_crawler_custom_ua'],

				// Unified CAPTCHA layer (Pro tier).
				$settings['captcha_enabled'],
				$settings['captcha_provider'],
				$settings['captcha_threshold'],
				$settings['turnstile_site_key'],
				$settings['turnstile_secret_key'],

				// Registration captcha (legacy + unified keys).
				$settings['reg_recaptcha_enabled'],
				$settings['reg_captcha_enabled'],

				// Passkeys + live traffic (Pro modules).
				$settings['passkeys_enabled'],
				$settings['live_traffic_enabled'],

				// Scanner & Anti-spam Pro extensions (v3.0).
				$settings['scan_schedule_enabled'],
				$settings['wc_disposable_email_block'],
				$settings['wc_captcha_enabled'],
				$settings['comment_captcha_enabled'],
				$settings['lostpassword_captcha_enabled'],
				$settings['auto_prune_logs_enabled'],
				$settings['events_retention_days'],
				$settings['login_log_retention_days'],
				$settings['slow_queries_retention_days'],
				$settings['max_log_rows_cap']
			);

			// Breached-password check is Pro; the rest of the policy is free.
			if ( isset( $settings['pw_breach_check'] ) ) {
				unset( $settings['pw_breach_check'] );
			}

			// IP lists: free tier allows single IPs only — strip CIDR ranges.
			foreach ( [ 'ip_blocklist', 'ip_allowlist' ] as $list_key ) {
				if ( isset( $settings[ $list_key ] ) && is_string( $settings[ $list_key ] ) && false !== strpos( $settings[ $list_key ], '/' ) ) {
					$kept = [];
					foreach ( array_filter( array_map( 'trim', preg_split( '/[\n\r,]+/', $settings[ $list_key ] ) ) ) as $entry ) {
						$entry = trim( preg_replace( '/#.*$/', '', $entry ) );
						if ( '' !== $entry && false === strpos( $entry, '/' ) && filter_var( $entry, FILTER_VALIDATE_IP ) ) {
							$kept[] = $entry;
						}
					}
					$settings[ $list_key ] = implode( ', ', array_unique( $kept ) );
				}
			}
			break;
	}

	return $settings;
	}
}

/**
 * Block Pro-only REST endpoints for Free users.
 *
 * @return true|\WP_Error
 */
if ( ! function_exists( 'wpepp_check_pro_permission' ) ) {
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
}
