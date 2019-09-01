<?php
define('ALLOW_UNFILTERED_UPLOADS', true);
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

		public function fbgrp2wp_insert_media($post_id, $post_attachments) {
		/*█████████████████████████████████████████████████████
			* Download and add image as featured image to post.
			*
			* @since    1.0.0
			*/

			//USE:
			//https://developer.wordpress.org/reference/functions/media_handle_sideload/

			do_action('fbgrp2wp_log', 'fbgrp2wp_insert_media - Start', true);

			include_once(ABSPATH . "wp-includes/pluggable.php");
			include_once(ABSPATH . "wp-admin/includes/media.php");
			include_once(ABSPATH . "wp-admin/includes/file.php");
			include_once(ABSPATH . "wp-admin/includes/image.php");

			foreach ($post_attachments as $i => $attachment) {
				
				// Set the max script timeout to 15s from now
				set_time_limit(15);		

				if ($attachment['subattachments']) {
					
					foreach ($attachment['subattachments'] as $k => $subattachment) {

						/*
						* Build the $file_array with
						* $url = the url of the image
						* $temp = storing the image in wordpress
						*/
						
						$url = $subattachment['media']['image']['src'];
						$tmp = download_url( $url );
						$path_noquery = explode("?", $url);
						$filename = basename($path_noquery[0]);

						$file_array = array(
							'name' => $filename,
							'tmp_name' => $tmp
						);
						
						// Check for download errors, if there are error unlink the temp file name
						if ( is_wp_error( $tmp ) ) {
							@unlink( $file_array[ 'tmp_name' ] );
							//return $tmp;
							echo 'ONE:';
							print_r($tmp);
						}
						
						/**
						 * now we can actually use media_handle_sideload
						 * we pass it the file array of the file to handle
						 * and the post id of the post to attach it to
						 * $post_id can be set to '0' to not attach it to any particular post
						 */			
						$id = media_handle_sideload( $file_array, $post_id );
						
						/**
						 * We don't want to pass something to $id
						 * if there were upload errors.
						 * So this checks for errors
						 */
						if ( is_wp_error( $id ) ) {
							@unlink( $file_array['tmp_name'] );
							//return $id;
							echo 'TWO:';
							print_r($id);
						}


					}

				} else {
					
					if($attachment['media']['source']){
						// Nothing
					}

				}

			}

			do_action('fbgrp2wp_log', 'fbgrp2wp_insert_media - Finish', true);
			
		}

		public function fbgrp2wp_insert_comments($post_id, $post_comments, $post_parent) {
			/*█████████████████████████████████████████████████████
				* Download and attach comments to the post.
				*
				* @since    1.0.0
				*/	
		
				do_action('fbgrp2wp_log', 'fbgrp2wp_insert_comments - Start', true);
	
				if (!$post_parent) {
					$post_parent = 0;
				}

				foreach ($post_comments as $i => $comment) {
					
					// Set the max script timeout to 15s from now
					set_time_limit(15);

					$comment_data = array(
						'comment_post_ID'	=> $post_id,
						'comment_author'	=> 'Unknown',
						'comment_content'	=> $comment['message'],
						'comment_date'		=> $comment['created_time']->format('Y-m-d g:ia'),
						'comment_parent' 	=> $post_parent,
						'comment_approved'	=> '1'
					);
					$comment_id = wp_insert_comment($comment_data);
					
					if ($comment['comments']) {
						apply_filters('fbgrp2wp_insert_comments', $post_id, $comment['comments'], $comment_id);
					}

				}

				do_action('fbgrp2wp_log', 'fbgrp2wp_insert_comments - End', true);
		}
		
		public function fbgrp2wp_import_group_posts( $echo_results ) {
		/*█████████████████████████████████████████████████████
			* Import Facebook events.
			*
			* @since    1.0.0
			*/	
	
			do_action('fbgrp2wp_log', 'fbgrp2wp_process_events - Start', true);
	
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
					'fields' => 'updated_time,message,id,link,attachments{media,subattachments,title},comments{id,created_time,message,comments{id,created_time,user_likes,message,comments}}',
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
			$posts = $response->getGraphEdge();
			
			//Set some vars
			$totalPosts = count($posts);
			$today = new DateTime(current_time('Y-m-d'));
	
			//Cycle through each post
			foreach ($posts as $index => $post) {
	
				if ($echo_results) {
					echo '<p>Processing post ' . (intval($index) + 1) . ' of ' . (intval($totalPosts)) . '<br />';
					flush();
				}
	
				// Set the max script timeout to 15s from now
				set_time_limit(15);
				
				// Basic fields
				$post_id 			= $post['id'];
				$post_link			= $post['link'];
				$post_message		= $post['message'];
				$post_attachments	= $post['attachments'];
				$post_comments		= $post['comments'];

				// If no attachments, then skip
				// *********** TURN INTO ADMIN OPTION
				if (!$post_attachments) { continue; }

				// Date
				$post_updated 	= $post['updated_time']->format('Y-m-d g:ia');				

				// Insert the new post
				$this_post = array(
					'post_type' 		=> 'fbgrp2wp_posts',
					'post_name'			=> $post_id,
					'post_date'			=> $post_updated,
					'post_title' 		=> wp_strip_all_tags($post_id),
					'post_content' 		=> wp_strip_all_tags($post_message),
					'comment_status'	=> 'closed',
					'post_status' 		=> 'publish',
				);		
				$this_id = wp_insert_post($this_post);

				// Add media to post
				if ($post_attachments) {
					apply_filters('fbgrp2wp_insert_media', $this_id, $post_attachments);
				}

				// Add comments to post
				if ($post_attachments) {
					apply_filters('fbgrp2wp_insert_comments', $this_id, $post_comments, 0);
				}	

				// Add metadata to post.
				update_post_meta($this_id, 'fbgrp2wp_fb_id', $post_id);
	
				if ($echo_results) {
					echo '</p>';
					echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
				}
	
			} //END - EACH POST
	
			if ($echo_results) {
				echo '</div>';
			}
	
			do_action('fbgrp2wp_log', trim($log));
			do_action('fbgrp2wp_log', 'fbgrp2wp_process_events - Finish' . PHP_EOL, true);
	
		}

}