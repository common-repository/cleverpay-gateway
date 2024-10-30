<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://helloclever.co/
 * @since             1.8.0
 * @package           CleverPay_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Hello Clever Gateway for Woocommerce
 * Plugin URI:        https://wordpress.org/plugins/cleverpay-gateway/
 * Description:       Universal open banking payment gateway.
 * Version:           1.8.0
 * Author:            Hello Clever PTY LTD
 * Author URI:        https://helloclever.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cleverpay-gateway
 * Domain Path:       /languages
 */

if ( ! defined( 'CLEVER_GATEWAY_URL' ) ) {
 define('CLEVER_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'CLEVER_GATEWAY_PATH' ) ) {
 define('CLEVER_GATEWAY_PATH', plugin_dir_path( __FILE__ ) );
}

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 6 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CLEVER_GATEWAY_VERSION', '1.8.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-clever-gateway-activator.php
 */
function activate_clever_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-clever-gateway-activator.php';
	Clever_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-clever-gateway-deactivator.php
 */
function deactivate_clever_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-clever-gateway-deactivator.php';
	Clever_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_clever_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_clever_gateway' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
include_once CLEVER_GATEWAY_PATH . '/includes/class-api.php';
include_once CLEVER_GATEWAY_PATH . '/includes/class-jwt.php';
include_once CLEVER_GATEWAY_PATH . '/includes/class-curl.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-clever-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.8.0
 */
function run_clever_gateway() {

	$plugin = new Clever_Gateway();
	$plugin->run();

}
run_clever_gateway();
