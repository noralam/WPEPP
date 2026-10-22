<?php
/**
 * WP-CLI commands.
 *
 *   wp wpepp score
 *   wp wpepp scan start [--full] | status | stop
 *   wp wpepp block add <ip|cidr> [--reason=...] [--duration=permanent|1h|24h|7d]
 *   wp wpepp block list [--type=...] [--expired]
 *   wp wpepp block remove <id>
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CLI
 */
class WPEPP_CLI {

	/**
	 * Register commands.
	 */
	public static function register() {
		\WP_CLI::add_command( 'wpepp', self::class );
	}

	/**
	 * Show the security score.
	 *
	 * ## EXAMPLES
	 *     wp wpepp score
	 */
	public function score() {
		$s = WPEPP_Security_Score::get_summary();

		\WP_CLI::line( sprintf( 'Security score: %d/100 (grade %s)', $s['score'], $s['grade'] ) );
		\WP_CLI::line( sprintf( 'Checks passing: %d/%d', $s['passed'], $s['total'] ) );

		foreach ( $s['items'] as $item ) {
			if ( ! $item['done'] ) {
				\WP_CLI::colorize( sprintf( '  %%R✗%%n %s (%s)', $item['label'], $item['route'] ) );
			}
		}
	}

	/**
	 * Scanner control.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : start|status|stop
	 *
	 * [--full]
	 * : Run a full scan instead of quick.
	 *
	 * ## EXAMPLES
	 *     wp wpepp scan start --full
	 *     wp wpepp scan status
	 */
	public function scan( $args, $assoc_args ) {
		$action = $args[0] ?? 'status';

		switch ( $action ) {

			case 'start':
				$profile = \WP_CLI\Utils\get_flag_value( $assoc_args, 'full' ) ? 'full' : 'quick';
				WPEPP_Scan_Engine::start( $profile );
				\WP_CLI::log( "Scan started ($profile)." );

				// Drive slices until done (CLI has no time pressure like HTTP).
				$guard = 0;

				while ( WPEPP_Scan_Engine::is_running() && $guard++ < 1000 ) {
					WPEPP_Scan_Engine::tick();
					$s = WPEPP_Scan_Engine::state();
					\WP_CLI::log( sprintf( '  [%s] scanned %d…', $s['stage'], $s['scanned'] ) );
				}

				$state = WPEPP_Scan_Engine::state();
				\WP_CLI::success( sprintf( 'Done. %d findings.', (int) ( $state['flagged'] ?? 0 ) ) );
				break;

			case 'status':
				$s = WPEPP_Scan_Engine::state();
				\WP_CLI::line(
					sprintf(
						'running: %s · stage: %s · profile: %s · scanned: %d · flagged: %d',
						! empty( $s['running'] ) ? 'yes' : 'no',
						$s['stage'] ?? '-',
						$s['profile'] ?? '-',
						(int) ( $s['scanned'] ?? 0 ),
						(int) ( $s['flagged'] ?? 0 )
					)
				);
				break;

			case 'stop':
				WPEPP_Scan_Engine::stop();
				\WP_CLI::success( 'Scan stopped.' );
				break;

			default:
				\WP_CLI::error( "Unknown action '$action'. Use start|status|stop." );
		}
	}

	/**
	 * Block management.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : add|list|remove
	 *
	 * [<ip>]
	 * : IP or CIDR (add) or block id (remove).
	 *
	 * [--reason=<reason>]
	 * : Why this address is blocked.
	 *
	 * [--duration=<duration>]
	 * : permanent|1h|24h|7d
	 *
	 * [--type=<type>]
	 * : Filter list by block type.
	 *
	 * [--expired]
	 * : Include expired blocks in list.
	 *
	 * ## EXAMPLES
	 *     wp wpepp block add 203.0.113.9 --reason="spam bot" --duration=24h
	 */
	public function block( $args, $assoc_args ) {
		$action = $args[0] ?? 'list';

		switch ( $action ) {

			case 'add':
				if ( empty( $args[1] ) ) {
					\WP_CLI::error( 'Usage: wp wpepp block add <ip|cidr>' );
				}

				$durations = [ 'permanent' => null, '1h' => HOUR_IN_SECONDS, '24h' => DAY_IN_SECONDS, '7d' => 7 * DAY_IN_SECONDS ];
				$duration  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'duration', 'permanent' );
				$expires   = isset( $durations[ $duration ] ) && $durations[ $duration ]
					? time() + $durations[ $duration ]
					: null;

				$result = WPEPP_Blocks::add_block(
					$args[1],
					[
						'type'    => 'manual',
						'reason'  => (string) ( $assoc_args['reason'] ?? '' ),
						'expires' => $expires,
					]
				);

				if ( is_wp_error( $result ) ) {
					\WP_CLI::error( $result->get_error_message() );
				}

				\WP_CLI::success( "Blocked #$result." );
				break;

			case 'list':
				$rows = WPEPP_Blocks::list_blocks(
					[
						'type'            => (string) ( $assoc_args['type'] ?? '' ),
						'include_expired' => ! empty( $assoc_args['expired'] ),
						'per_page'        => 200,
					]
				);

				if ( ! $rows ) {
					\WP_CLI::line( 'No blocks.' );
					return;
				}

				foreach ( $rows as $row ) {
					\WP_CLI::line(
						sprintf(
							'#%-5d %-22s %-10s exp:%-16s hits:%-6d %s',
							$row['id'],
							$row['cidr'],
							$row['type'],
							$row['expires'] ?: 'never',
							$row['hit_count'],
							$row['reason']
						)
					);
				}
				break;

			case 'remove':
				if ( empty( $args[1] ) ) {
					\WP_CLI::error( 'Usage: wp wpepp block remove <id>' );
				}

				\WP_CLI::success( WPEPP_Blocks::remove_block( absint( $args[1] ) ) ? 'Removed.' : 'Not found.' );
				break;

			default:
				\WP_CLI::error( "Unknown action '$action'. Use add|list|remove." );
		}
	}

	/**
	 * Emergency safe-mode management.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : enable|disable|status
	 *
	 * ## EXAMPLES
	 *     wp wpepp safe-mode enable
	 *     wp wpepp safe-mode status
	 *     wp wpepp safe-mode disable
	 *
	 * @subcommand safe-mode
	 */
	public function safe_mode( $args, $assoc_args ) {
		$action    = $args[0] ?? 'status';
		$flag_file = WP_CONTENT_DIR . '/wpepp-safe-mode.txt';

		switch ( $action ) {
			case 'enable':
				if ( ! file_exists( $flag_file ) ) {
					global $wp_filesystem;
					if ( empty( $wp_filesystem ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						WP_Filesystem();
					}
					$written = $wp_filesystem ? $wp_filesystem->put_contents( $flag_file, 'WPEPP Safe Mode enabled on ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n" ) : false;
					if ( false === $written ) {
						\WP_CLI::error( "Failed to create {$flag_file}. Check directory permissions." );
					}
				}
				\WP_CLI::success( 'Emergency Safe Mode ENABLED. All firewalls, login lockouts, and rate limits are now bypassed.' );
				\WP_CLI::line( "Flag file created at: {$flag_file}" );
				break;

			case 'disable':
				$removed        = false;
				$possible_files = [
					WP_CONTENT_DIR . '/wpepp-safe-mode.txt',
					WP_CONTENT_DIR . '/wpepp-safe-mode',
					ABSPATH . 'wpepp-safe-mode.txt',
				];
				foreach ( $possible_files as $f ) {
					if ( file_exists( $f ) ) {
						wp_delete_file( $f );
						$removed = true;
					}
				}
				if ( defined( 'WPEPP_SAFE_MODE' ) && WPEPP_SAFE_MODE ) {
					\WP_CLI::warning( 'Constant WPEPP_SAFE_MODE is defined in wp-config.php. Remove or set it to false to completely exit Safe Mode.' );
				}
				if ( $removed ) {
					\WP_CLI::success( 'Safe mode flag file(s) removed. Normal security protections restored.' );
				} else {
					\WP_CLI::line( 'No safe mode flag files were found.' );
				}
				break;

			case 'status':
			default:
				$is_active = class_exists( 'WPEPP_Security' ) ? WPEPP_Security::is_safe_mode() : false;
				if ( $is_active ) {
					\WP_CLI::warning( 'Safe Mode is currently ACTIVE! Security protections, firewalls, and lockouts are bypassed.' );
					if ( defined( 'WPEPP_SAFE_MODE' ) && WPEPP_SAFE_MODE ) {
						\WP_CLI::line( '- Triggered by: Constant WPEPP_SAFE_MODE = true' );
					}
					if ( file_exists( WP_CONTENT_DIR . '/wpepp-safe-mode.txt' ) ) {
						\WP_CLI::line( '- Triggered by: ' . WP_CONTENT_DIR . '/wpepp-safe-mode.txt' );
					}
					if ( file_exists( ABSPATH . 'wpepp-safe-mode.txt' ) ) {
						\WP_CLI::line( '- Triggered by: ' . ABSPATH . 'wpepp-safe-mode.txt' );
					}
				} else {
					\WP_CLI::success( 'Safe Mode is INACTIVE. Normal protection is active.' );
				}
				break;
		}
	}
}
