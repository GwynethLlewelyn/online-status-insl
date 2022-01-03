<?php
/*
Plugin Name: Online Status inSL
Plugin URI: https://gwynethllewelyn.net/online-status-insl/
Description: Shows your online status in the Second Life®/OpenSimulator virtual worlds on a widget (or use shortcodes)
Version: 1.5.0
Requires at least: 4.6
Requires PHP: 7.3
Author: Gwyneth Llewelyn
Author URI: https://gwynethllewelyn.net/
License: BSD-3-Clause
License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause
Text Domain: online-status-insl

Copyright 2011-2021 Gwyneth Llewelyn. Most rights reserved.

Some tweaks by SignpostMarv

WP_List_Table code adapted from WP Engineer, Matt Van Andel and Paul Underwood
WP_Http code adapted from planetOzh

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

*/

define("NULL_KEY", "00000000-0000-0000-0000-000000000000");

if (!class_exists("WP_Http")) {
	include_once ABSPATH . WPINC . "/class-http.php";
}

/*
if( ! function_exists('get_plugin_data') ){
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
*/

/**
 * Sanitise avatar names.
 *
 * Deal with avatars called "SomethingOrOther Resident"
 *	 and sanitise the name by replacing spaces with dots.
 *
 * @param string avatarName is the avatar's name to be sanitised
 * @return string a sanitised avatar name
 **/
if (!function_exists("sanitise_avatarname")) {
	function sanitise_avatarname($avatarName)
	{
		$sanitised = rawurlencode(strtolower(strtr($avatarName, " ", ".")));
		// check if 'Resident' is appended
		if (($match = stripos($sanitised, "Resident")) !== false) {
			// return everything up to the character before the dot
			return substr($sanitised, 0, $match - 1);
		} else {
			return $sanitised;
		}
	}
}

/**
 * 	Simple wrapper function to save some typing when placing <strong> around text.
 *
 *  @param string text to wrap around with <strong>...</strong>
 *  @return string wrapped text
 */
if (!function_exists("set_bold")) {
	function set_bold($text = '') {
		return "<strong>" . $text . "</strong>";
	}
}

// Class to create fancy lists
if (!class_exists("Online_Status_inSL_List_Table")) {
	// 1.4.0 includes WP_List_Table to manage the list of objects. Duh! So much easier! Warning: this class is
	//  not "plugin-developer" friendly and might disappear in the future
	if (!class_exists("WP_List_Table")) {
		require_once ABSPATH . "wp-admin/includes/class-wp-list-table.php";
	}

	class Online_Status_inSL_List_Table extends WP_List_Table
	{
		var $internalSettings = []; // temporary internal variable for copying settings

		function __construct()
		{
			global $status, $page;

			parent::__construct([
				"singular"	=> __("avatar status tracking object", "online-status-insl"), //singular name of the listed records
				"plural"	=> __("avatar status tracking objects", "online-status-insl"), //plural name of the listed records
				"ajax"		=> false, //does this table support ajax?
			]);

			// check if we have saved the plugin version on the plugin class; if not, do it now
			//  it's a global static var for ease of use
			if (!online_status_insl::$plugin_version) {
				$plugin_data =  get_file_data( __FILE__, array(
					'Version' => 'Version'
				) );
				online_status_insl::$plugin_version = $plugin_data["Version"];
			}

			// get the plugin settings (table with all entries) and store it internally
			$this->internalSettings = maybe_unserialize(
				get_option("online_status_insl_settings")
			);
		}

		function get_bulk_actions()
		{
			$actions = [
				"ping"		=> __("Ping object", "online-status-insl"),
				"delete"	=> __("Delete from tracking table", "online-status-insl"),
				"reset"		=> __("Reset object(s)", "online-status-insl"),
				"die"		=> __("Destroy object forever", "online-status-insl"),
			];
			return $actions;
		}

		// Helper function. Not part of WP_List_Table
		public static function emit_status_message($type = "info", $text = '')
		{
			if (empty($text)) {
				$text = __("Empty message.", "online-status-insl");
			}
			printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr($type), $text);
		}

		function process_bulk_action()
		{
			// Skip processing if we have no requests (apparently this function gets called every time
			//  the page is called
			if (!empty($_REQUEST["deletedStatusIndicators"])) {
				// check if it's an array or a single request
				//  Single request: user clicked below the avatar picture
				//  Array: this was a bulk request, and we have several UUIDs
				if (is_array($_REQUEST["deletedStatusIndicators"])) {
					$processIDs = $_REQUEST["deletedStatusIndicators"];
				} else {
					$processIDs = [$_REQUEST["deletedStatusIndicators"]];
				}

				// Check if we got one of the valid actions
				//  Note that there is no "default" action. Default is doing nothing.
				if ("ping" === $this->current_action()) {
					// ping is an 'undefined' message to the object; even old objects should be able to reply
					//  because we have somehow programmed that in advance, for future expansion
					foreach ($processIDs as $pingTrackingObject) {
						$statusMessage =
							__("Pinging object for ", "online-status-insl") .
							$this->internalSettings[$pingTrackingObject][
								"avatarDisplayName"
							] .
							", " .
							set_bold(__("Object Name: ", "online-status-insl")) .
							$this->internalSettings[$pingTrackingObject]["objectName"] .
							" (" .
							$this->internalSettings[$pingTrackingObject]["objectKey"] .
							"), " .
							set_bold(__("Location: ", "online-status-insl")) .
							$this->internalSettings[$pingTrackingObject]["objectRegion"] .
							"<br />" .
							set_bold(__("Calling URL: ", "online-status-insl")) .
							$this->internalSettings[$pingTrackingObject]["PermURL"] .
							"<br />";
						self::emit_status_message("info", $statusMessage);

						// Get PermURL and contact the object
						$request = new WP_Http();
						$result = $request->request(
							$this->internalSettings[$pingTrackingObject]["PermURL"],
							["method" => "POST", "body" => ["command" => "ping"]]
						);

						if ($result["response"]["code"] == "200") {
							self::emit_status_message(
								"success",
								sprintf(
									__("Object '%s' replied: '%s'", "online-status-insl"),
									$this->internalSettings[$pingTrackingObject]["objectName"],
									$result["body"]
								)
							);
						} else {
							self::emit_status_message(
								"error",
								__(
									"Error communicating with in-world object ",
									"online-status-insl"
								) .
									$pingTrackingObject .
									": " .
									$result["response"]["code"] .
									" - " .
									$result["response"]["message"] .
									" [" .
									$result["body"] .
									"]"
							);
						}
					}
				} elseif ("delete" === $this->current_action()) {
					// in this case, $_REQUEST["deletedStatusIndicators"] gives us a list of items to delete

					$statusMessage = ""; // add to this string as we find objects to delete

					// Theoretically, all we need to do is to cycle $_REQUEST["deletedStatusIndicators"] and since these
					//  are objectKeys, we should be able to simply unset the appropriate element
					//  This will mean that previous versions of the settings might really be lost (= "undeletable")
					foreach ($processIDs as $deleteTrackingObject) {
						$statusMessage .=
							__("Deleting ", "online-status-insl") .
							$this->internalSettings[$deleteTrackingObject][
								"avatarDisplayName"
							] .
							", " .
							set_bold(__("Object Name: ", "online-status-insl")) .
							$this->internalSettings[$deleteTrackingObject]["objectName"] .
							" (" .
							$this->internalSettings[$deleteTrackingObject]["objectKey"] .
							"), " .
							set_bold(__("Location: ", "online-status-insl")) .
							$this->internalSettings[$deleteTrackingObject]["objectRegion"] .
							"<br />";

						// Get rid of it from our internal table
						unset($this->internalSettings[$deleteTrackingObject]);
					}

					// emit "info" class showing we have deleted some things; error if we haven't managed to delete anything
					if ($statusMessage) {
						self::emit_status_message("warning", $statusMessage);
					}
					// endif ($statusMessage)
					else {
						self::emit_status_message(
							"error",
							sprintf(
								__(
									"No online status indicators for %s found",
									"online-status-insl"
								),
								print_r($_REQUEST["deletedStatusIndicators"], true)
							)
						);
					}

					// update options with new settings; gets serialized automatically
					if (
						!update_option(
							"online_status_insl_settings",
							$this->internalSettings
						)
					) {
						self::emit_status_message(
							"error",
							set_bold(__(
								"WordPress settings could not be saved - Object(s) not deleted!"),
								"online-status-insl"
							)
						);
					} // endif update options
				}
				// endif delete
				elseif ("reset" === $this->current_action()) {
					// Send reset command to inworld object. Object will do a llResetScript() and attempt
					//  to register again. There is a slight bug here, because somehow the settings are
					//  not saved (maybe there is a lock on them?)
					foreach ($processIDs as $resetTrackingObject) {
						$statusMessage =
							__("Resetting object for ", "online-status-insl") .
							$this->internalSettings[$resetTrackingObject][
								"avatarDisplayName"
							] .
							", " .
							set_bold(__("Object Name: ", "online-status-insl")) .
							$this->internalSettings[$resetTrackingObject]["objectName"] .
							" (" .
							$this->internalSettings[$resetTrackingObject]["objectKey"] .
							"), " .
							set_bold(__("Location: ", "online-status-insl")) .
							$this->internalSettings[$resetTrackingObject]["objectRegion"] .
							"<br />" .
							set_bold(__("Calling URL: ", "online-status-insl")) .
							$this->internalSettings[$resetTrackingObject]["PermURL"] .
							"<br />";
						self::emit_status_message("info", $statusMessage);

						// Get PermURL and contact it
						$request = new WP_Http();
						$result = $request->request(
							$this->internalSettings[$resetTrackingObject]["PermURL"],
							["method" => "POST", "body" => ["command" => "reset"]]
						);

						// For some reason, we always get an error, although the communication worked!
						//  Maybe a good idea is to read the settings again? Or save them?
						if ("200" == $result["response"]["code"]) {
							self::emit_status_message(
								"success",
								sprintf(
									__("Object %s replied: %s", "online-status-insl"),
									$this->internalSettings[$resetTrackingObject]["objectName"],
									$result["body"]
								)
							);
						} else {
							self::emit_status_message(
								"error",
								__(
									"Error communicating with in-world object ",
									"online-status-insl"
								) .
									$resetTrackingObject .
									": " .
									$result["response"]["code"] .
									" - " .
									$result["response"]["message"] .
									" [" .
									$result["body"] .
									"]"
							);
						}
					}
				}
				// endif reset
				elseif ("die" === $this->current_action()) {
					// Send llDie() command to in-world object (it will disappear forever) and
					//  we need to delete them from our table too
					foreach ($processIDs as $dieTrackingObject) {
						$statusMessage =
							__("Deleting in-world object for ", "online-status-insl") .
							$this->internalSettings[$dieTrackingObject]["avatarDisplayName"] .
							", " .
							set_bold(__("Object Name: ", "online-status-insl")) .
							$this->internalSettings[$dieTrackingObject]["objectName"] .
							" (" .
							$this->internalSettings[$dieTrackingObject]["objectKey"] .
							"), " .
							set_bold(__("Location: ", "online-status-insl")) .
							$this->internalSettings[$dieTrackingObject]["objectRegion"] .
							"<br />" .
							set_bold(__("Calling URL: ", "online-status-insl")) .
							$this->internalSettings[$dieTrackingObject]["PermURL"] .
							"<br />";
						self::emit_status_message("info", $statusMessage);

						// Get PermURL and send it the killing message
						$request = new WP_Http();
						$result = $request->request(
							$this->internalSettings[$dieTrackingObject]["PermURL"],
							["method" => "POST", "body" => ["command" => "die"]]
						);

						if ("200" == $result["response"]["code"]) {
							self::emit_status_message(
								"success",
								sprintf(
									__("Object %s replied: %s", "online-status-insl"),
									$this->internalSettings[$dieTrackingObject]["objectName"],
									$result["body"]
								)
							);

							// now get rid of the object in the settings table
							unset($this->internalSettings[$dieTrackingObject]);
						} else {
							self::emit_status_message(
								"error",
								__(
									"Object not deleted because there was an error communicating with it ",
									"online-status-insl"
								) .
									$dieTrackingObject .
									": " .
									$result["response"]["code"] .
									" - " .
									$result["response"]["message"] .
									" [" .
									$result["body"] .
									"]"
							);
						}
					}
					// Objects might have been deleted, so now it's time to purge them from the WP settings
					if (
						!update_option(
							"online_status_insl_settings",
							$this->internalSettings
						)
					) {
						self::emit_status_message(
							"error",
							set_bold(__(
								"WordPress settings could not be saved - Object(s) not deleted!",
								"online-status-insl"
							) )
						);
					} // endif update options
				} // endif die
			}
			// no valid parameters for bulk actions! But this seems to be normal!
			else {
				// self::emit_status_message("error", __("No bulk actions to process", 'online-status-insl'));
			}
		} // end function process_bulk_actions

		function column_cb($item)
		{
			return sprintf(
				'<input type="checkbox" name="deletedStatusIndicators[]" value="%s" />',
				$item["objectKey"]
			);
		}

		function get_columns()
		{
			$columns = [
				"cb" 				=> '<input type="checkbox" />',
				"avatarDisplayName"	=> __("Avatar Display Name", "online-status-insl"),
				"Status"			=> __("Status", "online-status-insl"),
				"PermURL"			=> __("PermURL", "online-status-insl"),
				"avatarKey"			=> __("Avatar Key", "online-status-insl"),
				"objectName"		=> __("Object Name", "online-status-insl"),
				"objectKey"			=> __("Object Key", "online-status-insl"),
				"objectVersion"		=> __("Object Version", "online-status-insl"),
				"objectRegion"		=> __("Location", "online-status-insl"),
				"timeStamp"			=> __("Last time checked", "online-status-insl"),
			];
			return $columns;
		}

		// Display special columns

		function column_avatarDisplayName($item)
		{
			$avatarNameSanitised = sanitise_avatarname($item["avatarDisplayName"]);

			// This column will also have options to affect the in-world object
			$actions = [
				"ping" => sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$_REQUEST["page"],
					"ping",
					$item["objectKey"],
					__("Ping", "online-status-insl")
				),
				"delete" => sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$_REQUEST["page"],
					"delete",
					$item["objectKey"],
					__("Delete", "online-status-insl")
				),
				"reset" => sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$_REQUEST["page"],
					"reset",
					$item["objectKey"],
					__("Reset", "online-status-insl")
				),
				"die" => sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$_REQUEST["page"],
					"die",
					$item["objectKey"],
					__("Destroy", "online-status-insl")
				),
			];

			return '<a href="https://my.secondlife.com/' .
				$avatarNameSanitised .
				'" target="_blank">' .
				$item["avatarDisplayName"] .
				'<img src="https://my-secondlife.s3.amazonaws.com/users/' .
				$avatarNameSanitised .
				'/thumb_sl_image.png" width="80" height="80" alt="' .
				$item["avatarDisplayName"] .
				'" title="' .
				$item["avatarDisplayName"] .
				'" class="aligncenter" valign="bottom"></a>' .
				$this->row_actions($actions);
		}

		function column_objectRegion($item)
		{
			// parse name of the region and coordinates to create a link to maps.secondlife.com
			$regionName = substr(
				$item["objectRegion"],
				0,
				strpos($item["objectRegion"], "(") - 1
			);
			$coords = trim($item["objectLocalPosition"], "() \t\n\r");
			$xyz = explode(",", $coords);

			return sprintf(
				'<a href="https://maps.secondlife.com/secondlife/%s/%F/%F/%F?title=%s&amp;msg=%s&amp;img=%s" target="_blank">%s (%d,%d,%d)</a>',
				$regionName,
				$xyz[0],
				$xyz[1],
				$xyz[2],
				rawurlencode($item["objectName"]),
				rawurlencode(
					__("Online Status Indicator for ", "online-status-insl") .
						$item["avatarDisplayName"]
				),
				rawurlencode(
					"https://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png"
				),
				$regionName,
				$xyz[0],
				$xyz[1],
				$xyz[2]
			);
		}

		function column_objectVersion($item)
		{
			// Added some fancy visuals: green if the plugin is at the same version as the in-world object
			// Versions 1.3.X are considered "safe" (same protocol) so they're coloured a dark yellow
			// Anything before that might be still tracked, but communications will be lost with it
			// The hope is that users upgrade the script manually if they have some visual feedback
			if ($item["objectVersion"] == online_status_insl::$plugin_version) {
				$obv_css_colour = "DarkGreen";
			} elseif (
				in_array($item["objectVersion"], [
					"1.3.0",
					"1.3.5",
					"1.3.6",
					"1.3.7",
					"1.3.8",
					"1.4.0",
					"1.4.1",
					"1.4.2"
				])
			) {
				$obv_css_colour = "DarkGoldenRod";
			} else {
				$obv_css_colour = "Maroon";
			}
			return "<span style='color:" .
				$obv_css_colour .
				";'>" .
				$item["objectVersion"] .
				"</span>";
		}

		function column_Status($item)
		{
			// Just some fancy colouring. Note that only online/offline are valid status. If the object
			//  breaks but still communicates, it might send a different status (rare!)
			if ($item["Status"] == "online") {
				$obs_css_colour = "DarkGreen";
			} elseif ($item["Status"] == "offline") {
				$obs_css_colour = "Maroon";
			} else {
				$obs_css_colour = "DimGray";
			} // unknown status

			return "<span style='color:" .
				$obs_css_colour .
				";'>" .
				$item["Status"] .
				"</span>";
		}

		function column_timeStamp($item)
		{
			return date(__("Y M j H:i:s", "online-status-insl"), $item["timeStamp"]);
		}

		// Display normal columns
		function column_default($item, $column_name)
		{
			return $item[$column_name];
		}

		// sortable columnns

		function get_sortable_columns()
		{
			// Maybe other columns should be sorted too. These are the more useful ones.
			$sortable_columns = [
				"avatarDisplayName" => ["avatarDisplayName", false],
				"objectRegion"		=> ["objectRegion", false],
				"timeStamp"			=> ["timeStamp", false],
			];
			return $sortable_columns;
		}

		function usort_reorder($a, $b)
		{
			// If no sort, default to avatarDisplayName
			$orderby = !empty($_GET["orderby"])
				? $_GET["orderby"]
				: "avatarDisplayName";
			// If no order, default to asc
			$order = !empty($_GET["order"]) ? $_GET["order"] : "asc";
			// Determine sort order
			$result = strcmp($a[$orderby], $b[$orderby]);
			// Send final sort direction to usort
			return $order === "asc" ? $result : -$result;
		}

		function prepare_items()
		{
			$columns = $this->get_columns();
			$hidden = [];
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = [$columns, $hidden, $sortable];

			// $settings might have changed in the mean time due to bulk actions
			$this->internalSettings = maybe_unserialize(
				get_option("online_status_insl_settings")
			);

			// now process bulk actions
			$this->process_bulk_action();

			// sort our data, if it exists (to avoid an error)
			if (!empty($this->internalSettings)) {
				usort($this->internalSettings, [&$this, "usort_reorder"]);
			}
			// assign sorted data to items and hope it works!
			$this->items = $this->internalSettings;
		}

		function no_items()
		{
			_e("No avatars are being tracked.", "online-status-insl");
		}
	} // class Online_Status_inSL_List_Table
} // if !class_exists("Online_Status_inSL_List_Table")

/*
 *
 *	Widget class for this plugin starts here
 *
 */
if (!class_exists("online_status_insl")) {
	class online_status_insl extends WP_Widget
	{
		public static $plugin_version;

		function __construct()
		{
			// This might never be called, so it gets updated from other locations too
			if (!online_status_insl::$plugin_version) {
				$plugin_data =  get_file_data( __FILE__, array(
        			'Version' => 'Version'
    			) );
				online_status_insl::$plugin_version = $plugin_data["Version"];
			}
			$osinsl_widget_ops = [
				"classname" => "online_status_insl",
				"description" => __("Online Status inSL", "online-status-insl"),
			];
			parent::__construct("online_status_insl_widget",
				__("Online Status inSL Widget", "online-status-insl"),
				$osinsl_widget_ops
			);
		}

		/* This is the code that gets displayed on the UI side,
		 * what readers see.
		 */
		public function widget($args, $instance)
		{
			extract($args, EXTR_SKIP);
			echo $before_widget;
			$title = empty($instance["title"])
				? "&nbsp;"
				: apply_filters("widget_title", $instance["title"]);

			if (!empty($title)) {
				echo $before_title, $title, $after_title;
			}

			// Code to check the in-world thingy and spew out results

			// Get list of tracked avatars from the WordPress settings

			$settings = maybe_unserialize(get_option("online_status_insl_settings"));

			// Objects in settings are now indexed by object key
			$objectKey = $instance["object_key"] ?: NULL_KEY;

			// To get the avatar name for this object, we need to use its key:
			$avatarDisplayName = $settings[$objectKey]["avatarDisplayName"] ?: __("(unknown avatar)", "online_status_insl");
			// Similar for PermURL:
			$PermURL = $settings[$objectKey]["PermURL"] ?: __("(invalid URL)", "online_status_insl");
?>
	<div class="osinsl">
<?php
			// See if the user wants us to place a profile picture for the avatar
			// Note that we're using the default alignleft, aligncenter, alignright classes for WP
			if (!empty($instance["profile_picture"]) && $instance["profile_picture"] != "none") {
				$avatarNameSanitised = sanitise_avatarname($avatarDisplayName);
?>
		<a href="https://my.secondlife.com/<?php echo $avatarNameSanitised; ?>" target="_blank">
			<img class="osinsl-profile-picture align<?php echo $instance["profile_picture"]; ?>"
				src="https://my-secondlife.s3.amazonaws.com/users/<?php echo $avatarNameSanitised; ?>/thumb_sl_image.png"
				width="80" height="80"
				alt="<?php echo $avatarDisplayName; ?>"
				title="<?php echo $avatarDisplayName; ?>" valign="top">
		</a>
		<br />
<?php
			}

			// does this widget have an associated in-world object?
			if (empty($settings) || empty($settings[$objectKey]["Status"])) {
?>
		<span class="osinsl-unconfigured"><?php esc_attr_e($instance["unconfigured"]); ?></span>
<?php
			} else {
?>
		<span class="osinsl-before-status"><?php esc_attr_e($instance["before_status"]); ?></span>
		<span class="osinsl-status"><?php esc_attr_e($settings[$objectKey]["Status"]); ?></span>
		<span class="osinsl-after-status"><?php esc_attr_e($instance["after_status"]); ?></span>
	</div>
<?php		}
			// return to widget handling code
			echo $after_widget;
		} // end function widget()

		public function update($new_instance, $old_instance)
		{
			$instance = $old_instance;
			$instance["title"] 			 = strip_tags($new_instance["title"]);
			$instance["avatar_name"]	 = strip_tags($new_instance["avatar_name"]); // probably not needed
			$instance["object_key"]		 = strip_tags($new_instance["object_key"]);
			$instance["before_status"]	 = strip_tags($new_instance["before_status"]);
			$instance["after_status"]	 = strip_tags($new_instance["after_status"]);
			$instance["having_problems"] = strip_tags($new_instance["having_problems"]);
			$instance["unconfigured"]	 = strip_tags($new_instance["unconfigured"]);
			$instance["profile_picture"] = strip_tags($new_instance["profile_picture"]);
			return $instance;
		} // end public function update()

		/* Back end, the interface shown in Appearance -> Widgets
		 * administration interface.
		 */
		public function form($instance)
		{
			$defaults = [
				"title"				=> __("Second Life Online Status", "online-status-insl"),
				"avatar_name"		=> "", // probably not needed, we get this from the settings
				"object_key"		=> NULL_KEY,
				"before_status" 	=> __("I am ", "online-status-insl"),
				"after_status"		=> __(" in Second Life.", "online-status-insl"),
				"having_problems" 	=> __(
					"having problems contacting RPC server...",
					"online-status-insl"
				),
				"unconfigured" 		=> __(
					"Please set up your in-world object first",
					"online-status-insl"
				),
				"profile_picture"	=> __("none", "online-status-insl"),
			];

			$instance = wp_parse_args((array) $instance, $defaults);

			$title = strip_tags($instance["title"]);

			// get the saved options; this will allow us to choose avatar names from
			//  registered in-world objects (which are indexed by object key)
			//  and assign this widget to one avatar name
			$settings = maybe_unserialize(get_option("online_status_insl_settings"));

			// The obvious problem is selecting avatars that have the same name on different grids;
			//  thus we try to get the location as well, to help the user
			if (
				empty($instance["avatar_name"]) &&
				(!empty($instance["object_key"]) && $instance["object_key"] != NULL_KEY)
			) {
				$instance["avatar_name"] =
					$settings[$instance["object_key"]]["avatarName"];
			}
			// try to fill in something
?>
<p>
	<label for="<?php esc_attr_e($this->get_field_id("title"), "online-status-insl"); ?>">
		<?php _e("Title", "online-status-insl"); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id("title"), "online-status-insl" ); ?>"
			name="<?php esc_attr_e( $this->get_field_name("title"),	"online-status-insl" ); ?>"
			type="text"
			value="<?php esc_attr_e($title, "online-status-insl"); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e($this->get_field_id("object_key"), "online-status-insl"); ?>">
		<?php _e("Avatar Name", "online-status-insl"); ?>:
	</label>
	<select class="widefat"
		id="<?php esc_attr_e( $this->get_field_id("object_key"), "online-status-insl"); ?>"
		name="<?php esc_attr_e($this->get_field_name("object_key"), "online-status-insl"); ?>" style="width:100%;">
	<?php // now loop through all avatar names

	if (!empty($settings)) {
		foreach ($settings as $oneTrackedObject) {
			// parse name of the region and coordinates to help to identify tracked object
			$regionName = substr(
				$oneTrackedObject["objectRegion"],
				0,
				strpos($oneTrackedObject["objectRegion"], "(") - 1
			);
			$coords = trim($oneTrackedObject["objectLocalPosition"], "() \t\n\r");
			$xyz = explode(",", $coords);

			// Output a dropbox option with "Avatar Name [Region (x,y,z)]"
?>
		<option <?php if ($oneTrackedObject["objectKey"] == $instance["object_key"]) : ?>
			selected="selected"
				<?php endif; ?>
			value="<?php esc_attr_e($oneTrackedObject["objectKey"]); ?>">
			<?php esc_attr_e(sprintf("%s [%s (%d,%d,%d)]",
				$oneTrackedObject["avatarDisplayName"],
				$regionName, $xyz[0], $xyz[1], $xyz[2])); ?>
		</option>
<?php
		}
	}
	// never configured before; moved to have a "disabled" setting
	else {
?>
		<option disabled="disabled">--<?php _e(	"Unconfigured",	"online-status-insl"  ); ?>--</option>
<?php
	}
?>
	</select>
	<label for="<?php esc_attr_e( $this->get_field_id( "before_status"), "online-status-insl" ); ?>">
		<?php _e("Before status message", "online-status-insl"); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id("before_status"),	"online-status-insl" ); ?>"
			name="<?php esc_attr_e(	$this->get_field_name("before_status"),	"online-status-insl" ); ?>" type="text"
			value="<?php esc_attr_e( $instance["before_status"], "online-status-insl" ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id( "after_status"), "online-status-insl" ); ?>">
		<?php _e("After status message", "online-status-insl"); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id("after_status"), "online-status-insl" ); ?>"
			name="<?php esc_attr_e( $this->get_field_name("after_status"), "online-status-insl" ); ?>" type="text"
			value="<?php esc_attr_e($instance["after_status"], "online-status-insl"); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id("having_problems"), "online-status-insl"); ?>">
		<?php _e("Error message when communicating with SL", "online-status-insl"); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id("having_problems"), "online-status-insl" ); ?>"
			name="<?php esc_attr_e(	$this->get_field_name("having_problems"), "online-status-insl" ); ?>"
			type="text"
			value="<?php esc_attr_e( $instance["having_problems"], "online-status-insl" ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id("unconfigured"), "online-status-insl"	); ?>">
		<?php _e("Widget not configured message", "online-status-insl"); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id("unconfigured"), "online-status-insl"	); ?>"
			name="<?php esc_attr_e($this->get_field_name("unconfigured"), "online-status-insl"); ?>"
			type="text"
			value="<?php esc_attr_e($instance["unconfigured"], "online-status-insl"); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id("profile_picture"), "online-status-insl"); ?>">
		<?php _e("Profile picture?", "online-status-insl"); ?>
	</label>
	<select id="<?php esc_attr_e( $this->get_field_id("profile_picture"), "online-status-insl"); ?>"
		name="<?php esc_attr_e($this->get_field_name("profile_picture"), "online-status-insl"); ?>" class="widefat">
		<option <?php if ("none" == $instance["profile_picture"]) {
			echo 'selected="selected"';
		} ?>>none</option>
		<option <?php if ("center" == $instance["profile_picture"]) {
			echo 'selected="selected"';
		} ?>>center</option>
		<option <?php if ("left" == $instance["profile_picture"]) {
			echo 'selected="selected"';
		} ?>>left</option>
		<option <?php if ("right" == $instance["profile_picture"]) {
			echo 'selected="selected"';
		} ?>>right</option>
	</select>
</p>
<?php
		}	// end function form()
	} // end class online_status_insl
} // end if class_exists

/**
 *	Auxiliary plugin functions (outside any class)
 */
function online_status_insl_widget_init()
{
	register_widget("online_status_insl");
}

function online_status_insl_widget_activate()
{
	// no special options
}

function online_status_insl_widget_deactivate()
{
	// clean up options on database
	delete_option("online_status_insl_settings");
	// sanitize
	unregister_setting("online_status_insl", "online_status_insl_settings");
}

function online_status_insl_admin_menu_options()
{
	add_options_page(
		__("Online Status inSL", "online-status-insl"),
		__("Online Status inSL", "online-status-insl"),
		1,
		"online_status_insl",
		"online_status_insl_menu"
	);
}

/*
 *
 * request_protocol is a function to figure out if the WP site has been called via HTTPS or not.
 *
 * @see https://stackoverflow.com/a/16076965/1035977
 *
 * @return string 'https' or 'http'
 */
function request_protocol() {
	$isSecure = false;
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		$isSecure = true;
	}
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
		$isSecure = true;
	}
	return $isSecure ? 'https' : 'http';
}

function online_status_insl_menu()
{
?>
<div class="wrap"> <!-- Plugin page for Online Status inSL -->
	<h2><?php _e("Online Status inSL", "online-status-insl"); ?></h2>
	<p>
<?php _e(
	"Please create an object in Second Life on a plot owned by you, and drop the following script inside:",
	"online-status-insl"
); ?>
	</p>
	<hr />
<?php // Figure out plugin version; if we have it already, skip this check (since it's resource-intensive)
	if (!online_status_insl::$plugin_version) {
		$plugin_data =  get_file_data( __FILE__, array(
        	'Version' => 'Version'
		) );
		online_status_insl::$plugin_version = $plugin_data["Version"];
	}
// Now spew the script; one day, this might be tied in with a pretty-formatting thingy
//
// TODO(gwyneth): Check if llRequestSecureURL() works; a first approach could be to check if
//  we're inside SL and use llRequestSecureURL(); if in OpenSim, use llRequestURL()
?>
	<textarea name="osinsl-lsl-script" cols="120" rows="12" readonly style="font-family: monospace;">
// Code to show online status and let it be retrieved by external calls.
// © 2011-<?php echo date('Y'); ?> by Gwyneth Llewelyn. Most rights reserved.
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
string objectVersion = "<?php echo online_status_insl::$plugin_version; ?>";

// modified by SignpostMarv
string http_host = "<?php esc_attr_e($_SERVER["HTTP_HOST"]); ?>";

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
				registrationResponse = llHTTPRequest("<?php esc_attr_e(request_protocol()); ?>://" + http_host + "/wp-content/plugins/online-status-insl/save-channel.php",
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
			registrationResponse = llHTTPRequest("<?php esc_attr_e(request_protocol()); ?>://" + http_host + "/wp-content/plugins/online-status-insl/save-channel.php",
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
<?php _e(
	"Then you can drag the appropriate widget on your sidebar.",
	"online-status-insl"
); ?>
<?php
	// Prepare the list of tracked objects
	$myListTable = new Online_Status_inSL_List_Table();
	$myListTable->prepare_items();
	// Note that this is inside the <p>...</p> because it emits \n somewhere!
?>
	</p>
	<div class="wrap"> <!-- List of avatars being tracked -->
		<h2><?php _e("Current avatars being tracked", "online-status-insl"); ?>:</h2>
		<form method="post">
			<input type="hidden" name="page" value="<?php echo $_REQUEST["page"] ?: 1; ?>">
<?php $myListTable->display(); ?>
		</form>
		<div class="clear"></div>
		<div class="instructions">
<?php
// Add some instructions
	_e(
		"<p><strong>Ping</strong>: Checks if in-world object is still alive; if not, you should remove it manually.</p>",
		"online-status-insl"
	);
	_e(
		"<p><strong>Delete</strong>: Removes the tracking object from the WordPress table. The object will still remain in-world unless it gets manually deleted. You can touch it in-world to get it to register again.</p>",
		"online-status-insl"
	);
	_e(
		"<p><strong>Reset</strong>: Resets the in-world object. This will give you a new communications channel. Note that if the object is not responding to pings, it will not be affected. The object will remain both in-world <em>and</em> being tracked by WordPress.</p>",
		"online-status-insl"
	);
	_e(
		"<p><strong>Destroy</strong>: Attempts to tell the in-world object to be deleted in-world <em>and</em> also deletes it from the WordPress table. If it fails, you will have to delete it manually in-world <em>and</em> delete it from this table as well. Use with caution, since the object will <em>not</em> be returned to your inventory but disappear forever!</p>",
		"online-status-insl"
	);
	_e(
		"<p>Note that the different options exist mostly to help you to keep your objects in sync with WordPress. Sometimes this is not possible. Note that the objects will try to contact WordPress with the updated status if they change owners, if the region simulator crashes, etc. Sometimes that can fail.</p>",
		"online-status-insl"
	);
?>
		</div>
		<div class="clear"></div>
	</div><!-- end wrap for list -->
</div><!-- end wrap for whole plugin -->
<div class="clear"></div>
<?php
} // end function online_status_insl_menu()

// Add a settings group, which hopefully makes it easier to delete later on
function online_status_insl_register_settings()
{
	register_setting("online_status_insl", "online_status_insl_settings");
	// it's a huge serialised array for now, stored as a WP option in the database;
	//  if performance drops, this might change in the future
}

/**
 * The [osinsl] shortcode.
 *
 * For now, we just have [osinsl avatar="AvatarName"] or [osinsl objectkey="<UUID>"]
 *
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 */
function online_status_insl_shortcode( $atts = [], $content = null, $tag = '' )
{
//	error_log("Entering online_status_insl_shortcode, atts are " . print_r($atts, true));
	extract(
		shortcode_atts(
			[
				"avatar" => "(???)", // assigns $avatar to name if it exists, and provides a default of (???) which is supposed *not* to exist
				"objectkey" => NULL_KEY, // if there are multiple avatars with the same name, you need the object key instead
				"picture" => "none", // emits picture tags, can be center/right/left/ etc.
				"status" => "on", // emits no status, just the picture (or nothing)
				"profilelink" => "off", // puts links to web profile if picture active
			],
			$atts
		)
	);
	// search for the avatar name
	$settings = maybe_unserialize(get_option("online_status_insl_settings"));

	// figure out stupid id for nice formatting
	if ($avatar != "(???)") {
		$osinslID = strtolower(strtr($avatar, " ", "-"));
	} elseif ($objectkey != NULL_KEY) {
		$osinslID = $objectkey;
	} else {
		$osinslID = "broken";
	}

	// store things in a return value; add class attributes to allow styling
	// Added esc_attr() in case someone creates an avatar name encoded with a XSS attack (gwyneth 20210621)
	$returnValue =
		"<span class='osinsl-shortcode' id='osinsl-shortcode-" . esc_attr($osinslID) . "'>";
?>
<!-- Avatar Name or Object Key: "<?php esc_attr_e($osinslID); ?>" -->
<?php
	if (!empty($settings) && count($settings) > 0) {
		// did we find anything at all??
		// See if objectkey is set. If yes, instead of using avatar names, we use object UUIDs (guaranteed to
		//	be unique, even across grids)
		if (!empty($objectkey) && $objectkey != NULL_KEY) {
			if (!empty($settings[$objectkey])) {
				$avatarNameSanitised = sanitise_avatarname(
					$settings[$objectkey]["avatarDisplayName"]
				);

				if ($picture != "none") {
					if ($profilelink != "off") {
						// This will only work on OpenSimulator if there is an avatar with the same name in SL
						$returnValue .=
							"<a href='https://my.secondlife.com/" .
							$avatarNameSanitised .
							"' target='_blank'>";
					}
					$returnValue .=
						'<img class="osinsl-profile-picture align' .
						$picture .
						'" alt="' .
						$avatarNameSanitised .
						'" title="' .
						$avatarNameSanitised .
						'" src="https://my-secondlife.s3.amazonaws.com/users/' .
						$avatarNameSanitised .
						'/thumb_sl_image.png" width="80" height="80" alt="' .
						$avatar .
						'" valign="bottom">';
					if ($profilelink != "off") {
						$returnValue .= "</a>";
					}
				}
				if ($status != "off") {
					$returnValue .= $settings[$objectkey]["Status"];
				}
			}
			// no such object being tracked!
			else {
				$returnValue .=
					__("Invalid object key: ", "online-status-insl") . $objectkey;
			}
		} else {
			$avatarNameSanitised = sanitise_avatarname($avatar);

			// Search through settings; retrieve first tracked object with this avatar name

			$foundAvatar = false;

			foreach ($settings as $trackedAvatar) {
				if ($trackedAvatar["avatarDisplayName"] == $avatar) {
					if ($picture != "none") {
						if ($profilelink != "off") {
							$returnValue .=
								"<a href='https://my.secondlife.com/" .
								$avatarNameSanitised .
								"' target='_blank'>";
						}
						$returnValue .=
							'<img class="osinsl-profile-picture align' .
							$picture .
							'" alt="' .
							$avatarNameSanitised .
							'" title="' .
							$avatarNameSanitised .
							'" src="https://my-secondlife.s3.amazonaws.com/users/' .
							$avatarNameSanitised .
							'/thumb_sl_image.png" width="80" height="80" alt="' .
							$avatar .
							'" valign="bottom">';
						if ($profilelink != "off") {
							$returnValue .= "</a>";
						}
					}
					if ($status != "off") {
						$returnValue .= $trackedAvatar["Status"];
					}
					$foundAvatar = true;
					break;
				}
			}
			if (!$foundAvatar) {
				$returnValue .=
					__("No widget configured for ", "online-status-insl") . $avatar;
			}
		} // else
	} else {
		$returnValue .= __("No avatars being tracked", "online-status-insl");
	}

	$returnValue .= "</span>";

	return $returnValue;
} // end function online_status_insl_shortcode()

// Deal with translations. British English and European Portuguese only for now.
function online_status_insl_load_textdomain() {
	// This is how it _used_ to work under WP < 4.6:
	// error_log('Calling online_status_insl_load_textdomain(), locale is: "' . determine_locale() . '"');
	load_plugin_textdomain(
		'online-status-insl',
		false,
		basename( dirname( __FILE__ ) ) . '/languages/'
	);
}

/*
function online_status_insl_load_textdomain_mofile( $mofile, $domain ) {
	// https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
	// Found via https://stackoverflow.com/questions/45525390/wordpress-plugin-translation-load-plugin-textdomain#comment105104921_45883184

	if ( 'online-status-insl' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
		$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
	}
	error_log('Calling online_status_insl_load_textdomain_mofile() with "' . $mofile . '"...');
	return $mofile;
}
*/

/**
 * Central location to create all shortcodes.
 */
function online_status_insl_shortcodes_init() {
	add_shortcode( 'osinsl', 'online_status_insl_shortcode' );
}

// error_log('Entering action/hook area...');
//add_filter( 'load_textdomain_mofile', 'online_status_insl_load_textdomain_mofile', 10, 2 );
add_action( 'init', 'online_status_insl_load_textdomain' );	// load translations here
add_action( 'widgets_init', 'online_status_insl_widget_init' );
add_action( 'admin_menu', 'online_status_insl_admin_menu_options' );
register_activation_hook(__FILE__, 'online_status_insl_widget_activate' );
register_deactivation_hook(__FILE__, 'online_status_insl_widget_deactivate' );
add_action( 'admin_init', 'online_status_insl_register_settings' );
add_action( 'init', 'online_status_insl_shortcodes_init' );
// error_log('Leaving action/hook area...');

$wpdpd = new online_status_insl('online-status-insl', 'Online Status inSL');
