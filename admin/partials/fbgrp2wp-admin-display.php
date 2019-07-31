<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       jameselks.com
 * @since      1.0.0
 *
 * @package    Fbgrp2wp
 * @subpackage Fbgrp2wp/admin/partials
 */

if (isset($_POST['import'])) {
	do_action('fbgrp_import_feed', true);
}

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div id='fbgrp' class='wrap'>

	<h1>Facebook Group to Wordpress &mdash; Settings</h1>
	<form method='post' action='options.php' id="fbgrp-settings">
		<?php settings_fields( 'fbgrp-group' ); ?>
		<?php do_settings_sections( 'fbgrp-group' ); ?>
		<div class="form-table">
			<h2>Facebook app credentials</h2>
			<p>Create a new Facebook app at <a href="https://developers.facebook.com/">Facebook for developers</a>, add your domain and copy your app ID and app secret into the fields below.</p>
			<div>
				<label for="fb_app_id">Facebook App ID</label>
				<input type='text' name='fb_app_id' id='fb_app_id' value='<?php echo esc_attr( get_option('fb_app_id') ); ?>' />
			</div>
			<div>
				<label for="fb_app_secret">Facebook App Secret</label>
				<input type='text' name='fb_app_secret' id='fb_app_secret' value='<?php echo esc_attr( get_option('fb_app_secret') ); ?>' />
			</div>			
			<div>
				<label for="fb_longtoken">Facebook long-lived token</label>
				<span>Don't edit this field. Generate a new long-lived token automatically by selecting the Facebook 'Log in' button at the bottom of this settings page.</span>
				<input type='text' name='fb_longtoken' id='fb_longtoken' value='<?php echo esc_attr( get_option('fb_longtoken') ); ?>' />
			</div>
			<div>
				<label for="fb_get_events">Number of Facebook events to request</label>
				<span>More events leads to a slower request time. Less than 200 recommended. Use the <a href="https://developers.facebook.com/tools/explorer/">Facebook Graph API Explorer</a> to check request time.
				<input type='text' name='fb_get_events' id='fb_get_events' value='<?php echo esc_attr( get_option('fb_get_events') ); ?>' />
			</div>
		</div>
		<?php submit_button( 'Save settings', 'primary', 'save', false ); ?>
	</form>

	<form action="" method="post" id="fbgrp-import">
		<?php submit_button( 'Import Facebook Group feed now', 'secondary', 'import', false ); ?>
	</form>

	<h2>Facebook Connector</h2>
	<div id="status"></div>
	<fb:login-button scope="public_profile,email,user_events" onlogin="checkLoginState();"></fb:login-button>

</div>
