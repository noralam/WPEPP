<?php
/**
 * Unit tests for WPEPP_Blocks::parse_entry() — IP/CIDR → binary range math.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

/**
 * Class BlocksTest
 */
class BlocksTest extends TestCase {

	/* ─── Single IPs ─── */

	public function test_single_ipv4() {
		$r = WPEPP_Blocks::parse_entry( '192.0.2.55' );

		$this->assertNotNull( $r );
		$this->assertSame( $r['start'], $r['end'] );
		$this->assertSame( 4, strlen( $r['start'] ) );
	}

	public function test_single_ipv6() {
		$r = WPEPP_Blocks::parse_entry( '2001:db8::1' );

		$this->assertNotNull( $r );
		$this->assertSame( 16, strlen( $r['start'] ) );
		$this->assertSame( $r['start'], $r['end'] );
	}

	/* ─── CIDR ranges ─── */

	public function test_cidr24_bounds() {
		$r = WPEPP_Blocks::parse_entry( '192.0.2.0/24' );

		$this->assertNotNull( $r );
		$this->assertSame( inet_pton( '192.0.2.0' ), $r['start'] );
		$this->assertSame( inet_pton( '192.0.2.255' ), $r['end'] );
		$this->assertSame( 24, $r['prefix'] );
	}

	public function test_cidr_non_octet_boundary() {
		// /26: .64 block covers .64–.127.
		$r = WPEPP_Blocks::parse_entry( '192.0.2.64/26' );

		$this->assertSame( inet_pton( '192.0.2.64' ), $r['start'] );
		$this->assertSame( inet_pton( '192.0.2.127' ), $r['end'] );
	}

	public function test_cidr32_collapses_to_single() {
		$r = WPEPP_Blocks::parse_entry( '10.9.8.7/32' );

		$this->assertSame( inet_pton( '10.9.8.7' ), $r['start'] );
		$this->assertSame( $r['start'], $r['end'] );
	}

	public function test_ipv6_cidr48() {
		$r = WPEPP_Blocks::parse_entry( '2001:db8:1234::/48' );

		$this->assertSame( inet_pton( '2001:db8:1234::' ), $r['start'] );
		$this->assertSame( inet_pton( '2001:db8:1234:ffff:ffff:ffff:ffff:ffff' ), $r['end'] );
	}

	public function test_inline_comments_stripped() {
		$r = WPEPP_Blocks::parse_entry( '203.0.113.9 # spam bot' );

		$this->assertNotNull( $r );
		$this->assertSame( inet_pton( '203.0.113.9' ), $r['start'] );
	}

	/* ─── Invalid input ─── */

	public function test_invalid_entries_return_null() {
		$this->assertNull( WPEPP_Blocks::parse_entry( '' ) );
		$this->assertNull( WPEPP_Blocks::parse_entry( 'not-an-ip' ) );
		$this->assertNull( WPEPP_Blocks::parse_entry( '999.1.1.1' ) );
		$this->assertNull( WPEPP_Blocks::parse_entry( '192.0.2.0/33' ) );
		$this->assertNull( WPEPP_Blocks::parse_entry( '2001:db8::/129' ) );
	}

	public function test_range_contains_expected_ips() {
		$r     = WPEPP_Blocks::parse_entry( '203.0.113.0/25' );
		$lower = inet_pton( '203.0.113.5' );
		$upper = inet_pton( '203.0.113.250' ); // Outside /25 (belongs to upper half).

		$this->assertTrue( strcmp( $lower, $r['start'] ) >= 0 && strcmp( $lower, $r['end'] ) <= 0 );
		$this->assertFalse( strcmp( $upper, $r['end'] ) <= 0 && strcmp( $upper, $r['start'] ) >= 0 );
	}
}
