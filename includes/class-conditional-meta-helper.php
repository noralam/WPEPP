<?php
/**
 * Conditional display — server-side condition evaluator.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Conditional_Meta_Helper
 */
class WPEPP_Conditional_Meta_Helper {

	/**
	 * Evaluate the condition for a given post.
	 *
	 * Returns true if the condition is met, false otherwise.
	 * Client-side conditions (browser_type, referrer_source) always return true
	 * server-side — they are handled by JS.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function evaluate_condition( $post_id ) {
		$condition = get_post_meta( $post_id, '_wpepp_conditional_display_condition', true );

		switch ( $condition ) {
			case 'user_logged_in':
				return is_user_logged_in();

			case 'user_logged_out':
				return ! is_user_logged_in();

			case 'user_role':
				return self::check_user_role( $post_id );

			case 'device_type':
				return self::check_device_type( $post_id );

			case 'day_of_week':
				return self::check_day_of_week( $post_id );

			case 'time_of_day':
				return self::check_time_of_day( $post_id );

			case 'date_range':
				return self::check_date_range( $post_id );

			case 'recurring_schedule':
				return self::check_recurring_schedule( $post_id );

			case 'post_type':
				return self::check_post_type( $post_id );

			case 'url_parameter':
				return self::check_url_parameter( $post_id );

			// Client-side conditions — always true server-side, JS handles visibility.
			case 'browser_type':
			case 'referrer_source':
				return true;

			default:
				return true;
		}
	}

	/**
	 * Check user role condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_user_role( $post_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$allowed_roles = get_post_meta( $post_id, '_wpepp_conditional_user_role', true );
		if ( ! is_array( $allowed_roles ) || empty( $allowed_roles ) ) {
			return true;
		}

		$user  = wp_get_current_user();
		$match = array_intersect( $user->roles, $allowed_roles );

		return ! empty( $match );
	}

	/**
	 * Check device type condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_device_type( $post_id ) {
		$device = get_post_meta( $post_id, '_wpepp_conditional_device_type', true );

		if ( 'mobile' === $device ) {
			return wp_is_mobile();
		}

		if ( 'desktop' === $device ) {
			return ! wp_is_mobile();
		}

		// Tablet detection is limited — wp_is_mobile() includes tablets.
		return true;
	}

	/**
	 * Check day of week condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_day_of_week( $post_id ) {
		$days = get_post_meta( $post_id, '_wpepp_conditional_day_of_week', true );
		if ( ! is_array( $days ) || empty( $days ) ) {
			return true;
		}

		$today = strtolower( wp_date( 'l' ) );
		return in_array( $today, $days, true );
	}

	/**
	 * Check time of day condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_time_of_day( $post_id ) {
		$start = get_post_meta( $post_id, '_wpepp_conditional_time_start', true );
		$end   = get_post_meta( $post_id, '_wpepp_conditional_time_end', true );

		if ( empty( $start ) || empty( $end ) ) {
			return true;
		}

		$now = wp_date( 'H:i' );
		return ( $now >= $start && $now <= $end );
	}

	/**
	 * Check date range condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_date_range( $post_id ) {
		$start = get_post_meta( $post_id, '_wpepp_conditional_date_start', true );
		$end   = get_post_meta( $post_id, '_wpepp_conditional_date_end', true );

		if ( empty( $start ) && empty( $end ) ) {
			return true;
		}

		$today = wp_date( 'Y-m-d' );

		if ( ! empty( $start ) && $today < $start ) {
			return false;
		}

		if ( ! empty( $end ) && $today > $end ) {
			return false;
		}

		return true;
	}

	/**
	 * Check recurring schedule condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_recurring_schedule( $post_id ) {
		$recurring_days  = get_post_meta( $post_id, '_wpepp_conditional_recurring_days', true );
		$recurring_start = get_post_meta( $post_id, '_wpepp_conditional_recurring_time_start', true );
		$recurring_end   = get_post_meta( $post_id, '_wpepp_conditional_recurring_time_end', true );

		if ( ! is_array( $recurring_days ) || empty( $recurring_days ) ) {
			return true;
		}

		$today = strtolower( wp_date( 'l' ) );
		if ( ! in_array( $today, $recurring_days, true ) ) {
			return false;
		}

		if ( ! empty( $recurring_start ) && ! empty( $recurring_end ) ) {
			$now = wp_date( 'H:i' );
			return ( $now >= $recurring_start && $now <= $recurring_end );
		}

		return true;
	}

	/**
	 * Check post type condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_post_type( $post_id ) {
		$expected = get_post_meta( $post_id, '_wpepp_conditional_post_type', true );
		if ( empty( $expected ) ) {
			return true;
		}

		return get_post_type( $post_id ) === $expected;
	}

	/**
	 * Check URL parameter condition.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function check_url_parameter( $post_id ) {
		$key   = get_post_meta( $post_id, '_wpepp_conditional_url_parameter_key', true );
		$value = get_post_meta( $post_id, '_wpepp_conditional_url_parameter_value', true );

		if ( empty( $key ) ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only condition check.
		$param = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : null;

		if ( null === $param ) {
			return false;
		}

		// If value is empty, just check parameter existence.
		if ( empty( $value ) ) {
			return true;
		}

		return $param === $value;
	}

	/**
	 * Get client-side condition data for JS localization.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_client_condition_data( $post_id ) {
		$condition = get_post_meta( $post_id, '_wpepp_conditional_display_condition', true );

		switch ( $condition ) {
			case 'browser_type':
				$browsers = get_post_meta( $post_id, '_wpepp_conditional_browser_type', true );
				return [
					'browsers' => is_array( $browsers ) ? array_map( 'sanitize_text_field', $browsers ) : [],
				];

			case 'referrer_source':
				return [
					'referrer' => sanitize_text_field( get_post_meta( $post_id, '_wpepp_conditional_referrer_source', true ) ),
				];

			default:
				return [];
		}
	}
}
