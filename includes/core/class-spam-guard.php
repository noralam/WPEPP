<?php
/**
 * WPEPP Spam Guard — Comprehensive Anti-Spam protection for WooCommerce, Comments, & Forms.
 *
 * @package WP_Edit_Password_Protected
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPEPP_Spam_Guard
 */
class WPEPP_Spam_Guard {

	/**
	 * Settings cache.
	 *
	 * @var array|null
	 */
	private static $settings = null;

	/**
	 * Retrieve cached security/anti-spam settings.
	 *
	 * @return array
	 */
	public static function settings() {
		if ( null === self::$settings ) {
			$raw            = get_option( 'wpepp_security_settings', '{}' );
			self::$settings = json_decode( $raw, true );
			if ( ! is_array( self::$settings ) ) {
				self::$settings = [];
			}
		}
		return self::$settings;
	}

	/**
	 * Initialize all anti-spam hooks.
	 *
	 * @param array|null $settings Optional settings array.
	 */
	public static function init( $settings = null ) {
		if ( is_array( $settings ) ) {
			self::$settings = $settings;
		} else {
			$settings = self::settings();
		}

		/* ─── WooCommerce Registration & Login Spam ─── */
		// Registration Honeypot
		if ( ! empty( $settings['wc_honeypot_enabled'] ?? true ) ) {
			add_action( 'woocommerce_register_form', [ __CLASS__, 'render_wc_honeypot' ] );
		}

		// Registration validation (Honeypot, Disposable Email, Rate Limiting, CAPTCHA)
		add_filter( 'woocommerce_process_registration_errors', [ __CLASS__, 'validate_wc_registration' ], 10, 4 );

		// WooCommerce CAPTCHA (Login & Register)
		if ( ! empty( $settings['wc_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
			add_action( 'woocommerce_register_form', [ 'WPEPP_Captcha', 'render_widget' ] );
			add_action( 'woocommerce_login_form', [ 'WPEPP_Captcha', 'render_widget' ] );
		}

		// WooCommerce Checkout spam filter
		if ( ! empty( $settings['wc_spam_protection_enabled'] ?? true ) ) {
			add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate_wc_checkout' ] );
		}

		/* ─── Comment Spam Protection ─── */
		if ( ! empty( $settings['comment_spam_protection_enabled'] ?? true ) ) {
			if ( ! empty( $settings['comment_honeypot_enabled'] ?? true ) ) {
				add_action( 'comment_form', [ __CLASS__, 'render_comment_honeypot' ] );
			}
			add_filter( 'preprocess_comment', [ __CLASS__, 'validate_comment' ] );

			if ( ! empty( $settings['comment_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
				add_action( 'comment_form', [ 'WPEPP_Captcha', 'render_widget' ] );
			}
		}

		/* ─── Lost Password Protection ─── */
		if ( ! empty( $settings['lostpassword_honeypot_enabled'] ?? true ) ) {
			add_action( 'lostpassword_form', [ __CLASS__, 'render_lostpassword_honeypot' ] );
		}

		if ( ! empty( $settings['lostpassword_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
			add_action( 'lostpassword_form', [ 'WPEPP_Captcha', 'render_widget' ] );
		}

		add_action( 'lostpassword_post', [ __CLASS__, 'validate_lostpassword' ] );
	}

	/* ─── WooCommerce Handlers ─── */

	/**
	 * Output hidden honeypot field in WooCommerce registration form.
	 */
	public static function render_wc_honeypot() {
		echo '<p class="wpepp-wc-hp" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;opacity:0;" aria-hidden="true">';
		echo '<label for="wpepp_wc_hp">' . esc_html__( 'Leave this field empty', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="wpepp_wc_hp" id="wpepp_wc_hp" value="" tabindex="-1" autocomplete="off" />';
		echo '</p>';
	}

	/**
	 * Validate WooCommerce registration submission against bot attacks.
	 *
	 * @param \WP_Error $errors   WooCommerce error object.
	 * @param string    $username Submitted username.
	 * @param string    $password Submitted password.
	 * @param string    $email    Submitted email.
	 * @return \WP_Error
	 */
	public static function validate_wc_registration( $errors, $username, $password, $email ) {
		$s = self::settings();

		// 1. Honeypot check
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $s['wc_honeypot_enabled'] ?? true ) && ( ! empty( $_POST['wpepp_wc_hp'] ) || ( isset( $_POST['wpepp_reg_hp'] ) && '' !== $_POST['wpepp_reg_hp'] ) ) ) {
			$errors->add(
				'wpepp_wc_honeypot',
				__( '<strong>Error:</strong> Automated bot registration detected and blocked.', 'wp-edit-password-protected' )
			);
			return $errors;
		}

		// 2. CAPTCHA verification (if enabled)
		if ( ! empty( $s['wc_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
			$captcha = WPEPP_Captcha::verify();
			if ( is_wp_error( $captcha ) ) {
				$errors->add( 'wpepp_wc_captcha', $captcha->get_error_message() );
				return $errors;
			}
		}

		// 3. Disposable email blocker (if enabled)
		if ( ! empty( $s['reg_block_disposable_emails'] ) || ! empty( $s['wc_disposable_email_block'] ?? true ) ) {
			if ( class_exists( 'WPEPP_Security' ) && is_email( $email ) ) {
				$domain             = strtolower( substr( strrchr( $email, '@' ), 1 ) );
				$disposable_domains = WPEPP_Security::get_disposable_email_domains();
				if ( in_array( $domain, $disposable_domains, true ) ) {
					$errors->add(
						'wpepp_wc_disposable_email',
						__( '<strong>Error:</strong> Registration with temporary or disposable email addresses is not allowed. Please use a permanent email.', 'wp-edit-password-protected' )
					);
					return $errors;
				}
			}
		}

		// 4. Rate limiting per IP
		if ( ! empty( $s['reg_rate_limit_enabled'] ) || ! empty( $s['wc_rate_limit_enabled'] ?? true ) ) {
			$ip            = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : ( class_exists( 'WPEPP_Security' ) ? WPEPP_Security::get_client_ip() : '0.0.0.0' );
			$transient_key = 'wpepp_wc_reg_' . md5( $ip );
			$attempts      = (int) get_transient( $transient_key );
			$max_regs      = absint( $s['reg_rate_limit_max'] ?? 3 );
			$window        = absint( $s['reg_rate_limit_window'] ?? 60 );

			if ( $attempts >= $max_regs ) {
				$errors->add(
					'wpepp_wc_rate_limit',
					__( '<strong>Error:</strong> Too many registration attempts from your IP. Please try again later.', 'wp-edit-password-protected' )
				);
				return $errors;
			}
			set_transient( $transient_key, $attempts + 1, $window * MINUTE_IN_SECONDS );
		}

		return $errors;
	}

	/**
	 * Validate WooCommerce checkout when creating a new account during order placement.
	 */
	public static function validate_wc_checkout() {
		// Only check if user is not logged in and creates an account during checkout.
		if ( is_user_logged_in() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['wpepp_wc_hp'] ) ) {
			wc_add_notice( __( 'Automated bot submission blocked.', 'wp-edit-password-protected' ), 'error' );
		}
	}

	/* ─── Comment Spam Handlers ─── */

	/**
	 * Output hidden honeypot field and timestamp token in WordPress comment form.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function render_comment_honeypot( $post_id = 0 ) {
		unset( $post_id );
		$time_token = time();
		echo '<p class="wpepp-comment-hp" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;opacity:0;" aria-hidden="true">';
		echo '<label for="wpepp_comment_hp">' . esc_html__( 'Leave empty', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="wpepp_comment_hp" id="wpepp_comment_hp" value="" tabindex="-1" autocomplete="off" />';
		echo '<input type="hidden" name="wpepp_comment_time" value="' . esc_attr( $time_token ) . '" />';
		echo '</p>';
	}

	/**
	 * Validate incoming comment before it is saved into database.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public static function validate_comment( $commentdata ) {
		// Admins and editors are exempt from spam checks.
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		$s = self::settings();

		// 1. Honeypot check
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $s['comment_honeypot_enabled'] ?? true ) && ! empty( $_POST['wpepp_comment_hp'] ) ) {
			wp_die(
				esc_html__( 'Spam detected: Comment blocked by security honeypot.', 'wp-edit-password-protected' ),
				esc_html__( 'Spam Blocked', 'wp-edit-password-protected' ),
				[ 'response' => 403 ]
			);
		}

		// 2. Time-gate check (Bots submit instantly in < 3 seconds; humans need more)
		if ( ! empty( $s['comment_timegate_enabled'] ?? true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token_time = isset( $_POST['wpepp_comment_time'] ) ? absint( $_POST['wpepp_comment_time'] ) : 0;
			if ( $token_time > 0 && ( time() - $token_time ) < 3 ) {
				wp_die(
					esc_html__( 'Comment submitted too quickly. Please take a moment to write your comment before submitting.', 'wp-edit-password-protected' ),
					esc_html__( 'Submission Too Fast', 'wp-edit-password-protected' ),
					[ 'response' => 429 ]
				);
			}
		}

		// 3. Comment CAPTCHA (if configured)
		if ( ! empty( $s['comment_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
			$captcha = WPEPP_Captcha::verify();
			if ( is_wp_error( $captcha ) ) {
				wp_die(
					esc_html( $captcha->get_error_message() ),
					esc_html__( 'CAPTCHA Failed', 'wp-edit-password-protected' ),
					[ 'response' => 403 ]
				);
			}
		}

		return $commentdata;
	}

	/* ─── Lost Password Handlers ─── */

	/**
	 * Output hidden honeypot on Lost Password screen.
	 */
	public static function render_lostpassword_honeypot() {
		echo '<p class="wpepp-lostpwd-hp" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;opacity:0;" aria-hidden="true">';
		echo '<label for="wpepp_lostpwd_hp">' . esc_html__( 'Leave empty', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="wpepp_lostpwd_hp" id="wpepp_lostpwd_hp" value="" tabindex="-1" autocomplete="off" />';
		echo '</p>';
	}

	/**
	 * Validate Lost Password form submissions.
	 *
	 * @param \WP_Error $errors Error object.
	 * @return \WP_Error
	 */
	public static function validate_lostpassword( $errors ) {
		$s = self::settings();

		// Honeypot
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $s['lostpassword_honeypot_enabled'] ?? true ) && ! empty( $_POST['wpepp_lostpwd_hp'] ) ) {
			$errors->add(
				'wpepp_lostpwd_hp',
				__( '<strong>Error:</strong> Password reset request blocked by anti-spam.', 'wp-edit-password-protected' )
			);
			return $errors;
		}

		// CAPTCHA
		if ( ! empty( $s['lostpassword_captcha_enabled'] ) && class_exists( 'WPEPP_Captcha' ) && '' !== WPEPP_Captcha::provider() ) {
			$captcha = WPEPP_Captcha::verify();
			if ( is_wp_error( $captcha ) ) {
				$errors->add( 'wpepp_lostpwd_captcha', $captcha->get_error_message() );
			}
		}

		return $errors;
	}
}
