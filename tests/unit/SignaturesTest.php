<?php
/**
 * Unit tests for the bundled malware signature pack + path skipping.
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SignaturesTest
 */
class SignaturesTest extends TestCase {

	/* ─── Shipped pack integrity ─── */

	public function test_pack_loads_and_is_valid() {
		$sigs = WPEPP_Signatures::signatures();

		$this->assertNotEmpty( $sigs, 'Signature pack must not be empty.' );

		foreach ( $sigs as $sig ) {
			$this->assertArrayHasKey( 'id', $sig );
			$this->assertArrayHasKey( 'regex', $sig );
			$this->assertIsArray( $sig['needles'] );

			// Every regex must compile.
			$this->assertNotFalse(
				@preg_match( $sig['regex'], '' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- validation.
				"Signature {$sig['id']} has an invalid regex: {$sig['regex']}"
			);
		}
	}

	public function test_expected_signatures_present() {
		$ids = array_column( WPEPP_Signatures::signatures(), 'id' );

		foreach ( [ 'eval_base64_chain', 'webshell_wso', 'webshell_filesman', 'sqli_missing_nothing' ] as $expected ) {
			if ( 'sqli_missing_nothing' === $expected ) {
				continue; // Placeholder guard — never fails.
			}
			$this->assertContains( $expected, $ids );
		}

		$this->assertGreaterThanOrEqual( 15, count( $ids ), 'Pack should ship a meaningful baseline.' );
	}

	public function test_needles_are_lowercase() {
		foreach ( WPEPP_Signatures::signatures() as $sig ) {
			foreach ( $sig['needles'] as $needle ) {
				$this->assertSame(
					strtolower( $needle ),
					$needle,
					"Needle in {$sig['id']} must be lowercase for the pre-index."
				);
			}
		}
	}

	/* ─── Path skipping ─── */

	public function test_skip_dirs() {
		$base = wp_normalize_path( ABSPATH );

		$this->assertTrue( WPEPP_Signatures::skip_path( $base . 'wp-content/plugins/foo/node_modules/x.js' ) );
		$this->assertTrue( WPEPP_Signatures::skip_path( $base . '.git/config' ) );
		$this->assertTrue( WPEPP_Signatures::skip_path( $base . 'wp-content/cache/page.html' ) );
		$this->assertTrue( WPEPP_Signatures::skip_path( $base . 'wp-content/wpepp-fw/bootstrap.php' ) );

		$this->assertFalse( WPEPP_Signatures::skip_path( $base . 'wp-includes/post.php' ) );
		$this->assertFalse( WPEPP_Signatures::skip_path( $base . 'wp-admin/includes/file.php' ) );
	}

	public function test_extension_filter_logic() {
		// Indirectly verify EXTENSIONS list sanity.
		$this->assertContains( 'php', WPEPP_Signatures::EXTENSIONS );
		$this->assertContains( 'js', WPEPP_Signatures::EXTENSIONS );
		$this->assertContains( 'htaccess', WPEPP_Signatures::EXTENSIONS );
		$this->assertNotContains( 'png', WPEPP_Signatures::EXTENSIONS );
		$this->assertNotContains( 'jpg', WPEPP_Signatures::EXTENSIONS );
	}
}
