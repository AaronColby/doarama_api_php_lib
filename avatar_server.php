<?php
	//!! Important make sure there is no white space before or after your PHP tags.

	//Request = http://www.yourwebsite.com/avatar/1234
	//.htaccess converts to = http://www.yourwebsite.com/avatar_server.php?activity_id=1234
	
	// In this example I've setup the following structure
	// /root/avatars/defaults/ - contains 0.jpg .. 99.jpg
	// /root/avatars/<folderID>/<user_id>.jpg
	
	/*
	* Defaults exists for users who haven't created avatars yet
	* I pick defaults by doing the following: user_id%100 = default_id
	*
	* I decided to group user avatars into folders of 100.  I get the foler ID by doing floor(user_id/100) = folderID
	* From there I just reference the user_id direclty as '<user_id>.jpg'.  The whole path for user_id 55 would be:
	* /root/avatars/0/55.jpg
	*/
	
	$activity_id = $_GET["activity_id"];
	
	//TODO: replace with your own DB implementation
	//Getting the user_id from the DB based on activity_id provided by Doarama request
	$conn = initDB();
	
	$sql = "SELECT user_id FROM user_activities WHERE activity_id = ?";
	$params = array($activity_id);
	$types = "i";
	$rows = run_stmt_query($conn, $sql, $types, $params);
	$user_id = $rows[0]["user_id"];
	
	$folder_id = floor($user_id / 100); 
	$avatar_path = "/filesystem_root/avatars/$folder_id/$user_id.jpg";
	if (!file_exists($avatar_path)){
			$default_id = $user_id % 100;
			$avatar_path = "/filesystem_root/defaults/$default_id.jpg";
			if (!file_exists($avatar_path)){
				error_log('Could not find default avatar at $avatar_path');
				exit;
			}
	}

	$fp = fopen($avatar_path, 'rb');

	// send the right headers
	header("Content-Type: image/jpeg");
	header("Content-Length: " . filesize($avatar_path));

	// dump the picture and stop the script
	fpassthru($fp);
?>
