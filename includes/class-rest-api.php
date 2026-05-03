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
			'permission_callback' => [ $this, 'check_edit_posts_pro_permission' ],
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
				'permission_callback' => [ $this, 'check_admin_pro_permission' ],
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

		// Two-Factor Authentication (Pro) — user management.
		register_rest_route( self::NAMESPACE, '/2fa/setup', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'setup_2fa' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/2fa/confirm', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'confirm_2fa' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/2fa/disable', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'disable_2fa' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/2fa/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_2fa_status' ],
			'permission_callback' => [ $this, 'check_admin_pro_permission' ],
		] );

		// Dashboard stats.
		register_rest_route( self::NAMESPACE, '/dashboard/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_dashboard_stats' ],
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

		return rest_ensure_response( json_decode( $raw, true ) ?: [] );
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

		$settings   = $this->sanitize_settings_object( $settings );
		$settings   = wpepp_enforce_pro_settings( $settings, $section );
		$option_key = $this->get_option_key( $section );

		update_option( $option_key, wp_json_encode( $settings ) );

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
			update_option( 'wpepp_' . $section . '_settings', wp_json_encode( $settings ) );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	// ── Content Lock ──

	/**
	 * Get all locked posts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_locked_posts( $request ) {
		$args = [
			'post_type'      => [ 'post', 'page' ],
			'posts_per_page' => 50,
			'meta_key'       => '_wpepp_content_lock_enabled',
			'meta_value'     => 'yes',
			'post_status'    => 'publish',
		];

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$post_type = sanitize_text_field( $request->get_param( 'post_type' ) ?? '' );
		if ( ! empty( $post_type ) && in_array( $post_type, [ 'post', 'page' ], true ) ) {
			$args['post_type'] = $post_type;
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
			update_post_meta( $post_id, '_wpepp_content_lock_redirect', esc_url_raw( $body['redirect'] ) );
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
			if ( get_post( $post_id ) ) {
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
		$args = [
			'post_type'      => [ 'post', 'page' ],
			'posts_per_page' => 50,
			'meta_key'       => '_wpepp_conditional_display_enable',
			'meta_value'     => 'yes',
			'post_status'    => 'publish',
		];

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
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

			$result[] = [
				'id'         => $link['id'],
				'label'      => $link['label'] ?? '',
				'user_id'    => (int) $link['user_id'],
				'user_name'  => $user_name,
				'use_count'  => (int) ( $link['use_count'] ?? 0 ),
				'max_uses'   => (int) ( $link['max_uses'] ?? 0 ),
				'expires'    => ! empty( $link['expires_at'] )
					? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $link['expires_at'] )
					: '',
				'role_restriction' => $link['role_restriction'] ?? '',
				'redirect_url'     => $link['redirect_url'] ?? '',
				'url'        => add_query_arg( 'wpepp_autologin', $link['token'], home_url( '/' ) ),
				'created_at' => $link['created_at'] ?? '',
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

		$label       = sanitize_text_field( $request->get_param( 'label' ) ?: __( 'Auto Login Link', 'wp-edit-password-protected' ) );
		$redirect_url = esc_url_raw( $request->get_param( 'redirect_url' ) ?: '' );
		$token       = wp_generate_password( 48, false );
		$id          = wp_generate_password( 12, false );

		$link = [
			'id'               => $id,
			'token'            => $token,
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
}
