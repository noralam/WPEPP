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
	 * Not available on Windows (sys_getloadavg is a no-op there) — callers
	 * treat an empty array as "N/A".
	 *
	 * @return array{load_1: float, load_5: float, load_15: float}|array{}
	 */
	public static function get_load_average() {
		if ( PHP_OS_FAMILY === 'Windows' ) {
			return [];
		}

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
	 * Prefers a real measurement (procfs sampling on Linux, WMI on Windows)
	 * and only falls back to the load-average heuristic when neither is
	 * available. Result is cached for 30 seconds to avoid self-inflicted
	 * spikes.
	 *
	 * @return int Percentage (0–100), or -1 when unknown.
	 */
	public static function get_cpu_percent() {
		$cached = get_transient( 'wpepp_cpu_percent' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$percent = self::measure_cpu_percent();

		if ( -1 === $percent ) {
			// Heuristic fallback: load average vs core count.
			$load = self::get_load_average();
			if ( empty( $load ) ) {
				return -1;
			}

			$cores = self::get_cpu_cores();
			if ( $cores < 1 ) {
				$cores = 1;
			}

			// Use 5-minute average instead of 1-minute for stable readings.
			$percent = max( 0, (int) round( ( $load['load_5'] / $cores ) * 100 ) );
		}

		set_transient( 'wpepp_cpu_percent', $percent, 30 );

		return $percent;
	}

	/**
	 * Measure real CPU utilisation for the current platform.
	 *
	 * @return int Percentage (0–100), or -1 when no native source exists.
	 */
	private static function measure_cpu_percent() {
		if ( PHP_OS_FAMILY === 'Windows' ) {
			return self::windows_cpu_percent();
		}

		if ( PHP_OS_FAMILY === 'Linux' ) {
			return self::linux_cpu_percent();
		}

		return -1;
	}

	/**
	 * CPU usage on Windows via WMI (Win32_Processor.LoadPercentage).
	 *
	 * @return int Percentage (0–100), or -1 when unavailable.
	 */
	private static function windows_cpu_percent() {
		if ( class_exists( 'COM' ) ) {
			try {
				$wmi = new COM( 'winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\CIMV2' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- COM is the only WMI bridge in PHP.
				foreach ( $wmi->ExecQuery( 'SELECT LoadPercentage FROM Win32_Processor' ) as $cpu ) {
					$pct = (int) $cpu->LoadPercentage;
					if ( $pct >= 0 ) {
						return min( 100, $pct );
					}
				}
			} catch ( \Throwable $e ) { // phpcs:ignore Squiz.Commenting -- COM can throw when the extension is disabled.
				// Fall through to wmic.
			}
		}

		if ( function_exists( 'exec' ) ) {
			$out = [];
			@exec( 'wmic cpu get loadpercentage 2>nul', $out, $code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Last-resort local probe.
			if ( 0 === $code ) {
				foreach ( $out as $line ) {
					$line = trim( (string) $line );
					if ( '' !== $line && ctype_digit( $line ) ) {
						return min( 100, (int) $line );
					}
				}
			}
		}

		return -1;
	}

	/**
	 * CPU usage on Linux via two /proc/stat samples (busy / total delta).
	 *
	 * @return int Percentage (0–100), or -1 when procfs is unavailable.
	 */
	private static function linux_cpu_percent() {
		$snap = static function () {
			if ( ! is_readable( '/proc/stat' ) ) {
				return null;
			}

			$line = (string) file_get_contents( '/proc/stat' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local kernel file.
			$line = (string) strtok( $line, "\n" );

			$parts = preg_split( '/\s+/', trim( substr( $line, 4 ) ) );
			if ( empty( $parts ) ) {
				return null;
			}

			// user nice system idle iowait irq softirq steal ...
			$fields = array_map( 'intval', $parts );
			$idle   = ( $fields[3] ?? 0 ) + ( $fields[4] ?? 0 );
			$total  = array_sum( $fields );

			if ( $total <= 0 ) {
				return null;
			}

			return [ $idle, $total ];
		};

		$a = $snap();
		if ( null === $a ) {
			return -1;
		}

		usleep( 120000 );

		$b = $snap();
		if ( null === $b ) {
			return -1;
		}

		$d_total = $b[1] - $a[1];
		$d_idle  = $b[0] - $a[0];

		if ( $d_total <= 0 ) {
			return -1;
		}

		return (int) round( ( ( $d_total - $d_idle ) / $d_total ) * 100 );
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
