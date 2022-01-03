<?php
// This gets called from a Second Life object when registering and when changing status
// Most of the data will come from the headers (e.g. avatar UUID)
// "action" wil be set to "register" (empty on legacy code) or "status"
// PermURL is the SL-assigned URL during registration to call the object back 
// Our script will also send avatar_name and object_version

if (!$_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'])
{
	header("HTTP/1.0 405 Method Not Allowed");
	header("Content-type: text/plain; charset=utf-8");
	die(__("Request has to come from Second Life or OpenSimulator", 'online-status-insl'));
}

require_once('../../../wp-blog-header.php');

if ($_REQUEST['action'] == 'status')
{
	$settings = maybe_unserialize(get_option('online_status_insl_settings'));
	
	// change settings just for this OBJECT
//	$avatarLegacyName = $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'];
	$objectKey = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];

	if ($settings[$objectKey]) // do we have this object on the tracked list?
	{
		$settings[$objectKey]["Status"] = $_REQUEST['status'];
		$settings[$objectKey]["avatarDisplayName"] = $_REQUEST['avatar_name'];
		$settings[$objectKey]["timeStamp"] = time();
		
		update_option('online_status_insl_settings', $settings);

		header("HTTP/1.0 200 OK");
		header("Content-type: text/plain; charset=utf-8");
		printf(__("Status '%s' set for '%s' (%s)", 'online-status-insl'), $_REQUEST['status'], $settings[$objectKey]["avatarDisplayName"], $settings[$objectKey]["avatarKey"]);
	}
	else
	{
		header("HTTP/1.0 404 Avatar not found");
		header("Content-type: text/plain; charset=utf-8");
		echo($_REQUEST['avatar_name'] . __(" is not yet registered!", 'online-status-insl'));
	}
	
}
else if ($permURL = $_REQUEST['PermURL']) // assume it's a registration (legacy support)
{
	$avatarKey				= $_SERVER['HTTP_X_SECONDLIFE_OWNER_KEY'];
	$avatarLegacyName		= $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'];
	$avatarDisplayName		= $_REQUEST['avatar_name'];
	$objectVersion			= $_REQUEST['object_version']; // we'll ignore versions for now
	$objectKey				= $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];
	$objectName				= $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME']; // to do some fancy editing
	$objectRegion			= $_SERVER['HTTP_X_SECONDLIFE_REGION'];
	$objectLocalPosition	= $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
	
	// now get the whole serialised array
		
	$settings = maybe_unserialize(get_option('online_status_insl_settings'));

	$settings[$objectKey] = array(
		"avatarDisplayName" 	=> $avatarDisplayName,
		"Status"				=> $_REQUEST['status'] ? $_REQUEST['status'] : 'state unknown', 
		"PermURL"				=> $_REQUEST['PermURL'], 
		"avatarKey"				=> $avatarKey,
		"objectVersion" 		=> $objectVersion, 
		"objectKey"				=> $objectKey, 
		"objectName"			=> $objectName, 
		"objectRegion"			=> $objectRegion, 
		"objectLocalPosition"	=> $objectLocalPosition,
		"timeStamp"				=> time()
	);
	
	update_option('online_status_insl_settings', $settings);
	
	header("HTTP/1.0 200 OK");
	header("Content-type: text/plain; charset=utf-8");
	printf(__('PermURL %s saved for user "%s" using object named "%s" (%s)', 'online-status-insl'), $settings[$objectKey]["PermURL"], $settings[$objectKey]["avatarDisplayName"], $settings[$objectKey]["objectName"], $settings[$objectKey]["objectKey"]);
}
else
{
	header("HTTP/1.0 405 Method Not Allowed");
	header("Content-type: text/plain; charset=utf-8");
	_e("No PermURL specified on registration", 'online-status-insl');
}
?>