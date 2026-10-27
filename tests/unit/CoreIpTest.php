<?php
/**
 * Unit tests for WPEPP_Core_Ip — CIDR math, normalization, header parsing.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CoreIpTest
 */
class CoreIpTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']    = [];
		$GLOBALS['wp_test_filters']    = [];
		$GLOBALS['wp_test_object_cache'] = [];
		$_SERVER['REMOTE_ADDR']        = '203.0.113.50';
		unset(
			$_SERVER['HTTP_CF_CONNECTING_IP'],
			$_SERVER['HTTP_X_FORWARDED_FOR'],
			$_SERVER['HTTP_X_REAL_IP'],
			$_SERVER['HTTP_CF_IPCOUNTRY']
		);
	}

	/* ─── CIDR: IPv4 ─── */

	public function test_ipv4_cidr_match() {
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '192.168.1.55', '192.168.1.0/24' ) );
	}

	public function test_ipv4_cidr_mismatch() {
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '192.168.2.55', '192.168.1.0/24' ) );
	}

	public function test_ipv4_single_host_slash32() {
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '10.0.0.7', '10.0.0.7/32' ) );
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '10.0.0.8', '10.0.0.7/32' ) );
	}

	public function test_ipv4_wildcard_slash0() {
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '8.8.8.8', '0.0.0.0/0' ) );
	}

	public function test_ipv4_non_octet_boundary() {
		// /20 mask boundary inside the third octet.
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '173.245.48.1', '173.245.48.0/20' ) );
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '173.245.63.254', '173.245.48.0/20' ) );
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '173.245.64.1', '173.245.48.0/20' ) );
	}

	public function test_invalid_cidr_returns_false() {
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '1.2.3.4', 'not-a-cidr' ) );
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '1.2.3.4', '1.2.3.0/33' ) );
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '', '1.2.3.0/24' ) );
	}

	/* ─── CIDR: IPv6 ─── */

	public function test_ipv6_cidr_match() {
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '2001:db8:1234:5678::1', '2001:db8::/32' ) );
	}

	public function test_ipv6_cidr_mismatch() {
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '2001:db9::1', '2001:db8::/32' ) );
	}

	public function test_family_mismatch_is_false() {
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '192.168.1.1', '2001:db8::/32' ) );
		$this->assertFalse( WPEPP_Core_Ip::ip_in_cidr( '2001:db8::1', '192.168.1.0/24' ) );
	}

	/* ─── Normalization ─── */

	public function test_normalize_plain_addresses() {
		$this->assertSame( '1.2.3.4', WPEPP_Core_Ip::normalize( ' 1.2.3.4 ' ) );
		$this->assertSame( '::1', WPEPP_Core_Ip::normalize( '0:0:0:0:0:0:0:1' ) );
	}

	public function test_normalize_collapses_ipv6_mapped_ipv4() {
		$this->assertSame( '203.0.113.5', WPEPP_Core_Ip::normalize( '::ffff:203.0.113.5' ) );
	}

	public function test_normalize_invalid_returns_empty() {
		$this->assertSame( '', WPEPP_Core_Ip::normalize( '999.999.999.999' ) );
		$this->assertSame( '', WPEPP_Core_Ip::normalize( 'javascript:' ) );
	}

	public function test_private_range_detection() {
		$this->assertTrue( WPEPP_Core_Ip::is_private( '10.1.2.3' ) );
		$this->assertTrue( WPEPP_Core_Ip::is_private( '127.0.0.1' ) );
		$this->assertFalse( WPEPP_Core_Ip::is_private( '93.184.216.34' ) );
	}

	/* ─── List membership ─── */

	public function test_list_exact_and_cidr_and_comments() {
		$list = "192.168.0.1\n10.0.0.0/8, # internal net\n172.16.5.4 # blocked bot";
		$this->assertTrue( WPEPP_Core_Ip::is_ip_in_list( '192.168.0.1', $list ) );
		$this->assertTrue( WPEPP_Core_Ip::is_ip_in_list( '10.99.99.99', $list ) );
		$this->assertTrue( WPEPP_Core_Ip::is_ip_in_list( '172.16.5.4', $list ) );
		$this->assertFalse( WPEPP_Core_Ip::is_ip_in_list( '8.8.8.8', $list ) );
	}

	public function test_list_empty_or_non_string() {
		$this->assertFalse( WPEPP_Core_Ip::is_ip_in_list( '1.2.3.4', '' ) );
		$this->assertFalse( WPEPP_Core_Ip::is_ip_in_list( '1.2.3.4', null ) );
	}

	/* ─── Client IP resolution ─── */

	public function test_direct_connection_ignores_forwarded_headers() {
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '6.6.6.6'; // Spoofed attempt.
		$_SERVER['HTTP_X_FORWARDED_FOR']  = '7.7.7.7';

		$this->assertSame( '203.0.113.50', WPEPP_Core_Ip::get_client_ip() );
	}

	public function test_trusted_proxy_honours_cf_header() {
		add_filter( 'wpepp_trusted_proxies', static function () {
			return [ '203.0.113.0/24' ]; // REMOTE_ADDR is in this range.
		} );

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.23';
		$this->assertSame( '198.51.100.23', WPEPP_Core_Ip::get_client_ip() );
	}

	public function test_untrusted_proxy_header_ignored_even_with_xff() {
		add_filter( 'wpepp_trusted_proxies', static function () {
			return [ '198.51.100.0/24' ]; // Does NOT contain REMOTE_ADDR.
		} );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8';
		$this->assertSame( '203.0.113.50', WPEPP_Core_Ip::get_client_ip() );
	}

	public function test_chained_xff_skips_trusted_hops_right_to_left() {
		add_filter( 'wpepp_trusted_proxies', static function () {
			return [ '203.0.113.0/24', '10.0.0.1' ];
		} );

		// Chain order (left→right): client, inner proxy, edge proxy.
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.77, 10.0.0.1, 203.0.113.50';
		$this->assertSame( '198.51.100.77', WPEPP_Core_Ip::get_client_ip() );
	}

	public function test_settings_based_trusted_proxies() {
		$GLOBALS['wp_test_options']['wpepp_security_settings'] = wp_json_encode_stub(
			[ 'trusted_proxies' => "203.0.113.0/24\n# comment\n" ]
		);

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.23';
		$this->assertSame( '198.51.100.23', WPEPP_Core_Ip::get_client_ip() );
	}
}

/**
 * Local json_encode shim mirroring wp_json_encode for the bootstrap stub.
 *
 * @param mixed $data Data to encode.
 * @return string
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function wp_json_encode_stub( $data ) {
	return json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
}
