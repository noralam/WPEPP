<?php
/**
 * Scan issues storage — dedupe, ignore, resolve.
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Issues
 */
class WPEPP_Issues {

	/**
	 * Table suffix.
	 *
	 * @var string
	 */
	const TABLE = 'wpepp_scan_issues';

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
	 * Create the table.
	 */
	public static function create_table() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) && file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table_name();

		dbDelta(
			"CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			kind VARCHAR(32) NOT NULL DEFAULT '',
			severity TINYINT NOT NULL DEFAULT 1,
			object_path TEXT NULL,
			ignore_hash CHAR(32) NULL DEFAULT NULL,
			detail LONGTEXT NULL,
			status ENUM('new','ignored','fixed') NOT NULL DEFAULT 'new',
			created_at DATETIME NOT NULL,
			INDEX idx_kind (kind),
			KEY idx_status (status),
			KEY idx_sev (severity)
		) {$charset_collate};"
		);

		wp_cache_delete( 'wpepp_issues_exists', 'wpepp' );
	}

	/**
	 * Table existence (cached, optional creation).
	 *
	 * @param bool $create Create when missing.
	 * @return bool
	 */
	public static function table_exists( $create = false ) {
		global $wpdb;

		$table  = self::table_name();
		$exists = wp_cache_get( 'wpepp_issues_exists', 'wpepp' );

		if ( false === $exists ) {
			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			wp_cache_set( 'wpepp_issues_exists', $exists ? $table : 'no', 'wpepp', HOUR_IN_SECONDS );
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
	 * Compute the dedupe hash for a finding.
	 *
	 * @param string $kind Finding kind.
	 * @param string $path Object path.
	 * @return string
	 */
	public static function hash( $kind, $path ) {
		return md5( (string) $kind . '|' . (string) $path );
	}

	/**
	 * Add (or refresh) a finding. Deduped by kind+path hash.
	 *
	 * @param string $kind     core_modified|plugin_modified|theme_modified|core_unknown|missing_file|malware|suspicious_option|ghost_admin.
	 * @param int    $severity 1 low, 2 warn, 3 critical.
	 * @param string $path     Object path or identifier.
	 * @param array  $detail   Extra JSON payload.
	 */
	public static function add( $kind, $severity, $path, $detail = [] ) {
		global $wpdb;

		if ( ! self::table_exists( true ) ) {
			return;
		}

		$hash = self::hash( $kind, $path );
		$table = self::table_name();

		$existing_row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( 'SELECT id, status FROM %i WHERE ignore_hash = %s LIMIT 1', $table, $hash ),
			ARRAY_A
		);

		if ( ! empty( $existing_row['id'] ) ) {
			if ( 'ignored' === $existing_row['status'] ) {
				return;
			}

			// If already open (new), refresh timestamp and count in current scan findings.
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE %i SET created_at = %s WHERE id = %d AND status = 'new'",
					$table,
					current_time( 'mysql', true ),
					(int) $existing_row['id']
				)
			);

			$state            = WPEPP_Scan_Engine::state();
			$state['flagged'] = (int) ( $state['flagged'] ?? 0 ) + 1;
			WPEPP_Scan_Engine::persist( $state );
			return;
		}

		$state          = WPEPP_Scan_Engine::state();
		$state['flagged'] = (int) ( $state['flagged'] ?? 0 ) + 1;
		WPEPP_Scan_Engine::persist( $state );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			[
				'kind'        => sanitize_key( $kind ),
				'severity'    => max( 1, min( 3, absint( $severity ) ) ),
				'object_path' => sanitize_text_field( $path ),
				'ignore_hash' => $hash,
				'detail'      => wp_json_encode( is_array( $detail ) ? $detail : [] ),
				'status'      => 'new',
				'created_at'  => current_time( 'mysql', true ),
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * List issues.
	 *
	 * @param array $args {status, per_page, page}.
	 * @return array[]
	 */
	public static function list_issues( $args = [] ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [];
		}

		$status   = in_array( $args['status'] ?? '', [ 'new', 'ignored', 'fixed' ], true ) ? $args['status'] : '';
		$per_page = min( 200, max( 1, absint( $args['per_page'] ?? 50 ) ) );
		$page     = max( 1, absint( $args['page'] ?? 1 ) );

		$table = self::table_name();

		if ( '' !== $status ) {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT id, kind, severity, object_path, detail, status, created_at
					 FROM %i WHERE status = %s ORDER BY severity DESC, id DESC LIMIT %d OFFSET %d',
					$table,
					$status,
					$per_page,
					( $page - 1 ) * $per_page
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT id, kind, severity, object_path, detail, status, created_at
					 FROM %i ORDER BY severity DESC, id DESC LIMIT %d OFFSET %d',
					$table,
					$per_page,
					( $page - 1 ) * $per_page
				),
				ARRAY_A
			);
		}

		foreach ( (array) $rows as &$row ) {
			$row['detail'] = json_decode( (string) $row['detail'], true ) ?: [];
		}

		return (array) $rows;
	}

	/**
	 * Count new issues.
	 *
	 * @return int
	 */
	public static function count_new() {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table = self::table_name();

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'new'", $table )
		);
	}

	/**
	 * Set issue status.
	 *
	 * @param int    $id     Issue ID.
	 * @param string $status ignored|fixed|new.
	 * @return bool
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;

		if ( ! in_array( $status, [ 'new', 'ignored', 'fixed' ], true ) || ! self::table_exists() ) {
			return false;
		}

		return (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table_name(),
			[ 'status' => $status ],
			[ 'id' => absint( $id ) ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Clear all issues (optionally only resolved ones).
	 *
	 * @param bool $only_handled Delete ignored+fixed only.
	 * @return int Rows deleted.
	 */
	public static function clear( $only_handled = false ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table = self::table_name();

		if ( $only_handled ) {
			return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( "DELETE FROM %i WHERE status IN ('ignored','fixed')", $table )
			);
		}

		$n = (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		wp_cache_flush_group( 'wpepp' );

		return $n;
	}

	/**
	 * Bulk update status for findings.
	 *
	 * @param string $action 'resolve_all' | 'ignore_all' | 'clear_all'.
	 * @return int Rows affected.
	 */
	public static function bulk_action( $action ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table = self::table_name();

		if ( 'resolve_all' === $action ) {
			$n = (int) $wpdb->query(
				$wpdb->prepare( "UPDATE %i SET status = 'fixed' WHERE status = 'new'", $table )
			);
			wp_cache_flush_group( 'wpepp' );
			return $n;
		}

		if ( 'ignore_all' === $action ) {
			$n = (int) $wpdb->query(
				$wpdb->prepare( "UPDATE %i SET status = 'ignored' WHERE status = 'new'", $table )
			);
			wp_cache_flush_group( 'wpepp' );
			return $n;
		}

		if ( 'clear_all' === $action ) {
			return self::clear( false );
		}

		return 0;
	}

	/**
	 * Permanently delete a malicious file associated with an issue from disk.
	 *
	 * @param int $id Issue ID.
	 * @return array { success: bool, message: string }
	 */
	public static function delete_file( $id ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [ 'success' => false, 'message' => __( 'Issues table does not exist.', 'wp-edit-password-protected' ) ];
		}

		$table = self::table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT id, kind, object_path FROM %i WHERE id = %d LIMIT 1', $table, absint( $id ) ),
			ARRAY_A
		);

		if ( ! $row || empty( $row['object_path'] ) ) {
			return [ 'success' => false, 'message' => __( 'Issue not found.', 'wp-edit-password-protected' ) ];
		}

		$path    = wp_normalize_path( $row['object_path'] );
		$abspath = wp_normalize_path( ABSPATH );

		// Security: must be inside ABSPATH.
		if ( 0 !== strpos( $path, $abspath ) ) {
			return [ 'success' => false, 'message' => __( 'File path is outside of WordPress installation.', 'wp-edit-password-protected' ) ];
		}

		// Security: protect critical WordPress files from accidental deletion.
		$basename        = strtolower( basename( $path ) );
		$protected_files = [ 'wp-config.php', 'wp-settings.php', 'wp-load.php', 'wp-blog-header.php', 'index.php', '.htaccess' ];
		if ( in_array( $basename, $protected_files, true ) && dirname( $path ) === rtrim( $abspath, '/' ) ) {
			return [ 'success' => false, 'message' => __( 'Cannot delete core system file.', 'wp-edit-password-protected' ) ];
		}

		if ( ! file_exists( $path ) ) {
			// File is already gone, update status to fixed.
			self::set_status( $id, 'fixed' );
			return [ 'success' => true, 'message' => __( 'File was already removed from disk.', 'wp-edit-password-protected' ) ];
		}

		if ( is_dir( $path ) ) {
			return [ 'success' => false, 'message' => __( 'Target is a directory, not a file.', 'wp-edit-password-protected' ) ];
		}

		if ( ! @unlink( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return [ 'success' => false, 'message' => __( 'Permission denied: Could not delete file from server.', 'wp-edit-password-protected' ) ];
		}

		self::set_status( $id, 'fixed' );

		if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::log(
				'malware_deleted',
				sprintf( 'Malware file deleted: %s', $path ),
				WPEPP_Logger::SEV_WARN,
				[ 'issue_id' => $id, 'path' => $path ]
			);
		}

		return [ 'success' => true, 'message' => __( 'File permanently deleted from server.', 'wp-edit-password-protected' ) ];
	}
}
