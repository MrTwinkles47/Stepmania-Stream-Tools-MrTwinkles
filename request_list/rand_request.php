<?php
   
require_once ('config.php');
require_once ('misc_functions.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}

if(!isset($_GET["user"])){
	die("Error");
}

if(!isset($_GET["random"]) && ((!isset($_GET["num"]) && !is_numeric($_GET["num"])) || !isset($_GET["song"]))){
	die();
}

function request_song($song_id, $requestor, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty){
	global $conn;
	
	if(empty($request_type)){$request_type = "random";}

	$userobj = check_user($twitchid, $requestor);

	if(strtolower($broadcaster) != strtolower($requestor)){
		//requestor not broadcaster. broadcaster bypasses these checks
		if($userobj["banned"] == "true"){
			die();
		}   
		if($userobj["whitelisted"] != "true"){
			check_cooldown($requestor);
		}
		requested_recently($song_id,$requestor,$userobj["whitelisted"]);
	}

    $sql = "INSERT INTO sm_requests (song_id, request_time, requestor, twitch_tier, broadcaster, request_type, stepstype, difficulty) VALUES ('{$song_id}', NOW(), '{$requestor}', '{$tier}', '{$broadcaster}', '{$request_type}', '{$stepstype}', '{$difficulty}')";
    mysqli_query( $conn, $sql );

}

function build_whereclause($stepstype,$difficulty,$table){
	//build WHERE clause for stepstype/difficulty
	$whereTypeDiffClause = array();
	$whereTypeDiffClauseStr = "";
	if(!empty($stepstype)){
		$whereTypeDiffClause[] = "AND $table.stepstype LIKE '$stepstype'";
	}
	if(!empty($difficulty)){
		$whereTypeDiffClause[] = "AND $table.difficulty LIKE '$difficulty'";
	}
	//implode array to string
	if(!empty($whereTypeDiffClause)){
		$whereTypeDiffClauseStr = implode(" ",$whereTypeDiffClause);
	}

	return $whereTypeDiffClauseStr;
}

function percentdp_to_grade(string $percentdp){
	//look up grade for ddr/itg from %DP, SM tier/grade
	global $conn;
	global $scoreType;
	$grade = "";

	switch ($scoreType){
		case "ddr":
			$score_grade = "ddr_grade";
			$score_tier = "ddr_tier";
			break;
		case "itg":
		default:
			$score_grade = "itg_grade";
			$score_tier = "itg_tier";
			break;
	}

	$sql = "SELECT percentdp, $score_tier, $score_grade 
			FROM sm_grade_tiers 
			WHERE percentdp <= $percentdp 
			ORDER BY percentdp DESC 
			LIMIT 1";

	$retval = mysqli_query( $conn, $sql );
	if (mysqli_num_rows($retval) > 0){
		$grade = mysqli_fetch_assoc($retval)[$score_grade];
	}else{
		//die("Error finding the grade! " . mysqli_error($conn));
	}

	return (string)$grade;
}

function get_top_percent_played_songs(string $profileName, string $whereTypeDiffClause){
	//get the total number of unique songs played for use in "top" queries
	global $conn;
	global $topPercent;

	$sql = "SELECT song_id, SUM(numplayed) AS numplayed
			FROM sm_songsplayed
			JOIN sm_songs ON sm_songsplayed.song_id = sm_songs.id
			WHERE sm_songs.installed = 1 AND sm_songs.banned NOT IN(1, 2) AND sm_songsplayed.song_id > 0 AND username LIKE '{$profileName}' $whereTypeDiffClause AND sm_songsplayed.song_id IN (
				SELECT song_id
				FROM sm_scores
				GROUP BY song_id
				HAVING MAX(percentdp) > 0) 
			GROUP BY song_id
			ORDER BY numplayed DESC";
	if($retval = mysqli_query( $conn, $sql )){
		//get number of rows
		$total = mysqli_num_rows($retval);
		//calculate percent of total
		$total = round($total * $topPercent,0);
		$total = intval($total);
		if ($total < 100){$total = 100;} //100 is the lowest value
	}else{
		$total = 100; //fallback to 100 as the lowest value
		//die("Error getting top percent of played songs! " . mysqli_error($conn));
	}

	return (integer)$total;
}

function get_average_percentDP(string $profileName, string $whereTypeDiffClause){
	//get average score 
	global $conn;

	$sql = "SELECT AVG(percentdp) as average
			FROM sm_scores
			JOIN sm_songs ON sm_scores.song_id = sm_songs.id
			WHERE sm_songs.installed = 1 AND sm_songs.banned NOT IN(1, 2) AND grade <> 'Failed' AND percentdp > 0.5 AND username LIKE '{$profileName}' $whereTypeDiffClause";

	$retval = mysqli_query( $conn, $sql );
	
	if (mysqli_num_rows($retval) > 0){
		$average = mysqli_fetch_assoc($retval)['average'];
	}else{
		$average = 0.5; 
		//die("Error calculating the average score! " . mysqli_error($conn));
	}

	return (float)$average;
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

//check if the active channel category/game is StepMania, etc.
if(isset($_GET["game"])){
	$game = mysqli_real_escape_string($conn,$_GET["game"]);
    if(in_array(strtolower($game),array_map('strtolower',$categoryGame)) == FALSE){
        die("Hmmm...I don't think it's possible to request songs in ".$game.".");
    }
}

$user = mysqli_real_escape_string($conn,$_GET["user"]);
$tier = mysqli_real_escape_string($conn,$_GET["tier"]);
$twitchid = 0;
if(isset($_GET["userid"])){
	$twitchid = mysqli_real_escape_string($conn,$_GET["userid"]);
}

//get broadcaster and adjust query filters
if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
	$broadcaster = mysqli_real_escape_string($conn,$_GET["broadcaster"]);
	check_request_toggle($broadcaster, $user);
	if (array_key_exists($broadcaster,$broadcasters)){
		$profileName = $broadcasters[$broadcaster];
	}else{
		$profileName = "%";
	}
}else{
	$broadcaster = "";
	$profileName = "%";
}

//get number of random requests, if not specified, set as 1
if (isset($_GET["num"]) && !empty($_GET["num"]) && is_numeric($_GET["num"]) && $_GET["num"] > 0){
	$num = $_GET["num"];
}elseif(!isset($_GET["num"]) || empty($_GET["num"])){
	$num = 1;
}else{ 
	die("Good one, @$user, but only positive integers are allowed!");
}

if($num > $max_num){
	die("@$user can't request that many songs at once!");
}

$broadcasterLimits = get_broadcaster_limits($broadcaster);
if(!empty($broadcasterLimits) && is_array($broadcasterLimits)){
	$stepstype = $broadcasterLimits['stepstype'];
	$meter = $broadcasterLimits['meter_max'];
//	if(empty($stepstype)){$stepstype = '%';}
//	if(empty($meter)){$meter = '99999999999';}
}
$difficulty = "";

//get scoring type
global $scoreType;

//parse request stepstype and/or difficulty
if(isset($_GET["song"]) && !empty($_GET["song"])){
	$commandArgs = parseCommandArgs($_GET["song"],$user,$broadcaster);
	$song = $commandArgs["song"];
	$stepstype = $commandArgs["stepstype"];
	$difficulty = $commandArgs["difficulty"];
}

//standard random request from songs that have at least been played once
if($_GET["random"] == "random"){

	$request_type = "random";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songsplayed 
	JOIN sm_songs ON sm_songsplayed.song_id=sm_songs.id  
	WHERE sm_songsplayed.song_id > 0 AND sm_songsplayed.username LIKE '{$profileName}' AND banned NOT IN(1,2) AND installed=1 AND sm_songsplayed.numplayed > 0 $whereTypeDiffClause AND sm_songsplayed.song_id IN (
		SELECT song_id
		FROM sm_scores
		WHERE percentdp > 0)
	GROUP BY sm_songs.id 
	ORDER BY RAND()
	LIMIT 100";

	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
		$i=1;
		while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
			if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
				$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("@$user randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
				$i++;
			}
		}
	} elseif (mysqli_num_rows($retval) == 0) {
		//didn't find any songs from the sm_songsplayed table. request system is running in "offline" mode.
		$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,numplayed  
		FROM sm_songs 
		JOIN 
        	(SELECT sm_requests.song_id AS id,COUNT(sm_requests.song_id) AS numplayed 
             	FROM sm_requests
            	WHERE sm_requests.song_id > 0 AND sm_requests.broadcaster LIKE '$broadcaster' AND sm_requests.state = 'completed'
            	GROUP BY sm_requests.song_id
             ) AS t2
        ON t2.id=sm_songs.id 
		WHERE banned NOT IN(1,2) AND installed=1 AND numplayed>1 
		GROUP BY sm_songs.id 
		ORDER BY RAND()
		LIMIT 100";
		$retval = mysqli_query( $conn, $sql );
		
		if(mysqli_num_rows($retval) >= 100) {
			//let's hope for at least 100 results so that it at least seems like a random pick
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("@$user randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . " ");
					$i++;
				}
			}
		}else{
			die("Too few songs played/requested to pick a random song!");
		}
	
	} else {
        die("Didn't find any random songs!");
}

die();
}

//standard portal request, any installed/unbanned songs can be selected
if($_GET["random"] == "portal"){

	$request_type = "portal";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_notedata");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songs 
	WHERE installed=1 AND banned NOT IN(1,2) AND sm_songs.id IN (
		SELECT song_id 
		FROM sm_notedata 
		WHERE song_id > 0 $whereTypeDiffClause) 
	ORDER BY RAND() LIMIT 100";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("@$user opened a portal to " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
					$i++;
				}
			}
	} else {
        	die("Didn't find any portal songs!");
}

die();
}

//standard unplayed request, any installed/unbanned and unplayed songs can be selected
//credit: xancara
if($_GET["random"] == "unplayed"){

	$request_type = "unplayed";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_notedata");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songs
	WHERE installed=1 AND banned NOT IN(1,2) AND id NOT IN (
		SELECT song_id 
		FROM sm_songsplayed
		WHERE song_id>0 AND username LIKE '{$profileName}') 
		AND sm_songs.id IN (
            SELECT song_id
            FROM sm_notedata
            WHERE song_id>0 $whereTypeDiffClause)
	ORDER BY RAND() LIMIT 100";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("@$user requested the unplayed song " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
					$i++;
				}
			}
	} else {
        	die("Didn't find any unplayed songs!");
}

die();
}

//standard top request of 1 random 100 most played songs
if($_GET["random"] == "top"){

	$request_type = "top";
	//if(empty($stepstype)){$stepstype = '%';}
	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,numplayed,t2.stepstype AS stepstype  
			FROM sm_songs 
			JOIN 
				(SELECT song_id,SUM(numplayed) AS numplayed,stepstype 
				FROM sm_songsplayed
				WHERE song_id>0 AND numplayed>1 AND username LIKE '{$profileName}' $whereTypeDiffClause  
				GROUP BY song_id,stepstype 
				ORDER BY numplayed DESC 
				LIMIT 100) AS t2 
			ON t2.song_id=sm_songs.id 
			WHERE banned NOT IN(1,2) AND installed=1 
			ORDER BY RAND()";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
		$i=1;
		while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
			if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $row['stepstype'], $difficulty);
				$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("@$user picked a top request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
				$i++;
			}
		}
	} elseif (mysqli_num_rows($retval) == 0) {
		//didn't find any songs from the sm_songsplayed table. request system is running in "offline" mode.
		$sql = "SELECT id,title,subtitle,artist,pack,numplayed 
				FROM sm_songs 
				JOIN 
					(SELECT sm_requests.song_id AS song_id,COUNT(sm_requests.song_id) AS numplayed 
					FROM sm_requests
					WHERE sm_requests.song_id > 0 AND sm_requests.broadcaster LIKE '$broadcaster' AND sm_requests.state = 'completed'
					GROUP BY sm_requests.song_id
					ORDER BY numplayed DESC
					LIMIT 100) AS t2
				ON t2.song_id=sm_songs.id 
				WHERE banned NOT IN(1,2) AND installed=1 AND numplayed>1
				ORDER BY RAND()";
		$retval = mysqli_query( $conn, $sql );
		
		if(mysqli_num_rows($retval) >= 100) {
			//let's hope for at least 100 results so that it at least seems like a random pick
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("@$user picked a top request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . " ");
					$i++;
				}
			}
		}else{
			die("Too few songs played/requested to pick a top song!");
		}
	} else {
        die("Didn't find any top songs!");
}

die();
}

//random below average scored song in the top 10%
if($_GET["random"] == "gitgud"){

	$request_type = "gitgud";
	
	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_scores");
	$whereTypeDiffClauseSP = build_whereclause($stepstype,$difficulty,"sm_songsplayed");

	$topTotal = get_top_percent_played_songs($profileName,$whereTypeDiffClauseSP);
	$averagePercentDP = get_average_percentDP($profileName,$whereTypeDiffClause);

	$sql = "SELECT t2.song_id AS song_id, sm_songs.title AS title, sm_songs.subtitle AS subtitle, sm_songs.artist AS artist, sm_songs.pack AS pack, t2.percentdp as percentdp, grade, score, t2.stepstype, difficulty, scores, DATETIME
		FROM sm_scores
		JOIN(
			SELECT sm_scores.song_id, MAX(percentdp) AS percentdp, COUNT(sm_scores.id) AS scores, stepstype
			FROM sm_scores
			JOIN(
				SELECT song_id, SUM(numplayed) AS numplayed
				FROM sm_songsplayed
				JOIN sm_songs ON sm_songsplayed.song_id = sm_songs.id
				WHERE sm_songs.installed = 1 AND sm_songs.banned NOT IN(1, 2) AND sm_songsplayed.song_id > 0 AND numplayed > 1 AND username LIKE '{$profileName}' $whereTypeDiffClauseSP AND sm_songsplayed.song_id IN (
                    SELECT song_id
                    FROM sm_scores
                    GROUP BY song_id
                    HAVING MAX(percentdp) > 0) 
				GROUP BY song_id
				ORDER BY numplayed DESC
				LIMIT $topTotal 
				) AS topt
			ON topt.song_id = sm_scores.song_id
			WHERE grade <> 'Failed' AND percentdp > 0 AND username LIKE '{$profileName}' $whereTypeDiffClause 
			GROUP BY song_id, stepstype
			HAVING scores > 1
			ORDER BY percentdp ASC
			) AS t2
		ON t2.song_id = sm_scores.song_id AND t2.percentdp = sm_scores.percentdp 
		JOIN sm_songs ON sm_songs.id = sm_scores.song_id
		WHERE t2.percentdp < $averagePercentDP 
		ORDER BY RAND()";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["song_id"],1) && check_stepstype($broadcaster,$row["song_id"]) && check_meter($broadcaster,$row["song_id"])){
					request_song($row["song_id"], $user, $tier, $twitchid, $broadcaster, $request_type, $row['stepstype'], $row['difficulty']);
					switch ($scoreType){
						case "ddr":
							$score = $row['score'];
							$score !== 0 ? $base = ceil(log10($score)) : $base = 1;
							if($base > 6){
								//score is >1M. It was obtained while using a non-modern-ddr theme.
								//translate the score to ddr range (out of 1M)
								$score = $score / pow(10,$base - 6);
								$displayScore = "~".number_format($score,0,".",",");
							}else{
								$displayScore = number_format($score,0,".",",");
							}
							break;
						case "itg":
						default:
							$displayScore = number_format($row['percentdp']*100,2)."%";
							break;
					}
					//translate SM grade/tier to ddr/itg grade
					$grade = percentdp_to_grade($row['percentdp']);
					if (!empty($grade)){
						$displayScore = $displayScore . " (" . $grade . ")";
					}

					$displayModeDiff = display_ModeDiff(array('stepstype' => $row['stepstype'],'difficulty' => $row['difficulty']));
					$displayArtist = get_duplicate_song_artist ($row["song_id"]);
					echo ("@$user dares you to beat ".$displayScore." from ".date_format($row["datetime"],"Y/m/d")." at " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
					//wh_log("@$user requested $request_type (TopPercent: $topTotal, AveragePDP: $averagePercentDP): $displayScore at " . $row["song_id"] . " : " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff);
					$i++;
				}
			}
	} else {
        	die("Didn't find any songs to git gud at!");
}

die();
}

//roll command responds with 3 random songs that the user can then request with "requestid"
if($_GET["random"] == "roll"){

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songsplayed 
	JOIN sm_songs ON sm_songsplayed.song_id=sm_songs.id  
	WHERE sm_songsplayed.song_id > 0 AND sm_songsplayed.username LIKE '{$profileName}' AND banned NOT IN(1,2) AND installed=1 AND sm_songsplayed.numplayed > 0 $whereTypeDiffClause AND sm_songsplayed.song_id IN (
		SELECT song_id
		FROM sm_scores
		WHERE percentdp > 0)
	GROUP BY sm_songs.id 
	ORDER BY RAND()
	LIMIT 100";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
		echo "@$user rolled (request with !requestid [song id]):\n";
		$i=1;
		while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
			if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo " [ ".$row["id"]. " -> " .trim($row["title"]." ".$row["subtitle"]).$displayArtist." from ".$row["pack"]." ]";
				$i++;
			}
		}
	} elseif (mysqli_num_rows($retval) == 0) {
		//didn't find any songs from the sm_songsplayed table. request system is running in "offline" mode.
		$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,numplayed  
		FROM sm_songs 
		JOIN 
        	(SELECT sm_requests.song_id AS id,COUNT(sm_requests.song_id) AS numplayed 
             	FROM sm_requests
            	WHERE sm_requests.song_id > 0 AND sm_requests.broadcaster LIKE '$broadcaster' AND sm_requests.state = 'completed'
            	GROUP BY sm_requests.song_id
             ) AS t2
        ON t2.id=sm_songs.id 
		WHERE banned NOT IN(1,2) AND installed=1 AND numplayed>1 
		GROUP BY sm_songs.id 
		ORDER BY RAND()
		LIMIT 100";
		$retval = mysqli_query( $conn, $sql );
		
		if(mysqli_num_rows($retval) >= 100) {
			//let's hope for at least 100 results so that it at least seems like a random pick
			echo "@$user rolled (request with !requestid [song id]):\n";
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo " [ ".$row["id"]. " -> " .trim($row["title"]." ".$row["subtitle"]).$displayArtist." from ".$row["pack"]." ]";
					$i++;
				}
			}
		}else{
			die("Too few songs played/requested to roll random songs!");
		}	
	} else {
		die("@$user rolled a natural 1 BibleThump");
	}
	die();
}

//special random for regulars: picks a random song from top 10 requested by requestor
if($_GET["random"] == "theusual"){
	
	$request_type = "theusual";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_notedata");
	
	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,idcount    
			FROM sm_songs  
			JOIN 
				(SELECT song_id, COUNT(song_id) AS idcount 
				FROM sm_requests 
				WHERE song_id>0 AND LOWER(requestor) LIKE LOWER('$user') AND state <> 'canceled' AND state <> 'skipped' AND request_type = 'normal' 
				GROUP BY song_id
				HAVING idcount >= 3  
				ORDER BY idcount DESC  
				LIMIT 20) AS t2 
			ON t2.song_id=sm_songs.id    
			WHERE banned NOT IN(1,2) AND installed=1 AND sm_songs.id IN (
				SELECT song_id 
				FROM sm_notedata 
				WHERE song_id > 0 $whereTypeDiffClause) 
			ORDER BY RAND()";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) >= 5) {
		$i=1;
		while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
			if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("Of course @$user would request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . ". HoW oRiGiNaL! ");
				$i++;
			}
		}
	} else {
		die("@$user hasn't met the minimum number of requested songs or isn't a regular around here.");
	}

	die();
}

//specific pack(s) random request/catch-all REGEX pack name matching
//randomben, randomddr, randomnitg, randomhellkite...
if(!empty($_GET["random"]) && $_GET["random"] != "random"){
		
		$random = mysqli_real_escape_string($conn,$_GET["random"]);
		if(isset($_GET["type"])){$request_type = mysqli_real_escape_string($conn,strtolower($_GET["type"]));}
		$random = htmlspecialchars($random);

		$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,"sm_notedata");
		
        $sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
		FROM sm_songs 
		JOIN sm_notedata ON sm_notedata.song_id = sm_songs.id 
		WHERE installed=1 AND banned NOT IN(1,2) AND (pack REGEXP '{$random}' OR sm_songs.credit REGEXP '{$random}' OR sm_notedata.credit REGEXP '{$random}') $whereTypeDiffClause 
		GROUP BY sm_songs.id 
		ORDER BY RAND()
		LIMIT 100";

		$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					echo ("@$user randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
					$i++;
				}
			}
	} else {
        	die("Uh oh. RNGesus was not on your side!");
}

die();
}

mysqli_close($conn);
die();

?>
