<html>
   <head>
      
      <script type = "text/JavaScript">
         <!--
            function AutoRefresh( t ) {
               setTimeout("location.reload(true);", t);
            }
         //-->
      </script>
      <style type="text/css">
		@font-face {
		font-family: 'Fundamental';
		src: url('fonts/fundamentalbrigade.ttf') format('truetype');
		font-weight: normal;
		font-style: normal;
		}

		body {
			font-family: 'Fundamental', normal;
			letter-spacing:0.01em;
			font-size:5vw;
			color: white;
			white-space:nowrap;
			overflow:hidden;
			line-height: 38px;
		}

		h1 {
			font-family: 'Fundamental', normal;
			letter-spacing:0.01em;
			font-size:5vw;
			font-weight:normal;
			color: white;
			white-space:nowrap;
			overflow:hidden;
			text-align: center;
			padding-botton: 0vw;
		}
		
		h4 {
			font-family: 'Fundamental', normal;
			letter-spacing:0.01em;
			font-size:3vw;
			font-weight:normal;
			color: white;
			overflow-wrap: break-word;
			text-align: center;
			line-height: 4vw;
		}
		.type{
			height:8vw;
			width:auto;
			vertical-align:middle;
			padding: .1vw;
			-webkit-filter: drop-shadow(0.25vw 0.25vw 0.25vw rgba(0,0,0,0.9));
			filter: drop-shadow(0.25vw 0.25vw 0.25vw rgba(0,0,0,0.9));
		}

		table {
			font-size:2vh;
		}
		td {
			padding: 0.5vw;
		}
		.statusOFF { background-color: rgba(0, 0, 0, 0); color: White;} /* This is applied to the message before the status text when Off */
		.statusON { background-color: rgba(0, 0, 0, 0); color: White;} /* This is applied to the message before the status text when On */
		.outputOFF { color: Red; } /* This is applied only to the actual status text when Off */
		.outputON { color: Green; } /* This is applied only to the actual status text when On */
		
	    #scroll-container {
          height: 100%;
          overflow: hidden;
        }

		#scroll-text {
			height: 100%;
			text-align: center;
			
			/* animation properties */
			/* Negative for top to bottom. Positive for bottom to top */
			-moz-transform: translateY(-200%);
			-webkit-transform: translateY(-200%);
			transform: translateY(-200%);
			
			/* Modify the time to speed up or slow down the scroll speed */
			/* Change animation between top-to-bottom-animation or bottom-to-top-animation */
			-moz-animation: top-to-bottom-animation 30s linear infinite;
			-webkit-animation: top-to-bottom-animation 30s linear infinite;
			animation: top-to-bottom-animation 30s linear infinite;
		}

		/* Top to bottom Section */
		/* Top to Bottom for Firefox */
		@-moz-keyframes top-to-bottom-animation {
			from { -moz-transform: translateY(-150%); }
			to { -moz-transform: translateY(100%); }
		}

		/* Top to Bottom for Chrome */
		@-webkit-keyframes top-to-bottom-animation {
			from { -webkit-transform: translateY(-150%); }
			to { -webkit-transform: translateY(100%); }
		}

		@keyframes top-to-bottom-animation {
		from {
			-moz-transform: translateY(-150%);
			-webkit-transform: translateY(-150%);
			transform: translateY(-150%);
		}
		to {
			-moz-transform: translateY(100%);
			-webkit-transform: translateY(100%);
			transform: translateY(100%);
		}
		}

		/* Bottom to Top Section */
		/* Bottom to Top for Firefox */
		@-moz-keyframes bottom-to-top-animation {
			from { -moz-transform: translateY(100%); }
			to { -moz-transform: translateY(-150%); }
		}

		/* Bottom to Top for Chrome */
		@-webkit-keyframes bottom-to-top-animation {
			from { -webkit-transform: translateY(100%); }
			to { -webkit-transform: translateY(-150%); }
		}

		@keyframes bottom-to-top-animation {
			from {
				-moz-transform: translateY(100%);
				-webkit-transform: translateY(100%);
				transform: translateY(100%);
			}
			to {
				-moz-transform: translateY(-150%);
				-webkit-transform: translateY(-150%);
				transform: translateY(-150%);
			}
		}

		.requestor { color: white; } /** The class for ONLY the tag requestor */
		.requestor-data { color: white; } /** The class for ONLY the returned data for requestor */
		.song { color: white; } /** The class for ONLY the tag song */
		.song-data { color: white; } /** The class for ONLY the returned data for song */
		.score { color: white; } /** The class for ONLY the tag score */
		.score-data { color: white; } /** The class for ONLY the returned data for score */
		.award { color: white; } /** The class for ONLY the tag award */
		.award-data { color: white; } /** The class for ONLY the returned data for award */
	  </style>
 </head>
 
<?php

require_once ('config.php');

if(strtolower($_GET["data"])=="endscreenscroll"){ 
   echo "<body>";
   } else {
	echo "<body onload = \"JavaScript:AutoRefresh(5000);\">";
   }


if(!isset($_GET["data"])){die("No data set");}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function getLastRequest(){
	global $conn;

	$return = array('timestamp' => '','broadcaster' => '','username' => '','player_guid' => '');

	$lastRequest = array();
	$sql = "SELECT song_id,request_time,broadcaster,request_type 
			FROM sm_requests 
			WHERE state <> 'canceled' AND state <> 'skipped' 
			ORDER BY request_time DESC 
			LIMIT 1";
	$retval = mysqli_query( $conn, $sql );
	$lastRequest = mysqli_fetch_assoc($retval);
	$return["timestamp"] = $lastRequest["request_time"];
	$return["broadcaster"] = $lastRequest["broadcaster"];

	//get lastplayed timestamp
	$lastPlayed = array();
	$sql = "SELECT username,player_guid,lastplayed,datetime  
			FROM sm_songsplayed 
			ORDER BY lastplayed DESC 
			LIMIT 1 ";
	$retval = mysqli_query( $conn, $sql );
	$lastPlayed = mysqli_fetch_assoc($retval);

	$return["username"] = $lastPlayed["username"];
	$return["player_guid"] = $lastPlayed["player_guid"];
	
	if($lastPlayed["datetime"] > $lastRequest["request_time"]){
		//songsplayed datetime is later than the last request, use that timestamp
		$return["timestamp"] = $lastPlayed["datetime"];
	}

	return $return;
}

function format_pack($pack){
 	$pack = str_ireplace("Dance Dance Revolution","DDR",$pack);
	$pack = str_ireplace("DanceDanceRevolution","DDR",$pack);
	$pack = str_ireplace("Dancing Stage","DS",$pack);
	$pack = str_ireplace("DancingStage","DS",$pack);
	$pack = str_ireplace("In The Groove","ITG",$pack);
	$pack = str_ireplace("InTheGroove","ITG",$pack);
	//$pack = str_ireplace("Ben Speirs","BS",$pack);
	//$pack = str_ireplace("JBEAN Exclusives","JBEAN...",$pack);
	$pack = preg_replace("/(\(.*\).\(.*\))$/","",$pack,1);
return $pack;
}    

if(isset($_GET["session"]) && !empty($_GET["session"]) && is_numeric($_GET["session"])){
	$StreamSessionLength = mysqli_real_escape_string($conn,$_GET["session"]);
}else{
	$StreamSessionLength = 6; //stream session length in hours (default: 6)
}

$timestamp = getLastRequest()['timestamp'];

switch(strtolower($_GET["data"])){
////////REQUESTS/////////
	case "requests":	
		$sql = "SELECT COUNT(*) AS requestsToday FROM sm_requests WHERE state <> 'canceled' AND state <> 'skipped' AND request_time > DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR)";
		$retval = mysqli_query( $conn, $sql );

		$row = mysqli_fetch_assoc($retval);
		$requestsToday = $row["requestsToday"];

		echo "<body style=\"text-align: right;\">$requestsToday &nbsp; requests this session</body";
	break;
////////SONGS/////////
	case "songs":
		$sql = "SELECT COUNT(DISTINCT datetime) AS playedToday FROM sm_scores WHERE datetime > DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR)";
		$retval = mysqli_query( $conn, $sql );

		$row = mysqli_fetch_assoc($retval);
		$playedToday = $row["playedToday"];

		echo "<body style=\"text-align: right;\">$playedToday &nbsp; songs played this session</body>";
	break;
	case "scores":
////////SCORES/////////
		if(isset($scoreType)){
			switch ($scoreType){
				case "ddr":
					$tier = "ddr_tier";
					$grade = "ddr_grade";
					$score = "score";
				break;
				case "itg":
				default:
					$tier = "itg_tier";
					$grade = "itg_grade";
					$score = "percentdp";
			}
		}else{die("Score type missing from config.php file!");}
		
		$sql = "SELECT sm_grade_tiers.$grade,FORMAT(MAX(sm_scores.percentdp*100),2) AS percentdp,FORMAT(MAX(score),0) AS score,COUNT(sm_scores.grade) AS gradeCount 
		FROM sm_scores 
		LEFT JOIN sm_grade_tiers ON sm_grade_tiers.$tier = sm_scores.grade
		WHERE sm_scores.datetime > DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR) AND sm_scores.grade <> 'Failed' AND sm_scores.percentdp > 0 
		GROUP BY sm_scores.grade 
		ORDER BY sm_scores.grade ASC";
		$retval = mysqli_query( $conn, $sql );
		
		echo '<table>';
		while ($row = mysqli_fetch_assoc($retval)){
			echo '<tr>';
			echo "<td>".$row[$grade]."</td><td>(".$row[$score].")</td><td>".$row['gradeCount']."</td>";
			echo '</tr>';
		}
		echo '</table>';
	break;
	case "endscreenscroll":
////////EndScreenScroll/////////
		if(isset($scoreType)){
			switch ($scoreType){
				case "ddr":
					$tier = "ddr_tier";
					$grade = "ddr_grade";
					$score = "score";
					$stageAwards = array(
						"FullComboW3" => "Full Combo",
						"SingleDigitW3" => "",
						"OneW3" => "1 Great",
						"FullComboW2" => "Perfect Full Combo",
						"SingleDigitW2" => "",
						"OneW2" => "1 Perfect",
						"FullComboW1" => "Marvelous Full Combo"
					);
				break;
				case "itg":
				default:
					$tier = "itg_tier";
					$grade = "itg_grade";
					$score = "percentdp";
					$stageAwards = array(
						"FullComboW3" => "Full Combo",
						"SingleDigitW3" => "",
						"OneW3" => "1 Great",
						"FullComboW2" => "Tri-Star",
						"SingleDigitW2" => "",
						"OneW2" => "1 Excellent",
						"FullComboW1" => "Quad-Star"
					);
			}
		}else{die("Score type missing from config.php file!");}

		$sql = "SELECT
				sm_scores.song_id,
				sm_requests.requestor as requestor,
				sm_requests.request_time,
				sm_requests.request_type,
				TRIM(CONCAT(sm_songs.title,' ',sm_songs.subtitle)) AS title,
				sm_songs.pack,
				CONCAT(FORMAT(sm_scores.percentdp * 100, 2),'%') AS percentdp,
				FORMAT(sm_scores.score, 0) AS score,
				sm_grade_tiers.$grade AS grade,
				sm_scores.stage_award AS award,
				sm_scores.datetime
				FROM sm_requests
				INNER JOIN sm_scores
					JOIN sm_grade_tiers 
					ON sm_grade_tiers.$tier = sm_scores.grade
				ON sm_requests.song_id = sm_scores.song_id
				INNER JOIN sm_songs
				ON sm_requests.song_id = sm_songs.id
				WHERE sm_scores.datetime BETWEEN sm_requests.request_time AND sm_requests.timestamp
				AND sm_requests.request_time >= DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR)
				ORDER BY sm_requests.request_time DESC";
		$retval = mysqli_query( $conn, $sql );
	
		echo '<div id="scroll-container"><div id="scroll-text">';
		while ($row = mysqli_fetch_assoc($retval)){
			//translate SM5 scores and stage award to game-specific names
			$award = $row['award'];
			if(array_key_exists($award,$stageAwards)){
				$award = $stageAwards[$award];
			}
			echo "<h4><span class=\"requestor\">Requestor</span>: <span class=\"requestor-data\">".$row['requestor']."</span><br /><span class=\"song\">Song</span>: <span class=\"song-data\">".$row['title']."</span><br /><span class=\"score\">Score</span>: <span class=\"score-data\">".$row[$score]." (".$row['grade'].")</span>";
			if(!empty($award)){
				//Don't show the award if there isn't one
				echo "<br /><span class=\"award\">Award</span>: <span class=\"award-data\">".$award;
			}
			echo "</span></h4>";
		}
		echo '<div></div>';
	break;
	case "recent":
////////RECENT HIGHSCORES/////////
		if(isset($scoreType)){
			switch ($scoreType){
				case "ddr":
					$tier = "ddr_tier";
					$grade = "ddr_grade";
					$score = "score";
					$stageAwards = array(
						"FullComboW3" => "FC",
						"SingleDigitW3" => "",
						"OneW3" => "1G",
						"FullComboW2" => "PFC",
						"SingleDigitW2" => "",
						"OneW2" => "1P",
						"FullComboW1" => "MFC"
					);
				break;
				case "itg":
				default:
					$tier = "itg_tier";
					$grade = "itg_grade";
					$score = "percentdp";
					$stageAwards = array(
						"FullComboW3" => "FC",
						"SingleDigitW3" => "",
						"OneW3" => "1G",
						"FullComboW2" => "Tri-Star",
						"SingleDigitW2" => "",
						"OneW2" => "1EX",
						"FullComboW1" => "Quad"
					);
			}
		}else{die("Score type missing from config.php file!");}

		$sql = "SELECT TRIM(CONCAT(sm_songs.title,' ',sm_songs.subtitle)) AS title,sm_songs.pack AS pack,sm_grade_tiers.$grade,CONCAT(FORMAT(maxpercentdp * 100, 2),'%') AS percentdp,FORMAT(score, 0) AS score,sm_scores.stage_award AS award,sm_scores.stepstype,sm_scores.difficulty,sm_scores.username,datetime
		FROM
			sm_scores
		JOIN sm_songs ON sm_songs.id = sm_scores.song_id
		JOIN sm_grade_tiers ON sm_grade_tiers.$tier = sm_scores.grade
		JOIN(
			SELECT song_id,stepstype,difficulty,username,MAX(percentdp) AS MaxPercentdp
			FROM
				sm_scores
			WHERE
				song_id > 0 AND percentdp > 0 AND grade <> 'Failed'
			GROUP BY
				song_id,stepstype,difficulty,username
			) AS h2
		ON sm_scores.song_id = h2.song_id AND sm_scores.stepstype = h2.stepstype AND sm_scores.difficulty = h2.difficulty AND sm_scores.username = h2.username AND sm_scores.percentdp = h2.maxpercentdp
		ORDER BY `datetime` DESC
		LIMIT 5";
		$retval = mysqli_query( $conn, $sql );
		
		echo '<table>';
		while ($row = mysqli_fetch_assoc($retval)){
			//translate SM5 scores and stage award to game-specific names
			$award = $row['award'];
			if(array_key_exists($award,$stageAwards)){
				$award = $stageAwards[$award];
			}
		//build table of recent highscores
			echo '<tr>';
			echo '<td>'.$row['title'].'</td><td>'.format_pack($row['pack']).'</td><td><strong>'.$row[$grade].'</strong></td><td>('.$row[$score].')</td><td>'.$award.'</td>';
			echo '</tr>';
		}
		echo '</table>';
	break;
	case "requestors":
////////REQUESTORS/////////
		$broadcaster = getLastRequest()['broadcaster'];

		$sql = "SELECT requestor,COUNT(id) AS count 
		FROM sm_requests 
		WHERE state <> 'canceled' AND state <> 'skipped' AND LOWER(requestor) NOT IN(\"{$broadcaster}\") AND request_time > DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR) 
		GROUP BY requestor 
		ORDER BY count DESC,requestor DESC 
		LIMIT 5";
		$retval = mysqli_query( $conn, $sql );

		echo '<h1>Special thanks to requestors:</h1>';
		while ($row = mysqli_fetch_assoc($retval)){
			echo "<body style=\"text-align: center;\">".$row['requestor']." (".$row['count'].")</br>";
		}
		echo "</body>";
	break;
	case "requesttypes":
		////////Request Types////////
		$sql = "SELECT request_type,COUNT(request_type) as count
		FROM sm_requests
		WHERE request_type <> 'normal' AND request_time > DATE_SUB('$timestamp', INTERVAL $StreamSessionLength HOUR)
		GROUP BY request_type
		ORDER BY count DESC,request_type ASC";

		$retval = mysqli_query( $conn, $sql );
		while ($row = mysqli_fetch_assoc($retval)){
			$request_type = $row['request_type'];
			if($request_type != "normal"){
				$request_img = glob("images/".$request_type.".{png,gif}", GLOB_BRACE);
				if (!$request_img){
					$request_img = "images/random.png";
				}else{
					$request_img = "images/".urlencode(basename($request_img[0]));
				}
				$request_type = "<img src=\"$request_img\" class=\"type\">";
			}
			
			echo $request_type."\n";
			echo "<body style=\"vertical-align: middle;\">".$row['count']."</br>";

		}
		echo "</body>";
	break;
	////////Request Status/////////
    case "requeststatus":
		$sql = "SELECT request_toggle FROM sm_broadcaster";
        $retval = mysqli_query( $conn, $sql );
		if(mysqli_num_rows($retval) == 1){
			//only 1 broadcaster
        	$row = mysqli_fetch_assoc($retval);
        	$requestStatus = $row["request_toggle"];
		}elseif(mysqli_num_rows($retval) > 1){
			//more than 1 broadcaster
			//user needs to specify which broadcaster
			if(!isset($_GET["broadcaster"])){die("No broadcaster set! Usage: [URL]/stats.php?data=requeststatus&broadcaster=[BROADCASTER]");}
			if(empty($_GET["broadcaster"]) || strlen($_GET["broadcaster"]) < 3){die("Invalid broadcaster set!");}
			$broadcaster = mysqli_real_escape_string($conn,$_GET["broadcaster"]);

			$sql = "SELECT request_toggle FROM sm_broadcaster WHERE broadcaster = \"{$broadcaster}\"";
        	$retval = mysqli_query( $conn, $sql );

			if(mysqli_num_rows($retval) == 0){die("Broadcaster not found!");}

        	$row = mysqli_fetch_assoc($retval);
        	$requestStatus = $row["request_toggle"];
		}else{
			$requestStatus = "Error";
		}
		if(isset($_GET["onlystate"])){
			echo "<span id=\"requestStatus\" class=\"status{$requestStatus}\" style=\"text-align: right;\"><span class=\"output{$requestStatus}\"> $requestStatus </span></span>";
		}else{
			echo "<span id=\"requestStatus\" class=\"status{$requestStatus}\" style=\"text-align: right;\">Current Request Status is: <span class=\"output{$requestStatus}\"> $requestStatus </span></span>";
		}
    break;
	default:
	echo("Error: No data set! Usage: [URL]/stats.php?data=[requests,songs,scores,recent,endscreenscroll,requestors,requeststatus,requesttypes]");
	break;
}

echo "</body>\n</html>";
//close everything out
mysqli_close($conn);
die();
?>
