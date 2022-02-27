<?php
   
include('config.php');
include('misc_functions.php');

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
	
	$userobj = check_user($twitchid, $requestor);

	if($userobj["banned"] == "true"){
        die();
	}   
	if($userobj["whitelisted"] != "true"){
        check_cooldown($requestor);
		}

	if(empty($request_type)){$request_type = "random";}

	requested_recently($song_id,$requestor,$userobj["whitelisted"],1);

    $sql = "INSERT INTO sm_requests (song_id, request_time, requestor, twitch_tier, broadcaster, request_type, stepstype, difficulty) VALUES ('{$song_id}', NOW(), '{$requestor}', '{$tier}', '{$broadcaster}', '{$request_type}', '{$stepstype}', '{$difficulty}')";
    $retval = mysqli_query( $conn, $sql );

}

function build_whereclause($stepstype,$difficulty,$meter,$table){
	//build WHERE clause for stepstype/difficulty
	$whereTypeDiffClause = "";
	if(!empty($stepstype)){
		$whereTypeDiffClause = "AND $table.stepstype LIKE '$stepstype' ";
	}
	if(!empty($difficulty)){
		$whereTypeDiffClause = $whereTypeDiffClause . "AND $table.difficulty LIKE '$difficulty' ";
	}
	if(!empty($meter)){
		if($table != 'sm_notedata'){
			$table = 'sm_notedata';
		}
		$whereTypeDiffClause = $whereTypeDiffClause . "AND $table.meter LIKE '$meter' ";
	}

	return $whereTypeDiffClause;
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

//check if the active channel category/game is StepMania, etc.
if(isset($_GET["game"])){
	$game = $_GET["game"];
    if(in_array(strtolower($game),array_map('strtolower',$categoryGame)) == FALSE){
        die("Hmmm...I don't think it's possible to request songs in ".$game.".");
    }
}

$user = $_GET["user"];
$tier = $_GET["tier"];
if(isset($_GET["userid"])){
	$twitchid = $_GET["userid"];
}else{
	$twitchid = 0;
}
//get broadcaster and adjust query filters
if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
	check_request_toggle($broadcaster);
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
	die("Good one, $user, but only positive integers are allowed!");
}

if($num > $max_num){
	die("$user can't request that many songs at once!");
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

//parse request stepstype/difficulty/meter
if(isset($_GET["song"]) && !empty($_GET["song"])){
	$commandArgs = parseCommandArgs($_GET["song"],$user,$broadcaster);
	$song = $commandArgs["song"];
	$stepstype 	= $commandArgs["stepstype"];
	$difficulty = $commandArgs["difficulty"];
	$meter 		= $commandArgs['meter'];
}

//standard random request from songs that have at least been played once
if($_GET["random"] == "random"){

	$request_type = "random";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songsplayed 
	JOIN sm_songs ON sm_songsplayed.song_id=sm_songs.id 
	JOIN sm_notedata ON sm_songsplayed.song_id = sm_notedata.song_id AND sm_songsplayed.difficulty = sm_notedata.difficulty 
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
				$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
				$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("{$user} randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
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
		
		if(mysqli_num_rows($retval) >= 10) {
			//let's hope for at least 10 results so that it at least seems like a random pick
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("{$user} randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . " ");
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

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_notedata");

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
					$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("$user opened a portal to " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
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

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_notedata");

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
					$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("$user requested the unplayed song " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
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
	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,numplayed,t2.stepstype AS stepstype  
			FROM sm_songs 
			JOIN 
				(SELECT sm_songsplayed.song_id,SUM(numplayed) AS numplayed,sm_songsplayed.stepstype 
				FROM sm_songsplayed
				JOIN sm_notedata ON sm_songsplayed.song_id = sm_notedata.song_id AND sm_songsplayed.difficulty = sm_notedata.difficulty 
				WHERE sm_songsplayed.song_id>0 AND numplayed>1 AND username LIKE '{$profileName}' $whereTypeDiffClause  
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
				$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $row['stepstype'], $difficulty);
				$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("$user picked a top request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
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
		
		if(mysqli_num_rows($retval) >= 10) {
			//let's hope for at least 10 results so that it at least seems like a random pick
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("$user picked a top request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . " ");
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

//random worst 25 scored top 100 songs
if($_GET["random"] == "gitgud"){

	$request_type = "gitgud";
	//if(empty($stepstype)){$stepstype = '%';}
	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_scores");
	$whereTypeDiffClauseSP = build_whereclause($stepstype,$difficulty,$meter,"sm_songsplayed");

	switch ($scoreType){
		case "ddr":
			$score_grade = "ddr_grade";
			$score_tier = "ddr_tier";
			break;
		case "itg":
			$score_grade = "itg_grade";
			$score_tier = "itg_tier";
			break;
		default:
			$score_grade = "itg_grade";
			$score_tier = "itg_tier";
	}

        $sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,t2.percentdp,score,t2.stepstype,t2.difficulty,date,scores 
				FROM sm_songs 
				JOIN 
				(SELECT sm_scores.song_id,MAX(percentdp) AS percentdp,MAX(score) AS score,COUNT(sm_scores.song_id) AS scores,sm_scores.stepstype AS stepstype,sm_scores.difficulty AS difficulty,DATE_FORMAT(MAX(sm_scores.datetime),'%Y/%c/%e') AS date  
					FROM sm_scores 
					JOIN sm_notedata ON sm_scores.song_id = sm_notedata.song_id AND sm_scores.difficulty = sm_notedata.difficulty  
					WHERE EXISTS 
						(SELECT sm_songsplayed.song_id AS song_id,SUM(numplayed) AS numplayed   
						FROM sm_songsplayed
						JOIN sm_notedata ON sm_songsplayed.song_id = sm_notedata.song_id AND sm_songsplayed.difficulty = sm_notedata.difficulty  
						WHERE sm_songsplayed.song_id>0 AND numplayed>1 AND username LIKE '{$profileName}' $whereTypeDiffClauseSP  
						GROUP BY song_id 
						ORDER BY numplayed DESC 
						LIMIT 100) 
					AND grade <> 'Failed' AND percentdp BETWEEN 0.50 AND 1.0 AND username LIKE '{$profileName}' $whereTypeDiffClause 
					GROUP BY song_id,stepstype,difficulty
					HAVING scores > 1  
					ORDER BY percentdp ASC, score ASC 
					LIMIT 25) AS t2 
				ON t2.song_id = sm_songs.id 
				WHERE banned NOT IN(1,2) AND installed = 1 
				ORDER BY RAND()";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
			while(($row = mysqli_fetch_assoc($retval)) && ($i <= $num)) {
				if(!recently_played($row["id"],1) && check_stepstype($broadcaster,$row["id"]) && check_meter($broadcaster,$row["id"])){
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $row['stepstype'], $row['difficulty']);
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
							$displayScore = number_format($row['percentdp']*100,2)."%";
							break;
						default:
							$displayScore = number_format($row['percentdp']*100,2)."%";
					}
					$displayModeDiff = display_ModeDiff(array('stepstype' => $row['stepstype'],'difficulty' => $row['difficulty']));
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					echo ("$user dares you to beat ".$displayScore." at " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
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

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_songsplayed");

	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
	FROM sm_songsplayed 
	JOIN sm_songs ON sm_songsplayed.song_id=sm_songs.id 
	JOIN sm_notedata ON sm_scores.song_id = sm_notedata.song_id AND sm_scores.difficulty = sm_notedata.difficulty  
	WHERE sm_songsplayed.song_id > 0 AND sm_songsplayed.username LIKE '{$profileName}' AND banned NOT IN(1,2) AND installed=1 AND sm_songsplayed.numplayed > 0 $whereTypeDiffClause AND sm_songsplayed.song_id IN (
		SELECT song_id
		FROM sm_scores
		WHERE percentdp > 0)
	GROUP BY sm_songs.id 
	ORDER BY RAND()
	LIMIT 100";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
		echo "$user rolled (request with !requestid [song id]):\n";
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
		
		if(mysqli_num_rows($retval) >= 10) {
			//let's hope for at least 10 results so that it at least seems like a random pick
			echo "$user rolled (request with !requestid [song id]):\n";
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
		die("$user rolled a natural 1 BibleThump");
	}
	die();
}

//special random for regulars: picks a random song from top 10 requested by requestor
if($_GET["random"] == "theusual"){
	
	$request_type = "theusual";

	$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_notedata");
	
	$sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack,idcount    
			FROM sm_songs  
			JOIN 
				(SELECT song_id, COUNT(song_id) AS idcount 
				FROM sm_requests 
				WHERE song_id>0 AND LOWER(requestor) LIKE LOWER('$user') AND state <> 'canceled' AND state <> 'skipped' 
				GROUP BY song_id
				HAVING idcount > 1  
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
				$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
				request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
				$displayArtist = get_duplicate_song_artist ($row["id"]);
				echo ("Of course {$user} would request " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . ". HoW oRiGiNaL! ");
				$i++;
			}
		}
	} else {
		die("$user hasn't met the minimum number of requested songs or isn't a reqular around here.");
	}

	die();
}

//specific pack(s) random request/catch-all REGEX pack name matching
//randomben, randomddr, randomnitg, randomhellkite...
if(!empty($_GET["random"]) && $_GET["random"] != "random"){
		
		$random = mysqli_real_escape_string($conn,$_GET["random"]);
		if(isset($_GET["type"])){$request_type = mysqli_real_escape_string($conn,strtolower($_GET["type"]));}
		$random = htmlspecialchars($random);

		$whereTypeDiffClause = build_whereclause($stepstype,$difficulty,$meter,"sm_notedata");
		
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
					$difficulty = meter_to_difficulty($row["id"],$stepstype,$difficulty,$meter,$user);
					request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $request_type, $stepstype, $difficulty);
					$displayArtist = get_duplicate_song_artist ($row["id"]);
					$displayModeDiff = display_ModeDiff(array('stepstype' => $stepstype,'difficulty' => $difficulty));
					echo ("$user randomly requested " . trim($row["title"]." ".$row["subtitle"]).$displayArtist. " from " . $row["pack"] . $displayModeDiff . " ");
					$i++;
				}
			}
	} else {
        	die("Uh oh. RNGesus was not on your side!");
}

die();
}

mysqli_close();
die();

?>
