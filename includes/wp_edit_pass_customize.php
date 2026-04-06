<?php

defined( 'ABSPATH' ) || exit;

/*
 * @link              http://nalam.awesomebootstrap.net
 * @since             1.0.0
 * @package            WPEPP
 *
 * @wordpress-plugin
 */

class wpEditPasswordOutput
{

    public function __construct()
    {
        add_filter('the_password_form', [$this, 'output_pass_form'], 999);
        add_action('wp_loaded', [$this, 'cookie_set']);
    }


    public function output_pass_form()
    {
        // Default value 
        $wp_edit_pass_option = get_option('pp_basic_settings');
        $options = (!empty($wp_edit_pass_option)) ? $wp_edit_pass_option : '';
        $social_select = (isset($options['social_select'])) ? $options['social_select'] : array('middle' => 'middle');
        $icon_style = (isset($options['icon_style'])) ? $options['icon_style'] : 'square';
        $form_select_style = (isset($options['form_select'])) ? $options['form_select'] : 'four';



        $form_style_select = get_option('wppasspro_form_style', $form_select_style);


        $icon_style = get_option('wppasspro_icons_style', $icon_style);
        $social_vposition = get_option('wppasspro_icons_vposition', 'top');
        $error_text_position = get_option('wppasspro_error_text_position', 'top');
?>
        <div id="wpppass" class="wpppass-area wppass-style-<?php echo esc_attr($form_style_select); ?> <?php echo esc_attr($icon_style); ?>">
            <?php

            if ($social_vposition == 'top') {
                $this->pass_form_social_icons();
            }
            $this->pass_form_toptext();
            if ($social_vposition == 'middle') {
                $this->pass_form_social_icons();
            }
            if ($error_text_position == 'top') {
                $this->error_info_text();
            }
            $this->pass_main_form();
            if ($error_text_position == 'bottom') {
                $this->error_info_text();
            }
            $this->pass_form_bottomtext();
            if ($social_vposition == 'bottom') {
                $this->pass_form_social_icons();
            }

            ?>

        </div>

    <?php
    }



    public function pass_main_form()
    {
        $wp_edit_pass_option = get_option('pp_basic_settings');
        $options = (!empty($wp_edit_pass_option)) ? $wp_edit_pass_option : '';

        $submit_btn_text = (isset($options['wp_btn_text'])) ? $options['wp_btn_text'] : __('Submit', 'wp-edit-password-protected');


        global $post;
        $wppasspro_form_label = get_option('wppasspro_form_label', esc_html__('Password', 'wp-edit-password-protected'));
        $submit_btn_text = get_option('wppasspro_form_btn_text', $submit_btn_text);

        $label = 'pwbox-' . (empty($post->ID) ? wp_rand() : $post->ID);

    ?>
        <form class="wppass-form" action="<?php echo esc_url(site_url('wp-login.php?action=postpass', 'login_post')) ?>" method="post">
            <input name="post_password" placeholder="<?php echo esc_html($wppasspro_form_label); ?>" id="<?php echo esc_attr($label); ?>" type="password" size="20" maxlength="20" /><input type="submit" name="Submit" value="<?php echo esc_attr($submit_btn_text); ?>" />
        </form>
    <?php
    }

    public function pass_form_toptext()
    {
        $wp_edit_pass_option = get_option('pp_basic_settings');
        $options = (!empty($wp_edit_pass_option)) ? $wp_edit_pass_option : '';
        $text_select = (isset($options['text_select'])) ? $options['text_select'] : array('top' => 'top');
        $form_top_text = (isset($options['form_top_text'])) ? $options['form_top_text'] : 'For more public resources check out our followed link.';

        if (in_array("top", $text_select)) {
            $show_text_top = 'on';
        } else {
            $show_text_top = 'off';
        }


        $wppasspro_show_top_text  = get_option('wppasspro_show_top_text', '$show_text_top');



        if ($wppasspro_show_top_text == 'off') {
            return;
        }


        $toptext_align = get_option('wppasspro_top_text_align', 'center');
        $wppasspro_top_header = get_option('wppasspro_top_header', 'This content is password protected for members only');
        $wppasspro_top_content = get_option('wppasspro_top_content', $form_top_text);

    ?>
        <div class="wpppass-text wpppass-top-text wpppass-text-<?php echo esc_attr($toptext_align); ?>">
            <?php if ($wppasspro_top_header) : ?>
                <h3 class="wpppass-top-head"><?php echo esc_html($wppasspro_top_header); ?></h3>
            <?php endif; ?>
            <?php if ($wppasspro_top_content) : ?>
                <p><?php echo wp_kses_post($wppasspro_top_content); ?></p>
            <?php endif; ?>
        </div>

    <?php


    }

    public function pass_form_bottomtext()
    {
        $wp_edit_pass_option = get_option('pp_basic_settings');
        $options = (!empty($wp_edit_pass_option)) ? $wp_edit_pass_option : '';
        $text_select = (isset($options['text_select'])) ? $options['text_select'] : array('top' => 'top');
        $form_bottom_text = (isset($options['form_bottom_text'])) ? $options['form_bottom_text'] : '';
        if (in_array("bottom", $text_select)) {
            $show_text_bottom = 'on';
        } else {
            $show_text_bottom = 'off';
        }

        $wppasspro_show_bottom_text = get_option('wppasspro_show_bottom_text', $show_text_bottom);
        if ($wppasspro_show_bottom_text == 'off') {
            return;
        }

        $toptext_align = get_option('wppasspro_bottom_text_align', 'left');
        $wppasspro_bottom_header = get_option('wppasspro_bottom_header');
        $wppasspro_bottom_content = get_option('wppasspro_bottom_content', $form_bottom_text);
    ?>
        <div class="wpppass-text wpppass-bottom-text wpppass-text-<?php echo esc_attr($toptext_align); ?>">
            <?php if ($wppasspro_bottom_header) : ?>
                <h3 class="wpppass-bottom-head"><?php echo esc_html($wppasspro_bottom_header); ?></h3>
            <?php endif; ?>
            <?php if ($wppasspro_bottom_content) : ?>
                <p><?php echo wp_kses_post($wppasspro_bottom_content); ?></p>
            <?php endif; ?>
        </div>

    <?php


    }
    public function pass_form_social_icons()
    {
        $wppasspro_show_social = get_option('wppasspro_show_social', 'on');
        if ($wppasspro_show_social == 'off') {
            return;
        }
        $wp_edit_pass_option = get_option('pp_basic_settings');
        $options = (!empty($wp_edit_pass_option)) ? $wp_edit_pass_option : '';
        $facebook_url = (isset($options['facebook_url'])) ? $options['facebook_url'] : 'https://facebook.com';
        $twitter_url = (isset($options['twitter_url'])) ? $options['twitter_url'] : 'https://twitter.com';
        $tumblr_url = (isset($options['tumblr_url'])) ? $options['tumblr_url'] : '';
        $linkedin_url = (isset($options['linkedin_url'])) ? $options['linkedin_url'] : '';
        $pinterest_url = (isset($options['pinterest_url'])) ? $options['pinterest_url'] : '';
        $instagram_url = (isset($options['instagram_url'])) ? $options['instagram_url'] : '';
        $youtube_url = (isset($options['youtube_url'])) ? $options['youtube_url'] : '';
        $custom_url = (isset($options['custom_url'])) ? $options['custom_url'] : '';



        $social_hposition = get_option('wppasspro_icons_alignment', 'center');


        $social_urls = [];
        $social_urls[] = get_option('wppasspro_link_facebook', $facebook_url);
        $social_urls[] = get_option('wppasspro_link_twitter', $twitter_url);
        $social_urls[] = get_option('wppasspro_link_youtube', $youtube_url);
        $social_urls[] = get_option('wppasspro_link_instagram', $instagram_url);
        $social_urls[] = get_option('wppasspro_link_linkedin', $linkedin_url);
        $social_urls[] = get_option('wppasspro_link_pinterest', $pinterest_url);
        $social_urls[] = get_option('wppasspro_link_tumblr', $tumblr_url);
        $social_urls[] = get_option('wppasspro_link_custom', $custom_url);

    ?>
        <div class="wpp-social wpppass-social-top wppp-<?php echo esc_attr($social_hposition); ?>">
            <?php
            foreach ($social_urls as $link) :
                if ($link) {
                    $slink = wp_parse_url(esc_url($link));
                    if (!empty($slink['host'])) {
                        $sicon = explode('.', $slink['host']);
                        $icon_class = strtolower($sicon[0]);
                        if (!($icon_class == 'facebook' || $icon_class ==  'twitter' || $icon_class ==  'linkedin' || $icon_class ==  'pinterest' || $icon_class ==  'youtube' || $icon_class ==  'tumblr' || $icon_class ==  'instagram')) {
                            $icon_class = 'url';
                        }
                    } else {
                        $icon_class = 'url';
                    }


            ?>
                    <?php if ($icon_class == 'twitter'): ?>
                        <a target=" _blank" href="<?php echo esc_url($link); ?>">
                            <svg class="wpp-xicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                                <path fill="white" d="M16.64 3H20.89L13.9 10.92L22 21H15.95L10.98 14.77L5.29 21H1.02L8.46 12.5L0.67 3H6.89L11.38 8.64L16.64 3ZM15.61 18.89H17.44L6.13 4.03H4.15L15.61 18.89Z" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <a target=" _blank" href="<?php echo esc_url($link); ?>"><i class="icon-wpass-<?php echo esc_attr($icon_class); ?>"></i></a>

                    <?php endif; ?>
            <?php
                }
            endforeach;
            ?>
        </div>
        <?php
    }

    public function error_info_text()
    {
        $error_text = get_option('wppasspro_form_errortext', __('The password you have entered is invalid', 'wp-edit-password-protected'));
        $attempted     = isset($_SESSION['pass_attempt']) ? sanitize_text_field( wp_unslash( $_SESSION['pass_attempt'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        //$wrongPassword = '';
        // If cookie is set password is wrong.
        $cookie_key = 'wp-postpass_' . COOKIEHASH;
        $cookie_val = isset($_COOKIE[ $cookie_key ]) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $cookie_val && $attempted !== $cookie_val ) {
            // So we can show invalid password message only once.
            $_SESSION['pass_attempt'] = $cookie_val; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
            <p class="wppass-error-text"><?php echo esc_html($error_text); ?></p>
<?php
        }
    }

    public function cookie_set()
    {
        if (isset($_COOKIE['wp-postpass_' . COOKIEHASH])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // Start session to compare pass hashs.
            session_start();
        }
    }
}

$wpepp_form_output = new wpEditPasswordOutput();
