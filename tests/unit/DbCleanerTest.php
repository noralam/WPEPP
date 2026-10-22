<?php
/**
 * Test WPEPP_DB_Cleaner table stats and pruning routines.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

class DbCleanerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'WPEPP_DB_Cleaner' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-db-cleaner.php';
		}
	}

	public function test_get_stats_returns_expected_structure() {
		$stats = WPEPP_DB_Cleaner::get_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'tables', $stats );
		$this->assertArrayHasKey( 'total_rows', $stats );
		$this->assertArrayHasKey( 'total_size', $stats );
		$this->assertArrayHasKey( 'auto_prune', $stats );
		$this->assertArrayHasKey( 'max_rows_cap', $stats );

		$this->assertArrayHasKey( 'events', $stats['tables'] );
		$this->assertArrayHasKey( 'login_log', $stats['tables'] );
		$this->assertArrayHasKey( 'slow_queries', $stats['tables'] );
		$this->assertArrayHasKey( 'blocks', $stats['tables'] );
	}

	public function test_purge_all_old_logs_returns_purged_counts() {
		$purged = WPEPP_DB_Cleaner::purge_all_old_logs();

		$this->assertIsArray( $purged );
		$this->assertArrayHasKey( 'events', $purged );
		$this->assertArrayHasKey( 'login_log', $purged );
		$this->assertArrayHasKey( 'slow_queries', $purged );
		$this->assertArrayHasKey( 'blocks', $purged );
		$this->assertArrayHasKey( 'total', $purged );
	}

	public function test_purge_table_invalid_key_returns_zero() {
		$result = WPEPP_DB_Cleaner::purge_table( 'non_existent_table' );
		$this->assertSame( 0, $result );
	}
}
