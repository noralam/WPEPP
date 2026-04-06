<?php
/**
 * Settings migration from v1.x to v2.0.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Migration
 */
class WPEPP_Migration {

	/**
	 * Run migration if needed.
	 */
	public static function migrate() {
		$version = get_option( 'wpepp_version', '1.0' );

		if ( version_compare( $version, '2.0', '<' ) ) {
			self::migrate_password_settings();
			self::migrate_member_template();
			update_option( 'wpepp_version', WPEPP_VERSION );
		}
	}

	/**
	 * Migrate wppasspro_* options to wpepp_password_settings JSON.
	 */
	private static function migrate_password_settings() {
		if ( false !== get_option( 'wpepp_password_settings' ) ) {
			return;
		}

		$settings = [
			'active_style'        => get_option( 'wppasspro_form_style', 'four' ),
			'show_top_text'       => get_option( 'wppasspro_show_top_text', 'on' ),
			'top_text_align'      => get_option( 'wppasspro_top_text_align', 'center' ),
			'top_header'          => get_option( 'wppasspro_top_header', '' ),
			'top_content'         => get_option( 'wppasspro_top_content', '' ),
			'show_bottom_text'    => get_option( 'wppasspro_show_bottom_text', 'off' ),
			'bottom_text_align'   => get_option( 'wppasspro_bottom_text_align', 'left' ),
			'bottom_header'       => get_option( 'wppasspro_bottom_header', '' ),
			'bottom_content'      => get_option( 'wppasspro_bottom_content', '' ),
			'form_label'          => get_option( 'wppasspro_form_label', 'Password' ),
			'form_btn_text'       => get_option( 'wppasspro_form_btn_text', 'Submit' ),
			'form_errortext'      => get_option( 'wppasspro_form_errortext', '' ),
			'error_text_position' => get_option( 'wppasspro_error_text_position', 'top' ),
			'show_social'         => get_option( 'wppasspro_show_social', 'on' ),
			'icons_vposition'     => get_option( 'wppasspro_icons_vposition', 'top' ),
			'icons_alignment'     => get_option( 'wppasspro_icons_alignment', 'right' ),
			'icons_style'         => get_option( 'wppasspro_icons_style', 'square' ),
			'link_facebook'       => get_option( 'wppasspro_link_facebook', '' ),
			'link_twitter'        => get_option( 'wppasspro_link_twitter', '' ),
			'link_youtube'        => get_option( 'wppasspro_link_youtube', '' ),
			'link_instagram'      => get_option( 'wppasspro_link_instagram', '' ),
			'link_linkedin'       => get_option( 'wppasspro_link_linkedin', '' ),
			'link_pinterest'      => get_option( 'wppasspro_link_pinterest', '' ),
			'link_tumblr'         => get_option( 'wppasspro_link_tumblr', '' ),
			'link_custom'         => get_option( 'wppasspro_link_custom', '' ),
		];

		update_option( 'wpepp_password_settings', wp_json_encode( $settings ) );
	}

	/**
	 * Migrate wpe_adpage_* options to wpepp_member_template JSON.
	 */
	private static function migrate_member_template() {
		if ( false !== get_option( 'wpepp_member_template' ) ) {
			return;
		}

		$settings = [
			'page_fimg'            => get_option( 'wppasspro_page_fimg', 'hide' ),
			'class'                => get_option( 'wpe_adpage_class', '' ),
			'mode'                 => get_option( 'wpe_adpage_mode', 'login' ),
			'style'                => get_option( 'wpe_adpage_style', 's1' ),
			'text_align'           => get_option( 'wpe_adpage_text_align', 'center' ),
			'infotitle'            => get_option( 'wpe_adpage_infotitle', '' ),
			'titletag'             => get_option( 'wpe_adpage_titletag', 'h2' ),
			'text'                 => get_option( 'wpe_adpage_text', '' ),
			'shortcode'            => get_option( 'wpe_adpage_shortcode', '' ),
			'login_mode'           => get_option( 'wpe_adpage_login_mode', 'form' ),
			'login_url'            => get_option( 'wpe_adpage_login_url', '' ),
			'btntext'              => get_option( 'wpe_adpage_btntext', 'Login' ),
			'btnclass'             => get_option( 'wpe_adpage_btnclass', 'btn button' ),
			'form_head'            => get_option( 'wpe_adpage_form_head', 'Login Form' ),
			'user_placeholder'     => get_option( 'wpe_adpage_user_placeholder', 'username' ),
			'password_placeholder' => get_option( 'wpe_adpage_password_placeholder', 'Password' ),
			'form_remember'        => get_option( 'wpe_adpage_form_remember', 'on' ),
			'remember_text'        => get_option( 'wpe_adpage_remember_text', 'Remember Me' ),
			'wrongpassword'        => get_option( 'wpe_adpage_wrongpassword', '' ),
			'errorlogin'           => get_option( 'wpe_adpage_errorlogin', '' ),
			'formbtn_text'         => get_option( 'wpe_adpage_formbtn_text', 'Login' ),
			'width'                => get_option( 'wpe_adpage_width', 'standard' ),
			'header_show'          => get_option( 'wpe_adpage_header_show', 'on' ),
			'comment'              => get_option( 'wpe_adpage_comment', '' ),
		];

		update_option( 'wpepp_member_template', wp_json_encode( $settings ) );
	}
}
