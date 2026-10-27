<?php
/**
 * CPU Monitor — Options & Transient Bloat Monitor.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Options_Monitor
 */
class WPEPP_CPU_Options_Monitor {

	/**
	 * Get options table summary.
	 *
	 * @param bool $full Include full breakdown (Pro).
	 * @return array
	 */
	public static function get_stats( $full = false ) {
		global $wpdb;

		$cache_key = 'wpepp_options_bloat_' . ( $full ? 'full' : 'summary' );
		$cached    = wp_cache_get( $cache_key, 'wpepp' );

		if ( false !== $cached ) {
			return $cached;
		}

		// WP 6.6+ uses 'on'/'off'/'auto' instead of 'yes'/'no'.
		if ( function_exists( 'wp_autoload_values_to_autoload' ) ) {
			$autoload_values = wp_autoload_values_to_autoload();
		} else {
			$autoload_values = [ 'yes' ];
		}

		$clean_values = array_map( 'esc_sql', $autoload_values );
		$in_clause    = "'" . implode( "','", $clean_values ) . "'";

		// Total options table size.
		$table_size = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT ROUND( ( data_length + index_length ) ) FROM information_schema.TABLES WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$wpdb->options
			)
		);

		// Total options count.
		$total_options = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT COUNT(*) FROM {$wpdb->options}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in_clause is sanitized via esc_sql on static WP autoload keywords; options table is core.

		// Autoloaded options total size.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$autoload_size = (int) $wpdb->get_var(
			"SELECT COALESCE( SUM( LENGTH( option_value ) ), 0 ) FROM {$wpdb->options} WHERE autoload IN ({$in_clause})"
		);

		// Autoloaded options count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$autoload_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload IN ({$in_clause})"
		);

		// Expired transients count.
		$expired_transients = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		$stats = [
			'table_size_bytes'    => $table_size,
			'autoload_size_bytes' => $autoload_size,
			'autoload_count'      => $autoload_count,
			'total_options'       => $total_options,
			'expired_transients'  => $expired_transients,
			'autoload_warning'    => $autoload_size > 1048576,
		];

		// Pro: full breakdown of top 20 autoloaded options.
		if ( $full ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$top_options = $wpdb->get_results(
				"SELECT option_name, LENGTH( option_value ) AS size FROM {$wpdb->options} WHERE autoload IN ({$in_clause}) ORDER BY size DESC LIMIT 20"
			);

			$stats['top_autoloaded'] = [];
			if ( $top_options ) {
				foreach ( $top_options as $opt ) {
					$stats['top_autoloaded'][] = [
						'name'    => sanitize_text_field( $opt->option_name ),
						'size'    => (int) $opt->size,
						'size_kb' => round( (int) $opt->size / 1024, 1 ),
					];
				}
			}
		}

		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_cache_set( $cache_key, $stats, 'wpepp', 60 );

		return $stats;
	}

	/**
	 * Delete all expired transients.
	 *
	 * @return int Number of deleted transients.
	 */
	public static function clean_expired_transients() {
		global $wpdb;

		// Get expired timeout option names.
		$expired = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		if ( empty( $expired ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $expired as $timeout_key ) {
			$transient_name = str_replace( '_transient_timeout_', '', $timeout_key );
			if ( delete_transient( $transient_name ) ) {
				$count++;
			}
		}

		wp_cache_delete( 'wpepp_options_bloat_summary', 'wpepp' );
		wp_cache_delete( 'wpepp_options_bloat_full', 'wpepp' );

		return $count;
	}
}
