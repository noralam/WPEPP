<?php
/**
 * Security response headers manager.
 *
 * Configurable via security settings keys prefixed `hdr_`. Applied on
 * send_headers for front-end requests only (never admin/REST/AJAX).
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Hardening_Headers
 */
class WPEPP_Hardening_Headers {

	/**
	 * Boot.
	 */
	public static function boot() {
		add_action( 'send_headers', [ __CLASS__, 'send' ] );
		add_filter( 'wpepp/security/score_items', [ __CLASS__, 'score_item' ] );
	}

	/**
	 * Header configuration with defaults (all minimal-risk values ON).
	 *
	 * @return array
	 */
	public static function config() {
		$s = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		$s = is_array( $s ) ? $s : [];

		return [
			// frame-ancestors style clickjacking protection.
			'x_frame'            => $s['hdr_x_frame'] ?? 'SAMEORIGIN',
			// MIME sniffing.
			'nosniff'            => $s['hdr_nosniff'] ?? true,
			'referrer'           => $s['hdr_referrer'] ?? 'strict-origin-when-cross-origin',
			// Feature restrictions; empty string = header not sent.
			'permissions_policy' => (string) ( $s['hdr_permissions_policy'] ?? '' ),
			'hsts'               => (bool) ( $s['hdr_hsts'] ?? false ),
			'hsts_subdomains'    => (bool) ( $s['hdr_hsts_subdomains'] ?? true ),
			'hsts_preload'       => (bool) ( $s['hdr_hsts_preload'] ?? false ),
			// CSP: report-only raw policy string ('' = off).
			'csp_report_only'    => trim( (string) ( $s['hdr_csp_report_only'] ?? '' ) ),
		];
	}

	/**
	 * Build the header key/value list from a config array.
	 *
	 * Pure — unit-testable.
	 *
	 * @param array $c Config.
	 * @return array<string,string>
	 */
	public static function build( array $c ) {
		$headers = [];

		if ( ! empty( $c['nosniff'] ) ) {
			$headers['X-Content-Type-Options'] = 'nosniff';
		}

		$frame = strtoupper( (string) ( $c['x_frame'] ?? '' ) );

		if ( in_array( $frame, [ 'DENY', 'SAMEORIGIN' ], true ) ) {
			$headers['X-Frame-Options'] = $frame;
		}

		$ref = strtolower( (string) ( $c['referrer'] ?? '' ) );

		if ( in_array(
			$ref,
			[
				'no-referrer', 'no-referrer-when-downgrade', 'same-origin',
				'origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url',
			],
			true
		) ) {
			$headers['Referrer-Policy'] = $ref;
		}

		if ( ! empty( $c['permissions_policy'] ) && strlen( (string) $c['permissions_policy'] ) < 500 ) {
			// Basic sanity: only known-safe chars.
			if ( preg_match( '/^[a-zA-Z0-9=\(\)\.\,\*\-\s]+$/', (string) $c['permissions_policy'] ) ) {
				$headers['Permissions-Policy'] = (string) $c['permissions_policy'];
			}
		}

		if ( ! empty( $c['hsts'] ) ) {
			$max_age = 31536000;
			$value   = "max-age={$max_age}";

			if ( ! empty( $c['hsts_subdomains'] ) ) {
				$value .= '; includeSubDomains';
			}
			if ( ! empty( $c['hsts_preload'] ) ) {
				$value .= '; preload';
			}

			$headers['Strict-Transport-Security'] = $value;
		}

		if ( ! empty( $c['csp_report_only'] ) && strlen( (string) $c['csp_report_only'] ) < 2000 ) {
			if ( false === stripos( $c['csp_report_only'], '<' ) ) {
				$headers['Content-Security-Policy-Report-Only'] = (string) $c['csp_report_only'];
			}
		}

		return $headers;
	}

	/**
	 * Apply headers on front-end responses.
	 */
	public static function send() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		$headers = self::build( self::config() );

		// Browsers ignore HSTS over plain HTTP — sending it only confuses
		// site owners who toggle it and see no effect.
		if ( isset( $headers['Strict-Transport-Security'] ) && ! is_ssl() ) {
			unset( $headers['Strict-Transport-Security'] );
		}

		foreach ( $headers as $name => $value ) {
			header( "{$name}: {$value}" );
		}
	}

	/**
	 * Dashboard checklist item.
	 *
	 * @param array[] $items Items.
	 * @return array[]
	 */
	public static function score_item( array $items ) {
		$headers_on = count( self::build( self::config() ) );

		$items[] = [
			'id'       => 'security_headers',
			'label'    => __( 'Security response headers active', 'wp-edit-password-protected' ),
			'weight'   => 8,
			'category' => 'harden',
			'done'     => $headers_on >= 3,
			'route'    => '/hardening',
		];

		return $items;
	}
}
