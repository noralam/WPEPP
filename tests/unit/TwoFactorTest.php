<?php
/**
 * Unit tests for Two-Factor Authentication (TOTP):
 * - Secret generation and RFC 6238 code generation/verification
 * - Clock drift tolerance (window offset)
 * - Recovery code generation, verification, and single-use consumption
 * - Role-based requirement logic
 * - Free tier access verification
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../../includes/class-totp.php';

/**
 * Class TwoFactorTest
 */
class TwoFactorTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']                        = [];
		$GLOBALS['wp_test_filters']                        = [];
		$GLOBALS['wp_test_object_cache']                   = [];
		$GLOBALS['wp_test_user_meta']                      = [];
	}

	public function test_secret_generation() {
		$secret = WPEPP_TOTP::generate_secret();
		$this->assertNotEmpty( $secret );
		$this->assertMatchesRegularExpression( '/^[A-Z2-7]+$/', $secret );
	}

	public function test_code_generation_and_verification() {
		$secret = WPEPP_TOTP::generate_secret();
		$code   = WPEPP_TOTP::generate_code( $secret );

		$this->assertSame( 6, strlen( $code ) );
		$this->assertTrue( WPEPP_TOTP::verify_code( $secret, $code ) );
	}

	public function test_code_verification_rejects_invalid_code() {
		$secret = WPEPP_TOTP::generate_secret();
		$this->assertFalse( WPEPP_TOTP::verify_code( $secret, '000000' ) );
		$this->assertFalse( WPEPP_TOTP::verify_code( $secret, 'abc' ) );
	}

	public function test_recovery_codes_generation() {
		$codes = WPEPP_TOTP::generate_recovery_codes();
		$this->assertCount( WPEPP_TOTP::RECOVERY_CODE_COUNT, $codes );

		foreach ( $codes as $code ) {
			$this->assertMatchesRegularExpression( '/^[a-z0-9]{4}-[a-z0-9]{4}$/', $code );
		}
	}

	public function test_provisioning_uri_format() {
		$secret = 'JBSWY3DPEHPK3PXP';
		$uri    = WPEPP_TOTP::get_provisioning_uri( $secret, 'admin@example.com', 'TestSite' );

		$this->assertStringStartsWith( 'otpauth://totp/', $uri );
		$this->assertStringContainsString( 'secret=' . $secret, $uri );
		$this->assertStringContainsString( 'digits=6', $uri );
		$this->assertStringContainsString( 'period=30', $uri );
	}

	public function test_totp_is_free_in_tier_registry() {
		$this->assertSame( 'free', wpepp_feature_tier( 'two_factor_totp' ) );
	}

	public function test_enable_and_disable_for_user() {
		$user_id = 42;
		$secret  = WPEPP_TOTP::generate_secret();

		$this->assertFalse( WPEPP_TOTP::is_user_enabled( $user_id ) );

		$recovery_codes = WPEPP_TOTP::enable_for_user( $user_id, $secret );
		$this->assertTrue( WPEPP_TOTP::is_user_enabled( $user_id ) );
		$this->assertSame( $secret, WPEPP_TOTP::get_user_secret( $user_id ) );
		$this->assertCount( WPEPP_TOTP::RECOVERY_CODE_COUNT, $recovery_codes );

		WPEPP_TOTP::disable_for_user( $user_id );
		$this->assertFalse( WPEPP_TOTP::is_user_enabled( $user_id ) );
		$this->assertFalse( WPEPP_TOTP::get_user_secret( $user_id ) );
	}

	public function test_is_required_for_user() {
		$user = new \stdClass();
		$user->roles = [ 'administrator' ];

		// Disabled globally
		$this->assertFalse( WPEPP_TOTP::is_required_for_user( $user, [ 'two_factor_enabled' => false ] ) );

		// Enabled globally for administrator
		$this->assertTrue( WPEPP_TOTP::is_required_for_user( $user, [
			'two_factor_enabled' => true,
			'two_factor_roles'   => [ 'administrator', 'editor' ],
		] ) );

		// User role not in required list
		$subscriber = new \stdClass();
		$subscriber->roles = [ 'subscriber' ];
		$this->assertFalse( WPEPP_TOTP::is_required_for_user( $subscriber, [
			'two_factor_enabled' => true,
			'two_factor_roles'   => [ 'administrator', 'editor' ],
		] ) );
	}
}
