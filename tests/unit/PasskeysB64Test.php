<?php
/**
 * Unit tests for passkey b64url helpers (pure logic).
 *
 * @package wpepp-pro
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load module class without triggering the boot() side-effect at file end:
// strip the trailing boot() call, stage a clean copy in the OS temp dir,
// and include it from there instead of eval().
$passkeys_code = file_get_contents( dirname( __DIR__, 3 ) . '/wpepp-pro/inc/modules/passkeys.php' );
$passkeys_code = preg_replace( '/WPEPP_Pro_Passkeys::boot\(\);.*$/s', '', $passkeys_code );
$passkeys_tmp  = tempnam( sys_get_temp_dir(), 'wpepp_passkeys_' );

if ( false === $passkeys_tmp || false === $passkeys_code || false === file_put_contents( $passkeys_tmp, $passkeys_code ) ) {
	throw new RuntimeException( 'Unable to stage passkeys module for testing.' );
}

require_once $passkeys_tmp;
wp_delete_file( $passkeys_tmp );

/**
 * Class PasskeysB64Test
 */
class PasskeysB64Test extends TestCase {

	public function test_roundtrip_binary() {
		$bin = random_bytes( 48 );

		$encoded = WPEPP_Pro_Passkeys::b64url_encode( $bin );
		$decoded = WPEPP_Pro_Passkeys::b64url_decode( $encoded );

		$this->assertSame( $bin, $decoded );
	}

	public function test_url_safe_alphabet_only() {
		// Bytes that force + and / in standard base64.
		$bin = hex2bin( 'fbbfbfbffefdfcfb' );

		$encoded = WPEPP_Pro_Passkeys::b64url_encode( $bin );

		$this->assertStringNotContainsString( '+', $encoded );
		$this->assertStringNotContainsString( '/', $encoded );
		$this->assertStringNotContainsString( '=', $encoded );
	}

	public function test_decode_accepts_unpadded_input() {
		$bin = random_bytes( 31 ); // Length not multiple of 3 -> padding needed.
		$enc = WPEPP_Pro_Passkeys::b64url_encode( $bin );

		$this->assertSame( $bin, WPEPP_Pro_Passkeys::b64url_decode( $enc ) );
	}
}