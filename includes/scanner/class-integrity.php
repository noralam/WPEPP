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
				unset( $state['integrity_queue'], $state['integrity_index'] );
				WPEPP_Scan_Engine::advance( $state, 'options' );
				return false; // Stage complete.
			}

			$job = $queue[ $index ];

			if ( '__core__' === $job ) {
				self::check_core();
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
				'timeout'    => 8,
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
	 */
	public static function check_core() {
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
			return;
		}

		foreach ( $manifest as $rel_path => $md5 ) {

			// Only verify files OUTSIDE wp-content (themes/plugins handled separately).
			if ( 0 === strpos( $rel_path, 'wp-content/' ) ) {
				continue;
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

		// Unknown PHP files inside wp-admin / wp-includes.
		foreach ( [ 'wp-admin', 'wp-includes' ] as $core_dir ) {
			$dir_abs = wp_normalize_path( ABSPATH . $core_dir );

			if ( ! is_dir( $dir_abs ) ) {
				continue;
			}

			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir_abs, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME )
			);

			foreach ( $it as $file ) {
				$rel = $core_dir . '/' . str_replace( '\\\\', '/', substr( wp_normalize_path( (string) $file ), strlen( $dir_abs ) + 1 ) );

				if ( isset( $manifest[ $rel ] ) ) {
					continue;
				}

				if ( 'php' === strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) ) ) {
					WPEPP_Issues::add( 'core_unknown', 3, $rel, [] );
				}
			}
		}
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
			// Non-wp.org asset (premium) or API miss — flag once for review.
			WPEPP_Issues::add(
				"{$type}_unverified",
				1,
				$job['slug'],
				[ 'version' => $job['version'] ]
			);
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
