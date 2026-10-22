<?php
/**
 * File-level hardening tools — uploads PHP execution, directory listing,
 * DISALLOW_FILE_EDIT. Apache via marker blocks in .htaccess; nginx gets a
 * copy-paste snippet surfaced in the UI (servers rarely allow auto-edit).
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_File_Hardening
 */
class WPEPP_File_Hardening {

	const MARKER_BEGIN = '# BEGIN WPEPP Hardening';
	const MARKER_END   = '# END WPEPP Hardening';

	/**
	 * Boot: keep score item available.
	 */
	public static function boot() {
		add_filter( 'wpepp/security/score_items', [ __CLASS__, 'score_items' ] );
	}

	/* ── Snippet builders (pure) ── */

	public static function uploads_php_snippet() {
		return "<FilesMatch \"\\.(?i:php|phtml|php3|php4|php5|php7|phar)$\">\n\tRequire all denied\n</FilesMatch>";
	}

	public static function directory_listing_snippet() {
		return 'Options -Indexes';
	}

	public static function file_edit_snippet() {
		return "define( 'DISALLOW_FILE_EDIT', true );";
	}

	/* ── Marker-block writer ── */

	/**
	 * Insert or remove a marker-wrapped block inside .htaccess.
	 *
	 * @param string $file    Absolute path.
	 * @param string $snippet Body lines.
	 * @param string $tag     Unique tag inside markers, e.g. 'uploads-php'.
	 * @param bool   $enable  True = write, false = strip.
	 * @return true|\WP_Error
	 */
	public static function write_marker_block( $file, $snippet, $tag, $enable ) {
		$content = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

		$begin = self::MARKER_BEGIN . " {$tag}";
		$end   = self::MARKER_END . " {$tag}";

		// Strip any existing block for the tag first.
		$content = preg_replace(
			'/^' . preg_quote( $begin, '/' ) . '.*?^' . preg_quote( $end, '/' ) . '\n?/sm',
			'',
			$content
		);

		if ( is_wp_error( $content ) || null === $content ) {
			return new WP_Error( 'wpepp_strip_failed', __( 'Could not update the server configuration file.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		if ( $enable ) {
			if ( ! wp_is_writable( dirname( $file ) ) && ! file_exists( $file ) ) {
				return new WP_Error( 'wpepp_not_writable', __( 'The web root directory is not writable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
			}
			if ( file_exists( $file ) && ! wp_is_writable( $file ) ) {
				return new WP_Error( 'wpepp_not_writable', __( 'The configuration file is not writable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
			}

			$content = rtrim( $content ) . "\n\n{$begin}\n{$snippet}\n{$end}\n";
		}

		$bytes = file_put_contents( $file, ltrim( $content ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( false === $bytes ) {
			return new WP_Error( 'wpepp_write_failed', __( 'Could not write the configuration file.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		return true;
	}

	/**
	 * Whether a tag block currently exists in a file.
	 *
	 * @param string $file Path.
	 * @param string $tag  Tag.
	 * @return bool
	 */
	public static function has_block( $file, $tag ) {
		if ( ! file_exists( $file ) ) {
			return false;
		}

		$needle = self::MARKER_BEGIN . " {$tag}";

		return false !== strpos( (string) file_get_contents( $file ), $needle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
	}

	/* ── Tool implementations ── */

	private static function htaccess() {
		return ABSPATH . '.htaccess';
	}

	private static function uploads_htaccess() {
		$dir = wp_get_upload_dir();
		return trailingslashit( $dir['basedir'] ) . '.htaccess';
	}

	/**
	 * Toggle uploads PHP execution (Apache).
	 *
	 * @param bool $enable Block when true.
	 * @return true|\WP_Error|array Error or ['nginx' => snippet] on servers we cannot configure.
	 */
	public static function toggle_uploads_php( $enable ) {
		global $is_apache;

		$result = self::write_marker_block( self::uploads_htaccess(), self::uploads_php_snippet(), 'uploads-php', (bool) $enable );

		if ( is_wp_error( $result ) && ! file_exists( self::uploads_htaccess() ) ) {
			// Non-Apache or unwritable — surface manual snippet instead of failing hard.
			return [ 'nginx' => "# deny php in uploads\nlocation ~* /uploads/.*\\.(php|phtml|phar)$ { deny all; }" ];
		}

		return $result;
	}

	/**
	 * Toggle directory listing.
	 *
	 * @param bool $disable Disable listing when true.
	 * @return true|\WP_Error
	 */
	public static function toggle_directory_listing( $disable ) {
		return self::write_marker_block( self::htaccess(), self::directory_listing_snippet(), 'dirlist', (bool) $disable );
	}

	/**
	 * Toggle DISALLOW_FILE_EDIT in wp-config.php.
	 *
	 * Uses a conservative regex replace on an existing definition, else
	 * inserts above the "stop editing" comment.
	 *
	 * @param bool $enable Define when true.
	 * @return true|\WP_Error
	 */
	public static function toggle_file_edit( $enable ) {
		$file = ABSPATH . 'wp-config.php';

		if ( ! file_exists( $file ) || ! wp_is_writable( $file ) ) {
			return new WP_Error( 'wpepp_config_unwritable', __( 'wp-config.php is not writable.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		$content = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

		$pattern = '/^\s*define\(\s*[\'"]DISALLOW_FILE_EDIT[\'"],\s*(true|false)\s*\);\s*$/m';

		if ( preg_match( $pattern, $content ) ) {
			if ( ! $enable ) {
				$content = preg_replace( $pattern, '', $content );
			} else {
				$content = preg_replace( $pattern, "define( 'DISALLOW_FILE_EDIT', true );", $content );
			}
		} elseif ( $enable ) {
			$anchor = "/* That's all, stop editing!";

			if ( false === strpos( $content, $anchor ) ) {
				return new WP_Error( 'wpepp_anchor_missing', __( 'Could not find the safe insertion point in wp-config.php.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
			}

			$line   = "define( 'DISALLOW_FILE_EDIT', true );\n";
			$content = str_replace( $anchor, $line . "\n" . $anchor, $content );
		}

		// Atomic-ish write: temp + rename.
		$tmp = $file . '.wpepp-tmp';

		if ( false === file_put_contents( $tmp, $content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error( 'wpepp_write_failed', __( 'Could not write wp-config.php.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->move( $tmp, $file, true ) ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'wpepp_rename_failed', __( 'Could not finalize the wp-config.php change.', 'wp-edit-password-protected' ), [ 'status' => 500 ] );
		}

		if ( function_exists( 'opcache_invalidate' ) ) {
			@opcache_invalidate( $file ); // phpcs:ignore
		}

		return true;
	}

	/**
	 * Current tool states for the UI.
	 *
	 * @return array
	 */
	public static function status() {
		$config_file = ABSPATH . 'wp-config.php';
		$file_edit   = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;

		if ( ! $file_edit && file_exists( $config_file ) ) {
			$file_edit = (bool) preg_match(
				'/define\(\s*[\'"]DISALLOW_FILE_EDIT[\'"],\s*true\s*\)/',
				(string) file_get_contents( $config_file ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
			);
		}

		return [
			'uploads_php_blocked' => self::has_block( self::uploads_htaccess(), 'uploads-php' ),
			'dirlisting_disabled' => self::has_block( self::htaccess(), 'dirlist' ),
			'file_edit_disabled'  => $file_edit,
			'is_apache'           => (bool) ( $GLOBALS['is_apache'] ?? false ),
			'nginx_snippet'       => '# deny php execution inside uploads' . "\n" . 'location ~* /uploads/.*\.(php|phtml|phar)$ { deny all; }',
			'permissions'         => self::inspect_file_permissions(),
		];
	}

	/**
	 * Inspect permissions of critical WordPress files and directories.
	 *
	 * @return array
	 */
	public static function inspect_file_permissions() {
		$items = [
			'wp-config.php' => [
				'label'       => 'wp-config.php',
				'path'        => ABSPATH . 'wp-config.php',
				'expected'    => '0400, 0440, 0600 or 0644',
				'recommended' => '0440 / 0600',
				'is_dir'      => false,
			],
			'.htaccess'     => [
				'label'       => '.htaccess',
				'path'        => ABSPATH . '.htaccess',
				'expected'    => '0644 or 0444',
				'recommended' => '0644',
				'is_dir'      => false,
			],
			'.user.ini'     => [
				'label'       => '.user.ini',
				'path'        => ABSPATH . '.user.ini',
				'expected'    => '0644 or 0444',
				'recommended' => '0644',
				'is_dir'      => false,
			],
			'index.php'     => [
				'label'       => 'index.php (root)',
				'path'        => ABSPATH . 'index.php',
				'expected'    => '0644',
				'recommended' => '0644',
				'is_dir'      => false,
			],
			'wp-content'    => [
				'label'       => 'wp-content directory',
				'path'        => WP_CONTENT_DIR,
				'expected'    => '0755',
				'recommended' => '0755',
				'is_dir'      => true,
			],
		];

		$results = [];

		foreach ( $items as $key => $spec ) {
			$path = $spec['path'];

			if ( ! file_exists( $path ) ) {
				$results[ $key ] = [
					'key'         => $key,
					'label'       => $spec['label'],
					'exists'      => false,
					'path'        => $path,
					'perms_octal' => null,
					'writable'    => false,
					'expected'    => $spec['expected'],
					'recommended' => $spec['recommended'],
					'status'      => 'info',
					'message'     => __( 'File does not exist on this server', 'wp-edit-password-protected' ),
				];
				continue;
			}

			$perms    = @fileperms( $path );
			$octal    = false !== $perms ? substr( sprintf( '%o', $perms ), -4 ) : 'unknown';
			$writable = wp_is_writable( $path );

			$status  = 'ok';
			$message = __( 'Secured', 'wp-edit-password-protected' );

			if ( ! $spec['is_dir'] ) {
				if ( false !== $perms ) {
					$world = (int) substr( $octal, -1 );
					if ( ( $world & 2 ) !== 0 ) {
						$status  = 'fail';
						$message = __( 'World-writable! Critical security risk.', 'wp-edit-password-protected' );
					} elseif ( ( $world & 4 ) !== 0 && 'wp-config.php' === $key ) {
						$status  = 'warn';
						$message = __( 'World-readable. Restrict to 0440 or 0600 on production servers.', 'wp-edit-password-protected' );
					}
				}
			} else {
				if ( false !== $perms ) {
					$world = (int) substr( $octal, -1 );
					if ( ( $world & 2 ) !== 0 ) {
						$status  = 'warn';
						$message = __( 'Directory is world-writable.', 'wp-edit-password-protected' );
					}
				}
			}

			$results[ $key ] = [
				'key'         => $key,
				'label'       => $spec['label'],
				'exists'      => true,
				'path'        => $path,
				'perms_octal' => $octal,
				'writable'    => $writable,
				'expected'    => $spec['expected'],
				'recommended' => $spec['recommended'],
				'status'      => $status,
				'message'     => $message,
			];
		}

		return $results;
	}

	/**
	 * Score items.
	 *
	 * @param array[] $items Items.
	 * @return array[]
	 */
	public static function score_items( array $items ) {
		$status = self::status();

		$items[] = [
			'id'       => 'uploads_php_off',
			'label'    => __( 'PHP execution disabled in uploads', 'wp-edit-password-protected' ),
			'weight'   => 8,
			'category' => 'harden',
			'done'     => (bool) $status['uploads_php_blocked'],
			'route'    => '/hardening',
		];

		return $items;
	}
}
