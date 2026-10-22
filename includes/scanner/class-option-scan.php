<?php
/**
 * Suspicious-option and user-account scans (fast, DB-only stages).
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Option_Scan
 */
class WPEPP_Option_Scan {

	/**
	 * Run the options stage.
	 */
	public static function run() {
		self::check_site_urls();
		self::check_roles();
	}

	/**
	 * Detect siteurl/home overrides pointing somewhere else.
	 */
	private static function check_site_urls() {
		$siteurl = wp_parse_url( (string) get_option( 'siteurl', '' ), PHP_URL_HOST );
		$home    = wp_parse_url( (string) get_option( 'home', '' ), PHP_URL_HOST );

		foreach (
			[
				'siteurl' => $siteurl,
				'home'    => $home,
			] as $key => $host
		) {
			if ( ! is_string( $host ) || '' === $host ) {
				continue;
			}

			if ( false !== strpos( $host, 'localhost:' ) || filter_var( $host, FILTER_VALIDATE_IP ) ) {
				WPEPP_Issues::add(
					'suspicious_option',
					2,
					"option:{$key}",
					[ 'value_host' => $host ]
				);
			}
		}
	}

	/**
	 * Detect roles with admin caps that shouldn't have them.
	 */
	private static function check_roles() {
		$roles = wp_roles()->get_names();

		foreach ( array_keys( $roles ) as $role_key ) {

			// Only inspect non-standard roles.
			if ( in_array( $role_key, [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ], true ) ) {
				continue;
			}

			$role = get_role( $role_key );

			if ( $role && ! empty( $role->capabilities['manage_options'] ) && 1 === count( get_users( [ 'role' => $role_key, 'fields' => 'ID', 'number' => 1 ] ) ) ) {
				WPEPP_Issues::add(
					'suspicious_option',
					3,
					"role_admin_cap:{$role_key}",
					[]
				);
			}
		}
	}
}

/**
 * Class WPEPP_User_Scan
 */
class WPEPP_User_Scan {

	/**
	 * Run the users stage.
	 */
	public static function run() {
		$admins = get_users( [ 'role' => 'administrator' ] );

		// Flag admins created within the last 14 days for review.
		$cutoff = time() - 14 * DAY_IN_SECONDS;

		foreach ( $admins as $admin ) {
			$registered = strtotime( (string) $admin->user_registered );

			if ( $registered && $registered > $cutoff ) {
				// Ignore the current installer context.
				WPEPP_Issues::add(
					'recent_admin',
					2,
					'user:' . $admin->user_login,
					[
						'id'         => $admin->ID,
						'email'      => $admin->user_email,
						'registered' => gmdate( 'Y-m-d H:i:s', $registered ),
					]
				);
			}
		}
	}
}
