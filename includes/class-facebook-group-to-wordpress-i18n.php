<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       jameselks.com
 * @since      1.0.0
 *
 * @package    Facebook_Group_To_Wordpress
 * @subpackage Facebook_Group_To_Wordpress/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Facebook_Group_To_Wordpress
 * @subpackage Facebook_Group_To_Wordpress/includes
 * @author     James Elks <republicofelk@gmail.com>
 */
class Facebook_Group_To_Wordpress_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'facebook-group-to-wordpress',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
