<?php
/**
 * Conditional Meta Helper functionality
 *
 * @package WPEPP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Conditional Meta Helper Class
 */
class WPEPP_Conditional_Meta_Helper {

    /**
     * Get condition data
     *
     * @param int    $post_id  Post ID.
     * @param string $condition Condition type.
     * @return array
     */
    public static function get_condition_data($post_id, $condition) {
        $data = array();
        
        switch ($condition) {
            case 'user_logged_in':
            case 'user_logged_out':
                // No additional data needed
                break;
                
            case 'user_role':
                $data['user_roles'] = get_post_meta($post_id, '_wpepp_conditional_user_role', true);
                break;
                
            case 'device_type':
                $data['device_type'] = get_post_meta($post_id, '_wpepp_conditional_device_type', true);
                break;
                
            case 'day_of_week':
                $data['days'] = get_post_meta($post_id, '_wpepp_conditional_day_of_week', true);
                break;
                
            case 'time_of_day':
                $data['start_time'] = get_post_meta($post_id, '_wpepp_conditional_time_start', true);
                $data['end_time'] = get_post_meta($post_id, '_wpepp_conditional_time_end', true);
                break;
                
            case 'date_range':
                $data['start_date'] = get_post_meta($post_id, '_wpepp_conditional_date_start', true);
                $data['end_date'] = get_post_meta($post_id, '_wpepp_conditional_date_end', true);
                break;
                
            case 'recurring_schedule':
                $data['days'] = get_post_meta($post_id, '_wpepp_conditional_recurring_days', true);
                $data['start_time'] = get_post_meta($post_id, '_wpepp_conditional_recurring_time_start', true);
                $data['end_time'] = get_post_meta($post_id, '_wpepp_conditional_recurring_time_end', true);
                break;
                
            case 'post_type':
                $data['post_types'] = get_post_meta($post_id, '_wpepp_conditional_post_type', true);
                break;
                
            case 'browser_type':
                $data['browser_types'] = get_post_meta($post_id, '_wpepp_conditional_browser_type', true);
                break;
                
            case 'url_parameter':
                $data['parameter_name'] = get_post_meta($post_id, '_wpepp_conditional_url_parameter_name', true);
                $data['parameter_value'] = get_post_meta($post_id, '_wpepp_conditional_url_parameter_value', true);
                break;
                
            case 'referrer_source':
                $data['referrer_source'] = get_post_meta($post_id, '_wpepp_conditional_referrer_source', true);
                break;
        }
        
        return $data;
    }

    /**
     * Save condition-specific fields
     *
     * @param int $post_id Post ID.
     */
    public static function save_condition_specific_fields($post_id) {
        // Verify nonce (also checked by caller, but guard the public method).
        if (
            ! isset($_POST['wpepp_conditional_meta_box_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpepp_conditional_meta_box_nonce'])), 'wpepp_conditional_meta_box')
        ) {
            return;
        }

        // User role
        if (isset($_POST['wpepp_conditional_user_role'])) {
            $user_roles = array_map('sanitize_text_field', wp_unslash((array) $_POST['wpepp_conditional_user_role']));
            update_post_meta($post_id, '_wpepp_conditional_user_role', $user_roles);
        }
        
        // Device type
        if (isset($_POST['wpepp_conditional_device_type'])) {
            update_post_meta($post_id, '_wpepp_conditional_device_type', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_device_type'])));
        }
        
        // Day of week
        if (isset($_POST['wpepp_conditional_day_of_week'])) {
            $days = array_map('sanitize_text_field', wp_unslash((array) $_POST['wpepp_conditional_day_of_week']));
            update_post_meta($post_id, '_wpepp_conditional_day_of_week', $days);
        }
        
        // Time of day
        if (isset($_POST['wpepp_conditional_time_start'])) {
            update_post_meta($post_id, '_wpepp_conditional_time_start', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_time_start'])));
        }
        
        if (isset($_POST['wpepp_conditional_time_end'])) {
            update_post_meta($post_id, '_wpepp_conditional_time_end', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_time_end'])));
        }
        
        // Date range
        if (isset($_POST['wpepp_conditional_date_start'])) {
            update_post_meta($post_id, '_wpepp_conditional_date_start', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_date_start'])));
        }
        
        if (isset($_POST['wpepp_conditional_date_end'])) {
            update_post_meta($post_id, '_wpepp_conditional_date_end', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_date_end'])));
        }
        
        // Recurring schedule
        if (isset($_POST['wpepp_conditional_recurring_time_start'])) {
            update_post_meta($post_id, '_wpepp_conditional_recurring_time_start', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_recurring_time_start'])));
        }
        
        if (isset($_POST['wpepp_conditional_recurring_time_end'])) {
            update_post_meta($post_id, '_wpepp_conditional_recurring_time_end', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_recurring_time_end'])));
        }

        // Recurring days
        if (isset($_POST['wpepp_conditional_recurring_days'])) {
            $days = array_map('sanitize_text_field', wp_unslash((array) $_POST['wpepp_conditional_recurring_days']));
            update_post_meta($post_id, '_wpepp_conditional_recurring_days', $days);
        }

        // Post type
        if (isset($_POST['wpepp_conditional_post_type'])) {
            $post_types = array_map('sanitize_text_field', wp_unslash((array) $_POST['wpepp_conditional_post_type']));
            update_post_meta($post_id, '_wpepp_conditional_post_type', $post_types);
        }

        // Browser type
        if (isset($_POST['wpepp_conditional_browser_type'])) {
            $browser_types = array_map('sanitize_text_field', wp_unslash((array) $_POST['wpepp_conditional_browser_type']));
            update_post_meta($post_id, '_wpepp_conditional_browser_type', $browser_types);
        }

        // URL parameter
        if (isset($_POST['wpepp_conditional_url_parameter_name'])) {
            update_post_meta($post_id, '_wpepp_conditional_url_parameter_name', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_url_parameter_name'])));
        }
        
        if (isset($_POST['wpepp_conditional_url_parameter_value'])) {
            update_post_meta($post_id, '_wpepp_conditional_url_parameter_value', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_url_parameter_value'])));
        }

        // Referrer source
        if (isset($_POST['wpepp_conditional_referrer_source'])) {
            update_post_meta($post_id, '_wpepp_conditional_referrer_source', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_referrer_source'])));
        }
    }

    /**
     * Evaluate condition
     *
     * @param int    $post_id  Post ID.
     * @param string $condition Condition type.
     * @return bool
     */
    public static function evaluate_condition($post_id, $condition) {
        switch ($condition) {
            case 'user_logged_in':
                return is_user_logged_in();
                
            case 'user_logged_out':
                return !is_user_logged_in();
                
            case 'user_role':
                return self::check_user_role($post_id);
                
            case 'device_type':
                return self::check_device_type($post_id);
                
            case 'day_of_week':
                return self::check_day_of_week($post_id);
                
            case 'time_of_day':
                return self::check_time_of_day($post_id);
                
            case 'date_range':
                return self::check_date_range($post_id);
                
            case 'recurring_schedule':
                return self::check_recurring_schedule($post_id);
                
            case 'post_type':
                return self::check_post_type($post_id);
                
            case 'url_parameter':
                return self::check_url_parameter($post_id);
                
            default:
                return true;
        }
    }

    /**
     * Check user role
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_user_role($post_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_roles = get_post_meta($post_id, '_wpepp_conditional_user_role', true);
        
        if (empty($user_roles)) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        
        foreach ($user_roles as $role) {
            if (in_array($role, (array) $current_user->roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check device type
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_device_type($post_id) {
        $device_type = get_post_meta($post_id, '_wpepp_conditional_device_type', true);
        
        if (empty($device_type)) {
            return false;
        }
        
        $is_mobile = wp_is_mobile();
        $is_tablet = false;
        
        // Try to detect tablets
        if ($is_mobile && isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
            $is_tablet = preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent);
        }
        
        switch ($device_type) {
            case 'mobile':
                return $is_mobile && !$is_tablet;
            case 'tablet':
                return $is_tablet;
            case 'desktop':
                return !$is_mobile;
            default:
                return false;
        }
    }

    /**
     * Check day of week
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_day_of_week($post_id) {
        $days = get_post_meta($post_id, '_wpepp_conditional_day_of_week', true);
        
        if (empty($days)) {
            return false;
        }
        
        $current_day = gmdate('w'); // 0 (Sunday) to 6 (Saturday)
        
        return in_array($current_day, $days);
    }

    /**
     * Check time of day
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_time_of_day($post_id) {
        $start_time = get_post_meta($post_id, '_wpepp_conditional_time_start', true);
        $end_time = get_post_meta($post_id, '_wpepp_conditional_time_end', true);
        
        if (empty($start_time) || empty($end_time)) {
            return false;
        }
        
        $current_time = current_time('H:i');
        
        return ($current_time >= $start_time && $current_time <= $end_time);
    }

    /**
     * Check date range
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_date_range($post_id) {
        $start_date = get_post_meta($post_id, '_wpepp_conditional_date_start', true);
        $end_date = get_post_meta($post_id, '_wpepp_conditional_date_end', true);
        
        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        
        $current_date = current_time('Y-m-d');
        
        return ($current_date >= $start_date && $current_date <= $end_date);
    }

    /**
     * Check recurring schedule
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_recurring_schedule($post_id) {
        $days = get_post_meta($post_id, '_wpepp_conditional_recurring_days', true);
        $start_time = get_post_meta($post_id, '_wpepp_conditional_recurring_time_start', true);
        $end_time = get_post_meta($post_id, '_wpepp_conditional_recurring_time_end', true);
        
        if (empty($days) || empty($start_time) || empty($end_time)) {
            return false;
        }
        
        $current_day = gmdate('w'); // 0 (Sunday) to 6 (Saturday)
        $current_time = current_time('H:i');
        
        return (in_array($current_day, $days) && $current_time >= $start_time && $current_time <= $end_time);
    }

    /**
     * Check post type
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_post_type($post_id) {
        $post_types = get_post_meta($post_id, '_wpepp_conditional_post_type', true);
        
        if (empty($post_types)) {
            return false;
        }
        
        $current_post_type = get_post_type();
        
        return in_array($current_post_type, $post_types);
    }

    /**
     * Check URL parameter
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function check_url_parameter($post_id) {
        $param_name = get_post_meta($post_id, '_wpepp_conditional_url_parameter_name', true);
        $param_value = get_post_meta($post_id, '_wpepp_conditional_url_parameter_value', true);
        
        if (empty($param_name)) {
            return false;
        }
        
        // Check if parameter exists
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only frontend condition check, no form submission.
        if (!isset($_GET[$param_name])) {
            return false;
        }
        
        // If parameter value is specified, check it
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only frontend condition check, no form submission.
        $get_value = sanitize_text_field(wp_unslash($_GET[$param_name]));
        if (!empty($param_value) && $get_value !== $param_value) {
            return false;
        }
        
        return true;
    }

    /**
     * Get user roles
     *
     * @return array
     */
    public static function get_user_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $roles = $wp_roles->get_names();
        
        return $roles;
    }

    /**
     * Get post types
     *
     * @return array
     */
    public static function get_post_types() {
        $post_types = get_post_types(array(
            'public' => true
        ), 'objects');
        
        $result = array();
        
        foreach ($post_types as $post_type) {
            $result[$post_type->name] = $post_type->label;
        }
        
        return $result;
    }

    /**
     * Get browser types
     *
     * @return array
     */
    public static function get_browser_types() {
        return array(
            'chrome' => __('Chrome', 'wp-edit-password-protected'),
            'firefox' => __('Firefox', 'wp-edit-password-protected'),
            'safari' => __('Safari', 'wp-edit-password-protected'),
            'edge' => __('Edge', 'wp-edit-password-protected'),
            'opera' => __('Opera', 'wp-edit-password-protected'),
            'ie' => __('Internet Explorer', 'wp-edit-password-protected')
        );
    }

    /**
     * Get device types
     *
     * @return array
     */
    public static function get_device_types() {
        return array(
            'mobile' => __('Mobile', 'wp-edit-password-protected'),
            'tablet' => __('Tablet', 'wp-edit-password-protected'),
            'desktop' => __('Desktop', 'wp-edit-password-protected')
        );
    }

    /**
     * Get days of week
     *
     * @return array
     */
    public static function get_days_of_week() {
        return array(
            '0' => __('Sunday', 'wp-edit-password-protected'),
            '1' => __('Monday', 'wp-edit-password-protected'),
            '2' => __('Tuesday', 'wp-edit-password-protected'),
            '3' => __('Wednesday', 'wp-edit-password-protected'),
            '4' => __('Thursday', 'wp-edit-password-protected'),
            '5' => __('Friday', 'wp-edit-password-protected'),
            '6' => __('Saturday', 'wp-edit-password-protected')
        );
    }

    /**
     * Get condition options
     *
     * @return array
     */
    public static function get_condition_options() {
        return array(
            'user_logged_in' => __('User is logged in', 'wp-edit-password-protected'),
            'user_logged_out' => __('User is logged out', 'wp-edit-password-protected'),
            'user_role' => __('User has specific role', 'wp-edit-password-protected'),
            'device_type' => __('Device type is', 'wp-edit-password-protected'),
            'day_of_week' => __('Day of week is', 'wp-edit-password-protected'),
            'time_of_day' => __('Time of day is between', 'wp-edit-password-protected'),
            'date_range' => __('Date is between', 'wp-edit-password-protected'),
            'recurring_schedule' => __('Recurring schedule', 'wp-edit-password-protected'),
            'post_type' => __('Post type is', 'wp-edit-password-protected'),
            'browser_type' => __('Browser type is', 'wp-edit-password-protected'),
            'url_parameter' => __('URL parameter exists', 'wp-edit-password-protected'),
            'referrer_source' => __('Referrer source contains', 'wp-edit-password-protected')
        );
    }

    /**
     * Get action options
     *
     * @return array
     */
    public static function get_action_options() {
        return array(
            'show' => __('Show content', 'wp-edit-password-protected'),
            'hide' => __('Hide content', 'wp-edit-password-protected')
        );
    }
}