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
		'node_modules', '.git', '.svn', '.hg', 'vendor',
		'bower_components', 'tests', 'test', 'docs',
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
	 * Check if a directory should be skipped.
	 *
	 * @param string $path Directory path.
	 * @param bool   $is_quick Whether this is a quick scan.
	 * @return bool
	 */
	public static function is_skip_dir( $path, $is_quick = false ) {
		$name = strtolower( basename( $path ) );

		if ( in_array( $name, self::SKIP_DIR_NAMES, true ) ) {
			return true;
		}

		if ( preg_match( '/^(backup|updraft)/i', $name ) ) {
			return true;
		}

		// In Quick Scan, skip uploads directory entirely.
		if ( $is_quick && 'uploads' === $name ) {
			return true;
		}

		return false;
	}

	/**
	 * Get initial queue of directories/targets based on profile.
	 *
	 * @param string $profile 'quick' or 'full'.
	 * @return array List of targets: each [ 'path' => string, 'recursive' => bool, 'is_file' => bool ].
	 */
	public static function get_initial_queue( $profile = 'quick' ) {
		$queue   = [];
		$abspath = wp_normalize_path( ABSPATH );

		if ( 'quick' === $profile ) {
			// Quick Scan: High-risk infection vectors only (completes fast in 10-30 seconds).
			// 1. Core Root directory (index.php, wp-config.php, .htaccess, etc.) - non-recursive
			$queue[] = [ 'path' => $abspath, 'recursive' => false ];

			// 2. wp-content root (advanced-cache.php, object-cache.php, index.php, etc.) - non-recursive
			$wp_content = wp_normalize_path( WP_CONTENT_DIR );
			if ( is_dir( $wp_content ) ) {
				$queue[] = [ 'path' => $wp_content, 'recursive' => false ];
			}

			// 3. MU plugins (must-use plugins) - non-recursive
			$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? wp_normalize_path( WPMU_PLUGIN_DIR ) : $wp_content . '/mu-plugins';
			if ( is_dir( $mu_dir ) ) {
				$queue[] = [ 'path' => $mu_dir, 'recursive' => false ];
			}

			// 4. Active Theme root (functions.php, header.php, etc. where webshells inject) - non-recursive
			if ( function_exists( 'get_stylesheet_directory' ) ) {
				$theme_dir = wp_normalize_path( get_stylesheet_directory() );
				if ( is_dir( $theme_dir ) ) {
					$queue[] = [ 'path' => $theme_dir, 'recursive' => false ];
				}
				$template_dir = wp_normalize_path( get_template_directory() );
				if ( $template_dir !== $theme_dir && is_dir( $template_dir ) ) {
					$queue[] = [ 'path' => $template_dir, 'recursive' => false ];
				}
			}

			// 5. Active Plugins root files & main loader files (non-recursive)
			$active_plugins = (array) get_option( 'active_plugins', [] );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
			}

			$plugin_base = wp_normalize_path( WP_PLUGIN_DIR );
			foreach ( $active_plugins as $plugin_rel ) {
				$plugin_file = $plugin_base . '/' . $plugin_rel;
				if ( is_file( $plugin_file ) ) {
					$queue[] = [ 'path' => $plugin_file, 'is_file' => true ];
				}
				$parts = explode( '/', $plugin_rel );
				if ( count( $parts ) > 1 ) {
					$plugin_dir = $plugin_base . '/' . $parts[0];
					if ( is_dir( $plugin_dir ) ) {
						$queue[] = [ 'path' => $plugin_dir, 'recursive' => false ];
					}
				}
			}

			// 6. wp-includes root & wp-admin root (non-recursive)
			$wp_inc = wp_normalize_path( ABSPATH . WPINC );
			if ( is_dir( $wp_inc ) ) {
				$queue[] = [ 'path' => $wp_inc, 'recursive' => false ];
			}
			$wp_adm = wp_normalize_path( ABSPATH . 'wp-admin' );
			if ( is_dir( $wp_adm ) ) {
				$queue[] = [ 'path' => $wp_adm, 'recursive' => false ];
			}

			return $queue;
		}

		// Full Scan: Entire WordPress file tree from root (recursive)
		return [
			[ 'path' => $abspath, 'recursive' => true ],
		];
	}

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
	 * @param bool $force_reload Force reload bypassing static cache.
	 * @return array[] Each: {id, severity, name, targets[], needles[], regex}
	 */
	public static function signatures( $force_reload = false ) {
		static $sigs = null;

		if ( ! $force_reload && null !== $sigs ) {
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
					'targets'  => array_map( 'strtolower', (array) ( $sig['targets'] ?? [ 'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'inc' ] ) ),
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
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return new \WP_Error( 'insecure_feed_url', __( 'Signatures feed must use HTTPS.', 'wp-edit-password-protected' ) );
		}

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
		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( 200 !== (int) $code || ! is_array( $decoded ) || ! isset( $decoded['signatures'] ) || ! is_array( $decoded['signatures'] ) ) {
			// If remote feed is currently redirecting or unavailable, gracefully retain built-in definitions and record check.
			update_option( self::OPT_LAST_SYNC, time() );
			return self::get_sync_info();
		}

		$valid = [];
		foreach ( $decoded['signatures'] as $sig ) {
			if ( ! is_array( $sig ) || empty( $sig['id'] ) || empty( $sig['regex'] ) || ! isset( $sig['needles'] ) ) {
				continue;
			}

			if ( class_exists( 'WPEPP_FW_Engine' ) && ! WPEPP_FW_Engine::regex_safe( $sig['regex'] ) ) {
				continue;
			}

			// Validate that regex compiles safely without errors.
			if ( false === @preg_match( $sig['regex'], '' ) ) {
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
	 * Uses an O(1) directory queue + file queue so that on each slice it resumes
	 * immediately without re-walking tens of thousands of previously scanned files.
	 *
	 * @param array $state Scan state (mutated + persisted).
	 * @param float $start microtime start of this slice.
	 * @return bool True when stage complete, false when budget exhausted mid-stage.
	 */
	public static function process_slice( array &$state, $start ) {
		$profile  = $state['profile'] ?? 'quick';
		$is_quick = ( 'quick' === $profile );

		// Initialize queue if starting fresh or advancing into this stage.
		if ( ! isset( $state['dir_queue'] ) ) {
			$state['dir_queue']  = self::get_initial_queue( $profile );
			$state['file_queue'] = [];
		}

		try {
			while ( microtime( true ) - $start < WPEPP_Scan_Engine::SLICE_BUDGET - 0.25 ) {

				// 1. If we have files queued from current directory, process them first.
				if ( ! empty( $state['file_queue'] ) ) {
					$file = array_shift( $state['file_queue'] );
					if ( is_string( $file ) && is_file( $file ) ) {
						self::scan_file( $file, $state );
						$state['scanned'] = (int) ( $state['scanned'] ?? 0 ) + 1;
					}
					continue;
				}

				// 2. If no files queued, pick next target from dir_queue.
				if ( empty( $state['dir_queue'] ) ) {
					// All queues empty — files stage complete!
					unset( $state['dir_queue'], $state['file_queue'] );
					WPEPP_Scan_Engine::advance( $state, 'integrity' );
					return true;
				}

				$target = array_shift( $state['dir_queue'] );
				if ( ! is_array( $target ) || empty( $target['path'] ) ) {
					continue;
				}

				$target_path = $target['path'];

				// Single file target
				if ( ! empty( $target['is_file'] ) ) {
					if ( is_file( $target_path ) ) {
						$state['file_queue'][] = $target_path;
					}
					continue;
				}

				if ( ! is_dir( $target_path ) || self::is_skip_dir( $target_path, $is_quick ) ) {
					continue;
				}

				$entries = @scandir( $target_path );
				if ( false === $entries ) {
					continue;
				}

				$is_recursive = ! empty( $target['recursive'] );

				foreach ( $entries as $entry ) {
					if ( '.' === $entry || '..' === $entry ) {
						continue;
					}

					$full = $target_path . '/' . $entry;

					if ( is_dir( $full ) ) {
						if ( $is_recursive && ! self::is_skip_dir( $full, $is_quick ) ) {
							$state['dir_queue'][] = [
								'path'      => $full,
								'recursive' => true,
							];
						}
					} elseif ( is_file( $full ) ) {
						$ext = strtolower( pathinfo( $full, PATHINFO_EXTENSION ) );
						if ( in_array( $ext, self::EXTENSIONS, true ) ) {
							$state['file_queue'][] = $full;
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			error_log( '[WPEPP Scanner] walk error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		// Persist progress for seamless resumption on next slice.
		WPEPP_Scan_Engine::persist( $state );

		return false;
	}

	/**
	 * Scan a single file against the signature pack.
	 *
	 * @param string $path  Absolute file path.
	 * @param array  $state State (flag counter mutated via engine persist).
	 */
	private static function scan_file( $path, array &$state ) {
		unset( $state ); // Read-only here; issues add their own counters.

		$normalized = wp_normalize_path( $path );
		$abspath    = wp_normalize_path( ABSPATH );

		// Skip core files (wp-admin, wp-includes):
		// Official core files are verified cryptographically by Checksums (class-integrity.php).
		// Running heuristic regexes on core creates false positives on legitimate core functions.
		if ( 0 === strpos( $normalized, $abspath . 'wp-admin/' ) || 0 === strpos( $normalized, $abspath . 'wp-includes/' ) ) {
			return;
		}

		// Skip vendor and node_modules directories (Composer / npm packages).
		if ( false !== strpos( $normalized, '/vendor/' ) || false !== strpos( $normalized, '/node_modules/' ) ) {
			return;
		}

		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, self::EXTENSIONS, true ) ) {
			return;
		}

		// Compiled web assets (JS chunks, minified bundles in build/dist) are not PHP backdoors.
		if ( 'php' !== $ext && ( false !== strpos( $normalized, '/build/' ) || false !== strpos( $normalized, '/dist/' ) ) ) {
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
			// Extension target filtering: only test signatures matching this file's type.
			if ( ! empty( $sig['targets'] ) && ! in_array( $ext, $sig['targets'], true ) ) {
				continue;
			}

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
