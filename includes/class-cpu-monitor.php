<?php
/**
 * CPU Monitor — Main bootstrap class.
 *
 * Registers REST endpoints and cron hooks for the CPU Monitor feature.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Monitor
 */
class WPEPP_CPU_Monitor {

	/**
	 * REST namespace (shared with WPEPP_Rest_Api).
	 *
	 * @var string
	 */
	const REST_NS = 'wpepp/v1';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Auto-create DB table on version upgrade (without requiring reactivation).
		$this->maybe_create_table();

		// Schedule daily prune cron.
		if ( ! wp_next_scheduled( 'wpepp_cpu_prune_queries' ) ) {
			wp_schedule_event( time(), 'daily', 'wpepp_cpu_prune_queries' );
		}
		add_action( 'wpepp_cpu_prune_queries', [ 'WPEPP_CPU_Query_Monitor', 'prune' ] );

		// Log slow queries on shutdown (when SAVEQUERIES is on).
		// Runs on both admin and frontend requests so all slow queries are captured.
		$cpu_settings = json_decode( get_option( 'wpepp_cpu_monitor_settings', '{}' ), true );
		$enabled      = $cpu_settings['enabled'] ?? true;

		if ( $enabled && defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$threshold = (float) ( $cpu_settings['query_threshold'] ?? 0.05 );
			add_action( 'shutdown', static function () use ( $threshold ) {
				WPEPP_CPU_Query_Monitor::log_slow_queries( $threshold );
			} );
		}
	}

	/**
	 * Register CPU Monitor REST routes.
	 */
	public function register_routes() {
		$admin_perm = [ $this, 'check_admin_permission' ];
		$pro_perm   = [ $this, 'check_admin_pro_permission' ];

		// CPU stats (Free).
		register_rest_route( self::REST_NS, '/cpu/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_cpu_stats' ],
			'permission_callback' => $admin_perm,
		] );

		// Slow queries (Free limited / Pro full).
		register_rest_route( self::REST_NS, '/cpu/slow-queries', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_slow_queries' ],
			'permission_callback' => $admin_perm,
		] );

		// Cron jobs list (Free).
		register_rest_route( self::REST_NS, '/cpu/cron-jobs', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_cron_jobs' ],
			'permission_callback' => $admin_perm,
		] );

		// Cron job — run now (Pro).
		register_rest_route( self::REST_NS, '/cpu/cron-jobs/run', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'run_cron_job' ],
			'permission_callback' => $pro_perm,
		] );

		// Cron job — delete (Pro).
		register_rest_route( self::REST_NS, '/cpu/cron-jobs/delete', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'delete_cron_job' ],
			'permission_callback' => $pro_perm,
		] );

		// Error log (Pro).
		register_rest_route( self::REST_NS, '/cpu/error-log', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_error_log' ],
			'permission_callback' => $pro_perm,
		] );

		// Plugin stats (Free).
		register_rest_route( self::REST_NS, '/cpu/plugin-stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_plugin_stats' ],
			'permission_callback' => $admin_perm,
		] );

		// Plugin deactivate (Pro).
		register_rest_route( self::REST_NS, '/cpu/plugin-deactivate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'deactivate_plugin' ],
			'permission_callback' => $pro_perm,
		] );

		// Options bloat (Free limited / Pro full).
		register_rest_route( self::REST_NS, '/cpu/options-bloat', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_options_bloat' ],
			'permission_callback' => $admin_perm,
		] );

		// Clean expired transients (Pro).
		register_rest_route( self::REST_NS, '/cpu/transients/clean', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'clean_transients' ],
			'permission_callback' => $pro_perm,
		] );

		// CPU Monitor settings (Free).
		register_rest_route( self::REST_NS, '/cpu/settings', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => $admin_perm,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => $admin_perm,
			],
		] );

		// wp-config.php constant status (Free).
		register_rest_route( self::REST_NS, '/cpu/wp-config/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_wp_config_status' ],
			'permission_callback' => $admin_perm,
		] );

		// wp-config.php constant toggle (Free).
		register_rest_route( self::REST_NS, '/cpu/wp-config/toggle', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_wp_config_constant' ],
			'permission_callback' => $admin_perm,
		] );
	}

	/**
	 * Create the slow_queries table if it doesn't exist yet.
	 *
	 * Runs once per DB version upgrade so users don't need to reactivate.
	 */
	private function maybe_create_table() {
		$db_version_key = 'wpepp_cpu_db_version';
		$current        = '1.0';

		if ( get_option( $db_version_key ) === $current ) {
			return;
		}

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . WPEPP_CPU_Query_Monitor::TABLE;

		$sql = "CREATE TABLE {$table} (
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

		update_option( $db_version_key, $current );
	}

	// ── Permission callbacks ──

	/**
	 * Check admin capability.
	 *
	 * @return true|WP_Error
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
	 * Check admin + pro capability.
	 *
	 * @return true|WP_Error
	 */
	public function check_admin_pro_permission() {
		$admin_check = $this->check_admin_permission();
		if ( is_wp_error( $admin_check ) ) {
			return $admin_check;
		}
		return wpepp_check_pro_permission();
	}

	// ── Callbacks ──

	/**
	 * GET /cpu/stats — CPU, memory, health score.
	 *
	 * @return WP_REST_Response
	 */
	public function get_cpu_stats() {
		$stats = WPEPP_CPU_System_Info::get_stats();

		// Include summary counts for the overview cards.
		$stats['slow_queries_count']    = WPEPP_CPU_Query_Monitor::get_count();
		$stats['overdue_cron_count']    = WPEPP_CPU_Cron_Monitor::get_overdue_count();
		$stats['expired_transients']    = WPEPP_CPU_Options_Monitor::get_stats()['expired_transients'];
		$stats['cron_alternate']        = WPEPP_CPU_Cron_Monitor::is_alternate_cron();

		return rest_ensure_response( $stats );
	}

	/**
	 * GET /cpu/slow-queries — Recent slow queries (limited for free).
	 *
	 * @return WP_REST_Response
	 */
	public function get_slow_queries() {
		$limit = wpepp_has_pro_check() ? 100 : 10;
		$rows  = WPEPP_CPU_Query_Monitor::get_slow_queries( $limit );

		// Strip call_stack for free users.
		if ( ! wpepp_has_pro_check() ) {
			$rows = array_map( static function ( $row ) {
				unset( $row->call_stack );
				return $row;
			}, $rows );
		}

		return rest_ensure_response( $rows );
	}

	/**
	 * GET /cpu/cron-jobs — All cron events.
	 *
	 * @return WP_REST_Response
	 */
	public function get_cron_jobs() {
		return rest_ensure_response( WPEPP_CPU_Cron_Monitor::get_cron_jobs() );
	}

	/**
	 * POST /cpu/cron-jobs/run — Run a cron event now (Pro).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cron_job( $request ) {
		$body      = $request->get_json_params();
		$hook      = sanitize_text_field( $body['hook'] ?? '' );
		$sig       = sanitize_text_field( $body['sig'] ?? '' );
		$timestamp = absint( $body['timestamp'] ?? 0 );

		if ( empty( $hook ) || empty( $sig ) || empty( $timestamp ) ) {
			return new WP_Error( 'missing_params', __( 'Missing required parameters.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$result = WPEPP_CPU_Cron_Monitor::run_cron_event( $hook, $sig, $timestamp );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * POST /cpu/cron-jobs/delete — Delete a cron event (Pro).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_cron_job( $request ) {
		$body      = $request->get_json_params();
		$hook      = sanitize_text_field( $body['hook'] ?? '' );
		$sig       = sanitize_text_field( $body['sig'] ?? '' );
		$timestamp = absint( $body['timestamp'] ?? 0 );

		if ( empty( $hook ) || empty( $sig ) || empty( $timestamp ) ) {
			return new WP_Error( 'missing_params', __( 'Missing required parameters.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$result = WPEPP_CPU_Cron_Monitor::delete_cron_event( $hook, $sig, $timestamp );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * GET /cpu/error-log — Parsed error log entries (Pro).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_error_log( $request ) {
		$max_lines = absint( $request->get_param( 'max_lines' ) ?? 200 );
		$max_lines = min( $max_lines, 500 );

		$entries = WPEPP_CPU_Error_Log::get_entries( $max_lines );
		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		return rest_ensure_response( $entries );
	}

	/**
	 * GET /cpu/plugin-stats — Active plugin info (Pro).
	 *
	 * @return WP_REST_Response
	 */
	public function get_plugin_stats() {
		return rest_ensure_response( WPEPP_CPU_Plugin_Monitor::get_plugin_stats() );
	}

	/**
	 * POST /cpu/plugin-deactivate — Deactivate a plugin (Pro).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function deactivate_plugin( $request ) {
		$body        = $request->get_json_params();
		$plugin_file = sanitize_text_field( $body['plugin'] ?? '' );

		if ( empty( $plugin_file ) ) {
			return new WP_Error( 'missing_plugin', __( 'Plugin file is required.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$result = WPEPP_CPU_Plugin_Monitor::deactivate_plugin( $plugin_file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * GET /cpu/options-bloat — Options table analysis (limited for free).
	 *
	 * @return WP_REST_Response
	 */
	public function get_options_bloat() {
		$full  = wpepp_has_pro_check();
		$stats = WPEPP_CPU_Options_Monitor::get_stats( $full );

		return rest_ensure_response( $stats );
	}

	/**
	 * POST /cpu/transients/clean — Delete expired transients (Pro).
	 *
	 * @return WP_REST_Response
	 */
	public function clean_transients() {
		$deleted = WPEPP_CPU_Options_Monitor::clean_expired_transients();
		return rest_ensure_response( [ 'deleted' => $deleted ] );
	}

	/**
	 * GET /cpu/settings — Get CPU Monitor settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$raw      = get_option( 'wpepp_cpu_monitor_settings', '{}' );
		$settings = json_decode( $raw, true ) ?: [];

		return rest_ensure_response( $settings );
	}

	/**
	 * POST /cpu/settings — Save CPU Monitor settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_settings( $request ) {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid data.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$sanitized = [
			'enabled'         => ! empty( $body['enabled'] ),
			'query_threshold' => isset( $body['query_threshold'] ) ? max( 0.01, min( 5.0, (float) $body['query_threshold'] ) ) : 0.05,
			'query_logging'   => ! empty( $body['query_logging'] ),
		];

		update_option( 'wpepp_cpu_monitor_settings', wp_json_encode( $sanitized ) );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * GET /cpu/wp-config/status — Current wp-config constant states.
	 *
	 * @return WP_REST_Response
	 */
	public function get_wp_config_status() {
		return rest_ensure_response( WPEPP_CPU_WP_Config::get_status() );
	}

	/**
	 * POST /cpu/wp-config/toggle — Toggle a wp-config constant.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle_wp_config_constant( $request ) {
		$body     = $request->get_json_params();
		$constant = sanitize_text_field( $body['constant'] ?? '' );
		$value    = ! empty( $body['value'] );

		if ( empty( $constant ) ) {
			return new WP_Error( 'missing_constant', __( 'Constant name is required.', 'wp-edit-password-protected' ), [ 'status' => 400 ] );
		}

		$result = WPEPP_CPU_WP_Config::toggle_constant( $constant, $value );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [
			'success' => true,
			'status'  => WPEPP_CPU_WP_Config::get_status(),
		] );
	}
}
