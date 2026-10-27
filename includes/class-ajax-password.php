<?php
/**
 * AJAX Password Check Handlers.
 *
 * Handles server-side password verification used by the inline (no-reload)
 * error display feature for both post password forms and the site-wide
 * password protection form.
 *
 * @package wpepp
 * @since   2.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Ajax_Password
 */
class WPEPP_Ajax_Password {

	/**
	 * Register AJAX action hooks. Call once from the main plugin class.
	 */
	public static function register_hooks() {
		// Post password check (individual protected posts/pages).
		add_action( 'wp_ajax_wpepp_check_password',        [ __CLASS__, 'check_post_password' ] );
		add_action( 'wp_ajax_nopriv_wpepp_check_password', [ __CLASS__, 'check_post_password' ] );

		// Site-wide password check.
		add_action( 'wp_ajax_wpepp_check_site_password',        [ __CLASS__, 'check_site_password' ] );
		add_action( 'wp_ajax_nopriv_wpepp_check_site_password', [ __CLASS__, 'check_site_password' ] );
	}

	// -------------------------------------------------------------------------
	// Post password
	// -------------------------------------------------------------------------

	/**
	 * Rate-limit AJAX password attempts. Max 5 failures per 5 minutes per IP.
	 *
	 * @param string $action Action identifier.
	 * @return bool True if allowed, false if locked out.
	 */
	private static function check_rate_limit( $action = 'post_pass' ) {
		$ip   = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$key  = 'wpepp_rl_ajax_' . md5( $action . '_' . $ip );
		$hits = (int) get_transient( $key );

		return $hits < 5;
	}

	/**
	 * Record a failed attempt and log if threshold reached.
	 *
	 * @param string $action  Action identifier.
	 * @param string $context Context description.
	 */
	private static function record_failure( $action = 'post_pass', $context = '' ) {
		$ip   = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$key  = 'wpepp_rl_ajax_' . md5( $action . '_' . $ip );
		$hits = (int) get_transient( $key ) + 1;
		set_transient( $key, $hits, 300 ); // 5-minute lockout window.

		if ( class_exists( 'WPEPP_Logger' ) ) {
			WPEPP_Logger::log(
				'password_bruteforce_attempt',
				sprintf( 'Failed AJAX password attempt on %s from %s (attempt %d/5)', $context, $ip, $hits ),
				$hits >= 5 ? WPEPP_Logger::SEV_CRITICAL : WPEPP_Logger::SEV_WARN,
				[ 'ip' => $ip, 'action' => $action, 'attempt' => $hits ]
			);
		}
	}

	/**
	 * AJAX: verify a post password without page reload.
	 *
	 * Expects POST fields:
	 *   nonce    – wpepp_check_password nonce
	 *   post_id  – int, the protected post ID
	 *   password – plain-text password entered by the visitor
	 *
	 * Returns JSON {success:true} when the password matches, {success:false} otherwise.
	 */
	public static function check_post_password() {
		check_ajax_referer( 'wpepp_check_password', 'nonce' );

		if ( ! self::check_rate_limit( 'post_pass' ) ) {
			wp_send_json_error( [
				'message' => 'too_many_attempts',
				'error'   => __( 'Too many failed attempts. Please try again in 5 minutes.', 'wp-edit-password-protected' ),
			] );
		}

		$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw value required for comparison.
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'invalid_post' ] );
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_send_json_error( [ 'message' => 'invalid_post' ] );
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			wp_send_json_error( [ 'message' => 'invalid_post' ] );
		}

		if ( empty( $post->post_password ) ) {
			// Post has no password — let the form submit through.
			wp_send_json_success();
		}

		// WordPress stores post passwords as plain text; use timing-safe comparison.
		if ( hash_equals( (string) $post->post_password, (string) $password ) ) {
			wp_send_json_success();
		} else {
			self::record_failure( 'post_pass', 'Post #' . $post_id );
			wp_send_json_error( [ 'message' => 'wrong_password' ] );
		}
	}

	// -------------------------------------------------------------------------
	// Site-wide password
	// -------------------------------------------------------------------------

	/**
	 * AJAX: verify the site-wide password without page reload.
	 *
	 * Expects POST fields:
	 *   nonce    – wpepp_check_site_password nonce
	 *   password – plain-text password entered by the visitor
	 *
	 * Returns JSON {success:true} when the password matches, {success:false} otherwise.
	 * On success the JS lets the original form submit to set the cookie server-side.
	 */
	public static function check_site_password() {
		check_ajax_referer( 'wpepp_check_site_password', 'nonce' );

		if ( ! self::check_rate_limit( 'site_pass' ) ) {
			wp_send_json_error( [
				'message' => 'too_many_attempts',
				'error'   => __( 'Too many failed attempts. Please try again in 5 minutes.', 'wp-edit-password-protected' ),
			] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw value required for comparison.
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		$raw      = get_option( 'wpepp_site_access_settings', '{}' );
		$settings = json_decode( $raw, true );

		if ( ! is_array( $settings )
			|| empty( $settings['site_password_enabled'] )
			|| empty( $settings['site_password'] ) ) {
			// Site password not active — let through.
			wp_send_json_success();
		}

		if ( class_exists( 'WPEPP_Site_Access' ) && WPEPP_Site_Access::verify_site_password( (string) $password ) ) {
			wp_send_json_success();
		} else {
			self::record_failure( 'site_pass', 'Site Password' );
			wp_send_json_error( [ 'message' => 'wrong_password' ] );
		}
	}
}
