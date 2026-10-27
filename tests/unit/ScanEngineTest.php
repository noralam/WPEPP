<?php
/**
 * Unit tests for WPEPP_Scan_Engine state machine transitions.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScanEngineTest
 */
class ScanEngineTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['wp_test_options']      = [];
		$GLOBALS['wp_test_filters']      = [];
		$GLOBALS['wp_test_object_cache'] = [];

		require_once dirname( __DIR__, 2 ) . '/includes/scanner/class-issues.php';
		require_once dirname( __DIR__, 2 ) . '/includes/scanner/class-signatures.php';
		require_once dirname( __DIR__, 2 ) . '/includes/scanner/class-option-scan.php';
		require_once dirname( __DIR__, 2 ) . '/includes/scanner/class-scan-engine.php';
	}

	public function test_start_scan_initializes_state() {
		WPEPP_Scan_Engine::start( 'quick' );
		$state = WPEPP_Scan_Engine::state();

		$this->assertTrue( WPEPP_Scan_Engine::is_running() );
		$this->assertSame( 'files', $state['stage'] );
		$this->assertSame( 'quick', $state['profile'] );
		$this->assertSame( '', $state['cursor'] );
	}

	public function test_advance_persists_stage_to_database() {
		WPEPP_Scan_Engine::start( 'quick' );
		$state = WPEPP_Scan_Engine::state();

		WPEPP_Scan_Engine::advance( $state, 'options' );

		$refreshed = WPEPP_Scan_Engine::state();
		$this->assertSame( 'options', $refreshed['stage'] );
	}

	public function test_options_stage_advances_to_users_and_finishes() {
		// Mock state at options stage.
		update_option(
			WPEPP_Scan_Engine::STATE_KEY,
			wp_json_encode( [
				'running'    => true,
				'profile'    => 'quick',
				'stage'      => 'options',
				'cursor'     => '',
				'scanned'    => 100,
				'flagged'    => 0,
				'started_at' => time(),
				'tick_at'    => time(),
			] )
		);

		// Tick 1: Options runs, advances to users stage.
		$more1 = WPEPP_Scan_Engine::tick( true );
		$this->assertTrue( $more1, 'Options stage advances to users stage and yields.' );
		$state1 = WPEPP_Scan_Engine::state();
		$this->assertSame( 'users', $state1['stage'] );

		// Tick 2: Users runs, finishes scan.
		$more2 = WPEPP_Scan_Engine::tick( true );
		$this->assertFalse( $more2, 'Users stage completes the scan and returns false.' );

		$final_state = WPEPP_Scan_Engine::state();
		$this->assertFalse( $final_state['running'], 'Scan must no longer be running.' );
		$this->assertSame( 'done', $final_state['stage'], 'Scan stage must be done.' );
		$this->assertNotEmpty( get_option( 'wpepp_last_scan' ), 'wpepp_last_scan option must be populated.' );
	}

	public function test_stop_scan() {
		WPEPP_Scan_Engine::start( 'quick' );
		$this->assertTrue( WPEPP_Scan_Engine::is_running() );

		WPEPP_Scan_Engine::stop();
		$this->assertFalse( WPEPP_Scan_Engine::is_running() );
		$state = WPEPP_Scan_Engine::state();
		$this->assertSame( 'stopped', $state['stage'] );
	}

	public function test_signatures_have_target_extensions_and_skip_js_on_php_backdoors() {
		wp_cache_delete( 'wpepp_scan_signatures', 'wpepp' );
		$sigs = WPEPP_Signatures::signatures( true );

		$this->assertNotEmpty( $sigs );

		foreach ( $sigs as $sig ) {
			$this->assertArrayHasKey( 'targets', $sig );
			$this->assertIsArray( $sig['targets'] );

			// eval_base64_chain must only target PHP.
			if ( 'eval_base64_chain' === $sig['id'] ) {
				$this->assertNotContains( 'js', $sig['targets'] );
				$this->assertContains( 'php', $sig['targets'] );
			}

			// cloaked_iframe must not target JS.
			if ( 'cloaked_iframe' === $sig['id'] ) {
				$this->assertNotContains( 'js', $sig['targets'] );
			}
		}
	}

	public function test_preg_replace_eval_regex_does_not_flag_normal_replacement_strings() {
		wp_cache_delete( 'wpepp_scan_signatures', 'wpepp' );
		$sigs = WPEPP_Signatures::signatures( true );
		$preg_sig = null;
		foreach ( $sigs as $s ) {
			if ( 'preg_replace_eval' === $s['id'] ) {
				$preg_sig = $s;
				break;
			}
		}

		$this->assertNotNull( $preg_sig );

		// Malicious code: /e modifier in pattern delimiter.
		$malicious = "preg_replace('/[a-z]+/e', 'eval_code()', \$str);";
		$this->assertSame( 1, preg_match( $preg_sig['regex'], $malicious ) );

		// Benign code: replacement string happens to have letter 'e'.
		$benign = "preg_replace('/[a-z]+/', 'replacement_with_letter_e', \$str);";
		$this->assertSame( 0, preg_match( $preg_sig['regex'], $benign ) );
	}

	public function test_delete_file_safely() {
		// Non-existent issue.
		$res = WPEPP_Issues::delete_file( 999999 );
		$this->assertFalse( $res['success'] );

		// Create a temporary file inside ABSPATH.
		$temp_file = ABSPATH . 'test_malware_drop.php';
		file_put_contents( $temp_file, '<?php // backdoor' );
		$this->assertFileExists( $temp_file );

		// Mock issue row in wpdb.
		$GLOBALS['wpdb']->row_to_return = [
			'id'          => 123,
			'kind'        => 'malware',
			'object_path' => $temp_file,
		];

		// Delete file through engine.
		$delete_res = WPEPP_Issues::delete_file( 123 );
		$this->assertTrue( $delete_res['success'] );
		$this->assertFileDoesNotExist( $temp_file );

		// Reset mock.
		$GLOBALS['wpdb']->row_to_return = null;
	}
}
