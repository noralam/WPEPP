<?php
/**
 * Unit tests for Site-Wide Access Modes over WordPress REST API:
 * - Fix for CVE-2025-8945 residual reported by WPScan / Automattic (Artus KG).
 * - Site Password protection over REST.
 * - Admin Only mode protection over REST.
 * - Country Restriction protection over REST.
 * - Webhook exemptions and Emergency Safe Mode bypasses.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SiteAccessRestTest
 */
class SiteAccessRestTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']                        = [];
		$GLOBALS['wp_test_filters']                        = [];
		$GLOBALS['wp_test_object_cache']                   = [];
		$GLOBALS['wp_test_current_user_can_manage_options'] = false;
		$GLOBALS['wp']                                     = new \stdClass();
		$GLOBALS['wp']->query_vars                         = [];
		$_COOKIE                                           = [];
		$_GET                                              = [];
		$_SERVER['REQUEST_URI']                            = '/wp-json/wp/v2/posts';
	}

	public function test_admin_only_blocks_rest_for_unauthenticated_visitor() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'admin_only_enabled' => true,
		] ) );

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wpepp_admin_only_restricted', $result->get_error_code() );
	}

	public function test_admin_only_allows_authenticated_admin_over_rest() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'admin_only_enabled' => true,
		] ) );

		$GLOBALS['wp_test_current_user_can_manage_options'] = true;

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertNull( $result );
	}

	public function test_site_password_blocks_rest_without_cookie() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => 'SecretPass123',
		] ) );

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wpepp_site_password_required', $result->get_error_code() );
	}

	public function test_site_password_allows_rest_with_valid_cookie() {
		$password = 'SecretPass123';
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => $password,
		] ) );

		$_COOKIE[ WPEPP_Site_Access::COOKIE_NAME ] = wp_hash( $password );

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertNull( $result );
	}

	public function test_safe_mode_bypasses_site_wide_rest_restriction() {
		$flag_file = WP_CONTENT_DIR . '/wpepp-safe-mode.txt';
		if ( ! is_dir( WP_CONTENT_DIR ) ) {
			@mkdir( WP_CONTENT_DIR, 0777, true );
		}
		file_put_contents( $flag_file, 'Safe Mode Active' );

		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => 'SecretPass123',
		] ) );

		// Force refresh safe mode cache
		WPEPP_Security::is_safe_mode( true );

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		@unlink( $flag_file );
		WPEPP_Security::is_safe_mode( true );

		$this->assertNull( $result );
	}

	public function test_webhook_endpoints_bypass_site_access_rest() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => 'SecretPass123',
		] ) );

		add_filter( 'wpepp_site_access_allowed_rest_routes', function ( $routes ) {
			$routes[] = '/stripe/webhook';
			return $routes;
		} );

		$GLOBALS['wp']->query_vars['rest_route'] = '/stripe/webhook';

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertNull( $result );
	}

	public function test_wpepp_preview_parameter_does_not_bypass_site_access_rest() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => 'SecretPass123',
		] ) );

		$_GET['wpepp_preview']                   = '1';
		$GLOBALS['wp']->query_vars['rest_route'] = '/wp/v2/posts';

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wpepp_site_password_required', $result->get_error_code() );
	}

	public function test_query_parameter_injection_does_not_bypass_site_access_rest() {
		update_option( 'wpepp_site_access_settings', wp_json_encode( [
			'site_password_enabled' => true,
			'site_password'         => 'SecretPass123',
		] ) );

		$_GET['wc-api']                          = '1';
		$_SERVER['REQUEST_URI']                  = '/wp-json/wp/v2/posts/1?wc-api=1';
		$GLOBALS['wp']->query_vars['rest_route'] = '/wp/v2/posts/1';

		$access = new WPEPP_Site_Access();
		$result = $access->enforce_rest_access( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wpepp_site_password_required', $result->get_error_code() );
	}
}
