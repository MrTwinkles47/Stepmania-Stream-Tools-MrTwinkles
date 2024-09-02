<?php

require_once ('config.php');
require_once ('misc_functions.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Error: Missing or incorrect security key.");
}

if(!isset($_GET["song"]) && !isset($_GET["songid"]) && !isset($_GET["cancel"]) && !isset($_GET["skip"]) && !isset($_GET["complete"])){
	die();
}

if(!isset($_GET["user"])){
	die("Error");
}

function check_banned($song_id, $user){

	global $conn;
	$sql0 = "SELECT * FROM sm_songs WHERE installed=1 AND id = '$song_id' LIMIT 1";
	if( mysqli_fetch_assoc( mysqli_query( $conn,$sql0))['banned'] == 1)
		{
		die("I'm sorry @$user, but I'm afraid I can't do that.");
		}
}

function request_song($song_id, $requestor, $tier, $twitchid, $broadcaster, $commandArgs){
	global $conn;
	
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
	
	check_banned($song_id, $requestor);

	if(check_stepstype($broadcaster,$song_id) == FALSE){
		die("$requestor requested a song that does not have the appropriate chart!");
	}
	if(check_meter($broadcaster,$song_id) == FALSE){
		die("$requestor requested a song that appears to be too hard for $broadcaster!");
	}

	$stepstype 	= $commandArgs['stepstype'];
	$difficulty = $commandArgs['difficulty'];
	$meter 		= $commandArgs['meter'];

	if(!empty($stepstype)){
		$difficulty = meter_to_difficulty($song_id,$stepstype,$difficulty,$meter,$user);
	}

	if(!empty($stepstype) || !empty($difficulty)){
		if(check_notedata($broadcaster,$song_id,$stepstype,$difficulty,$requestor) == FALSE){
			die("$requestor requested a song without that steps-type or difficulty!");
		}
	}

	$stepstype = $commandArgs['stepstype'];
	$difficulty = $commandArgs['difficulty'];
	
	$sql = "INSERT INTO sm_requests (song_id, request_time, requestor, twitch_tier, broadcaster, request_type, stepstype, difficulty) VALUES ('$song_id', NOW(), '$requestor', '$tier', '$broadcaster', 'normal', '$stepstype', '$difficulty')";
	mysqli_query( $conn, $sql );
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

//check if the active channel category/game is StepMania, etc.
if(isset($_GET["game"]) && !empty($_GET["game"])){
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

//if(empty($_GET["song"]) || empty($_GET["songid"])){
//	die("@$user did not specify a song or songID.");
//}

//get broadcaster
if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
	$broadcasterQuery = $broadcaster;
	if (isset($_GET["song"]) || isset($_GET["songid"])){
		check_request_toggle($broadcaster, $user);
	}
}else{
	$broadcaster = "";
	$broadcasterQuery = "%";
}

if(isset($_GET["cancel"])){
	
	if (!empty($_GET["cancel"]) && is_numeric($_GET["cancel"]) && $_GET["cancel"] > 0){
		$num = $_GET["cancel"] - 1;
	}elseif(empty($_GET["cancel"])){
		$num = 0;
	}else{
		die("Good one, ".$user. ", but only positive integers are allowed!");
	}

        $sql = "SELECT * FROM sm_requests WHERE requestor = '$user' AND broadcaster LIKE '$broadcasterQuery' AND state <> 'canceled' AND state <> 'skipped' AND state <> 'completed' ORDER BY request_time DESC LIMIT 1 OFFSET $num";
	$retval = mysqli_query( $conn, $sql );

        if (mysqli_num_rows($retval) == 1) {
                while($row = mysqli_fetch_assoc($retval)) {

			$request_id = $row["id"];
			$song_id = $row["song_id"];
			
            $sql2 = "SELECT * FROM sm_songs WHERE id = '$song_id' LIMIT 1";
            $retval2 = mysqli_query( $conn, $sql2 );
			while($row2 = mysqli_fetch_assoc($retval2)){
		        $sql3 = "UPDATE sm_requests SET state = 'canceled' WHERE id = '$request_id'";
        		$retval3 = mysqli_query( $conn, $sql3 );
				echo "Canceled @$user's request for ".trim($row2["title"]." ".$row2["subtitle"]);
			}
		}

	}else{
		echo "@$user hasn't requested any songs!";
	}

die();
}

if(isset($_GET["skip"])){

	if (!empty($_GET["skip"]) && is_numeric($_GET["skip"]) && $_GET["skip"] > 0){
		$num = $_GET["skip"] - 1;
	}elseif(empty($_GET["skip"])){
		$num = 0;
	}else{
		die("Good one, @$user, but only positive integers are allowed!");
	}

	$sql = "SELECT * FROM sm_requests WHERE broadcaster LIKE '$broadcasterQuery' AND state <> 'canceled' AND state <> 'skipped' AND state <> 'completed' ORDER BY request_time DESC LIMIT 1 OFFSET $num";
        $retval = mysqli_query( $conn, $sql );

                while($row = mysqli_fetch_assoc($retval)) {
					$request_id = $row["id"];
					$song_id = $row["song_id"];
					$sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\" LIMIT 1";
					$retval2 = mysqli_query( $conn, $sql2 );
					while($row2 = mysqli_fetch_assoc($retval2)){
						$sql3 = "UPDATE sm_requests SET state=\"skipped\" WHERE id = \"$request_id\"";
						$retval3 = mysqli_query( $conn, $sql3 );
						echo "@$user skipped ".trim($row2["title"]." ".$row2["subtitle"]);
					}
                }

die();
}

if(isset($_GET["complete"])){

	if (!empty($_GET["complete"]) && is_numeric($_GET["complete"]) && $_GET["complete"] > 0){
		$num = $_GET["complete"] - 1;
	}elseif(empty($_GET["complete"])){
		$num = 0;
	}else{
		die("Good one, @$user, but only positive integers are allowed!");
	}

	$sql = "SELECT * FROM sm_requests WHERE broadcaster LIKE '$broadcasterQuery' AND state <> 'canceled' AND state <> 'skipped' AND state <> 'requested' ORDER BY request_time DESC LIMIT 1 OFFSET $num";
        $retval = mysqli_query( $conn, $sql );

                while($row = mysqli_fetch_assoc($retval)) {
					$request_id = $row["id"];
					$song_id = $row["song_id"];
					$sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\" LIMIT 1";
					$retval2 = mysqli_query( $conn, $sql2 );
					while($row2 = mysqli_fetch_assoc($retval2)){
						$sql3 = "UPDATE sm_requests SET state=\"completed\" WHERE id = \"$request_id\"";
						$retval3 = mysqli_query( $conn, $sql3 );
						echo "@$user completed ".trim($row2["title"]." ".$row2["subtitle"]);
					}
                }

die();
}

if(isset($_GET["songid"]) && !empty($_GET["songid"])){
	$commandArgs = parseCommandArgs($_GET["songid"],$user,$broadcaster);

	if(empty($commandArgs["song"])){
		echo "@$user didn't specify a song ID!";
		die();
	}

	//clean up song ID
	$song = clean($commandArgs["song"]);
	$song = preg_replace('/\D/','',$song);
	if(!is_numeric($song) || empty($song)){
		echo "@$user gave an invalid song ID!";
		die();
	}
        //lookup by ID and request it

        $sql = "SELECT * FROM sm_songs WHERE id = '$song' AND installed=1 ORDER BY title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $commandArgs);
				$displayModeDiff = display_ModeDiff($commandArgs);
				$displayArtist = get_duplicate_song_artist ($row["id"]);
        		echo "@$user requested " . trim($row["title"]." ".$row["subtitle"]). $displayArtist . " from " . $row["pack"].$displayModeDiff;
        		die();
    		}
	} else {
        	echo "@$user => Didn't find any songs matching the ID: " . $song . "!";
        	die();
}

die();
}

if(isset($_GET["song"]) && !empty($_GET["song"])){
	$commandArgs = parseCommandArgs($_GET["song"],$user,$broadcaster);

	if(empty($commandArgs["song"])){
		echo "@$user didn't specify a song name!";
		die();
	}

	$song = $commandArgs["song"];

	//easter egg requests
	$song = is_emote_request($song);
	//process & clean song
	$song = clean($song);
	
	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
        $sql = "SELECT * FROM sm_songs WHERE (IF(strippedsubtitle is NULL OR strippedsubtitle='',strippedtitle,CONCAT(strippedtitle,'-',strippedsubtitle))=\"$song\" OR strippedtitle=\"$song\") AND installed = 1 ORDER BY title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        	request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $commandArgs);
			$displayModeDiff = display_ModeDiff($commandArgs);
			$displayArtist = get_duplicate_song_artist ($row["id"]);
        	echo "@$user requested " . trim($row["title"]." ".$row["subtitle"]). $displayArtist . " from " . $row["pack"].$displayModeDiff;;
    	}
	die();
	//end exact match
	}

        $sql = "SELECT sm_songs.id AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.pack AS pack FROM sm_songs 
				LEFT JOIN sm_songsplayed ON sm_songs.id = sm_songsplayed.song_id 
				WHERE (IF(strippedsubtitle is NULL OR strippedsubtitle='',strippedtitle,CONCAT(strippedtitle,'-',strippedsubtitle)) LIKE \"%$song%\" OR strippedtitle LIKE \"%$song%\") AND installed = 1 
				GROUP BY sm_songs.id 
				ORDER BY SUM(sm_songsplayed.numplayed) DESC, title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    	while($row = mysqli_fetch_assoc($retval)) {
			request_song($row["id"], $user, $tier, $twitchid, $broadcaster, $commandArgs);
			$displayModeDiff = display_ModeDiff($commandArgs);
			$displayArtist = get_duplicate_song_artist ($row["id"]);
        	echo "@$user requested " . trim($row["title"]." ".$row["subtitle"]). $displayArtist . " from " . $row["pack"].$displayModeDiff;;
    	}
	die();
	//end one match
	}
	//no one match
	if (mysqli_num_rows($retval) > 0) {
		echo "@$user => Top matches (request with !requestid [song id]):";
		$i=1;
    	while($row = mysqli_fetch_assoc($retval)) {
        	if($i>4){die();}
			$displayArtist = get_duplicate_song_artist ($row["id"]);
			echo " [ ".$row["id"]. " -> " .trim($row["title"]." ".$row["subtitle"]).$displayArtist." from ".$row["pack"]." ] ";
			$i++;
    	}
	} elseif (is_numeric($song)) {
		echo "@$user => Did you mean to use !requestid ".$song."?";
	}else{
		echo "@$user => Didn't find any songs matching that name! Check the !songlist.";
	}

	die();
}

mysqli_close($conn);
die();
?>
