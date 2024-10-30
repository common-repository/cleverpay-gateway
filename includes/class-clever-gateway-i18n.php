<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://helloclever.co/
 * @since      1.8.0
 *
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.8.0
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/includes
 * @author     Hello Clever PTY LTD <support@helloclever.co>
 */
class Clever_Gateway_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.8.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'clever-gateway',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
