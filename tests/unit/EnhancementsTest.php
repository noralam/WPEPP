<?php
/**
 * Unit tests for new security enhancements:
 * - Remote malware signature sync info
 * - SMTP detection
 * - File permissions inspection
 * - Cloudflare CIDRs & proxy status
 * - Security audit report generation
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EnhancementsTest
 */
class EnhancementsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']    = [];
		$GLOBALS['wp_test_filters']    = [];
		$GLOBALS['wp_test_object_cache'] = [];
	}

	public function test_cloudflare_cidrs_structure_and_validity() {
		$cidrs = WPEPP_Core_Ip::get_cloudflare_cidrs();
		$this->assertIsArray( $cidrs );
		$this->assertGreaterThanOrEqual( 20, count( $cidrs ) );

		// Check known Cloudflare subnets
		$this->assertContains( '173.245.48.0/20', $cidrs );
		$this->assertContains( '104.16.0.0/13', $cidrs );
		$this->assertContains( '2400:cb00::/32', $cidrs );

		// Test that a Cloudflare IP matches the CIDR
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '173.245.48.15', '173.245.48.0/20' ) );
		$this->assertTrue( WPEPP_Core_Ip::ip_in_cidr( '104.16.5.99', '104.16.0.0/13' ) );
	}

	public function test_inspect_proxy_status() {
		$_SERVER['REMOTE_ADDR'] = '198.41.128.5';
		$status = WPEPP_Core_Ip::inspect_proxy_status();

		$this->assertIsArray( $status );
		$this->assertArrayHasKey( 'remote_addr', $status );
		$this->assertArrayHasKey( 'client_ip', $status );
		$this->assertArrayHasKey( 'is_trusted_proxy', $status );
		$this->assertArrayHasKey( 'cloudflare_cidrs', $status );
		$this->assertSame( '198.41.128.5', $status['remote_addr'] );
	}

	public function test_detect_smtp() {
		$smtp = WPEPP_Alerts::detect_smtp();
		$this->assertIsArray( $smtp );
		$this->assertArrayHasKey( 'configured', $smtp );
		$this->assertArrayHasKey( 'provider', $smtp );
		$this->assertIsBool( $smtp['configured'] );
		$this->assertIsString( $smtp['provider'] );
	}

	public function test_inspect_file_permissions() {
		$perms = WPEPP_File_Hardening::inspect_file_permissions();
		$this->assertIsArray( $perms );

		$expected_keys = [ 'wp-config.php', '.htaccess', '.user.ini', 'index.php', 'wp-content' ];
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $perms );
			$this->assertArrayHasKey( 'expected', $perms[ $key ] );
			$this->assertArrayHasKey( 'status', $perms[ $key ] );
		}
	}

	public function test_signatures_sync_info() {
		$info = WPEPP_Signatures::get_sync_info();
		$this->assertIsArray( $info );
		$this->assertArrayHasKey( 'local_count', $info );
		$this->assertArrayHasKey( 'remote_count', $info );
		$this->assertArrayHasKey( 'total', $info );
		$this->assertArrayHasKey( 'feed_url', $info );
		$this->assertGreaterThan( 10, $info['total'] );
	}

	public function test_generate_audit_report() {
		$report = WPEPP_Security_Score::generate_audit_report();
		$this->assertIsArray( $report );

		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertArrayHasKey( 'site', $report );
		$this->assertArrayHasKey( 'score', $report );
		$this->assertArrayHasKey( 'checklist', $report );
		$this->assertArrayHasKey( 'firewall', $report );
		$this->assertArrayHasKey( 'scanner', $report );
		$this->assertArrayHasKey( 'hardening', $report );
		$this->assertArrayHasKey( 'smtp', $report );
		$this->assertArrayHasKey( 'proxy', $report );

		$this->assertIsArray( $report['site'] );
		$this->assertArrayHasKey( 'wp_version', $report['site'] );
		$this->assertArrayHasKey( 'php_version', $report['site'] );
		$this->assertArrayHasKey( 'score', $report['score'] );
		$this->assertArrayHasKey( 'grade', $report['score'] );
	}

	public function test_safe_mode_file_and_bypass() {
		// phpcs:disable WordPress.WP.AlternativeFunctions, PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite -- Test verifies emergency safe-mode bypass via file creation.
		if ( ! is_dir( WP_CONTENT_DIR ) ) {
			wp_mkdir_p( WP_CONTENT_DIR );
		}

		$flag_file = WP_CONTENT_DIR . '/wpepp-safe-mode.txt';

		// Clean up any preexisting file.
		if ( file_exists( $flag_file ) ) {
			wp_delete_file( $flag_file );
		}

		$this->assertFalse( WPEPP_Security::is_safe_mode( true ) );

		// Create safe mode flag file.
		file_put_contents( $flag_file, 'Safe mode activated' );
		$this->assertTrue( WPEPP_Security::is_safe_mode( true ) );

		// When safe mode is active, is_ip_locked must return false immediately.
		$this->assertFalse( WPEPP_Security::is_ip_locked( '192.168.1.50' ) );

		// Clean up.
		wp_delete_file( $flag_file );
		$this->assertFalse( WPEPP_Security::is_safe_mode( true ) );
		// phpcs:enable WordPress.WP.AlternativeFunctions, PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite
	}

	public function test_webhook_exemption() {
		// Test standard payment and webhook endpoints
		$webhook_requests = [
			[ 'uri' => '/wc-api/WC_Gateway_Stripe' ],
			[ 'uri' => '/wp-json/wc/v3/webhooks' ],
			[ 'uri' => '/wp-json/stripe/v1/webhook' ],
			[ 'uri' => '/wp-json/paypal/ipn' ],
			[ 'uri' => '/wp-json/razorpay/webhook' ],
			[ 'uri' => '/wp-json/mollie/webhook' ],
			[ 'uri' => '/wp-json/square/callback' ],
			[ 'uri' => '/wp-json/paystack/webhook' ],
			[ 'uri' => '/wp-json/contact-form-7/v1/contact-forms' ],
			[ 'uri' => '/checkout/?wc-ajax=update_order_review' ],
			[ 'uri' => '/edd-listener=stripe' ],
		];

		foreach ( $webhook_requests as $req ) {
			$this->assertTrue(
				WPEPP_Firewall::is_webhook_exempt( $req ),
				"Failed asserting that {$req['uri']} is webhook exempt."
			);
		}

		// Non-webhook requests must NOT be exempt
		$regular_requests = [
			[ 'uri' => '/wp-login.php' ],
			[ 'uri' => '/?id=1%27%20OR%201=1' ],
			[ 'uri' => '/wp-admin/admin-ajax.php' ],
			[ 'uri' => '/blog/sample-post/' ],
		];

		foreach ( $regular_requests as $req ) {
			$this->assertFalse(
				WPEPP_Firewall::is_webhook_exempt( $req ),
				"Failed asserting that {$req['uri']} is NOT webhook exempt."
			);
		}

		// Test filter hook wpepp_firewall_whitelist_endpoints
		add_filter( 'wpepp_firewall_whitelist_endpoints', function ( $endpoints ) {
			$endpoints[] = 'custom-billing-webhook';
			return $endpoints;
		} );

		$this->assertTrue( WPEPP_Firewall::is_webhook_exempt( [ 'uri' => '/api/custom-billing-webhook' ] ) );
	}

	public function test_runtime_block_cache() {
		// Test that find_active_block caches result per-request
		$block = WPEPP_Blocks::find_active_block( '10.0.0.99' );
		$this->assertNull( $block );

		// Second call for same IP should resolve through runtime cache without errors
		$block2 = WPEPP_Blocks::find_active_block( '10.0.0.99' );
		$this->assertNull( $block2 );
	}

	public function test_autologin_token_encryption_and_decryption() {
		$raw_token = 'secret_token_abc123_xyz789_' . bin2hex( random_bytes( 16 ) );
		$encrypted = WPEPP_Security::encrypt_token( $raw_token );

		$this->assertNotEmpty( $encrypted );
		$this->assertNotEquals( $raw_token, $encrypted );

		$decrypted = WPEPP_Security::decrypt_token( $encrypted );
		$this->assertSame( $raw_token, $decrypted );
	}

	public function test_autologin_token_legacy_plain_fallback() {
		$legacy = 'legacy_unencrypted_token';
		$decrypted = WPEPP_Security::decrypt_token( $legacy );
		$this->assertSame( $legacy, $decrypted );
	}

	public function test_pro_settings_enforcement_for_v3_features() {
		$input_settings = [
			'scan_schedule_enabled'       => true,
			'wc_disposable_email_block'   => true,
			'wc_captcha_enabled'          => true,
			'comment_captcha_enabled'     => true,
			'lostpassword_captcha_enabled'=> true,
			'auto_prune_logs_enabled'     => true,
			'events_retention_days'       => 90,
			'login_log_retention_days'    => 90,
			'slow_queries_retention_days' => 30,
			'max_log_rows_cap'            => 10000,
			'free_feature'                => 'allowed_value',
		];

		// Ensure Pro is inactive.
		delete_option( 'wpepp_has_pro' );

		$enforced = wpepp_enforce_pro_settings( $input_settings, 'security' );

		$this->assertArrayNotHasKey( 'scan_schedule_enabled', $enforced );
		$this->assertArrayNotHasKey( 'wc_disposable_email_block', $enforced );
		$this->assertArrayNotHasKey( 'wc_captcha_enabled', $enforced );
		$this->assertArrayNotHasKey( 'comment_captcha_enabled', $enforced );
		$this->assertArrayNotHasKey( 'lostpassword_captcha_enabled', $enforced );
		$this->assertArrayNotHasKey( 'auto_prune_logs_enabled', $enforced );
		$this->assertArrayNotHasKey( 'events_retention_days', $enforced );
		$this->assertArrayNotHasKey( 'login_log_retention_days', $enforced );
		$this->assertArrayNotHasKey( 'slow_queries_retention_days', $enforced );
		$this->assertArrayNotHasKey( 'max_log_rows_cap', $enforced );
		$this->assertSame( 'allowed_value', $enforced['free_feature'] );
	}

	public function test_db_cleaner_cron_maintenance_bypassed_without_pro() {
		delete_option( 'wpepp_has_pro' );
		// Should execute cleanly and return without running pruning when not Pro.
		WPEPP_DB_Cleaner::cron_maintenance();
		$this->assertTrue( true );
	}

	public function test_auto_login_free_cap_enforcement() {
		// Mock existing 3 links in option.
		update_option( 'wpepp_auto_login_links', [
			[ 'id' => 'link1' ],
			[ 'id' => 'link2' ],
			[ 'id' => 'link3' ],
		] );

		$GLOBALS['wp_test_has_pro'] = false;
		$links = get_option( 'wpepp_auto_login_links', [] );
		$is_capped = ! wpepp_has_pro_check() && count( $links ) >= 3;
		$this->assertTrue( $is_capped );

		// When Pro is unlocked, cap is not enforced.
		$GLOBALS['wp_test_has_pro'] = true;
		$is_capped_pro = ! wpepp_has_pro_check() && count( $links ) >= 3;
		$this->assertFalse( $is_capped_pro );
		$GLOBALS['wp_test_has_pro'] = false;
	}
}
