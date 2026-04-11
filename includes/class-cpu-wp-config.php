<?php
/**
 * CPU Monitor — wp-config.php constant manager.
 *
 * Safely reads and toggles constants (SAVEQUERIES, WP_DEBUG, WP_DEBUG_LOG)
 * in the site's wp-config.php without requiring manual file edits.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_WP_Config
 */
class WPEPP_CPU_WP_Config {

	/**
	 * Allowed constants that can be toggled.
	 *
	 * @var string[]
	 */
	const ALLOWED_CONSTANTS = [
		'SAVEQUERIES',
		'WP_DEBUG',
		'WP_DEBUG_LOG',
	];

	/**
	 * Get the current status of all managed constants.
	 *
	 * Reads values directly from wp-config.php so the response reflects
	 * any changes made during the same request (runtime constants don't
	 * update until the next page load).
	 *
	 * @return array Associative array of constant => bool.
	 */
	public static function get_status() {
		$status = [];

		$config_path = self::get_config_path();
		$from_file   = [];

		if ( ! is_wp_error( $config_path ) ) {
			$contents = file_get_contents( $config_path );
			if ( false !== $contents ) {
				$from_file = self::parse_constants( $contents );
			}
		}

		foreach ( self::ALLOWED_CONSTANTS as $constant ) {
			// Prefer file value (accurate after toggle), fall back to runtime.
			if ( isset( $from_file[ $constant ] ) ) {
				$status[ $constant ] = $from_file[ $constant ];
			} else {
				$status[ $constant ] = defined( $constant ) && constant( $constant );
			}
		}

		$status['writable'] = self::is_config_writable();

		return $status;
	}

	/**
	 * Parse define() values for managed constants from wp-config.php content.
	 *
	 * @param string $contents File contents.
	 * @return array Associative array of constant => bool.
	 */
	private static function parse_constants( $contents ) {
		$result = [];

		foreach ( self::ALLOWED_CONSTANTS as $constant ) {
			// Match define() lines that are NOT commented out. Skip // and # comments.
			$pattern = '/^[ \t]*define\s*\(\s*[\'"]' . preg_quote( $constant, '/' ) . '[\'"]\s*,\s*(.+?)\s*\)\s*;/m';
			if ( preg_match_all( $pattern, $contents, $matches ) ) {
				// Use the last non-commented match (in case of duplicates).
				$val = strtolower( trim( end( $matches[1] ) ) );
				// Evaluate common PHP boolean representations.
				$result[ $constant ] = in_array( $val, [ 'true', '1', "'1'" ], true );
			}
		}

		return $result;
	}

	/**
	 * Toggle a constant in wp-config.php.
	 *
	 * @param string $constant The constant name.
	 * @param bool   $value    Whether to enable or disable.
	 * @return true|WP_Error
	 */
	public static function toggle_constant( $constant, $value ) {
		if ( ! in_array( $constant, self::ALLOWED_CONSTANTS, true ) ) {
			return new WP_Error(
				'invalid_constant',
				__( 'This constant cannot be managed.', 'wp-edit-password-protected' ),
				[ 'status' => 400 ]
			);
		}

		$config_path = self::get_config_path();
		if ( is_wp_error( $config_path ) ) {
			return $config_path;
		}

		if ( ! is_writable( $config_path ) ) {
			return new WP_Error(
				'config_not_writable',
				__( 'wp-config.php is not writable. Please check file permissions.', 'wp-edit-password-protected' ),
				[ 'status' => 403 ]
			);
		}

		$contents = file_get_contents( $config_path );
		if ( false === $contents ) {
			return new WP_Error(
				'config_read_error',
				__( 'Could not read wp-config.php.', 'wp-edit-password-protected' ),
				[ 'status' => 500 ]
			);
		}

		$php_value = $value ? 'true' : 'false';

		// Pattern: define( 'CONSTANT', value );
		$pattern = '/^(\s*)define\s*\(\s*[\'"]' . preg_quote( $constant, '/' ) . '[\'"]\s*,\s*[^)]*\)\s*;/m';

		if ( preg_match( $pattern, $contents ) ) {
			// Replace existing define.
			$replacement = "define( '{$constant}', {$php_value} );";
			$contents    = preg_replace( $pattern, $replacement, $contents );
		} else {
			// Insert new define before the stop-editing marker or wp-settings require.
			$insert_line = "define( '{$constant}', {$php_value} );\n";
			$inserted    = false;

			// Try inserting before "That's all, stop editing!" comment.
			$marker = "/* That's all, stop editing!";
			$pos    = strpos( $contents, $marker );
			if ( false !== $pos ) {
				$contents = substr_replace( $contents, $insert_line, $pos, 0 );
				$inserted = true;
			}

			// Fallback: insert before require_once ABSPATH . 'wp-settings.php'.
			if ( ! $inserted ) {
				$settings_pattern = '/^.*require.*wp-settings\.php.*/m';
				if ( preg_match( $settings_pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
					$pos      = $matches[0][1];
					$contents = substr_replace( $contents, $insert_line, $pos, 0 );
					$inserted = true;
				}
			}

			if ( ! $inserted ) {
				return new WP_Error(
					'config_insert_failed',
					__( 'Could not find a suitable location in wp-config.php to insert the constant.', 'wp-edit-password-protected' ),
					[ 'status' => 500 ]
				);
			}
		}

		// Write atomically: write to temp file then rename.
		$temp_path = $config_path . '.wpepp-tmp';
		$written   = file_put_contents( $temp_path, $contents, LOCK_EX );

		if ( false === $written ) {
			return new WP_Error(
				'config_write_error',
				__( 'Could not write to wp-config.php.', 'wp-edit-password-protected' ),
				[ 'status' => 500 ]
			);
		}

		if ( ! rename( $temp_path, $config_path ) ) {
			@unlink( $temp_path );
			return new WP_Error(
				'config_rename_error',
				__( 'Could not replace wp-config.php.', 'wp-edit-password-protected' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Find wp-config.php path.
	 *
	 * @return string|WP_Error
	 */
	private static function get_config_path() {
		// Standard location.
		$path = ABSPATH . 'wp-config.php';
		if ( file_exists( $path ) ) {
			return $path;
		}

		// One directory up (common setup).
		$path = dirname( ABSPATH ) . '/wp-config.php';
		if ( file_exists( $path ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $path;
		}

		return new WP_Error(
			'config_not_found',
			__( 'wp-config.php could not be located.', 'wp-edit-password-protected' ),
			[ 'status' => 500 ]
		);
	}

	/**
	 * Check if wp-config.php is writable.
	 *
	 * @return bool
	 */
	private static function is_config_writable() {
		$path = self::get_config_path();
		if ( is_wp_error( $path ) ) {
			return false;
		}
		return is_writable( $path );
	}
}
