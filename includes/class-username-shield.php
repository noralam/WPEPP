<?php
/**
 * Username Shield — block common username-discovery vectors (FREE feature).
 *
 * Covers:
 *   - ?author=N scans that leak login names via redirect
 *   - oEmbed author name exposure
 *   - wp-sitemap.xml users provider
 *   - Uniform "invalid" login errors (no username/password oracle)
 *   - Optional banned-username list on registration
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Username_Shield
 */
class WPEPP_Username_Shield {

	/**
	 * Register hooks. Called from WPEPP_Security::init().
	 *
	 * @param array $settings Decoded security settings.
	 */
	public static function init( $settings ) {
		// 1. Kill ?author=N enumeration redirects for logged-out visitors.
		add_filter( 'author_link', [ __CLASS__, 'filter_author_link' ], 99, 2 );
		add_action( 'template_redirect', [ __CLASS__, 'maybe_block_author_scan' ], 0 );

		// 2. Remove author name from oEmbed responses.
		add_filter( 'oembed_response_data', [ __CLASS__, 'filter_oembed_author' ] );

		// 3. Drop the users sitemap provider.
		add_filter( 'wp_sitemaps_add_provider', [ __CLASS__, 'remove_users_sitemap' ], 10, 2 );

		// 4. Uniform login errors — same message for unknown user vs wrong password.
		if ( ! empty( $settings['username_shield_mask_errors'] ) ) {
			add_filter( 'authenticate', [ __CLASS__, 'mask_login_errors' ], 100, 3 );
		}

		// 5. Banned usernames on registration.
		if ( ! empty( $settings['username_shield_banned'] ) ) {
			add_filter( 'registration_errors', [ __CLASS__, 'check_banned_username' ], 10, 3 );
		}
	}

	/**
	 * Replace author archive links with home URL for logged-out users,
	 * so scraped pages never contain a resolvable ?author=N link.
	 *
	 * @param string $link    Author link.
	 * @param int    $user_id Author user ID.
	 * @return string
	 */
	public static function filter_author_link( $link, $user_id ) {
		unset( $user_id );

		if ( is_user_logged_in() ) {
			return $link;
		}

		return home_url( '/' );
	}

	/**
	 * Block direct ?author=N visits for logged-out users.
	 */
	public static function maybe_block_author_scan() {
		if ( is_admin() || is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public query var, read-only check.
		$author = isset( $_GET['author'] ) ? absint( $_GET['author'] ) : 0;

		if ( $author > 0 && is_author() === false ) {
			status_header( 403 );
			nocache_headers();
			wp_die(
				esc_html__( 'Access denied.', 'wp-edit-password-protected' ),
				esc_html__( 'Forbidden', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// Also strip the author name from real author archives.
		if ( is_author() ) {
			add_filter( 'get_the_archive_title', [ __CLASS__, 'neutralize_archive_title' ], 99 );
		}
	}

	/**
	 * Neutralize the display name in author-archive titles for logged-out users.
	 *
	 * @param string $title Archive title.
	 * @return string
	 */
	public static function neutralize_archive_title( $title ) {
		if ( is_user_logged_in() ) {
			return $title;
		}

		return __( 'Author Archive', 'wp-edit-password-protected' );
	}

	/**
	 * Strip author name/slugs from oEmbed payloads.
	 *
	 * @param array $data oEmbed response data.
	 * @return array
	 */
	public static function filter_oembed_author( $data ) {
		if ( ! is_user_logged_in() ) {
			unset( $data['author_name'], $data['author_url'] );
		}

		return $data;
	}

	/**
	 * Remove the users sitemap provider entirely.
	 *
	 * @param object $provider Sitemap provider.
	 * @param string $name     Provider name.
	 * @return object|false
	 */
	public static function remove_users_sitemap( $provider, $name ) {
		if ( 'users' === $name && ! current_user_can( 'list_users' ) ) {
			return false;
		}

		return $provider;
	}

	/**
	 * Normalize invalid-username vs wrong-password errors into one message
	 * so attackers cannot probe which usernames exist.
	 *
	 * @param \WP_User|\WP_Error|null $user     Auth result.
	 * @param string                  $username Attempted username.
	 * @param string                  $password Attempted password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function mask_login_errors( $user, $username, $password ) {
		unset( $password );

		if ( empty( $username ) ) {
			return $user;
		}

		if ( is_wp_error( $user ) ) {
			$error_codes = [
				'invalid_username',
				'incorrect_password',
				'invalid_email',
				'invalidcombo',
			];

			foreach ( $error_codes as $code ) {
				if ( $user->get_error_code() === $code ) {
					return new \WP_Error(
						'wpepp_invalid_login',
						sprintf(
							/* translators: %s: username or email attempted */
							__( '<strong>Error:</strong> The password you entered for the username %s is incorrect.', 'wp-edit-password-protected' ),
							'<strong>' . esc_html( $username ) . '</strong>'
						)
					);
				}
			}
		}

		return $user;
	}

	/**
	 * Block registration with banned usernames.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email.
	 * @return \WP_Error
	 */
	public static function check_banned_username( $errors, $sanitized_user_login, $user_email ) {
		unset( $user_email );

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( $raw, true );
		$list     = $settings['username_shield_banned'] ?? '';

		if ( ! is_string( $list ) || '' === trim( $list ) ) {
			return $errors;
		}

		$banned = array_filter( array_map( 'strtolower', array_map( 'trim', preg_split( '/[\n\r,]+/', strtolower( $list ) ) ) ) );

		if ( in_array( strtolower( (string) $sanitized_user_login ), $banned, true ) ) {
			$errors->add(
				'wpepp_banned_username',
				__( '<strong>Error:</strong> This username is not allowed.', 'wp-edit-password-protected' )
			);
		}

		return $errors;
	}
}
