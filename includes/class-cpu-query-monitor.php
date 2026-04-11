<?php
/**
 * CPU Monitor — Slow Query Monitor.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Query_Monitor
 */
class WPEPP_CPU_Query_Monitor {

	/**
	 * DB table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE = 'wpepp_slow_queries';

	/**
	 * Get the full table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Get recent slow queries.
	 *
	 * @param int $limit Max rows to return.
	 * @return array
	 */
	public static function get_slow_queries( $limit = 10 ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [];
		}

		$table = self::table_name();

		$cache_key = 'wpepp_slow_queries_' . $limit;
		$results   = wp_cache_get( $cache_key, 'wpepp' );

		if ( false === $results ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					'SELECT id, query_sql, exec_time, call_stack, recorded_at FROM %i ORDER BY recorded_at DESC LIMIT %d',
					$table,
					$limit
				)
			);
			wp_cache_set( $cache_key, $results, 'wpepp' );
		}

		return $results ?: [];
	}

	/**
	 * Log slow queries from the current request.
	 *
	 * Intended to be called on `shutdown`.
	 *
	 * @param float $threshold Minimum execution time in seconds.
	 */
	public static function log_slow_queries( $threshold = 0.5 ) {
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			return;
		}

		global $wpdb;

		if ( empty( $wpdb->queries ) || ! self::table_exists() ) {
			return;
		}

		$table = self::table_name();

		foreach ( $wpdb->queries as $query_data ) {
			$sql  = $query_data[0] ?? '';
			$time = $query_data[1] ?? 0;
			$trace = $query_data[2] ?? '';

			if ( (float) $time < $threshold ) {
				continue;
			}

			// Truncate very long queries to prevent storage bloat.
			if ( strlen( $sql ) > 5000 ) {
				$sql = substr( $sql, 0, 5000 ) . '…';
			}
			if ( strlen( $trace ) > 2000 ) {
				$trace = substr( $trace, 0, 2000 ) . '…';
			}

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$table,
				[
					'query_sql'   => $sql,
					'exec_time'   => (float) $time,
					'call_stack'  => $trace,
					'recorded_at' => current_time( 'mysql', true ),
				],
				[ '%s', '%f', '%s', '%s' ]
			);
		}
	}

	/**
	 * Prune old entries (keep last 7 days).
	 */
	public static function prune() {
		if ( ! self::table_exists() ) {
			return;
		}

		global $wpdb;

		$table   = self::table_name();
		$cutoff  = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare( 'DELETE FROM %i WHERE recorded_at < %s', $table, $cutoff )
		);

		// Clear cached counts and query lists.
		wp_cache_delete( 'wpepp_slow_queries_count', 'wpepp' );
		wp_cache_delete( 'wpepp_slow_queries_10', 'wpepp' );
		wp_cache_delete( 'wpepp_slow_queries_100', 'wpepp' );
	}

	/**
	 * Get total count of slow queries logged.
	 *
	 * @return int
	 */
	public static function get_count() {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table  = self::table_name();
		$cached = wp_cache_get( 'wpepp_slow_queries_count', 'wpepp' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table )
		);

		wp_cache_set( 'wpepp_slow_queries_count', $count, 'wpepp' );

		return $count;
	}

	/**
	 * Check if the slow queries table exists.
	 *
	 * @return bool
	 */
	public static function table_exists() {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}
}
