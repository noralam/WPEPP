<?php
/**
 * Geo helper — resolve a visitor IP to an ISO-3166 alpha-2 country code.
 *
 * Resolution order:
 *   1. Cloudflare `CF-IPCountry` request header (no external call).
 *   2. ipapi.co lookup, cached in a transient.
 *
 * Designed to FAIL OPEN: any failure (no header, private IP, API down,
 * timeout, malformed response) returns an empty string so callers allow
 * the visitor rather than locking the whole site on a 3rd-party outage.
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Geo
 */
class WPEPP_Geo {

	/**
	 * Transient prefix for cached lookups.
	 */
	const CACHE_PREFIX = 'wpepp_geo_';

	/**
	 * Cache TTL for a successful lookup (7 days).
	 */
	const CACHE_TTL = 604800;

	/**
	 * Cache TTL for a failed lookup (1 hour) so outages self-heal.
	 */
	const CACHE_TTL_NEG = 3600;

	/**
	 * Resolve an IP to a 2-letter uppercase country code.
	 *
	 * @param string $ip Visitor IP address.
	 * @return string ISO-3166 alpha-2 code, or '' when unknown.
	 */
	public static function country_for_ip( $ip ) {
		$code = '';

		// 1. Cloudflare header — instant, no external request.
		// ONLY trusted when the direct peer is a registered proxy, otherwise
		// any visitor could spoof the header to bypass geo rules (B1).
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) && self::is_behind_trusted_proxy() ) {
			$cf = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			// Cloudflare uses XX (unknown) and T1 (Tor) for non-countries — ignore those.
			if ( preg_match( '/^[A-Z]{2}$/', $cf ) && ! in_array( $cf, [ 'XX', 'T1' ], true ) ) {
				$code = $cf;
			}
		}

		// 2. API fallback when no usable Cloudflare value.
		if ( '' === $code ) {
			$code = self::lookup_via_api( $ip );
		}

		/**
		 * Filter the resolved country code, e.g. for other CDNs/proxies.
		 *
		 * @param string $code Country code ('' if unknown).
		 * @param string $ip   Visitor IP.
		 */
		return (string) apply_filters( 'wpepp_geo_country', $code, $ip );
	}

	/**
	 * Whether the direct peer of this request is a registered trusted proxy.
	 *
	 * @return bool
	 */
	private static function is_behind_trusted_proxy() {
		if ( ! class_exists( 'WPEPP_Core_Ip' ) ) {
			return false;
		}

		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP )
			: false;

		return $remote ? WPEPP_Core_Ip::is_trusted_proxy( $remote ) : false;
	}

	/**
	 * Look up a country code from ipapi.co, with caching.
	 *
	 * @param string $ip Visitor IP address.
	 * @return string Country code, or '' on any failure.
	 */
	private static function lookup_via_api( $ip ) {
		// Skip private/reserved IPs — they cannot be geolocated.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return '';
		}

		$cache_key = self::CACHE_PREFIX . md5( $ip );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			// '0' is our stored marker for a cached negative result.
			return '0' === $cached ? '' : $cached;
		}

		$response = wp_remote_get(
			'https://ipapi.co/' . rawurlencode( $ip ) . '/country/',
			[
				'timeout'    => 3,
				'user-agent' => 'WPEPP/' . ( defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '2.1.0' ),
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, '0', self::CACHE_TTL_NEG );
			return '';
		}

		$body = strtoupper( trim( wp_remote_retrieve_body( $response ) ) );

		if ( ! preg_match( '/^[A-Z]{2}$/', $body ) ) {
			set_transient( $cache_key, '0', self::CACHE_TTL_NEG );
			return '';
		}

		set_transient( $cache_key, $body, self::CACHE_TTL );
		return $body;
	}
}
