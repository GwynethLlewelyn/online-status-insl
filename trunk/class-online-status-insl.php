<?php
/**
 *  Main class for the widget provided by this plugin.
 *
 *  @category OnlineStatusInSL
 *  @package  OnlineStatusInSL
 *  @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 *  @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 *  @version  1.5.1
 *  @link     https://gwynethllewelyn.net/online-status-insl/
 */

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
			extract( $args, EXTR_SKIP );
			echo $before_widget ?? '';
			$title = empty( $instance['title'] )
				? '&nbsp;'
				: apply_filters( 'widget_title', $instance['title'] );

			if ( ! empty( $title ) ) {
				echo $before_title ?? '', $title ?? '', $after_title ?? '';
			}

			// Code to check the in-world thingy and spew out results.

			// Get list of tracked avatars from the WordPress settings.

			$settings = maybe_unserialize( get_option( 'Online_Status_InSL_settings' ) );

			// Objects in settings are now indexed by object key.
			$object_key = $instance['object_key'] ?? NULL_KEY;

			// To get the avatar name for this object, we need to use its key...
			$avatar_display_name = $settings[ $object_key ]['avatarDisplayName'] ?? __( '(unknown avatar)', 'Online_Status_InSL' );
			// Similar for PermURL...
			$perm_url = $settings[ $object_key ]['PermURL'] ?? __( '(invalid URL)', 'Online_Status_InSL' );
			?>
	<div class='osinsl'>
			<?php
			// See if the user wants us to place a profile picture for the avatar.
			// Note that we're using the default alignleft, aligncenter, alignright classes for WP.
			if ( ! empty( $instance['profile_picture'] ) && 'none' !== $instance['profile_picture'] ) {
				$avatar_name_sanitised = sanitise_avatarname( $avatar_display_name );
			?>
		<a href="https://my.secondlife.com/<?php echo $avatar_name_sanitised; ?>" target="_blank">
			<img class="osinsl-profile-picture align<?php echo esc_attr( $instance['profile_picture'] ); ?>"
				src="https://my-secondlife.s3.amazonaws.com/users/<?php echo $avatar_name_sanitised; ?>/thumb_sl_image.png"
				width="80" height="80"
				alt="<?php echo esc_attr( $avatar_display_name ); ?>"
				title="<?php echo esc_attr( $avatar_display_name ); ?>" valign="top">
		</a>
		<br />
		<?php
			} // if picture == none, do not put anything here

			// does this widget have an associated in-world object?
			if ( empty( $settings ) || empty( $settings[ $object_key ]['Status'] ) ) {
		?>
		<span class="osinsl-unconfigured"><?php esc_attr_e( $instance['unconfigured'] ); ?></span>
		<?php
			} else {
		?>
		<span class="osinsl-before-status"><?php esc_attr_e( $instance['before_status'] ); ?></span>
		<span class="osinsl-status"><?php esc_attr_e( $settings[ $object_key ]['Status'] ); ?></span>
		<span class="osinsl-after-status"><?php esc_attr_e( $instance['after_status'] ); ?></span>
	</div>
<?php		}
			// return to widget handling code
			echo $after_widget ?? '';
		} // end function widget()

		/**
		  *  The WP core calls this method when the widget gets updated by the user.
		  *
		  *  @param string[] $new_instance (one wonders why this gets passed at all).
		  *  @param string[] $old_instance
		  *  @return string[] with the new instance.
		 *  @phan-return array{}
		  */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title']           = strip_tags( $new_instance['title'] );
			$instance['avatar_name']     = strip_tags( $new_instance['avatar_name'] ); // probably not needed
			$instance['object_key']      = strip_tags( $new_instance['object_key'] );
			$instance['before_status']   = strip_tags( $new_instance['before_status'] );
			$instance['after_status']    = strip_tags( $new_instance['after_status'] );
			$instance['having_problems'] = strip_tags( $new_instance['having_problems'] );
			$instance['unconfigured']    = strip_tags( $new_instance['unconfigured'] );
			$instance['profile_picture'] = strip_tags( $new_instance['profile_picture'] );
			return $instance;
		} // end public function update()

		/**
		 *  Back end, the interface shown in the _Appearance -> Widgets_ administration interface.
		 *
		 *  @param string[] $instance of the widget for which we are displaying the form.
		 *  @phan-param array{string, string} $instance
		 *  @return void
		 */
		public function form( $instance )
		{
			$defaults = array(
				'title'             => __( 'Second Life Online Status', 'online-status-insl' ),
				'avatar_name'       => '', // probably not needed, we get this from the settings.
				'object_key'        => NULL_KEY,
				'before_status'     => __( 'I am ', 'online-status-insl' ),
				'after_status'      => __( ' in Second Life.', 'online-status-insl' ),
				'having_problems'   => __(
					'having problems contacting RPC server...',
					'online-status-insl'
				),
				'unconfigured'      => __(
					'Please set up your in-world object first',
					'online-status-insl'
				),
				'profile_picture'   => __( 'none', 'online-status-insl' ),
			);

			$instance = wp_parse_args( (array) $instance, $defaults );

			$title    = strip_tags( $instance['title'] );

			// Get the saved options; this will allow us to choose avatar names from
			// registered in-world objects (which are indexed by object key)
			// and assign this widget to one avatar name.
			$settings = maybe_unserialize( get_option( 'Online_Status_InSL_settings' ) );

			// The obvious problem is selecting avatars that have the same name on different grids;
			// thus we try to get the location as well, to help the user.
			if (
				empty( $instance['avatar_name'] ) &&
				( ! empty( $instance['object_key'] ) && NULL_KEY !== $instance['object_key'] )
			) {
				$instance['avatar_name'] = $settings[$instance['object_key']]['avatarName'];
			}
			// try to fill in something...
?>
<p>
	<label for="<?php esc_attr_e( $this->get_field_id( 'title' ), 'online-status-insl' ); ?>">
		<?php _e('Title', 'online-status-insl'); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id( 'title' ), 'online-status-insl' ); ?>"
			name="<?php esc_attr_e( $this->get_field_name( 'title' ),	'online-status-insl' ); ?>"
			type="text"
			value="<?php esc_attr_e( $title, 'online-status-insl' ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id('object_key' ), 'online-status-insl' ); ?>">
		<?php _e( 'Avatar Name', 'online-status-insl' ); ?>:
	</label>
	<select class="widefat"
		id="<?php esc_attr_e( $this->get_field_id( 'object_key' ), 'online-status-insl') ; ?>"
		name="<?php esc_attr_e( $this->get_field_name( 'object_key' ), 'online-status-insl' ); ?>" style="width:100%;">
	<?php // now loop through all avatar names!

	if ( ! empty( $settings ) ) {
		foreach ( $settings as $oneTrackedObject ) {
			// parse name of the region and coordinates to help to identify tracked object.
			$regionName = substr(
				$oneTrackedObject['objectRegion'],
				0,
				strpos( $oneTrackedObject['objectRegion'], '(' ) - 1
			);
			$coords = trim( $oneTrackedObject['objectLocalPosition'], '() \t\n\r' );
			$xyz    = explode( ',', $coords );

			// Output a dropbox option with 'Avatar Name [Region (x,y,z)]'.
?>
		<option <?php if ( $oneTrackedObject['objectKey'] == $instance['object_key'] ) : ?>
			selected="selected"
				<?php endif; ?>
			value="<?php esc_attr_e( $oneTrackedObject['objectKey'] ); ?>">
			<?php esc_attr_e(
				sprintf(
					"%s [%s (%d,%d,%d)]",
					$oneTrackedObject['avatarDisplayName'],
					$regionName,
					$xyz[0],
					$xyz[1],
					$xyz[2]
				)
			); ?>
		</option>
<?php
		}
	}
	// never configured before; moved to have a 'disabled' setting.
	else {
?>
		<option disabled="disabled">--<?php _e(	'Unconfigured',	'online-status-insl'  ); ?>--</option>
<?php
	}
?>
	</select>
	<label for="<?php esc_attr_e( $this->get_field_id( 'before_status' ), 'online-status-insl' ); ?>">
		<?php _e('Before status message', 'online-status-insl'); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id( 'before_status' ), 'online-status-insl' ); ?>"
			name="<?php esc_attr_e(	$this->get_field_name( 'before_status' ), 'online-status-insl' ); ?>" type="text"
			value="<?php esc_attr_e( $instance['before_status'], 'online-status-insl' ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id( 'after_status' ), 'online-status-insl' ); ?>">
		<?php _e( 'After status message', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id( 'after_status' ), 'online-status-insl' ); ?>"
			name="<?php esc_attr_e( $this->get_field_name( 'after_status' ), 'online-status-insl' ); ?>" type="text"
			value="<?php esc_attr_e( $instance['after_status'], 'online-status-insl' ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id( 'having_problems' ), 'online-status-insl' ); ?>">
		<?php _e( 'Error message when communicating with SL', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id( 'having_problems' ), 'online-status-insl' ); ?>"
			name="<?php esc_attr_e(	$this->get_field_name( 'having_problems' ), 'online-status-insl' ); ?>"
			type="text"
			value="<?php esc_attr_e( $instance['having_problems'], 'online-status-insl' ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id( 'unconfigured' ), 'online-status-insl' ); ?>">
		<?php _e( 'Widget not configured message', 'online-status-insl' ); ?>:
		<input class="widefat"
			id="<?php esc_attr_e( $this->get_field_id( 'unconfigured' ), 'online-status-insl'	); ?>"
			name="<?php esc_attr_e( $this->get_field_name( 'unconfigured' ), 'online-status-insl' ); ?>"
			type="text"
			value="<?php esc_attr_e( $instance['unconfigured'], 'online-status-insl' ); ?>"
		/>
	</label>
	<label for="<?php esc_attr_e( $this->get_field_id( 'profile_picture' ), 'online-status-insl' ); ?>">
		<?php _e( 'Profile picture?', 'online-status-insl' ); ?>
	</label>
	<select id="<?php esc_attr_e( $this->get_field_id( 'profile_picture' ), 'online-status-insl' ); ?>"
		name="<?php esc_attr_e( $this->get_field_name( 'profile_picture' ), 'online-status-insl' ); ?>" class="widefat">
		<option <?php if ( 'none' == $instance['profile_picture'] ) {
			echo 'selected="selected"';
		} ?>>none</option>
		<option <?php if ( 'center' == $instance['profile_picture'] ) {
			echo 'selected="selected"';
		} ?>>center</option>
		<option <?php if ( 'left' == $instance['profile_picture'] ) {
			echo 'selected="selected"';
		} ?>>left</option>
		<option <?php if ( 'right' == $instance['profile_picture'] ) {
			echo 'selected="selected"';
		} ?>>right</option>
	</select>
</p>
<?php
		}	// end function form()
	}	// end class Online_Status_InSL
}	// end if class_exists
