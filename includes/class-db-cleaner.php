<?php
/**
 * WPEPP Database & Log Lifecycle Cleaner.
 *
 * Prevents database bloat, monitors table sizes, and automatically prunes
 * stale security logs (Activity events, Login logs, Slow queries, Expired blocks)
 * via WP-Cron and on-demand REST actions.
 *
 * @package wpepp
 * @since   3.1.2
 */

defined( 'ABSPATH' ) || exit;

class WPEPP_DB_Cleaner {

	const CRON_HOOK = 'wpepp_daily_db_maintenance';

	/**
	 * Initialize hooks and cron.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, [ __CLASS__, 'cron_maintenance' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Run scheduled daily maintenance.
	 */
	public static function cron_maintenance() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = is_array( json_decode( $raw, true ) ) ? json_decode( $raw, true ) : [];

		// Automated daily background pruning is a Pro feature. Free users can trigger manual cleanup.
		if ( ! function_exists( 'wpepp_has_pro_check' ) || ! wpepp_has_pro_check() ) {
			return;
		}

		$auto_prune = ! empty( $settings['auto_prune_logs_enabled'] );
		if ( ! $auto_prune ) {
			return;
		}

		self::purge_all_old_logs();
	}

	/**
	 * Get statistics for all WPEPP tables.
	 *
	 * @return array
	 */
	public static function get_stats() {
		global $wpdb;

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = is_array( json_decode( $raw, true ) ) ? json_decode( $raw, true ) : [];

		$tables = [
			'events' => [
				'label'     => __( 'Activity Events Log', 'wp-edit-password-protected' ),
				'table'     => $wpdb->prefix . 'wpepp_events',
				'time_col'  => 'created_at',
				'retention' => absint( $settings['events_retention_days'] ?? 30 ),
			],
			'login_log' => [
				'label'     => __( 'Login Activity Log', 'wp-edit-password-protected' ),
				'table'     => $wpdb->prefix . 'wpepp_login_log',
				'time_col'  => 'created_at',
				'retention' => absint( $settings['login_log_retention_days'] ?? 30 ),
			],
			'slow_queries' => [
				'label'     => __( 'Slow Database Queries', 'wp-edit-password-protected' ),
				'table'     => $wpdb->prefix . 'wpepp_slow_queries',
				'time_col'  => 'recorded_at',
				'retention' => absint( $settings['slow_queries_retention_days'] ?? 7 ),
			],
			'blocks' => [
				'label'     => __( 'Blocked IP Records', 'wp-edit-password-protected' ),
				'table'     => $wpdb->prefix . 'wpepp_blocks',
				'time_col'  => 'created_at',
				'retention' => null, // Expired blocks pruned
			],
		];

		$stats = [];
		$total_rows = 0;
		$total_bytes = 0;

		foreach ( $tables as $key => $meta ) {
			$table_name = $meta['table'];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

			if ( ! $exists ) {
				$stats[ $key ] = [
					'key'         => $key,
					'label'       => $meta['label'],
					'exists'      => false,
					'rows'        => 0,
					'data_size'   => '0 KB',
					'data_bytes'  => 0,
					'retention'   => $meta['retention'],
					'oldest'      => null,
					'newest'      => null,
				];
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name ) );
			$total_rows += $row_count;

			// Get table size from information_schema.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$size_info = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT (data_length + index_length) AS total_size
					 FROM information_schema.TABLES
					 WHERE table_schema = %s AND table_name = %s',
					defined( 'DB_NAME' ) ? DB_NAME : '',
					$table_name
				)
			);
			$bytes = (int) ( $size_info->total_size ?? 0 );
			$total_bytes += $bytes;

			$time_col = $meta['time_col'];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$dates = $wpdb->get_row( $wpdb->prepare( 'SELECT MIN(%i) AS oldest, MAX(%i) AS newest FROM %i', $time_col, $time_col, $table_name ) );

			$stats[ $key ] = [
				'key'        => $key,
				'label'      => $meta['label'],
				'exists'     => true,
				'rows'       => $row_count,
				'data_size'  => function_exists( 'size_format' ) ? size_format( $bytes, 2 ) : round( $bytes / 1024, 2 ) . ' KB',
				'data_bytes' => $bytes,
				'retention'  => $meta['retention'],
				'oldest'     => $dates ? $dates->oldest : null,
				'newest'     => $dates ? $dates->newest : null,
			];
		}

		return [
			'tables'            => $stats,
			'total_rows'        => $total_rows,
			'total_size'        => function_exists( 'size_format' ) ? size_format( $total_bytes, 2 ) : round( $total_bytes / 1024, 2 ) . ' KB',
			'total_bytes'       => $total_bytes,
			'auto_prune'        => (bool) ( $settings['auto_prune_logs_enabled'] ?? true ),
			'max_rows_cap'      => absint( $settings['max_log_rows_cap'] ?? 5000 ),
			'last_maintenance'  => get_option( 'wpepp_last_db_maintenance', null ),
		];
	}

	/**
	 * Prune old logs from all tables based on retention settings.
	 *
	 * @return array Summary of purged records.
	 */
	public static function purge_all_old_logs() {
		global $wpdb;

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = is_array( json_decode( $raw, true ) ) ? json_decode( $raw, true ) : [];

		$events_retention = absint( $settings['events_retention_days'] ?? 30 );
		$login_retention  = absint( $settings['login_log_retention_days'] ?? 30 );
		$slow_retention   = absint( $settings['slow_queries_retention_days'] ?? 7 );
		$row_cap          = absint( $settings['max_log_rows_cap'] ?? 5000 );

		$purged = [
			'events'       => 0,
			'login_log'    => 0,
			'slow_queries' => 0,
			'blocks'       => 0,
			'total'        => 0,
		];

		// 1. Prune Activity Events
		$t_events = $wpdb->prefix . 'wpepp_events';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_events ) ) ) {
			if ( $events_retention > 0 ) {
				$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $events_retention * DAY_IN_SECONDS ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted = $wpdb->query(
					$wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $t_events, $cutoff )
				);
				$purged['events'] += max( 0, (int) $deleted );
			}

			// Enforce row cap.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $t_events ) );
			if ( $row_cap > 0 && $count > $row_cap ) {
				$excess = $count - $row_cap;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$cap_del = $wpdb->query(
					$wpdb->prepare( 'DELETE FROM %i ORDER BY id ASC LIMIT %d', $t_events, $excess )
				);
				$purged['events'] += max( 0, (int) $cap_del );
			}
			wp_cache_delete( 'wpepp_event_stats', 'wpepp' );
		}

		// 2. Prune Login Log
		$t_login = $wpdb->prefix . 'wpepp_login_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_login ) ) ) {
			if ( $login_retention > 0 ) {
				$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $login_retention * DAY_IN_SECONDS ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted = $wpdb->query(
					$wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $t_login, $cutoff )
				);
				$purged['login_log'] += max( 0, (int) $deleted );
			}

			// Enforce row cap.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $t_login ) );
			if ( $row_cap > 0 && $count > $row_cap ) {
				$excess = $count - $row_cap;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$cap_del = $wpdb->query(
					$wpdb->prepare( 'DELETE FROM %i ORDER BY id ASC LIMIT %d', $t_login, $excess )
				);
				$purged['login_log'] += max( 0, (int) $cap_del );
			}
		}

		// 3. Prune Slow Queries
		$t_slow = $wpdb->prefix . 'wpepp_slow_queries';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_slow ) ) ) {
			if ( $slow_retention > 0 ) {
				$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $slow_retention * DAY_IN_SECONDS ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted = $wpdb->query(
					$wpdb->prepare( 'DELETE FROM %i WHERE recorded_at < %s', $t_slow, $cutoff )
				);
				$purged['slow_queries'] += max( 0, (int) $deleted );
			}
			wp_cache_delete( 'wpepp_slow_queries_count', 'wpepp' );
		}

		// 4. Prune Expired Blocks
		if ( class_exists( 'WPEPP_Blocks' ) ) {
			$purged['blocks'] = (int) WPEPP_Blocks::purge_expired();
		}

		$purged['total'] = $purged['events'] + $purged['login_log'] + $purged['slow_queries'] + $purged['blocks'];

		update_option( 'wpepp_last_db_maintenance', current_time( 'mysql' ) );

		return $purged;
	}

	/**
	 * Purge an individual table completely or purge only its expired/old records.
	 *
	 * @param string $key   Table key: 'events', 'login_log', 'slow_queries', 'blocks'.
	 * @param bool   $all   If true, truncates/deletes all rows; if false, deletes old rows.
	 * @return int Rows removed.
	 */
	public static function purge_table( $key, $all = false ) {
		global $wpdb;

		$map = [
			'events'       => [ 'table' => $wpdb->prefix . 'wpepp_events', 'time_col' => 'created_at', 'default_days' => 30 ],
			'login_log'    => [ 'table' => $wpdb->prefix . 'wpepp_login_log', 'time_col' => 'created_at', 'default_days' => 30 ],
			'slow_queries' => [ 'table' => $wpdb->prefix . 'wpepp_slow_queries', 'time_col' => 'recorded_at', 'default_days' => 7 ],
			'blocks'       => [ 'table' => $wpdb->prefix . 'wpepp_blocks', 'time_col' => 'created_at', 'default_days' => 30 ],
		];

		if ( ! isset( $map[ $key ] ) ) {
			return 0;
		}

		$t = $map[ $key ]['table'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) ) {
			return 0;
		}

		if ( $all ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $t ) );
			return true === $deleted || 0 === $deleted ? 1 : 0;
		}

		if ( 'blocks' === $key && class_exists( 'WPEPP_Blocks' ) ) {
			return (int) WPEPP_Blocks::purge_expired();
		}

		$days   = $map[ $key ]['default_days'];
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$col    = $map[ $key ]['time_col'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare( 'DELETE FROM %i WHERE %i < %s', $t, $col, $cutoff )
		);

		return max( 0, (int) $deleted );
	}
}
