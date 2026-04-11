<?php
/**
 * CPU Monitor — PHP Error Log Parser (Pro).
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Error_Log
 */
class WPEPP_CPU_Error_Log {

	/**
	 * Get parsed error log entries.
	 *
	 * @param int $max_lines Max lines to read from end of file.
	 * @return array|WP_Error
	 */
	public static function get_entries( $max_lines = 200 ) {
		$log_path = self::get_log_path();

		if ( ! $log_path || ! is_readable( $log_path ) ) {
			return new WP_Error(
				'log_not_found',
				__( 'Error log file not found or not readable.', 'wp-edit-password-protected' ),
				[ 'status' => 404 ]
			);
		}

		$lines  = self::read_last_lines( $log_path, $max_lines );
		$parsed = self::parse_lines( $lines );

		return $parsed;
	}

	/**
	 * Determine the error log file path.
	 *
	 * @return string|false
	 */
	private static function get_log_path() {
		// Check WP debug log constant first.
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && '' !== WP_DEBUG_LOG ) {
			if ( is_readable( WP_DEBUG_LOG ) ) {
				return WP_DEBUG_LOG;
			}
		}

		// Default WordPress debug log path.
		$wp_default = WP_CONTENT_DIR . '/debug.log';
		if ( is_readable( $wp_default ) ) {
			return $wp_default;
		}

		// PHP error log from ini.
		$php_log = ini_get( 'error_log' );
		if ( $php_log && is_readable( $php_log ) ) {
			return $php_log;
		}

		return false;
	}

	/**
	 * Read last N lines of a file efficiently.
	 *
	 * @param string $filepath File path.
	 * @param int    $count    Number of lines.
	 * @return array
	 */
	private static function read_last_lines( $filepath, $count ) {
		$handle = fopen( $filepath, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local log file.
		if ( ! $handle ) {
			return [];
		}

		$lines    = [];
		$buffer   = '';
		$chunk    = 4096;
		$filesize = filesize( $filepath );

		if ( 0 === $filesize ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return [];
		}

		$pos = $filesize;
		while ( $pos > 0 && count( $lines ) < $count ) {
			$read_size = min( $chunk, $pos );
			$pos      -= $read_size;
			fseek( $handle, $pos );
			$buffer = fread( $handle, $read_size ) . $buffer; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$lines  = explode( "\n", $buffer );
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		// Remove empty trailing line.
		if ( '' === end( $lines ) ) {
			array_pop( $lines );
		}

		return array_slice( $lines, -$count );
	}

	/**
	 * Parse raw log lines into structured entries.
	 *
	 * @param array $lines Raw log lines.
	 * @return array
	 */
	private static function parse_lines( $lines ) {
		$entries  = [];
		$grouped  = [];
		$pattern  = '/^\[(?P<date>[^\]]+)\]\s*(?:PHP\s+)?(?P<type>Fatal error|Warning|Notice|Deprecated|Parse error|Strict Standards):\s*(?P<message>.+?)(?:\s+in\s+(?P<file>.+?)\s+on\s+line\s+(?P<line>\d+))?$/i';

		foreach ( $lines as $raw_line ) {
			$raw_line = trim( $raw_line );
			if ( '' === $raw_line ) {
				continue;
			}

			if ( preg_match( $pattern, $raw_line, $m ) ) {
				$type = self::normalize_type( $m['type'] );
				$key  = md5( $type . ( $m['message'] ?? '' ) . ( $m['file'] ?? '' ) . ( $m['line'] ?? '' ) );

				if ( isset( $grouped[ $key ] ) ) {
					$grouped[ $key ]['count']++;
					$grouped[ $key ]['last_seen'] = sanitize_text_field( $m['date'] );
				} else {
					$grouped[ $key ] = [
						'type'      => $type,
						'message'   => sanitize_text_field( $m['message'] ?? '' ),
						'file'      => sanitize_text_field( $m['file'] ?? '' ),
						'line'      => (int) ( $m['line'] ?? 0 ),
						'first_seen' => sanitize_text_field( $m['date'] ),
						'last_seen'  => sanitize_text_field( $m['date'] ),
						'count'     => 1,
					];
				}
			}
		}

		$entries = array_values( $grouped );

		// Sort by count descending.
		usort( $entries, static function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		} );

		return $entries;
	}

	/**
	 * Normalize error type label.
	 *
	 * @param string $type Raw type string.
	 * @return string
	 */
	private static function normalize_type( $type ) {
		$type = strtolower( trim( $type ) );
		$map  = [
			'fatal error'     => 'fatal',
			'parse error'     => 'fatal',
			'warning'         => 'warning',
			'notice'          => 'notice',
			'deprecated'      => 'deprecated',
			'strict standards' => 'notice',
		];

		return $map[ $type ] ?? 'warning';
	}
}
