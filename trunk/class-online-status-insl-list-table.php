<?php
/**
 *  Extending the WP_List_Table class to create fancy lists
 *
 *  @category OnlineStatusInSL
 *  @package  OnlineStatusInSL
 *  @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 *  @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 *  @version  1.6.1
 *  @link     https://gwynethllewelyn.net/online-status-insl/
 */

if ( ! class_exists( 'Online_Status_InSL_List_Table' ) ) {
	// 1.4.0 includes WP_List_Table to manage the list of objects. Duh! So much easier! Warning: this class is
	// not 'plugin-developer' friendly and might disappear in the future.
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	/**
	 *  The main class for this widget
	 *
	 *  @since WP 1.4.0
	 */
	class Online_Status_InSL_List_Table extends WP_List_Table {
		/**
		 * Temporary internal variable for copying settings.
		 *
		 * @var array $internal_settings
		 */
		private $internal_settings = array();

		/**
		 *  Online_Status_InSL_List_Table constructor
		 */
		public function __construct() {
			global $status, $page;

			parent::__construct(
				array(
					'singular' => __( 'avatar status tracking object', 'online-status-insl' ), // Singular name of the listed records.
					'plural'   => __( 'avatar status tracking objects', 'online-status-insl' ), // Plural name of the listed records.
					'ajax'     => false, // Does this table support ajax?
				)
			);

			// Check if we have saved the plugin version on the plugin class; if not, do it now!
			// It's a global static var for ease of use.
			if ( ! Online_Status_InSL::$plugin_version ) {
				$plugin_data                        = get_file_data(
					__FILE__,
					array(
						'Version' => 'Version',
					),
				);
				Online_Status_InSL::$plugin_version = $plugin_data['Version'];
			}

			// Get the plugin settings (table with all entries) and store it internally.
			$this->internal_settings = maybe_unserialize(
				get_option( 'online_status_insl_settings' )
			);
		}

		/**
		 *  Returns selected list of bulk actions for the `actions` dropdown.
		 *
		 *  @return string[]
		 *  @phan-return array{string:string}
		 */
		public function get_bulk_actions() {
			$actions = array(
				'ping'   => __( 'Ping object', 'online-status-insl' ),
				'delete' => __( 'Delete from tracking table', 'online-status-insl' ),
				'reset'  => __( 'Reset object(s)', 'online-status-insl' ),
				'die'    => __( 'Destroy object forever', 'online-status-insl' ),
			);
			return $actions;
		}

		/**
		 *  Helper function to emit a status message in the WP backoffice.
		 *  Not part of WP_List_Table.
		 *
		 *  @param string $type of the message (according to WP message types).
		 *  @param string $text of the message to display.
		 *  @return void
		 */
		public static function emit_status_message( $type = 'info', $text = '' ) {
			if ( empty( $text ) ) {
				$text = __( 'Empty message.', 'online-status-insl' );
			}
			echo wp_sprintf(
				"<div class='notice notice-%s is-dismissible'><p>%s</p></div>",
				esc_attr( $type ),
				wp_kses_post( $text )
			);
		}

		/**
		 *  Main processor function for WP_List_Table.
		 *
		 *  Essentially it checks which actions have been selected, gathers a list_plugin_updates()
		 *  of tracked objects that have been marked by the user, and applies the chosen action
		 *  to each and every one of them.
		 *
		 *  @return void
		 */
		public function process_bulk_action() {
			// Skip processing if we have no requests (apparently this function gets called every time
			// the page is called).
			if ( ! empty( $_REQUEST['deletedStatusIndicators'] ) ) {
				// Check if it's an array or a single request:
				// Single request: user clicked below the avatar picture;
				// Array: this was a bulk request, and we have several UUIDs.
				$process_ids = wp_unslash( $_REQUEST['deletedStatusIndicators'] );
				if ( ! is_array( $process_ids ) ) {
					// For our purposes, we need everything to be an array later on (gwyneth 20220104).
					$process_ids = array( $process_ids );
				}

				// Check if we got one of the valid actions.
				// Note that there is no 'default' action. Default is doing nothing.
				if ( 'ping' === $this->current_action() ) {
					// `ping` is an 'undefined' message to the object; even old objects should be able to reply
					// because we have somehow programmed that in advance, for future expansion.
					foreach ( $process_ids as $ping_tracking_object ) {
						$status_message =
							__( 'Pinging object for ', 'online-status-insl' ) .
							$this->internal_settings[ $ping_tracking_object ]['avatarDisplayName'] .
							', ' .
							set_bold( __( 'Object Name: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $ping_tracking_object ]['objectName'] .
							' (' .
							$this->internal_settings[ $ping_tracking_object ]['objectKey'] .
							'), ' .
							set_bold( __( 'Location: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $ping_tracking_object ]['objectRegion'] .
							'<br />' .
							set_bold( __( 'Calling URL: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $ping_tracking_object ]['PermURL'] .
							'<br />';
						self::emit_status_message( 'info', $status_message );

						// Get PermURL and contact the object.
						// Note: I used the WP_Http class directly; but now I prefer to use the encapsulated
						// `wp_remote_*` functions instead (gwyneth 20220106).
						$result = wp_remote_post(
							$this->internal_settings[ $ping_tracking_object ]['PermURL'],
							array(
								'method' => 'POST',
								'body'   => array( 'command' => 'ping' ),
							)
						);

						if ( 200 === wp_remote_retrieve_response_code( $result ) ) {
							self::emit_status_message(
								'success',
								wp_sprintf(
									// translators: first string is object name; second string is a reply from the in-world object.
									__( 'Object "%1$s" replied: "%2$s"', 'online-status-insl' ),
									$this->internal_settings[ $ping_tracking_object ]['objectName'],
									$result['body']
								)
							);
						} else {
							self::emit_status_message(
								'error',
								__(
									'Error communicating with in-world object ',
									'online-status-insl'
								) .
								$ping_tracking_object .
								': ' .
								$result['response']['code'] .
								' - ' .
								$result['response']['message'] .
								' [' .
								$result['body'] .
								']'
							);
						}
					}
				} elseif ( 'delete' === $this->current_action() ) {
					// in this case, $_REQUEST['deletedStatusIndicators'] gives us a list of items to delete.

					$status_message = ''; // add to this string as we find objects to delete.

					// Theoretically, all we need to do is to cycle `$_REQUEST['deletedStatusIndicators']` and since these
					// are `objectKeys`, we should be able to simply unset the appropriate element.
					// This will mean that previous versions of the settings might really be lost (= 'undeletable').
					foreach ( $process_ids as $delete_tracking_object ) {
						$status_message .=
							__( 'Deleting ', 'online-status-insl' ) .
							$this->internal_settings[ $delete_tracking_object ]['avatarDisplayName'] .
							', ' .
							set_bold( __( 'Object Name: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $delete_tracking_object ]['objectName'] .
							' (' .
							$this->internal_settings[ $delete_tracking_object ]['objectKey'] .
							'), ' .
							set_bold( __( 'Location: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $delete_tracking_object ]['objectRegion'] .
							'<br />';

						// Get rid of it from our internal table!
						unset( $this->internal_settings[ $delete_tracking_object ] );
					}

					// Emit *info* class showing we have deleted some things; error if we haven't managed to delete anything.
					if ( $status_message ) {
						self::emit_status_message( 'warning', $status_message );
					} else {
						self::emit_status_message(
							'error',
							wp_sprintf(
								// translators: placeholder is (possibly) an avatar name.
								__(
									'No online status indicators for %s found',
									'online-status-insl'
								),
								// phpcs:ignore WordPress.PHP.DevelopmentFunctions
								print_r( $process_ids, true )
							)
						);
					}

					// update options with new settings; gets serialized automatically.
					if (
						! update_option(
							'online_status_insl_settings',
							$this->internal_settings
						)
					) {
						self::emit_status_message(
							'error',
							set_bold(
								__(
									'WordPress settings could not be saved - Object(s) not deleted!',
									'online-status-insl'
								)
							)
						);
					} // endif update options
				} elseif ( 'reset' === $this->current_action() ) {
					// Send reset command to inworld object. Object will do a llResetScript() and attempt
					// to register again. There is a slight bug here, because somehow the settings are
					// not saved (maybe there is a lock on them?).
					foreach ( $process_ids as $reset_tracking_object ) {
						$status_message =
							__( 'Resetting object for ', 'online-status-insl' ) .
							$this->internal_settings[ $reset_tracking_object ]['avatarDisplayName'] .
							', ' .
							set_bold( __( 'Object Name: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $reset_tracking_object ]['objectName'] .
							' (' .
							$this->internal_settings[ $reset_tracking_object ]['objectKey'] .
							'), ' .
							set_bold( __( 'Location: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $reset_tracking_object ]['objectRegion'] .
							'<br />' .
							set_bold( __( 'Calling URL: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $reset_tracking_object ]['PermURL'] .
							'<br />';
						self::emit_status_message( 'info', $status_message );

						// Get PermURL and contact it.
						$result = wp_remote_post(
							$this->internal_settings[ $reset_tracking_object ]['PermURL'],
							array(
								'method' => 'POST',
								'body'   => array( 'command' => 'reset' ),
							)
						);

						// For some reason, we always get an error, although the communication worked!
						// Maybe a good idea is to read the settings again? Or save them?
						if ( 200 === wp_remote_retrieve_response_code( $result ) ) {
							self::emit_status_message(
								'success',
								wp_sprintf(
									// translators: first string is the object name; second string is a message coming from that object in SL.
									__( 'Object "%1$s" replied: "%2$s"', 'online-status-insl' ),
									$this->internal_settings[ $reset_tracking_object ]['objectName'],
									$result['body']
								)
							);
						} else {
							self::emit_status_message(
								'error',
								__(
									'Error communicating with in-world object ',
									'online-status-insl'
								) .
								$reset_tracking_object .
								': ' .
								$result['response']['code'] .
								' - ' .
								$result['response']['message'] .
								' [' .
								$result['body'] .
								']'
							);
						}
					}
				} elseif ( 'die' === $this->current_action() ) {
					// Send `llDie()` command to each in-world object (they will disappear forever) and
					// we need to delete them from our table too.
					foreach ( $process_ids as $die_tracking_object ) {
						$status_message =
							__( 'Deleting in-world object for ', 'online-status-insl' ) .
							$this->internal_settings[ $die_tracking_object ]['avatarDisplayName'] .
							', ' .
							set_bold( __( 'Object Name: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $die_tracking_object ]['objectName'] .
							' (' .
							$this->internal_settings[ $die_tracking_object ]['objectKey'] .
							'), ' .
							set_bold( __( 'Location: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $die_tracking_object ]['objectRegion'] .
							'<br />' .
							set_bold( __( 'Calling URL: ', 'online-status-insl' ) ) .
							$this->internal_settings[ $die_tracking_object ]['PermURL'] .
							'<br />';
						self::emit_status_message( 'info', $status_message );

						// Get PermURL and send it the killing message!
						$result = wp_remote_post(
							$this->internal_settings[ $die_tracking_object ]['PermURL'],
							array(
								'method' => 'POST',
								'body'   => array( 'command' => 'die' ),
							)
						);

						if ( 200 === wp_remote_retrieve_response_code( $result ) ) {
							self::emit_status_message(
								'success',
								wp_sprintf(
									// translators: first string is the object name; second string is a message coming from that object in SL.
									__( 'Object "%1$s" replied: "%2$s"', 'online-status-insl' ),
									$this->internal_settings[ $die_tracking_object ]['objectName'],
									$result['body']
								)
							);

							// now get rid of the object in the settings table!
							unset( $this->internal_settings[ $die_tracking_object ] );
						} else {
							self::emit_status_message(
								'error',
								__(
									'Object not deleted because there was an error communicating with it ',
									'online-status-insl'
								) .
								$die_tracking_object .
								': ' .
								$result['response']['code'] .
								' - ' .
								$result['response']['message'] .
								' [' .
								$result['body'] .
								']'
							);
						}
					}
					// Objects might have been deleted, so now it's time to purge them from the WP settings.
					if ( ! update_option(
						'online_status_insl_settings',
						$this->internal_settings
					)
					) {
						self::emit_status_message(
							'error',
							set_bold(
								__(
									'WordPress settings could not be saved - Object(s) not deleted!',
									'online-status-insl'
								)
							)
						);
					} // endif update options
				} // endif die
			}

			/*
			No valid parameters for bulk actions! But this seems to be normal!

			// else {
			// // self::emit_status_message('error', __('No bulk actions to process', online-status-insl'));
			}
			*/
		} // end function process_bulk_actions

		/**
		 *  Places a checkbox on the column for the user to select this object.
		 *
		 *  @param object $item to be selected.
		 *  @return string HTML-formatted code for the correspondent item.
		 */
		public function column_cb( $item ) {
			return wp_sprintf(
				'<input type="checkbox" name="deletedStatusIndicators[]" value="%s" />',
				esc_attr( $item['objectKey'] )
			);
		}

		/**
		 *  Assembles the column names and descriptions we wish to apply to each line
		 *  and returns it with proper HTML formatting.
		 *
		 *  @return string[]
		 *  @phan-return array{string:string}
		 */
		public function get_columns() {
			$columns = array(
				'cb'                => '<input type="checkbox" />',
				'avatarDisplayName' => __( 'Avatar Display Name', 'online-status-insl' ),
				'Status'            => __( 'Status', 'online-status-insl' ),
				'PermURL'           => __( 'PermURL', 'online-status-insl' ),
				'avatarKey'         => __( 'Avatar Key', 'online-status-insl' ),
				'objectName'        => __( 'Object Name', 'online-status-insl' ),
				'objectKey'         => __( 'Object Key', 'online-status-insl' ),
				'objectVersion'     => __( 'Object Version', 'online-status-insl' ),
				'objectRegion'      => __( 'Location', 'online-status-insl' ),
				'timeStamp'         => __( 'Last time checked', 'online-status-insl' ),
			);
			return $columns;
		}

		// Display special columns, which require non-standard processing procedures to
		// generate the links for manually applying an action to them.

		/**
		 *  Deals with the special column with the avatar's display name.
		 *
		 *  @param object $item to be selected for the action.
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_avatarDisplayName( $item ) {
			$avatar_name_sanitised = sanitise_avatarname( $item['avatarDisplayName'] );
			$page                  = esc_attr( $_REQUEST['page'] ?? '' );
			$object_key            = esc_attr( $item['objectKey'] );

			// This column will also have options to affect the in-world object.
			$actions = array(
				'ping'   => wp_sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$page,
					'ping',
					$object_key,
					__( 'Ping', 'online-status-insl' )
				),
				'delete' => wp_sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$page,
					'delete',
					$object_key,
					__( 'Delete', 'online-status-insl' )
				),
				'reset'  => wp_sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$page,
					'reset',
					$object_key,
					__( 'Reset', 'online-status-insl' )
				),
				'die'    => wp_sprintf(
					'<a href="?page=%s&action=%s&deletedStatusIndicators=%s">%s</a>',
					$page,
					'die',
					$object_key,
					__( 'Destroy', 'online-status-insl' )
				),
			);

			// If the object is in Second Life, we can add a few more cute things;
			// if it's in OpenSimulator, we may not be so lucky (gwyneth 20220106)...
			if ( false !== stripos( $item['PermURL'], 'secondlife' ) ) {
				$display_string = '<a href="https://my.secondlife.com/' .
				$avatar_name_sanitised .
				'" target="_blank">' .
				esc_attr( $item['avatarDisplayName'] ) .
				'<img src="https://my-secondlife.s3.amazonaws.com/users/' .
				$avatar_name_sanitised .
				'/thumb_sl_image.png" width="80" height="80" alt="' .
				esc_attr( $item['avatarDisplayName'] ) .
				'" title="' .
				esc_attr( $item['avatarDisplayName'] ) .
				'" class="aligncenter" valign="bottom"></a>';
			} else {
				$display_string = esc_attr( $item['avatarDisplayName'] );
			}

			return $display_string . $this->row_actions( $actions );
		}

		/**
		 *  Deals with the special column with the object's region name.
		 *
		 *  If the request comes from Second Life, add a fancy map link (gwyneth 20220106).
		 *
		 *  @param object $item to be selected for the action.
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_objectRegion( $item ) {
			// parse name of the region and coordinates.
			$object_region = esc_attr( $item['objectRegion'] ); // first, sanitise!
			$region_name   = substr(
				$object_region,
				0,
				strpos( $object_region, '(' ) - 1
			);
			$coords        = trim( esc_attr( $item['objectLocalPosition'] ), '() \t\n\r' );
			$xyz           = explode( ',', $coords );

			// if we're in Second Life, generate a link to maps.secondlife.com.
			if ( false !== stripos( $item['PermURL'], 'secondlife' ) ) {
				$location_str = '<a href="https://maps.secondlife.com/secondlife/%s/%F/%F/%F?title=%s&amp;msg=%s&amp;img=%s" target="_blank">%s (%d,%d,%d)</a><br /><small>(Second Life)</small>';
			} else {
				$location_str = '%s (%d,%d,%d)<br /><small>(OpenSimulator)</small>';
			}

			return wp_sprintf(
				$location_str,
				esc_attr( $region_name ),
				$xyz[0],
				$xyz[1],
				$xyz[2],
				rawurlencode( $item['objectName'] ),
				rawurlencode(
					__( 'Online Status Indicator for ', 'online-status-insl' )
					. esc_attr( $item['avatarDisplayName'] )
				),
				rawurlencode(
					'https://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png'
				),
				esc_attr( $region_name ),
				$xyz[0],
				$xyz[1],
				$xyz[2]
			);
		}

		/**
		 *  Deals with the special column that displays the script version contained in an item.
		 *
		 *  @param string[] $item to be selected for the action.
		 *  @phan-param array{string, string} $item to be selected for the action.
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_objectVersion( $item ) {
			// Added some fancy visuals: green if the plugin is at the same version as the in-world object.
			// Versions 1.3.X are considered 'safe' (same protocol) so they're coloured a dark yellow.
			// Anything before that might be still tracked, but communications will be lost with it.
			// The hope is that users upgrade the script manually if they have some visual feedback.
			$object_version = esc_attr( $item['objectVersion'] ); // sanitise!
			$obv_css_colour = 'Maroon'; // colour by default, if we couldn't check version.
			if ( Online_Status_InSL::$plugin_version === $object_version ) {
				$obv_css_colour = 'DarkGreen';
			} elseif (
				in_array(
					$object_version,
					array(
						'1.3.0',
						'1.3.5',
						'1.3.6',
						'1.3.7',
						'1.3.8',
						'1.4.0',
						'1.4.1',
						'1.4.2',
						'1.5.0',
						// note: 1.5.1 was never released; 1.6.0 was abandoned...
					),
					true // strict checking required in WordPress (gwyneth 20220104).
				)
			) {
				$obv_css_colour = 'DarkGoldenRod';
			}

			return "<span style='color:" .
				$obv_css_colour .
				";'>" .
				$object_version .
				'</span>';
		}

		/**
		 *  Deals with the special column with the avatar's online status (online/offline/unknown).
		 *
		 *  @param string[] $item to be selected for the action.
		 *  @phan-param array{string: string} $item
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_Status( $item ) {
			// Just some fancy colouring. Note that only online/offline are valid status. If the object
			// breaks but still communicates, it might send a different status (rare!).
			$item_status    = esc_attr( $item['Status'] ); // sanitise it first!
			$obs_css_colour = 'DimGray'; // colour by default for unknown status.
			if ( 'online' === $item_status ) {
				$obs_css_colour = 'DarkGreen';
			} elseif ( 'offline' === $item_status ) {
				$obs_css_colour = 'Maroon';
			}

			return "<span style='color:" .
				$obs_css_colour .
				";'>" .
				$item_status .
				'</span>';
		}

		/**
		 *  Deals with the special column with the timestamp of the last successful connection
		 *  with the script running inside the in-world item.
		 *
		 *  @param object $item to be selected for the action.
		 *  @phan-param array{string: string} $item
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_timeStamp( $item ) {
			// translators: timestamp in date format, see http://php.net/date.
			return date( __( 'Y M j H:i:s', 'online-status-insl' ), esc_attr( $item['timeStamp'] ) );
		}

		/**
		 *  Display a normal column using the default, mainline code.
		 *
		 *  @param string[] $item to be selected for the action.
		 *  @phan-param array{string: string} $item
		 *  @param string   $column_name (which is one of the valid column names _not_ listed on any of the above functions!).
		 *  @return string HTML-formatted code for the link that will enable this action.
		 */
		public function column_default( $item, $column_name ) {
			return esc_attr( $item[ $column_name ] );
		}

		/**
		 *  Indicates to the table which columns are sortable.
		 *
		 *  @return string[] an array with the names of the sortable columns.
		 *  @phan-return array(string, string)
		 */
		protected function get_sortable_columns() {
			// Maybe other columns should be sorted too. These are the more useful ones.
			$sortable_columns = array(
				'avatarDisplayName' => array( 'avatarDisplayName', false ),
				'objectRegion'      => array( 'objectRegion', false ),
				'timeStamp'         => array( 'timeStamp', false ),
			);
			return $sortable_columns;
		}

		/**
		 *  Utility function to be passed to `usort` to reorder the sortable columns
		 *  according to our preferences (ascending or descending),
		 *
		 *  @param string[] $a one of the strings to be compared inside `usort`.
		 *  @param string[] $b the other string to be compared with.
		 *  @phan-param array{string:string} $a
		 *  @phan-param array{string:string} $a
		 *  @return boolean result of comparing a with b, returning `asc` (ascending) for true, false otherwise.
		 */
		private function usort_reorder( $a, $b ) {
			// If no sort, default to avatarDisplayName.
			$orderby = esc_attr( $_GET['orderby'] ?? 'avatarDisplayName' );
			// If no order, default to asc.
			$order = esc_attr( $_GET['order'] ?? 'asc' );
			// Determine sort order.
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			// Send final sort direction to usort.
			return 'asc' === $order ? $result : -$result;
		}

		/**
		 *  Utility function to get the current WP settings
		 *  according to our preferences (ascending or descending)
		 *  and to process bulk actions.
		 *
		 *  @return void
		 */
		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			// $settings might have changed in the mean time due to bulk actions.
			$this->internal_settings = maybe_unserialize(
				get_option( 'online_status_insl_settings' )
			);

			// now process bulk actions!
			$this->process_bulk_action();

			// sort our data, if it exists (to avoid an error).
			if ( ! empty( $this->internal_settings ) ) {
				usort( $this->internal_settings, array( &$this, 'usort_reorder' ) );
			}
			// assign sorted data to items and hope it works!
			$this->items = $this->internal_settings;
		}

		/**
		 *  Edge case function to deal with the special case that there are no items on the table
		 *  because no avatars have been configured yet.
		 *
		 *  @return void
		 */
		public function no_items() {
			esc_html_e( 'No avatars are being tracked.', 'online-status-insl' );
		}
	} // end class Online_Status_InSL_List_Table
} // end if class_exists
