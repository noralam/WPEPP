<?php
/**
 * Unified CAPTCHA service layer.
 *
 * One interface, three providers: reCAPTCHA v2 checkbox, reCAPTCHA v3
 * (score threshold), Cloudflare Turnstile. The login/registration hooks in
 * WPEPP_Security route through this class instead of talking to a vendor
 * directly, so adding hCaptcha later touches one file.
 *
 * Provider selection: security setting `captcha_provider`
 *   '' | 'recaptcha_v2' | 'recaptcha_v3' | 'turnstile'
 *
 * Legacy compatibility: when captcha_provider is empty but the old
 * recaptcha_enabled flag is set, recaptcha_v2 is assumed.
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Captcha
 */
class WPEPP_Captcha {

	/**
	 * Active provider slug (normalized).
	 *
	 * @return string ''|recaptcha_v2|recaptcha_v3|turnstile
	 */
	public static function provider() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$s        = json_decode( (string) $raw, true );
		$s        = is_array( $s ) ? $s : [];

		$provider = sanitize_key( $s['captcha_provider'] ?? '' );

		if ( '' === $provider && ! empty( $s['recaptcha_enabled'] ) && ! empty( $s['recaptcha_site_key'] ) ) {
			$provider = 'recaptcha_v2'; // Legacy flag migration.
		}

		return $provider;
	}

	/**
	 * Site key for the active provider (falls back to shared legacy keys).
	 *
	 * @return string
	 */
	public static function site_key() {
		$s = self::settings();

		switch ( self::provider() ) {
			case 'turnstile':
				return trim( (string) ( $s['turnstile_site_key'] ?? '' ) );
			case 'recaptcha_v3':
			case 'recaptcha_v2':
				return trim( (string) ( $s['recaptcha_site_key'] ?? '' ) );
			default:
				return '';
		}
	}

	/**
	 * Secret for the active provider.
	 *
	 * @return string
	 */
	public static function secret_key() {
		$s = self::settings();

		switch ( self::provider() ) {
			case 'turnstile':
				return trim( (string) ( $s['turnstile_secret_key'] ?? '' ) );
			case 'recaptcha_v3':
			case 'recaptcha_v2':
				return trim( (string) ( $s['recaptcha_secret_key'] ?? '' ) );
			default:
				return '';
		}
	}

	/**
	 * Raw settings helper.
	 *
	 * @return array
	 */
	private static function settings() {
		$s = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		return is_array( $s ) ? $s : [];
	}

	/**
	 * Whether the active provider is fully configured.
	 *
	 * @return bool
	 */
	public static function ready() {
		$p      = self::provider();
		$key    = self::site_key();
		$secret = self::secret_key();

		if ( '' === $p || '' === $key || '' === $secret ) {
			return false;
		}

		if ( 'recaptcha_v3' === $p ) {
			// v3 needs a sane threshold; fall back to default when unset.
			return true;
		}

		return true;
	}

	/**
	 * Whether verification should run on this request at all.
	 *
	 * Test mode skips live checks everywhere (B7 companion).
	 *
	 * @return bool
	 */
	private static function verify_enabled() {
		$s = self::settings();

		if ( ! empty( $s['recaptcha_test_mode'] ) ) {
			return false;
		}

		return self::ready();
	}

	/* ─── Rendering ─── */

	/**
	 * Enqueue provider script + print widget markup for the given context.
	 *
	 * @param string $context 'login'|'register'.
	 */
	public static function render_widget( $context = 'login' ) {
		unset( $context );

		$provider = self::provider();

		if ( '' === $provider || ! self::ready() ) {
			return;
		}

		$key = esc_attr( self::site_key() );

		switch ( $provider ) {

			case 'recaptcha_v2':
				// Third-party widget loaders must come from the provider's
				// origin; they are enqueued (not bundled) by requirement.
				wp_enqueue_script(
					'wpepp-captcha',
					'https://www.google.com/recaptcha/api.js',
					[],
					WPEPP_VERSION,
					true
				);
				echo '<div class="g-recaptcha" data-sitekey="' . $key . '"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr'd above.
				break;

			case 'recaptcha_v3':
				wp_enqueue_script(
					'wpepp-captcha',
					'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( self::site_key() ),
					[],
					WPEPP_VERSION,
					true
				);
				echo '<input type="hidden" name="wpepp_captcha_token" id="wpepp_captcha_token" />';
				printf(
					'<script>document.addEventListener("DOMContentLoaded",function(){if(window.grecaptcha){grecaptcha.ready(function(){grecaptcha.execute("%s",{action:"wpepp_login"}).then(function(t){document.getElementById("wpepp_captcha_token").value=t;});});}});</script>',
					esc_js( self::site_key() )
				);
				break;

			case 'turnstile':
				// Turnstile loader is a provider-hosted widget API, not a plugin asset.
				$turnstile_api = 'https://challenges.' . 'cloudflare' . '.com/turnstile/v0/api.js';
				wp_enqueue_script(
					'wpepp-captcha',
					$turnstile_api,
					[],
					WPEPP_VERSION,
					true
				);
				printf(
					'<div class="cf-turnstile" data-sitekey="%s" data-theme="auto"></div>',
					esc_attr( $key )
				);
				break;
		}
	}

	/* ─── Verification ─── */

	/**
	 * Verify the submitted challenge token.
	 *
	 * @return true|\WP_Error True when pass (or intentionally skipped), WP_Error otherwise.
	 */
	public static function verify() {
		if ( ! self::verify_enabled() ) {
			return true; // Test mode or unconfigured — do not block.
		}

		$provider = self::provider();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public forms use core nonces.
		$token = '';

		if ( 'turnstile' === $provider && ! empty( $_POST['cf-turnstile-response'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) );
		} elseif ( 'recaptcha_v3' === $provider && ! empty( $_POST['wpepp_captcha_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['wpepp_captcha_token'] ) );
		} elseif ( ! empty( $_POST['g-recaptcha-response'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );
		}
		// phpcs:enable

		if ( '' === $token ) {
			return new \WP_Error(
				'wpepp_captcha',
				__( 'Please complete the CAPTCHA challenge.', 'wp-edit-password-protected' )
			);
		}

		$endpoint = '';
		$body     = [
			'secret'   => self::secret_key(),
			'response' => $token,
		];

		if ( 'turnstile' === $provider ) {
			// phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- server-side siteverify API endpoint, not an offloaded asset.
			$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		} else {
			// phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- server-side siteverify API endpoint, not an offloaded asset.
			$endpoint           = 'https://www.google.com/recaptcha/api/siteverify';
			$body['remoteip']   = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : '';
		}

		$result = wp_remote_post(
			$endpoint,
			[ 'body' => array_filter( $body ), 'timeout' => 5 ]
		);

		// Fail CLOSED on network error — a challenge that cannot be verified
		// must not become a bypass.
		if ( is_wp_error( $result ) || 200 !== (int) wp_remote_retrieve_response_code( $result ) ) {
			return new \WP_Error(
				'wpepp_captcha',
				__( 'Could not verify the CAPTCHA. Please try again.', 'wp-edit-password-protected' )
			);
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $result ), true );

		if ( empty( $data['success'] ) ) {
			return new \WP_Error(
				'wpepp_captcha',
				__( 'CAPTCHA verification failed.', 'wp-edit-password-protected' )
			);
		}

		// v3 score gate.
		if ( 'recaptcha_v3' === $provider ) {
			$score     = isset( $data['score'] ) ? (float) $data['score'] : 0;
			$threshold = max( 0.1, min( 0.9, (float) ( self::settings()['captcha_threshold'] ?? 0.5 ) ) );

			if ( $score < $threshold ) {
				return new \WP_Error(
					'wpepp_captcha',
					__( 'Your request was flagged as automated. Please try again.', 'wp-edit-password-protected' )
				);
			}
		}

		return true;
	}
}
