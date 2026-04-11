<?php
/**
 * CPU Monitor — System Info (CPU, memory, server stats).
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_System_Info
 */
class WPEPP_CPU_System_Info {

	/**
	 * Get server load averages.
	 *
	 * @return array{load_1: float, load_5: float, load_15: float}|array{}
	 */
	public static function get_load_average() {
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();
			if ( is_array( $load ) && count( $load ) >= 3 ) {
				return [
					'load_1'  => round( (float) $load[0], 2 ),
					'load_5'  => round( (float) $load[1], 2 ),
					'load_15' => round( (float) $load[2], 2 ),
				];
			}
		}
		return [];
	}

	/**
	 * Estimate CPU usage percentage based on load average and core count.
	 *
	 * Uses the 5-minute load average for stability (1-min spikes with each
	 * REST request and gives misleading readings on shared hosts).
	 * Result is cached for 30 seconds to avoid self-inflicted spikes.
	 *
	 * @return int Percentage (0–100+).
	 */
	public static function get_cpu_percent() {
		$cached = get_transient( 'wpepp_cpu_percent' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$load = self::get_load_average();
		if ( empty( $load ) ) {
			return -1;
		}

		$cores = self::get_cpu_cores();
		if ( $cores < 1 ) {
			$cores = 1;
		}

		// Use 5-minute average instead of 1-minute for stable readings.
		$percent = (int) round( ( $load['load_5'] / $cores ) * 100 );

		set_transient( 'wpepp_cpu_percent', $percent, 30 );

		return $percent;
	}

	/**
	 * Get the number of CPU cores.
	 *
	 * @return int
	 */
	public static function get_cpu_cores() {
		if ( PHP_OS_FAMILY === 'Linux' && is_readable( '/proc/cpuinfo' ) ) {
			$cpuinfo = file_get_contents( '/proc/cpuinfo' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local system file.
			if ( false !== $cpuinfo ) {
				return max( 1, substr_count( $cpuinfo, 'processor' ) );
			}
		}

		if ( PHP_OS_FAMILY === 'Windows' ) {
			$cores = getenv( 'NUMBER_OF_PROCESSORS' );
			if ( false !== $cores ) {
				return max( 1, (int) $cores );
			}
		}

		return 1;
	}

	/**
	 * Get PHP memory usage stats.
	 *
	 * @return array
	 */
	public static function get_memory_info() {
		$current = memory_get_usage( true );
		$peak    = memory_get_peak_usage( true );
		$limit   = self::parse_php_size( ini_get( 'memory_limit' ) );

		$wp_limit = defined( 'WP_MEMORY_LIMIT' ) ? self::parse_php_size( WP_MEMORY_LIMIT ) : $limit;

		return [
			'current'      => $current,
			'peak'         => $peak,
			'limit'        => $limit,
			'wp_limit'     => $wp_limit,
			'current_mb'   => round( $current / 1048576, 1 ),
			'peak_mb'      => round( $peak / 1048576, 1 ),
			'limit_mb'     => $limit >= PHP_INT_MAX ? -1 : round( $limit / 1048576, 1 ),
			'wp_limit_mb'  => $wp_limit >= PHP_INT_MAX ? -1 : round( $wp_limit / 1048576, 1 ),
			'usage_percent' => ( $limit > 0 && $limit < PHP_INT_MAX ) ? (int) round( ( $current / $limit ) * 100 ) : 0,
		];
	}

	/**
	 * Get overall system stats.
	 *
	 * @return array
	 */
	public static function get_stats() {
		$load        = self::get_load_average();
		$cpu_percent = self::get_cpu_percent();
		$memory      = self::get_memory_info();

		// Health score: green (0–60%), yellow (60–85%), red (85%+).
		$health = 'green';
		if ( $cpu_percent >= 85 || $memory['usage_percent'] >= 85 ) {
			$health = 'red';
		} elseif ( $cpu_percent >= 60 || $memory['usage_percent'] >= 60 ) {
			$health = 'yellow';
		}

		return [
			'load'        => $load,
			'cpu_percent' => $cpu_percent,
			'cpu_cores'   => self::get_cpu_cores(),
			'memory'      => $memory,
			'health'      => $health,
			'php_version' => PHP_VERSION,
			'server'      => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
		];
	}

	/**
	 * Parse a PHP size string (e.g. "256M") into bytes.
	 *
	 * @param string $size PHP ini size value.
	 * @return int Bytes.
	 */
	private static function parse_php_size( $size ) {
		$size = trim( (string) $size );
		if ( '' === $size || '-1' === $size ) {
			return PHP_INT_MAX;
		}

		$last = strtolower( substr( $size, -1 ) );
		$val  = (int) $size;

		switch ( $last ) {
			case 'g':
				$val *= 1024;
				// fall through.
			case 'm':
				$val *= 1024;
				// fall through.
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
}
