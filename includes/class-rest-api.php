<?php
/**
 * REST API endpoints.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Rest_Api
 */
class WPEPP_Rest_Api {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'wpepp/v1';

	/**
	 * Allowed settings sections.
	 *
	 * @var array
	 */
	const ALLOWED_SECTIONS = [ 'login', 'register', 'password', 'lostpassword', 'security', 'general', 'member_template', 'site_access' ];

	/**
	 * Register all REST routes.
	 */
	public function register_routes() {
		// Settings — get all.
		register_rest_route( self::NAMESPACE, '/settings', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Settings — save all.
		register_rest_route( self::NAMESPACE, '/settings', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_all_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Settings Import (POST).
		register_rest_route( self::NAMESPACE, '/settings/import', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'import_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Settings — get by section.
		register_rest_route( self::NAMESPACE, '/settings/(?P<section>[a-z_-]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_section_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'section' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => [ $this, 'validate_section' ],
				],
			],
		] );

		// Settings — save by section.
		register_rest_route( self::NAMESPACE, '/settings/(?P<section>[a-z_-]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_section_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'section' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => [ $this, 'validate_section' ],
				],
			],
		] );

		// Settings — reset (delete) by section.
		register_rest_route( self::NAMESPACE, '/settings/(?P<section>[a-z_-]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'reset_section_settings' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'section' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => [ $this, 'validate_section' ],
				],
			],
		] );

		// Pro status.
		register_rest_route( self::NAMESPACE, '/pro/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_pro_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Templates.
		register_rest_route( self::NAMESPACE, '/templates', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_templates' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/templates/apply', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'apply_template' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/templates/export', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'export_template' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/templates/import', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'import_template' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		// Content Lock (Pro).
		register_rest_route( self::NAMESPACE, '/content-lock', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_locked_posts' ],
			'permission_callback' => [ $this, 'check_edit_posts_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/content-lock/bulk', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'bulk_lock_posts' ],
			'permission_callback' => [ $this, 'check_edit_others_pro_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/content-lock/(?P<post_id>\d+)', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_post_lock' ],
				'permission_callback' => [ $this, 'check_edit_posts_pro_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'toggle_post_lock' ],
				'permission_callback' => [ $this, 'check_edit_posts_pro_permission' ],
			],
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_post_lock' ],
				'permission_callback' => [ $this, 'check_edit_posts_pro_permission' ],
			],
		] );

		// Conditional Display.
		register_rest_route( self::NAMESPACE, '/conditional', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_conditional_posts' ],
			'permission_callback' => [ $this, 'check_edit_posts_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/conditional/(?P<post_id>\d+)', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_post_conditional' ],
				'permission_callback' => [ $this, 'check_edit_posts_permission' ],
			],
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_post_conditional' ],
				'permission_callback' => [ $this, 'check_edit_posts_permission' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/conditional/(?P<post_id>\d+)/toggle', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_conditional' ],
			'permission_callback' => [ $this, 'check_edit_posts_permission' ],
		] );

		// Security log (Pro).
		register_rest_route( self::NAMESPACE, '/security/log', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_login_log' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'clear_login_log' ],
				'permission_callback' => [ $this, 'check_admin_pro_permission' ],
			],
		] );

		// Preview URL.
		register_rest_route( self::NAMESPACE, '/preview/url', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_preview_url' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Auto Login Links.
		register_rest_route( self::NAMESPACE, '/auto-login', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_auto_login_links' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_auto_login_link' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/auto-login/(?P<id>[a-zA-Z0-9_-]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_auto_login_link' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// Two-Factor Authentication (FREE) — user management.
		register_rest_route( self::NAMESPACE, '/2fa/setup', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'setup_2fa' ],
			'permission_callback' => 'is_user_logged_in',
		] );

		register_rest_route( self::NAMESPACE, '/2fa/confirm', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'confirm_2fa' ],
			'permission_callback' => 'is_user_logged_in',
		] );

		register_rest_route( self::NAMESPACE, '/2fa/disable', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'disable_2fa' ],
			'permission_callback' => 'is_user_logged_in',
		] );

		register_rest_route( self::NAMESPACE, '/2fa/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_2fa_status' ],
			'permission_callback' => 'is_user_logged_in',
		] );

		// Dashboard stats.
		register_rest_route( self::NAMESPACE, '/dashboard/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_dashboard_stats' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Admin Theme (GET, POST).
		register_rest_route( self::NAMESPACE, '/theme', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_theme' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_theme' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
		] );

		// Block Center — list and create blocks.
		register_rest_route( self::NAMESPACE, '/blocks', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_blocks' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_block' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
			],
		] );

		// Block Center — delete single block.
		register_rest_route( self::NAMESPACE, '/blocks/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_block' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// Block Center — purge expired blocks.
		register_rest_route( self::NAMESPACE, '/blocks/purge-expired', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'purge_expired_blocks' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Firewall — status, toggle, test.
		register_rest_route( self::NAMESPACE, '/firewall/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_firewall_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/firewall/toggle', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_firewall' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/firewall/test', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'test_firewall' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Scanner — status, start, stop, tick.
		register_rest_route( self::NAMESPACE, '/scan/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_scan_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/scan/start', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'start_scan' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/scan/stop', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'stop_scan' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/scan/tick', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'tick_scan' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Scanner — issues list, clear, and action on single issue.
		register_rest_route( self::NAMESPACE, '/scan/issues', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_scan_issues' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/scan/issues/(?P<id>\d+)/(?P<action>[a-z_]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'act_on_scan_issue' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id'     => [
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				],
				'action' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/scan/issues/clear', [
			'methods'             => [ 'POST', 'DELETE' ],
			'callback'            => [ $this, 'clear_scan_issues' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/scan/issues/bulk', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'bulk_act_on_scan_issues' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'action' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/scan/signatures/sync', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'sync_signatures' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Alerts — status & test delivery.
		register_rest_route( self::NAMESPACE, '/alerts/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_alerts_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/alerts/test', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'test_alert_delivery' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Proxy & Cloudflare.
		register_rest_route( self::NAMESPACE, '/proxy/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_proxy_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/proxy/cloudflare-preset', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'apply_cloudflare_preset' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Hardening & Diagnostics.
		register_rest_route( self::NAMESPACE, '/hardening/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_hardening_status' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/hardening/toggle', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_hardening' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/diagnostics', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_diagnostics' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Security Score & Setup Wizard.
		register_rest_route( self::NAMESPACE, '/security/score', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_security_score' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/security/wizard-steps', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_security_wizard_steps' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/security/apply-preset', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'apply_security_preset' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/security/audit-report', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_security_audit_report' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Activity Events.
		register_rest_route( self::NAMESPACE, '/events', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_events' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/events/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_events_stats' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// Passkeys fallback when Pro is not active.
		if ( ! class_exists( 'WPEPP_Pro_Passkeys' ) ) {
			register_rest_route( self::NAMESPACE, '/passkeys', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_passkeys_fallback' ],
				'permission_callback' => 'is_user_logged_in',
			] );

			register_rest_route( self::NAMESPACE, '/passkeys/register/start', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'passkeys_pro_required' ],
				'permission_callback' => 'is_user_logged_in',
			] );

			register_rest_route( self::NAMESPACE, '/passkeys/register/finish', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'passkeys_pro_required' ],
				'permission_callback' => 'is_user_logged_in',
			] );

			register_rest_route( self::NAMESPACE, '/passkeys/(?P<id>[A-Za-z0-9_\-]+)', [
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'passkeys_pro_required' ],
				'permission_callback' => 'is_user_logged_in',
			] );
		}

		// Database & Log Maintenance.
		register_rest_route( self::NAMESPACE, '/database/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_database_stats' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/database/purge', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'purge_database_logs' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/database/purge-table', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'purge_database_table' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );
	}

	// ── Permission callbacks ──

	/**
	 * Check admin capability.
	 *
	 * @return true|\WP_Error
	 */
	public function check_admin_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'wpepp_rest_forbidden',
				__( 'You do not have permission to access this resource.', 'wp-edit-password-protected' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Check admin + pro permission.
	 *
	 * @return true|\WP_Error
	 */
	public function check_admin_pro_permission() {
		$admin_check = $this->check_admin_permission();
		if ( is_wp_error( $admin_check ) ) {
			return $admin_check;
		}
		return wpepp_check_pro_permission();
	}

	/**
	 * Check edit_posts capability.
	 *
	 * @return true|\WP_Error
	 */
	public function check_edit_posts_permission() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'wpepp_rest_forbidden',
				__( 'You do not have permission to access this resource.', 'wp-edit-password-protected' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Check edit_posts + pro permission.
	 *
	 * @return true|\WP_Error
	 */
	public function check_edit_posts_pro_permission() {
		$edit_check = $this->check_edit_posts_permission();
		if ( is_wp_error( $edit_check ) ) {
			return $edit_check;
		}
		return wpepp_check_pro_permission();
	}

	/**
	 * Check edit_others_posts + pro permission.
	 *
	 * @return true|\WP_Error
	 */
	public function check_edit_others_pro_permission() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error(
				'wpepp_rest_forbidden',
				__( 'Insufficient permissions.', 'wp-edit-password-protected' ),
				[ 'status' => 403 ]
			);
		}
		return wpepp_check_pro_permission();
	}

	/**
	 * Validate section parameter.
	 *
	 * @param string $value Section name.
	 * @return bool
	 */
	public function validate_section( $value ) {
		return in_array( $value, self::ALLOWED_SECTIONS, true );
	}

	// ── Settings callbacks ──

	/**
	 * Get all settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_all_settings() {
		$settings = [];
		foreach ( self::ALLOWED_SECTIONS as $section ) {
			$option_key           = 'wpepp_' . $section . '_settings';
			$raw                  = get_option( $option_key, '{}' );
			$settings[ $section ] = json_decode( $raw, true ) ?: [];
		}

		// Mask site password on read.
		if ( isset( $settings['site_access'] ) && is_array( $settings['site_access'] ) ) {
			$settings['site_access']['has_site_password'] = ! empty( $settings['site_access']['site_password'] );
			$settings['site_access']['site_password']     = '';
		}

		// Include member template separately.
		$settings['member_template'] = json_decode( get_option( 'wpepp_member_template', '{}' ), true ) ?: [];

		return rest_ensure_response( $settings );
	}

	/**
	 * Save all settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_all_settings( $request ) {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid data.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		foreach ( $body as $section => $settings ) {
			if ( ! in_array( $section, self::ALLOWED_SECTIONS, true ) ) {
				continue;
			}

			// Secure site password: hash immediately and preserve if untouched.
			if ( 'site_access' === $section && is_array( $settings ) ) {
				$existing_raw = get_option( 'wpepp_site_access_settings', '{}' );
				$existing_set = json_decode( (string) $existing_raw, true );
				$existing_pw  = ( is_array( $existing_set ) && ! empty( $existing_set['site_password'] ) ) ? (string) $existing_set['site_password'] : '';

				if ( ! isset( $settings['site_password'] ) || '' === trim( (string) $settings['site_password'] ) ) {
					if ( ! empty( $existing_pw ) ) {
						$settings['site_password'] = $existing_pw;
					}
				} else {
					$submitted_pw = (string) $settings['site_password'];
					if ( class_exists( 'WPEPP_Site_Access' ) && ! WPEPP_Site_Access::looks_hashed( $submitted_pw ) ) {
						$settings['site_password'] = wp_hash_password( $submitted_pw );
					}
				}
			}

			$settings   = $this->sanitize_settings_object( $settings );
			$settings   = wpepp_enforce_pro_settings( $settings, $section );
			$option_key = $this->get_option_key( $section );
			update_option( $option_key, wp_json_encode( $settings ) );
		}

		do_action( 'wpepp/settings/saved' );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Get settings for a specific section.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_section_settings( $request ) {
		$section    = $request->get_param( 'section' );
		$option_key = $this->get_option_key( $section );
		$raw        = get_option( $option_key, '{}' );
		$decoded    = json_decode( $raw, true ) ?: [];

		if ( 'site_access' === $section && is_array( $decoded ) ) {
			$decoded['has_site_password'] = ! empty( $decoded['site_password'] );
			$decoded['site_password']     = '';
		}

		return rest_ensure_response( $decoded );
	}

	/**
	 * Save settings for a specific section.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_section_settings( $request ) {
		$section  = $request->get_param( 'section' );
		$settings = $request->get_json_params();

		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid data.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		// Secure site password: hash immediately and preserve if untouched.
		if ( 'site_access' === $section ) {
			$existing_raw = get_option( 'wpepp_site_access_settings', '{}' );
			$existing_set = json_decode( (string) $existing_raw, true );
			$existing_pw  = ( is_array( $existing_set ) && ! empty( $existing_set['site_password'] ) ) ? (string) $existing_set['site_password'] : '';

			if ( ! isset( $settings['site_password'] ) || '' === trim( (string) $settings['site_password'] ) ) {
				if ( ! empty( $existing_pw ) ) {
					$settings['site_password'] = $existing_pw;
				}
			} else {
				$submitted_pw = (string) $settings['site_password'];
				if ( class_exists( 'WPEPP_Site_Access' ) && ! WPEPP_Site_Access::looks_hashed( $submitted_pw ) ) {
					$settings['site_password'] = wp_hash_password( $submitted_pw );
				}
			}
		}

		$option_key   = $this->get_option_key( $section );
		$existing_raw = get_option( $option_key, '{}' );
		$existing     = json_decode( (string) $existing_raw, true );
		if ( is_array( $existing ) ) {
			$settings = array_merge( $existing, $settings );
		}

		$settings   = $this->sanitize_settings_object( $settings );
		$settings   = wpepp_enforce_pro_settings( $settings, $section );

		update_option( $option_key, wp_json_encode( $settings ) );

		if ( 'security' === $section && class_exists( 'WPEPP_Scan_Engine' ) ) {
			WPEPP_Scan_Engine::schedule();
		}

		do_action( 'wpepp/settings/saved', $section );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Reset (delete) settings for a section — restores to defaults.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function reset_section_settings( $request ) {
		$section    = $request->get_param( 'section' );
		$option_key = $this->get_option_key( $section );

		delete_option( $option_key );

		do_action( 'wpepp/settings/reset', $section );

		return rest_ensure_response( [] );
	}

	/**
	 * Get the option key for a section.
	 *
	 * @param string $section Section name.
	 * @return string
	 */
	private function get_option_key( $section ) {
		if ( 'member_template' === $section ) {
			return 'wpepp_member_template';
		}
		return 'wpepp_' . $section . '_settings';
	}

	// ── Pro Status ──

	/**
	 * Get Pro status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_pro_status() {
		return rest_ensure_response( [ 'isPro' => wpepp_has_pro_check() ] );
	}

	// ── Templates ──

	/**
	 * Get available templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_templates() {
		$templates_dir = WPEPP_PATH . 'assets/templates/';
		$templates     = [];

		$free_templates = [ 'starter', 'modern-dark', 'gradient-wave' ];

		if ( is_dir( $templates_dir ) ) {
			$files = glob( $templates_dir . '*.json' );
			foreach ( $files as $file ) {
				$name        = basename( $file, '.json' );
				$templates[] = [
					'id'     => $name,
					'name'   => ucwords( str_replace( '-', ' ', $name ) ),
					'isFree' => in_array( $name, $free_templates, true ),
				];
			}
		}

		return rest_ensure_response( $templates );
	}

	/**
	 * Apply a template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function apply_template( $request ) {
		$template_id = sanitize_file_name( $request->get_param( 'template_id' ) );
		$file        = WPEPP_PATH . 'assets/templates/' . $template_id . '.json';

		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'not_found', __( 'Template not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}

		$free_templates = [ 'starter', 'modern-dark', 'gradient-wave' ];
		if ( ! in_array( $template_id, $free_templates, true ) && ! wpepp_has_pro_check() ) {
			return wpepp_check_pro_permission();
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$data    = json_decode( $content, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_template', __( 'Invalid template data.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$allowed_sections = [ 'login', 'register', 'password', 'lostpassword' ];

		// If a specific section is requested, only apply that section.
		$target_section = sanitize_key( $request->get_param( 'section' ) );
		if ( $target_section && in_array( $target_section, $allowed_sections, true ) ) {
			if ( isset( $data[ $target_section ] ) ) {
				$settings = $this->sanitize_settings_object( $data[ $target_section ] );
				$settings = wpepp_enforce_pro_settings( $settings, $target_section );
				update_option( 'wpepp_' . $target_section . '_settings', wp_json_encode( $settings ) );
			}
		} else {
			foreach ( $data as $section => $settings ) {
				if ( ! in_array( $section, $allowed_sections, true ) ) {
					continue;
				}
				$settings = $this->sanitize_settings_object( $settings );
				$settings = wpepp_enforce_pro_settings( $settings, $section );
				update_option( 'wpepp_' . $section . '_settings', wp_json_encode( $settings ) );
			}
		}

		update_option( 'wpepp_active_template', $template_id );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Export current settings (Pro).
	 *
	 * @return \WP_REST_Response
	 */
	public function export_template() {
		$export = [];
		foreach ( [ 'login', 'register', 'password', 'lostpassword' ] as $section ) {
			$raw                = get_option( 'wpepp_' . $section . '_settings', '{}' );
			$export[ $section ] = json_decode( $raw, true ) ?: [];
		}

		return rest_ensure_response( $export );
	}

	/**
	 * Import settings from JSON (Pro).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import_template( $request ) {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$allowed_sections = [ 'login', 'register', 'password', 'lostpassword' ];
		$body             = array_intersect_key( $body, array_flip( $allowed_sections ) );

		foreach ( $body as $section => $settings ) {
			$settings = $this->sanitize_settings_object( $settings );
			$settings = wpepp_enforce_pro_settings( $settings, $section );
			update_option( 'wpepp_' . $section . '_settings', wp_json_encode( $settings ) );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	// ── Content Lock ──

	/**
	 * Ensure the current user can edit the given post.
	 *
	 * The route permission_callback only checks the generic `edit_posts`
	 * capability; this enforces the per-post `edit_post` meta-capability so a
	 * user cannot mutate posts they do not own/cannot edit.
	 *
	 * @param int $post_id Post ID.
	 * @return true|\WP_Error
	 */
	private function require_edit_post( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not allowed to edit this post.', 'wp-edit-password-protected' ), [ 'status' => 403 ] );
		}
		return true;
	}

	/**
	 * Get all locked posts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_locked_posts( $request ) {
		if ( ! wpepp_has_pro_check() ) {
			return rest_ensure_response( [] );
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Filter locked posts by meta.
		$args = [
			'post_type'      => [ 'post', 'page' ],
			'posts_per_page' => 50,
			'meta_key'       => '_wpepp_content_lock_enabled',
			'meta_value'     => 'yes',
			'post_status'    => 'publish',
		];
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$post_type = sanitize_text_field( $request->get_param( 'post_type' ) ?? '' );
		if ( ! empty( $post_type ) && in_array( $post_type, [ 'post', 'page' ], true ) ) {
			$args['post_type'] = $post_type;
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$args['author'] = get_current_user_id();
		}

		$query = new WP_Query( $args );
		$posts = [];

		foreach ( $query->posts as $post ) {
			$posts[] = [
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'post_type' => $post->post_type,
				'date'      => $post->post_date,
				'locked'    => true,
				'message'   => get_post_meta( $post->ID, '_wpepp_content_lock_message', true ),
				'action'    => get_post_meta( $post->ID, '_wpepp_content_lock_action', true ),
			];
		}

		return rest_ensure_response( $posts );
	}

	/**
	 * Get lock settings for a single post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_post_lock( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$cap     = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}

		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}

		return rest_ensure_response( [
			'enabled'      => get_post_meta( $post_id, '_wpepp_content_lock_enabled', true ) === 'yes',
			'message'      => get_post_meta( $post_id, '_wpepp_content_lock_message', true ),
			'action'       => get_post_meta( $post_id, '_wpepp_content_lock_action', true ) ?: 'link',
			'redirect'      => get_post_meta( $post_id, '_wpepp_content_lock_redirect', true ),
			'show_excerpt'  => get_post_meta( $post_id, '_wpepp_content_lock_show_excerpt', true ) === 'yes',
			'excerpt_text'  => get_post_meta( $post_id, '_wpepp_content_lock_excerpt_text', true ),
		] );
	}

	/**
	 * Toggle lock on a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function toggle_post_lock( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$cap     = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}
		$body    = $request->get_json_params();

		if ( isset( $body['enabled'] ) ) {
			$new = rest_sanitize_boolean( $body['enabled'] ) ? 'yes' : 'no';
		} else {
			$current = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );
			$new     = ( 'yes' === $current ) ? 'no' : 'yes';
		}

		update_post_meta( $post_id, '_wpepp_content_lock_enabled', $new );

		return rest_ensure_response( [ 'enabled' => 'yes' === $new ] );
	}

	/**
	 * Update lock settings for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_post_lock( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$cap     = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}
		$body    = $request->get_json_params();

		if ( isset( $body['enabled'] ) ) {
			update_post_meta( $post_id, '_wpepp_content_lock_enabled', rest_sanitize_boolean( $body['enabled'] ) ? 'yes' : 'no' );
		}

		if ( isset( $body['message'] ) ) {
			update_post_meta( $post_id, '_wpepp_content_lock_message', wp_kses_post( $body['message'] ) );
		}

		if ( isset( $body['action'] ) ) {
			$action  = sanitize_text_field( $body['action'] );
			$allowed = [ 'form', 'link', 'popup', 'redirect' ];
			if ( in_array( $action, $allowed, true ) ) {
				update_post_meta( $post_id, '_wpepp_content_lock_action', $action );
			}
		}

		if ( isset( $body['redirect'] ) ) {
			$redirect_url = esc_url_raw( $body['redirect'] );
			if ( ! current_user_can( 'manage_options' ) && ! empty( $redirect_url ) ) {
				$redirect_url = wp_validate_redirect( $redirect_url, home_url( '/' ) );
			}
			update_post_meta( $post_id, '_wpepp_content_lock_redirect', $redirect_url );
		}

		if ( isset( $body['show_excerpt'] ) ) {
			update_post_meta( $post_id, '_wpepp_content_lock_show_excerpt', rest_sanitize_boolean( $body['show_excerpt'] ) ? 'yes' : 'no' );
		}

		if ( isset( $body['excerpt_text'] ) ) {
			update_post_meta( $post_id, '_wpepp_content_lock_excerpt_text', sanitize_textarea_field( $body['excerpt_text'] ) );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Bulk lock/unlock posts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function bulk_lock_posts( $request ) {
		$body   = $request->get_json_params();
		$ids    = isset( $body['ids'] ) ? array_map( 'absint', (array) $body['ids'] ) : [];
		$action = isset( $body['action'] ) ? sanitize_text_field( $body['action'] ) : '';

		if ( empty( $ids ) || ! in_array( $action, [ 'lock', 'unlock' ], true ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid data.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$value = ( 'lock' === $action ) ? 'yes' : 'no';

		foreach ( $ids as $post_id ) {
			if ( get_post( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
				update_post_meta( $post_id, '_wpepp_content_lock_enabled', $value );
			}
		}

		return rest_ensure_response( [ 'success' => true, 'count' => count( $ids ) ] );
	}

	// ── Conditional Display ──

	/**
	 * Get all posts with conditional display enabled.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_conditional_posts( $request ) {
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Filter conditional posts by meta.
		$args = [
			'post_type'      => [ 'post', 'page' ],
			'posts_per_page' => 50,
			'meta_key'       => '_wpepp_conditional_display_enable',
			'meta_value'     => 'yes',
			'post_status'    => 'publish',
		];
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$args['author'] = get_current_user_id();
		}

		$query = new WP_Query( $args );
		$posts = [];

		$condition_labels = [
			'user_logged_in'     => __( 'User is logged in', 'wp-edit-password-protected' ),
			'user_logged_out'    => __( 'User is logged out', 'wp-edit-password-protected' ),
			'user_role'          => __( 'User role', 'wp-edit-password-protected' ),
			'device_type'        => __( 'Device type', 'wp-edit-password-protected' ),
			'day_of_week'        => __( 'Day of week', 'wp-edit-password-protected' ),
			'time_of_day'        => __( 'Time of day', 'wp-edit-password-protected' ),
			'date_range'         => __( 'Date range', 'wp-edit-password-protected' ),
			'recurring_schedule' => __( 'Recurring schedule', 'wp-edit-password-protected' ),
			'post_type'          => __( 'Post type', 'wp-edit-password-protected' ),
			'browser_type'       => __( 'Browser type', 'wp-edit-password-protected' ),
			'url_parameter'      => __( 'URL parameter', 'wp-edit-password-protected' ),
			'referrer_source'    => __( 'Referrer source', 'wp-edit-password-protected' ),
		];

		foreach ( $query->posts as $post ) {
			$cond_key = get_post_meta( $post->ID, '_wpepp_conditional_display_condition', true );
			$posts[] = [
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'type'      => $post->post_type,
				'condition' => isset( $condition_labels[ $cond_key ] ) ? $condition_labels[ $cond_key ] : $cond_key,
				'action'    => get_post_meta( $post->ID, '_wpepp_conditional_action', true ) ?: 'show',
				'enabled'   => true,
				'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
			];
		}

		return rest_ensure_response( $posts );
	}

	/**
	 * Get conditional settings for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_post_conditional( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$cap     = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}

		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}

		return rest_ensure_response( [
			'enabled'   => get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) === 'yes',
			'condition' => get_post_meta( $post_id, '_wpepp_conditional_display_condition', true ),
			'action'    => get_post_meta( $post_id, '_wpepp_conditional_action', true ) ?: 'show',
		] );
	}

	/**
	 * Update conditional settings for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_post_conditional( $request ) {
		$post_id            = absint( $request->get_param( 'post_id' ) );
		$cap                = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}
		$body               = $request->get_json_params();
		$available          = wpepp_get_available_conditions();
		$available_keys     = array_keys( $available );

		if ( isset( $body['condition'] ) ) {
			$condition = sanitize_text_field( $body['condition'] );
			if ( in_array( $condition, $available_keys, true ) ) {
				update_post_meta( $post_id, '_wpepp_conditional_display_condition', $condition );
			}
		}

		if ( isset( $body['action'] ) ) {
			$action = sanitize_text_field( $body['action'] );
			if ( in_array( $action, [ 'show', 'hide' ], true ) ) {
				update_post_meta( $post_id, '_wpepp_conditional_action', $action );
			}
		}

		if ( isset( $body['enabled'] ) ) {
			update_post_meta( $post_id, '_wpepp_conditional_display_enable', rest_sanitize_boolean( $body['enabled'] ) ? 'yes' : 'no' );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Toggle conditional display on a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function toggle_conditional( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$cap     = $this->require_edit_post( $post_id );
		if ( is_wp_error( $cap ) ) {
			return $cap;
		}
		$body    = $request->get_json_params();

		if ( isset( $body['enabled'] ) ) {
			$new = rest_sanitize_boolean( $body['enabled'] ) ? 'yes' : 'no';
		} else {
			$current = get_post_meta( $post_id, '_wpepp_conditional_display_enable', true );
			$new     = ( 'yes' === $current ) ? 'no' : 'yes';
		}

		update_post_meta( $post_id, '_wpepp_conditional_display_enable', $new );

		return rest_ensure_response( [ 'enabled' => 'yes' === $new ] );
	}

	// ── Security Log ──

	/**
	 * Get login log entries.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_login_log( $request ) {
		if ( ! wpepp_has_pro_check() ) {
			return rest_ensure_response( [] );
		}

		global $wpdb;

		$table = $wpdb->prefix . 'wpepp_login_log';
		$limit = absint( $request->get_param( 'limit' ) ?? 100 );
		$limit = min( $limit, 500 );

		$cache_key = 'wpepp_login_log_' . $limit;
		$results   = wp_cache_get( $cache_key, 'wpepp' );

		if ( ! $this->login_log_table_exists( true ) ) {
			return rest_ensure_response( [] );
		}

		if ( false === $results ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					'SELECT id, user_login, ip_address, status, user_agent, created_at FROM %i ORDER BY created_at DESC LIMIT %d',
					$table,
					$limit
				)
			);
			wp_cache_set( $cache_key, $results, 'wpepp' );
		}

		return rest_ensure_response( $results ?: [] );
	}

	/**
	 * Clear login log.
	 *
	 * @return \WP_REST_Response
	 */
	public function clear_login_log() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpepp_login_log';

		if ( ! $this->login_log_table_exists( true ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		wp_cache_flush_group( 'wpepp' );

		return rest_ensure_response( [ 'success' => true ] );
	}

	// ── Dashboard Stats ──

	/**
	 * Get dashboard statistics.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_dashboard_stats() {
		global $wpdb;

		$cached = wp_cache_get( 'wpepp_dashboard_stats', 'wpepp' );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table  = $wpdb->prefix . 'wpepp_login_log';
		$thirty = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$today  = gmdate( 'Y-m-d 00:00:00' );

		// Login log counts (last 30 days).
		$login_success_30d = 0;
		$login_failed_30d  = 0;
		$logins_today      = 0;

		if ( $this->login_log_table_exists( true ) ) {
			$login_success_30d = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i WHERE status = 'success' AND created_at >= %s",
					$table,
					$thirty
				)
			);
			wp_cache_set( 'wpepp_success_30d', $login_success_30d, 'wpepp' );

			$login_failed_30d = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i WHERE status IN ('failed','lockout') AND created_at >= %s",
					$table,
					$thirty
				)
			);
			wp_cache_set( 'wpepp_failed_30d', $login_failed_30d, 'wpepp' );

			$logins_today = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE created_at >= %s',
					$table,
					$today
				)
			);
			wp_cache_set( 'wpepp_logins_today', $logins_today, 'wpepp' );
		}

		// AI Crawler blocked count.
		$ai_blocked = (int) get_option( 'wpepp_ai_crawler_blocked_count', 0 );

		// Locked posts count.
		$locked_posts = wp_cache_get( 'wpepp_locked_posts', 'wpepp' );
		if ( false === $locked_posts ) {
			$locked_posts = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT post_id) FROM %i WHERE meta_key = '_wpepp_content_lock_enabled' AND meta_value = 'yes'",
					$wpdb->postmeta
				)
			);
			wp_cache_set( 'wpepp_locked_posts', $locked_posts, 'wpepp' );
		}

		$stats = [
			'ai_bots_blocked'    => $ai_blocked,
			'login_success_30d'  => $login_success_30d,
			'login_failed_30d'   => $login_failed_30d,
			'logins_today'       => $logins_today,
			'locked_posts'       => $locked_posts,
		];

		wp_cache_set( 'wpepp_dashboard_stats', $stats, 'wpepp' );

		return rest_ensure_response( $stats );
	}

	/**
	 * Check whether the login log table exists, optionally creating plugin tables.
	 *
	 * @param bool $create Whether to try creating missing tables.
	 * @return bool
	 */
	private function login_log_table_exists( $create = false ) {
		global $wpdb;

		$table        = $wpdb->prefix . 'wpepp_login_log';
		$cache_key    = 'wpepp_login_log_exists';
		$table_exists = wp_cache_get( $cache_key, 'wpepp' );

		if ( false === $table_exists ) {
			$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			wp_cache_set( $cache_key, $table_exists ?: 'no', 'wpepp' );
		}

		if ( $table_exists && 'no' !== $table_exists ) {
			return true;
		}

		if ( ! $create || ! class_exists( 'WPEPP_Activator' ) ) {
			return false;
		}

		WPEPP_Activator::create_tables();
		wp_cache_delete( $cache_key, 'wpepp' );

		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
		wp_cache_set( $cache_key, $table_exists ?: 'no', 'wpepp' );

		return (bool) $table_exists;
	}

	// ── Preview ──

	/**
	 * Get preview URL with token.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_preview_url( $request ) {
		$type  = sanitize_text_field( $request->get_param( 'type' ) ?? 'login' );
		$token = wpepp_generate_preview_token();

		$url = add_query_arg( [
			'wpepp_preview' => '1',
			'type'          => $type,
			'wpepp_token'   => $token,
		], home_url( '/' ) );

		return rest_ensure_response( [ 'url' => $url ] );
	}

	// ── Sanitization helpers ──

	/**
	 * Recursively sanitize a settings object.
	 *
	 * @param mixed $data Input data.
	 * @return mixed Sanitized data.
	 */
	public function sanitize_settings_object( $data ) {
		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}

		if ( is_numeric( $data ) ) {
			return $data;
		}

		if ( is_bool( $data ) ) {
			return $data;
		}

		if ( is_array( $data ) ) {
			$sanitized = [];
			foreach ( $data as $key => $value ) {
				$safe_key = sanitize_text_field( $key );

				// Special handling for known HTML fields.
				if ( in_array( $safe_key, [ 'top_content', 'bottom_content', 'text', 'message', 'admin_only_message', 'site_password_message', 'custom_css' ], true ) ) {
					if ( 'custom_css' === $safe_key ) {
						$sanitized[ $safe_key ] = $this->sanitize_css( $value );
					} else {
						$sanitized[ $safe_key ] = wp_kses_post( $value );
					}
				} else {
					$sanitized[ $safe_key ] = $this->sanitize_settings_object( $value );
				}
			}
			return $sanitized;
		}

		return $data;
	}

	/**
	 * Sanitize CSS input.
	 *
	 * @param string $css Raw CSS.
	 * @return string Sanitized CSS.
	 */
	private function sanitize_css( $css ) {
		if ( empty( $css ) ) {
			return '';
		}

		$css = wp_strip_all_tags( $css );
		$css = preg_replace( '/expression\s*\(/i', '/* blocked */(', $css );
		$css = preg_replace( '/url\s*\(\s*["\']?\s*javascript:/i', 'url(/* blocked */', $css );
		$css = preg_replace( '/@import\s+url\s*\(/i', '/* @import blocked */(', $css );
		$css = preg_replace( '/behavior\s*:/i', '/* behavior blocked */:', $css );
		$css = preg_replace( '/-moz-binding\s*:/i', '/* binding blocked */:', $css );
		$css = preg_replace( '/-webkit-binding\s*:/i', '/* binding blocked */:', $css );

		return $css;
	}

	// ── Auto Login Links ──

	/**
	 * Get all auto-login links.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_auto_login_links() {
		$links = get_option( 'wpepp_auto_login_links', [] );
		if ( ! is_array( $links ) ) {
			$links = [];
		}

		$result = [];
		foreach ( $links as $link ) {
			$user      = get_user_by( 'id', (int) $link['user_id'] );
			$user_name = $user ? $user->display_name : __( 'Unknown', 'wp-edit-password-protected' );

			$token_str = '';
			if ( ! empty( $link['token_encrypted'] ) && class_exists( 'WPEPP_Security' ) ) {
				$token_str = WPEPP_Security::decrypt_token( $link['token_encrypted'] );
			} elseif ( ! empty( $link['token'] ) ) {
				$token_str = $link['token'];
			}

			$result[] = [
				'id'               => $link['id'],
				'label'            => $link['label'] ?? '',
				'user_id'          => (int) $link['user_id'],
				'user_name'        => $user_name,
				'use_count'        => (int) ( $link['use_count'] ?? 0 ),
				'max_uses'         => (int) ( $link['max_uses'] ?? 0 ),
				'expires'          => ! empty( $link['expires_at'] )
					? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $link['expires_at'] )
					: '',
				'role_restriction' => $link['role_restriction'] ?? '',
				'redirect_url'     => $link['redirect_url'] ?? '',
				'url'              => ! empty( $token_str ) ? add_query_arg( 'wpepp_autologin', $token_str, home_url( '/' ) ) : '',
				'created_at'       => $link['created_at'] ?? '',
			];
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Create a new auto-login link.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_auto_login_link( $request ) {
		$user_id = absint( $request->get_param( 'user_id' ) );
		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$label        = sanitize_text_field( $request->get_param( 'label' ) ?: __( 'Auto Login Link', 'wp-edit-password-protected' ) );
		$redirect_url = esc_url_raw( $request->get_param( 'redirect_url' ) ?: '' );
		$token        = wp_generate_password( 48, false );
		$id           = wp_generate_password( 12, false );
		$token_hash   = hash( 'sha256', $token );
		$token_enc    = class_exists( 'WPEPP_Security' ) ? WPEPP_Security::encrypt_token( $token ) : $token;

		$link = [
			'id'               => $id,
			'token_hash'       => $token_hash,
			'token_encrypted'  => $token_enc,
			'user_id'          => $user_id,
			'label'            => $label,
			'redirect_url'     => $redirect_url,
			'use_count'        => 0,
			'max_uses'         => 0,
			'expires_at'       => '',
			'role_restriction' => '',
			'created_at'       => gmdate( 'Y-m-d H:i:s' ),
		];

		// Pro conditions — only store if Pro is active.
		if ( wpepp_has_pro_check() ) {
			$expires_in = absint( $request->get_param( 'expires_in' ) ); // hours
			if ( $expires_in > 0 ) {
				$link['expires_at'] = time() + ( $expires_in * HOUR_IN_SECONDS );
			}

			$max_uses = absint( $request->get_param( 'max_uses' ) );
			if ( $max_uses > 0 ) {
				$link['max_uses'] = $max_uses;
			}

			$role = sanitize_text_field( $request->get_param( 'role_restriction' ) ?: '' );
			if ( $role && wp_roles()->is_role( $role ) ) {
				$link['role_restriction'] = $role;
			}
		}

		$links = get_option( 'wpepp_auto_login_links', [] );
		if ( ! is_array( $links ) ) {
			$links = [];
		}

		// Free version is capped at 3 active links.
		if ( ! wpepp_has_pro_check() && count( $links ) >= 3 ) {
			return new WP_Error(
				'wpepp_free_limit_reached',
				__( 'Free version is limited to 3 active auto-login links. Upgrade to Pro for unlimited links.', 'wp-edit-password-protected' ),
				[ 'status' => 403 ]
			);
		}

		array_unshift( $links, $link );
		update_option( 'wpepp_auto_login_links', $links, false );

		$user = get_user_by( 'id', $user_id );

		return rest_ensure_response( [
			'id'         => $id,
			'label'      => $label,
			'user_id'    => $user_id,
			'user_name'  => $user ? $user->display_name : '',
			'use_count'  => 0,
			'max_uses'   => $link['max_uses'],
			'expires'    => ! empty( $link['expires_at'] )
				? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $link['expires_at'] )
				: '',
			'role_restriction' => $link['role_restriction'],
			'redirect_url'     => $link['redirect_url'],
			'url'        => add_query_arg( 'wpepp_autologin', $token, home_url( '/' ) ),
			'created_at' => $link['created_at'],
		] );
	}

	/**
	 * Delete an auto-login link.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_auto_login_link( $request ) {
		$id    = sanitize_text_field( $request->get_param( 'id' ) );
		$links = get_option( 'wpepp_auto_login_links', [] );

		if ( ! is_array( $links ) ) {
			return new WP_Error( 'not_found', __( 'Link not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}

		$found = false;
		$links = array_values( array_filter( $links, function ( $link ) use ( $id, &$found ) {
			if ( $link['id'] === $id ) {
				$found = true;
				return false;
			}
			return true;
		} ) );

		if ( ! $found ) {
			return new WP_Error( 'not_found', __( 'Link not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
		}

		update_option( 'wpepp_auto_login_links', $links, false );

		return rest_ensure_response( [ 'success' => true ] );
	}

	// ── Two-Factor Authentication ──

	/**
	 * Start 2FA setup — generate a secret and return QR code data.
	 *
	 * @return \WP_REST_Response
	 */
	public function setup_2fa() {
		$user_id = get_current_user_id();
		$secret  = WPEPP_TOTP::generate_secret();

		update_user_meta( $user_id, '_wpepp_2fa_pending_secret', $secret );

		$user = wp_get_current_user();
		$uri  = WPEPP_TOTP::get_provisioning_uri( $secret, $user->user_email );

		return rest_ensure_response( [
			'secret'  => $secret,
			'qr_url'  => WPEPP_TOTP::get_qr_code_url( $uri ),
			'uri'     => $uri,
		] );
	}

	/**
	 * Confirm 2FA setup — verify the code and enable.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function confirm_2fa( $request ) {
		$user_id = get_current_user_id();
		$code    = sanitize_text_field( $request->get_param( 'code' ) ?? '' );
		$secret  = get_user_meta( $user_id, '_wpepp_2fa_pending_secret', true );

		if ( empty( $secret ) ) {
			return new WP_Error( 'no_pending', __( 'No pending 2FA setup. Please start setup first.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		if ( ! WPEPP_TOTP::verify_code( $secret, $code ) ) {
			return new WP_Error( 'invalid_code', __( 'Invalid verification code. Please try again.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$recovery_codes = WPEPP_TOTP::enable_for_user( $user_id, $secret );
		delete_user_meta( $user_id, '_wpepp_2fa_pending_secret' );

		return rest_ensure_response( [
			'success'        => true,
			'recovery_codes' => $recovery_codes,
		] );
	}

	/**
	 * Disable 2FA for the current user.
	 *
	 * @return \WP_REST_Response
	 */
	public function disable_2fa() {
		$user_id = get_current_user_id();
		WPEPP_TOTP::disable_for_user( $user_id );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Get 2FA status for the current user.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_2fa_status() {
		$user_id    = get_current_user_id();
		$is_enabled = WPEPP_TOTP::is_user_enabled( $user_id );

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		$user        = wp_get_current_user();
		$is_required = WPEPP_TOTP::is_required_for_user( $user, $settings );

		return rest_ensure_response( [
			'enabled'  => $is_enabled,
			'required' => $is_required,
		] );
	}

	// ── Admin Theme ──

	/**
	 * Get the admin theme preference.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_theme() {
		$theme = get_user_meta( get_current_user_id(), 'wpepp_admin_theme', true );
		if ( empty( $theme ) ) {
			$theme = get_option( 'wpepp_admin_theme', 'dark' );
		}
		return rest_ensure_response( [ 'theme' => $theme ?: 'dark' ] );
	}

	/**
	 * Save the admin theme preference.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_theme( $request ) {
		$theme = sanitize_text_field( (string) $request->get_param( 'theme' ) );
		if ( ! in_array( $theme, [ 'light', 'dark' ], true ) ) {
			$theme = 'dark';
		}
		update_user_meta( get_current_user_id(), 'wpepp_admin_theme', $theme );
		update_option( 'wpepp_admin_theme', $theme );
		return rest_ensure_response( [ 'success' => true, 'theme' => $theme ] );
	}

	// ── Settings Import ──

	/**
	 * Import settings payload.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import_settings( $request ) {
		$payload = $request->get_param( 'payload' );
		if ( is_string( $payload ) ) {
			$data = json_decode( $payload, true );
		} elseif ( is_array( $payload ) ) {
			$data = $payload;
		} else {
			$data = null;
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_payload', __( 'Invalid settings payload.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$imported = [];
		foreach ( self::ALLOWED_SECTIONS as $section ) {
			if ( isset( $data[ $section ] ) && is_array( $data[ $section ] ) ) {
				$sanitized  = $this->sanitize_settings_object( $data[ $section ] );
				$sanitized  = wpepp_enforce_pro_settings( $sanitized, $section );
				$option_key = $this->get_option_key( $section );
				update_option( $option_key, wp_json_encode( $sanitized ) );
				do_action( 'wpepp/settings/saved', $section );
				$imported[] = $section;
			}
		}

		if ( class_exists( 'WPEPP_Logger' ) && ! empty( $imported ) ) {
			WPEPP_Logger::log(
				'settings_changed',
				sprintf( 'Imported settings for: %s', implode( ', ', $imported ) ),
				WPEPP_Logger::SEV_INFO,
				[ 'sections' => implode( ', ', $imported ) ]
			);
		}

		return rest_ensure_response( [
			'success'  => true,
			'imported' => $imported,
		] );
	}

	// ── Block Center ──

	/**
	 * List blocked IPs and CIDR ranges.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_blocks( $request ) {
		if ( ! class_exists( 'WPEPP_Blocks' ) ) {
			return rest_ensure_response( [] );
		}
		$args = [
			'per_page'        => $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 50,
			'page'            => $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1,
			'type'            => $request->get_param( 'type' ) ? sanitize_key( $request->get_param( 'type' ) ) : '',
			'include_expired' => (bool) $request->get_param( 'include_expired' ),
		];
		$blocks = WPEPP_Blocks::list_blocks( $args );
		return rest_ensure_response( $blocks );
	}

	/**
	 * Add a manual block.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_block( $request ) {
		if ( ! class_exists( 'WPEPP_Blocks' ) ) {
			return new WP_Error( 'not_available', __( 'Block center is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$entry = sanitize_text_field( (string) ( $request->get_param( 'entry' ) ?? $request->get_param( 'ip' ) ?? '' ) );
		if ( '' === $entry ) {
			return new WP_Error( 'missing_entry', __( 'IP address or CIDR range is required.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}
		$expires  = $request->get_param( 'expires' );
		$duration = $request->get_param( 'duration' );
		if ( empty( $expires ) && ! empty( $duration ) && 'permanent' !== $duration ) {
			if ( '1h' === $duration ) {
				$expires = time() + HOUR_IN_SECONDS;
			} elseif ( '24h' === $duration ) {
				$expires = time() + DAY_IN_SECONDS;
			} elseif ( '7d' === $duration ) {
				$expires = time() + 7 * DAY_IN_SECONDS;
			}
		}
		$args = [
			'type'    => sanitize_key( (string) ( $request->get_param( 'type' ) ?? 'manual' ) ),
			'reason'  => sanitize_text_field( (string) ( $request->get_param( 'reason' ) ?? '' ) ),
			'expires' => ! empty( $expires ) ? ( is_numeric( $expires ) ? (int) $expires : strtotime( (string) $expires ) ) : null,
		];
		$id = WPEPP_Blocks::add_block( $entry, $args );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::log(
				'block_manual',
				sprintf( 'Manually blocked %s', $entry ),
				WPEPP_Logger::SEV_WARN,
				[ 'entry' => $entry, 'reason' => $args['reason'] ]
			);
		}

		return rest_ensure_response( [ 'success' => true, 'id' => $id ] );
	}

	/**
	 * Delete a block by ID.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_block( $request ) {
		if ( ! class_exists( 'WPEPP_Blocks' ) ) {
			return new WP_Error( 'not_available', __( 'Block center is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$id = absint( $request->get_param( 'id' ) );
		$ok = WPEPP_Blocks::remove_block( $id );
		return rest_ensure_response( [ 'success' => (bool) $ok ] );
	}

	/**
	 * Purge all expired temporary blocks.
	 *
	 * @return \WP_REST_Response
	 */
	public function purge_expired_blocks() {
		if ( ! class_exists( 'WPEPP_Blocks' ) ) {
			return rest_ensure_response( [ 'success' => false, 'purged' => 0 ] );
		}
		$purged = WPEPP_Blocks::purge_expired();
		return rest_ensure_response( [ 'success' => true, 'purged' => $purged ] );
	}

	// ── Firewall ──

	/**
	 * Get firewall status and statistics.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_firewall_status() {
		if ( ! class_exists( 'WPEPP_Firewall' ) ) {
			return rest_ensure_response( [
				'basic_enabled'    => false,
				'extended_enabled' => false,
				'learning_mode'    => false,
				'rule_count'       => 0,
				'blocked_total'    => 0,
				'recent_matches'   => [],
			] );
		}

		$settings       = WPEPP_Firewall::settings();
		$rules          = WPEPP_Firewall::get_rules();
		$recent_matches = get_transient( 'wpepp_fw_recent' );
		$blocked_total  = (int) get_option( WPEPP_Firewall::COUNTER_KEY, 0 );
		$bucket         = get_transient( 'wpepp_fw_total_bucket' );
		if ( is_array( $bucket ) && ! empty( $bucket['n'] ) ) {
			$blocked_total += (int) $bucket['n'];
		}

		return rest_ensure_response( [
			'basic_enabled'    => ! empty( $settings['basic_enabled'] ),
			'extended_enabled' => WPEPP_Firewall::extended_installed(),
			'learning_mode'    => ! empty( $settings['learning_mode'] ),
			'rule_count'       => count( $rules ),
			'blocked_total'    => $blocked_total,
			'recent_matches'   => is_array( $recent_matches ) ? $recent_matches : [],
		] );
	}

	/**
	 * Toggle a firewall mode setting.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_firewall( $request ) {
		if ( ! class_exists( 'WPEPP_Firewall' ) ) {
			return new WP_Error( 'not_available', __( 'Firewall is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		$setting = sanitize_key( (string) ( $request->get_param( 'setting' ) ?? '' ) );
		$value   = (bool) $request->get_param( 'value' );

		$raw = get_option( WPEPP_Firewall::OPT_SETTINGS, '{}' );
		$s   = json_decode( (string) $raw, true );
		if ( ! is_array( $s ) ) {
			$s = [];
		}

		if ( 'basic_enabled' === $setting ) {
			$s['basic_enabled'] = $value;
			update_option( WPEPP_Firewall::OPT_SETTINGS, wp_json_encode( $s ) );
		} elseif ( 'learning_mode' === $setting ) {
			$s['learning_mode'] = $value;
			update_option( WPEPP_Firewall::OPT_SETTINGS, wp_json_encode( $s ) );
			$runtime_file = WPEPP_Firewall::runtime_dir() . '/settings.json';
			if ( file_exists( $runtime_file ) ) {
				@file_put_contents( $runtime_file, wp_json_encode( [ 'learning_mode' => $value ] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		} elseif ( 'extended_enabled' === $setting ) {
			if ( $value && ! wpepp_has_pro_check() ) {
				return new WP_Error( 'wpepp_pro_required', __( 'Extended firewall protection requires WPEPP Pro.', 'wp-edit-password-protected' ), [ 'status' => 403 ] );
			}
			if ( $value ) {
				$res = WPEPP_Firewall::enable_extended();
				if ( is_wp_error( $res ) ) {
					return $res;
				}
				$s['extended_enabled'] = true;
			} else {
				WPEPP_Firewall::disable_extended();
				$s['extended_enabled'] = false;
			}
			update_option( WPEPP_Firewall::OPT_SETTINGS, wp_json_encode( $s ) );
		} else {
			return new WP_Error( 'invalid_setting', __( 'Invalid firewall setting.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		return rest_ensure_response( [
			'success'          => true,
			'extended_enabled' => WPEPP_Firewall::extended_installed(),
		] );
	}

	/**
	 * Test a request against the firewall rule set.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function test_firewall( $request ) {
		if ( ! wpepp_has_pro_check() ) {
			return new WP_Error( 'wpepp_pro_required', __( 'Live firewall testing requires WPEPP Pro.', 'wp-edit-password-protected' ), [ 'status' => 403 ] );
		}

		if ( ! class_exists( 'WPEPP_Firewall' ) || ! class_exists( 'WPEPP_FW_Engine' ) ) {
			return rest_ensure_response( [ 'matched' => null ] );
		}

		$url    = (string) ( $request->get_param( 'url' ) ?? '' );
		$method = strtoupper( sanitize_text_field( (string) ( $request->get_param( 'method' ) ?? 'GET' ) ) );
		$ua     = sanitize_text_field( (string) ( $request->get_param( 'ua' ) ?? '' ) );
		$body   = $request->get_param( 'body' );
		$body   = is_array( $body ) ? $body : [];

		$parsed_url = wp_parse_url( $url );
		$path       = $parsed_url['path'] ?? '/';
		$qs         = $parsed_url['query'] ?? '';
		$query      = [];
		if ( ! empty( $qs ) ) {
			wp_parse_str( $qs, $query );
		}

		$sim_request = [
			'method'       => $method,
			'path'         => $path,
			'query_string' => $qs,
			'query'        => $query,
			'body'         => $body,
			'ua'           => $ua,
			'referer'      => '',
			'ip'           => '127.0.0.1',
		];

		$matched = WPEPP_FW_Engine::inspect( WPEPP_Firewall::get_rules(), $sim_request );

		return rest_ensure_response( [
			'matched' => $matched ? [
				'id'       => $matched['id'] ?? '',
				'name'     => $matched['name'] ?? $matched['id'] ?? '',
				'severity' => $matched['severity'] ?? 1,
				'action'   => $matched['action'] ?? 'block',
				'_match'   => $matched['_match'] ?? '',
			] : null,
		] );
	}

	// ── Scanner ──

	/**
	 * Get scan status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_scan_status() {
		if ( ! class_exists( 'WPEPP_Scan_Engine' ) ) {
			return rest_ensure_response( [
				'running'     => false,
				'stage'       => '',
				'stage_index' => 0,
				'stage_total' => 4,
				'scanned'     => 0,
				'flagged'     => 0,
				'issues_new'  => 0,
				'last_scan'   => '',
				'scheduled'   => false,
			] );
		}

		$state       = WPEPP_Scan_Engine::state();
		$stages      = [ 'files' => 0, 'integrity' => 1, 'options' => 2, 'users' => 3 ];
		$current_st  = (string) ( $state['stage'] ?? '' );
		$stage_index = isset( $stages[ $current_st ] ) ? $stages[ $current_st ] : ( 'done' === $current_st ? 4 : 0 );
		$issues_new  = class_exists( 'WPEPP_Issues' ) ? WPEPP_Issues::count_new() : 0;
		$last_scan   = (string) get_option( 'wpepp_last_scan', '' );

		$sec_settings = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		$scheduled    = is_array( $sec_settings ) && ! empty( $sec_settings['scan_schedule_enabled'] );
		$sigs_info    = class_exists( 'WPEPP_Signatures' ) ? WPEPP_Signatures::get_sync_info() : [];

		return rest_ensure_response( [
			'running'     => ! empty( $state['running'] ),
			'profile'     => (string) ( $state['profile'] ?? 'quick' ),
			'stage'       => $current_st,
			'stage_index' => $stage_index,
			'stage_total' => 4,
			'scanned'     => (int) ( $state['scanned'] ?? 0 ),
			'flagged'     => max( (int) ( $state['flagged'] ?? 0 ), $issues_new ),
			'issues_new'  => $issues_new,
			'last_scan'   => $last_scan,
			'scheduled'   => $scheduled,
			'signatures'  => $sigs_info,
		] );
	}

	/**
	 * Start a malware scan.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function start_scan( $request ) {
		if ( ! class_exists( 'WPEPP_Scan_Engine' ) ) {
			return new WP_Error( 'not_available', __( 'Scanner is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$profile = sanitize_key( (string) ( $request->get_param( 'profile' ) ?? 'quick' ) );
		$res = WPEPP_Scan_Engine::start( $profile );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return $this->get_scan_status();
	}

	/**
	 * Stop a running scan.
	 *
	 * @return \WP_REST_Response
	 */
	public function stop_scan() {
		if ( class_exists( 'WPEPP_Scan_Engine' ) ) {
			WPEPP_Scan_Engine::stop();
		}
		return $this->get_scan_status();
	}

	/**
	 * Run one slice of the scan.
	 *
	 * @return \WP_REST_Response
	 */
	public function tick_scan() {
		if ( class_exists( 'WPEPP_Scan_Engine' ) ) {
			WPEPP_Scan_Engine::tick( true );
		}
		return $this->get_scan_status();
	}

	/**
	 * Sync malware signatures from remote repository.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function sync_signatures() {
		if ( ! wpepp_has_pro_check() ) {
			return new WP_Error( 'wpepp_pro_required', __( 'Cloud signature sync requires WPEPP Pro.', 'wp-edit-password-protected' ), [ 'status' => 403 ] );
		}

		if ( ! class_exists( 'WPEPP_Signatures' ) ) {
			return new WP_Error( 'not_available', __( 'Signatures engine unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$res = WPEPP_Signatures::sync_signatures( true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return rest_ensure_response( [
			'success' => true,
			'info'    => WPEPP_Signatures::get_sync_info(),
		] );
	}

	/**
	 * Get scan issues list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_scan_issues( $request ) {
		if ( ! class_exists( 'WPEPP_Issues' ) ) {
			return rest_ensure_response( [] );
		}
		$args = [
			'status'   => $request->get_param( 'status' ) ? sanitize_key( $request->get_param( 'status' ) ) : '',
			'per_page' => $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 100,
			'page'     => $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1,
		];
		$issues = WPEPP_Issues::list_issues( $args );
		return rest_ensure_response( $issues );
	}

	/**
	 * Act on a scan issue (resolve, ignore, unignore).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function act_on_scan_issue( $request ) {
		if ( ! class_exists( 'WPEPP_Issues' ) ) {
			return new WP_Error( 'not_available', __( 'Issues module is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$id     = absint( $request->get_param( 'id' ) );
		$action = sanitize_key( (string) ( $request->get_param( 'action' ) ?? '' ) );

		if ( 'delete_file' === $action ) {
			$res = WPEPP_Issues::delete_file( $id );
			return rest_ensure_response( $res );
		} elseif ( 'resolve' === $action || 'fixed' === $action ) {
			$ok = WPEPP_Issues::set_status( $id, 'fixed' );
		} elseif ( 'ignore' === $action || 'ignored' === $action ) {
			$ok = WPEPP_Issues::set_status( $id, 'ignored' );
		} elseif ( 'unignore' === $action || 'new' === $action ) {
			$ok = WPEPP_Issues::set_status( $id, 'new' );
		} else {
			return new WP_Error( 'invalid_action', __( 'Invalid issue action.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		return rest_ensure_response( [ 'success' => (bool) $ok ] );
	}

	/**
	 * Clear scan issues.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function clear_scan_issues( $request ) {
		if ( ! class_exists( 'WPEPP_Issues' ) ) {
			return rest_ensure_response( [ 'cleared' => 0 ] );
		}
		$only_handled = (bool) $request->get_param( 'only_handled' );
		$cleared      = WPEPP_Issues::clear( $only_handled );
		return rest_ensure_response( [ 'success' => true, 'cleared' => $cleared ] );
	}

	/**
	 * Bulk action on scan issues (resolve_all, ignore_all, clear_all).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function bulk_act_on_scan_issues( $request ) {
		if ( ! class_exists( 'WPEPP_Issues' ) ) {
			return rest_ensure_response( [ 'success' => false, 'affected' => 0 ] );
		}

		$action   = sanitize_key( (string) $request->get_param( 'action' ) );
		$affected = WPEPP_Issues::bulk_action( $action );

		return rest_ensure_response( [
			'success'  => true,
			'action'   => $action,
			'affected' => $affected,
		] );
	}

	// ── Hardening & Diagnostics ──

	/**
	 * Get file hardening status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_hardening_status() {
		if ( ! class_exists( 'WPEPP_File_Hardening' ) ) {
			return rest_ensure_response( [
				'tools' => [
					'uploads_php_blocked' => false,
					'dirlisting_disabled' => false,
					'file_edit_disabled'  => false,
					'is_apache'           => false,
					'nginx_snippet'       => '',
				],
			] );
		}

		return rest_ensure_response( [
			'tools' => WPEPP_File_Hardening::status(),
		] );
	}

	/**
	 * Toggle file hardening tool.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_hardening( $request ) {
		if ( ! class_exists( 'WPEPP_File_Hardening' ) ) {
			return new WP_Error( 'not_available', __( 'File hardening is unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		$tool  = sanitize_key( (string) ( $request->get_param( 'tool' ) ?? '' ) );
		$value = (bool) $request->get_param( 'value' );

		$result = null;
		if ( 'uploads_php' === $tool ) {
			$result = WPEPP_File_Hardening::toggle_uploads_php( $value );
		} elseif ( 'dirlist' === $tool ) {
			$result = WPEPP_File_Hardening::toggle_directory_listing( $value );
		} elseif ( 'file_edit' === $tool ) {
			$result = WPEPP_File_Hardening::toggle_file_edit( $value );
		} else {
			return new WP_Error( 'invalid_tool', __( 'Invalid hardening tool.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$manual  = is_array( $result ) && ! empty( $result['nginx'] );
		$snippet = $manual ? $result['nginx'] : '';

		return rest_ensure_response( [
			'success' => true,
			'manual'  => $manual,
			'snippet' => $snippet,
			'tools'   => WPEPP_File_Hardening::status(),
		] );
	}

	/**
	 * Run diagnostic health checks.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_diagnostics() {
		if ( ! class_exists( 'WPEPP_Diagnostics' ) ) {
			return rest_ensure_response( [] );
		}
		return rest_ensure_response( WPEPP_Diagnostics::run() );
	}

	// ── Security Score & Wizard ──

	/**
	 * Get security score summary.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_security_score() {
		if ( ! class_exists( 'WPEPP_Security_Score' ) ) {
			return rest_ensure_response( [
				'score'  => 0,
				'grade'  => 'F',
				'passed' => 0,
				'total'  => 0,
				'max'    => 0,
				'earned' => 0,
				'items'  => [],
			] );
		}
		return rest_ensure_response( WPEPP_Security_Score::get_summary() );
	}

	/**
	 * Get security setup wizard steps.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_security_wizard_steps() {
		if ( ! class_exists( 'WPEPP_Security_Score' ) ) {
			return rest_ensure_response( [] );
		}
		$steps = [];
		foreach ( WPEPP_Security_Score::wizard_presets() as $key => $preset ) {
			$steps[] = [
				'step'  => $key,
				'label' => $preset['label'],
				'desc'  => $preset['desc'] ?? '',
			];
		}
		return rest_ensure_response( $steps );
	}

	/**
	 * Apply a security preset step.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function apply_security_preset( $request ) {
		if ( ! class_exists( 'WPEPP_Security_Score' ) ) {
			return new WP_Error( 'not_available', __( 'Security score module unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}
		$step = sanitize_key( (string) ( $request->get_param( 'step' ) ?? '' ) );
		$res  = WPEPP_Security_Score::apply_wizard_step( $step );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return rest_ensure_response( [
			'success' => true,
			'summary' => WPEPP_Security_Score::get_summary(),
		] );
	}

	/**
	 * Get 1-click Security & Health Audit Report.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_security_audit_report() {
		if ( ! class_exists( 'WPEPP_Security_Score' ) ) {
			return rest_ensure_response( [] );
		}
		return rest_ensure_response( WPEPP_Security_Score::generate_audit_report() );
	}

	/**
	 * Get email alerts & SMTP delivery status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_alerts_status() {
		$smtp = class_exists( 'WPEPP_Alerts' ) ? WPEPP_Alerts::detect_smtp() : [
			'configured' => false,
			'provider'   => 'Unknown',
			'details'    => '',
		];

		$sec = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		$to  = is_array( $sec ) && ! empty( $sec['alert_email'] ) ? sanitize_email( $sec['alert_email'] ) : get_option( 'admin_email' );

		return rest_ensure_response( [
			'smtp'      => $smtp,
			'recipient' => $to,
			'enabled'   => ! isset( $sec['alerts_enabled'] ) || ! empty( $sec['alerts_enabled'] ),
		] );
	}

	/**
	 * Test sending a security alert email.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function test_alert_delivery( $request ) {
		if ( ! class_exists( 'WPEPP_Alerts' ) ) {
			return new \WP_Error( 'not_available', __( 'Alerts system unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		$to  = sanitize_email( (string) ( $request->get_param( 'to' ) ?? '' ) );
		$res = WPEPP_Alerts::send_test_alert( $to );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return rest_ensure_response( $res );
	}

	/**
	 * Get proxy and Cloudflare status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_proxy_status() {
		if ( ! class_exists( 'WPEPP_Core_Ip' ) ) {
			return rest_ensure_response( [] );
		}
		return rest_ensure_response( WPEPP_Core_Ip::inspect_proxy_status() );
	}

	/**
	 * Apply official Cloudflare IP ranges to trusted proxies setting.
	 *
	 * @return \WP_REST_Response
	 */
	public function apply_cloudflare_preset() {
		if ( ! class_exists( 'WPEPP_Core_Ip' ) ) {
			return new \WP_Error( 'not_available', __( 'IP service unavailable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( (string) $raw, true );
		$settings = is_array( $settings ) ? $settings : [];

		$current        = (string) ( $settings['trusted_proxies'] ?? '' );
		$lines          = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $current ) ) );
		$existing_lower = array_map( 'strtolower', $lines );

		$cf_cidrs = WPEPP_Core_Ip::get_cloudflare_cidrs();
		$added    = 0;

		foreach ( $cf_cidrs as $cidr ) {
			if ( ! in_array( strtolower( $cidr ), $existing_lower, true ) ) {
				$lines[] = $cidr;
				$added++;
			}
		}

		$settings['trusted_proxies'] = implode( "\n", $lines );
		update_option( 'wpepp_security_settings', wp_json_encode( $settings ) );
		do_action( 'wpepp/settings/saved', 'security' );

		return rest_ensure_response( [
			'success' => true,
			'added'   => $added,
			'total'   => count( $lines ),
			'proxy'   => WPEPP_Core_Ip::inspect_proxy_status(),
		] );
	}

	// ── Activity Events ──

	/**
	 * Get activity events stream.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_events( $request ) {
		if ( ! class_exists( 'WPEPP_Logger' ) ) {
			return rest_ensure_response( [] );
		}
		$args = [
			'per_page'     => $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 60,
			'page'         => $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1,
			'severity_min' => $request->get_param( 'severity_min' ) ? absint( $request->get_param( 'severity_min' ) ) : 0,
		];
		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			$args['types'] = [ sanitize_key( $type ) ];
		}
		$events = WPEPP_Logger::query( $args );
		return rest_ensure_response( $events );
	}

	/**
	 * Get event statistics over time window.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_events_stats( $request ) {
		if ( ! class_exists( 'WPEPP_Logger' ) ) {
			return rest_ensure_response( [ 'total' => 0, 'by_type' => [], 'by_day' => [] ] );
		}
		$days = $request->get_param( 'days' ) ? absint( $request->get_param( 'days' ) ) : 7;
		return rest_ensure_response( WPEPP_Logger::stats( $days ) );
	}

	// ── Passkeys Fallback ──

	/**
	 * Passkeys list fallback when Pro is inactive.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_passkeys_fallback() {
		return rest_ensure_response( [] );
	}

	/**
	 * Passkeys action fallback when Pro is inactive.
	 *
	 * @return \WP_Error
	 */
	public function passkeys_pro_required() {
		return new WP_Error(
			'wpepp_pro_required',
			__( 'Passkeys are a Pro feature. Please upgrade to WPEPP Pro to use passkeys.', 'wp-edit-password-protected' ),
			[ 'status' => 403 ]
		);
	}

	// ── Database & Log Maintenance ──

	/**
	 * Get database and log statistics.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_database_stats() {
		if ( ! class_exists( 'WPEPP_DB_Cleaner' ) ) {
			return rest_ensure_response( [ 'tables' => [], 'total_rows' => 0, 'total_size' => '0 KB' ] );
		}
		return rest_ensure_response( WPEPP_DB_Cleaner::get_stats() );
	}

	/**
	 * Trigger automatic pruning of all old logs based on retention settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function purge_database_logs() {
		if ( ! class_exists( 'WPEPP_DB_Cleaner' ) ) {
			return rest_ensure_response( [ 'total' => 0 ] );
		}
		$result = WPEPP_DB_Cleaner::purge_all_old_logs();
		$stats  = WPEPP_DB_Cleaner::get_stats();
		return rest_ensure_response( [
			'purged' => $result,
			'stats'  => $stats,
		] );
	}

	/**
	 * Purge or truncate a single table.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function purge_database_table( $request ) {
		if ( ! class_exists( 'WPEPP_DB_Cleaner' ) ) {
			return rest_ensure_response( [ 'purged_rows' => 0 ] );
		}
		$table = sanitize_key( (string) $request->get_param( 'table' ) );
		$all   = (bool) $request->get_param( 'all' );
		$count = WPEPP_DB_Cleaner::purge_table( $table, $all );
		$stats = WPEPP_DB_Cleaner::get_stats();
		return rest_ensure_response( [
			'purged_rows' => $count,
			'stats'       => $stats,
		] );
	}
}

