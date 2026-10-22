<?php
/**
 * WPEPP Firewall — pure rule engine.
 *
 * NO WordPress dependencies. Loaded both from the normal plugin runtime AND
 * from the early-boot extended-mode bootstrap (auto_prepend_file), where WP
 * does not exist yet. Keep this file free of any WordPress function calls.
 *
 * Rule shape:
 * {
 *   "id": "probe_wp_config",
 *   "name": "wp-config.php probing",
 *   "category": "recon",
 *   "severity": 3,
 *   "action": "block",            // block | log
 *   "matchers": [                 // ALL matchers must pass (AND)
 *     { "type": "path_regex", "value": "#wp-config\\.#i" }
 *   ]
 * }
 *
 * Matcher types:
 *   method            value: "GET|POST|..."  (pipe-separated list)
 *   path_regex        value: regex against URL path
 *   ua_regex          value: regex against User-Agent
 *   referer_regex     value: regex against Referer
 *   query_regex       value: regex against raw query string
 *   param_regex       name + value: regex against $_GET/$query param
 *   body_regex        name + value: regex against POST field
 *   ip_cidr           value: pipe-separated IPs/CIDRs
 *
 * @package wpepp
 * @since   3.0.0
 */

// Direct-access guard. The early-boot extended-mode bootstrap defines ABSPATH
// itself before requiring this file (WordPress is not loaded at that stage).
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPEPP_FW_ENGINE' ) ) {
	define( 'WPEPP_FW_ENGINE', '1' );
}

/**
 * Class WPEPP_FW_Engine
 */
final class WPEPP_FW_Engine {

	/**
	 * Inspect a request against a rule set. First match wins.
	 *
	 * @param array $rules   Flat rules array.
	 * @param array $request Normalized request:
	 *                        ['method','path','query_string','query'=>[], 'body'=>[], 'ua','referer','ip'].
	 * @return array|null Matched rule (with '_match' detail) or null.
	 */
	public static function inspect( array $rules, array $request ) {
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return null;
		}

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule )
				|| empty( $rule['id'] )
				|| empty( $rule['matchers'] )
				|| ! is_array( $rule['matchers'] ) ) {
				continue;
			}

			$detail = self::match_rule( $rule, $request );

			if ( null !== $detail ) {
				$rule['_match'] = $detail;
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Evaluate every matcher of one rule (AND semantics).
	 *
	 * @param array $rule    Rule.
	 * @param array $request Request.
	 * @return string|null Human-readable match detail, or null when no match.
	 */
	private static function match_rule( array $rule, array $request ) {
		$details = [];

		foreach ( $rule['matchers'] as $m ) {
			if ( ! is_array( $m ) || empty( $m['type'] ) ) {
				return null;
			}

			$hit = self::match_one( $m, $request );

			if ( true !== $hit ) {
				return null; // AND — one miss kills the rule.
			}

			$details[] = $m['type'];
		}

		return implode( '+', $details );
	}

	/**
	 * Evaluate a single matcher.
	 *
	 * @param array $m       Matcher.
	 * @param array $request Request.
	 * @return bool
	 */
	private static function match_one( array $m, array $request ) {
		$value = isset( $m['value'] ) ? (string) $m['value'] : '';

		switch ( $m['type'] ) {

			case 'method':
				$methods = array_map( 'strtoupper', array_filter( array_map( 'trim', explode( '|', strtoupper( $value ) ) ) ) );
				return ! empty( $methods ) && in_array( strtoupper( (string) ( $request['method'] ?? '' ) ), $methods, true );

			case 'path_regex':
				$path = (string) ( $request['path'] ?? '' );
				return self::regex_hit( $value, $path ) || self::regex_hit( $value, rawurldecode( $path ) );

			case 'ua_regex':
				return '' === $value ? false : self::regex_hit( $value, (string) ( $request['ua'] ?? '' ) );

			case 'referer_regex':
				return '' === $value ? false : self::regex_hit( $value, (string) ( $request['referer'] ?? '' ) );

			case 'query_regex':
				$qs = (string) ( $request['query_string'] ?? '' );
				return self::regex_hit( $value, $qs ) || self::regex_hit( $value, rawurldecode( $qs ) );

			case 'param_regex':
				$name = isset( $m['name'] ) ? (string) $m['name'] : '';
				$hay  = $request['query'][ $name ] ?? '';
				if ( ! is_scalar( $hay ) || '' === (string) $hay ) {
					return false;
				}
				$str = (string) $hay;
				return self::regex_hit( $value, $str ) || self::regex_hit( $value, rawurldecode( $str ) );

			case 'body_regex':
				$name = isset( $m['name'] ) ? (string) $m['name'] : '';
				$hay  = $request['body'][ $name ] ?? '';
				if ( ! is_scalar( $hay ) || '' === (string) $hay ) {
					return false;
				}
				$str = (string) $hay;
				return self::regex_hit( $value, $str ) || self::regex_hit( $value, rawurldecode( $str ) );

			case 'ip_cidr':
				$ip = (string) ( $request['ip'] ?? '' );

				if ( '' === $ip || ! class_exists( 'WPEPP_Core_Ip' ) ) {
					return false;
				}

				foreach ( array_filter( array_map( 'trim', explode( '|', $value ) ) ) as $entry ) {
					if ( false === strpos( $entry, '/' ) ) {
						if ( strtolower( $entry ) === strtolower( $ip ) ) {
							return true;
						}
						continue;
					}
					if ( WPEPP_Core_Ip::ip_in_cidr( $ip, $entry ) ) {
						return true;
					}
				}
				return false;

			default:
				return false;
		}
	}

	/**
	 * Safe regex test — invalid patterns simply never match.
	 *
	 * Also rejects DEGENERATE patterns that match the empty string
	 * (e.g. a trailing pipe creating an empty alternation branch) — those
	 * would otherwise match every request.
	 *
	 * @param string $pattern Regex.
	 * @param string $subject Subject.
	 * @return bool
	 */
	public static function regex_hit( $pattern, $subject ) {
		if ( ! self::regex_safe( $pattern ) || '' === (string) $subject ) {
			return false;
		}

		$result = @preg_match( $pattern, $subject ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- intentional probe.

		return 1 === $result;
	}

	/**
	 * Whether a pattern compiles AND does not match the empty string.
	 *
	 * Used both at evaluation time and before persisting custom rules.
	 *
	 * @param string $pattern Regex.
	 * @return bool
	 */
	public static function regex_safe( $pattern ) {
		if ( ! is_string( $pattern ) || strlen( trim( $pattern ) ) < 2 ) {
			return false;
		}

		if ( @preg_match( $pattern, '' ) === false ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- intentional probe.
			return false; // Does not compile.
		}

		return @preg_match( $pattern, '' ) !== 1; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- must not match ''.
	}

	/**
	 * Build a normalized request array from PHP superglobals (or overrides).
	 *
	 * Works without WP; sanitization happens only for storage/logging paths,
	 * matching itself uses raw values.
	 *
	 * @param array|null $server Override $_SERVER (for tests).
	 * @param array|null $get    Override $_GET.
	 * @param array|null $post   Override $_POST.
	 * @return array
	 */
	public static function capture_request( ?array $server = null, ?array $get = null, ?array $post = null ) {
		$server = $server ?: $_SERVER; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing -- engine reads raw superglobals by design.
		$get    = $get ?: $_GET;      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Recommended -- engine reads raw superglobals by design.
		$post   = $post ?: $_POST;    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing -- engine reads raw superglobals by design.

		$uri     = isset( $server['REQUEST_URI'] ) ? (string) $server['REQUEST_URI'] : '';
		$parsed  = function_exists( 'wp_parse_url' )
			? wp_parse_url( $uri )
			: parse_url( $uri ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- early-boot fallback before WordPress loads.
		$path    = isset( $parsed['path'] ) ? (string) $parsed['path'] : '/';
		$query_s = isset( $parsed['query'] ) ? (string) $parsed['query'] : '';

		// Resolve client IP: use the core service for live requests
		// (trusted-proxy aware); direct parse when superglobals are overridden.
		$ip = '';
		if ( null === $server ) {
			$ip = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : '';
		} elseif ( ! empty( $server['REMOTE_ADDR'] ) ) {
			$ip = filter_var( (string) $server['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ?: '';
		}

		// Flatten arrays to strings for matching depth-safety.
		$flatten = static function ( array $src ) {
			$out = [];
			foreach ( $src as $k => $v ) {
				if ( is_array( $v ) ) {
					$v = implode( ',', array_map( 'strval', $v ) );
				}
				$out[ (string) $k ] = (string) $v;
			}
			return $out;
		};

		return [
			'method'       => strtoupper( (string) ( $server['REQUEST_METHOD'] ?? 'GET' ) ),
			'path'         => $path,
			'query_string' => $query_s,
			'query'        => $flatten( is_array( $get ) ? $get : [] ),
			'body'         => $flatten( is_array( $post ) ? $post : [] ),
			'ua'           => substr( (string) ( $server['HTTP_USER_AGENT'] ?? '' ), 0, 512 ),
			'referer'      => substr( (string) ( $server['HTTP_REFERER'] ?? '' ), 0, 512 ),
			'ip'           => $ip,
		];
	}
}
