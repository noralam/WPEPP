<?php
/**
 * Password policy engine — strength rules + common-password blocklist
 * + breached-password check (k-anonymity, Pro tier).
 *
 * Enforcement points: registration, profile update (own + others),
 * password reset. Login is never blocked on policy — only on change.
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Password_Policy
 */
class WPEPP_Password_Policy {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'registration_errors', [ __CLASS__, 'enforce_registration' ], 20, 3 );
		add_action( 'user_profile_update_errors', [ __CLASS__, 'enforce_profile_update' ], 10, 3 );
		add_action( 'validate_password_reset', [ __CLASS__, 'enforce_reset' ], 10, 2 );
	}

	/**
	 * Effective policy settings with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$s        = json_decode( (string) $raw, true );
		$s        = is_array( $s ) ? $s : [];

		return [
			'enabled'       => ! empty( $s['pw_policy_enabled'] ),
			'min_length'    => max( 6, absint( $s['pw_min_length'] ?? 12 ) ),
			'require_mixed' => ! isset( $s['pw_require_mixed'] ) || ! empty( $s['pw_require_mixed'] ), // upper+lower+digit.
			'require_symbol'=> ! empty( $s['pw_require_symbol'] ),
			'block_common'  => ! isset( $s['pw_block_common'] ) || ! empty( $s['pw_block_common'] ),
			'breach_check'  => ! empty( $s['pw_breach_check'] ),
		];
	}

	/**
	 * Validate a candidate password against the policy.
	 *
	 * @param string $password Plain-text candidate.
	 * @return string[] Array of human-readable error messages (empty = pass).
	 */
	public static function validate( $password ) {
		$errors = [];
		$s      = self::settings();

		if ( ! $s['enabled'] ) {
			return $errors;
		}

		if ( strlen( (string) $password ) < $s['min_length'] ) {
			$errors[] = sprintf(
				/* translators: %d: minimum character count */
				__( 'Password must be at least %d characters long.', 'wp-edit-password-protected' ),
				$s['min_length']
			);
		}

		if ( $s['require_mixed'] ) {
			$has_lower = preg_match( '/[a-z]/', (string) $password );
			$has_upper = preg_match( '/[A-Z]/', (string) $password );
			$has_digit = preg_match( '/\d/', (string) $password );

			if ( ! ( $has_lower && $has_upper && $has_digit ) ) {
				$errors[] = __( 'Password must include uppercase letters, lowercase letters, and numbers.', 'wp-edit-password-protected' );
			}
		}

		if ( $s['require_symbol'] && ! preg_match( '/[^a-zA-Z0-9]/', (string) $password ) ) {
			$errors[] = __( 'Password must include at least one special character.', 'wp-edit-password-protected' );
		}

		if ( $s['block_common'] && self::is_common_password( $password ) ) {
			$errors[] = __( 'This password appears on public lists of compromised passwords and cannot be used.', 'wp-edit-password-protected' );
		}

		return $errors;
	}

	/* ─── Common passwords ─── */

	/**
	 * Check against the bundled common-password list (lazy-loaded).
	 *
	 * @param string $password Candidate.
	 * @return bool
	 */
	public static function is_common_password( $password ) {
		static $common = null;

		if ( null === $common ) {
			$file   = WPEPP_PATH . 'assets/passwords/common.txt';
			$common = [];

			if ( file_exists( $file ) ) {
				$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file -- local static asset.

				foreach ( (array) $lines as $line ) {
					if ( 0 !== strpos( $line, '#' ) ) {
						$common[ strtolower( trim( $line ) ) ] = true;
					}
				}
			}
		}

		// Also cover the obvious always-blocked set regardless of list size.
		$always = [
			'password', 'password1', 'password123', '12345678', '123456789',
			'qwerty123', 'letmein', 'welcome', 'admin123', 'iloveyou',
		];

		foreach ( $always as $a ) {
			$common[ $a ] = true;
		}

		return isset( $common[ strtolower( (string) $password ) ] );
	}

	/* ─── Breach check (k-anonymity range query) ─── */

	/**
	 * Whether the password appears in known breach corpora using the
	 * k-anonymity range model: only the first 5 chars of the SHA-1 hash are
	 * sent; matching happens locally against returned suffixes.
	 *
	 * Pro-tier feature — callers should gate via wpepp_feature_tier().
	 * Fails OPEN (allows the password) when the service is unreachable:
	 * availability beats strictness for a best-effort signal.
	 *
	 * @param string $password Plain-text candidate.
	 * @return array {breached:bool, count:int, skipped:string|null}
	 */
	public static function breach_check( $password ) {
		if ( '' === (string) $password || strlen( $password ) < 8 ) {
			return [ 'breached' => false, 'count' => 0, 'skipped' => null ];
		}

		$sha1 = strtoupper( sha1( $password ) );
		$prefix = substr( $sha1, 0, 5 );
		$suffix = substr( $sha1, 5 );

		$suffixes = self::fetch_range( $prefix );

		if ( null === $suffixes ) {
			return [ 'breached' => false, 'count' => 0, 'skipped' => 'service_unavailable' ];
		}

		$count = isset( $suffixes[ $suffix ] ) ? (int) $suffixes[ $suffix ] : 0;

		return [
			'breached' => $count > 0,
			'count'    => $count,
			'skipped'  => null,
		];
	}

	/**
	 * Fetch + cache one range response.
	 *
	 * @param string $prefix First five SHA-1 hex chars (uppercase).
	 * @return array<string,int>|null suffix=>count map, or null on failure.
	 */
	private static function fetch_range( $prefix ) {
		$cache_key = 'wpepp_breach_' . strtolower( $prefix );

		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null; // 'x' marks cached failure → null.
		}

		$response = wp_remote_get(
			'https://api.pwnedpasswords.com/range/' . rawurlencode( $prefix ),
			[
				'timeout'    => 4,
				'user-agent' => 'WPEPP/' . ( defined( 'WPEPP_VERSION' ) ? WPEPP_VERSION : '3.1' ),
				'headers'    => [ 'Add-Padding' => 'true' ], // Extra volume shielding.
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, 'fail', 15 * MINUTE_IN_SECONDS ); // Short negative cache.
			return null;
		}

		$map = [];

		foreach ( preg_split( '/\r\n|\r|\n/', (string) wp_remote_retrieve_body( $response ) ) as $line ) {
			$line = trim( $line );

			if ( '' === $line || false === strpos( $line, ':' ) ) {
				continue;
			}

			[ $hash_suffix, $hits ] = array_pad( explode( ':', $line, 2 ), 2, '0' );
			$map[ strtoupper( trim( $hash_suffix ) ) ] = (int) $hits;
		}

		set_transient( $cache_key, $map, DAY_IN_SECONDS );

		return $map;
	}

	/* ─── Hook handlers ─── */

	/**
	 * Shared enforcement used by all hooks.
	 *
	 * @param \WP_Error $errors  Error collector.
	 * @param string    $password Candidate password.
	 * @return \WP_Error
	 */
	private static function apply_policy( $errors, $password ) {
		foreach ( self::validate( $password ) as $message ) {
			$errors->add( 'wpepp_pw_policy', $message );
		}

		// Breach check — Pro tier only, runs after basic rules passed.
		if ( empty( $errors->get_error_codes() )
			&& 'pro' === wpepp_feature_tier( 'breach_check' )
			&& wpepp_has_pro_check()
			&& self::settings()['breach_check'] ) {

			$result = self::breach_check( $password );

			if ( $result['breached'] ) {
				$errors->add(
					'wpepp_pw_breached',
					sprintf(
						/* translators: %d: number of times seen in breach data */
						_n(
							'This password appears in breach data %d time and cannot be used.',
							'This password appears in breach data %d times and cannot be used.',
							max( 1, $result['count'] ),
							'wp-edit-password-protected'
						),
						$result['count']
					)
				);
			}
		}

		return $errors;
	}

	/**
	 * Registration form.
	 *
	 * @param \WP_Error $errors             Errors.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email         Email.
	 * @return \WP_Error
	 */
	public static function enforce_registration( $errors, $sanitized_user_login, $user_email ) {
		unset( $sanitized_user_login, $user_email );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP handles registration nonce.
		if ( empty( $_POST['user_login'] ) ) {
			return $errors; // Only enforce when a real registration submit happens.
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- raw passwords verified by WP core registration flow.
		$password = $_POST['pass1'] ?? $_POST['password'] ?? $_POST['user_password'] ?? '';

		return self::apply_policy( $errors, (string) wp_unslash( $password ) );
		// phpcs:enable
	}

	/**
	 * Profile update / user edit.
	 *
	 * @param \WP_Error $errors Error collector (by reference in WP core).
	 * @param bool      $update Whether this is an update (vs new insert).
	 * @param \stdClass $user   User object with submitted fields.
	 */
	public static function enforce_profile_update( &$errors, $update, $user ) {
		unset( $update );

		if ( empty( $user->user_pass ) ) {
			return; // No password change attempted.
		}

		self::apply_policy( $errors, (string) $user->user_pass );
	}

	/**
	 * Password reset flow.
	 *
	 * @param \WP_Error $errors Errors.
	 * @param \WP_User  $user   User resetting (or false-ish).
	 */
	public static function enforce_reset( $errors, $user ) {
		unset( $user );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- resetpass nonce handled by core before this hook.
		if ( ! isset( $_POST['pass1'] ) || '' === $_POST['pass1'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw password required, resetpass nonce handled by core.
		self::apply_policy( $errors, (string) wp_unslash( $_POST['pass1'] ) );
	}

	/**
	 * Checklist item for the security dashboard.
	 *
	 * @param array[] $items Existing items.
	 * @return array[]
	 */
	public static function score_item( $items ) {
		$s = self::settings();

		$items[] = [
			'id'       => 'pw_policy',
			'label'    => __( 'Password strength policy enforced', 'wp-edit-password-protected' ),
			'weight'   => 6,
			'category' => 'login',
			'done'     => $s['enabled'],
			'route'    => '/logins/passwords',
		];

		return $items;
	}
}
