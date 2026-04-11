<?php
/**
 * CPU Monitor — Cron Job Monitor.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Cron_Monitor
 */
class WPEPP_CPU_Cron_Monitor {

	/**
	 * Get all registered cron events with status.
	 *
	 * @return array
	 */
	public static function get_cron_jobs() {
		$crons  = _get_cron_array();
		$events = [];

		if ( ! is_array( $crons ) ) {
			return $events;
		}

		$now = time();

		foreach ( $crons as $timestamp => $cron_hooks ) {
			foreach ( $cron_hooks as $hook => $args_set ) {
				foreach ( $args_set as $sig => $data ) {
					$schedule = $data['schedule'] ?? false;
					$interval = $data['interval'] ?? 0;

					$events[] = [
						'hook'      => sanitize_text_field( $hook ),
						'timestamp' => (int) $timestamp,
						'next_run'  => gmdate( 'Y-m-d H:i:s', (int) $timestamp ),
						'schedule'  => $schedule ?: __( 'One-time', 'wp-edit-password-protected' ),
						'interval'  => (int) $interval,
						'overdue'   => (int) $timestamp < $now,
						'sig'       => sanitize_text_field( $sig ),
						'args'      => $data['args'] ?? [],
					];
				}
			}
		}

		// Sort: overdue first, then by timestamp ascending.
		usort( $events, static function ( $a, $b ) {
			if ( $a['overdue'] !== $b['overdue'] ) {
				return $b['overdue'] <=> $a['overdue'];
			}
			return $a['timestamp'] <=> $b['timestamp'];
		} );

		return $events;
	}

	/**
	 * Run a specific cron event now.
	 *
	 * Falls back to matching by hook+sig at any timestamp if the exact
	 * timestamp is stale (event was rescheduled between list load and click).
	 *
	 * @param string $hook      Cron hook name.
	 * @param string $sig       Cron event signature.
	 * @param int    $timestamp Scheduled timestamp.
	 * @return bool|WP_Error
	 */
	public static function run_cron_event( $hook, $sig, $timestamp ) {
		$crons = _get_cron_array();

		// Exact match first.
		if ( ! isset( $crons[ $timestamp ][ $hook ][ $sig ] ) ) {
			// Fallback: find by hook + sig at any timestamp (event may have been rescheduled).
			$found = false;
			foreach ( $crons as $ts => $hooks ) {
				if ( isset( $hooks[ $hook ][ $sig ] ) ) {
					$timestamp = $ts;
					$found     = true;
					break;
				}
			}

			if ( ! $found ) {
				return new WP_Error( 'not_found', __( 'Cron event not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
			}
		}

		$event = $crons[ $timestamp ][ $hook ][ $sig ];
		$args  = $event['args'] ?? [];

		do_action_ref_array( $hook, $args );

		return true;
	}

	/**
	 * Delete a specific cron event.
	 *
	 * Falls back to matching by hook+sig at any timestamp if the exact
	 * timestamp is stale (event was rescheduled between list load and click).
	 *
	 * @param string $hook      Cron hook name.
	 * @param string $sig       Cron event signature.
	 * @param int    $timestamp Scheduled timestamp.
	 * @return bool|WP_Error
	 */
	public static function delete_cron_event( $hook, $sig, $timestamp ) {
		$crons = _get_cron_array();

		// Exact match first.
		if ( ! isset( $crons[ $timestamp ][ $hook ][ $sig ] ) ) {
			// Fallback: find by hook + sig at any timestamp (event may have been rescheduled).
			$found = false;
			foreach ( $crons as $ts => $hooks ) {
				if ( isset( $hooks[ $hook ][ $sig ] ) ) {
					$timestamp = $ts;
					$found     = true;
					break;
				}
			}

			if ( ! $found ) {
				return new WP_Error( 'not_found', __( 'Cron event not found.', 'wp-edit-password-protected' ), [ 'status' => 404 ] );
			}
		}

		$args = $crons[ $timestamp ][ $hook ][ $sig ]['args'] ?? [];
		wp_unschedule_event( $timestamp, $hook, $args );

		return true;
	}

	/**
	 * Get count of overdue cron events.
	 *
	 * @return int
	 */
	public static function get_overdue_count() {
		$crons = _get_cron_array();
		$now   = time();
		$count = 0;

		if ( ! is_array( $crons ) ) {
			return 0;
		}

		foreach ( $crons as $timestamp => $hooks ) {
			if ( (int) $timestamp < $now ) {
				foreach ( $hooks as $hook_events ) {
					$count += count( $hook_events );
				}
			}
		}

		return $count;
	}

	/**
	 * Check if WP-Cron is using the alternate (page-visit) method.
	 *
	 * @return bool
	 */
	public static function is_alternate_cron() {
		return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	}
}
