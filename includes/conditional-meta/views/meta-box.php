<?php

/**
 * Meta box view for conditional display
 *
 * @package WPEPP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Get the enable value first
$wpepp_enable = get_post_meta($post->ID, '_wpepp_conditional_display_enable', true) ?: 'no';
$wpepp_condition = get_post_meta($post->ID, '_wpepp_conditional_display_condition', true);
$wpepp_action = get_post_meta($post->ID, '_wpepp_conditional_action', true);

// Get saved values for specific conditions
$wpepp_recurring_time_start = get_post_meta($post->ID, '_wpepp_conditional_recurring_time_start', true) ?: '09:00';
$wpepp_recurring_time_end = get_post_meta($post->ID, '_wpepp_conditional_recurring_time_end', true) ?: '17:00';
$wpepp_recurring_days = get_post_meta($post->ID, '_wpepp_conditional_recurring_days', true) ?: array();
$wpepp_browser_types = get_post_meta($post->ID, '_wpepp_conditional_browser_type', true) ?: array();
$wpepp_url_parameter_name = get_post_meta($post->ID, '_wpepp_conditional_url_parameter_name', true) ?: '';
$wpepp_url_parameter_value = get_post_meta($post->ID, '_wpepp_conditional_url_parameter_value', true) ?: '';
$wpepp_referrer_source = get_post_meta($post->ID, '_wpepp_conditional_referrer_source', true) ?: '';
$wpepp_post_types = get_post_meta($post->ID, '_wpepp_conditional_post_type', true) ?: array();
$wpepp_user_roles = get_post_meta($post->ID, '_wpepp_conditional_user_role', true) ?: array();
$wpepp_device_type = get_post_meta($post->ID, '_wpepp_conditional_device_type', true) ?: 'mobile';
$wpepp_day_of_week = get_post_meta($post->ID, '_wpepp_conditional_day_of_week', true) ?: array();
$wpepp_time_start = get_post_meta($post->ID, '_wpepp_conditional_time_start', true) ?: '09:00';
$wpepp_time_end = get_post_meta($post->ID, '_wpepp_conditional_time_end', true) ?: '17:00';
$wpepp_date_start = get_post_meta($post->ID, '_wpepp_conditional_date_start', true) ?: gmdate('Y-m-d');
$wpepp_date_end = get_post_meta($post->ID, '_wpepp_conditional_date_end', true) ?: gmdate('Y-m-d', strtotime('+7 days'));

// Get title and featured image control values
$wpepp_control_title = get_post_meta($post->ID, '_wpepp_conditional_control_title', true);
$wpepp_control_featured_image = get_post_meta($post->ID, '_wpepp_conditional_control_featured_image', true);
$wpepp_hide_comments = get_post_meta($post->ID, '_wpepp_conditional_control_comments', true);
$wpepp_show_notice = get_post_meta($post->ID, '_wpepp_conditional_notice_enable', true);
$wpepp_notice_text = get_post_meta($post->ID, '_wpepp_conditional_notice_text', true);

// Add metabox-specific wrapper class
wp_nonce_field('wpepp_conditional_meta_box', 'wpepp_conditional_meta_box_nonce');
?>

<div class="wpepp-conditional-meta-box-wrapper">
    <div class="wpepp-conditional-enable">
        <label>
            <input type="checkbox" name="wpepp_conditional_display_enable" value="yes" <?php checked('yes', $wpepp_enable); ?> />
            <?php esc_html_e('Enable Conditional Display', 'wp-edit-password-protected'); ?>
        </label>
    </div>

    <div class="wpepp-conditional-options" <?php if ( 'yes' !== $wpepp_enable ) { echo 'style="display:none;"'; } ?>>
        <p class="wpepp-notice">
            <?php esc_html_e('Note: Conditions are applied on the frontend only.', 'wp-edit-password-protected'); ?>
        </p>

        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_display_condition">
                <strong><?php esc_html_e('CONDITION:', 'wp-edit-password-protected'); ?></strong>
            </label>
            <select name="wpepp_conditional_display_condition" id="wpepp_conditional_display_condition">
                <option value="user_logged_in" <?php selected('user_logged_in', $wpepp_condition); ?>>
                    <?php esc_html_e('User is logged in', 'wp-edit-password-protected'); ?>
                </option>
                <option value="user_logged_out" <?php selected('user_logged_out', $wpepp_condition); ?>>
                    <?php esc_html_e('User is logged out', 'wp-edit-password-protected'); ?>
                </option>
                <option value="user_role" <?php selected('user_role', $wpepp_condition); ?>>
                    <?php esc_html_e('User has specific role', 'wp-edit-password-protected'); ?>
                </option>
                <option value="device_type" <?php selected('device_type', $wpepp_condition); ?>>
                    <?php esc_html_e('Device type is', 'wp-edit-password-protected'); ?>
                </option>
                <option value="day_of_week" <?php selected('day_of_week', $wpepp_condition); ?>>
                    <?php esc_html_e('Day of week is', 'wp-edit-password-protected'); ?>
                </option>
                <option value="time_of_day" <?php selected('time_of_day', $wpepp_condition); ?>>
                    <?php esc_html_e('Time is between', 'wp-edit-password-protected'); ?>
                </option>
                <option value="date_range" <?php selected('date_range', $wpepp_condition); ?>>
                    <?php esc_html_e('Date is between', 'wp-edit-password-protected'); ?>
                </option>
                <option value="recurring_schedule" <?php selected('recurring_schedule', $wpepp_condition); ?>>
                    <?php esc_html_e('Recurring schedule', 'wp-edit-password-protected'); ?>
                </option>
                <option value="post_type" <?php selected('post_type', $wpepp_condition); ?>>
                    <?php esc_html_e('Current post type is', 'wp-edit-password-protected'); ?>
                </option>
                <option value="browser_type" <?php selected('browser_type', $wpepp_condition); ?>>
                    <?php esc_html_e('Browser is', 'wp-edit-password-protected'); ?>
                </option>
                <option value="url_parameter" <?php selected('url_parameter', $wpepp_condition); ?>>
                    <?php esc_html_e('URL parameter exists', 'wp-edit-password-protected'); ?>
                </option>
                <option value="referrer_source" <?php selected('referrer_source', $wpepp_condition); ?>>
                    <?php esc_html_e('Visitor came from', 'wp-edit-password-protected'); ?>
                </option>
            </select>
        </div>

    </div>

    <!-- User Role Condition -->
    <div class="wpepp-condition-fields wpepp-condition-user_role" <?php if ( 'user_role' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_user_role">
                <?php esc_html_e('Select Role', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_user_role[]" id="wpepp_conditional_user_role" multiple="multiple" style="width:100%">
                <?php
                $wpepp_role_options = method_exists('WPEPP_Conditional_Meta', 'get_user_roles') ?
                    (new WPEPP_Conditional_Meta())->get_user_roles() :
                    array(
                        'administrator' => __('Administrator', 'wp-edit-password-protected'),
                        'editor' => __('Editor', 'wp-edit-password-protected'),
                        'author' => __('Author', 'wp-edit-password-protected'),
                        'contributor' => __('Contributor', 'wp-edit-password-protected'),
                        'subscriber' => __('Subscriber', 'wp-edit-password-protected')
                    );

                foreach ($wpepp_role_options as $wpepp_role_value => $wpepp_role_label) {
                    echo '<option value="' . esc_attr( $wpepp_role_value ) . '"';
                    selected( in_array( $wpepp_role_value, (array) $wpepp_user_roles, true ), true );
                    echo '>' . esc_html( $wpepp_role_label ) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <!-- Device Type Condition -->
    <div class="wpepp-condition-fields wpepp-condition-device_type" <?php if ( 'device_type' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_device_type">
                <?php esc_html_e('Select Device', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_device_type" id="wpepp_conditional_device_type">
                <option value="desktop" <?php selected('desktop', $wpepp_device_type); ?>>
                    <?php esc_html_e('Desktop', 'wp-edit-password-protected'); ?>
                </option>
                <option value="tablet" <?php selected('tablet', $wpepp_device_type); ?>>
                    <?php esc_html_e('Tablet', 'wp-edit-password-protected'); ?>
                </option>
                <option value="mobile" <?php selected('mobile', $wpepp_device_type); ?>>
                    <?php esc_html_e('Mobile', 'wp-edit-password-protected'); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- Day of Week Condition -->
    <div class="wpepp-condition-fields wpepp-condition-day_of_week" <?php if ( 'day_of_week' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_day_of_week">
                <?php esc_html_e('Select Days', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_day_of_week[]" id="wpepp_conditional_day_of_week" multiple="multiple" style="width:100%">
                <option value="1" <?php selected( in_array( '1', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Monday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="2" <?php selected( in_array( '2', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Tuesday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="3" <?php selected( in_array( '3', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Wednesday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="4" <?php selected( in_array( '4', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Thursday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="5" <?php selected( in_array( '5', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Friday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="6" <?php selected( in_array( '6', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Saturday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="0" <?php selected( in_array( '0', (array) $wpepp_day_of_week, true ), true ); ?>>
                    <?php esc_html_e('Sunday', 'wp-edit-password-protected'); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- Time of Day Condition -->
    <div class="wpepp-condition-fields wpepp-condition-time_of_day" <?php if ( 'time_of_day' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_time_start">
                <?php esc_html_e('Start Time', 'wp-edit-password-protected'); ?>
            </label>
            <input type="time" name="wpepp_conditional_time_start" id="wpepp_conditional_time_start" value="<?php echo esc_attr($wpepp_time_start); ?>" />
        </div>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_time_end">
                <?php esc_html_e('End Time', 'wp-edit-password-protected'); ?>
            </label>
            <input type="time" name="wpepp_conditional_time_end" id="wpepp_conditional_time_end" value="<?php echo esc_attr($wpepp_time_end); ?>" />
        </div>
    </div>

    <!-- Date Range Condition -->
    <div class="wpepp-condition-fields wpepp-condition-date_range" <?php if ( 'date_range' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_date_start">
                <?php esc_html_e('Start Date', 'wp-edit-password-protected'); ?>
            </label>
            <input type="date" name="wpepp_conditional_date_start" id="wpepp_conditional_date_start" value="<?php echo esc_attr($wpepp_date_start); ?>" />
        </div>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_date_end">
                <?php esc_html_e('End Date', 'wp-edit-password-protected'); ?>
            </label>
            <input type="date" name="wpepp_conditional_date_end" id="wpepp_conditional_date_end" value="<?php echo esc_attr($wpepp_date_end); ?>" />
        </div>
    </div>

    <!-- Recurring Schedule Condition -->
    <div class="wpepp-condition-fields wpepp-condition-recurring_schedule" <?php if ( 'recurring_schedule' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_recurring_days">
                <?php esc_html_e('Select Days', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_recurring_days[]" id="wpepp_conditional_recurring_days" multiple="multiple" style="width:100%">
                <option value="1" <?php selected( in_array( '1', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Monday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="2" <?php selected( in_array( '2', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Tuesday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="3" <?php selected( in_array( '3', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Wednesday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="4" <?php selected( in_array( '4', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Thursday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="5" <?php selected( in_array( '5', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Friday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="6" <?php selected( in_array( '6', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Saturday', 'wp-edit-password-protected'); ?>
                </option>
                <option value="0" <?php selected( in_array( '0', (array) $wpepp_recurring_days, true ), true ); ?>>
                    <?php esc_html_e('Sunday', 'wp-edit-password-protected'); ?>
                </option>
            </select>
        </div>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_recurring_time_start">
                <?php esc_html_e('Start Time', 'wp-edit-password-protected'); ?>
            </label>
            <input type="time" name="wpepp_conditional_recurring_time_start" id="wpepp_conditional_recurring_time_start" value="<?php echo esc_attr($wpepp_recurring_time_start); ?>" />
        </div>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_recurring_time_end">
                <?php esc_html_e('End Time', 'wp-edit-password-protected'); ?>
            </label>
            <input type="time" name="wpepp_conditional_recurring_time_end" id="wpepp_conditional_recurring_time_end" value="<?php echo esc_attr($wpepp_recurring_time_end); ?>" />
        </div>
    </div>

    <!-- Post Type Condition -->
    <div class="wpepp-condition-fields wpepp-condition-post_type" <?php if ( 'post_type' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_post_type">
                <?php esc_html_e('Select Post Types', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_post_type[]" id="wpepp_conditional_post_type" multiple="multiple" style="width:100%">
                <?php
                $wpepp_available_post_types = get_post_types(array('public' => true), 'objects');
                foreach ($wpepp_available_post_types as $wpepp_post_type_obj) {
                    echo '<option value="' . esc_attr( $wpepp_post_type_obj->name ) . '"';
                    selected( in_array( $wpepp_post_type_obj->name, (array) $wpepp_post_types, true ), true );
                    echo '>' . esc_html( $wpepp_post_type_obj->label ) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <!-- Browser Type Condition -->
    <div class="wpepp-condition-fields wpepp-condition-browser_type" <?php if ( 'browser_type' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_browser_type">
                <?php esc_html_e('Select Browsers', 'wp-edit-password-protected'); ?>
            </label>
            <select name="wpepp_conditional_browser_type[]" id="wpepp_conditional_browser_type" multiple="multiple" style="width:100%">
                <option value="chrome" <?php selected( in_array( 'chrome', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('Chrome', 'wp-edit-password-protected'); ?>
                </option>
                <option value="firefox" <?php selected( in_array( 'firefox', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('Firefox', 'wp-edit-password-protected'); ?>
                </option>
                <option value="safari" <?php selected( in_array( 'safari', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('Safari', 'wp-edit-password-protected'); ?>
                </option>
                <option value="edge" <?php selected( in_array( 'edge', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('Edge', 'wp-edit-password-protected'); ?>
                </option>
                <option value="opera" <?php selected( in_array( 'opera', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('Opera', 'wp-edit-password-protected'); ?>
                </option>
                <option value="ie" <?php selected( in_array( 'ie', (array) $wpepp_browser_types, true ), true ); ?>>
                    <?php esc_html_e('IE', 'wp-edit-password-protected'); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- URL Parameter Condition -->
    <div class="wpepp-condition-fields wpepp-condition-url_parameter" <?php if ( 'url_parameter' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_url_parameter_name">
                <?php esc_html_e('Parameter Name', 'wp-edit-password-protected'); ?>
            </label>
            <input type="text" name="wpepp_conditional_url_parameter_name" id="wpepp_conditional_url_parameter_name" value="<?php echo esc_attr($wpepp_url_parameter_name); ?>" placeholder="param" />
        </div>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_url_parameter_value">
                <?php esc_html_e('Parameter Value', 'wp-edit-password-protected'); ?>
            </label>
            <input type="text" name="wpepp_conditional_url_parameter_value" id="wpepp_conditional_url_parameter_value" value="<?php echo esc_attr($wpepp_url_parameter_value); ?>" placeholder="value" />
        </div>
    </div>

    <!-- Referrer Source Condition -->
    <div class="wpepp-condition-fields wpepp-condition-referrer_source" <?php if ( 'referrer_source' !== $wpepp_condition ) { echo 'style="display:none;"'; } ?>>
        <div class="wpepp-conditional-row">
            <label for="wpepp_conditional_referrer_source">
                <?php esc_html_e('Referrer Contains', 'wp-edit-password-protected'); ?>
            </label>
            <input type="text" name="wpepp_conditional_referrer_source" id="wpepp_conditional_referrer_source" value="<?php echo esc_attr($wpepp_referrer_source); ?>" placeholder="google.com" />
        </div>
    </div>

    <!-- Action Selection -->
    <div class="wpepp-conditional-row wpepp-action-row">
        <label for="wpepp_conditional_action">
            <strong><?php esc_html_e('ACTION:', 'wp-edit-password-protected'); ?></strong>
        </label>
        <select name="wpepp_conditional_action" id="wpepp_conditional_action">
            <option value="show" <?php selected('show', $wpepp_action); ?>>
                <?php esc_html_e('Show content when condition is met', 'wp-edit-password-protected'); ?>
            </option>
            <option value="hide" <?php selected('hide', $wpepp_action); ?>>
                <?php esc_html_e('Hide content when condition is met', 'wp-edit-password-protected'); ?>
            </option>
        </select>
    </div>

    <!-- Additional Options -->
    <div class="wpepp-conditional-extra-options">
        <label>
            <input type="checkbox" name="wpepp_conditional_control_title" value="yes" <?php checked('yes', $wpepp_control_title); ?> />
            <?php esc_html_e('Also control title visibility', 'wp-edit-password-protected'); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" name="wpepp_conditional_control_featured_image" value="yes" <?php checked('yes', $wpepp_control_featured_image); ?> />
            <?php esc_html_e('Also control featured image', 'wp-edit-password-protected'); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" name="wpepp_conditional_control_comments" value="yes" <?php checked('yes', $wpepp_hide_comments); ?> />
            <?php esc_html_e('Also hide comments', 'wp-edit-password-protected'); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" name="wpepp_conditional_notice_enable" value="yes" <?php checked('yes', $wpepp_show_notice); ?> />
            <?php esc_html_e('Show notice when hidden', 'wp-edit-password-protected'); ?>
        </label>
        <div class="wpepp-notice-text-wrapper" <?php if ( 'yes' !== $wpepp_show_notice ) { echo 'style="display:none;"'; } ?>>
            <label for="wpepp_conditional_notice_text">
                <strong><?php esc_html_e('NOTICE TEXT:', 'wp-edit-password-protected'); ?></strong>
            </label>
            <textarea name="wpepp_conditional_notice_text" id="wpepp_conditional_notice_text" rows="3" style="width:100%"><?php echo esc_textarea($wpepp_notice_text); ?></textarea>
        </div>
    </div>
</div>