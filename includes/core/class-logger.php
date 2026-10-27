<?php
/**
 * Event logger — structured security event stream.
 *
 * One row per notable occurrence: logins, lockouts, auto-blocks,
 * firewall matches, scan outcomes, alerts sent. Feeds the Activity page,
 * weekly report, and any future offsite export.
 *
 * Housekeeping: daily prune by retention days (default 30) with a hard
 * row cap so a flood can never grow the table unbounded.
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Logger
 */
class WPEPP_Logger {

	/**
	 * Table suffix.
	 */
	const TABLE = 'wpepp_events';

	/**
	 * Severity levels.
	 */
	const SEV_INFO     = 1;
	const SEV_WARN     = 2;
	const SEV_CRIT     = 3;
	const SEV_CRITICAL = 3;

	/**
	 * Known event types (allowlist).
	 *
	 * @var array
	 */
	const TYPES = [
		'login_success', 'login_failed', 'lockout',
		'block_manual', 'block_auto',
		'firewall_blocked', 'firewall_logged',
		'scan_started', 'scan_finished', 'scan_critical',
		'passkey_registered', 'passkey_login',
		'alert_sent', 'settings_changed',
	];

	const HARD_CAP = 50000;

	/**
	 * Full table name.
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create table.
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table_name();

		dbDelta(
			"CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_type VARCHAR(40) NOT NULL DEFAULT '',
			severity TINYINT NOT NULL DEFAULT 1,
			actor_user BIGINT NULL,
			ip VARCHAR(45) NULL,
			summary VARCHAR(255) NOT NULL DEFAULT '',
			context LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			KEY idx_type_time (event_type, created_at),
			KEY idx_sev (severity)
		)"
		);

		wp_cache_delete( 'wpepp_events_exists', 'wpepp' );
	}

	/**
	 * Table existence (cached, optional create).
	 *
	 * @param bool $create Auto-create when missing.
	 * @return bool
	 */
	public static function table_exists( $create = false ) {
		global $wpdb;

		$table  = self::table_name();
		$exists = wp_cache_get( 'wpepp_events_exists', 'wpepp' );

		if ( false === $exists ) {
			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			wp_cache_set( 'wpepp_events_exists', $exists ? $table : 'no', 'wpepp', HOUR_IN_SECONDS );
		}

		if ( $exists && 'no' !== $exists ) {
			return true;
		}

		if ( ! $create ) {
			return false;
		}

		self::create_table();

		return self::table_exists();
	}

	/**
	 * Write an event.
	 *
	 * @param string $type    One of TYPES.
	 * @param string $summary Human sentence fragment ("Locked out 203.0.113.9").
	 * @param int    $sev     Severity constant.
	 * @param array  $ctx     Extra JSON payload (scalar values only).
	 */
	public static function log( $type, $summary, $sev = self::SEV_INFO, array $ctx = [] ) {
		global $wpdb;

		if ( ! in_array( $type, self::TYPES, true ) || '' === trim( (string) $summary ) ) {
			return false;
		}

		if ( ! self::table_exists( true ) ) {
			return false;
		}

		// Sanitize context to scalars only.
		$clean_ctx = [];
		foreach ( $ctx as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$clean_ctx[ sanitize_key( (string) $k ) ] = sanitize_text_field( (string) $v );
			}
		}

		$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table_name(),
			[
				'event_type' => sanitize_key( $type ),
				'severity'   => max( 1, min( 3, absint( $sev ) ) ),
				'actor_user' => $user_id ?: null,
				'ip'         => class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::normalize( WPEPP_Core_Ip::get_client_ip() ) : '',
				'summary'    => sanitize_text_field( $summary ),
				'context'    => $clean_ctx ? wp_json_encode( $clean_ctx ) : null,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
		);

		wp_cache_delete( 'wpepp_event_stats', 'wpepp' );

		return (bool) $ok;
	}

	/**
	 * Query events.
	 *
	 * @param array $args {types[], severity_min, per_page, page}.
	 * @return array[]
	 */
	public static function query( array $args = [] ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [];
		}

		$per_page = min( 200, max( 1, absint( $args['per_page'] ?? 50 ) ) );
		$page     = max( 1, absint( $args['page'] ?? 1 ) );

		$where  = ' WHERE 1=1';
		$params = [];

		if ( ! empty( $args['types'] ) && is_array( $args['types'] ) ) {
			$allowed = array_intersect( $args['types'], self::TYPES );

			if ( $allowed ) {
				$ph      = implode( ',', array_fill( 0, count( $allowed ), '%s' ) );
				$where  .= " AND event_type IN ($ph)";
				$params  = array_merge( $params, $allowed );
			}
		}

		if ( ! empty( $args['severity_min'] ) ) {
			$where   .= ' AND severity >= %d';
			$params[] = absint( $args['severity_min'] );
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
		$sql   = "SELECT id, event_type, severity, actor_user, ip, summary, context, created_at
			  FROM {$table}{$where} ORDER BY id DESC LIMIT %d OFFSET %d";

		array_push( $params, $per_page, ( $page - 1 ) * $per_page );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( $sql, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders bound via prepare(); table from $wpdb->prefix.
			ARRAY_A
		);

		foreach ( (array) $rows as &$row ) {
			$row['context'] = json_decode( (string) $row['context'], true ) ?: [];
		}

		return (array) $rows;
	}

	/**
	 * Aggregated counters for dashboards/report.
	 *
	 * @param int $days Window.
	 * @return array {total:int, by_type:{}, by_day:{}}
	 */
	public static function stats( $days = 7 ) {
		global $wpdb;

		$cached = wp_cache_get( 'wpepp_event_stats', 'wpepp' );

		if ( is_array( $cached ) && isset( $cached[ $days ] ) ) {
			return $cached[ $days ];
		}

		$out    = [ 'total' => 0, 'by_type' => [], 'by_day' => [] ];
		$since  = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );

		if ( self::table_exists() ) {
			$table = self::table_name();

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
			$sql_total = "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s";
			$out['total'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( $sql_total, $since ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
			$sql_by_type = "SELECT event_type, COUNT(*) AS n FROM {$table}
						 WHERE created_at >= %s GROUP BY event_type";

			foreach (
				$wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						$sql_by_type, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
						$since
					),
					ARRAY_A
				) as $row
			) {
				$out['by_type'][ $row['event_type'] ] = (int) $row['n'];
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
			$sql_by_day = "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM {$table}
						   WHERE created_at >= %s GROUP BY DATE(created_at)";

			foreach (
				$wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						$sql_by_day, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
						$since
					),
					ARRAY_A
				) as $row
			) {
				$out['by_day'][ $row['d'] ] = (int) $row['n'];
			}
		}

		$cache         = is_array( $cached ) ? $cached : [];
		$cache[ $days ] = $out;
		wp_cache_set( 'wpepp_event_stats', $cache, 'wpepp', 120 );

		return $out;
	}

	/* ─── Housekeeping ─── */

	/**
	 * Daily prune: retention window + hard cap.
	 */
	public static function prune() {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return;
		}

		$s        = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		$s        = is_array( $s ) ? $s : [];
		$retention = max( 1, absint( $s['events_retention_days'] ?? 30 ) );

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
		$sql_retention = "DELETE FROM {$table} WHERE created_at < %s";

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				$sql_retention, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
				gmdate( 'Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS )
			)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $table always derives from $wpdb->prefix.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $total > self::HARD_CAP ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
			$sql_cap = "DELETE FROM {$table} ORDER BY id ASC LIMIT %d";

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					$sql_cap, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
					$total - self::HARD_CAP
				)
			);
		}

		wp_cache_delete( 'wpepp_event_stats', 'wpepp' );
	}

	/**
	 * Schedule housekeeping.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( 'wpepp_prune_events' ) ) {
			wp_schedule_event( time() + 4 * HOUR_IN_SECONDS, 'daily', 'wpepp_prune_events' );
		}
		add_action( 'wpepp_prune_events', [ __CLASS__, 'prune' ] );
	}
}
