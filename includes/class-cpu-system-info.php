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
		if ( false !== $cached && is_numeric( $cached ) ) {
			return min( 100, max( 0, (int) $cached ) );
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

			// Use 5-minute average for stable readings; clamp to 0-100%.
			$percent = min( 100, max( 0, (int) round( ( $load['load_5'] / $cores ) * 100 ) ) );
		} else {
			$percent = min( 100, max( 0, $percent ) );
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
	 * CPU usage on Windows via WMI (Win32_Processor.LoadPercentage) or wmic.
	 *
	 * @return int Percentage (0–100), or -1 when unavailable.
	 */
	private static function windows_cpu_percent() {
		if ( class_exists( 'COM' ) ) {
			try {
				$wmi = new COM( 'winmgmts:{impersonationLevel=impersonate}!\\.\root\CIMV2' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- COM is the only WMI bridge in PHP.
				foreach ( $wmi->ExecQuery( 'SELECT LoadPercentage FROM Win32_Processor' ) as $cpu ) {
					$pct = (int) $cpu->LoadPercentage;
					if ( $pct >= 0 && $pct <= 100 ) {
						return $pct;
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
						return min( 100, max( 0, (int) $line ) );
					}
				}
			}
		}

		return -1;
	}

	/**
	 * CPU usage on Linux via /proc/stat delta sampling.
	 *
	 * Prefers comparing against a previous snapshot taken on an earlier request
	 * (zero request delay, smooth rolling average matching cPanel/top).
	 * Falls back to a short 150ms delta if no prior snapshot exists.
	 *
	 * @return int Percentage (0–100), or -1 when procfs is unavailable.
	 */
	private static function linux_cpu_percent() {
		$snap = static function () {
			if ( ! is_readable( '/proc/stat' ) ) {
				return null;
			}

			$line = (string) @file_get_contents( '/proc/stat' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local kernel file.
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

		$now = $snap();
		if ( null === $now ) {
			return -1;
		}

		// Check for a rolling snapshot from the previous request (1s to 60s ago).
		$prev = get_transient( 'wpepp_proc_stat_snapshot' );
		if ( is_array( $prev ) && isset( $prev['idle'], $prev['total'], $prev['time'] ) ) {
			$time_delta = microtime( true ) - (float) $prev['time'];
			if ( $time_delta >= 1.0 && $time_delta <= 60.0 ) {
				$d_total = $now[1] - (int) $prev['total'];
				$d_idle  = $now[0] - (int) $prev['idle'];

				if ( $d_total > 0 && $d_total >= $d_idle ) {
					set_transient(
						'wpepp_proc_stat_snapshot',
						[
							'idle'  => $now[0],
							'total' => $now[1],
							'time'  => microtime( true ),
						],
						120
					);
					return min( 100, max( 0, (int) round( ( ( $d_total - $d_idle ) / $d_total ) * 100 ) ) );
				}
			}
		}

		// First run / expired snapshot: take a quick delta and store the snapshot.
		usleep( 150000 );
		$b = $snap();
		if ( null === $b ) {
			return -1;
		}

		set_transient(
			'wpepp_proc_stat_snapshot',
			[
				'idle'  => $b[0],
				'total' => $b[1],
				'time'  => microtime( true ),
			],
			120
		);

		$d_total = $b[1] - $now[1];
		$d_idle  = $b[0] - $now[0];

		if ( $d_total <= 0 ) {
			return -1;
		}

		return min( 100, max( 0, (int) round( ( ( $d_total - $d_idle ) / $d_total ) * 100 ) ) );
	}

	/**
	 * Get the number of CPU cores.
	 *
	 * Robustly detects host/container cores on Linux (online CPU map,
	 * procfs, nproc) and Windows to ensure load-average heuristics
	 * accurately reflect multi-core capacity in shared hosting.
	 *
	 * @return int
	 */
	public static function get_cpu_cores() {
		if ( PHP_OS_FAMILY === 'Linux' ) {
			// 1. Try /sys/devices/system/cpu/online (e.g. "0-7" or "0,1,2,3")
			if ( is_readable( '/sys/devices/system/cpu/online' ) ) {
				$online = trim( (string) @file_get_contents( '/sys/devices/system/cpu/online' ) );
				if ( preg_match( '/^(\d+)-(\d+)$/', $online, $m ) ) {
					return max( 1, ( (int) $m[2] - (int) $m[1] + 1 ) );
				} elseif ( '' !== $online && ctype_digit( $online ) ) {
					return 1;
				} elseif ( false !== strpos( $online, ',' ) ) {
					return max( 1, count( explode( ',', $online ) ) );
				}
			}

			// 2. Try /proc/cpuinfo counting distinct processor lines
			if ( is_readable( '/proc/cpuinfo' ) ) {
				$cpuinfo = (string) @file_get_contents( '/proc/cpuinfo' );
				if ( '' !== $cpuinfo ) {
					$count = preg_match_all( '/^processor\s*:\s*\d+/m', $cpuinfo );
					if ( $count > 0 ) {
						return $count;
					}
				}
			}

			// 3. Try nproc via shell_exec if available
			if ( function_exists( 'shell_exec' ) ) {
				$nproc = @shell_exec( 'nproc 2>/dev/null' );
				if ( null !== $nproc && '' !== trim( $nproc ) && ctype_digit( trim( $nproc ) ) ) {
					return max( 1, (int) trim( $nproc ) );
				}
			}
		}

		if ( PHP_OS_FAMILY === 'Windows' ) {
			$cores = getenv( 'NUMBER_OF_PROCESSORS' );
			if ( false !== $cores && ctype_digit( (string) $cores ) ) {
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
			'usage_percent' => ( $limit > 0 && $limit < PHP_INT_MAX ) ? min( 100, (int) round( ( $current / $limit ) * 100 ) ) : 0,
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

		// Health score:
		// CPU: green (< 70%), yellow (70–89%), red (90%+).
		// PHP Memory: memory_limit is a script ceiling, not system RAM.
		// Yellow warning at 85-94%, Red critical only if >= 95% (immediate risk of fatal OOM).
		$health  = 'green';
		$mem_pct = $memory['usage_percent'] ?? 0;

		if ( ( $cpu_percent >= 90 ) || ( $mem_pct >= 95 ) ) {
			$health = 'red';
		} elseif ( ( $cpu_percent >= 70 ) || ( $mem_pct >= 85 ) ) {
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
