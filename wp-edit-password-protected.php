<?php
/**
 * Main plugin bootstrap file.
 *
 * @package wpepp
 * @since   2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WPEPP – All In One WordPress Security, Firewall, Malware Scanner & Login Protection
 * Plugin URI:        http://wpthemespace.com
 * Description:       The complete All-In-One WordPress security suite: Web Application Firewall (WAF), malware scanner, login protection, 2FA, passkeys, bot shield & password protect.
 * Version:           3.0.0
 * Requires PHP:      7.4
 * Requires at least: 6.2
 * Author:            Noor alam
 * Author URI:        http://wpthemespace.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-edit-password-protected
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version.
 *
 * @var string
 */
define( 'WPEPP_VERSION', '3.0.0' );

/**
 * Main plugin file path.
 *
 * @var string
 */
define( 'WPEPP_FILE', __FILE__ );

/**
 * Plugin directory path.
 *
 * @var string
 */
define( 'WPEPP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @var string
 */
define( 'WPEPP_URL', plugins_url( '', __FILE__ ) );

// Autoload includes.
require_once WPEPP_PATH . 'includes/class-pro.php';
require_once WPEPP_PATH . 'includes/class-activator.php';
require_once WPEPP_PATH . 'includes/class-migration.php';
require_once WPEPP_PATH . 'includes/class-plugin.php';

// Activation hook.
register_activation_hook( __FILE__, [ 'WPEPP_Activator', 'activate' ] );

// Boot the plugin.
WPEPP_Plugin::instance();
