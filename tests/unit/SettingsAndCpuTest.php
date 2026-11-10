<?php
/**
 * Tests for Settings functionality (cookie expiration filter, CPU Monitor accuracy and health score).
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

require_once WPEPP_PATH . 'includes/class-cpu-system-info.php';
require_once WPEPP_PATH . 'includes/class-plugin.php';

class SettingsAndCpuTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = [];
		delete_option( 'wpepp_general_settings' );
		delete_transient( 'wpepp_cpu_percent' );
	}

	protected function tearDown(): void {
		delete_option( 'wpepp_general_settings' );
		delete_transient( 'wpepp_cpu_percent' );
		parent::tearDown();
	}

	public function test_post_password_expires_uses_default_ten_days(): void {
		$now = time();
		$expires = WPEPP_Plugin::filter_post_password_expires( 0 );
		$diff_days = round( ( $expires - $now ) / DAY_IN_SECONDS );

		$this->assertSame( 10.0, (float) $diff_days );
	}

	public function test_post_password_expires_uses_custom_days(): void {
		update_option( 'wpepp_general_settings', json_encode( [ 'cookie_expiration' => 30 ] ) );
		$now = time();
		$expires = WPEPP_Plugin::filter_post_password_expires( 0 );
		$diff_days = round( ( $expires - $now ) / DAY_IN_SECONDS );

		$this->assertSame( 30.0, (float) $diff_days );
	}

	public function test_post_password_expires_falls_back_on_invalid_value(): void {
		update_option( 'wpepp_general_settings', json_encode( [ 'cookie_expiration' => 0 ] ) );
		$now = time();
		$expires = WPEPP_Plugin::filter_post_password_expires( 0 );
		$diff_days = round( ( $expires - $now ) / DAY_IN_SECONDS );

		$this->assertSame( 10.0, (float) $diff_days );
	}

	public function test_cpu_system_info_get_stats_structure(): void {
		$stats = WPEPP_CPU_System_Info::get_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'cpu_percent', $stats );
		$this->assertArrayHasKey( 'cpu_cores', $stats );
		$this->assertArrayHasKey( 'memory', $stats );
		$this->assertArrayHasKey( 'health', $stats );
		$this->assertContains( $stats['health'], [ 'green', 'yellow', 'red' ] );
		$this->assertGreaterThanOrEqual( 1, $stats['cpu_cores'] );
		$this->assertGreaterThanOrEqual( -1, $stats['cpu_percent'] );
		$this->assertLessThanOrEqual( 100, $stats['cpu_percent'] );
	}

	public function test_cpu_system_info_core_detection_positive(): void {
		$cores = WPEPP_CPU_System_Info::get_cpu_cores();
		$this->assertGreaterThanOrEqual( 1, $cores );
	}
}
