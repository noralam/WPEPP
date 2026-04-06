<?php
/**
 * TOTP (Time-based One-Time Password) helper for Two-Factor Authentication.
 *
 * Implements RFC 6238 TOTP using HMAC-SHA1.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_TOTP
 */
class WPEPP_TOTP {

	/**
	 * Length of the generated secret (in bytes, before base32 encoding).
	 *
	 * @var int
	 */
	const SECRET_LENGTH = 20;

	/**
	 * Number of digits in the OTP code.
	 *
	 * @var int
	 */
	const CODE_DIGITS = 6;

	/**
	 * Time step in seconds (standard is 30).
	 *
	 * @var int
	 */
	const TIME_STEP = 30;

	/**
	 * Number of recovery codes to generate.
	 *
	 * @var int
	 */
	const RECOVERY_CODE_COUNT = 8;

	/**
	 * Base32 alphabet.
	 *
	 * @var string
	 */
	const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Generate a random TOTP secret key.
	 *
	 * @return string Base32-encoded secret.
	 */
	public static function generate_secret() {
		$random = wp_generate_password( self::SECRET_LENGTH, false, false );
		return self::base32_encode( $random );
	}

	/**
	 * Generate a TOTP code for a given secret and time.
	 *
	 * @param string   $secret    Base32-encoded secret.
	 * @param int|null $timestamp Unix timestamp (defaults to current time).
	 * @return string The 6-digit TOTP code.
	 */
	public static function generate_code( $secret, $timestamp = null ) {
		if ( null === $timestamp ) {
			$timestamp = time();
		}

		$time_counter = intdiv( $timestamp, self::TIME_STEP );
		$binary_time  = pack( 'N*', 0 ) . pack( 'N*', $time_counter );
		$secret_raw   = self::base32_decode( $secret );
		$hash         = hash_hmac( 'sha1', $binary_time, $secret_raw, true );

		// Dynamic truncation.
		$offset = ord( $hash[19] ) & 0x0F;
		$code   = (
			( ( ord( $hash[ $offset ] ) & 0x7F ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xFF ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xFF ) << 8 ) |
			( ord( $hash[ $offset + 3 ] ) & 0xFF )
		) % pow( 10, self::CODE_DIGITS );

		return str_pad( (string) $code, self::CODE_DIGITS, '0', STR_PAD_LEFT );
	}

	/**
	 * Verify a TOTP code against a secret.
	 *
	 * Allows a window of ±1 time step to account for clock drift.
	 *
	 * @param string $secret Base32-encoded secret.
	 * @param string $code   The 6-digit code to verify.
	 * @return bool True if valid.
	 */
	public static function verify_code( $secret, $code ) {
		$code = preg_replace( '/\s+/', '', $code );

		if ( strlen( $code ) !== self::CODE_DIGITS ) {
			return false;
		}

		$now = time();

		// Check current, previous, and next time steps.
		for ( $offset = -1; $offset <= 1; $offset++ ) {
			$check_time = $now + ( $offset * self::TIME_STEP );
			if ( hash_equals( self::generate_code( $secret, $check_time ), $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate a provisioning URI for QR code generation.
	 *
	 * @param string $secret Base32-encoded secret.
	 * @param string $email  User email or account name.
	 * @param string $issuer Site name / issuer.
	 * @return string otpauth:// URI.
	 */
	public static function get_provisioning_uri( $secret, $email, $issuer = '' ) {
		if ( empty( $issuer ) ) {
			$issuer = get_bloginfo( 'name' );
		}

		$label = rawurlencode( $issuer ) . ':' . rawurlencode( $email );

		return sprintf(
			'otpauth://totp/%s?secret=%s&issuer=%s&digits=%d&period=%d',
			$label,
			$secret,
			rawurlencode( $issuer ),
			self::CODE_DIGITS,
			self::TIME_STEP
		);
	}

	/**
	 * Generate a QR code URL using Google Charts API.
	 *
	 * @param string $provisioning_uri The otpauth:// URI.
	 * @param int    $size             QR code image size in pixels.
	 * @return string URL to QR code image.
	 */
	public static function get_qr_code_url( $provisioning_uri, $size = 200 ) {
		return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode( $provisioning_uri );
	}

	/**
	 * Generate recovery codes.
	 *
	 * @return array Array of recovery code strings.
	 */
	public static function generate_recovery_codes() {
		$codes = [];
		for ( $i = 0; $i < self::RECOVERY_CODE_COUNT; $i++ ) {
			$codes[] = strtolower( wp_generate_password( 4, false, false ) . '-' . wp_generate_password( 4, false, false ) );
		}
		return $codes;
	}

	/**
	 * Verify a recovery code for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $code    Recovery code to verify.
	 * @return bool True if valid (and consumed).
	 */
	public static function verify_recovery_code( $user_id, $code ) {
		$code  = sanitize_text_field( strtolower( trim( $code ) ) );
		$codes = get_user_meta( $user_id, '_wpepp_2fa_recovery_codes', true );

		if ( ! is_array( $codes ) || empty( $codes ) ) {
			return false;
		}

		$hashed_codes = $codes;
		foreach ( $hashed_codes as $index => $stored_hash ) {
			if ( wp_check_password( $code, $stored_hash ) ) {
				// Consume the code — remove it.
				unset( $codes[ $index ] );
				update_user_meta( $user_id, '_wpepp_2fa_recovery_codes', array_values( $codes ) );
				return true;
			}
		}

		return false;
	}

	/**
	 * Store hashed recovery codes for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $codes   Plain-text recovery codes.
	 */
	public static function store_recovery_codes( $user_id, $codes ) {
		$hashed = array_map( 'wp_hash_password', $codes );
		update_user_meta( $user_id, '_wpepp_2fa_recovery_codes', $hashed );
	}

	/* ─── Base32 helpers ─── */

	/**
	 * Base32 encode a string.
	 *
	 * @param string $data Raw data.
	 * @return string Base32-encoded string.
	 */
	private static function base32_encode( $data ) {
		$binary = '';
		foreach ( str_split( $data ) as $char ) {
			$binary .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		$encoded = '';
		$chunks  = str_split( $binary, 5 );
		foreach ( $chunks as $chunk ) {
			$chunk    = str_pad( $chunk, 5, '0', STR_PAD_RIGHT );
			$encoded .= self::BASE32_CHARS[ bindec( $chunk ) ];
		}

		return $encoded;
	}

	/**
	 * Base32 decode a string.
	 *
	 * @param string $data Base32-encoded string.
	 * @return string Raw data.
	 */
	private static function base32_decode( $data ) {
		$data   = strtoupper( $data );
		$binary = '';
		foreach ( str_split( $data ) as $char ) {
			$pos = strpos( self::BASE32_CHARS, $char );
			if ( false === $pos ) {
				continue;
			}
			$binary .= str_pad( decbin( $pos ), 5, '0', STR_PAD_LEFT );
		}

		$decoded = '';
		$bytes   = str_split( $binary, 8 );
		foreach ( $bytes as $byte ) {
			if ( strlen( $byte ) < 8 ) {
				continue;
			}
			$decoded .= chr( bindec( $byte ) );
		}

		return $decoded;
	}

	/* ─── User 2FA Setup ─── */

	/**
	 * Check if a user has 2FA enabled.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_user_enabled( $user_id ) {
		return '1' === get_user_meta( $user_id, '_wpepp_2fa_enabled', true );
	}

	/**
	 * Get a user's TOTP secret.
	 *
	 * @param int $user_id User ID.
	 * @return string|false Secret or false if not set.
	 */
	public static function get_user_secret( $user_id ) {
		$secret = get_user_meta( $user_id, '_wpepp_2fa_secret', true );
		return ! empty( $secret ) ? $secret : false;
	}

	/**
	 * Enable 2FA for a user with a verified secret.
	 *
	 * @param int    $user_id User ID.
	 * @param string $secret  Base32-encoded TOTP secret.
	 * @return array Recovery codes (plain text — show once).
	 */
	public static function enable_for_user( $user_id, $secret ) {
		update_user_meta( $user_id, '_wpepp_2fa_secret', $secret );
		update_user_meta( $user_id, '_wpepp_2fa_enabled', '1' );

		$codes = self::generate_recovery_codes();
		self::store_recovery_codes( $user_id, $codes );

		return $codes;
	}

	/**
	 * Disable 2FA for a user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function disable_for_user( $user_id ) {
		delete_user_meta( $user_id, '_wpepp_2fa_secret' );
		delete_user_meta( $user_id, '_wpepp_2fa_enabled' );
		delete_user_meta( $user_id, '_wpepp_2fa_recovery_codes' );
		delete_user_meta( $user_id, '_wpepp_2fa_pending_secret' );
	}

	/**
	 * Check if 2FA is required for a user based on their role.
	 *
	 * @param \WP_User $user     User object.
	 * @param array    $settings Security settings.
	 * @return bool
	 */
	public static function is_required_for_user( $user, $settings ) {
		if ( empty( $settings['two_factor_enabled'] ) ) {
			return false;
		}

		$roles = $settings['two_factor_roles'] ?? [ 'administrator' ];
		if ( empty( $roles ) ) {
			return false;
		}

		return ! empty( array_intersect( (array) $user->roles, (array) $roles ) );
	}
}
