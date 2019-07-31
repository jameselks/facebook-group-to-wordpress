'use strict';

jQuery(function() {
	jQuery('#status').append( '<p>Loading Facebook things.</p>' );
});

// This is called with the results from from FB.getLoginStatus().
function statusChangeCallback(response, doExchange) {
	
	// The response object is returned with a status field that lets the app know the current login status of the person.
	// Full docs on the response object can be found in the documentation for FB.getLoginStatus().
	
	if (response.status === 'connected') {
		// Logged into your app and Facebook.
		showStatus();
		if (doExchange) {
			exchangeToken();
		}

	} else if (response.status === 'not_authorized') {
		// The person is logged into Facebook, but not your app.
		document.getElementById('status').innerHTML = 'Please log ' + 'into this app.';

	} else {
		// The person is not logged into Facebook, so we're not sure if they are logged into this app or not.
		document.getElementById('status').innerHTML = 'Please log ' + 'into Facebook.';

	}
}

// This function is called when someone finishes with the Login Button.  See the onlogin handler attached to it in the sample code below.
function checkLoginState() {
	FB.getLoginStatus(function(response) {
		statusChangeCallback(response, true);
	});
}

window.fbAsyncInit = function() {

	jQuery('#status').append( '<p>Downloading the Facebook Javascript SDK.</p>' );
	jQuery('#status').append( '<p>Using app ID ' + fbgrpjs.fbAppId + '.</p>' );

	FB.init({
		appId      : fbgrpjs.fbAppId, //'490280357839488',
		cookie     : true,  // enable cookies to allow the server to access the session
		xfbml      : true,  // parse social plugins on this page
		version    : 'v4.0' // use graph api version 4.0
	});

	FB.getLoginStatus(function(response) {
		statusChangeCallback(response, false);
	});

};

// Load the SDK asynchronously
(function(d, s, id) {
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) return;
	js = d.createElement(s); js.id = id;
	js.src = '//connect.facebook.net/en_US/sdk.js';
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

// Here we run a very simple test of the Graph API after login is successful. See statusChangeCallback() for when this call is made.
function showStatus() {
	FB.api('/me', function(response) {
		jQuery('#status').append( '<p>Logged in &mdash; ' + response.name + '.</p>' );
	});
}

// Exchange the short-lived token for a long-lived token.
function exchangeToken() {
	jQuery('#status').append( '<p>Getting the long-lived token.</p>' );
	jQuery.ajax({
		type: 'post',
		url: ajaxurl,
		data: {
			'action': 'fbgrp_fb_tokenexchange'
		},
		success: function(result){
			jQuery('#status').append( '<p>Long-lived token: ' + result + '</p>' );
			jQuery('#status').append( '<p>Done!</p>' );
			location.reload();
		}
	});			

}
