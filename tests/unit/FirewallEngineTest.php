<?php
/**
 * Unit tests for the pure firewall engine (WPEPP_FW_Engine).
 *
 * @package wpepp
 */

use PHPUnit\Framework\TestCase;

/**
 * Class FirewallEngineTest
 */
class FirewallEngineTest extends TestCase {

	private const RULES = [
		[
			'id'       => 'sqli_union',
			'name'     => 'UNION SELECT',
			'action'   => 'block',
			'severity' => 3,
			'matchers' => [
				[ 'type' => 'query_regex', 'value' => '#\bunion\b.{0,40}\bselect\b#i' ],
			],
		],
		[
			'id'       => 'post_sqli',
			'name'     => 'POST SQLi',
			'action'   => 'block',
			'matchers' => [
				[ 'type' => 'method', 'value' => 'POST' ],
				[ 'type' => 'body_regex', 'name' => 'cmd', 'value' => '#eval\s*\(#i' ],
			],
		],
		[
			'id'       => 'sqlmap_ua',
			'name'     => 'sqlmap',
			'action'   => 'block',
			'matchers' => [ [ 'type' => 'ua_regex', 'value' => '#sqlmap#i' ] ],
		],
		[
			'id'       => 'cidr_block',
			'name'     => 'Bad range',
			'action'   => 'block',
			'matchers' => [ [ 'type' => 'ip_cidr', 'value' => '203.0.113.0/24|10.10.10.5' ] ],
		],
		[
			'id'       => 'log_only',
			'name'     => 'Log rule',
			'action'   => 'log',
			'matchers' => [ [ 'type' => 'path_regex', 'value' => '#/readme\.html$#' ] ],
		],
	];

	private function req( array $over = [] ) {
		return array_merge(
			[
				'method'       => 'GET',
				'path'         => '/shop/',
				'query_string' => '',
				'query'        => [],
				'body'         => [],
				'ua'           => 'Mozilla/5.0',
				'referer'      => '',
				'ip'           => '93.184.216.34',
			],
			$over
		);
	}

	public function test_no_match_clean_request() {
		$this->assertNull( WPEPP_FW_Engine::inspect( self::RULES, $this->req() ) );
	}

	public function test_union_select_in_query_matches() {
		$m = WPEPP_FW_Engine::inspect(
			self::RULES,
			$this->req( [ 'query_string' => 'id=1+union+select+password', 'query' => [ 'id' => '1 union select password' ] ] )
		);

		$this->assertNotNull( $m );
		$this->assertSame( 'sqli_union', $m['id'] );
	}

	public function test_post_rule_requires_method_and_param() {
		// Body hit but wrong method → no match (AND semantics).
		$this->assertNull(
			WPEPP_FW_Engine::inspect(
				self::RULES,
				$this->req( [ 'method' => 'GET', 'body' => [ 'cmd' => 'eval(base64_decode($x));' ] ] )
			)
		);

		$m = WPEPP_FW_Engine::inspect(
			self::RULES,
			$this->req( [ 'method' => 'POST', 'body' => [ 'cmd' => 'eval(base64_decode($x));' ] ] )
		);

		$this->assertSame( 'post_sqli', $m['id'] ?? '' );
	}

	public function test_ua_match() {
		$m = WPEPP_FW_Engine::inspect( self::RULES, $this->req( [ 'ua' => 'sqlmap/1.5.2#stable' ] ) );

		$this->assertSame( 'sqlmap_ua', $m['id'] ?? '' );
	}

	public function test_ip_cidr_single_and_range() {
		$m = WPEPP_FW_Engine::inspect( self::RULES, $this->req( [ 'ip' => '203.0.113.77' ] ) );
		$this->assertSame( 'cidr_block', $m['id'] ?? '', 'CIDR range member should match.' );

		$m = WPEPP_FW_Engine::inspect( self::RULES, $this->req( [ 'ip' => '10.10.10.5' ] ) );
		$this->assertSame( 'cidr_block', $m['id'] ?? '', 'Piped single IP should match.' );

		$this->assertNull( WPEPP_FW_Engine::inspect( self::RULES, $this->req( [ 'ip' => '10.10.10.6' ] ) ), 'Non-listed IP must not match.' );
	}

	public function test_first_match_wins_and_action_preserved() {
		$rules = array_merge(
			[ [ 'id' => 'first_log', 'action' => 'log', 'matchers' => [ [ 'type' => 'method', 'value' => 'GET' ] ] ] ],
			self::RULES
		);

		$m = WPEPP_FW_Engine::inspect( $rules, $this->req() );

		$this->assertSame( 'first_log', $m['id'] ?? '' );
		$this->assertSame( 'log', $m['action'] );
	}

	public function test_malformed_rules_skipped() {
		$rules = [
			'garbage',
			[ 'no_id' => true, 'matchers' => [] ],
			[ 'id' => 'empty_matchers', 'matchers' => [] ],
		];

		$this->assertNull( WPEPP_FW_Engine::inspect( $rules, $this->req() ) );
	}

	public function test_invalid_regex_never_matches() {
		$rules = [
			[ 'id' => 'bad_regex', 'action' => 'block', 'matchers' => [ [ 'type' => 'path_regex', 'value' => '#unbalanced(#i' ] ] ],
		];

		$this->assertNull( WPEPP_FW_Engine::inspect( $rules, $this->req() ) );
	}

	public function test_trailing_pipe_empty_branch_never_matches() {
		// Regression: '#a|#i' matches EVERYTHING via its empty branch.
		$rules = [
			[ 'id' => 'degenerate', 'action' => 'block', 'matchers' => [ [ 'type' => 'query_regex', 'value' => '#<script\\b|javascript:|#i' ] ] ],
		];

		$this->assertNull(
			WPEPP_FW_Engine::inspect( $rules, $this->req( [ 'query_string' => 'innocent=1', 'query' => [ 'innocent' => '1' ] ] ) ),
			'Degenerate empty-alternation patterns must be ignored.'
		);
	}

	public function test_regex_safe_rejects_empty_matchers() {
		$this->assertFalse( WPEPP_FW_Engine::regex_safe( '#abc|#i' ) );   // Empty branch.
		$this->assertTrue( WPEPP_FW_Engine::regex_safe( '#(abc|def)#i' ) ); // Proper group — fine.
		$this->assertFalse( WPEPP_FW_Engine::regex_safe( '#broken(#i' ) ); // Compile error.
	}

	public function test_regex_valid() {
		$this->assertTrue( WPEPP_FW_Engine::regex_safe( '#abc#i' ) );
		$this->assertFalse( WPEPP_FW_Engine::regex_safe( '#abc(#i' ) );
		$this->assertFalse( WPEPP_FW_Engine::regex_safe( '' ) );
	}

	public function test_capture_request_from_superglobals() {
		$req = WPEPP_FW_Engine::capture_request(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI'    => '/wp/admin.php?page=x&evil=<script>',
				'REMOTE_ADDR'    => '198.51.100.9',
				'HTTP_USER_AGENT'=> 'TestUA',
			],
			[ 'page' => 'x' ],
			[ 'pwd' => 'secret' ]
		);

		$this->assertSame( 'POST', $req['method'] );
		$this->assertSame( '/wp/admin.php', $req['path'] );
		$this->assertSame( 'page=x&evil=<script>', $req['query_string'] );
		$this->assertSame( 'x', $req['query']['page'] );
		$this->assertSame( 'secret', $req['body']['pwd'] );
		$this->assertSame( '198.51.100.9', $req['ip'] );
	}
}
