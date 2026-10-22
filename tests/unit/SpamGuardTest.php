<?php
/**
 * Unit tests for WPEPP_Spam_Guard — WooCommerce bot protection & anti-spam defense.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SpamGuardTest
 */
class SpamGuardTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$_POST = [];
	}

	protected function tearDown(): void {
		$_POST = [];
		parent::tearDown();
	}

	public function test_wc_registration_passes_for_clean_submission() {
		$errors = new \WP_Error();
		$res    = WPEPP_Spam_Guard::validate_wc_registration( $errors, 'realuser', 'secretPass123!', 'valid.person@example.com' );

		$this->assertFalse( $res->has_errors() );
	}

	public function test_wc_registration_blocks_honeypot_bot() {
		$_POST['wpepp_wc_hp'] = 'I am an automated bot';

		$errors = new \WP_Error();
		$res    = WPEPP_Spam_Guard::validate_wc_registration( $errors, 'botuser', 'pass', 'bot@example.com' );

		$this->assertTrue( $res->has_errors() );
		$this->assertArrayHasKey( 'wpepp_wc_honeypot', $res->errors );
	}

	public function test_wc_registration_blocks_disposable_email() {
		$errors = new \WP_Error();
		$res    = WPEPP_Spam_Guard::validate_wc_registration( $errors, 'fakeuser', 'pass', 'spammer123@mailinator.com' );

		$this->assertTrue( $res->has_errors() );
		$this->assertArrayHasKey( 'wpepp_wc_disposable_email', $res->errors );
	}

	public function test_lostpassword_blocks_honeypot_bot() {
		$_POST['wpepp_lostpwd_hp'] = 'spam reset';

		$errors = new \WP_Error();
		$res    = WPEPP_Spam_Guard::validate_lostpassword( $errors );

		$this->assertTrue( $res->has_errors() );
		$this->assertArrayHasKey( 'wpepp_lostpwd_hp', $res->errors );
	}
}
