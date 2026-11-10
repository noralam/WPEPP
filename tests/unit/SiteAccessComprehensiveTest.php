<?php
/**
 * Comprehensive Unit Tests for Site Access Gate, Site Password, and Country Restriction.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../../includes/class-geo.php';

class SiteAccessComprehensiveTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']                        = [];
		$GLOBALS['wp_test_filters']                        = [];
		$GLOBALS['wp_test_object_cache']                   = [];
		$GLOBALS['wp_test_current_user_can_manage_options'] = false;
		$GLOBALS['wp_test_has_pro']                         = false;
		$GLOBALS['wp']                                     = new \stdClass();
		$GLOBALS['wp']->query_vars                         = [];
		$_COOKIE                                           = [];
		$_GET                                              = [];
		$_SERVER['REQUEST_URI']                            = '/';
		$_SERVER['REMOTE_ADDR']                            = '127.0.0.1';
	}

	// -------------------------------------------------------------
	// 1. Site Access Gate (Admin Only Mode)
	// -------------------------------------------------------------

	public function test_site_access_gate_disabled_allows_all() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'admin_only_enabled' => false,
		] ) );

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	public function test_site_access_gate_blocks_logged_out_visitor() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'admin_only_enabled' => true,
			'admin_only_target'  => 'logged_in',
		] ) );

		$access = new WPEPP_Site_Access();
		$block  = $access->get_site_access_block();

		$this->assertNotNull( $block );
		$this->assertSame( 'admin_only', $block['type'] );
		$this->assertSame( 401, $block['status'] );
		$this->assertSame( 'wpepp_admin_only_restricted', $block['code'] );
	}

	public function test_site_access_gate_allows_admin() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'admin_only_enabled' => true,
			'admin_only_target'  => 'admins_only',
		] ) );

		$GLOBALS['wp_test_current_user_can_manage_options'] = true;

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	// -------------------------------------------------------------
	// 2. Site Password
	// -------------------------------------------------------------

	public function test_site_password_empty_password_fails_safe() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => '',
		] ) );

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	public function test_site_password_blocks_visitor_without_cookie() {
		$hashed = '$P$B' . md5( 'Secret123!' . 'test_hash_salt' );
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled'          => true,
			'site_password'                  => $hashed,
			'site_password_bypass_logged_in' => false,
		] ) );

		$access = new WPEPP_Site_Access();
		$block  = $access->get_site_access_block();

		$this->assertNotNull( $block );
		$this->assertSame( 'site_password', $block['type'] );
		$this->assertSame( 403, $block['status'] );
		$this->assertSame( 'wpepp_site_password_required', $block['code'] );
	}

	public function test_site_password_allows_visitor_with_valid_cookie() {
		$password = 'Secret123!';
		$hashed   = '$P$B' . md5( $password . 'test_hash_salt' );
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled'          => true,
			'site_password'                  => $hashed,
			'site_password_bypass_logged_in' => false,
		] ) );

		$_COOKIE[ WPEPP_Site_Access::COOKIE_NAME ] = wp_hash( $hashed );

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	public function test_site_password_legacy_plaintext_and_upgrade() {
		$raw_password = 'MyCustomPassword2026';

		// Store plaintext legacy password
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => $raw_password,
		] ) );

		$this->assertTrue( WPEPP_Site_Access::verify_site_password( $raw_password ) );

		// Check that it was upgraded to a hash
		$updated_raw      = get_option( 'wpepp_site_access_settings', '{}' );
		$updated_settings = json_decode( $updated_raw, true );
		$this->assertNotEmpty( $updated_settings['site_password'] );
	}

	public function test_site_password_admin_always_bypasses() {
		$hashed = '$P$B' . md5( 'Secret123!' . 'test_hash_salt' );
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => $hashed,
		] ) );

		$GLOBALS['wp_test_current_user_can_manage_options'] = true;

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	// -------------------------------------------------------------
	// 3. Country Restriction
	// -------------------------------------------------------------

	public function test_country_restriction_blocks_country_in_blocklist() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'country_restriction_enabled' => true,
			'country_restriction_mode'    => 'block',
			'country_restriction_list'    => [ 'US', 'RU' ],
			'country_restriction_message' => 'Not available in your country.',
		] ) );

		$_SERVER['REMOTE_ADDR'] = '8.8.8.8';

		add_filter( 'wpepp_geo_country', function() {
			return 'US';
		} );

		$access = new WPEPP_Site_Access();
		$block  = $access->get_site_access_block();

		$this->assertNotNull( $block );
		$this->assertSame( 'country_restriction', $block['type'] );
		$this->assertSame( 403, $block['status'] );
		$this->assertSame( 'Not available in your country.', $block['message'] );
	}

	public function test_country_restriction_allows_country_not_in_blocklist() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'country_restriction_enabled' => true,
			'country_restriction_mode'    => 'block',
			'country_restriction_list'    => [ 'US', 'RU' ],
		] ) );

		$_SERVER['REMOTE_ADDR'] = '103.20.1.1';

		add_filter( 'wpepp_geo_country', function() {
			return 'BD';
		} );

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	public function test_country_restriction_allowlist_mode() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'country_restriction_enabled' => true,
			'country_restriction_mode'    => 'allow',
			'country_restriction_list'    => [ 'BD', 'GB' ],
		] ) );

		// Case A: Country in allowlist
		add_filter( 'wpepp_geo_country', function() {
			return 'BD';
		} );
		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );

		// Case B: Country NOT in allowlist
		$GLOBALS['wp_test_filters'] = []; // clear filter
		add_filter( 'wpepp_geo_country', function() {
			return 'US';
		} );
		$access = new WPEPP_Site_Access();
		$block  = $access->get_site_access_block();
		$this->assertNotNull( $block );
		$this->assertSame( 'country_restriction', $block['type'] );
		$this->assertSame( 403, $block['status'] );
	}

	public function test_country_restriction_unknown_ip_fails_open() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'country_restriction_enabled' => true,
			'country_restriction_mode'    => 'block',
			'country_restriction_list'    => [ 'US' ],
		] ) );

		add_filter( 'wpepp_geo_country', function() {
			return '';
		} );

		$access = new WPEPP_Site_Access();
		$this->assertNull( $access->get_site_access_block() );
	}

	public function test_country_restriction_free_tier_enforcement() {
		$sanitized = wpepp_enforce_pro_settings( [
			'country_restriction_list' => [ 'US', 'BD', 'GB', 'FR', 'DE' ],
		], 'site_access' );

		$this->assertCount( 2, $sanitized['country_restriction_list'] );
		$this->assertSame( [ 'US', 'BD' ], $sanitized['country_restriction_list'] );
	}
}
