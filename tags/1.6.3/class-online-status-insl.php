<?php
/**
 *  Main class for the widget provided by this plugin.
 *
 *  @category OnlineStatusInSL
 *  @package  OnlineStatusInSL
 *  @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 *  @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 *  @version  1.6.3
 *  @link     https://gwynethllewelyn.net/online-status-insl/
 */

// Do not allow direct access to this file.
if ( ! defined( 'WPINC' ) ) {
	die( 'Direct script access denied.' ); // no translation, because `__()` may not be available...
}

if ( ! class_exists( 'Online_Status_InSL' ) ) {
	/**
	 *  Widget class for this plugin starts here.
	 *  It extends WP_Widget like all WP widget classes should do.
	 */
	class Online_Status_InSL extends WP_Widget {
		/**
		 *  Version of this plugin (the constructor will fill it in).
		 *
		 *  @var string $plugin_version
		 */
		public static $plugin_version;

		private const ONLINE_STATUS_INSL_VALID_KSES_TAGS = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'i'      => array(),
			'strong' => array(),
			'b'      => array(),
		);

		/**
		 *  Constructor for the widget class.
		 *
		 *  @return void
		 */
		public function __construct() {
			// This might never be called, so it gets updated from other locations too.
			if ( ! self::$plugin_version ) {
				$plugin_data          = get_file_data(
					__FILE__,
					array(
						'Version' => 'Version',
					)
				);
				self::$plugin_version = $plugin_data['Version'];
			}
			$osinsl_widget_ops = array(
				'classname'   => 'Online_Status_InSL',
				'description' => __( 'Online Status inSL', 'online-status-insl' ),
			);
			parent::__construct(
				'Online_Status_InSL_widget',
				__( 'Online Status inSL Widget', 'online-status-insl' ),
				$osinsl_widget_ops
			);
		}

		/**
		 *  This is the code that gets displayed on the UI side, what readers see.
		 *
		 *  @param array $args Arguments that the widget function gets passed from the WP core.
		 *  @param array $instance of this particular widget (there can be several similar widgets on a sidebar).
		 *  @return void
		 */
		public function widget( $args, $instance ) {
			/**
			 *  The variables below are extracted.
			 *
			 *  @var string $before_widget HTML set by user to be shown before the widget is printed
			 *  @var string $title         Widget title, which may even be set from a filter.
			 *  @var string $after_widget  HTML set by user to be shown after the widget is printed
			 */
			echo wp_kses( $args['before_widget'] ?? '', self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$title = empty( $instance['title'] )
				? '&nbsp;'
				: apply_filters( 'widget_title', $instance['title'] );

			if ( ! empty( $title ) ) {
				echo wp_kses( $args['before_title'] . ' ' . $title . ' ' . $args['after_title'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			}

			// Code to check the in-world thingy and spew out results.

			// Get list of tracked avatars from the WordPress settings.

			$settings = maybe_unserialize( get_option( 'online_status_insl_settings' ) );

			// Objects in settings are now indexed by object key.
			$object_key = $instance['object_key'] ?? NULL_KEY;

			// To get the avatar name for this object, we need to use its key...
			$avatar_display_name = $settings[ $object_key ]['avatarDisplayName'] ?? __( '(unknown avatar)', 'Online_Status_InSL' );

			// Similar for PermURL...
			// with a catch: we now do an extra check to see if the avatar comes from the Second Life grid or an OpenSimulator grid;
			// this will matter down below when we address the issue of profile pics & links (gwyneth 20220106).
			$perm_url = $settings[ $object_key ]['PermURL'] ?? '';

			/**
			 *  Because of scope issues, `$in_secondlife` is set here to `false` by default,
			 *  and changed below if we properly detect that the request comes from Second Life.
			 *  (gwyneth 20220106)
			 *
			 *  @var boolean
			 */
			$in_secondlife = false;
			if ( empty( $perm_url ) ) {
				$perm_url = esc_attr__( '(invalid URL)', 'Online_Status_InSL' );
			} else {
				$in_secondlife = ( false !== stripos( $perm_url, 'secondlife' ) ); // making sure that this is boolean  (gwyneth 20220106)!
			}
			?>
	<div class='osinsl'>
			<?php
			// See if the user wants us to place a profile picture for the avatar.
			// Note that we're using the default alignleft, aligncenter, alignright classes for WP.
			if ( ! empty( $instance['profile_picture'] ) && 'none' !== $instance['profile_picture'] && $in_secondlife ) {
				$avatar_name_sanitised = sanitise_avatarname( $avatar_display_name );
				?>
		<a href="https://my.secondlife.com/<?php echo esc_attr( $avatar_name_sanitised ); ?>" target="_blank">
		<img class="osinsl-profile-picture align<?php echo esc_attr( $instance['profile_picture'] ); ?>"
			src="https://my-secondlife.s3.amazonaws.com/users/<?php echo esc_attr( $avatar_name_sanitised ); ?>/thumb_sl_image.png"
			width="80" height="80"
			alt="<?php echo esc_attr( $avatar_display_name ); ?>"
			title="<?php echo esc_attr( $avatar_display_name ); ?>" valign="top">
		</a>
		<br />
				<?php
			} // if picture == none, or if this avatar comes from OpenSimulator, do not put anything here.

			// does this widget have an associated in-world object?
			if ( empty( $settings ) || empty( $settings[ $object_key ]['Status'] ) ) {
				?>
		<span class="osinsl-unconfigured"><?php echo wp_kses( $instance['unconfigured'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?></span>
				<?php
			} else {
				?>
		<span class="osinsl-before-status"><?php echo wp_kses( $instance['before_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?></span>
		<span class="osinsl-status"><?php echo wp_kses( $settings[ $object_key ]['Status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?></span>
		<span class="osinsl-after-status"><?php echo wp_kses( $instance['after_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?></span>
	</div>
				<?php
			}
			// return to widget handling code.
			echo wp_kses( $args['after_widget'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
		} // end function widget

		/**
		 *  The WP core calls this method when the widget gets updated by the user.
		 *
		 *  It now includes our 'own' check of tags for `wp_kses()` in an attempt
		 *  to preserve _some_ of the perfectly valid tags (gwyneth 20220106).
		 *
		 *  @param string[] $new_instance (allegedly, these are the settings we get via form).
		 *  @param string[] $old_instance The (full set of the) old settings for this widget.
		 *  @return string[]|false Returns the new instance, or false for no update.
		 *  @phan-return array{}
		 */
		public function update( $new_instance, $old_instance ) {
			$instance                    = $old_instance;
			$instance['title']           = wp_kses( $new_instance['title'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['avatar_name']     = wp_kses_post( $new_instance['avatar_name'] ?? '(???)' ); // probably not needed...
			$instance['object_key']      = wp_kses( $new_instance['object_key'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['before_status']   = wp_kses( $new_instance['before_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['after_status']    = wp_kses( $new_instance['after_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['having_problems'] = wp_kses( $new_instance['having_problems'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['unconfigured']    = wp_kses( $new_instance['unconfigured'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			$instance['profile_picture'] = wp_kses( $new_instance['profile_picture'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );
			return $instance;
		} // end public function update

		/**
		 *  Back end, the interface shown in the _Appearance -> Widgets_ administration interface.
		 *
		 *  @param string[] $instance of the widget for which we are displaying the form.
		 *  @phan-param array{string, string} $instance
		 *  @return void
		 */
		public function form( $instance ) {
			$defaults = array(
				// translators: this is the title of the *widget* by default.
				'title'           => __( 'Second Life Online Status', 'online-status-insl' ),
				'avatar_name'     => '', // probably not needed, we get this from the settings.
				'object_key'      => NULL_KEY,
				// translators: what ought to appear *before* the online/offline status.
				'before_status'   => __( 'I am ', 'online-status-insl' ),
				// translators: what ought to appear *after* the online/offline status.
				'after_status'    => __( ' in Second Life.', 'online-status-insl' ),
				// translators: the message replacing status/after_status if WordPress cannot contact the in-world object.
				'having_problems' => __(
					'having problems contacting the in-world object...',
					'online-status-insl'
				),
				'unconfigured'    => __(
					'Please set up your in-world object first',
					'online-status-insl'
				),
				// translators: when the user is asked for a profile picture alignment, 'none' means *not* to display the profile picture at all.
				'profile_picture' => __( 'none', 'online-status-insl' ),
			);

			$instance = wp_parse_args( (array) $instance, $defaults );
			$title    = wp_kses( $instance['title'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS );

			// Get the saved options; this will allow us to choose avatar names from
			// registered in-world objects (which are indexed by object key)
			// and assign this widget to one avatar name.
			$settings = maybe_unserialize( get_option( 'online_status_insl_settings' ) );

			// The obvious problem is selecting avatars that have the same name on different grids;
			// thus we try to get the location as well, to help the user.
			if (
				empty( $instance['avatar_name'] ) &&
				( ! empty( $instance['object_key'] && NULL_KEY !== $instance['object_key'] ) )
			) {
				if ( defined( WP_DEBUG ) ) {
					// phpcs:disable WordPress.PHP.DevelopmentFunctions
					error_log(
						wp_sprintf(
							'DEBUG: instance[avatar_name]: "%1$s" instance[object_key]: "%2$s" settings[instance[object_key]] "%3$s" and finally: what we\'re assigning, after all: "%4$s"',
							$instance['avatar_name'],
							$instance['object_key'],
							print_r( $settings[ $instance['object_key'] ], true ), // phpcs:ignore
							$settings[ $instance['object_key'] ]['avatarDisplayName'] ?? 'invalid avatar display name'
						)
					);
					// phpcs:enable
				}
				$instance['avatar_name'] = $settings[ $instance['object_key'] ]['avatarDisplayName'] ?? '(???)';
			}
			// try to fill in something...
			?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
			<?php esc_attr_e( 'Title', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			type="text"
			value="<?php echo esc_attr( $title ); ?>"
		/>
	</label>
	<label for="<?php echo esc_attr( $this->get_field_id( 'object_key' ) ); ?>">
			<?php esc_attr_e( 'Avatar Name', 'online-status-insl' ); ?>:
	</label>
	<select class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'object_key' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'object_key' ) ); ?>" style="width:100%;">
			<?php
			// now loop through all avatar names!

			if ( ! empty( $settings ) ) {
				foreach ( $settings as $one_tracked_object ) {
					// parse name of the region and coordinates to help to identify tracked object.
					$region_name = substr(
						$one_tracked_object['objectRegion'],
						0,
						strpos( $one_tracked_object['objectRegion'], '(' ) - 1
					);
					$coords      = trim( $one_tracked_object['objectLocalPosition'], '() \t\n\r' );
					$xyz         = explode( ',', $coords );

					// Output a dropbox option with 'Avatar Name [Region (x,y,z)]'.
					?>
		<option <?php if ( $one_tracked_object['objectKey'] === $instance['object_key'] ) : ?>
			selected="selected"
				<?php endif; ?>
			value="<?php echo esc_attr( $one_tracked_object['objectKey'] ); ?>">
					<?php
					echo esc_attr(
						wp_sprintf(
							'%s [%s (%d,%d,%d)]',
							$one_tracked_object['avatarDisplayName'],
							$region_name,
							$xyz[0],
							$xyz[1],
							$xyz[2]
						)
					);
					?>
		</option>
					<?php
				}
			} else {
				// never configured before; moved to have a 'disabled' setting.
				?>
		<option disabled="disabled">--<?php esc_attr_e( 'Unconfigured', 'online-status-insl' ); ?>--</option>
					<?php
			} // end empty settings.
			?>
	</select>
	<label for="<?php echo esc_attr( $this->get_field_id( 'before_status' ) ); ?>">
			<?php esc_attr_e( 'Before status message', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'before_status' ) ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'before_status' ) ); ?>" type="text"
			value="<?php echo wp_kses( $instance['before_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?>"
		/>
	</label>
	<label for="<?php echo esc_attr( $this->get_field_id( 'after_status' ) ); ?>">
			<?php esc_attr_e( 'After status message', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'after_status' ) ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'after_status' ) ); ?>" type="text"
			value="<?php echo wp_kses( $instance['after_status'], self::ONLINE_STATUS_INSL_VALID_KSES_TAGS ); ?>"
		/>
	</label>
	<label for="<?php echo esc_attr( $this->get_field_id( 'having_problems' ) ); ?>">
			<?php esc_attr_e( 'Error message when communicating with SL', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'having_problems' ) ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'having_problems' ) ); ?>"
			type="text"
			value="<?php echo esc_attr( $instance['having_problems'] ); ?>"
		/>
	</label>
	<label for="<?php esc_attr( $this->get_field_id( 'unconfigured' ) ); ?>">
			<?php esc_attr_e( 'Widget not configured message', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'unconfigured' ) ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'unconfigured' ) ); ?>"
			type="text"
			value="<?php echo esc_attr( $instance['unconfigured'] ); ?>"
		/>
	</label>
	<label for="<?php echo esc_attr( $this->get_field_id( 'profile_picture' ) ); ?>">
			<?php /* translators: this question is for the profile picture alignment. */ esc_attr_e( 'Profile picture?', 'online-status-insl' ); ?>
	</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'profile_picture' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'profile_picture' ) ); ?>" class="widefat">
		<option <?php /* translators: this means no profile picture at all. */ echo ( 'none' === $instance['profile_picture'] ) ? 'selected="selected"' : ''; ?>><?php esc_attr_e( 'none', 'online-status-insl' ); ?></option>
		<option <?php /* translators: profile picture aligned to the center. */ echo ( 'center' === $instance['profile_picture'] ) ? 'selected="selected"' : ''; ?>><?php esc_attr_e( 'center', 'online-status-insl' ); ?></option>
		<option <?php /* translators: profile picture aligned to the left. */ echo ( 'left' === $instance['profile_picture'] ) ? 'selected="selected"' : ''; ?>><?php esc_attr_e( 'left', 'online-status-insl' ); ?></option>
		<option <?php /* translators: profile picture aligned to the right. */ echo ( 'right' === $instance['profile_picture'] ) ? 'selected="selected"' : ''; ?>><?php esc_attr_e( 'right', 'online-status-insl' ); ?></option>
	</select>
</p>
			<?php
		} // end function form.
	} // end class Online_Status_InSL.
} // end if class_exists.
