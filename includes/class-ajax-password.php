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

		$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw value required for comparison.
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'invalid_post' ] );
		}

		$post = get_post( $post_id );

		if ( ! $post || empty( $post->post_password ) ) {
			// Post has no password — let the form submit through.
			wp_send_json_success();
		}

		// WordPress stores post passwords as plain text in post_password.
		if ( $post->post_password === $password ) {
			wp_send_json_success();
		} else {
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

		if ( sanitize_text_field( $password ) === $settings['site_password'] ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( [ 'message' => 'wrong_password' ] );
		}
	}
}
