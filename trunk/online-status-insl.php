<?php
/**
Plugin Name: Online Status inSL
Plugin URI: https://gwynethllewelyn.net/online-status-insl/
Description: Shows your online status in the Second Life®/OpenSimulator virtual worlds on a widget (or use shortcodes/editor blocks)
Version: 1.6.1
Requires at least: 5.0
Requires PHP: 7.3
Author: Gwyneth Llewelyn
Author URI: https://gwynethllewelyn.net/
License: BSD-3-Clause
License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause
Text Domain: online-status-insl
Domain Path: /languages

Copyright 2011-2022 Gwyneth Llewelyn. Most rights reserved.

Some tweaks by SignpostMarv

`WP_List_Table` code adapted from WP Engineer, Matt Van Andel and Paul Underwood
`WP_Http` code adapted from planetOzh

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

	(1) Redistributions of source code must retain the above copyright
	notice, this list of conditions and the following disclaimer.

	(2) Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in
	the documentation and/or other materials provided with the
	distribution.

	(3)The name of the author may not be used to
	endorse or promote products derived from this software without
	specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

---

Based on code developed by Dave Doolin, http://website-in-a-weekend.net/extending-wordpress/wordpress-widget-plugin-super-easy-customization-tuesday-means-technical/

 * PHPDocumentor tags for this plugin below.
 *
 * @category OnlineStatusInSL
 * @package  OnlineStatusInSL
 * @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 * @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 * @version  1.6.1
 * @link     https://gwynethllewelyn.net/online-status-insl/
 */

define( 'NULL_KEY', '00000000-0000-0000-0000-000000000000' ); // always useful when playing around with SL-related code.
define( 'ONLINE_STATUS_INSL_MAIN_FILE', __FILE__ ); // needed by blocks/blocks.php (gwyneth 20210622) - unused (yet); will be used in 1.6.0+.
if ( ! class_exists( 'WP_Http' ) ) {
	include_once ABSPATH . WPINC . '/class-http.php';
}

// Include the two classes we're using.
require_once 'class-online-status-insl.php';
require_once 'class-online-status-insl-list-table.php';

/**
 *  Auxiliary generic functions.
 */

if ( ! function_exists( 'sanitise_avatarname' ) ) {
	/**
	 *  Sanitise avatar names.
	 *
	 *  Deal with avatars called 'SomethingOrOther Resident'
	 *  and sanitise the name by replacing spaces with dots.
	 *
	 *  @param string $avatar_name is the avatar's name to be sanitised.
	 *  @return string is a sanitised avatar name.
	 **/
	function sanitise_avatarname( $avatar_name ) {
		$sanitised = rawurlencode( strtolower( strtr( $avatar_name, ' ', '.' ) ) );
		// Check if 'Resident' is appended!
		$match = stripos( $sanitised, 'Resident' );
		if ( false !== $match ) {
			// Return everything up to the character before the dot.
			return substr( $sanitised, 0, $match - 1 );
		}
		return $sanitised;
	}
}

if ( ! function_exists( 'set_bold' ) ) {
	/**
	 *  Simple wrapper function to save some typing when placing <strong> around text.
	 *
	 *  @param string $text to wrap around with <strong>...</strong>.
	 *  @return string is the wrapped text.
	 */
	function set_bold( $text = '' ) {
		return '<strong>' . esc_attr( $text ) . '</strong>';
	}
}

/**
 *  Auxiliary plugin functions (outside any class).
 */

/**
 *  Init callback to register a widget for this plugin.
 *
 *  @return void
 */
function online_status_insl_widget_init() {
	register_widget( 'Online_Status_InSL' );
}

/**
 *  Activation callback to change options for a widget.
 *  In our case, we don't need to do anything special.
 *
 *  @return void
 */
function online_status_insl_widget_activate() {
	// no special options.
}

/**
 *  Deactivation callback to make post-deactivation changes, if needed.
 *  We have some cleaning up to do.
 *
 *  @return void
 */
function online_status_insl_widget_deactivate() {
	// First, clean up options on the settings database table.
	delete_option( 'online_status_insl_settings' );
	// _Then_ sanitise the rest.
	unregister_setting( 'Online_Status_InSL', 'online_status_insl_settings' );
}

/**
 *  Adding an item to the option menu from which the user will be able
 *  to use the functionalities of this plugin.
 *
 *  @return void
 */
function online_status_insl_admin_menu_options() {
	add_options_page(
		__( 'Online Status inSL', 'online-status-insl' ),
		__( 'Online Status inSL', 'online-status-insl' ),
		1,
		'Online_Status_InSL',
		'online_status_insl_menu'
	);
}

/**
 *  Function request_protocol() attempts to figure out if the WP site has been called via HTTPS or not.
 *
 *  @see https://stackoverflow.com/a/16076965/1035977
 *
 *  @return string 'https' or 'http'
 */
function request_protocol() {
	$is_secure = false;
	if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
		$is_secure = true;
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] || ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) {
		$is_secure = true;
	}
	return $is_secure ? 'https' : 'http';
}

/**
 *  Menu for the plugin backoffice.
 *
 *  @return void
 */
function online_status_insl_menu() {
	?>
<div class="wrap"> <!-- Plugin page for Online Status inSL -->
	<h2><?php esc_attr_e( 'Online Status inSL', 'online-status-insl' ); ?></h2>
	<p>
	<?php
	esc_attr_e(
		'Please create an object in Second Life on a plot owned by you, and drop the following script inside:',
		'online-status-insl'
	);
	?>
	</p>
	<hr />

	<?php
	// Figure out plugin version; if we have it already, skip this check (since it's resource-intensive).
	if ( ! Online_Status_InSL::$plugin_version ) {
		$plugin_data                        = get_file_data(
			__FILE__,
			array(
				'Version' => 'Version',
			)
		);
		Online_Status_InSL::$plugin_version = $plugin_data['Version'];
	}
	// Now spew the script; one day, this might be tied in with a pretty-formatting thingy!
	//
	// TODO(gwyneth): Check if llRequestSecureURL() works; a first approach could be to check if
	// we're inside SL and use llRequestSecureURL(); if in OpenSim, use llRequestURL().
	// Note: llRequestSecureURL() seems to be partially implemented in OpenSim these days (gwyneth 20220103).
	?>
	<textarea name="osinsl-lsl-script" cols="120" rows="12" readonly style="font-family: monospace;">
// Code to show online status and let it be retrieved by external calls.
// © 2011-<?php echo date( 'Y' ); // phpcs:ignore ?> by Gwyneth Llewelyn. Most rights reserved.
// Global Variables
key avatar;
string avatarName;
// Things we will receive from the dataserver
string onlineStatus = "status unknown"; // when the dataserver is slow, this will remain unset
string displayName = "(???)";
string dateBorn = "1970-01-01"; // avatar rezday; set to epoch if not retrieved
key onlineStatusRequest;		// to request items from the dataserver
key dateBornStatusRequest;
key displayNameStatusRequest;
key registrationResponse;		// to send the PermURL to the blog
key webResponse;				// to send periodic updates to the blog
string objectVersion = "<?php echo esc_attr( Online_Status_InSL::$plugin_version ); ?>";

// modified by SignpostMarv
string http_host = "<?php echo esc_attr( $_SERVER['HTTP_HOST'] ?? '<<unknown>>' ); ?>";

default
{
	state_entry()
	{
		llSetText("Waiting for data server...", <0.8, 0.1, 0.1>, 1.0);
		avatar = llGetOwner();
		avatarName = llKey2Name(avatar);
		llOwnerSay("Registering with your blog and retrieving user data from the SL dataserver...");
		onlineStatusRequest = llRequestAgentData(avatar, DATA_ONLINE);
		dateBornStatusRequest = llRequestAgentData(avatar, DATA_BORN);
		displayNameStatusRequest = llRequestAgentData(avatar, DATA_NAME);
		llSetText("Requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
		llRequestURL();	 // this sets the object up to accept external HTTP-in calls
	}

	on_rez(integer startParam)
	{
		llResetScript();
	}

	touch(integer howmany)	// Allow owner to reset this
	{
		if (llDetectedKey(0) == avatar)
		{
			llResetScript();
		}
	}

	timer()
	{
		// Every 60 seconds, retrieve the online status from the SL database server
		//	The other things will remain static (e.g. original name and date born)
		onlineStatusRequest = llRequestAgentData(avatar, DATA_ONLINE);
		llSetText(avatarName + " is " + onlineStatus, ZERO_VECTOR, 1.0);	// set the hover text

	}

	changed(integer what)
	{
		if (what & CHANGED_OWNER)
			llResetScript();	// make sure the new owner gets a fresh PermURL!
		if (what & (CHANGED_REGION | CHANGED_REGION_START | CHANGED_TELEPORT) )
		{
			llSetText("Requesting new PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
			llRequestURL();
		}
	}

	dataserver(key queryid, string data)
	{
		// this is what SL returns to us when we call llRequestAgentData()
		if (queryid == onlineStatusRequest) // check if it’s the correct request
		{
			string newOnlineStatus;

			if (data == "1")	// online status is just 0 or 1
				newOnlineStatus = "online";
			else
				newOnlineStatus = "offline";
			// did it change since the last time checked?
			if (onlineStatus != newOnlineStatus)
			{
				onlineStatus = newOnlineStatus;

				// call the blog with new online status
				string message =
					"action=status" +
					"&avatar_name=" + llEscapeURL(avatarName) +
					"&object_version=" + llEscapeURL(objectVersion) +
					"&dateBorn=" + dateBorn +
					"&status=" + llEscapeURL(onlineStatus);
				// llOwnerSay("DEBUG: Message to send to blog is: " + message);
				registrationResponse = llHTTPRequest("<?php echo esc_attr( request_protocol() ); ?>://" + http_host + "/wp-content/plugins/online-status-insl/save-channel.php",
				[HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
				message);
			}
		}
		else if (queryid == dateBornStatusRequest)
		{
			dateBorn = data;
		}
		else if (queryid == displayNameStatusRequest)
		{
			displayName = data;
		}
	}

	// This is just to catch that our website has the widget active
	http_response(key request_id, integer status, list metadata, string body)
	{
		if (request_id == registrationResponse)
		{
			if (status == 200)
			{
				llOwnerSay("PermURL sent to gateway! Msg. id is " + body);
			}
			else if (status == 499)
			{
				llOwnerSay("Timeout waiting for gateway! Your PermURL might still be sent, please be patient");
			}
			else
			{
				llOwnerSay("PermURL NOT sent. Status was " + (string)status + "; error message: " + body);
			}
			llSetText("", <0.0, 0.0, 0.0>, 1.0);
		}
		else if (request_id == webResponse)
		{
			if (status == 200)
			{
				llOwnerSay("Online status (" + onlineStatus + ") sent to blog! Msg. received is " + body);
			}
			else if (status == 499)
			{
				llOwnerSay("Timeout waiting for blog!");
			}
			else
			{
				llOwnerSay("Online status NOT sent. Request to blog returned " + (string)status + "; error message: " + body);
			}
		}
	}

	// These are requests made from our blog to this object
	http_request(key id, string method, string body)
	{
		if (method == URL_REQUEST_GRANTED)
		{
			llSetText("Sending PermURL to blog...", <0.6, 0.6, 0.1>, 1.0);

			string avatarName = llKey2Name(llGetOwner());
			string message =
				"action=register" +
				"&avatar_name=" + llEscapeURL(displayName) +
				"&object_version=" + llEscapeURL(objectVersion) +
				"&dateBorn=" + dateBorn +
				"&status=" + llEscapeURL(onlineStatus) +
				"&PermURL=" + llEscapeURL(body);
			// llOwnerSay("DEBUG: Message to send to blog is: " + message);
			registrationResponse = llHTTPRequest("<?php echo esc_attr( request_protocol() ); ?>://" + http_host + "/wp-content/plugins/online-status-insl/save-channel.php",
				[HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
				message);
			llSetTimerEvent(60.0);	// call a timer event every 60 seconds.
		}
		else if (method == "POST" || method == "GET")
		{
			if (body == "") // weird, no request
			{
				llHTTPResponse(id, 403, "Empty message received");
			}
			else if (llUnescapeURL(body) == "command=reset")
			{
				llHTTPResponse(id, 200, "Resetting...");
				llResetScript();
			}
			else if (llUnescapeURL(body) == "command=die")
			{
				llHTTPResponse(id, 200, "Deleting object forever...");
				llDie();
			}
			else // This includes ping
			{
				// compatibility with pre-1.3 versions
				// send stored message back
				llHTTPResponse(id, 200, onlineStatus);
			}
		}
	}
}
	</textarea>
	<hr />
	<p>
	<?php
	esc_attr_e(
		'Then you can drag the appropriate widget on your sidebar.',
		'online-status-insl'
	);
	?>
	<?php
		// Prepare the list of tracked objects.
		$my_list_table = new online_status_insl_List_Table();
		$my_list_table->prepare_items();
		// Note that this is inside the <p>...</p> because it emits \n somewhere!
	?>
	</p>
	<div class="wrap"> <!-- List of avatars being tracked. -->
		<h2><?php esc_attr_e( 'Current avatar(s) being tracked', 'online-status-insl' ); ?>:</h2>
		<form method="post">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ?? 1 ); ?>">
		<?php $my_list_table->display(); ?>
		</form>
		<div class="clear"></div>
		<div class="instructions">
		<?php
		// Add some instructions.
		_e(
			'<p><strong>Ping</strong>: Checks if in-world object is still alive; if not, you should remove it manually.</p>',
			'online-status-insl'
		);
		_e(
			'<p><strong>Delete</strong>: Removes the tracking object from the WordPress table. The object will still remain in-world unless it gets manually deleted. You can touch it in-world to get it to register again.</p>',
			'online-status-insl'
		);
		_e(
			'<p><strong>Reset</strong>: Resets the in-world object. This will give you a new communications channel. Note that if the object is not responding to pings, it will not be affected. The object will remain both in-world <em>and</em> being tracked by WordPress.</p>',
			'online-status-insl'
		);
		_e(
			'<p><strong>Destroy</strong>: Attempts to tell the in-world object to be deleted in-world <em>and</em> also deletes it from the WordPress table. If it fails, you will have to delete it manually in-world <em>and</em> delete it from this table as well. Use with caution, since the object will <em>not</em> be returned to your inventory but disappear forever!</p>',
			'online-status-insl'
		);
		_e(
			'<p>Note that the different options exist mostly to help you to keep your objects in sync with WordPress. Sometimes this is not possible. Note that the objects will try to contact WordPress with the updated status if they change owners, if the region simulator crashes, etc. Sometimes that can fail.</p>',
			'online-status-insl'
		);
		?>
		</div>
		<div class="clear"></div>
	</div><!-- end wrap for list -->
</div><!-- end wrap for whole plugin -->
<div class="clear"></div>
		<?php
} // end function online_status_insl_menu

/**
 *  Add a settings group, which hopefully makes it easier to delete later on.
 *
 *  @return void
 */
function online_status_insl_register_settings() {
	// it's a huge serialised array for now, stored as a WP option in the database;
	// if performance drops, this might change in the future.
	register_setting( 'Online_Status_InSL', 'online_status_insl_settings' );
}

/**
 * The [osinsl] shortcode.
 *
 * For now, we just have [osinsl avatar='AvatarName'] or [osinsl objectkey='<UUID>'].
 *
 * @param  string[]    $atts    Shortcode attributes. Default empty.
 * @param  string|null $content Shortcode content. Default null.
 * @param  string      $tag     Shortcode tag (name). Default empty.
 * @return string               Shortcode output.
 */
function online_status_insl_shortcode( $atts = array(), $content = null, $tag = '' ) {
	// error_log('Entering online_status_insl_shortcode, atts are ' . print_r($atts, true)); // debug.
	extract(
		shortcode_atts(
			array(
				'avatar'      => '(???)', // assigns $avatar to name if it exists, and provides a default of (???) which is supposed *not* to exist.
				'objectkey'   => NULL_KEY, // if there are multiple avatars with the same name, you need the object key instead.
				'picture'     => 'none', // emits picture tags, can be center/right/left/ etc.
				'status'      => 'on', // emits no status, just the picture (or nothing).
				'profilelink' => 'off', // puts links to web profile if picture active.
			),
			$atts
		)
	);
	// search for the avatar name.
	$settings = maybe_unserialize( get_option( 'online_status_insl_settings' ) );

	// figure out stupid id for nice formatting.
	$os_insl_id = 'broken'; // default: we assume it's broken until proven otherwise (gwyneth 20220103)!
	if ( ! empty( $avatar ) && ( '(???)' !== $avatar ) ) {
		$os_insl_id = strtolower( strtr( $avatar, ' ', '-' ) );
	} elseif ( ! empty( $objectkey ) && ( NULL_KEY !== $objectkey ) ) {
		$os_insl_id = $objectkey;
	}
	// Store things in a return value; add class attributes to allow styling.
	// Added esc_attr() in case someone creates an avatar name encoded with a XSS attack (gwyneth 20210621).
	$return_value =
		"<span class='osinsl-shortcode' id='osinsl-shortcode-" . esc_attr( $os_insl_id ) . "'>";
	?>
<!-- Avatar Name or Object Key: "<?php echo esc_attr( $os_insl_id ); ?>" -->
	<?php
	if ( ! empty( $settings ) && count( $settings ) > 0 ) {
		// did we find anything at all??
		// See if objectkey is set. If yes, instead of using avatar names, we use object UUIDs (guaranteed to
		// be unique, even across grids).

		$avatar_name_sanitised = ''; // to avoid assigning nulls (gwyneth 20220103).

		if ( ! empty( $objectkey ) && ( NULL_KEY !== $objectkey ) ) {
			if ( ! empty( $settings[ $objectkey ] ) ) {
				$avatar_name_sanitised = sanitise_avatarname(
					$settings[ $objectkey ]['avatarDisplayName']
				);
				if ( ! empty( $picture ) && ( 'none' !== $picture ) ) {
					if ( ! empty( $profilelink ) && ( 'off' !== $profilelink ) ) {
						// This will only work on OpenSimulator if there is an avatar with the same name in SL!
						$return_value .=
							"<a href='https://my.secondlife.com/" .
							$avatar_name_sanitised .
							"' target='_blank'>";
					}
					$return_value .=
						'<img class="osinsl-profile-picture align' .
						$picture .
						'" alt="' .
						$avatar_name_sanitised .
						'" title="' .
						$avatar_name_sanitised .
						'" src="https://my-secondlife.s3.amazonaws.com/users/' .
						$avatar_name_sanitised .
						'/thumb_sl_image.png" width="80" height="80" alt="' .
						$avatar .
						'" valign="bottom">';
					if ( ! empty( $profilelink ) && ( 'off' !== $profilelink ) ) {
						$return_value .= '</a>';
					}
				}
				if ( ! empty( $status ) && ( 'off' !== $status ) ) {
					$return_value .= $settings[ $objectkey ]['Status'];
				}
			} else {
				// no such object being tracked!
				$return_value .=
					__( 'Invalid object key: ', 'online-status-insl' ) . $objectkey;
			}
		} else {
			if ( ! empty( $avatar ) ) {
				$avatar_name_sanitised = sanitise_avatarname( $avatar );
			}
		}
		// Search through settings; retrieve first tracked object with this avatar name.

		$found_avatar = false;

		foreach ( $settings as $tracked_avatar ) {
			if ( ! empty( $tracked_avatar['avatarDisplayName'] )
				&& ( $avatar === $tracked_avatar['avatarDisplayName'] ) ) {
				if ( ! empty( $picture ) && ( 'none' !== $picture ) ) {
					if ( ! empty( $profilelink ) && ( 'off' !== $profilelink ) ) {
						$return_value .=
							"<a href='https://my.secondlife.com/" .
							$avatar_name_sanitised .
							"' target='_blank'>";
					}
					$return_value .=
						'<img class="osinsl-profile-picture align' .
						$picture ?? '' .
						'" alt="' .
						$avatar_name_sanitised .
						'" title="' .
						$avatar_name_sanitised .
						'" src="https://my-secondlife.s3.amazonaws.com/users/' .
						$avatar_name_sanitised .
						'/thumb_sl_image.png" width="80" height="80" alt="' .
						$avatar ?? '' .
						'" valign="bottom">';
					if ( ! empty( $profilelink ) && ( 'off' !== $profilelink ) ) {
						$return_value .= '</a>';
					}
				}
				if ( ! empty( $status ) && ( 'off' !== $status ) ) {
					$return_value .= $tracked_avatar['Status'] ?? __( '(unknown status)', 'online-status-insl' );
				}
				$found_avatar = true;
				break;
			}
		}
		if ( ! $found_avatar ) {
			$return_value .= __( 'No widget configured for ', 'online-status-insl' ) . $avatar;
		} else {
			$return_value .= __( 'No avatars being tracked', 'online-status-insl' );
		}
	}

	$return_value .= '</span>';

	return $return_value;
} // end function online_status_insl_shortcode

/**
 *  Deal with translations. British English and European Portuguese only for now.
 *
 *  @return void
 */
function online_status_insl_load_textdomain() {
	/*
	This is how it _used_ to work under WP < 4.6:
	error_log('Calling online_status_insl_load_textdomain(), locale is: "' . determine_locale() . '"');
	*/
	load_plugin_textdomain(
		'online-status-insl',
		false,
		basename( dirname( __FILE__ ) ) . '/languages/'
	);
} // end function online_status_insl_load_textdomain

/**
 *  Central location to create all shortcodes.
 *
 *  @return void
 */
function online_status_insl_shortcodes_init() {
	add_shortcode( 'osinsl', 'online_status_insl_shortcode' );
}

/**
 *  Main action/filter calls for this plugin.
 */

// error_log( 'Entering action/hook area...' ); // debug.
// add_filter( 'load_textdomain_mofile', 'online_status_insl_load_textdomain_mofile', 10, 2 ); // deprecated.
add_action( 'init', 'online_status_insl_load_textdomain' ); // load translations here.
add_action( 'widgets_init', 'online_status_insl_widget_init' );
add_action( 'admin_menu', 'online_status_insl_admin_menu_options' );
register_activation_hook( __FILE__, 'online_status_insl_widget_activate' );
register_deactivation_hook( __FILE__, 'online_status_insl_widget_deactivate' );
add_action( 'admin_init', 'online_status_insl_register_settings' );
add_action( 'init', 'online_status_insl_shortcodes_init' );
// error_log( 'Leaving action/hook area...' ); // debug.

$wpdpd = new Online_Status_InSL( 'online-status-insl', 'Online Status inSL' );
