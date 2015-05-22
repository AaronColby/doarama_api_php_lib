<?php
	/* Sample of final Link
	http://api.doarama.com/api/0.2/visualisation?k=eMvBQak&avatarBaseUrl=http%3A%2F%2Fwww.socalxcleague.com%2Favatar%2F&dzml=http%3A%2F%2Fwww.socalxcleague.com%2Ftracklogs%2F2015%2Fsocalxcleague%2F2015-05-17%2Ftask.dzml&name=AaronP&name=AlexG&name=DmitriS&name=DougB&name=GregoryH&name=HenryB&name=JaiP&name=JasonK&name=JeffW&name=JeromeM&name=LenS&name=MarkG&name=PeterH&name=RussD
	*/
			
	/* In this case we have one visualization per day.
	 * $date = 'YYYY-MM-DD'
	 */
	function getDoaramaURL($league_nm, $date){
		//TODO, could get all doaramas at once eventually to optimize
		$league_key = $league_nm."-".$date;
		
		//TODO: replace with your own DB implementation
		//Getting our doarama key for the given date
		$conn = initDB();
		$sql = "SELECT doarama_key FROM doaramas WHERE league_key = ?"; 
		$rows = run_stmt_query($conn, $sql, "s", array($league_key));		

		$doarama_key = $rows[0]['doarama_key'];
		// Get names for activity, very important to sort by date_created to ensure that the names match the track logs
		$sql = "SELECT u.name
				FROM user_activity ua
				JOIN user u ON (u.user_id = ua.user_id)
				WHERE ua.league_key = ?
				ORDER BY ua.date_created";
		$rows = run_stmt_query($conn, $sql, "s", array($league_key));
		
		$url = "http://api.doarama.com/api/0.2/visualisation?k=$doarama_key";
		$url .= "&avatarBaseUrl=http%3A%2F%2Fwww.yourwebsite.com%2Favatar%2F";
		$url .= "&dzml=http%3A%2F%2Fwww.yourwebsite.com%2Ftask.dzml";
		foreach ($rows as $row){
			$name = $row["name"];
			$name =  ucwords(strtolower($name));
			$nameParts = preg_split("/ /", $name);
			$formattedName = "unknown";
			
			//Take a name like 'aaron price' and convert it to 'AaronP'
			if (count($nameParts) == 1){
				$formattedName = $nameParts[0];
			} elseif (count($nameParts) > 1){	
				$formattedName = $nameParts[0] . $nameParts[1][0];	
			}											
			$url .= "&name=$formattedName";
		}
		return $url;
	}
?>
