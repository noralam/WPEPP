<?php
/**
 * CPU Monitor — Plugin Performance Monitor (Pro).
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_CPU_Plugin_Monitor
 */
class WPEPP_CPU_Plugin_Monitor {

	/**
	 * Get active plugin stats (load time, query count).
	 *
	 * @return array
	 */
	public static function get_plugin_stats() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active  = get_option( 'active_plugins', [] );
		$plugins = get_plugins();
		$stats   = [];

		foreach ( $active as $plugin_file ) {
			$data = $plugins[ $plugin_file ] ?? [];
			$slug = dirname( $plugin_file );
			if ( '.' === $slug ) {
				$slug = basename( $plugin_file, '.php' );
			}

			$stats[] = [
				'file'    => sanitize_text_field( $plugin_file ),
				'name'    => sanitize_text_field( $data['Name'] ?? $slug ),
				'version' => sanitize_text_field( $data['Version'] ?? '' ),
				'slug'    => sanitize_text_field( $slug ),
				'author'  => wp_strip_all_tags( $data['Author'] ?? '' ),
				'is_self' => plugin_basename( WPEPP_FILE ) === $plugin_file,
			];
		}

		// Sort alphabetically.
		usort( $stats, static function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		} );

		return $stats;
	}

	/**
	 * Deactivate a plugin.
	 *
	 * @param string $plugin_file Plugin file relative to plugins directory.
	 * @return true|WP_Error
	 */
	public static function deactivate_plugin( $plugin_file ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = sanitize_text_field( $plugin_file );

		// Prevent deactivating self.
		if ( plugin_basename( WPEPP_FILE ) === $plugin_file ) {
			return new WP_Error(
				'cannot_deactivate_self',
				__( 'Cannot deactivate WPEPP from within itself.', 'wp-edit-password-protected' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! is_plugin_active( $plugin_file ) ) {
			return new WP_Error(
				'not_active',
				__( 'Plugin is not active.', 'wp-edit-password-protected' ),
				[ 'status' => 400 ]
			);
		}

		deactivate_plugins( $plugin_file );

		return true;
	}
}
