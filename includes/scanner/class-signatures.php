<?php
/**
 * Malware signature scanner — bundled heuristic pack with common-string
 * pre-indexing (skip regex when required substrings are absent).
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Signatures
 */
class WPEPP_Signatures {

	/**
	 * Text file extensions considered scannable.
	 *
	 * @var array
	 */
	const EXTENSIONS = [ 'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'inc', 'js', 'html', 'htm', 'htaccess' ];

	/**
	 * Max file size scanned (bytes).
	 *
	 * @var int
	 */
	const MAX_FILE_SIZE = 1048576; // 1 MB.

	/**
	 * Directory names pruned at WALK level (never descended into).
	 *
	 * Pruning beats per-file skipping: the iterator never enters these
	 * trees, saving tens of thousands of stat() calls per slice.
	 *
	 * @var array
	 */
	const SKIP_DIR_NAMES = [
		'node_modules', '.git', '.svn', '.hg',
		'wpepp-fw', 'cache', 'upgrade', 'ai1wm-backups', 'wflogs',
	];

	/**
	 * Option keys for remote signatures.
	 */
	const OPT_REMOTE_SIGS = 'wpepp_remote_signatures';
	const OPT_LAST_SYNC   = 'wpepp_signatures_last_sync';

	/**
	 * Remote signatures update feed URL.
	 */
	const FEED_URL = 'https://wpthemespace.com/api/signatures.json';

	/**
	 * Recursive filter that prunes junk directories during traversal.
	 */
	private static function walker( $root ) {
		return new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
				static function ( $current ) {
					if ( $current->isDir() ) {
						$name = strtolower( $current->getFilename() );
						// Prune junk dirs anywhere in the tree.
						if ( in_array( $name, self::SKIP_DIR_NAMES, true ) ) {
							return false;
						}
						// Prune backup-style dirs: backup*, updraft*.
						if ( preg_match( '/^(backup|updraft)/', $name ) ) {
							return false;
						}
					}
					return true;
				}
			),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
	}

	/**
	 * Whether a path should be skipped entirely.
	 *
	 * Kept as a second line of defense (cursor resumption can hand us
	 * paths from before a rule change).
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public static function skip_path( $path ) {
		$normalized = str_replace( '\\\\', '/', (string) $path );
		$relative   = ltrim( str_replace( wp_normalize_path( ABSPATH ), '', $normalized ), '/' );

		foreach ( self::SKIP_DIR_NAMES as $dir ) {
			$dir = str_replace( '*', '', rtrim( strtolower( $dir ), '/' ) );

			if ( '' !== $dir && '' !== $relative && false !== strpos( strtolower( $relative ), '/' . $dir . '/' ) ) {
				return true;
			}

			if ( '' !== $dir && preg_match( '#(^|/)' . preg_quote( $dir, '#' ) . '(/|$)#i', $relative ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load the signature pack (cached).
	 * Merges bundled signatures with any verified remote updates.
	 *
	 * @return array[] Each: {id, severity, name, needles[], regex}
	 */
	public static function signatures() {
		static $sigs = null;

		if ( null !== $sigs ) {
			return $sigs;
		}

		$sigs = wp_cache_get( 'wpepp_scan_signatures', 'wpepp' );

		if ( ! is_array( $sigs ) ) {
			$file = WPEPP_PATH . 'assets/scanner/signatures.json';
			$by_id = [];

			$decoded = file_exists( $file )
				? json_decode( (string) file_get_contents( $file ), true ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local asset.
				: null;

			$all_raw = (array) ( $decoded['signatures'] ?? [] );

			// Merge remote signatures if stored.
			$remote_raw = json_decode( (string) get_option( self::OPT_REMOTE_SIGS, '[]' ), true );
			if ( is_array( $remote_raw ) && ! empty( $remote_raw ) ) {
				$all_raw = array_merge( $all_raw, $remote_raw );
			}

			foreach ( $all_raw as $sig ) {
				if ( ! is_array( $sig )
					|| empty( $sig['id'] )
					|| empty( $sig['regex'] )
					|| ! isset( $sig['needles'] ) ) {
					continue;
				}

				// Validate pattern compiles once, up front — and reject
				// degenerate patterns that match the empty string.
				if ( class_exists( 'WPEPP_FW_Engine' ) ) {
					if ( ! WPEPP_FW_Engine::regex_safe( $sig['regex'] ) ) {
						continue;
					}
				} elseif ( @preg_match( $sig['regex'], '' ) === false ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- intentional probe.
					continue;
				}

				$sig_id = sanitize_key( $sig['id'] );
				$by_id[ $sig_id ] = [
					'id'       => $sig_id,
					'severity' => max( 1, min( 3, absint( $sig['severity'] ?? 2 ) ) ),
					'name'     => sanitize_text_field( $sig['name'] ?? $sig['id'] ),
					'needles'  => array_map( 'strtolower', (array) $sig['needles'] ),
					'regex'    => $sig['regex'],
				];
			}

			$sigs = array_values( $by_id );
			wp_cache_set( 'wpepp_scan_signatures', $sigs, 'wpepp', HOUR_IN_SECONDS );
		}

		return $sigs;
	}

	/**
	 * Get signature sync status information.
	 *
	 * @return array
	 */
	public static function get_sync_info() {
		$remote       = json_decode( (string) get_option( self::OPT_REMOTE_SIGS, '[]' ), true );
		$remote_count = is_array( $remote ) ? count( $remote ) : 0;
		$total        = count( self::signatures() );
		return [
			'total'        => $total,
			'local_count'  => max( 0, $total - $remote_count ),
			'remote_count' => $remote_count,
			'feed_url'     => self::FEED_URL,
			'last_sync'    => (int) get_option( self::OPT_LAST_SYNC, 0 ),
		];
	}

	/**
	 * Fetch and update malware signatures from the remote feed.
	 *
	 * @param bool $force Force sync even if recent.
	 * @return array|\WP_Error
	 */
	public static function sync_signatures( $force = false ) {
		$last_sync = (int) get_option( self::OPT_LAST_SYNC, 0 );

		if ( ! $force && ( time() - $last_sync ) < 12 * HOUR_IN_SECONDS ) {
			return self::get_sync_info();
		}

		$url = apply_filters( 'wpepp_signatures_feed_url', self::FEED_URL );

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'    => 15,
				'user-agent' => 'WPEPP-Security/' . ( defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '3.0.0' ) . '; ' . home_url(),
				'headers'    => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'wpepp_sig_fetch_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Could not connect to signature update server: %s', 'wp-edit-password-protected' ),
					$response->get_error_message()
				),
				[ 'status' => 502 ]
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return new \WP_Error(
				'wpepp_sig_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Signature update server returned HTTP %d', 'wp-edit-password-protected' ),
					$code
				),
				[ 'status' => 502 ]
			);
		}

		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) || ! isset( $decoded['signatures'] ) || ! is_array( $decoded['signatures'] ) ) {
			return new \WP_Error(
				'wpepp_sig_invalid_json',
				__( 'Invalid signature payload received from server.', 'wp-edit-password-protected' ),
				[ 'status' => 502 ]
			);
		}

		$valid = [];
		foreach ( $decoded['signatures'] as $sig ) {
			if ( ! is_array( $sig ) || empty( $sig['id'] ) || empty( $sig['regex'] ) || ! isset( $sig['needles'] ) ) {
				continue;
			}

			if ( class_exists( 'WPEPP_FW_Engine' ) && ! WPEPP_FW_Engine::regex_safe( $sig['regex'] ) ) {
				continue;
			}

			$valid[] = [
				'id'       => sanitize_key( $sig['id'] ),
				'severity' => max( 1, min( 3, absint( $sig['severity'] ?? 2 ) ) ),
				'name'     => sanitize_text_field( $sig['name'] ?? $sig['id'] ),
				'needles'  => array_map( 'strtolower', (array) $sig['needles'] ),
				'regex'    => $sig['regex'],
			];
		}

		update_option( self::OPT_REMOTE_SIGS, wp_json_encode( $valid ), false );
		update_option( self::OPT_LAST_SYNC, time(), false );

		wp_cache_delete( 'wpepp_scan_signatures', 'wpepp' );

		if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::log(
				'scan_finished',
				sprintf( 'Malware signatures updated remotely (%d active)', count( self::signatures() ) ),
				WPEPP_Logger::SEV_INFO,
				[ 'remote_count' => count( $valid ) ]
			);
		}

		return [
			'success'      => true,
			'total'        => count( self::signatures() ),
			'remote_count' => count( $valid ),
			'last_sync'    => time(),
		];
	}

	/**
	 * Process one slice of the files stage — walk + scan until budget ends.
	 *
	 * @param array $state Scan state (mutated + persisted).
	 * @param float $start microtime start of this slice.
	 * @return bool True when budget exhausted mid-stage, false when stage done.
	 */
	public static function process_slice( array &$state, $start ) {
		$root    = wp_normalize_path( ABSPATH );
		$cursor  = (string) ( $state['cursor'] ?? '' );

		$iterator = self::walker( $root );

		$passed_cursor = '' === $cursor;

		try {
			foreach ( $iterator as $path ) {

				if ( ! $passed_cursor ) {
					if ( wp_normalize_path( (string) $path ) === $cursor ) {
						$passed_cursor = true;
					}
					continue;
				}

				if ( self::skip_path( (string) $path ) ) {
					continue;
				}

				// Budget check BEFORE expensive work on each file.
				if ( microtime( true ) - $start >= WPEPP_Scan_Engine::SLICE_BUDGET - 0.25 ) {
					$state['cursor'] = wp_normalize_path( (string) $path );
					WPEPP_Scan_Engine::persist( $state );
					return false;
				}

				self::scan_file( (string) $path, $state );

				$state['scanned'] = (int) ( $state['scanned'] ?? 0 ) + 1;

				// Persist progress every 200 files so UI stays live.
				if ( 0 === ( $state['scanned'] % 200 ) ) {
					WPEPP_Scan_Engine::persist( $state );
				}
			}
		} catch ( \Throwable $e ) {
			error_log( '[WPEPP Scanner] walk error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		// Files stage complete → integrity next.
		WPEPP_Scan_Engine::advance( $state, 'integrity' );

		return true;
	}

	/**
	 * Scan a single file against the signature pack.
	 *
	 * @param string $path  Absolute file path.
	 * @param array  $state State (flag counter mutated via engine persist).
	 */
	private static function scan_file( $path, array &$state ) {
		unset( $state ); // Read-only here; issues add their own counters.

		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, self::EXTENSIONS, true ) ) {
			return;
		}

		$size = (int) @filesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $size <= 0 || $size > self::MAX_FILE_SIZE ) {
			return;
		}

		$content = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

		if ( false === $content || '' === $content ) {
			return;
		}

		// Binary sniff — NUL byte in first 8KB means not text.
		if ( false !== strpos( substr( $content, 0, 8192 ), "\0" ) ) {
			return;
		}

		$haystack_lower = strtolower( $content );

		foreach ( self::signatures() as $sig ) {
			// Common-string pre-index: every needle must be present.
			foreach ( $sig['needles'] as $needle ) {
				if ( '' !== $needle && false === strpos( $haystack_lower, $needle ) ) {
					continue 2;
				}
			}

			if ( @preg_match( $sig['regex'], $content ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				WPEPP_Issues::add(
					'malware',
					$sig['severity'],
					wp_normalize_path( $path ),
					[
						'signature' => $sig['id'],
						'name'      => $sig['name'],
					]
				);
			}
		}
	}
}
