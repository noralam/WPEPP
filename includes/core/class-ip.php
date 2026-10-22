<?php
/**
 * Core IP service — single source of truth for client IP resolution,
 * trusted-proxy detection, and IP/CIDR matching (IPv4 + IPv6).
 *
 * All modules MUST resolve visitor IPs through this class. Raw access to
 * $_SERVER['REMOTE_ADDR'] outside this file is forbidden (CI-checked).
 *
 * Spoof-proof default: forwarded headers (CF-Connecting-IP, X-Forwarded-For,
 * X-Real-IP, CF-IPCountry) are honoured ONLY when the direct peer
 * (REMOTE_ADDR) is a registered trusted proxy. Sites behind Cloudflare/Nginx
 * register their proxy IPs/CIDRs via:
 *
 *   1. Security setting:  wpepp_security_settings['trusted_proxies'] (newline/comma list), or
 *   2. Code filter:       add_filter( 'wpepp_trusted_proxies', fn() => [ '173.245.48.0/20', ... ] );
 *
 * @package wpepp
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Core_Ip
 */
class WPEPP_Core_Ip {

	/**
	 * Header chain consulted (in priority order) when behind a trusted proxy.
	 *
	 * @var array
	 */
	const FORWARDED_HEADERS = [
		'HTTP_CF_CONNECTING_IP', // Cloudflare.
		'HTTP_CF_CONNECTING_IPV6',
		'HTTP_X_FORWARDED_FOR',  // Generic reverse proxies.
		'HTTP_X_REAL_IP',        // Nginx.
	];

	/**
	 * Resolve the real client IP for the current request.
	 *
	 * @return string Validated IP address, or '0.0.0.0' when unknown.
	 */
	public static function get_client_ip() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP )
			: false;

		$remote = $remote ? $remote : '0.0.0.0';

		if ( ! self::is_trusted_proxy( $remote ) ) {
			return $remote;
		}

		foreach ( self::FORWARDED_HEADERS as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = trim( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );

			// XFF may carry a comma-separated chain; walk right-to-left skipping
			// trusted hops so chained proxies are supported correctly.
			if ( 'HTTP_X_FORWARDED_FOR' === $header && false !== strpos( $value, ',' ) ) {
				$candidates = array_map( 'trim', explode( ',', $value ) );
				$candidates = array_reverse( $candidates );

				foreach ( $candidates as $candidate ) {
					if ( '' === $candidate ) {
						continue;
					}
					// First untrusted hop from the right is the client.
					if ( filter_var( $candidate, FILTER_VALIDATE_IP ) && ! self::is_trusted_proxy( $candidate ) ) {
						return $candidate;
					}
				}
				continue;
			}

			if ( filter_var( $value, FILTER_VALIDATE_IP ) && ! self::is_trusted_proxy( $value ) ) {
				return $value;
			}
		}

		return $remote;
	}

	/**
	 * Whether the given direct-peer IP is a configured trusted reverse proxy.
	 *
	 * Sources merged: security setting 'trusted_proxies' + wpepp_trusted_proxies filter.
	 *
	 * @param string $ip Direct peer IP (REMOTE_ADDR).
	 * @return bool
	 */
	public static function is_trusted_proxy( $ip ) {
		$proxies = [];

		// From saved settings (get_option is itself object-cache backed).
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( (string) $raw, true );
		$setting  = ( is_array( $settings ) && ! empty( $settings['trusted_proxies'] ) )
			? (string) $settings['trusted_proxies']
			: '';

		if ( '' !== $setting ) {
			$proxies = array_merge(
				$proxies,
				array_filter( array_map( 'trim', preg_split( '/[\n\r,\s]+/', strtolower( $setting ) ) ) )
			);
		}

		// From code filter.
		$filtered = apply_filters( 'wpepp_trusted_proxies', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- documented public API.
		if ( is_array( $filtered ) ) {
			$proxies = array_merge(
				$proxies,
				array_filter( array_map( 'trim', array_map( 'strtolower', array_map( 'strval', $filtered ) ) ) )
			);
		}

		$proxies = array_values( array_unique( array_filter( $proxies ) ) );

		if ( empty( $proxies ) ) {
			return false;
		}

		$ip = strtolower( (string) $ip );

		foreach ( $proxies as $proxy ) {
			if ( '' === $proxy ) {
				continue;
			}

			if ( false === strpos( $proxy, '/' ) ) {
				if ( hash_equals( $proxy, strtolower( $ip ) ) ) {
					return true;
				}
				continue;
			}

			if ( self::ip_in_cidr( $ip, $proxy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether an IP falls within a CIDR range (IPv4 + IPv6).
	 *
	 * @param string $ip   IP address.
	 * @param string $cidr CIDR notation, e.g. 192.168.1.0/24 or 2001:db8::/32.
	 * @return bool
	 */
	public static function ip_in_cidr( $ip, $cidr ) {
		$parts     = explode( '/', trim( (string) $cidr ), 2 );
		$range_ip  = trim( $parts[0] );
		$max_bits  = ( false !== strpos( $range_ip, ':' ) ) ? 128 : 32;
		$prefix    = isset( $parts[1] ) ? (int) $parts[1] : $max_bits;

		if ( $prefix < 0 || $prefix > $max_bits ) {
			return false;
		}

		$ip_bin    = @inet_pton( (string) $ip );
		$range_bin = @inet_pton( $range_ip );

		if ( false === $ip_bin || false === $range_bin || null === $ip_bin || null === $range_bin ) {
			return false;
		}

		if ( strlen( $ip_bin ) !== strlen( $range_bin ) ) {
			// Family mismatch (IPv4 vs IPv6). Allow IPv6-mapped IPv4 comparison.
			$mapped = self::ipv6_mapped_ipv4( $ip_bin );
			if ( null === $mapped ) {
				return false;
			}
			$ip6_bin = $ip_bin;
			if ( 4 === strlen( $mapped ) && 16 === strlen( $range_bin ) ) {
				$ip6_bin = self::ipv4_to_ipv6_mapped( $mapped );
			} elseif ( 16 === strlen( $ip_bin ) && 4 === strlen( $range_bin ) ) {
				return false;
			} else {
				return false;
			}
			$ip_bin    = $ip6_bin;
			$max_bits  = 128;
			if ( ! isset( $parts[1] ) ) {
				$prefix = 128;
			}
		}

		// Compare bit-by-bit over the prefix length.
		$full_bytes = intdiv( $prefix, 8 );
		$rem_bits   = $prefix % 8;

		if ( $full_bytes > 0 && substr( $ip_bin, 0, $full_bytes ) !== substr( $range_bin, 0, $full_bytes ) ) {
			return false;
		}

		if ( $rem_bits > 0 ) {
			$mask = 0xFF << ( 8 - $rem_bits ) & 0xFF;
			if ( ( ord( $ip_bin[ $full_bytes ] ) & $mask ) !== ( ord( $range_bin[ $full_bytes ] ) & $mask ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Extract embedded IPv4 from an IPv6-mapped address (::ffff:a.b.c.d).
	 *
	 * @param string $bin Binary IP.
	 * @return string|null Dotted IPv4 or null.
	 */
	public static function ipv6_mapped_ipv4( $bin ) {
		if ( 16 !== strlen( (string) $bin ) ) {
			return null;
		}
		if ( substr( $bin, 0, 12 ) !== str_repeat( "\x00", 10 ) . "\xff\xff" ) {
			return null;
		}
		return inet_ntop( substr( $bin, 12, 4 ) );
	}

	/**
	 * Wrap an IPv4 address in an IPv6-mapped binary representation.
	 *
	 * @param string $ipv4 Dotted IPv4.
	 * @return string 16-byte binary.
	 */
	public static function ipv4_to_ipv6_mapped( $ipv4 ) {
		$bin = inet_pton( $ipv4 );
		if ( false === $bin || 4 !== strlen( $bin ) ) {
			return str_repeat( "\x00", 16 );
		}
		return str_repeat( "\x00", 10 ) . "\xff\xff" . $bin;
	}

	/**
	 * Normalize an IP string; returns '' for invalid input.
	 *
	 * Collapses IPv6-mapped IPv4 to plain dotted quad so equality checks and
	 * DB lookups behave consistently.
	 *
	 * @param string $ip IP address.
	 * @return string
	 */
	public static function normalize( $ip ) {
		$bin = @inet_pton( trim( (string) $ip ) );
		if ( false === $bin ) {
			return '';
		}

		$mapped = self::ipv6_mapped_ipv4( $bin );
		if ( null !== $mapped ) {
			return $mapped;
		}

		return (string) inet_ntop( $bin );
	}

	/**
	 * Whether an IP is a private/reserved range (cannot be geolocated).
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	public static function is_private( $ip ) {
		return false === filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * Check membership against a newline/comma-separated IP/CIDR list.
	 *
	 * Supports inline comments ("1.2.3.4 # note").
	 *
	 * @param string $ip   IP address.
	 * @param string $list List string.
	 * @return bool
	 */
	public static function is_ip_in_list( $ip, $list ) {
		if ( empty( $list ) || ! is_string( $list ) ) {
			return false;
		}

		$entries = array_filter( array_map( 'trim', preg_split( '/[\n\r,\s]+/', $list ) ) );

		foreach ( $entries as $entry ) {
			$entry = trim( preg_replace( '/#.*$/', '', $entry ) );
			if ( '' === $entry ) {
				continue;
			}

			if ( strtolower( $ip ) === strtolower( $entry ) ) {
				return true;
			}

			if ( false !== strpos( $entry, '/' ) && self::ip_in_cidr( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Official Cloudflare IPv4 & IPv6 CIDRs.
	 *
	 * @return string[]
	 */
	public static function get_cloudflare_cidrs() {
		return [
			// IPv4
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22',
			// IPv6
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2a06:98c0::/29',
			'2c0f:f248::/32',
		];
	}

	/**
	 * Inspect proxy status and headers for the current request / server environment.
	 *
	 * @return array
	 */
	public static function inspect_proxy_status() {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		$client_ip   = self::get_client_ip();
		$is_trusted  = self::is_trusted_proxy( $remote_addr );

		$headers_present = [];
		foreach ( self::FORWARDED_HEADERS as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$headers_present[ $h ] = sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) );
			}
		}

		$cf_connecting = ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) || ! empty( $_SERVER['HTTP_CF_RAY'] );

		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( (string) $raw, true );
		$trusted  = ( is_array( $settings ) && ! empty( $settings['trusted_proxies'] ) ) ? (string) $settings['trusted_proxies'] : '';
		$proxies  = array_filter( array_map( 'trim', preg_split( '/[\n\r,\s]+/', $trusted ) ) );

		return [
			'remote_addr'            => $remote_addr,
			'client_ip'              => $client_ip,
			'is_trusted_proxy'       => $is_trusted,
			'is_cloudflare_detected' => $cf_connecting,
			'headers_present'        => $headers_present,
			'configured_count'       => count( $proxies ),
			'cloudflare_cidrs'       => self::get_cloudflare_cidrs(),
		];
	}
}
