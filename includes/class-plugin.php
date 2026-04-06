<?php
/**
 * Main plugin class — singleton that boots all modules.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Plugin
 */
final class WPEPP_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — load files and register hooks.
	 */
	private function __construct() {
		$this->load_includes();

		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'save_install_date' ] );
		add_action( 'init', [ $this, 'register_post_meta_fields' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_meta_box_assets' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_panels' ] );
			add_action( 'admin_init', [ $this, 'register_post_list_columns' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( WPEPP_FILE ), [ $this, 'plugin_action_links' ] );
		}

		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_password_form_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_content_lock_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_conditional_script' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'enqueue_login_styles' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'enqueue_global_custom_css' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_global_custom_css' ] );
		add_filter( 'login_headerurl', [ $this, 'custom_login_logo_url' ] );
		add_filter( 'login_message', [ $this, 'add_login_heading' ] );
		add_action( 'login_footer', [ $this, 'add_login_video_background' ] );

		// Content filters.
		add_filter( 'the_password_form', [ $this, 'custom_password_form' ] );
		add_filter( 'the_content', [ $this, 'maybe_lock_content' ], 99 );
		add_filter( 'get_the_excerpt', [ $this, 'maybe_lock_excerpt' ], 99, 2 );

		// Hide locked posts (without excerpt) from blog/archive queries.
		add_action( 'pre_get_posts', [ $this, 'exclude_locked_posts_from_archive' ] );

		// Content lock redirect (must fire before output).
		add_action( 'template_redirect', [ $this, 'maybe_redirect_locked_content' ] );

		// Conditional display.
		add_filter( 'the_content', [ $this, 'conditional_display_filter' ], 98 );
		add_filter( 'the_title', [ $this, 'conditional_display_title_filter' ], 98, 2 );
		add_filter( 'post_thumbnail_html', [ $this, 'conditional_display_thumbnail_filter' ], 98, 5 );
		add_filter( 'post_class', [ $this, 'conditional_display_post_class' ], 10, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, 'conditional_display_hide_meta_css' ] );
		add_filter( 'comments_open', [ $this, 'conditional_display_comments_filter' ], 98, 2 );
		add_filter( 'get_comments_number', [ $this, 'conditional_display_comments_number_filter' ], 98, 2 );

		// REST API content protection.
		add_filter( 'rest_prepare_post', [ $this, 'protect_rest_content' ], 10, 3 );
		add_filter( 'rest_prepare_page', [ $this, 'protect_rest_content' ], 10, 3 );

		// Login fail redirect.
		add_action( 'wp_login_failed', [ $this, 'login_fail_redirect' ] );

		// Security features.
		$this->init_security();

		// Site Access — Admin Only & Site Password.
		new WPEPP_Site_Access();

		// Member-only page template.
		new WPEPP_Member_Template();

		// Documentation page.
		new WPEPP_Docs();

		// Admin notices (menu badge + Pro upgrade banner).
		if ( is_admin() ) {
			new WPEPP_Notice();
		}

		// Meta boxes.
		new WPEPP_Content_Lock();
		new WPEPP_Conditional_Meta();

		// Preview handler.
		add_action( 'template_redirect', [ $this, 'handle_preview' ] );
		add_action( 'wp_ajax_wpepp_preview', [ $this, 'ajax_preview' ] );

		// Appsero tracker.
		$this->init_appsero();
	}

	/**
	 * Load include files.
	 */
	private function load_includes() {
		$includes = [
			'class-admin.php',
			'class-notice.php',
			'class-docs.php',
			'class-rest-api.php',
			'class-password-customizer.php',
			'class-frontend.php',
			'class-login-customizer.php',
			'class-content-lock.php',
			'class-conditional-meta.php',
			'class-conditional-meta-helper.php',
			'class-totp.php',
			'class-security.php',
			'class-member-template.php',
			'class-site-access.php',
		];

		foreach ( $includes as $file ) {
			require_once WPEPP_PATH . 'includes/' . $file;
		}
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-edit-password-protected', false, dirname( plugin_basename( WPEPP_FILE ) ) . '/languages' );
	}

	/**
	 * Save install date on first run.
	 */
	public function save_install_date() {
		if ( ! get_option( 'wpepp_install_date' ) ) {
			update_option( 'wpepp_install_date', current_time( 'mysql' ) );
		}
	}

	/**
	 * Register admin menu page.
	 */
	public function add_admin_menu() {
		$icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2">'
			. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
			. '</svg>';

		add_menu_page(
			__( 'WPEPP', 'wp-edit-password-protected' ),
			__( 'WPEPP', 'wp-edit-password-protected' ),
			'manage_options',
			'wpepp-settings',
			[ $this, 'render_admin_page' ],
			'data:image/svg+xml;base64,' . base64_encode( $icon_svg ),
			30
		);
	}

	/**
	 * Render the admin page container for the React app.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'wp-edit-password-protected' ) );
		}
		echo '<div id="wpepp-admin-root" class="wpepp-admin-wrap"></div>';
	}

	/**
	 * Add Settings and Upgrade links on the Plugins page.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ),
			esc_html__( 'Settings', 'wp-edit-password-protected' )
		);

		$docs_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wpepp-docs' ) ),
			esc_html__( 'Docs', 'wp-edit-password-protected' )
		);

		array_unshift( $links, $docs_link );
		array_unshift( $links, $settings_link );

		if ( ! wpepp_has_pro_check() ) {
			$pro_link = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#6366f1;font-weight:600;">%s</a>',
				esc_url( 'https://wpthemespace.com/product/wpepp-login-security-password-protect-login-page-customizer/#pricing' ),
				esc_html__( 'Upgrade Now', 'wp-edit-password-protected' )
			);
			$links[] = $pro_link;
		}

		return $links;
	}

	/**
	 * Enqueue React admin app — ONLY on our plugin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_wpepp-settings' !== $hook ) {
			return;
		}

		$asset_file = WPEPP_PATH . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'wpepp-admin',
			WPEPP_URL . '/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'wpepp-admin',
			WPEPP_URL . '/build/index.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_localize_script( 'wpepp-admin', 'wpeppData', [
			'restUrl'      => esc_url_raw( rest_url( 'wpepp/v1/' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'previewNonce' => wp_create_nonce( 'wpepp_preview_nonce' ),
			'adminUrl'     => esc_url( admin_url() ),
			'loginUrl'     => esc_url( wp_login_url() ),
			'pluginUrl'    => esc_url( WPEPP_URL ),
			'homeUrl'      => esc_url( home_url( '/' ) ),
			'version'      => WPEPP_VERSION,
			'isPro'        => wpepp_has_pro_check(),
			'proUrl'       => esc_url( 'https://wpthemespace.com/product/wpepp-login-security-password-protect-login-page-customizer/#pricing' ),
			'clientIp'     => WPEPP_Security::get_client_ip(),
		] );

		// Media uploader for image uploads.
		wp_enqueue_media();
	}

	/**
	 * Enqueue meta box assets — ONLY on post editor screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_meta_box_assets( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-select2',
			WPEPP_URL . '/assets/css/select2.min.css',
			[],
			WPEPP_VERSION
		);

		wp_enqueue_style(
			'wpepp-meta-box',
			WPEPP_URL . '/assets/css/meta-box.css',
			[ 'wpepp-select2' ],
			WPEPP_VERSION
		);

		wp_enqueue_script(
			'wpepp-select2',
			WPEPP_URL . '/assets/js/select2.min.js',
			[ 'jquery' ],
			WPEPP_VERSION,
			true
		);

		wp_enqueue_script(
			'wpepp-meta-box',
			WPEPP_URL . '/assets/js/meta-box.js',
			[ 'jquery', 'wpepp-select2' ],
			WPEPP_VERSION,
			true
		);
	}

	/**
	 * Register post meta for REST API so block editor panels can read/write them.
	 */
	public function register_post_meta_fields() {
		$string_meta = [
			'_wpepp_content_lock_enabled',
			'_wpepp_content_lock_action',
			'_wpepp_content_lock_header',
			'_wpepp_content_lock_redirect',
			'_wpepp_content_lock_expiry',
			'_wpepp_content_lock_show_excerpt',
			'_wpepp_content_lock_excerpt_text',
			'_wpepp_conditional_display_enable',
			'_wpepp_conditional_display_condition',
			'_wpepp_conditional_action',
			'_wpepp_conditional_control_title',
			'_wpepp_conditional_control_featured_image',
			'_wpepp_conditional_control_comments',
			'_wpepp_conditional_notice_enable',
			'_wpepp_conditional_notice_text',
			'_wpepp_conditional_device_type',
			'_wpepp_conditional_time_start',
			'_wpepp_conditional_time_end',
			'_wpepp_conditional_date_start',
			'_wpepp_conditional_date_end',
			'_wpepp_conditional_recurring_time_start',
			'_wpepp_conditional_recurring_time_end',
			'_wpepp_conditional_url_parameter_key',
			'_wpepp_conditional_url_parameter_value',
			'_wpepp_conditional_referrer_source',
		];

		foreach ( $string_meta as $key ) {
			register_post_meta( '', $key, [
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}

		// Message fields allow HTML.
		register_post_meta( '', '_wpepp_content_lock_message', [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		] );

		// Array meta fields.
		$array_meta = [
			'_wpepp_content_lock_roles',
			'_wpepp_conditional_user_role',
			'_wpepp_conditional_day_of_week',
			'_wpepp_conditional_recurring_days',
			'_wpepp_conditional_post_type',
			'_wpepp_conditional_browser_type',
		];

		foreach ( $array_meta as $key ) {
			register_post_meta( '', $key, [
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
				],
				'single'            => true,
				'type'              => 'array',
				'default'           => [],
				'sanitize_callback' => function ( $value ) {
					return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : [];
				},
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}
	}

	/**
	 * Enqueue block editor sidebar panels for Content Lock & Conditional Display.
	 */
	public function enqueue_editor_panels() {
		wp_enqueue_script(
			'wpepp-editor-panels',
			WPEPP_URL . '/assets/js/editor-panels.js',
			[ 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-data', 'wp-core-data', 'wp-components', 'wp-element', 'wp-i18n', 'wp-dom-ready' ],
			WPEPP_VERSION,
			true
		);

		$roles = [];
		foreach ( wp_roles()->get_names() as $slug => $name ) {
			$roles[] = [ 'value' => $slug, 'label' => translate_user_role( $name ) ];
		}

		$post_types = [];
		foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) {
			$post_types[] = [ 'value' => $pt->name, 'label' => $pt->labels->singular_name ];
		}

		wp_localize_script( 'wpepp-editor-panels', 'wpeppEditorData', [
			'isPro'     => wpepp_has_pro_check(),
			'roles'     => $roles,
			'postTypes' => $post_types,
		] );

		// Style the FormTokenField multiselect in the sidebar.
		wp_add_inline_style( 'wp-components', '
			.wpepp-multi-select .components-form-token-field__input-container {
				border: 1px solid #757575;
				border-radius: 4px;
				padding: 4px;
				min-height: 36px;
				flex-wrap: wrap;
			}
			.wpepp-multi-select .components-form-token-field__token {
				background: #2271b1 !important;
				color: #fff !important;
				border-radius: 12px !important;
				padding: 2px 8px !important;
				margin: 3px !important;
				font-size: 12px;
				display: inline-flex;
				align-items: center;
				gap: 2px;
				border: none !important;
				box-shadow: none !important;
			}
			.wpepp-multi-select .components-form-token-field__token-text {
				color: #fff !important;
				background: transparent !important;
				border: none !important;
				padding: 0 !important;
			}
			.wpepp-multi-select .components-form-token-field__remove-token {
				color: #fff !important;
				fill: #fff !important;
				background: transparent !important;
				border: none !important;
				box-shadow: none !important;
				min-width: 18px !important;
				min-height: 18px !important;
				padding: 0 !important;
			}
			.wpepp-multi-select .components-form-token-field__remove-token svg {
				fill: #fff !important;
				width: 16px;
				height: 16px;
			}
			.wpepp-multi-select .components-form-token-field__suggestions-list {
				border: 1px solid #757575;
				border-top: 0;
				border-radius: 0 0 4px 4px;
				max-height: 180px;
				overflow-y: auto;
			}
			.wpepp-multi-select .components-form-token-field__suggestion {
				padding: 6px 8px;
				font-size: 12px;
				cursor: pointer;
			}
			.wpepp-multi-select .components-form-token-field__label {
				display: none !important;
			}
		' );
	}

	/**
	 * Register post list columns for all public post types.
	 */
	public function register_post_list_columns() {
		$post_types = get_post_types( [ 'public' => true ] );

		foreach ( $post_types as $pt ) {
			add_filter( "manage_{$pt}_posts_columns", [ $this, 'add_post_list_columns' ] );
			add_action( "manage_{$pt}_posts_custom_column", [ $this, 'render_post_list_column' ], 10, 2 );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'post_list_column_css' ] );
	}

	/**
	 * Inline CSS for the Protection column badges.
	 */
	public function post_list_column_css() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}
		$css = '.column-wpepp_status{width:160px}'
			. '.wpepp-badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;line-height:1.6;white-space:nowrap;margin:1px 0}'
			. '.wpepp-badge-password{background:#fce4ec;color:#c62828}'
			. '.wpepp-badge-lock{background:#e3f2fd;color:#1565c0}'
			. '.wpepp-badge-cond{background:#f3e5f5;color:#7b1fa2}'
			. '.wpepp-badge-none{color:#999}';
		wp_register_style( 'wpepp-post-list-columns', false, [], defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0' );
		wp_enqueue_style( 'wpepp-post-list-columns' );
		wp_add_inline_style( 'wpepp-post-list-columns', $css );
	}

	/**
	 * Add custom columns to the post list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_post_list_columns( $columns ) {
		$columns['wpepp_status'] = __( 'Protection', 'wp-edit-password-protected' );
		return $columns;
	}

	/**
	 * Render the custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_post_list_column( $column, $post_id ) {
		if ( 'wpepp_status' !== $column ) {
			return;
		}

		$badges = [];

		// Password protected (core WP).
		if ( post_password_required( $post_id ) ) {
			$badges[] = '<span class="wpepp-badge wpepp-badge-password" title="' . esc_attr__( 'Password Protected', 'wp-edit-password-protected' ) . '">&#128274;</span>';
		}

		// Content Lock.
		$lock_enabled = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );
		if ( 'yes' === $lock_enabled ) {
			$lock_action = get_post_meta( $post_id, '_wpepp_content_lock_action', true ) ?: 'link';
			$lock_labels = [
				'link'     => __( 'Lock: Login Link', 'wp-edit-password-protected' ),
				'form'     => __( 'Lock: Login Form', 'wp-edit-password-protected' ),
				'popup'    => __( 'Lock: Popup', 'wp-edit-password-protected' ),
				'redirect' => __( 'Lock: Redirect', 'wp-edit-password-protected' ),
			];
			$label    = isset( $lock_labels[ $lock_action ] ) ? $lock_labels[ $lock_action ] : __( 'Locked', 'wp-edit-password-protected' );
			$badges[] = '<span class="wpepp-badge wpepp-badge-lock" title="' . esc_attr( $label ) . '">&#128275; ' . esc_html( $label ) . '</span>';
		}

		// Conditional Display.
		$cond_enabled = get_post_meta( $post_id, '_wpepp_conditional_display_enable', true );
		if ( 'yes' === $cond_enabled ) {
			$cond_type   = get_post_meta( $post_id, '_wpepp_conditional_display_condition', true );
			$cond_action = get_post_meta( $post_id, '_wpepp_conditional_action', true ) ?: 'show';
			$action_label = 'hide' === $cond_action ? __( 'Hide', 'wp-edit-password-protected' ) : __( 'Show', 'wp-edit-password-protected' );
			$cond_label   = ucwords( str_replace( '_', ' ', $cond_type ) );
			$badges[]     = '<span class="wpepp-badge wpepp-badge-cond" title="' . esc_attr( $action_label . ': ' . $cond_label ) . '">&#128065; ' . esc_html( $action_label . ': ' . $cond_label ) . '</span>';
		}

		if ( empty( $badges ) ) {
			echo '<span class="wpepp-badge-none">&mdash;</span>';
		} else {
			echo implode( ' ', $badges ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
	}

	/**
	 * Enqueue frontend password form CSS on protected posts only.
	 */
	public function enqueue_password_form_styles() {
		if ( ! is_singular() || ! post_password_required() ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-password-form',
			WPEPP_URL . '/assets/css/frontend-password-form.css',
			[],
			WPEPP_VERSION
		);

		$raw      = get_option( 'wpepp_password_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( ! empty( $settings ) && is_array( $settings ) ) {
			$css = WPEPP_Password_Customizer::generate_css( $settings );
			if ( ! empty( $css ) ) {
				wp_add_inline_style( 'wpepp-password-form', wp_strip_all_tags( $css ) );
			}
		}
	}

	/**
	 * Enqueue content lock CSS for locked posts when user is logged out.
	 */
	public function enqueue_content_lock_styles() {
		if ( ! is_singular() || is_user_logged_in() ) {
			return;
		}

		$locked = get_post_meta( get_the_ID(), '_wpepp_content_lock_enabled', true );
		if ( 'yes' !== $locked ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-content-lock',
			WPEPP_URL . '/assets/css/frontend-content-lock.css',
			[],
			WPEPP_VERSION
		);
	}

	/**
	 * Enqueue conditional display JS for client-side conditions.
	 */
	public function enqueue_conditional_script() {
		if ( ! is_singular() ) {
			return;
		}

		$enabled   = get_post_meta( get_the_ID(), '_wpepp_conditional_display_enable', true );
		$condition = get_post_meta( get_the_ID(), '_wpepp_conditional_display_condition', true );

		if ( 'yes' !== $enabled ) {
			return;
		}

		$client_conditions = [ 'browser_type', 'referrer_source' ];

		if ( in_array( $condition, $client_conditions, true ) ) {
			wp_enqueue_script(
				'wpepp-conditional',
				WPEPP_URL . '/assets/js/conditional-display.js',
				[],
				WPEPP_VERSION,
				true
			);

			wp_localize_script( 'wpepp-conditional', 'wpeppCondition', [
				'condition' => sanitize_text_field( $condition ),
				'action'    => sanitize_text_field(
					get_post_meta( get_the_ID(), '_wpepp_conditional_action', true )
				),
				'data'      => WPEPP_Conditional_Meta_Helper::get_client_condition_data( get_the_ID() ),
			] );
		}
	}

	/**
	 * Get the correct form settings for the current login page action.
	 *
	 * Returns register settings on ?action=register, lostpassword settings
	 * on ?action=lostpassword, and login settings otherwise.
	 *
	 * @return array|null Decoded settings array or null.
	 */
	private function get_current_login_settings() {
		// WordPress wp-login.php sets $action as a global before login_enqueue_scripts fires.
		global $action;

		$current_action = '';
		if ( ! empty( $action ) && is_string( $action ) ) {
			$current_action = $action;
		} elseif ( isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
		}

		switch ( $current_action ) {
			case 'register':
				$option = 'wpepp_register_settings';
				break;
			case 'lostpassword':
			case 'retrievepassword':
				$option = 'wpepp_lostpassword_settings';
				break;
			default:
				$option = 'wpepp_login_settings';
		}

		$raw      = get_option( $option, '{}' );
		$settings = json_decode( $raw, true );

		return ( ! empty( $settings ) && is_array( $settings ) ) ? $settings : null;
	}

	/**
	 * Enqueue login page styles.
	 */
	public function enqueue_login_styles() {
		$settings = $this->get_current_login_settings();

		if ( ! $settings ) {
			return;
		}

		$css = WPEPP_Login_Customizer::generate_css( $settings );
		if ( ! empty( $css ) ) {
			wp_add_inline_style( 'login', wp_strip_all_tags( $css ) );
		}
	}

	/**
	 * Filter the login logo URL — use the saved URL, or fall back to home_url().
	 *
	 * @param string $url Default logo URL.
	 * @return string
	 */
	public function custom_login_logo_url( $url ) {
		$settings = $this->get_current_login_settings();

		if ( ! empty( $settings['logo']['url'] ) ) {
			return esc_url( $settings['logo']['url'] );
		}

		return home_url( '/' );
	}

	/**
	 * Output global Custom CSS from Settings → Custom CSS on all frontend pages and the login page.
	 */
	public function enqueue_global_custom_css() {
		$raw      = get_option( 'wpepp_general_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( empty( $settings['custom_css'] ) ) {
			return;
		}

		$css = wp_strip_all_tags( $settings['custom_css'] );
		if ( empty( $css ) ) {
			return;
		}

		// On login page, attach to 'login' handle; on frontend, register a dummy handle.
		if ( did_action( 'login_init' ) ) {
			wp_add_inline_style( 'login', $css );
		} else {
			wp_register_style( 'wpepp-global-custom-css', false );
			wp_enqueue_style( 'wpepp-global-custom-css' );
			wp_add_inline_style( 'wpepp-global-custom-css', $css );
		}
	}

	/**
	 * Add a heading above the login/register/lostpassword form.
	 *
	 * @param string $message Existing login message HTML.
	 * @return string
	 */
	public function add_login_heading( $message ) {
		$settings = $this->get_current_login_settings();

		if ( ! $settings ) {
			return $message;
		}

		$heading = $settings['heading'] ?? [];
		if ( empty( $heading['show'] ) || empty( $heading['text'] ) ) {
			return $message;
		}

		$heading_html = '<div class="wpepp-login-heading">' . esc_html( $heading['text'] ) . '</div>';

		return $heading_html . $message;
	}

	/**
	 * Add video background to the login page (MP4, YouTube, Vimeo).
	 */
	public function add_login_video_background() {
		$settings = $this->get_current_login_settings();

		if ( ! $settings ) {
			return;
		}

		$page = $settings['page'] ?? [];
		if ( 'video' !== ( $page['background_type'] ?? '' ) || empty( $page['background_video'] ) ) {
			return;
		}

		$url      = esc_url( $page['background_video'] );
		$video_id = '';
		$provider = 'mp4';

		// Detect YouTube.
		if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/', $url, $m ) ) {
			$video_id = $m[1];
			$provider = 'youtube';
		}
		// Detect Vimeo.
		elseif ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m ) ) {
			$video_id = $m[1];
			$provider = 'vimeo';
		}

		$video_css = '.wpepp-video-bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;overflow:hidden;}'
			. '.wpepp-video-bg video{width:100%;height:100%;object-fit:cover;}'
			. '.wpepp-video-bg iframe{position:absolute;top:50%;left:50%;width:100vw;height:56.25vw;'
			. 'min-height:100vh;min-width:177.78vh;transform:translate(-50%,-50%);border:0;pointer-events:none;}';
		wp_register_style( 'wpepp-video-bg', false, [], defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0' );
		wp_add_inline_style( 'wpepp-video-bg', $video_css );
		wp_print_styles( [ 'wpepp-video-bg' ] );

		echo '<div class="wpepp-video-bg">';
		if ( 'youtube' === $provider ) {
			echo '<iframe src="' . esc_url( 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1' ) . '" allow="autoplay" allowfullscreen></iframe>';
		} elseif ( 'vimeo' === $provider ) {
			echo '<iframe src="' . esc_url( 'https://player.vimeo.com/video/' . $video_id . '?autoplay=1&muted=1&loop=1&background=1&app_id=122963' ) . '" allow="autoplay" allowfullscreen></iframe>';
		} else {
			echo '<video autoplay muted loop playsinline><source src="' . esc_url( $url ) . '" type="video/mp4"></video>';
		}
		echo '</div>';
	}

	/**
	 * Custom password form output.
	 *
	 * @param string $output Default password form HTML.
	 * @return string
	 */
	public function custom_password_form( $output ) {
		return WPEPP_Password_Customizer::render_form( $output );
	}

	/**
	 * Check whether the current user is locked out of a post based on lock_for setting.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if the user should be locked.
	 */
	public static function is_user_locked( $post_id ) {
		// Check expiry first — if lock has expired, no one is locked.
		$expiry = get_post_meta( $post_id, '_wpepp_content_lock_expiry', true );
		if ( ! empty( $expiry ) ) {
			$expiry_time = strtotime( $expiry, current_time( 'timestamp' ) );
			if ( $expiry_time && current_time( 'timestamp' ) >= $expiry_time ) {
				return false;
			}
		}

		$lock_roles = get_post_meta( $post_id, '_wpepp_content_lock_roles', true );

		// No roles selected — default: lock for logged-out users.
		if ( ! is_array( $lock_roles ) || empty( $lock_roles ) ) {
			return ! is_user_logged_in();
		}

		// Check logged-out users.
		if ( in_array( 'logged_out', $lock_roles, true ) && ! is_user_logged_in() ) {
			return true;
		}

		// Check role-based lock for logged-in users.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( ! empty( array_intersect( $user->roles, $lock_roles ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Exclude locked posts (without show-excerpt) from blog/archive queries.
	 *
	 * @param \WP_Query $query Main query object.
	 */
	public function exclude_locked_posts_from_archive( $query ) {
		if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
			return;
		}

		if ( ! wpepp_has_pro_check() ) {
			return;
		}

		// Find all locked posts.
		$all_locked = get_posts( [
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_wpepp_content_lock_enabled',
					'value' => 'yes',
				],
			],
		] );

		// From locked posts, exclude those the current user can't see
		// AND have neither show_excerpt checked NOR custom excerpt text.
		$exclude_ids = [];
		foreach ( $all_locked as $pid ) {
			if ( ! self::is_user_locked( $pid ) ) {
				continue; // User has access — don't exclude.
			}
			$show_excerpt = get_post_meta( $pid, '_wpepp_content_lock_show_excerpt', true );
			if ( 'yes' === $show_excerpt ) {
				continue; // Show excerpt enabled — keep in listing.
			}
			$exclude_ids[] = $pid;
		}

		if ( ! empty( $exclude_ids ) ) {
			$existing = $query->get( 'post__not_in' );
			$existing = is_array( $existing ) ? $existing : [];
			$query->set( 'post__not_in', array_merge( $existing, $exclude_ids ) );
		}
	}

	/**
	 * Content Lock — hide content from logged-out users.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_lock_content( $content ) {
		if ( ! wpepp_has_pro_check() ) {
			return $content;
		}

		// On blog/archive pages, show excerpt or custom text for locked posts.
		if ( ! is_singular() ) {
			$post_id = get_the_ID();
			$locked  = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );
			if ( 'yes' === $locked && self::is_user_locked( $post_id ) ) {
				$show_excerpt = get_post_meta( $post_id, '_wpepp_content_lock_show_excerpt', true );

				if ( 'yes' === $show_excerpt ) {
					// Checked — show custom excerpt text if provided, otherwise auto-generate.
					$custom_text = get_post_meta( $post_id, '_wpepp_content_lock_excerpt_text', true );
					if ( ! empty( $custom_text ) ) {
						return '<p>' . esc_html( $custom_text ) . '</p>';
					}
					$post    = get_post( $post_id );
					$excerpt = $post->post_excerpt;
					if ( empty( $excerpt ) ) {
						$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 55, '&hellip;' );
					}
					return '<p>' . esc_html( $excerpt ) . '</p>';
				}

				// Unchecked — no excerpt.
			}
			return $content;
		}

		$post_id = get_the_ID();
		$locked  = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );
		if ( 'yes' !== $locked ) {
			return $content;
		}

		if ( ! self::is_user_locked( $post_id ) ) {
			return $content;
		}

		$action = get_post_meta( $post_id, '_wpepp_content_lock_action', true ) ?: 'link';

		// Popup action — show blurred content with login overlay.
		if ( 'popup' === $action ) {
			return WPEPP_Frontend::get_popup_locked_content( $post_id, $content );
		}

		return WPEPP_Frontend::get_locked_message( $post_id );
	}

	/**
	 * Content Lock — override excerpt for locked posts on blog/archive pages.
	 *
	 * Themes that use the_excerpt() instead of the_content() need this filter
	 * to replace WP's default "There is no excerpt because this is a protected post." text.
	 *
	 * @param string   $excerpt The post excerpt.
	 * @param \WP_Post $post    Post object.
	 * @return string
	 */
	public function maybe_lock_excerpt( $excerpt, $post ) {
		if ( ! wpepp_has_pro_check() || is_singular() ) {
			return $excerpt;
		}

		$locked = get_post_meta( $post->ID, '_wpepp_content_lock_enabled', true );
		if ( 'yes' !== $locked || ! self::is_user_locked( $post->ID ) ) {
			return $excerpt;
		}

		$show_excerpt = get_post_meta( $post->ID, '_wpepp_content_lock_show_excerpt', true );

		if ( 'yes' === $show_excerpt ) {
			// Checked — return custom text if provided, otherwise auto-generate.
			$custom_text = get_post_meta( $post->ID, '_wpepp_content_lock_excerpt_text', true );
			if ( ! empty( $custom_text ) ) {
				return $custom_text;
			}
			$text = $post->post_excerpt;
			if ( empty( $text ) ) {
				$text = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 55, '&hellip;' );
			}
			return $text;
		}

		// Unchecked — no excerpt.
		return '';
	}

	/**
	 * Content Lock — redirect locked posts before output starts.
	 */
	public function maybe_redirect_locked_content() {
		if ( ! wpepp_has_pro_check() || ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		$locked  = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );
		if ( 'yes' !== $locked ) {
			return;
		}

		if ( ! self::is_user_locked( $post_id ) ) {
			return;
		}

		$action = get_post_meta( $post_id, '_wpepp_content_lock_action', true ) ?: 'link';
		if ( 'redirect' !== $action ) {
			return;
		}

		$redirect_url = get_post_meta( $post_id, '_wpepp_content_lock_redirect', true );
		if ( ! empty( $redirect_url ) ) {
			$redirect_url = esc_url_raw( $redirect_url );
			$host         = wp_parse_url( $redirect_url, PHP_URL_HOST );

			// Temporarily allow the target host so wp_safe_redirect() accepts external URLs.
			$allow_host = static function ( $hosts ) use ( $host ) {
				$hosts[] = $host;
				return $hosts;
			};
			add_filter( 'allowed_redirect_hosts', $allow_host );
			wp_safe_redirect( $redirect_url, 302, 'WPEPP' );
			remove_filter( 'allowed_redirect_hosts', $allow_host );
			exit;
		}

		// Default: redirect to the WordPress login page.
		wp_safe_redirect( wp_login_url( get_permalink( $post_id ) ) );
		exit;
	}

	/**
	 * Conditional display content filter.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function conditional_display_filter( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

		$enabled = get_post_meta( get_the_ID(), '_wpepp_conditional_display_enable', true );
		if ( 'yes' !== $enabled ) {
			return $content;
		}

		if ( $this->should_hide_conditional( get_the_ID() ) ) {
			$notice_on  = get_post_meta( get_the_ID(), '_wpepp_conditional_notice_enable', true );
			$notice_txt = get_post_meta( get_the_ID(), '_wpepp_conditional_notice_text', true );

			if ( 'yes' === $notice_on && ! empty( $notice_txt ) ) {
				return '<div class="wpepp-conditional-notice">' . wp_kses_post( $notice_txt ) . '</div>';
			}

			return '';
		}

		return $content;
	}

	/**
	 * Conditional display title filter.
	 *
	 * @param string $title   Post title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function conditional_display_title_filter( $title, $post_id = 0 ) {
		if ( ! $post_id || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return $title;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_control_title', true ) ) {
			return $title;
		}

		if ( $this->should_hide_conditional( $post_id ) ) {
			return '';
		}

		return $title;
	}

	/**
	 * Conditional display featured image filter.
	 *
	 * @param string $html              Thumbnail HTML.
	 * @param int    $post_id           Post ID.
	 * @param int    $post_thumbnail_id Attachment ID.
	 * @param string $size              Image size.
	 * @param array  $attr              Attributes.
	 * @return string
	 */
	public function conditional_display_thumbnail_filter( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $html;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return $html;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_control_featured_image', true ) ) {
			return $html;
		}

		if ( $this->should_hide_conditional( $post_id ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Check if conditional display should hide content for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if content should be hidden.
	 */
	private function should_hide_conditional( $post_id ) {
		$should_show = WPEPP_Conditional_Meta_Helper::evaluate_condition( $post_id );
		$action      = get_post_meta( $post_id, '_wpepp_conditional_action', true );

		if ( 'show' === $action && ! $should_show ) {
			return true;
		}

		if ( 'hide' === $action && $should_show ) {
			return true;
		}

		return false;
	}

	/**
	 * Disable comments when conditional display hides content.
	 *
	 * @param bool $open    Whether comments are open.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public function conditional_display_comments_filter( $open, $post_id ) {
		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return $open;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_control_comments', true ) ) {
			return $open;
		}

		if ( $this->should_hide_conditional( $post_id ) ) {
			return false;
		}

		return $open;
	}

	/**
	 * Return zero comments when conditional display hides content.
	 *
	 * @param int $count   Comment count.
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function conditional_display_comments_number_filter( $count, $post_id ) {
		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return $count;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_control_comments', true ) ) {
			return $count;
		}

		if ( $this->should_hide_conditional( $post_id ) ) {
			return 0;
		}

		return $count;
	}

	/**
	 * Add a CSS class to the post wrapper when conditional display hides content.
	 *
	 * @param array $classes Existing post classes.
	 * @param array $class   Additional classes.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public function conditional_display_post_class( $classes, $class, $post_id ) {
		if ( ! is_singular() ) {
			return $classes;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return $classes;
		}

		if ( $this->should_hide_conditional( $post_id ) ) {
			$classes[] = 'wpepp-conditional-hidden';
		}

		return $classes;
	}

	/**
	 * Output inline CSS to hide post meta when content is conditionally hidden.
	 */
	public function conditional_display_hide_meta_css() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		if ( 'yes' !== get_post_meta( $post_id, '_wpepp_conditional_display_enable', true ) ) {
			return;
		}

		if ( ! $this->should_hide_conditional( $post_id ) ) {
			return;
		}

		$hide_comments = 'yes' === get_post_meta( $post_id, '_wpepp_conditional_control_comments', true );

		$css = '.wpepp-conditional-hidden .entry-meta,'
			. '.wpepp-conditional-hidden .entry-footer,'
			. '.wpepp-conditional-hidden .post-meta,'
			. '.wpepp-conditional-hidden .posted-on,'
			. '.wpepp-conditional-hidden .byline,'
			. '.wpepp-conditional-hidden .cat-links,'
			. '.wpepp-conditional-hidden .tags-links,'
			. '.wpepp-conditional-hidden .edit-link,'
			. '.wpepp-conditional-hidden .post-info,'
			. '.wpepp-conditional-hidden .meta-info,'
			. '.wpepp-conditional-hidden .wp-block-post-date,'
			. '.wpepp-conditional-hidden .wp-block-post-author,'
			. '.wpepp-conditional-hidden .wp-block-post-terms,'
			. '.wpepp-conditional-hidden .taxonomy-post_tag,'
			. '.wpepp-conditional-hidden .taxonomy-category'
			. '{display:none!important}'
			. '.wpepp-conditional-notice{'
			. 'background:#fff8e1;border-left:4px solid #ffb300;'
			. 'padding:16px 20px;margin:20px 0;border-radius:4px;'
			. 'color:#6d4c00;font-size:15px;line-height:1.6'
			. '}'
			. ( $hide_comments ? '#comments,.comments-area,.wp-block-comments{display:none!important}' : '' );
		wp_register_style( 'wpepp-conditional-hidden', false, [], defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.0.0' );
		wp_enqueue_style( 'wpepp-conditional-hidden' );
		wp_add_inline_style( 'wpepp-conditional-hidden', $css );
	}

	/**
	 * Protect REST API content for password-protected or conditional posts.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param \WP_Post          $post     Post object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response
	 */
	public function protect_rest_content( $response, $post, $request ) {
		$hide = false;

		if ( post_password_required( $post ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			$hide = true;
		}

		$conditional_enabled = get_post_meta( $post->ID, '_wpepp_conditional_display_enable', true );
		if ( 'yes' === $conditional_enabled && ! current_user_can( 'edit_post', $post->ID ) ) {
			$hide = true;
		}

		if ( $hide ) {
			$response->data['content']['rendered'] = __( 'This content is protected.', 'wp-edit-password-protected' );
			$response->data['excerpt']['rendered']  = '';
		}

		return $response;
	}

	/**
	 * Redirect failed front-end logins back to referrer.
	 *
	 * @param string $username The username attempted.
	 */
	public function login_fail_redirect( $username ) {
		// Don't redirect when using the Admin Only popup login form —
		// the popup handles its own error display.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in WPEPP_Site_Access::enforce_admin_only().
		if ( isset( $_POST['wpepp_admin_login_nonce'] ) ) {
			return;
		}

		$referrer = isset( $_SERVER['HTTP_REFERER'] )
			? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
			: '';

		if ( ! empty( $referrer ) && false === strpos( $referrer, 'wp-login' ) && false === strpos( $referrer, 'wp-admin' ) ) {
			wp_safe_redirect( add_query_arg( 'login', 'failed', $referrer ) );
			exit;
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$rest = new WPEPP_Rest_Api();
		$rest->register_routes();
	}

	/**
	 * Handle preview requests via template_redirect.
	 */
	public function handle_preview() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Uses signed token validation instead.
		if ( ! isset( $_GET['wpepp_preview'] ) ) {
			return;
		}

		$token = isset( $_GET['wpepp_token'] )
			? sanitize_text_field( wp_unslash( $_GET['wpepp_token'] ) )
			: '';

		if ( ! wpepp_validate_preview_token( $token ) ) {
			wp_die( esc_html__( 'Invalid preview token.', 'wp-edit-password-protected' ) );
		}

		$type = isset( $_GET['type'] )
			? sanitize_text_field( wp_unslash( $_GET['type'] ) )
			: 'login';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		WPEPP_Frontend::render_preview( $type );
		exit;
	}

	/**
	 * Handle preview requests via admin-ajax.
	 */
	public function ajax_preview() {
		check_ajax_referer( 'wpepp_preview_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wp-edit-password-protected' ), 403 );
		}

		$type = isset( $_GET['type'] )
			? sanitize_text_field( wp_unslash( $_GET['type'] ) )
			: 'login';

		WPEPP_Frontend::render_preview( $type );
		exit;
	}

	/**
	 * Initialize security features.
	 */
	private function init_security() {
		$defaults = [
			'login_limit_enabled'  => true,
			'max_attempts'         => 5,
			'lockout_duration'     => 15,
			'honeypot_enabled'     => true,
			'disable_xmlrpc'       => false,
			'hide_wp_version'      => false,
			'disable_rest_users'   => false,
			'recaptcha_enabled'    => false,
			'recaptcha_site_key'   => '',
			'recaptcha_secret_key' => '',
			'custom_login_url'     => '',
			'login_log_enabled'    => true,
			'hide_login_page'      => false,
			'after_login_redirect' => '',
			// AI Crawler Blocker.
			'ai_crawler_blocker_enabled' => true,
			'ai_crawler_block_ua'        => true,
			'ai_crawler_bots'            => null,
			'ai_crawler_custom_ua'       => '',
		];

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$settings = array_merge( $defaults, $settings );

		WPEPP_Security::init( $settings );
	}

	/**
	 * Initialize Appsero tracker.
	 */
	private function init_appsero() {
		$client_file = WPEPP_PATH . 'vendor/appsero/client/src/Client.php';
		if ( ! class_exists( 'Appsero\Client' ) && file_exists( $client_file ) ) {
			require_once $client_file;
		}

		if ( class_exists( 'Appsero\Client' ) ) {
			$client = new Appsero\Client( '08132ef7-0f22-4c36-9ac4-0cad92ae19de', 'WPEPP – Login Security, Password Protect & Login Page Customizer', WPEPP_FILE );
			$client->insights()->init();
		}
	}
}

/**
 * Generate a secure preview token.
 *
 * @return string
 */
function wpepp_generate_preview_token() {
	$token = wp_generate_password( 32, false );
	set_transient(
		'wpepp_preview_' . $token,
		get_current_user_id(),
		5 * MINUTE_IN_SECONDS
	);
	return $token;
}

/**
 * Validate a preview token.
 *
 * @param string $token The token to validate.
 * @return bool
 */
function wpepp_validate_preview_token( $token ) {
	$token   = sanitize_text_field( $token );
	$user_id = get_transient( 'wpepp_preview_' . $token );

	if ( ! $user_id || ! user_can( $user_id, 'manage_options' ) ) {
		return false;
	}

	delete_transient( 'wpepp_preview_' . $token );
	return true;
}
