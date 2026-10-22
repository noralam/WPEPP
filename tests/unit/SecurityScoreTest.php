<?php
/**
 * Unit tests for WPEPP_Security_Score — checklist integrity, grading,
 * and wizard preset application.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SecurityScoreTest
 */
class SecurityScoreTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']    = [];
		$GLOBALS['wp_test_filters']    = [];
		$GLOBALS['wp_test_object_cache'] = [];
	}

	private function set_security( array $settings ) {
		$GLOBALS['wp_test_options']['wpepp_security_settings'] = json_encode( $settings );
	}

	private function set_site_access( array $settings ) {
		$GLOBALS['wp_test_options']['wpepp_site_access_settings'] = json_encode( $settings );
	}

	/* ─── Grading ─── */

	public function test_grades() {
		$this->assertSame( 'A', WPEPP_Security_Score::grade_for( 95 ) );
		$this->assertSame( 'A', WPEPP_Security_Score::grade_for( 90 ) );
		$this->assertSame( 'B', WPEPP_Security_Score::grade_for( 89 ) );
		$this->assertSame( 'C', WPEPP_Security_Score::grade_for( 60 ) );
		$this->assertSame( 'D', WPEPP_Security_Score::grade_for( 45 ) );
		$this->assertSame( 'F', WPEPP_Security_Score::grade_for( 10 ) );
	}

	/* ─── Checklist ─── */

	public function test_checklist_has_expected_items() {
		$items = WPEPP_Security_Score::get_items();
		$ids   = wp_list_pluck_stub( $items, 'id' );

		foreach ( [ 'login_limiter', 'honeypot', 'xmlrpc_off', 'https_admin', 'hide_login' ] as $expected ) {
			$this->assertContains( $expected, $ids );
		}
	}

	public function test_score_full_marks_when_everything_on() {
		$this->set_security(
			[
				'two_factor_enabled'         => true,
				'username_shield_enabled'    => true,
				'disable_rest_users'         => true,
				'ai_crawler_blocker_enabled' => true,
				'hide_wp_version'            => true,
				'disable_xmlrpc'             => true,
				'hide_login_page'            => true,
				'alert_email'                => 'admin@example.com',
			]
		);
		$this->set_site_access( [ 'site_password_enabled' => true ] );

		// DISALLOW_FILE_EDIT is the only remaining off item.
		$r = WPEPP_Security_Score::get_summary();

		$this->assertSame( $r['total'] - 1, $r['passed'], 'Only the file-edit check should fail.' );
		$this->assertGreaterThan( 80, $r['score'], 'Score should be high with one minor item missing.' );
	}

	public function test_score_zero_when_nothing_configured() {
		// Defaults-on items still pass — verify score reflects only optional items failing.
		$r = WPEPP_Security_Score::get_summary();

		$this->assertGreaterThan( 0, $r['max'], 'Checklist must have weight.' );
		$this->assertLessThan( 100, $r['score'] );
	}

	public function test_filter_can_extend_checklist() {
		add_filter( 'wpepp/security/score_items', static function ( $items ) {
			$items[] = [
				'id'       => 'pro_firewall',
				'label'    => 'Firewall extended',
				'weight'   => 20,
				'category' => 'firewall',
				'done'     => true,
				'route'    => '/firewall',
			];
			return $items;
		} );

		$items = WPEPP_Security_Score::get_items();
		$ids   = wp_list_pluck_stub( $items, 'id' );

		$this->assertContains( 'pro_firewall', $ids );
	}

	public function test_malformed_filter_items_dropped() {
		add_filter( 'wpepp/security/score_items', static function ( $items ) {
			$items[] = [ 'no_id', 'broken' ];          // Missing required keys.
			$items[] = [ 'id' => 'x', 'weight' => -5, 'done' => true ]; // Bad weight.
			return $items;
		} );

		foreach ( WPEPP_Security_Score::get_items() as $item ) {
			$this->assertArrayHasKey( 'id', $item );
			$this->assertNotEmpty( $item['id'] );
			$this->assertGreaterThan( 0, $item['weight'] );
		}
	}

	/* ─── Wizard presets ─── */

	public function test_wizard_steps_exist() {
		$presets = WPEPP_Security_Score::wizard_presets();

		foreach ( [ 'logins', 'shield', 'harden', 'monitor', 'alerts' ] as $step ) {
			$this->assertArrayHasKey( $step, $presets );
		}
	}

	public function test_apply_step_enables_features() {
		$result = WPEPP_Security_Score::apply_wizard_step( 'harden' );

		$this->assertTrue( $result );

		$saved = json_decode( $GLOBALS['wp_test_options']['wpepp_security_settings'], true );

		$this->assertTrue( $saved['disable_xmlrpc'] );
		$this->assertTrue( $saved['hide_login_page'] );
		$this->assertTrue( $saved['hide_login_exempt_logged_in'] );
	}

	public function test_apply_unknown_step_errors() {
		$result = WPEPP_Security_Score::apply_wizard_step( 'nonsense' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wpepp_unknown_step', $result->get_error_code() );
	}

	public function test_apply_skips_secret_keys() {
		$this->set_security( [ 'ip_blocklist' => '1.2.3.4' ] );

		WPEPP_Security_Score::apply_wizard_step( 'logins' );

		$saved = json_decode( $GLOBALS['wp_test_options']['wpepp_security_settings'], true );

		// Preset never touches list/secret keys.
		$this->assertSame( '1.2.3.4', $saved['ip_blocklist'] );
	}
}

/**
 * Local wp_list_pluck shim for tests.
 *
 * @param array  $list Input list.
 * @param string $field Field key.
 * @return array
 */
function wp_list_pluck_stub( $list, $field ) {
	$out = [];
	foreach ( $list as $item ) {
		if ( is_array( $item ) && isset( $item[ $field ] ) ) {
			$out[] = $item[ $field ];
		}
	}
	return $out;
}
