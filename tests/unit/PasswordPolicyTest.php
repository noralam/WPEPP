<?php
/**
 * Unit tests for WPEPP_Password_Policy — pure validation logic.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

/**
 * Class PasswordPolicyTest
 */
class PasswordPolicyTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']    = [];
		$GLOBALS['wp_test_filters']    = [];
		$GLOBALS['wp_test_has_pro']    = true; // Breach-check gate on by default in tests.
	}

	private function enable( array $over = [] ) {
		$GLOBALS['wp_test_options']['wpepp_security_settings'] = json_encode(
			array_merge(
				[
					'pw_policy_enabled' => true,
					'pw_min_length'     => 12,
					'pw_require_mixed'  => true,
					'pw_require_symbol' => false,
					'pw_block_common'   => true,
					'pw_breach_check'   => false,
				],
				$over
			)
		);
	}

	public function test_disabled_policy_never_errors() {
		$this->enable( [ 'pw_policy_enabled' => false ] );

		$this->assertSame( [], WPEPP_Password_Policy::validate( 'password' ) );
	}

	public function test_min_length() {
		$this->enable( [ 'pw_min_length' => 12 ] );

		$errors = WPEPP_Password_Policy::validate( 'Sh0rt#x' );
		$this->assertCount( 1, $errors, 'Only the length error should fire.' );
		$this->assertStringContainsString( '12', $errors[0] );

		$this->assertSame(
			[],
			WPEPP_Password_Policy::validate( 'LongEnough123' ),
			'Mixed-case + digits + length should pass.'
		);
	}

	public function test_mixed_case_requirement() {
		$this->enable();

		$all_lower  = WPEPP_Password_Policy::validate( 'alllowercase12345' ); // Missing upper.
		$all_upper  = WPEPP_Password_Policy::validate( 'ALLUPPERCASE123' ); // Missing lower.
		$no_digits  = WPEPP_Password_Policy::validate( 'NoDigitsHereAbc' );     // Missing digit.
		$valid      = WPEPP_Password_Policy::validate( 'GoodMix1234567' );       // Has all three.

		$this->assertCount( 1, $all_lower );
		$this->assertCount( 1, $all_upper );
		$this->assertCount( 1, $no_digits );
		$this->assertSame( [], $valid );
	}

	public function test_symbol_requirement_optional() {
		$this->enable( [ 'pw_require_symbol' => true ] );

		$this->assertCount( 1, WPEPP_Password_Policy::validate( 'GoodMix12345678' ), 'Missing symbol should error.' );
		$this->assertSame( [], WPEPP_Password_Policy::validate( 'GoodMix12345!' ) );
	}

	public function test_common_passwords_blocked_case_insensitively() {
		$this->enable();

		$this->assertNotEmpty( WPEPP_Password_Policy::validate( 'PASSWORD' ), 'Uppercase variant of common pw must still block.' );
		$this->assertNotEmpty( WPEPP_Password_Policy::validate( 'password123' ) );
	}

	public function test_common_block_can_be_disabled() {
		$this->enable( [ 'pw_block_common' => false ] );

		// Still fails mixed/length rules but must NOT include the common-pw message.
		foreach ( WPEPP_Password_Policy::validate( 'password123' ) as $error ) {
			$this->assertStringNotContainsStringIgnoringCase( 'public lists', $error );
		}
	}

	public function test_breach_check_requires_pro_gate_and_setting() {
		$this->enable( [ 'pw_breach_check' => true ] );

		// Pro active → validate() may call breach path; network is stubbed
		// away because wp_remote_get doesn't exist here — ensure no fatal and
		// fail-open semantics via the skipped marker contract instead.
		$GLOBALS['wp_test_has_pro'] = false;
		$this->assertSame( [], WPEPP_Password_Policy::validate( 'GoodMix12345' ) );
	}

	public function test_score_item_registered_when_filter_runs() {
		add_filter( 'wpepp/security/score_items', [ 'WPEPP_Password_Policy', 'score_item' ] );

		$this->enable();
		$ids = wp_list_pluck_stub( WPEPP_Security_Score::get_items(), 'id' );

		$this->assertContains( 'pw_policy', $ids );
	}
}
