<?php
/**
 * Rate limiting — per-IP request counters with 404-flood auto-blocking.
 *
 * Counters live in the object cache (transient fallback) using one-minute
 * buckets — no DB writes on hot paths. When an IP exceeds the 404 budget
 * it is moved into wpepp_blocks as a temporary auto_404 block.
 *
 * @package wpepp
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Rate_Limit
 */
class WPEPP_Rate_Limit {

	/**
	 * Counter TTL (2 minutes covers the 60s bucket generously).
	 *
	 * @var int
	 */
	const BUCKET_TTL = 120;

	/**
	 * Get rate-limit settings with defaults.
	 *
	 * @return array
	 */
	private static function settings() {
		$raw      = get_option( 'wpepp_security_settings', '{}' );
		$settings = json_decode( (string) $raw, true );
		$settings = is_array( $settings ) ? $settings : [];

		return [
			'enabled'          => ! empty( $settings['rate_limit_enabled'] ),
			'views_per_min'    => max( 0, absint( $settings['rate_views_max'] ?? 0 ) ),      // 0 = off.
			'notfound_per_min' => max( 1, absint( $settings['rate_404_max'] ?? 30 ) ),
			'block_minutes'    => max( 5, absint( $settings['rate_block_minutes'] ?? 60 ) ),
		];
	}

	/**
	 * Bucket key for the current minute.
	 *
	 * @param string $kind 'view'|'404'.
	 * @return string
	 */
	private static function bucket_key( $kind, $ip ) {
		$minute = (int) ( time() / 60 );

		return sprintf( 'wpepp_rl_%s_%s_%d', $kind, md5( (string) $ip ), $minute );
	}

	/**
	 * Increment a counter and return the new value.
	 *
	 * @param string $kind Counter kind.
	 * @param string $ip   IP address.
	 * @return int
	 */
	private static function increment( $kind, $ip ) {
		$key = self::bucket_key( $kind, $ip );

		$value = wp_cache_get( $key, 'wpepp' );

		if ( false === $value ) {
			// Object-cache miss: fall back to transient so non-persistent
			// object caches still get short-lived counters.
			$value = get_transient( $key );
			if ( false === $value ) {
				$value = 0;
			}
		}

		$value = (int) $value + 1;

		wp_cache_set( $key, $value, 'wpepp', self::BUCKET_TTL );

		if ( ! wp_using_ext_object_cache() ) {
			set_transient( $key, $value, self::BUCKET_TTL );
		}

		return $value;
	}

	/**
	 * Register hooks. Called from WPEPP_Security::init().
	 */
	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'count_request' ], 999 );
		self::schedule_prune();
	}

	/**
	 * Count the current request (views + 404s) and enforce budgets.
	 */
	public static function count_request() {
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( is_admin() || is_user_logged_in() ) {
			return; // Only police anonymous front-end traffic.
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		// Emergency Safe Mode bypass
		if ( class_exists( 'WPEPP_Security' ) && WPEPP_Security::is_safe_mode() ) {
			return;
		}

		$s = self::settings();

		if ( ! $s['enabled'] ) {
			return;
		}

		$ip     = class_exists( 'WPEPP_Core_Ip' ) ? WPEPP_Core_Ip::get_client_ip() : '';
		$is_404 = is_404();

		if ( $is_404 && class_exists( 'WPEPP_Blocks' ) ) {
			$hits = self::increment( '404', $ip );

			if ( $hits >= $s['notfound_per_min'] && null === WPEPP_Blocks::find_active_block( $ip ) ) {
				self::auto_block_404( $ip, $hits, $s['block_minutes'] );
			}
			return;
		}

		// Views budget: 0 disables view throttling entirely.
		if ( $s['views_per_min'] > 0 ) {
			$views = self::increment( 'view', $ip );

			if ( $views >= $s['views_per_min'] ) {
				status_header( 429 );
				nocache_headers();
				header( 'Retry-After: 60' );
				wp_die(
					esc_html__( 'Too many requests. Please slow down.', 'wp-edit-password-protected' ),
					esc_html__( 'Rate Limited', 'wp-edit-password-protected' ),
					[ 'response' => 429 ]
				);
			}
		}
	}

	/**
	 * Move a 404 flooder into the block table.
	 *
	 * @param string $ip        Flooder IP.
	 * @param int    $hits      Hits recorded in the window.
	 * @param int    $minutes   Block duration.
	 */
	private static function auto_block_404( $ip, $hits, $minutes ) {
		// One block per IP per hour regardless of thresholds.
		$guard = 'wpepp_auto404_' . md5( (string) $ip );

		if ( get_transient( $guard ) ) {
			return;
		}

		set_transient( $guard, 1, HOUR_IN_SECONDS );

		WPEPP_Blocks::add_block(
			$ip,
			[
				'type'   => 'auto_404',
				'reason' => sprintf(
					/* translators: 1: hit count, 2: minutes blocked */
					__( 'Automatic block — %1$d not-found requests in under a minute.', 'wp-edit-password-protected' ),
					(int) $hits
				),
				'expires'=> time() + ( $minutes * MINUTE_IN_SECONDS ),
				'created_by' => 0,
			]
		);

		do_action( 'wpepp/ratelimit/auto_blocked', $ip, '404_flood', $hits ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public event API.
	}

	/**
	 * Daily cleanup of stray counter transients (non-persistent cache hosts).
	 */
	public static function schedule_prune() {
		if ( ! wp_next_scheduled( 'wpepp_prune_rl_buckets' ) ) {
			wp_schedule_event( time() + 6 * HOUR_IN_SECONDS, 'daily', 'wpepp_prune_rl_buckets' );
		}
		add_action( 'wpepp_prune_rl_buckets', [ __CLASS__, 'prune_stale_transients' ] );
	}

	/**
	 * Remove leftover rate-limit transients older than their TTL.
	 */
	public static function prune_stale_transients() {
		global $wpdb;

		if ( wp_using_ext_object_cache() ) {
			return;
		}

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_wpepp_rl_' ) . '%'
			)
		);

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_wpepp_rl_' ) . '%'
			)
		);
	}
}
