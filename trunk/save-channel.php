<?php
/**
 *  Saves a Second Life callback.
 *
 * This gets called from a Second Life object when registering and when changing status.
 * Most of the data will come from the headers (e.g. avatar UUID).
 * `action` will be set to `register` (empty on legacy code) or `status`
 * perm_url is the SL-assigned URL during registration to call the object back
 * Our script will also send avatar_name and object_version
 *
 *  @category OnlineStatusInSL
 *  @package  OnlineStatusInSL
 *  @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 *  @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 *  @version  1.5.1
 *  @link     https://gwynethllewelyn.net/online-status-insl/
 */

if ( empty( $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'] ) ) {
	header( 'HTTP/1.0 405 Method Not Allowed' );
	header( 'Content-type: text/plain; charset=utf-8' );
	die( esc_attr__( 'Request has to come from Second Life or OpenSimulator', 'online-status-insl' ) );
}

require_once '../../../wp-blog-header.php';

if ( ! empty( $_REQUEST['action'] ) && 'status' === $_REQUEST['action'] ) {
	$settings = maybe_unserialize( get_option( 'online_status_insl_settings' ) );

	// Change settings just for this OBJECT.
	// $avatar_legacy_name = $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'];
	if ( ! empty( $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] ) ) {
		$object_key = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] );
	}

	if ( $settings[ $object_key ] ) {
		$settings[ $object_key ]['Status']            = wp_unslash( $_REQUEST['status'] ?? '' );
		$settings[ $object_key ]['avatarDisplayName'] = wp_unslash( $_REQUEST['avatar_name'] ?? '' );
		$settings[ $object_key ]['timeStamp']         = time();

		update_option( 'online_status_insl_settings', $settings );

		header( 'HTTP/1.0 200 OK' );
		header( 'Content-type: text/plain; charset=utf-8' );
		printf(
			// translators: in-world status (online/offline/unknown), avatar display name, avatar key (UUID).
			esc_attr__( "Status '%1\$s' set for '%2\$s' (%3\$s)", 'online-status-insl' ),
			esc_attr( $_REQUEST['status'] ),
			esc_attr( $settings[ $object_key ]['avatarDisplayName'] ),
			esc_attr( $settings[ $object_key ]['avatarKey'] )
		);
	} else {
		header( 'HTTP/1.0 404 Avatar not found' );
		header( 'Content-type: text/plain; charset=utf-8' );
		// translators: the sentence begins with the avatar name.
		esc_attr_e( ( $_REQUEST['avatar_name'] . ' is not yet registered!' ), 'online-status-insl' );
	}
} else {
	// clean up with esc_url, but _avoid_ the 'display' context which will mess everything up (gwyneth 202220105).
	$perm_url = esc_url( $_REQUEST['PermURL'], null, 'none' );
	if ( ! empty( $perm_url ) ) {
		$avatar_key            = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_OWNER_KEY'] );
		$avatar_legacy_name    = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'] );
		$avatar_display_name   = wp_unslash( $_REQUEST['avatar_name'] );
		$object_version        = wp_unslash( $_REQUEST['object_version'] ); // We'll ignore versions for now.
		$object_key            = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] );
		$object_name           = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME'] ); // This will allow us to do some fancy editing.
		$object_region         = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_REGION'] );
		$object_local_position = wp_unslash( $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'] );

		// Now get the whole serialised array!

		$settings = maybe_unserialize( get_option( 'online_status_insl_settings' ) );

		$settings[ $object_key ] = array(
			'avatarDisplayName'   => $avatar_display_name,
			'Status'              => wp_unslash( $_REQUEST['status'] ) ?? 'state unknown',
			'PermURL'             => $perm_url,
			'avatarKey'           => $avatar_key,
			'objectVersion'       => $object_version,
			'objectKey'           => $object_key,
			'objectName'          => $object_name,
			'objectRegion'        => $object_region,
			'objectLocalPosition' => $object_local_position,
			'timeStamp'           => time(),
		);

		update_option( 'online_status_insl_settings', $settings );

		header( 'HTTP/1.0 200 OK' );
		header( 'Content-type: text/plain; charset=utf-8' );
		printf(
			// translators: URL, avatar display name, object name, object key.
			esc_attr__( 'PermURL %1$s saved for user "%2$s" using object named "%3$s" (%4$s)', 'online-status-insl' ),
			esc_url( $settings[ $object_key ]['PermURL'], null, 'none' ),
			$settings[ $object_key ]['avatarDisplayName'],
			$settings[ $object_key ]['objectName'],
			$settings[ $object_key ]['object_key']
		);
	} else {
		header( 'HTTP/1.0 405 Method Not Allowed' );
		header( 'Content-type: text/plain; charset=utf-8' );
		esc_attr_e( 'No PermURL specified on registration', 'online-status-insl' );
	}
}
