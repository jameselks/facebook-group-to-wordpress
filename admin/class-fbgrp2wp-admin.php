<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       jameselks.com
 * @since      1.0.0
 *
 * @package    Fbgrp2wp
 * @subpackage Fbgrp2wp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fbgrp2wp
 * @subpackage Fbgrp2wp/admin
 * @author     James Elks <republicofelk@gmail.com>
 */
class Fbgrp2wp_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function fbgrp2wp_admin() {
		/*█████████████████████████████████████████████████████
		 * Create the admin settings.
		 *
		 * @since    1.0.0
		 */
	
			add_menu_page('Facebook Group to Wordpress settings', 'Facebook Group to Wordpress', 'manage_options', $this->plugin_name, array($this, 'fbgrp2wp_admin_page'), 'dashicons-admin-generic');
	
		}

	public function fbgrp2wp_admin_page() {
		/*█████████████████████████████████████████████████████
			* Display the admin settings page.
			*
			* @since    1.0.0
			*/
	
			echo require "partials/fbgrp2wp-admin-display.php";
	
		}
	
		public function fbgrp2wp_admin_settings() {
		/*█████████████████████████████████████████████████████
			* Register the admin settings.
			*
			* @since    1.0.0
			*/		
			register_setting( 'fbgrp2wp-group', 'fb_longtoken' );
			register_setting( 'fbgrp2wp-group', 'fb_app_id' );
			register_setting( 'fbgrp2wp-group', 'fb_app_secret' );
			register_setting( 'fbgrp2wp-group', 'fb_group_id' );
			register_setting( 'fbgrp2wp-group', 'fb_get_events' );
		}
	
		public function fbgrp2wp_admin_enqueue_styles() {
		/*█████████████████████████████████████████████████████
			* Register the stylesheets for the admin area.
			*
			* @since    1.0.0
			*/
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fbgrp2wp-admin.css', array(), $this->version, 'all' );
		}
	
		public function fbgrp2wp_admin_enqueue_scripts() {
		/*█████████████████████████████████████████████████████
			* Register the JavaScript for the admin area.
			*
			* @since    1.0.0
			*/
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fbgrp2wp-admin.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'fbgrp2wpjs', array( 'fbAppId' => get_option('fb_app_id') ) );
		}

		public function fbgrp2wp_log( $text, $timestamp = false ) {
			/*█████████████████████████████████████████████████████
			 * Write text to log file.
			 *
			 * @since    1.0.0
			 */	
		
				// Add timestamp
				if ($timestamp) {
					$text = '[' . current_time('Y-m-d h:i:sa') . '] ' . $text;	
				}
		
				$text = $text . PHP_EOL;
		
				if (!file_exists(ABSPATH . 'wp-content/uploads/fbgrp2wplog')) {
					wp_mkdir_p(ABSPATH . 'wp-content/uploads/fbgrp2wplog');
				}	
				file_put_contents(ABSPATH . 'wp-content/uploads/fbgrp2wplog/fbgrp2wplog_'.current_time('Y-m-d').'.txt', $text, FILE_APPEND);
			}

		public function fbgrp2wp_fb_tokenexchange() {
		/*█████████████████████████████████████████████████████
			* Exchange a short-lived Facebook authentication for a long-lived token.
			*
			* @since    1.0.0
			*/
			$fb = new Facebook\Facebook([
				'app_id' => get_option('fb_app_id'),
				'app_secret' => get_option('fb_app_secret'),
				'default_graph_version' => 'v4.0',
			]);
	
			$helper = $fb->getJavaScriptHelper();
	
			try {
				$accessToken = $helper->getAccessToken();
	
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				// When Graph returns an error
				do_action('fbgrp2wp_log', 'Token exchange - Facebook Graph returned an error: ' . $e->getMessage(), true);
				exit;
	
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				// When validation fails or other local issues
				do_action('fbgrp2wp_log', 'Token exchange - Facebook SDK returned an error: ' . $e->getMessage(), true);
				exit;
	
			}
	
			if (! isset($accessToken)) {
				//If cookie not set
				do_action('fbgrp2wp_log', 'No cookie set or no OAuth data could be obtained from cookie.', true);
				exit;
			}
	
			// OAuth 2.0 client handler
			$oAuth2Client = $fb->getOAuth2Client();
				
			// Exchanges a short-lived access token for a long-lived one
			$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
	
			// Add the long-lived token to the plugin options
			update_option( 'fb_longtoken', $accessToken );
	
			$_SESSION['fb_access_token'] = (string) $accessToken;
	
			// Log the successful token exchange.
			do_action('fbgrp2wp_log', 'Long-lived access token is ' . $accessToken . '.', true);

			// User is logged in!
			wp_die();
		}

		public function fbgrp2wp_insert_image($post_id, $cover_url) {
		/*█████████████████████████████████████████████████████
			* Download and add image as featured image to post.
			*
			* @since    1.0.0
			*/
	
			include_once(ABSPATH . "wp-includes/pluggable.php");
			include_once(ABSPATH . "wp-admin/includes/media.php");
			include_once(ABSPATH . "wp-admin/includes/file.php");
			include_once(ABSPATH . "wp-admin/includes/image.php");
	
			//Download the image from the specified URL and attach it to the post
			$media = media_sideload_image($cover_url, $post_id);
			
			//If successful, attach it as the cover image
			if ( !empty($media) && !is_wp_error($media) ){
				
				$args = array(
					'post_type' => 'attachment',
					'posts_per_page' => -1,
					'post_status' => 'any',
					'post_parent' => $post_id
					);
				$attachments = get_posts($args);
				
				if ( isset($attachments) && is_array($attachments) ) {
	
					foreach($attachments as $attachment){
	
						$cover_url = wp_get_attachment_image_src( $attachment->ID, 'full' );
	
						if ( strpos($media, $cover_url[0]) !== false ) {
							set_post_thumbnail($post_id, $attachment->ID);
							break;
						}
	
					}
				}
	
			}
	
			update_post_meta($post_id, 'fbgrp2wp_fb_cover', $cover_url);
		}
	
		public function fbgrp2wp_import_group_posts( $echo_results ) {
		/*█████████████████████████████████████████████████████
			* Import Facebook events.
			*
			* @since    1.0.0
			*/	
	
			do_action('fbgrp2wp_log', 'Start fbgrp2wp_process_events', true);
	
			if ($echo_results) {
				echo '<div id="progress"><p>Connecting to Facebook</p>';
				flush();
			}
	
			// Create the Facebook object.
			$fb = new Facebook\Facebook([
				'app_id' => get_option('fb_app_id'),
				'app_secret' => get_option('fb_app_secret'),
				'default_graph_version' => 'v4.0',
				'default_access_token' => get_option('fb_longtoken'),
			]);
	
			// Create the Facebook Graph request (but don't execute - that happens later).
			$request = $fb->request(
				'GET',
				get_option('fb_group_id') . '/feed',
				array(
					'fields' => 'updated_time,message,id,link,attachments,comments,likes,reactions',
					'limit' => get_option('fb_get_events')
				)
			);
	
			// Set the max script timeout to 20s from now
			set_time_limit(20);
	
			// Send the request to Graph.
			try {
				$response = $fb->getClient()->sendRequest($request);
	
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				// If Graph returns an error
				$fb_error = 'Facebook Graph returned an error: ' . $e->getMessage();
				do_action('fbgrp2wp_log', $fb_error, true);
				if($echo_results) {
					echo $fb_error;
				}
				exit;
	
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				// If validation fails or other local issues
				$fb_error = 'Facebook SDK returned an error: ' . $e->getMessage();
				do_action('fbgrp2wp_log', $fb_error, true);
				if($echo_results) {
					echo $fb_error;
				}
				exit;
	
			}
	
			// Process the Graph response.
			$events = $response->getGraphEdge();
			
			//Set some vars
			$totalEvents = count($events);
			$today = new DateTime(current_time('Y-m-d'));
			$before_today = false;
	
			//Cycle through each event
			foreach ($events as $index => $e) {
	
				if ($echo_results) {
					echo '<p>Processing event ' . (intval($index) + 1) . ' of ' . (intval($totalEvents)) . '<br />';
					flush();
				}
	
				// Set the max script timeout to 15s from now
				set_time_limit(15);
	
				$e_id = $e['id'];
				$e_name = $e['name'];
				$e_description = wp_strip_all_tags($e['description']);
				$e_cover = $e['cover']['source'];
				
				// Date stuff
				$e_start = $e['start_time']->format('Y-m-d g:ia'); //Format the start date
				$e_start_datetime = new DateTime($e_start); //Convert the start date into a PHP datetime object
				if ((date_diff($today,$e_start_datetime)->format('%r%a')) < -2) { //Set a flag to stop the loop for older events.
					$before_today = true;
					$log = $log . 'Processed ' . (intval($index) + 1) . ' of ' . (intval($totalEvents)) . ' events.' . PHP_EOL;
				}
				$e_updated = $e['updated_time']->format('Y-m-d g:ia');
	
				//Location name
				if ( !empty($e['place']['name']) ) {
						$e_location_name = $e['place']['name'];
				}
	
				//Location address
				if ( !empty($e['place']['location']['street']) && !empty($e['place']['location']['city']) ) {
					$e_location_address = $e['place']['location']['street'] .', ' . $e['place']['location']['city'];
				}
	
				$e_location = $e_location_name . ' &mdash; ' . $e_location_address;
	
				//Latitude and longitude
				$e_latitude = $e['place']['location']['latitude'];
				$e_longitude = $e['place']['location']['longitude'];
	
				// Try and geocode if there is a location name, but no lat/lng
				if (!empty($e_location_name) && empty($e_latitude) && empty($e_longitude)) {
						$log = $log . 'Attempting geocode for address: ' . $e_location_name . PHP_EOL;
						$log = $log . 'Facebook event ID: ' . $e_id . PHP_EOL;
						$geocode = apply_filters('fbgrp2wp_geocode_place', $e_location_name, get_option('radius'), array('lat'=>get_option('radius_lat'),'lng'=>get_option('radius_lng')), get_option('api_key_gp'));
						if (!empty($geocode)) {
							$log = $log . 'Geocode successful: ' . $geocode['lat'] . ', ' . $geocode['lng'] . PHP_EOL;
							$e_latitude = $geocode['lat'];
							$e_longitude = $geocode['lng'];
							$e_location = $e_location_name;
						} else {
							$log = $log . 'Geocode unsuccessful.' . PHP_EOL;
							$e_location = '';
						};
				};
	
				// The Query
				$args = array (
					'post_type' => 'fbgrp2wp_events',
					'posts_per_page' => -1,
					'meta_key' => 'fbgrp2wp_fb_id',
					'meta_query' => array(
						'key'		=> 'fbgrp2wp_fb_id',
						'value'		=> $e_id,
						),
					);
				$the_query = new WP_Query( $args );
	
				// The Loop - HAVE POSTS
				if ( $the_query->have_posts() ) {
					
					$do_update_meta = false;
	
					while ( $the_query->have_posts() ) {
						
						$the_query->the_post();
						$this_id = get_the_ID();
						
						//Don't update if 'stop_update' custom field is true
						if ( ! get_post_meta($this_id, 'fbgrp2wp_stop_update', true) ) {
						
							if ( get_post_meta($this_id, 'fbgrp2wp_fb_updated', true) !=  $e_updated ) {
								$this_event = array(
									'ID'			=> $this_id,
									'post_type'		=> 'fbgrp2wp_events',
									'post_title' 	=> wp_strip_all_tags($e_name),
									'post_content'	=> wp_strip_all_tags($e_description),
									'post_status'	=> 'publish',			
								);
								wp_update_post( $this_event );
								$do_update_meta = true;
							};
						
							$this_img = get_post_meta( $this_id, 'fbgrp2wp_fb_cover', true )[0];
	
							$e_cover_basename = explode('?', basename($e_cover));
							$e_cover_basename = reset($e_cover_basename);
							$this_img_basename = basename($this_img);				
							$e_cover_basename_noext = pathinfo($e_cover_basename, PATHINFO_FILENAME);
							
							if ( (strpos($this_img_basename, $e_cover_basename_noext) === false)  && !empty($e_cover)) {
								apply_filters('fbgrp2wp_insert_image', $this_id, $e_cover);
							}
	
						}
	
					}
				
				} else {
	
					$this_event = array(
						'post_type' 	=> 'fbgrp2wp_events',
						'post_name'		=> $e_id,
						'post_title' 	=> wp_strip_all_tags($e_name),
						'post_content' 	=> wp_strip_all_tags($e_description),
						'post_status' 	=> 'publish',
					);		
					$this_id = wp_insert_post($this_event);
					$do_update_meta = true;
	
					if ($e_cover != '') {
						apply_filters('fbgrp2wp_insert_image', $this_id, $e_cover);
					}
	
				} //END - HAVE POSTS
	
				//Update the post metadata.
				if ($do_update_meta) {
					update_post_meta($this_id, 'fbgrp2wp_source_url', 'https://www.facebook.com/events/' . $e_id);
					update_post_meta($this_id, 'fbgrp2wp_start', $e_start_datetime->format('Y-m-d g:ia'));
					update_post_meta($this_id, 'fbgrp2wp_location', $e_location);
					update_post_meta($this_id, 'fbgrp2wp_lat', $e_latitude);
					update_post_meta($this_id, 'fbgrp2wp_lng', $e_longitude);
					update_post_meta($this_id, 'fbgrp2wp_fb_id', $e_id);
					update_post_meta($this_id, 'fbgrp2wp_fb_updated', $e_updated);
				}
	
				if ($echo_results) {
					echo '</p>';
					echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
				}
				if ($before_today) {
					break;
				}
	
			} //END - EACH EVENT
	
			if ($echo_results) {
				echo '</div>';
			}
	
			do_action('fbgrp2wp_log', trim($log));
			do_action('fbgrp2wp_log', 'Finish fbgrp2wp_process_events' . PHP_EOL, true);
	
		}

}
