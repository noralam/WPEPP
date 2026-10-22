<?php
/**
 * Scan engine — chunked, resumable state machine.
 *
 * A scan runs in small slices (default 5s wall budget) across multiple
 * requests so it survives shared-hosting process recycling. State lives in
 * one option; the file walker resumes from a path cursor.
 *
 * Stages: files → integrity → options → users
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Scan_Engine
 */
class WPEPP_Scan_Engine {

	/**
	 * State option key.
	 *
	 * @var string
	 */
	const STATE_KEY = 'wpepp_scan_state';

	/**
	 * Wall-clock budget per slice (seconds).
	 *
	 * @var float
	 */
	const SLICE_BUDGET = 5.0;

	/**
	 * Stale scan threshold — watchdog marks failed after this.
	 *
	 * @var int
	 */
	const STALE_SECONDS = 900;

	/**
	 * Get the current state array.
	 *
	 * @return array
	 */
	public static function state() {
		$state = json_decode( (string) get_option( self::STATE_KEY, '{}' ), true );
		return is_array( $state ) ? $state : [];
	}

	private static function save_state( array $state ) {
		update_option( self::STATE_KEY, wp_json_encode( $state ), false );
	}

	/**
	 * Whether a scan is running.
	 *
	 * @return bool
	 */
	public static function is_running() {
		$state = self::state();
		return ! empty( $state['running'] );
	}

	/**
	 * Start a new scan.
	 *
	 * @param string $profile quick|full.
	 * @return true|\WP_Error
	 */
	public static function start( $profile = 'quick' ) {
		global $wpdb;

		if ( ! in_array( $profile, [ 'quick', 'full' ], true ) ) {
			$profile = 'quick';
		}

		if ( ! WPEPP_Issues::table_exists( true ) ) {
			return new \WP_Error( 'wpepp_scan_no_table', __( 'Could not create the issues table.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		self::save_state(
			[
				'running'     => true,
				'profile'     => $profile,
				'stage'       => 'files',
				'cursor'      => '',           // File walker resume point.
				'scanned'     => 0,
				'flagged'     => 0,
				'started_at'  => time(),
				'tick_at'     => time(),
				'checksums'   => [],           // Cached remote checksum payloads per slug.
			]
		);

		do_action( 'wpepp/scan/started', $profile ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.

		return true;
	}

	/**
	 * Stop a running scan.
	 */
	public static function stop() {
		$state         = self::state();
		$state['running'] = false;
		$state['stage']   = 'stopped';
		self::save_state( $state );
	}

	/**
	 * Concurrency lock key — prevents overlapping slices.
	 *
	 * @var string
	 */
	const LOCK_KEY = 'wpepp_scan_lock';

	/**
	 * Whether a slice may start right now (non-reentrant).
	 *
	 * @return bool
	 */
	public static function acquire_lock() {
		if ( get_transient( self::LOCK_KEY ) ) {
			return false;
		}

		set_transient( self::LOCK_KEY, (string) getmypid(), 90 );

		return true;
	}

	/**
	 * Release the slice lock.
	 */
	public static function release_lock() {
		delete_transient( self::LOCK_KEY );
	}

	/**
	 * Process one slice of the scan. Safe to call on every request.
	 *
	 * Non-reentrant: a second concurrent caller bails immediately instead
	 * of stacking another 5s filesystem walk on top of the running one.
	 *
	 * @param bool $force Bypass the front-end-only context gate (REST tick endpoint).
	 * @return bool True when more work remains.
	 */
	public static function tick( $force = false ) {
		$state = self::state();

		if ( empty( $state['running'] ) ) {
			return false;
		}

		// Slices run ONLY from: explicit REST tick calls, the SPA-driven
		// loop, and the cron watchdog. Never opportunistically during
		// admin/AJAX requests — that caused request pileups and nonce churn.
		if ( ! $force && ( is_admin() || wp_doing_ajax() ) ) {
			return false;
		}

		if ( ! self::acquire_lock() ) {
			return true; // Another slice in flight; work remains.
		}

		try {
			return self::run_slice( $state );
		} finally {
			self::release_lock();
		}
	}

	/**
	 * Execute one processing slice (caller must hold the lock).
	 *
	 * @param array $state Current state snapshot.
	 * @return bool True when more work remains.
	 */
	private static function run_slice( array $state ) {
		$start = microtime( true );

		while ( microtime( true ) - $start < self::SLICE_BUDGET ) {
			$state = self::state();

			if ( empty( $state['running'] ) ) {
				return false;
			}

			switch ( $state['stage'] ) {

				case 'files':
					$more = WPEPP_Signatures::process_slice( $state, $start );
					break;

				case 'integrity':
					$more = WPEPP_Integrity::process_slice( $state, $start );
					break;

				case 'options':
					WPEPP_Option_Scan::run();
					$state             = self::state();
					$state['stage']    = 'users';
					$more              = true;
					break;

				case 'users':
					WPEPP_User_Scan::run();
					$state          = self::state();
					$state['stage'] = '';
					self::finish( $state );
					return false;

				default:
					self::finish( $state );
					return false;
			}

			if ( ! $more ) {
				return true; // Budget spent — continue next request.
			}
		}

		return true;
	}

	/**
	 * Advance stage / persist counters.
	 *
	 * @param array  $state Current state.
	 * @param string $next  Next stage key.
	 */
	public static function advance( array $state, $next ) {
		$state['stage']  = $next;
		$state['cursor'] = '';
		self::save_state( $state );
	}

	/**
	 * Persist a slice's mutated state.
	 *
	 * @param array $state State.
	 */
	public static function persist( array $state ) {
		$state['tick_at'] = time();
		self::save_state( $state );
	}

	/**
	 * Mark completion.
	 *
	 * @param array $state Final state.
	 */
	private static function finish( array $state ) {
		$state['running']    = false;
		$state['stage']      = 'done';
		$state['finished_at'] = time();

		update_option( 'wpepp_last_scan', gmdate( 'Y-m-d H:i:s', time() ), false );

		self::save_state( $state );

		do_action( 'wpepp/scan/finished', $state ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.
	}

	/* ─── Scheduling ─── */

	/**
	 * Schedule daily automatic scans + hourly watchdog when enabled.
	 */
	public static function schedule() {
		$s       = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		$enabled = is_array( $s ) ? ! empty( $s['scan_schedule_enabled'] ) : false;

		add_filter( 'wpepp/security/score_items', [ __CLASS__, 'score_item' ] );

		if ( $enabled && ! wp_next_scheduled( 'wpepp_scan_daily' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:00' ), 'daily', 'wpepp_scan_daily' );
		} elseif ( ! $enabled && wp_next_scheduled( 'wpepp_scan_daily' ) ) {
			wp_clear_scheduled_hook( 'wpepp_scan_daily' );
		}

		add_action( 'wpepp_scan_daily', [ __CLASS__, 'run_cron_scan' ] );

		if ( ! wp_next_scheduled( 'wpepp_scan_watchdog' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'wpepp_scan_watchdog' );
		}
		add_action( 'wpepp_scan_watchdog', [ __CLASS__, 'watchdog' ] );
	}

	/**
	 * Checklist item — recent successful scan (Layer 3 integration).
	 *
	 * @param array[] $items Existing items.
	 * @return array[]
	 */
	public static function score_item( $items ) {
		$last = get_option( 'wpepp_last_scan', '' );
		$fresh = '' !== $last && ( time() - strtotime( $last . ' UTC' ) ) < 7 * DAY_IN_SECONDS;

		$items[] = [
			'id'       => 'scan_recent',
			'label'    => __( 'Malware scan run in the last 7 days', 'wp-edit-password-protected' ),
			'weight'   => 8,
			'category' => 'monitor',
			'done'     => $fresh,
			'route'    => '/scanner',
		];

		return $items;
	}

	/**
	 * Cron callback — start a quick scan if none running.
	 */
	public static function run_cron_scan() {
		if ( class_exists( 'WPEPP_Signatures' ) ) {
			WPEPP_Signatures::sync_signatures();
		}
		if ( ! self::is_running() ) {
			self::start( 'quick' );
		}
		self::tick();
	}

	/**
	 * Watchdog — restart slices for a live scan; fail stale ones.
	 */
	public static function watchdog() {
		$state = self::state();

		if ( empty( $state['running'] ) ) {
			return;
		}

		if ( time() - (int) ( $state['tick_at'] ?? 0 ) > self::STALE_SECONDS ) {
			$state['running'] = false;
			$state['stage']   = 'stalled';
			self::save_state( $state );

			do_action( 'wpepp/scan/stalled', $state ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.
			return;
		}

		// Keep pushing progress on cron cycles too.
		self::tick();
	}
}
