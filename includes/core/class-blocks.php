<?php
/**
 * Block Center — IP/CIDR block storage, lookup and housekeeping.
 *
 * Storage: {prefix}wpepp_blocks with binary range columns so single IPs,
 * CIDR ranges AND IPv6 all live in one indexed structure.
 *
 * All public entry points are fail-safe: DB errors never break the site.
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Blocks
 */
class WPEPP_Blocks {

	/**
	 * Table name suffix (prefixed at runtime).
	 *
	 * @var string
	 */
	const TABLE = 'wpepp_blocks';

	/**
	 * Valid block types.
	 *
	 * @var array
	 */
	const TYPES = [ 'manual', 'auto_404', 'rate', 'lockout', 'country' ];

	/**
	 * Full table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create the blocks table (dbDelta-safe). Runs on activation + on demand.
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table_name();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			ip_start VARBINARY(16) NOT NULL,
			ip_end VARBINARY(16) NOT NULL,
			cidr_prefix TINYINT UNSIGNED NOT NULL DEFAULT 0,
			type ENUM('manual','auto_404','rate','lockout','country') NOT NULL DEFAULT 'manual',
			reason VARCHAR(255) NOT NULL DEFAULT '',
			hit_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
			expires DATETIME NULL DEFAULT NULL,
			created_by BIGINT NULL,
			created_at DATETIME NOT NULL,
			KEY idx_range (ip_start, ip_end),
			KEY idx_expires (expires),
			KEY idx_type (type)
		) {$charset_collate};";

		dbDelta( $sql );

		wp_cache_delete( 'wpepp_blocks_exists', 'wpepp' );
	}

	/**
	 * Whether the table exists (cached).
	 *
	 * @param bool $create Create when missing.
	 * @return bool
	 */
	public static function table_exists( $create = false ) {
		global $wpdb;

		$table   = self::table_name();
		$exists  = wp_cache_get( 'wpepp_blocks_exists', 'wpepp' );

		if ( false === $exists ) {
			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			wp_cache_set( 'wpepp_blocks_exists', $exists ? $table : 'no', 'wpepp', HOUR_IN_SECONDS );
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
	 * Binary representation of an IP for range storage.
	 *
	 * @param string $ip Dotted/colon IP.
	 * @return string|false Binary or false when invalid.
	 */
	private static function to_bin( $ip ) {
		$norm = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::normalize( $ip ) : trim( (string) $ip );
		return false === $norm || '' === $norm ? false : @inet_pton( $norm );
	}

	/**
	 * Parse an IP-or-CIDR input into range bounds.
	 *
	 * @param string $input Single IP or CIDR.
	 * @return array{start:string,end:string,prefix:int}|null Binary bounds or null.
	 */
	public static function parse_entry( $input ) {
		$input = trim( preg_replace( '/#.*$/', '', (string) $input ) );

		if ( '' === $input ) {
			return null;
		}

		if ( false === strpos( $input, '/' ) ) {
			$bin = self::to_bin( $input );
			return $bin ? [
				'start'  => $bin,
				'end'    => $bin,
				'prefix' => 0,
			] : null;
		}

		[ $range_ip, $prefix ] = array_pad( explode( '/', $input, 2 ), 2, '' );
		$max_bits              = ( false !== strpos( $range_ip, ':' ) ) ? 128 : 32;
		$prefix                = (int) $prefix;

		if ( '' === $range_ip || $prefix < 0 || $prefix > $max_bits ) {
			return null;
		}

		$bin = self::to_bin( $range_ip );
		if ( ! $bin ) {
			return null;
		}

		// Compute the last address of the range.
		$total_bytes = strlen( $bin );
		$start       = $bin;
		$end         = $bin;
		$full        = intdiv( $prefix, 8 );
		$rem         = $prefix % 8;

		for ( $i = $full; $i < $total_bytes; $i++ ) {
			$end[ $i ]   = chr( 0xFF );
			$start[ $i ] = chr( 0x00 );
		}

		if ( $rem > 0 ) {
			$mask            = ( 0xFF << ( 8 - $rem ) ) & 0xFF;
			$start[ $full ]  = chr( ord( $bin[ $full ] ) & $mask );
			$end[ $full ]    = chr( ord( $bin[ $full ] ) | ( ~$mask & 0xFF ) );
		}

		return [
			'start'  => $start,
			'end'    => $end,
			'prefix' => $prefix,
		];
	}

	/**
	 * Add a block.
	 *
	 * @param string     $entry     IP or CIDR.
	 * @param array      $args      {type, reason, expires(int ts|null), created_by}
	 * @return int|\WP_Error Inserted ID or error.
	 */
	public static function add_block( $entry, $args = [] ) {
		global $wpdb;

		$parsed = self::parse_entry( $entry );

		if ( null === $parsed ) {
			return new \WP_Error(
				'wpepp_invalid_ip',
				__( 'Invalid IP address or CIDR range.', 'wp-edit-password-protected' ),
				[ 'status' => 400 ]
			);
		}

		$type = in_array( $args['type'] ?? '', self::TYPES, true ) ? $args['type'] : 'manual';
		$type = sanitize_key( $type );

		self::table_exists( true );

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table_name(),
			[
				'ip_start'   => $parsed['start'],
				'ip_end'     => $parsed['end'],
				'cidr_prefix'=> min( 128, max( 0, (int) $parsed['prefix'] ) ),
				'type'       => $type,
				'reason'     => sanitize_text_field( (string) ( $args['reason'] ?? '' ) ),
				'hit_count'  => 0,
				'expires'    => ! empty( $args['expires'] ) ? gmdate( 'Y-m-d H:i:s', (int) $args['expires'] ) : null,
				'created_by' => (int) ( $args['created_by'] ?? get_current_user_id() ),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s' ]
		);

		if ( ! $ok ) {
			return new \WP_Error(
				'wpepp_block_insert_failed',
				__( 'Could not save the block.', 'wp-edit-password-protected' ),
				[ 'status' => 500 ]
			);
		}

		wp_cache_delete( 'wpepp_blocks_active_' . md5( (string) $entry ), 'wpepp' );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Remove a block by ID.
	 *
	 * @param int $id Block row ID.
	 * @return bool
	 */
	public static function remove_block( $id ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return false;
		}

		return (bool) $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table_name(),
			[ 'id' => absint( $id ) ],
			[ '%d' ]
		);
	}

	/**
	 * Find the active block covering the given IP (permanent or unexpired).
	 *
	 * @param string $ip Visitor IP.
	 * @return object|null Row with id/type/reason/expires/hit_count, or null.
	 */
	public static function find_active_block( $ip ) {
		static $runtime_cache = [];
		if ( array_key_exists( $ip, $runtime_cache ) ) {
			return $runtime_cache[ $ip ];
		}

		global $wpdb;

		if ( ! self::table_exists() ) {
			$runtime_cache[ $ip ] = null;
			return null;
		}

		$bin = self::to_bin( $ip );

		if ( ! $bin ) {
			$runtime_cache[ $ip ] = null;
			return null;
		}

		$cache_key = 'wpepp_blocks_active_' . md5( $bin );
		$cached    = wp_cache_get( $cache_key, 'wpepp' );

		if ( false !== $cached ) {
			$runtime_cache[ $ip ] = $cached ?: null;
			return $runtime_cache[ $ip ]; // '' marks a cached miss.
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
		$sql   = "SELECT id, type, reason, expires, hit_count
			 FROM {$table}
			 WHERE ip_start <= %s AND ip_end >= %s
			   AND ( expires IS NULL OR expires > %s )
			 ORDER BY id DESC LIMIT 1";
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix; placeholders bound below.
				$bin,
				$bin,
				current_time( 'mysql', true )
			)
		);

		wp_cache_set( $cache_key, $row ?: '', 'wpepp', 60 );
		$runtime_cache[ $ip ] = $row ?: null;

		return $runtime_cache[ $ip ];
	}

	/**
	 * Increment hit counter for a block row (batched via cache).
	 *
	 * @param int $id Block row ID.
	 */
	public static function bump_hit_count( $id ) {
		$key = 'wpepp_block_hits_' . absint( $id );

		$n = (int) wp_cache_get( $key, 'wpepp' );
		$n++;

		if ( $n >= 25 ) {
			global $wpdb;
			if ( self::table_exists() ) {
				$table = self::table_name();
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
				$sql = "UPDATE {$table} SET hit_count = hit_count + %d WHERE id = %d";
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( $sql, $n, absint( $id ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix.
				);
			}
			wp_cache_delete( $key, 'wpepp' );
			return;
		}

		wp_cache_set( $key, $n, 'wpepp', HOUR_IN_SECONDS );
	}

	/**
	 * List blocks with optional filters.
	 *
	 * @param array $args {per_page, page, type, include_expired}
	 * @return array[]
	 */
	public static function list_blocks( $args = [] ) {
		global $wpdb;

		if ( ! self::table_exists( true ) ) {
			return [];
		}

		$per_page = min( 200, max( 1, absint( $args['per_page'] ?? 50 ) ) );
		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$where  = '';
		$params = [];

		if ( ! empty( $args['type'] ) && in_array( $args['type'], self::TYPES, true ) ) {
			$where   .= empty( $where ) ? ' WHERE' : ' AND';
			$where   .= ' type = %s';
			$params[] = $args['type'];
		}

		$include_expired = rest_sanitize_boolean( $args['include_expired'] ?? false );
		if ( ! $include_expired ) {
			$where   .= empty( $where ) ? ' WHERE' : ' AND';
			$where   .= ' ( expires IS NULL OR expires > %s )';
			$params[] = current_time( 'mysql', true );
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
		$sql   = "SELECT id, ip_start, ip_end, cidr_prefix, type, reason, hit_count, expires, created_at
			  FROM {$table}{$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d";
		array_push( $params, $per_page, $offset );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( $sql, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders bound via prepare(); table from $wpdb->prefix.
			ARRAY_A
		);

		$rows = is_array( $rows ) ? $rows : [];
		foreach ( $rows as &$row ) {
			$row['cidr'] = self::format_cidr( $row );
			unset( $row['ip_start'], $row['ip_end'], $row['cidr_prefix'] );
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Format a stored range back into a human CIDR/IP string.
	 *
	 * @param array $row Row with binary columns.
	 * @return string
	 */
	private static function format_cidr( $row ) {
		$raw_start = $row['ip_start'];

		if ( is_resource( $raw_start ) ) {
			$raw_start = stream_get_contents( $raw_start );
		}

		$start = @inet_ntop( (string) $raw_start );

		if ( false === $start || null === $start ) {
			return '?';
		}

		$max_bits = ( false !== strpos( (string) $start, ':' ) ) ? 128 : 32;
		$prefix   = (int) $row['cidr_prefix'];

		if ( 0 === $prefix || $prefix >= $max_bits ) {
			return $start;
		}

		return "{$start}/{$prefix}";
	}

	/**
	 * Delete every expired temporary block.
	 *
	 * @return int Rows removed.
	 */
	public static function purge_expired() {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table always derives from $wpdb->prefix.
		$sql = "DELETE FROM {$table} WHERE expires IS NOT NULL AND expires <= %s";

		$count = (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table derives from $wpdb->prefix; placeholder bound below.
				current_time( 'mysql', true )
			)
		);

		if ( $count > 0 ) {
			wp_cache_flush_group( 'wpepp' );
		}

		return $count;
	}

	/**
	 * Daily prune cron.
	 */
	public static function cron_prune() {
		self::purge_expired();
	}

	/**
	 * Schedule housekeeping.
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( 'wpepp_prune_blocks' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpepp_prune_blocks' );
		}
		add_action( 'wpepp_prune_blocks', [ __CLASS__, 'cron_prune' ] );
	}
}
