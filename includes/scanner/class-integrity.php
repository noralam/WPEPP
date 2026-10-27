<?php
/**
 * Integrity scanner — core/plugin/theme checksums from api.wordpress.org
 * plus unknown-file detection inside core directories.
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Integrity
 */
class WPEPP_Integrity {

	/**
	 * API endpoint base.
	 *
	 * @var string
	 */
	const API = 'https://api.wordpress.org';

	/**
	 * Process one slice of the integrity stage.
	 *
	 * Slice 1: core files. Slices 2..n: one plugin per slice, then themes.
	 *
	 * @param array $state Scan state.
	 * @param float $start Slice start time.
	 * @return bool True when more work remains in the stage.
	 */
	public static function process_slice( array &$state, $start ) {
		$is_quick = ( ( $state['profile'] ?? 'quick' ) === 'quick' );

		// In Quick Scan, perform core integrity check on critical entrypoints and immediately advance to options,
		// avoiding slow remote HTTP API calls for individual plugins and thousands of deep file hashes.
		if ( $is_quick ) {
			self::check_core( true );
			WPEPP_Scan_Engine::advance( $state, 'options' );
			return true;
		}

		$queue = $state['integrity_queue'] ?? null;

		if ( null === $queue ) {
			// Build the work queue: 'core' + each plugin slug.
			$queue = [ '__core__' ];

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( get_plugins() as $plugin_file => $data ) {
				$slug = dirname( $plugin_file );

				// Skip single-file plugins from directory traversal to prevent scanning all of wp-content/plugins.
				if ( '.' === $slug || empty( $slug ) ) {
					continue;
				}

				$queue[] = [
					'type'    => 'plugin',
					'slug'    => $slug,
					'version' => $data['Version'] ?? '',
					'dir'     => WP_PLUGIN_DIR . '/' . $slug,
				];
			}

			$state['integrity_queue'] = $queue;
			$state['integrity_index'] = 0;
		}

		while ( microtime( true ) - $start < WPEPP_Scan_Engine::SLICE_BUDGET - 0.5 ) {

			$index = (int) ( $state['integrity_index'] ?? 0 );

			if ( $index >= count( $queue ) ) {
				unset( $state['integrity_queue'], $state['integrity_index'], $state['core_cursor'] );
				WPEPP_Scan_Engine::advance( $state, 'options' );
				return true; // Stage complete — advance to options!
			}

			$job = $queue[ $index ];

			if ( '__core__' === $job ) {
				$core_done = self::check_core( false, $start, $state );
				if ( ! $core_done ) {
					// Time budget spent checking core files — persist and yield for next slice.
					WPEPP_Scan_Engine::persist( $state );
					return false;
				}
			} else {
				self::check_extension( $job );
			}

			$state['integrity_index'] = $index + 1;
			WPEPP_Scan_Engine::persist( $state );
		}

		return false; // Budget spent — resume next slice.
	}

	/**
	 * Fetch + cache a checksum manifest.
	 *
	 * @param string $url API URL.
	 * @return array File→md5 map, or [] on failure.
	 */
	private static function fetch_checksums( $url ) {
		$key = 'wpepp_checksum_' . md5( $url );

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 3,
				'user-agent' => 'WPEPP/' . ( defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '3.0' ),
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $key, [], 30 * MINUTE_IN_SECONDS ); // Negative cache.
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$map  = $body['checksums'] ?? [];

		set_transient( $key, is_array( $map ) ? $map : [], DAY_IN_SECONDS );

		return is_array( $map ) ? $map : [];
	}

	/**
	 * Verify WordPress core files against the official checksums.
	 *
	 * @param bool       $is_quick Whether this is a quick scan (checks top-level & critical loaders only).
	 * @param float|null $start    Microtime start timestamp for slice budget tracking.
	 * @param array|null $state    Current scan state reference.
	 * @return bool True if core checks completed, false if yielded due to slice budget.
	 */
	public static function check_core( $is_quick = false, $start = null, array &$state = null ) {
		global $wp_version, $wp_local_package;

		$url     = add_query_arg(
			[
				'version' => $wp_version,
				'locale'  => $wp_local_package ?? get_locale(),
			],
			self::API . '/core/checksums/1.0/'
		);
		$manifest = self::fetch_checksums( $url );

		if ( empty( $manifest ) ) {
			WPEPP_Issues::add( 'core_unverified', 1, 'api.wordpress.org unreachable', [] );
			return true;
		}

		$keys    = array_keys( $manifest );
		$total   = count( $keys );
		$cursor  = (int) ( $state['core_cursor'] ?? 0 );
		$budget  = WPEPP_Scan_Engine::SLICE_BUDGET - 0.5;

		while ( $cursor < $total ) {
			$rel_path = $keys[ $cursor ];
			$md5      = $manifest[ $rel_path ];
			$cursor++;

			// Check time budget periodically in Full Scan
			if ( ! $is_quick && null !== $start && 0 === ( $cursor % 30 ) ) {
				if ( ( microtime( true ) - $start ) >= $budget ) {
					$state['core_cursor'] = $cursor;
					return false; // Yield cleanly — more core files remain!
				}
			}

			// Only verify files OUTSIDE wp-content (themes/plugins handled separately).
			if ( 0 === strpos( $rel_path, 'wp-content/' ) ) {
				continue;
			}

			// In Quick Scan, only verify top-level root files and critical loaders,
			// leaving deep core subdirectories for Full Scan.
			if ( $is_quick ) {
				$is_root_file  = ( false === strpos( $rel_path, '/' ) );
				$is_key_loader = in_array( $rel_path, [
					'wp-admin/index.php', 'wp-admin/admin.php', 'wp-admin/admin-ajax.php',
					'wp-includes/version.php', 'wp-includes/pluggable.php', 'wp-includes/functions.php',
				], true );
				if ( ! $is_root_file && ! $is_key_loader ) {
					continue;
				}
			} else {
				// In Full Scan, verify scripts and code files; skip 2,200 pure static images/fonts/icons
				$ext = strtolower( pathinfo( $rel_path, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, [ 'php', 'js', 'html', 'htaccess' ], true ) ) {
					continue;
				}
			}

			$abs = wp_normalize_path( ABSPATH . ltrim( $rel_path, '/' ) );

			if ( ! file_exists( $abs ) ) {
				WPEPP_Issues::add( 'missing_file', 2, $rel_path, [ 'expected_md5' => $md5 ] );
				continue;
			}

			$actual = md5_file( $abs );

			if ( $actual && ! hash_equals( $md5, $actual ) ) {
				WPEPP_Issues::add(
					'core_modified',
					3,
					$rel_path,
					[
						'expected_md5' => $md5,
						'actual_md5'   => $actual,
						'size'         => filesize( $abs ),
					]
				);
			}
		}

		if ( isset( $state['core_cursor'] ) ) {
			unset( $state['core_cursor'] );
		}

		// In Quick Scan, skip deep recursive traversal of wp-admin and wp-includes.
		if ( $is_quick ) {
			return true;
		}

		// Unknown PHP files inside wp-admin / wp-includes (Full Scan only).
		foreach ( [ 'wp-admin', 'wp-includes' ] as $core_dir ) {
			$dir_abs = wp_normalize_path( ABSPATH . $core_dir );

			if ( ! is_dir( $dir_abs ) ) {
				continue;
			}

			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir_abs, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME )
			);

			foreach ( $it as $file ) {
				$rel = $core_dir . '/' . str_replace( '\\', '/', substr( wp_normalize_path( (string) $file ), strlen( $dir_abs ) + 1 ) );

				if ( isset( $manifest[ $rel ] ) ) {
					continue;
				}

				if ( 'php' === strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) ) ) {
					WPEPP_Issues::add( 'core_unknown', 3, $rel, [] );
				}
			}
		}

		return true;
	}

	/**
	 * Verify a plugin or theme directory against repository checksums.
	 *
	 * @param array $job {type,slug,version,dir}.
	 */
	private static function check_extension( array $job ) {
		if ( empty( $job['slug'] ) || empty( $job['version'] ) || ! is_dir( (string) $job['dir'] ) ) {
			return;
		}

		$type = 'plugin' === $job['type'] ? 'plugins' : 'themes';

		$manifest = self::fetch_checksums(
			add_query_arg(
				[ 'slug' => rawurlencode( $job['slug'] ), 'version' => rawurlencode( $job['version'] ) ],
				self::API . "/{$type}/checksums/1.0/"
			)
		);

		if ( empty( $manifest ) ) {
			// Non-wp.org asset (premium or custom plugin/theme) — cannot checksum from api.wordpress.org.
			// Do NOT flag as a security vulnerability; simply skip checksum verification.
			return;
		}

		// Compare manifest against disk.
		foreach ( $manifest as $rel_path => $md5 ) {
			$abs = wp_normalize_path( trailingslashit( $job['dir'] ) . ltrim( $rel_path, '/' ) );

			if ( ! file_exists( $abs ) ) {
				WPEPP_Issues::add( "{$type}_modified", 3, "{$job['slug']}/{$rel_path}", [ 'status' => 'missing', 'expected_md5' => $md5 ] );
				continue;
			}

			$actual = md5_file( $abs );

			if ( $actual && ! hash_equals( $md5, $actual ) ) {
				WPEPP_Issues::add(
					"{$type}_modified",
					3,
					"{$job['slug']}/{$rel_path}",
					[ 'status' => 'modified', 'expected_md5' => $md5, 'actual_md5' => $actual ]
				);
			}
		}

		// Unknown files inside the extension dir (not in any known manifest).
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( wp_normalize_path( (string) $job['dir'] ), FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $it as $file ) {
			$rel = str_replace( '\\\\', '/', substr( wp_normalize_path( (string) $file ), strlen( wp_normalize_path( (string) $job['dir'] ) ) + 1 ) );

			if ( isset( $manifest[ $rel ] ) ) {
				continue;
			}

			if ( 'php' === strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) ) ) {
				WPEPP_Issues::add( "{$type}_unknown", 2, "{$job['slug']}/{$rel}", [] );
			}
		}
	}
}
