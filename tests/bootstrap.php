<?php
/**
 * PHPUnit bootstrap — loads the core IP service with minimal WP shims so
 * pure logic (IP/CIDR math, header parsing) is testable without WordPress.
 *
 * @package wpepp
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
define( 'WPEPP_PATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'DB_NAME', 'test_db' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );

function wp_cache_flush_group( $group ) {
	return true;
}

// ── Minimal WordPress shims ─────────────────────────────────────────

$GLOBALS['wp_test_options']    = [];
$GLOBALS['wp_test_filters']    = [];
$GLOBALS['wp_test_object_cache'] = [];

function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
}

function wp_parse_url( $url, $component = -1 ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- this IS the wp_parse_url shim; runs before WP loads.
	return parse_url( $url, $component );
}

function wp_delete_file( $file ) {
	if ( file_exists( (string) $file ) ) {
		unlink( (string) $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- test shim; wp_delete_file unavailable pre-WP.
		return true;
	}
	return false;
}

function sanitize_text_field( $value ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- test shim runs before WP loads; wp_strip_all_tags() unavailable here.
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) );
}

function wp_salt( $scheme = 'auth' ) {
	return 'phpunit_mock_auth_salt_1234567890abcdefghijklmnopqrstuvwxyz';
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['wp_test_options'] ) ? $GLOBALS['wp_test_options'][ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['wp_test_options'][ $key ] = $value;
	return true;
}

function delete_option( $key ) {
	unset( $GLOBALS['wp_test_options'][ $key ] );
	return true;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $tag, $callback, $priority, $accepted_args );
}

$GLOBALS['wpdb'] = new class {
	public $prefix = 'wp_';
	public $row_to_return = null;
	public function get_row( $query = null, $output = 'OBJECT' ) {
		return $this->row_to_return;
	}
	public function get_results( $query = null, $output = 'OBJECT' ) {
		return [];
	}
	public function get_var( $query = null ) {
		if ( false !== strpos( (string) $query, 'SHOW TABLES LIKE' ) ) {
			return 'wp_wpepp_scanner_issues';
		}
		return null;
	}
	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}
	public function query( $query ) {
		return true;
	}
	public function prepare( $query, ...$args ) {
		return $query;
	}
	public function insert( $table, $data, $format = null ) {
		return 1;
	}
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		return 1;
	}
};
global $wpdb;
$wpdb = $GLOBALS['wpdb'];

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wp_test_filters'][ $tag ][] = [ $callback, $accepted_args ];
	return true;
}

function apply_filters( $tag, $value ) {
	if ( empty( $GLOBALS['wp_test_filters'][ $tag ] ) ) {
		return $value;
	}
	foreach ( $GLOBALS['wp_test_filters'][ $tag ] as $entry ) {
		[ $callback, $args ] = $entry;
		$value              = call_user_func( $callback, $value );
	}
	return $value;
}

function wp_cache_get( $key, $group = '' ) {
	return $GLOBALS['wp_test_object_cache'][ $group . '|' . $key ] ?? false;
}

function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) {
	$GLOBALS['wp_test_object_cache'][ $group . '|' . $key ] = $value;
	return true;
}

function wp_cache_delete( $key, $group = '' ) {
	unset( $GLOBALS['wp_test_object_cache'][ $group . '|' . $key ] );
	return true;
}

function home_url( $path = '' ) {
	return 'https://example.com' . $path;
}

function is_ssl() {
	return true;
}

function is_email( $email ) {
	return filter_var( (string) $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
}

function sanitize_email( $email ) {
	return is_email( $email ) ?: '';
}

function current_time( $type ) {
	return gmdate( 'Y-m-d H:i:s' );
}

function get_bloginfo( $show = '' ) {
	return 'Test Site';
}

function wp_specialchars_decode( $string, $quote_style = ENT_NOQUOTES ) {
	return htmlspecialchars_decode( $string, $quote_style );
}

function wp_get_theme() {
	return new class {
		public function get( $prop ) {
			return 'TestTheme';
		}
	};
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

function wp_get_upload_dir() {
	return [
		'basedir' => ABSPATH . 'wp-content/uploads',
		'baseurl' => 'https://example.com/wp-content/uploads',
	];
}

function has_action( $tag, $function_to_check = false ) {
	return ! empty( $GLOBALS['wp_test_filters'][ $tag ] );
}

function untrailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' );
}

function trailingslashit( $value ) {
	return untrailingslashit( $value ) . '/';
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function __( $text, $domain = 'default' ) {
	unset( $domain );
	return $text;
}

function wp_json_encode( $data, $flags = 0 ) {
	return json_encode( $data, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
}

function wp_is_writable( $path ) {
	return is_writable( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Test stub implementation.
}

function wp_normalize_path( $path ) {
	$path = str_replace( '\\\\', '/', (string) $path );
	return preg_replace( '|/+|', '/', strtolower( $path ) === $path ? $path : $path );
}

function do_action( $tag, ...$args ) {
	// Test shim: fire registered callbacks like apply_filters but no return.
	if ( empty( $GLOBALS['wp_test_filters'][ $tag ] ) ) {
		return;
	}
	foreach ( $GLOBALS['wp_test_filters'][ $tag ] as $entry ) {
		call_user_func_array( $entry[0], array_slice( $args, 0, max( 1, (int) $entry[1] ) ) );
	}
}

/**
 * Minimal WP_Error stand-in so plugin code paths run under PHPUnit.
 */
class WP_Error {

	public $errors = [];
	public $error_data = [];

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) {
			$this->add( $code, $message, $data );
		}
	}

	public function add( $code, $message, $data = '' ) {
		$this->errors[ $code ][] = $message;
		if ( ! empty( $data ) ) {
			$this->error_data[ $code ] = $data;
		}
	}

	public function has_errors() {
		return ! empty( $this->errors );
	}

	public function get_error_code() {
		$codes = array_keys( $this->errors );
		return empty( $codes ) ? '' : $codes[0];
	}

	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}

	public function get_error_codes() {
		return array_keys( $this->errors );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof WP_Error );
	}
}

if ( ! function_exists( 'sanitize_user' ) ) {
	function sanitize_user( $username, $strict = false ) {
		return preg_replace( '|[^a-zA-Z0-9 _.\-@]|i', '', (string) $username );
	}
}

if ( ! function_exists( 'wp_login_url' ) ) {
	function wp_login_url( $redirect = '', $force_reauth = false ) {
		return 'https://example.com/wp-login.php';
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron() {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return false;
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302 ) {
		return true;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		return 'https://example.com/test';
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['wp_test_options'][ '_transient_' . $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['wp_test_options'][ '_transient_' . $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['wp_test_options'][ '_transient_' . $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_hash' ) ) {
	function wp_hash( $data, $scheme = 'auth' ) {
		return md5( (string) $data . 'test_salt' );
	}
}

if ( ! function_exists( 'wp_hash_password' ) ) {
	function wp_hash_password( $password ) {
		return md5( (string) $password . 'test_hash_salt' );
	}
}

if ( ! function_exists( 'wp_check_password' ) ) {
	function wp_check_password( $password, $hash, $user_id = '' ) {
		return wp_hash( $password ) === $hash || $password === $hash;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return ! empty( $GLOBALS['wp_test_current_user_can_' . $capability] );
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries = '', $execute = true ) {
		return [];
	}
}

if ( ! function_exists( 'wp_roles' ) ) {
	function wp_roles() {
		return new class {
			public function get_names() {
				return [
					'administrator' => 'Administrator',
					'editor'        => 'Editor',
					'author'        => 'Author',
					'contributor'   => 'Contributor',
					'subscriber'    => 'Subscriber',
				];
			}
			public function is_role( $role ) {
				return in_array( $role, [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ], true );
			}
		};
	}
}

if ( ! function_exists( 'get_users' ) ) {
	function get_users( $args = [] ) {
		return [];
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars ) {
			$chars .= '!@#$%^&*()';
		}
		if ( $extra_special_chars ) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}
		$password = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}
		return $password;
	}
}

if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( $min = 0, $max = 0 ) {
		if ( 0 === $max ) {
			$max = mt_getrandmax();
		}
		return random_int( $min, $max );
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key = '', $single = false ) {
		$meta = $GLOBALS['wp_test_user_meta'][ $user_id ] ?? [];
		if ( '' === $key ) {
			return $meta;
		}
		if ( ! array_key_exists( $key, $meta ) ) {
			return $single ? '' : [];
		}
		return $single ? $meta[ $key ] : [ $meta[ $key ] ];
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $key, $value, $prev_value = '' ) {
		$GLOBALS['wp_test_user_meta'][ $user_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_user_meta' ) ) {
	function delete_user_meta( $user_id, $key, $value = '' ) {
		unset( $GLOBALS['wp_test_user_meta'][ $user_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = 'name' ) {
		return 'Test WordPress Site';
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return ! empty( $GLOBALS['wp_test_logged_in_user_id'] );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return $GLOBALS['wp_test_logged_in_user_id'] ?? 0;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		$u = new \stdClass();
		$u->ID = get_current_user_id();
		$u->user_email = 'test@example.com';
		$u->roles = [ 'administrator' ];
		return $u;
	}
}

require_once __DIR__ . '/../includes/core/class-ip.php';
require_once __DIR__ . '/../includes/core/class-fw-engine.php';
require_once __DIR__ . '/../includes/core/class-firewall.php';
require_once __DIR__ . '/../includes/core/class-blocks.php';
require_once __DIR__ . '/../includes/core/class-password-policy.php';
require_once __DIR__ . '/../includes/core/class-alerts.php';
require_once __DIR__ . '/../includes/core/class-spam-guard.php';
require_once __DIR__ . '/../includes/hardening/class-file-hardening.php';
require_once __DIR__ . '/../includes/scanner/class-signatures.php';
require_once __DIR__ . '/../includes/dashboard/class-score.php';
require_once __DIR__ . '/../includes/class-security.php';
require_once __DIR__ . '/../includes/class-site-access.php';

/**
 * Test double for the pro gate — controllable per test.
 */
$GLOBALS['wp_test_has_pro'] = false;

function wpepp_has_pro_check() {
	return (bool) $GLOBALS['wp_test_has_pro'];
}

function wpepp_feature_tier( $feature ) {
	if ( isset( $GLOBALS['wp_test_feature_tier'][ $feature ] ) ) {
		return $GLOBALS['wp_test_feature_tier'][ $feature ];
	}
	$free_features = [
		'login_limiter', 'honeypot', 'two_factor_totp', 'custom_login_url',
		'username_shield', 'security_headers', 'ai_crawler_blocker',
		'integrity_scan', 'malware_basic',
	];
	return in_array( $feature, $free_features, true ) ? 'free' : 'pro';
}

require_once __DIR__ . '/../includes/class-pro.php';
