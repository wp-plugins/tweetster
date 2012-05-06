<?php 

require 'tmhOAuth/tmhOAuth.php';
require 'tmhOAuth/tmhUtilities.php';

/*
Plugin Name: Tweetster
Plugin URL: http://corybohon.com/tweetster/
Description: Automatically tweets new post titles and links to a Twitter account.
Version: 1.0
Author: Cory Bohon
Author URI: http://corybohon.com
License: MIT Open Source License (http://www.opensource.org/licenses/mit-license.php)
*/


/* LICENSE OF THE TWEETSTER PLUGIN

Copyright (c) 2010-2012 Cory Bohon.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

//Register Actions
add_action('publish_post', 'tweetster_onpublish');

//Register Hooks
register_activation_hook (__FILE__,'tweetster_install');
register_deactivation_hook(__FILE__, 'tweetster_remove' );


/*

	Function: tweetster_install
	
	Runs when the plugin is installed and creates the options for Tweetster.
	
	See Also:
		<tweetster_remove>

*/
function tweetster_install() 
{
	add_option("tweetster_consumer_key", 		'', 	'', 	'yes');
	add_option("tweetster_consumer_secret", 	'', 	'', 	'yes');
	add_option("tweetster_user_token", 			'', 	'', 	'yes');
	add_option("tweetster_token_secret", 		'', 	'', 	'yes');
	add_option("tweetster_bitly_login", 		'', 	'', 	'yes');
	add_option("tweetster_bitly_apikey", 		'', 	'', 	'yes');
}


/*

	Function: tweetster_remove 
	
	Runs when the plugin is removed from WordPress and deletes the plugin-owned options.
	
	See Also: 
		<tweetster_install> 

*/
function tweetster_remove()
{
	delete_option("tweetster_consumer_key", 	'', 	'', 	'yes');
	delete_option("tweetster_consumer_secret", 	'', 	'', 	'yes');
	delete_option("tweetster_user_token", 		'', 	'', 	'yes');
	delete_option("tweetster_token_secret", 	'', 	'', 	'yes');
	delete_option("tweetster_bitly_login", 		'', 	'', 	'yes');
	delete_option("tweetster_bitly_apikey", 	'', 	'', 	'yes');
}


//Display the plugin if the administrator is currently logged in.
if(is_admin())
{

	/* Call the html code */
	add_action('admin_menu', 'tweetster_admin_menu');

	function tweetster_admin_menu() 
	{
		add_options_page('Configure Tweetster', 'Configure Tweetster', 'administrator','tweetster-admin', 'tweetster_admin_page');
	}
}

function tweetster_admin_page() 
{
?>
	<div>
	<h2>Configure Tweetster</h2>
	To configure Tweetster for WordPress, fill in the required fields below. For information on how to set up your Twitter account for plugin access, please download the documentation by visiting the <a href="http://corybohon.com/tweetster" target="_blank">Tweetster support site</a>.
	
	<br /><br /><br />

	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>

	<h3>Configure Twitter</h3>
	<table width="400" border="0" cellpadding="0" cellspacing="2">
		<tr>
			<td><strong>Twitter Consumer Key: </strong></td>
			<td><input name="tweetster_consumer_key" type="text" id="tweetster_consumer_key" value="<?php echo get_option('tweetster_consumer_key'); ?>" /></td>
		</tr>
		<tr>
			<td><strong>Twitter Consumer Secret: </strong></td>
			<td><input name="tweetster_consumer_secret" type="text" id="tweetster_consumer_secret" value="<?php echo get_option('tweetster_consumer_secret'); ?>" /></td>
		</tr>
		<tr>
			<td><strong>Twitter User Token: </strong></td>
			<td><input name="tweetster_user_token" type="text" id="tweetster_user_token" value="<?php echo get_option('tweetster_user_token'); ?>" /></td>
		</tr>
		<tr>
			<td><strong>Twitter Token Secret: </strong></td>
			<td><input name="tweetster_token_secret" type="text" id="tweetster_token_secret" value="<?php echo get_option('tweetster_token_secret'); ?>" /></td>
		</tr>
	</table>
	
	<br />
	<br />
	
	<h3>Configure Bit.ly</h3>
	<table width="400" border="0" cellpadding="0" cellspacing="2">
		<tr>
		    <td><strong>Bit.ly Login</strong></td>
		    <td><input name="tweetster_bitly_login" type="text" id="tweetster_bitly_login" value="<?php echo get_option('tweetster_bitly_login'); ?>" /></td>
		</tr>
		<tr>
		    <td><strong>Bit.ly API Key: </strong></td>
		    <td><input name="tweetster_bitly_apikey" type="text" id="tweetster_bitly_apikey" value="<?php echo get_option('tweetster_bitly_apikey'); ?>" /></td>
		</tr>
	</table>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="tweetster_consumer_key, tweetster_consumer_secret, tweetster_user_token, tweetster_token_secret, tweetster_bitly_login, tweetster_bitly_apikey" />
	
	<p>
	<input type="submit" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
<?php
}


/*

	Function: tweetster_onpublish
	
	When a new post is published, this function is called, creating a new tweet for the newly published WordPress post.
	
	Parameters: 
	
		$postID - The WordPress Post ID
	

*/
function tweetster_onpublish($postID)
{
	$post = get_post($postID); 
	$post_title = $post->post_title;
	$post_title .= ' -';
	
	$url = get_permalink($postID);
	$short_url = tweetster_shortenlink($url);
	
	//check to make sure the tweet is within the 140 char limit
	//if not, shorten and place ellipsis and leave room for link. 
	if (strlen($post_title) + strlen($short_url) > 125)
	{
	   $total_len = strlen($post_title) + strlen($short_url);
	   $over_flow_count = $total_len - 125;
	   $post_title = substr($post_title,0,strlen($post_title) - $over_flow_count - 3);
	   $post_title .= '...';		
	}
	
	//add in the shortened bit.ly link
	$message = $post_title . " " . $short_url;

	//call the tweet function to tweet out the message
	tweetster_tweetnow($message);
}


/*

	Function: tweetster_shortenlink
	
	Shortens a link to the post.
	
	Parameters: 
	
		$postLink - the permalink to the post
		
	returns:
		A shortened link to the post, ready to be added to the message.
		
	See Also: 
		<tweetster_onpublish>
		<tweetster_tweetnow>

*/
function tweetster_shortenlink($postLink)
{
	$login = get_option('tweetster_bitly_login'); 
	$appkey = get_option('tweetster_bitly_apikey');
	$format = "txt";
	$connectURL = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($postLink).'&format='.$format;
	
	return file_get_contents($connectURL);
}

/*

	Function: tweetster_tweetnow
	
	Tweets the message for the post. 
	
	Parameters: 
		$message - the message to be tweeted
		
	See Also: 
		<tweetster_shortenlink>
		<tweetster_onpublish>

*/
function tweetster_tweetnow($message)
{

	$tmhOAuth = new tmhOAuth(array(
	  'consumer_key'    => get_option('tweetster_consumer_key'),
	  'consumer_secret' => get_option('tweetster_consumer_secret'),
	  'user_token'      => get_option('tweetster_user_token'),
	  'user_secret'     => get_option('tweetster_token_secret'),
	));
	
	$return_code = $tmhOAuth->request('POST', $tmhOAuth->url('1/statuses/update'), array(
  'status' => $message));
}

?>