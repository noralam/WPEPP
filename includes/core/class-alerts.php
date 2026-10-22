<?php
/**
 * Email alerts — configurable matrix with rate guards.
 *
 * Free tier:  lockouts, scan criticals.
 * Pro tier:   admin logins, failed-admin bursts, firewall block bursts,
 *             weekly activity report.
 *
 * Every alert is rate-limited (one email per key per guard window) so a
 * flood of events can never turn into a mail flood.
 *
 * @package wpepp
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Alerts
 */
class WPEPP_Alerts {

	/**
	 * Boot — subscribe to event sources.
	 */
	public static function boot() {
		// Lockout issued (free).
		add_action( 'wpepp/unlock/consumed', '__return_null' ); // no-op keeps hook list symmetric.
		add_action( 'init', [ __CLASS__, 'subscribe_lockouts' ], 20 );

		// Scan finished with criticals (free).
		add_action( 'wpepp/scan/finished', [ __CLASS__, 'on_scan_finished' ] );

		// Admin login (pro).
		add_action( 'wp_login', [ __CLASS__, 'on_login' ], 10, 2 );

		// Firewall blocked match burst (pro).
		add_action( 'wpepp/firewall/match', [ __CLASS__, 'on_firewall_match' ], 10, 3 );

		// Weekly report.
		self::schedule_weekly();
	}

	private static function settings() {
		$s = json_decode( (string) get_option( 'wpepp_security_settings', '{}' ), true );
		return is_array( $s ) ? $s : [];
	}

	private static function recipient() {
		$s = self::settings();
		return sanitize_email( $s['alert_email'] ?? '' ) ?: get_option( 'admin_email' );
	}

	private static function enabled_master() {
		$s = self::settings();
		return ! isset( $s['alerts_enabled'] ) || ! empty( $s['alerts_enabled'] );
	}

	/**
	 * Whether a specific alert may send now (master + per-type + pro gate).
	 *
	 * @param string $key Alert key.
	 * @return bool
	 */
	private static function can_send( $key ) {
		if ( ! self::enabled_master() ) {
			return false;
		}

		$free_keys   = [ 'lockout', 'scan_criticals' ];
		$is_pro_only = ! in_array( $key, $free_keys, true );

		if ( $is_pro_only && ( ! function_exists( 'wpepp_has_pro_check' ) || ! wpepp_has_pro_check() ) ) {
			return false;
		}

		$s = self::settings();

		return ! empty( $s[ 'alert_' . $key ] );
	}

	/**
	 * Rate guard — true when this exact alert may fire once per window.
	 *
	 * @param string $guard_key Unique key.
	 * @param int    $seconds   Window.
	 * @return bool
	 */
	private static function pass_guard( $guard_key, $seconds ) {
		$key = 'wpepp_alert_guard_' . md5( $guard_key );

		if ( get_transient( $key ) ) {
			return false;
		}

		set_transient( $key, 1, $seconds );

		return true;
	}

	/**
	 * Send a styled-ish plain-text email + log the fact.
	 *
	 * @param string $subject Subject line (site prefix added).
	 * @param string $body    Plain text body.
	 * @param string $log_ctx Context for the alert_sent event.
	 */
	private static function send( $subject, $body, $log_ctx = [] ) {
		$to      = self::recipient();
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$headers = [ 'Content-Type: text/plain; charset=utf-8' ];

		$ok = wp_mail( $to, sprintf( '[%s] %s', $site, $subject ), $body, $headers );

		WPEPP_Logger::log(
			'alert_sent',
			sprintf( 'Alert "%s" %s', $subject, $ok ? 'sent to ' . $to : 'failed' ),
			WPEPP_Logger::SEV_INFO,
			array_merge( [ 'ok' => $ok ? 'yes' : 'no' ], $log_ctx )
		);
	}

	/* ─── Source handlers ─── */

	/**
	 * Lockout emails — hook into limiter via lockout transient marker.
	 */
	public static function subscribe_lockouts() {
		add_action( 'wpepp/ratelimit/auto_blocked', [ __CLASS__, 'on_auto_block' ], 10, 3 );
	}

	/**
	 * Called by WPEPP_Security::log_lockout_once path through filter below.
	 */
	public static function on_lockout( $ip, $username = '' ) {
		if ( ! self::can_send( 'lockout' ) || ! self::pass_guard( 'lockout_' . md5( (string) $ip ), 30 * MINUTE_IN_SECONDS ) ) {
			return;
		}

		self::send(
			__( 'Login lockout issued', 'wp-edit-password-protected' ),
			sprintf(
				/* translators: 1: IP, 2: username attempted, 3: site URL */
				__( "Too many failed login attempts triggered a lockout.\n\nIP: %1\$s\nUsername tried: %2\$s\nSite: %3\$s\n\nManage blocks: %4\$s", 'wp-edit-password-protected' ),
				(string) $ip,
				$username ?: '(unknown)',
				home_url(),
				admin_url( 'admin.php?page=wpepp-settings#/blocks/list' )
			),
			[ 'ip' => (string) $ip ]
		);
	}

	/**
	 * Auto 404-flood block notice (free — same tier as lockout).
	 *
	 * @param string $ip     Blocked IP.
	 * @param string $reason Reason slug.
	 * @param int    $hits   Hit count.
	 */
	public static function on_auto_block( $ip, $reason, $hits ) {
		WPEPP_Logger::log(
			'block_auto',
			sprintf( 'Auto-blocked %s (%s, %d hits)', (string) $ip, (string) $reason, (int) $hits ),
			WPEPP_Logger::SEV_WARN,
			[ 'reason' => (string) $reason ]
		);

		if ( ! self::can_send( 'lockout' ) || ! self::pass_guard( 'auto404_' . md5( (string) $ip ), HOUR_IN_SECONDS ) ) {
			return;
		}

		self::send(
			__( 'Scanner IP auto-blocked (404 flood)', 'wp-edit-password-protected' ),
			sprintf(
				/* translators: 1: IP, 2: hits */
				__( "An IP was automatically blocked after excessive not-found requests.\n\nIP: %1\$s\n404 hits: %2\$d", 'wp-edit-password-protected' ),
				(string) $ip,
				(int) $hits
			)
		);
	}

	/**
	 * Scan finished — alert on new critical findings (free).
	 *
	 * @param array $state Final scan state.
	 */
	public static function on_scan_finished( $state ) {
		$flagged = (int) ( $state['flagged'] ?? 0 );

		WPEPP_Logger::log(
			'scan_finished',
			sprintf( 'Scan finished — %d new findings', $flagged ),
			$flagged > 0 ? WPEPP_Logger::SEV_WARN : WPEPP_Logger::SEV_INFO,
			[ 'profile' => (string) ( $state['profile'] ?? '' ) ]
		);

		if ( $flagged <= 0 || ! self::can_send( 'scan_criticals' ) || ! self::pass_guard( 'scan_criticals', 6 * HOUR_IN_SECONDS ) ) {
			return;
		}

		self::send(
			sprintf(
				/* translators: %d: finding count */
				_n( '%d new security finding', '%d new security findings', $flagged, 'wp-edit-password-protected' ),
				$flagged
			),
			sprintf(
				/* translators: 1: count, 2: review URL */
				__( "A security scan finished with %1\$d new findings.\n\nReview them here:\n%2\$s", 'wp-edit-password-protected' ),
				$flagged,
				admin_url( 'admin.php?page=wpepp-settings#/scanner' )
			)
		);
	}

	/**
	 * Admin login notice (pro).
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 */
	public static function on_login( $user_login, $user ) {
		unset( $user_login );

		WPEPP_Logger::log(
			'login_success',
			sprintf( 'Logged in: %s', $user->user_login ),
			WPEPP_Logger::SEV_INFO,
			[ 'roles' => implode( ',', (array) $user->roles ) ]
		);

		if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
			return;
		}

		if ( ! self::can_send( 'admin_login' ) || ! self::pass_guard( 'adminlogin_' . $user->ID, 15 * MINUTE_IN_SECONDS ) ) {
			return;
		}

		$ip = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : '';

		self::send(
			__( 'Administrator login', 'wp-edit-password-protected' ),
			sprintf(
				/* translators: 1: username, 2: IP */
				__( "An administrator signed in.\n\nUser: %1\$s\nIP: %2\$s", 'wp-edit-password-protected' ),
				$user->user_login,
				(string) $ip
			),
			[ 'user' => $user->user_login ]
		);
	}

	/**
	 * Firewall matches — burst digest (pro): max one email per hour when
	 * more than N blocks happened inside that hour.
	 *
	 * @param array $rule    Matched rule.
	 * @param array $request Request data.
	 * @param bool  $blocked Blocked or logged-only.
	 */
	public static function on_firewall_match( $rule, $request, $blocked ) {
		if ( ! $blocked ) {
			return;
		}

		WPEPP_Logger::log(
			'firewall_blocked',
			sprintf( 'Firewall blocked request (%s)', (string) ( $rule['id'] ?? '?' ) ),
			WPEPP_Logger::SEV_WARN,
			[ 'rule' => (string) ( $rule['id'] ?? '' ), 'path' => substr( (string) ( $request['path'] ?? '' ), 0, 120 ) ]
		);

		if ( ! self::can_send( 'firewall_burst' ) ) {
			return;
		}

		// Count blocked events in the last hour from logger stats cache-free.
		global $wpdb;

		if ( ! WPEPP_Logger::table_exists() ) {
			return;
		}

		$table = WPEPP_Logger::table_name();
		$since = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE event_type = 'firewall_blocked' AND created_at >= %s",
				$table,
				$since
			)
		);

		$threshold = max( 5, absint( self::settings()['alert_firewall_burst_min'] ?? 25 ) );

		if ( $count < $threshold || ! self::pass_guard( 'fwburst', HOUR_IN_SECONDS ) ) {
			return;
		}

		self::send(
			__( 'Firewall blocked multiple requests', 'wp-edit-password-protected' ),
			sprintf(
				/* translators: 1: count, 2: threshold */
				__( "The firewall blocked %1\$d requests in the past hour (alert threshold: %2\$d).\n\nRecent matches page: %3\$s", 'wp-edit-password-protected' ),
				$count,
				$threshold,
				admin_url( 'admin.php?page=wpepp-settings#/firewall' )
			)
		);
	}

	/* ─── Weekly report ─── */

	public static function schedule_weekly() {
		// 'weekly' is not a core recurrence — register it ourselves.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_weekly_schedule' ] );

		if ( ! wp_next_scheduled( 'wpepp_weekly_report' ) ) {
			wp_schedule_event( strtotime( 'next monday 09:00' ), 'weekly', 'wpepp_weekly_report' );
		}
		add_action( 'wpepp_weekly_report', [ __CLASS__, 'send_weekly_report' ] );
	}

	/**
	 * Register the 'weekly' cron recurrence.
	 *
	 * @param array $schedules Core + plugin schedules.
	 * @return array
	 */
	public static function add_weekly_schedule( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = [
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'wp-edit-password-protected' ),
			];
		}

		return $schedules;
	}

	/**
	 * Build + send the weekly activity report (pro).
	 */
	public static function send_weekly_report() {
		if ( ! self::can_send( 'weekly_report' ) ) {
			return;
		}

		$stats = WPEPP_Logger::stats( 7 );

		$logins_ok = (int) ( $stats['by_type']['login_success'] ?? 0 );
		$logins_no = (int) ( $stats['by_type']['login_failed'] ?? 0 );
		$lockouts  = (int) ( $stats['by_type']['lockout'] ?? 0 );
		$blocked   = (int) ( $stats['by_type']['firewall_blocked'] ?? 0 ) + (int) ( $stats['by_type']['block_auto'] ?? 0 );

		// Blocks by type from blocks table.
		global $wpdb;
		$by_type = [];

		if ( class_exists( 'WPEPP_Blocks' ) && WPEPP_Blocks::table_exists() ) {
			$table = WPEPP_Blocks::table_name();

			foreach (
				$wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						'SELECT type, COUNT(*) AS n FROM %i WHERE created_at >= %s GROUP BY type',
						$table,
						gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS )
					),
					ARRAY_A
				) as $row
			) {
				$by_type[ $row['type'] ] = (int) $row['n'];
			}
		}

		$score = class_exists( 'WPEPP_Security_Score' ) ? WPEPP_Security_Score::get_summary() : null;

		$body = __( "Weekly security summary\n========================\n\n", 'wp-edit-password-protected' );

		/* translators: 1: number of successful logins, 2: number of failed logins */
		$body .= sprintf( __( "Logins: %1\$d successful · %2\$d failed\n", 'wp-edit-password-protected' ), $logins_ok, $logins_no );
		/* translators: %d: number of lockouts issued */
		$body .= sprintf( __( "Lockouts issued: %d\n", 'wp-edit-password-protected' ), $lockouts );
		/* translators: %d: number of blocked requests */
		$body .= sprintf( __( "Requests blocked: %d\n", 'wp-edit-password-protected' ), $blocked );

		if ( $by_type ) {
			$body .= "\n" . __( "New blocks by type:\n", 'wp-edit-password-protected' );
			foreach ( $by_type as $type => $n ) {
				$body .= sprintf( "  %-10s %d\n", $type, $n );
			}
		}

		if ( $score ) {
			$body .= "\n" . sprintf(
				/* translators: 1: score, 2: grade */
				__( 'Security score: %1$d / 100 (grade %2$s)', 'wp-edit-password-protected' ),
				$score['score'],
				$score['grade']
			) . "\n";
		}

		$body .= "\n" . admin_url( 'admin.php?page=wpepp-settings#/activity' ) . "\n";

		self::send( __( 'Weekly activity report', 'wp-edit-password-protected' ), $body );
	}

	/**
	 * Detect if an active SMTP plugin or custom SMTP configuration is present.
	 *
	 * @return array
	 */
	public static function detect_smtp() {
		$configured = false;
		$provider   = __( 'Default PHP mail()', 'wp-edit-password-protected' );
		$details    = '';

		if ( class_exists( 'WPMailSMTP\Core' ) || class_exists( 'WP_Mail_SMTP' ) ) {
			$configured = true;
			$provider   = 'WP Mail SMTP';
		} elseif ( class_exists( 'FluentMail\App\App' ) || class_exists( 'FluentMail' ) ) {
			$configured = true;
			$provider   = 'FluentSMTP';
		} elseif ( class_exists( 'Postman' ) || class_exists( 'PostSMTP' ) ) {
			$configured = true;
			$provider   = 'Post SMTP';
		} elseif ( class_exists( 'EasyWPSMTP' ) || class_exists( 'EasyWPData' ) ) {
			$configured = true;
			$provider   = 'Easy WP SMTP';
		} elseif ( class_exists( 'SMTP_Mailer' ) ) {
			$configured = true;
			$provider   = 'SMTP Mailer';
		} elseif ( defined( 'SMTP_HOST' ) || defined( 'WP_MAIL_SMTP_HOST' ) ) {
			$configured = true;
			$provider   = 'Custom SMTP (Constant)';
		} elseif ( has_action( 'phpmailer_init' ) ) {
			$configured = true;
			$provider   = 'Custom SMTP (phpmailer_init hook)';
		}

		return [
			'configured' => $configured,
			'provider'   => $provider,
			'details'    => $details,
		];
	}

	/**
	 * Send a test security alert to verify delivery.
	 *
	 * @param string $recipient Optional custom recipient email.
	 * @return array|\WP_Error
	 */
	public static function send_test_alert( $recipient = '' ) {
		$to = $recipient ? sanitize_email( $recipient ) : self::recipient();
		if ( ! is_email( $to ) ) {
			return new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'wp-edit-password-protected' ) );
		}

		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf( '[%s] %s', $site, __( 'Security Alert Delivery Test', 'wp-edit-password-protected' ) );
		$smtp    = self::detect_smtp();

		$body = sprintf(
			/* translators: 1: site name, 2: current time, 3: recipient email, 4: SMTP provider */
			__( "Hello,\n\nThis is a test security alert from %1\$s powered by WP Edit Password Protected.\n\nTime: %2\$s\nRecipient: %3\$s\nSMTP Provider: %4\$s\n\nIf you received this message, your site security alerts and email delivery are functioning properly.", 'wp-edit-password-protected' ),
			$site,
			current_time( 'mysql' ),
			$to,
			$smtp['provider']
		);

		$headers = [ 'Content-Type: text/plain; charset=utf-8' ];
		$ok      = wp_mail( $to, $subject, $body, $headers );

		if ( ! $ok ) {
			return new \WP_Error(
				'mail_failed',
				__( 'wp_mail() returned false. Please verify your SMTP settings, mail logs, or server mail configuration.', 'wp-edit-password-protected' )
			);
		}

		WPEPP_Logger::log(
			'alert_sent',
			sprintf( 'Test security alert sent to %s', $to ),
			WPEPP_Logger::SEV_INFO,
			[ 'ok' => 'yes', 'recipient' => $to, 'type' => 'test' ]
		);

		return [
			'success'   => true,
			'recipient' => $to,
			'provider'  => $smtp['provider'],
		];
	}
}
