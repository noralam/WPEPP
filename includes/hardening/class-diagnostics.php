<?php
/**
 * Diagnostics — environment, connectivity, cron, filesystem checks.
 *
 * Stateless: every run recomputes; results are not persisted.
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Diagnostics
 */
class WPEPP_Diagnostics {

	/**
	 * Run all checks.
	 *
	 * @return array[] {group,label,status(ok|warn|fail|info),detail}
	 */
	public static function run() {
		return [
			'Environment'  => self::env_checks(),
			'Connectivity' => self::connectivity_checks(),
			'Cron'         => self::cron_checks(),
			'Filesystem'   => self::fs_checks(),
		];
	}

	private static function env_checks() {
		global $wp_version;

		$checks = [];

		$checks[] = [
			'label'  => 'PHP version',
			'status' => version_compare( PHP_VERSION, '7.4', '>=' ) ? ( version_compare( PHP_VERSION, '8.0', '>=' ) ? 'ok' : 'warn' ) : 'fail',
			'detail' => PHP_VERSION,
		];

		$memory = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : ini_get( 'memory_limit' );
		$bytes  = wp_convert_hr_to_bytes( (string) $memory );

		$checks[] = [
			'label'  => 'Memory limit',
			'status' => $bytes >= 128 * MB_IN_BYTES ? 'ok' : 'warn',
			'detail' => (string) $memory,
		];

		$max_exec = (int) ini_get( 'max_execution_time' );

		$checks[] = [
			'label'  => 'Max execution time',
			'status' => 0 === $max_exec || $max_exec >= 30 ? 'ok' : 'warn',
			'detail' => $max_exec . 's',
		];

		$checks[] = [
			'label'  => 'WordPress version',
			'status' => version_compare( (string) $wp_version, '6.0', '>=' ) ? 'ok' : 'warn',
			'detail' => (string) $wp_version,
		];

		$free = @disk_free_space( ABSPATH ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $free && null !== $free ) {
			$gb    = $free / GB_IN_BYTES;
			$checks[] = [
				'label'  => 'Disk free',
				'status' => $gb > 1 ? 'ok' : ( $gb > 0.2 ? 'warn' : 'fail' ),
				'detail' => round( $gb, 2 ) . ' GB',
			];
		}

		// Detected IP + trusted proxy state.
		$ip        = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : '';
		$is_cli_ip = in_array( $ip, [ '', '0.0.0.0', '::1', '127.0.0.1' ], true );

		$checks[] = [
			'label'  => 'Detected client IP',
			'status' => $is_cli_ip ? 'info' : 'ok',
			'detail' => $ip . ( $is_cli_ip ? ' (CLI/local request)' : '' ),
		];

		// Email & SMTP delivery status.
		if ( class_exists( 'WPEPP_Alerts' ) ) {
			$smtp_status = WPEPP_Alerts::detect_smtp();
			$checks[]    = [
				'label'  => 'Email / SMTP delivery',
				'status' => $smtp_status['configured'] ? 'ok' : 'warn',
				'detail' => $smtp_status['provider'] . ( $smtp_status['configured'] ? '' : ' (Recommend configuring an SMTP plugin for reliable alerts)' ),
			];
		}

		return $checks;
	}

	private static function connectivity_checks() {
		$checks = [];

		// WordPress.org API reachability (checksums endpoint HEAD-ish).
		$res = wp_remote_get(
			'https://api.wordpress.org/core/checksums/1.0/?version=' . get_bloginfo( 'version' ),
			[ 'timeout' => 6 ]
		);

		$checks[] = [
			'label'  => 'api.wordpress.org',
			'status' => ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) ? 'ok' : 'fail',
			'detail' => is_wp_error( $res ) ? $res->get_error_message() : 'reachable',
		];

		// Loopback to own site.
		$loop = wp_remote_get(
			add_query_arg( 'wpepp_diag', wp_generate_password( 8, false ), home_url( '/' ) ),
			[
				'timeout'    => 6,
				'sslverify'  => true,
				'blocking'   => true,
			]
		);

		$loop_code = is_wp_error( $loop ) ? 0 : (int) wp_remote_retrieve_response_code( $loop );

		$checks[] = [
			'label'  => 'Loopback request',
			'status' => $loop_code >= 200 && $loop_code < 400 ? 'ok' : 'warn',
			'detail' => $loop_code ? (string) $loop_code : ( is_wp_error( $loop ) ? $loop->get_error_message() : 'no response' ),
		];

		// TLS sanity for admin.
		$checks[] = [
			'label'  => 'Admin over HTTPS',
			'status' => is_ssl() ? 'ok' : 'warn',
			'detail' => is_ssl() ? 'yes' : 'no',
		];

		return $checks;
	}

	private static function cron_checks() {
		$crons  = _get_cron_array();
		$total  = 0;
		$overdue = 0;
		$now     = time();

		foreach ( (array) $crons as $ts => $hooks ) {
			foreach ( (array) $hooks as $hook => $args_sets ) {
				$total += count( (array) $args_sets );

				if ( $ts < $now - HOUR_IN_SECONDS * 6 ) {
					$overdue++;
				}
			}
		}

		return [
			[
				'label'  => 'Scheduled events',
				'status' => 'ok',
				'detail' => (string) $total,
			],
			[
				'label'  => 'Overdue events (>6h)',
				'status' => $overdue > 5 ? 'warn' : 'ok',
				'detail' => (string) $overdue,
			],
			[
				'label'  => 'WP-Cron constant',
				'status' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'warn' : 'ok',
				'detail' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'DISABLE_WP_CRON=true — ensure a system cron hits wp-cron.php' : 'default',
			],
		];
	}

	private static function fs_checks() {
		$upload_dir = wp_get_upload_dir();
		$root       = ABSPATH;

		return [
			[
				'label'  => 'uploads/ writable',
				'status' => wp_is_writable( $upload_dir['basedir'] ) ? 'ok' : 'fail',
				'detail' => $upload_dir['basedir'],
			],
			[
				'label'  => '.htaccess present',
				'status' => file_exists( $root . '.htaccess' ) ? 'ok' : 'info',
				'detail' => file_exists( $root . '.htaccess' ) ? 'yes' : 'no (nginx or missing)',
			],
			[
				'label'  => 'DISALLOW_FILE_EDIT',
				'status' => class_exists( 'WPEPP_File_Hardening' ) && ( WPEPP_File_Hardening::status()['file_edit_disabled'] ?? false )
					? 'ok'
					: 'warn',
				'detail' => '',
			],
		];
	}
}
