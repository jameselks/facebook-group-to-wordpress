<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       jameselks.com
 * @since      1.0.0
 *
 * @package    Fbgrp2wp
 * @subpackage Fbgrp2wp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Fbgrp2wp
 * @subpackage Fbgrp2wp/public
 * @author     James Elks <republicofelk@gmail.com>
 */
class Fbgrp2wp_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function fbgrp2wp_register_styles() {
	/*█████████████████████████████████████████████████████
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */

		wp_register_style( $this->plugin_name . '-css', plugin_dir_url( __FILE__ ) . 'css/fbgrp2wp-public.css', array(), $this->version, 'all' );

	}

	public function fbgrp2wp_register_scripts() {
	/*█████████████████████████████████████████████████████
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */

		wp_register_script( $this->plugin_name . '-js', plugin_dir_url( __FILE__ ) . 'js/fbgrp2wp-public.js', array( 'jquery' ), $this->version, false );

	}

	public function fbgrp2wp_create_post_type() {
	/*█████████████████████████████████████████████████████
	 * Create the custom post type.
	 *
	 * @since    1.0.0
	 */

		register_post_type( 
			'fbgrp2wp_posts',
			array(
				'labels' => array(
					'name' 			=> __( 'Facebook Group posts' ),
					'singular_name'	=> __( 'Facebook Group post' )
					),
				'public' 	=> true,
				'show_ui'	=> true,
				'supports' 	=> array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'custom-fields' ),
				'has_archive' => true,
				)
			);
	}

}
