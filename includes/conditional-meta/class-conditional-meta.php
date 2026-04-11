<?php

/**
 * Conditional Meta functionality
 *
 * @package WPEPP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include helper class
require_once plugin_dir_path(__FILE__) . 'class-conditional-meta-helper.php';

/**
 * Conditional Meta Class
 */
class WPEPP_Conditional_Meta
{

    /**
     * Instance
     *
     * @var WPEPP_Conditional_Meta
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WPEPP_Conditional_Meta
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
        
    }

    /**
     * Initialize hooks
     */
    public function init_hooks()
    {
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Save meta data
        add_action('save_post', array($this, 'save_meta_data'));

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // Apply conditional display
        add_filter('the_content', array($this, 'apply_conditional_display'));

        // Add filters for title and featured image
        add_filter('the_title', array($this, 'apply_conditional_title'), 10, 2);
        add_filter('post_thumbnail_html', array($this, 'apply_conditional_thumbnail'), 10, 5);
        // Add filter for comments
        add_filter('comments_open', array($this, 'apply_conditional_comments'), 10, 2);
        add_filter('get_comments_number', array($this, 'apply_conditional_comments_number'), 10, 2);
        // REST API: Hide content if conditional display applies
        add_filter('rest_prepare_post', array($this, 'rest_api_conditional_content'), 12, 3);
        add_filter('rest_prepare_page', array($this, 'rest_api_conditional_content'), 12, 3);
    // REST API: Hide title and featured image if conditional display applies
    add_filter('rest_prepare_post', array($this, 'rest_api_conditional_title_and_thumbnail'), 13, 3);
    add_filter('rest_prepare_page', array($this, 'rest_api_conditional_title_and_thumbnail'), 13, 3);
    }


     
    /**
         * Hide REST API content if conditional display is enabled and condition/action require hiding
         */
        public function rest_api_conditional_content($response, $post, $request) {
            $post_id = $post->ID;
            $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
            if ('yes' !== $enable) {
                return $response;
            }
            $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
            $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';
            // Browser/referrer conditions are client-side only, so always hide in REST
            if (in_array($condition, array('browser_type', 'referrer_source'))) {
                $should_hide = true;
            } else {
                $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);
                $should_hide = !($condition_met && $action === 'show') && !(!$condition_met && $action === 'hide');
            }
            if ($should_hide) {
                $response->data['content']['rendered'] = __('This content is protected by conditional display.', 'wp-edit-password-protected');
                $response->data['excerpt']['rendered'] = '';
            }
            return $response;
        }

        /**
         * Hide REST API title and featured image if conditional display is enabled and control is enabled
         */
        public function rest_api_conditional_title_and_thumbnail($response, $post, $request) {
            $post_id = $post->ID;
            $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
            if ('yes' !== $enable) {
                return $response;
            }
            $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
            $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';
            $control_title = get_post_meta($post_id, '_wpepp_conditional_control_title', true);
            $control_featured_image = get_post_meta($post_id, '_wpepp_conditional_control_featured_image', true);
            // Browser/referrer conditions are client-side only, so always hide in REST
            if (in_array($condition, array('browser_type', 'referrer_source'))) {
                $should_hide = true;
            } else {
                $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);
                $should_hide = !($condition_met && $action === 'show') && !(!$condition_met && $action === 'hide');
            }
            if ($should_hide) {
                if ($control_title === 'yes' && isset($response->data['title']['rendered'])) {
                    $response->data['title']['rendered'] = '';
                }
                if ($control_featured_image === 'yes' && isset($response->data['featured_media'])) {
                    $response->data['featured_media'] = 0;
                }
            }
            return $response;
        }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes()
    {
        // $post_types = get_post_types(array('public' => true));

        $post_types = ['post', 'page'];

        foreach ($post_types as $post_type) {
            add_meta_box(
                'wpepp_conditional_meta_box',
                __('Conditional Display', 'wp-edit-password-protected'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render meta box
     *
     * @param WP_Post $post Post object.
     */
    public function render_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('wpepp_conditional_meta_nonce', 'wpepp_conditional_meta_nonce');

        // Get saved values
        $enable = get_post_meta($post->ID, '_wpepp_conditional_display_enable', true);
        $condition = get_post_meta($post->ID, '_wpepp_conditional_display_condition', true) ?: 'user_logged_out';
        $action = get_post_meta($post->ID, '_wpepp_conditional_action', true) ?: 'hide';

        // Include view
        include plugin_dir_path(__FILE__) . 'views/meta-box.php';
    }

    /**
     * Save meta data
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_data($post_id)
    {
        // Check if nonce is set
        if (!isset($_POST['wpepp_conditional_meta_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpepp_conditional_meta_nonce'])), 'wpepp_conditional_meta_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save enable/disable
        if (isset($_POST['wpepp_conditional_display_enable'])) {
            update_post_meta($post_id, '_wpepp_conditional_display_enable', 'yes');
        } else {
            update_post_meta($post_id, '_wpepp_conditional_display_enable', 'no');
        }

        // Save title control
        if (isset($_POST['wpepp_conditional_control_title'])) {
            update_post_meta($post_id, '_wpepp_conditional_control_title', 'yes');
        } else {
            update_post_meta($post_id, '_wpepp_conditional_control_title', 'no');
        }

        // Save featured image control
        if (isset($_POST['wpepp_conditional_control_featured_image'])) {
            update_post_meta($post_id, '_wpepp_conditional_control_featured_image', 'yes');
        } else {
            update_post_meta($post_id, '_wpepp_conditional_control_featured_image', 'no');
        }

        // Save hide comments
        if (isset($_POST['wpepp_conditional_control_comments'])) {
            update_post_meta($post_id, '_wpepp_conditional_control_comments', 'yes');
        } else {
            update_post_meta($post_id, '_wpepp_conditional_control_comments', 'no');
        }

        // Save show notice
        if (isset($_POST['wpepp_conditional_notice_enable'])) {
            update_post_meta($post_id, '_wpepp_conditional_notice_enable', 'yes');
        } else {
            update_post_meta($post_id, '_wpepp_conditional_notice_enable', 'no');
        }

        // Save notice text
        if (isset($_POST['wpepp_conditional_notice_text'])) {
            update_post_meta($post_id, '_wpepp_conditional_notice_text', sanitize_textarea_field(wp_unslash($_POST['wpepp_conditional_notice_text'])));
        }

        // Save condition.
        if (isset($_POST['wpepp_conditional_display_condition'])) {
            $submitted_condition = sanitize_text_field(wp_unslash($_POST['wpepp_conditional_display_condition']));
            $allowed_conditions = array(
                'user_logged_in', 'user_logged_out', 'user_role', 'device_type',
                'day_of_week', 'time_of_day', 'date_range', 'recurring_schedule',
                'post_type', 'browser_type', 'url_parameter', 'referrer_source',
            );

            if (in_array($submitted_condition, $allowed_conditions, true)) {
                update_post_meta($post_id, '_wpepp_conditional_display_condition', $submitted_condition);
            }
        }

        // Save action
        if (isset($_POST['wpepp_conditional_action'])) {
            update_post_meta($post_id, '_wpepp_conditional_action', sanitize_text_field(wp_unslash($_POST['wpepp_conditional_action'])));
        }

        // Save condition-specific fields
        WPEPP_Conditional_Meta_Helper::save_condition_specific_fields($post_id);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post;

        // Only enqueue on post edit screens
        if (!in_array($hook, array('post.php', 'post-new.php')) || !$post) {
            return;
        }

        // Enqueue Select2 if not already enqueued
        wp_enqueue_style('select2', WP_EDIT_PASS_ASSETS . 'css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('select2', WP_EDIT_PASS_ASSETS . 'js/select2.min.js', array('jquery'), '4.0.13', true);

        // Enqueue our custom styles and scripts
        wp_enqueue_style('wpepp-conditional-meta', WP_EDIT_PASS_ASSETS . 'css/conditional-meta-admin.css', array(), WP_EDIT_PASS_VERSION);
        wp_enqueue_script('wpepp-conditional-meta', WP_EDIT_PASS_ASSETS . 'js/conditional-meta-admin.js', array('jquery', 'select2'), WP_EDIT_PASS_VERSION, true);

        // Localize script with translation strings
        wp_localize_script('wpepp-conditional-meta', 'wpepp_conditional_data', array(
            'select_placeholder' => esc_attr__('Select options', 'wp-edit-password-protected')
        ));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        if (is_singular()) {
            $post_id = get_the_ID();
            $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);

            if ('yes' === $enable) {
                $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);

                // Only enqueue for browser-specific conditions
                if (in_array($condition, array('browser_type', 'referrer_source'))) {
                    wp_enqueue_script('wpepp-conditional-meta-frontend', WP_EDIT_PASS_ASSETS . 'js/conditional-meta-frontend.js', array('jquery'), WP_EDIT_PASS_VERSION, true);

                    // Pass data to script
                    $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

                    $data = array(
                        'post_id' => $post_id,
                        'condition' => $condition,
                        'action' => $action,
                        'condition_data' => WPEPP_Conditional_Meta_Helper::get_condition_data($post_id, $condition),
                        'needs_client_detection' => true
                    );

                    wp_localize_script('wpepp-conditional-meta-frontend', 'wpeppConditionalMeta', $data);
                }
            }
        }
    }

    /**
     * Apply conditional display
     *
     * @param string $content Post content.
     * @return string
     */
    public function apply_conditional_display($content)
    {
        if (!is_singular()) {
            return $content;
        }

        $post_id = get_the_ID();
        $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);

        if ('yes' !== $enable) {
            return $content;
        }

        $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
        $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

        // Skip browser-specific conditions as they're handled client-side
        if (in_array($condition, array('browser_type', 'referrer_source'))) {
            return '<div id="wpepp-conditional-content" style="display:none;">' . $content . '</div>';
        }

        // Evaluate condition
        $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);

        // Apply action
        if (($condition_met && $action === 'show') || (!$condition_met && $action === 'hide')) {
            return $content;
        } else {
            // Show notice if enabled
            $show_notice = get_post_meta($post_id, '_wpepp_conditional_notice_enable', true);
            if ('yes' === $show_notice) {
                $notice_text = get_post_meta($post_id, '_wpepp_conditional_notice_text', true);
                if (empty($notice_text)) {
                    $notice_text = __('This content is not available.', 'wp-edit-password-protected');
                }
                return '<div class="wpepp-conditional-notice">' . esc_html($notice_text) . '</div>';
            }
            return '';
        }
    }

    /**
     * Apply conditional display to post title
     *
     * @param string $title The post title.
     * @param int    $post_id The post ID.
     * @return string
     */
    public function apply_conditional_title($title, $post_id)
    {
        // Skip if not in main query or not a singular view
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $title;
        }

        $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
        $control_title = get_post_meta($post_id, '_wpepp_conditional_control_title', true);

        // Only apply if conditional display is enabled and title control is enabled
        if ('yes' !== $enable || 'yes' !== $control_title) {
            return $title;
        }

        $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
        $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

        // Handle browser-specific conditions
        if (in_array($condition, array('browser_type', 'referrer_source'))) {
            return '<span id="wpepp-conditional-title" style="display:none;">' . $title . '</span>';
        }

        // Evaluate condition
        $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);

        // Apply action
        if (($condition_met && $action === 'show') || (!$condition_met && $action === 'hide')) {
            return $title;
        } else {
            return ''; // Don't show title
        }
    }

    /**
     * Apply conditional display to featured image
     *
     * @param string $html The post thumbnail HTML.
     * @param int    $post_id The post ID.
     * @param int    $post_thumbnail_id The post thumbnail ID.
     * @param string $size The image size.
     * @param array  $attr The image attributes.
     * @return string
     */
    public function apply_conditional_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        // Skip if not in main query or not a singular view
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $html;
        }

        $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
        $control_featured_image = get_post_meta($post_id, '_wpepp_conditional_control_featured_image', true);

        // Only apply if conditional display is enabled and featured image control is enabled
        if ('yes' !== $enable || 'yes' !== $control_featured_image) {
            return $html;
        }

        $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
        $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

        // Handle browser-specific conditions
        if (in_array($condition, array('browser_type', 'referrer_source'))) {
            return '<div id="wpepp-conditional-thumbnail" style="display:none;">' . $html . '</div>';
        }

        // Evaluate condition
        $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);

        // Apply action
        if (($condition_met && $action === 'show') || (!$condition_met && $action === 'hide')) {
            return $html;
        } else {
            return ''; // Don't show thumbnail
        }
    }
    /**
     * Apply conditional display to comments.
     *
     * @param bool $open    Whether comments are open.
     * @param int  $post_id The post ID.
     * @return bool
     */
    public function apply_conditional_comments($open, $post_id)
    {
        if (!is_singular() || !$post_id) {
            return $open;
        }

        $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
        $hide_comments = get_post_meta($post_id, '_wpepp_conditional_control_comments', true);

        if ('yes' !== $enable || 'yes' !== $hide_comments) {
            return $open;
        }

        $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
        $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

        // Skip browser-specific conditions
        if (in_array($condition, array('browser_type', 'referrer_source'))) {
            return $open;
        }

        $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);
        $should_hide = !(($condition_met && $action === 'show') || (!$condition_met && $action === 'hide'));

        if ($should_hide) {
            return false;
        }

        return $open;
    }

    /**
     * Apply conditional display to comments number.
     *
     * @param int $count   The comments count.
     * @param int $post_id The post ID.
     * @return int
     */
    public function apply_conditional_comments_number($count, $post_id)
    {
        if (!is_singular() || !$post_id) {
            return $count;
        }

        $enable = get_post_meta($post_id, '_wpepp_conditional_display_enable', true);
        $hide_comments = get_post_meta($post_id, '_wpepp_conditional_control_comments', true);

        if ('yes' !== $enable || 'yes' !== $hide_comments) {
            return $count;
        }

        $condition = get_post_meta($post_id, '_wpepp_conditional_display_condition', true);
        $action = get_post_meta($post_id, '_wpepp_conditional_action', true) ?: 'hide';

        if (in_array($condition, array('browser_type', 'referrer_source'))) {
            return $count;
        }

        $condition_met = WPEPP_Conditional_Meta_Helper::evaluate_condition($post_id, $condition);
        $should_hide = !(($condition_met && $action === 'show') || (!$condition_met && $action === 'hide'));

        if ($should_hide) {
            return 0;
        }

        return $count;
    }
}

// Initialize the class
WPEPP_Conditional_Meta::get_instance();
