<?php
	$DOARAMA_API_NAME="foo";
	$DOARAMA_API_KEY="bar";

	//May need to change this depending on if you are on production or QA server
	$DOARAMA_BASE_URL = "https://api.doarama.com/api/0.2/";
	
	$MASTER_USER_ID = "1"; //Set this to something that will uniquely identify the creator of each visualization (kind of like an admin_id)
	
	/*
	* Routine to load individual tracks to Doarama, save the activity to
	* our local DB, then check if a visualization exists already.  If not
	* create a new visualization with the given track as the first entry
	* and save doarama_key for visualization in our local DB.  If a visualization
	* does exist, just add the tracklog to that existing visualization.  
	*
	* In this example we create one visualization per day.  Any activities
	* that happen on the same day get added to the same visualization
	
	* $track_filepath: server location of the IGC you want to upload
	* $user_id: unique id for the user's tracklog
	* $task_date: the day the tracklog occured (YYYY-MM-DD)
	*/
	function uploadTrack($track_filepath, $user_id, $task_date){
		//Upload track to Doarama (create new activity)
		$data = array('gps_track' => "@$track_filepath");
		$response = doCurl("activity", $data, $user_id);

		$data = json_decode($response, true);	
		$activity_id = $data["id"];
		
		//Next set activity info https://api.doarama.com/api/0.2/activityType
		/* 27	Fly - Hang Glide
		   28	Fly - Sailplane / Glider
		   29	Fly - Paraglide	*/
		$data = '{"activityTypeId":29}'; //TODO: refactor to not just be paragliding		
		doCurl("activity/$activity_id", $data, $user_id);

		//TODO: replace with your own DB initialization.  I set mine to transactional since I don't want to populate
		//it with bad entries if the track upload fails.
		$conn = initDB();	
		$conn->autocommit(FALSE);		
		
		//Create a key for our local DB that we can use to link to a visualization.
		//I use a key that given a date and a competition name will map to one specific visualization for that day
		$league_key = "socal-$task_date"; //TODO: factor out for other leagues.
		
		//Create an entry in the DB that maps a doarama activity_id to a unique_user_id in your local DB
		//Since naming pilots in the API is done in the order of upload, if you want to name the tracks with 
		//user names make sure to be able to query your DB for activities that map to the end visualization
		//and can be sorted in the order they were created.  I've setup my DB to populate date_created automatically
		$sql = "INSERT INTO user_activities (USER_ID, ACTIVITY_ID, LEAGUE_KEY) VALUES (?, ?, ?)";
		$types = "iis";
		$params = array($user_id,$activity_id, $league_key);
		insert_row( $conn, $sql, $types, $params );	//TODO replace with your own DB implementation

		//TODO: update with your own DB implementation
		//Try to find the doarama_key in our DB from our league_key, if we don't get any results it means we haven't
		//yet created a visualization so we'll need to make one and save the Doarama Key in our DB
		$sql = "SELECT doarama_key FROM doaramas WHERE league_key = ?"; 
		$rows = run_stmt_query($conn, $sql, "s", array($league_key));
		
		if (count($rows) < 1){	
			// Create Visualization
			$data = '{"activityIds":['.$activity_id.']}'; 
			$response = doCurl("visualisation", $data, $user_id);
			
			//get doarama_key
			$data = json_decode($response, true);	
			$doarama_key = $data["key"];			
				
			//TODO: update with your own DB implementation
			//insert into DB
			$sql = "INSERT INTO doaramas (league_key, doarama_key) VALUES (?, ?)";
			$types = "ss";
			$params = array($league_key, $doarama_key);
			insert_row( $conn, $sql, $types, $params );  			
			
		} else {
			$doarama_key = $rows[0]['doarama_key'];
			// Add activity to visualization			
			$data = '{"visualisationKey" : "'.$doarama_key.'", "activityIds": ['.$activity_id.']}'; 
			doCurl("visualisation/addActivities", $data, $user_id);
		}
		
		//TODO: update with your DB implementation
		//all done now, can commite the transaction
		$conn->commit();
		$conn->close();		
	}
	
	/*
	 * Somewhat generic CURL routine to do one of 4 requests:
	 * - upload activity
	 * - set activity info
	 * - create visualization
	 * - add activity to visualization
	 * 
	 * We detect if we are doing file upload or not and adjust the header
	 * parameters accordingly.
	 *
	 * $url_endpoint: gets added on to the global base url, this determines
	 *                the action we are doing
	 * $data: either the track_log or JSON data string of values to pass
	 *        to request
	 * $user_id: id to uniquely identify the owner of the activity or the
	 *           author of the visualization.
	 */
	function doCurl($url_endpoint, $data, $user_id){
		global $DOARAMA_API_NAME;
		global $DOARAMA_API_KEY;
		global $DOARAMA_BASE_URL;		
		//echo ("request: $url_endpoint - data: $data - user_id: $user_id<br>");
		// create a new cURL resource
		$ch = curl_init();

		$url = $DOARAMA_BASE_URL . $url_endpoint;
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$headers = array( 
			"api-name:$DOARAMA_API_NAME",
			"api-key:$DOARAMA_API_KEY",
			"user-id:$user_id",
			"Accept:application/json"
		);
			
		// If we aren't doing a track upload then add in headers for JSON data content
		if (!is_array($data)) {			
			array_push($headers, "Content-Type: application/json");
			array_push($headers, "Content-Length: " . strlen($data));
		}
	   
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); //could be refactored to constants package, global var, or similar
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 	
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$response = curl_exec($ch);
		//echo "response = $response<br>";
		if (curl_errno($ch)) { 
			error_log("Curl Error: " . curl_error($ch));
			error_log("Failed to upload track to doarama: $track_filepath");
		}
		
		// close cURL resource, and free up system resources
		curl_close($ch);

		return $response;
	}
?>
