<?php
//
//Contains all functions for managing users during request processing and other misc. functions that are called throughout
//

//include("config.php");

//-------Polyfill Function---------//

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

//---------SMR Functions---------// 
$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function clean($string) {
    $string = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $string); //tranliterate
    $string = preg_replace('/ +/', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    return $string;
}

function add_user($userid, $user){

	global $conn;
	
	$user = strtolower($user);
	$sql = "INSERT INTO sm_requestors (twitchid, name, dateadded) VALUES ('$userid', '$user', NOW())";
	mysqli_query( $conn, $sql );
	$the_id = mysqli_insert_id($conn);

	return($the_id);

}

function check_user($userid, $user){

    global $conn;

    $user = strtolower($user);

    //case where the bot cannot supply the twitchid, use the name
    if($userid > 0 || !empty($userid)){
        $sql0 = "SELECT * FROM sm_requestors WHERE twitchid = '$userid'";
    }else{
        $sql0 = "SELECT * FROM sm_requestors WHERE name = '$user'";
    }
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);

    if($numrows != 0){
        //User exists in DB, return data
        $row0 = mysqli_fetch_assoc($retval0);
        $id = $row0["id"];
        $twitchid = $row0["twitchid"];
        $whitelisted = $row0["whitelisted"];
        $banned = $row0["banned"];
    }else{
        //User is new - create then return data
        $id = add_user($userid, $user);
        $whitelisted = "false";
        $banned = "false";
    }

    $userobj["id"] = "$id";
    $userobj["name"] = "$user";
    $userobj["twitchid"] = "$userid";
    $userobj["whitelisted"] = "$whitelisted";
    $userobj["banned"] = "$banned";

    return($userobj);

}

function check_length($maxRequests){
    global $conn;
    if(!isset($maxRequests) || !is_numeric($maxRequests)){
        $maxRequests = 10;
    }
    $sql0 = "SELECT state FROM sm_requests ORDER BY request_time DESC LIMIT $maxRequests";
    $retval0 = mysqli_query( $conn, $sql0 );
    $length = 0;
    foreach($retval0 as $row){
        if($row['state'] == 'requested'){
            $length++;
        }
    }
    if($length > $maxRequests){
        die("Too many songs on the request list! Try again in a few minutes.");
    }
    return $length;
}

function check_cooldown($user){
    global $cooldownMultiplier;
    global $cooldownUser;
    global $maxRequests;

    //check config variables
    if(empty($cooldownMultiplier) || !is_numeric($cooldownMultiplier)){
        $cooldownMultiplier = 0.5; //minutes per open request
    }
    if(empty($cooldownUser) || !is_numeric($cooldownUser)){
        $cooldownUser = 30; //seconds
    }
    if(empty($maxRequests) || !is_numeric($maxRequests)){
        $maxRequests = 10;
    }

    //check total length of requests, if over maxRequests, stop
    $length = check_length($maxRequests);

    // global cooldown + user cooldown
    $interval = ($cooldownMultiplier * $length) + ($cooldownUser / 60);

    //scale cooldown as a function of the number of requests. X minutes per open request.	
    global $conn;
    $sql0 = "SELECT * FROM sm_requests WHERE state <> 'canceled' AND requestor = '$user' AND request_time > DATE_SUB(NOW(), INTERVAL $interval MINUTE)";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);
    if($numrows > 0 && floor($interval) > 1){
        die("@$user => Slow down there, part'ner! Try again in ".floor($interval)." minutes.");
    }elseif($numrows > 0 && floor($interval) <= 1){
        die("@$user => Slow down there, part'ner! Try again in 1 minute.");
    }
}

function requested_recently($song_id,$requestor,$whitelisted,$interval = 1){
    global $conn;
    
    if(empty($interval) || !is_numeric($interval)){$interval = 1;}
    $sql0 = "SELECT COUNT(*) AS total 
            FROM sm_requests 
            WHERE song_id = '$song_id' AND (state = 'requested' OR state = 'completed') AND request_time > DATE_SUB(NOW(), INTERVAL $interval HOUR)";
	$retval0 = mysqli_query( $conn, $sql0 );
	$row0 = mysqli_fetch_assoc($retval0);

	if($row0["total"] > 0){
    //if(($row0["total"] > 0) && ($whitelisted != "true")){
        die("$requestor => This song has already been requested recently!");
    }
}

function recently_played($song_id,$interval){
	global $conn;
	$recently_played = FALSE;
    if(empty($interval) || !is_numeric($interval)){$interval = 1;}
	$sql = "SELECT song_id FROM sm_songsplayed WHERE song_id = $song_id AND lastplayed > DATE_SUB(NOW(), INTERVAL $interval HOUR)";
	$retval = mysqli_query($conn,$sql);
	if(mysqli_num_rows($retval) > 0){
		$recently_played = TRUE;
	}
	return $recently_played;
}

function is_emote_request($song){
    $emoteArray = array (
        "mrtwin1HaHaHa"     =>  "The Smiler",
        "danizoOHNO"        =>  "Minna no Kimochi",
        "beniplKitty"       =>  "Kitty From Hell",
        "ddrDav2"           =>  "Heaven is a 57 metallic gray",
        "hellki1Nabi1"      =>  "My Baby Mama",
        "iambgsKool"        =>  "New Horizons TOKYO",
        "kikoiaRusty"       =>  "Doom Crossing",
        "noreseSLOW"        =>  "VEAH",
        "xancarDex"         =>  "Ding Dong Song"
    );

    if(array_key_exists($song,$emoteArray)){
        $song = $emoteArray[$song];
    }

    return $song;
}

function get_broadcaster_limits($broadcaster){
    global $conn;
    $broadcaserLimits = array();
    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
    $retval0 = mysqli_query( $conn, $sql0 );

    if(mysqli_num_rows($retval0) == 1){
        $broadcaserLimits = mysqli_fetch_assoc($retval0);
    }
    return $broadcaserLimits;
}

function check_request_toggle($broadcaster,$user = NULL){
    global $conn;

    if(strtolower($broadcaster) == strtolower($user)){
		//requestor is broadcaster: bypass
        return;
    }

    if(check_user(0,$user)["banned"] == "true"){
        //user is banned
        die();
    }

    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);

    if($numrows == 1){
        $row0 = mysqli_fetch_assoc($retval0);
        $request_toggle = $row0["request_toggle"];
        $message = $row0["message"];
        if($request_toggle == "OFF"){
            $requestsDisableResponses = array("Requests are off.","Requests are disabled.","Requests are deactivated.");
            $response = $requestsDisableResponses[array_rand($requestsDisableResponses,1)];
            die($response . " " . $message);
        }
    }
}

function check_stepstype($broadcaster,$song_id){
    global $conn;

    $response = TRUE;
    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);

    if($numrows == 1){
        $row0 = mysqli_fetch_assoc($retval0);
        $stepstype = $row0["stepstype"];
        if(!empty($stepstype)){
            $sql = "SELECT * FROM sm_notedata WHERE song_id = '$song_id' AND stepstype LIKE '$stepstype'";
            $retval = mysqli_query( $conn, $sql);
            if(mysqli_num_rows($retval) == 0){
                $response = FALSE;
            }
        }
    }
    return $response;
}

function check_meter($broadcaster,$song_id){
    global $conn;

    $response = TRUE;
    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);

    if($numrows == 1){
        $row0 = mysqli_fetch_assoc($retval0);
        $meter_max = $row0["meter_max"];
        if(!empty($meter_max)){
            $sql = "SELECT * FROM sm_notedata WHERE song_id = '$song_id' AND stepstype LIKE 'dance%' AND meter <= '$meter_max'";
            $retval = mysqli_query( $conn, $sql);
            if(mysqli_num_rows($retval) == 0){
                $response = FALSE;
            }
        }
    }
    return $response;
}

function check_notedata($broadcaster,$song_id,$stepstype,$difficulty,$user){
    global $conn;
    $response = TRUE;

    if(!empty($stepstype) && $stepstype != '%' && empty($difficulty)){
        $difficulty = '%';
    }elseif(empty($stepstype) && !empty($difficulty)){
        die("@$user didn't specify a stepstype!");
    }

    $sql = "SELECT * FROM sm_notedata WHERE song_id = '$song_id' AND stepstype LIKE '$stepstype' AND difficulty LIKE '$difficulty'";
    $retval = mysqli_query( $conn, $sql);
    if(mysqli_num_rows($retval) == 0){
        $response = FALSE;
    }
    return $response;
}

function get_duplicate_song_artist($song_id){
    //check if the title/pack of the songid is a duplicate and return the artist
    global $conn;
    $response = "";

    $sql = "SELECT * FROM sm_songs
            JOIN sm_songs AS t2 ON
            sm_songs.strippedtitle = t2.strippedtitle AND sm_songs.strippedsubtitle = t2.strippedsubtitle AND sm_songs.pack = t2.pack
            WHERE t2.id = '$song_id' AND sm_songs.installed = 1";
    $retval = mysqli_query( $conn, $sql);
    if(mysqli_num_rows($retval) > 1){
        //the song title is a duplicate in this pack, return the artist of the songid
        $sql = "SELECT artist FROM sm_songs WHERE id = '$song_id'";
        $retval = mysqli_query( $conn, $sql);
        $response = mysqli_fetch_assoc($retval)['artist'];
        $response = " by " . $response;
    }
    return $response;
}

function parseCommandArgs($argsStr,$user,$broadcaster){
    global $conn;

    //build a blank array
    $result = array('song'=>'','stepstype'=>'','difficulty'=>'','meter'=>'');
    //split string by '#', keeping the delimiter, and trimming
    $args = preg_split('/(?=#)/', $argsStr,-1,PREG_SPLIT_NO_EMPTY);
    $args = array_map("trim",$args);
    //splice "song", keep arguments in the array
    for($i=0; $i < count($args); $i++){
        if(substr($args[$i],0,1) === "#"){
            //$args[$i] = trim(str_replace("#","",$args[$i]));
        }else{
            $result['song'] = array_splice($args,$i,1);
        }
    }
    if(is_array($result['song'])){
        $result['song'] = implode("",$result['song']);
    }
    //remove '#' from the resulting array
    $args = array_map(function($str) {return trim(str_replace("#","",$str));},$args);

    if(count($args) == 1 && (strlen($args[0]) == 2 || strlen($args[0]) == 3) ){
        switch (strtoupper($args[0])){
            case "BSP":
            case "SE":
                $result['stepstype'] = "dance-single";
                $result['difficulty'] = "Easy";
            break;
            case "DSP":
            case "MSP":
            case "SSP":
            case "SM":
                $result['stepstype'] = "dance-single";
                $result['difficulty'] = "Medium";
            break;
            case "ESP":
            case "HSP":
            case "SH":
                $result['stepstype'] = "dance-single";
                $result['difficulty'] = "Hard";
            break;
            case "CSP":
            case "SX":
                $result['stepstype'] = "dance-single";
                $result['difficulty'] = "Challenge";
            break;
            case "BDP":
            case "DE":
                $result['stepstype'] = "dance-double";
                $result['difficulty'] = "Easy";
            break;
            case "DDP":
            case "MDP":
            case "SDP":
            case "DM":
                $result['stepstype'] = "dance-double";
                $result['difficulty'] = "Medium";
            break;
            case "EDP":
            case "HDP":
            case "DH":
                $result['stepstype'] = "dance-double";
                $result['difficulty'] = "Hard";
            break;
            case "CDP":
            case "DX":
                $result['stepstype'] = "dance-double";
                $result['difficulty'] = "Challenge";
            break;
            default:
                die("@$user gave an invalid 2- or 3-letter steps-type/difficulty combo.");
        }  
    }elseif(count($args) >= 1){
        //$args = array_splice($args,1);
        foreach ($args as $arg){
            $arg = trim(strtolower($arg));
            if(!is_numeric($arg)){
                switch (strtolower($arg)){
                    case "single":
                    case "singles":
                    case "singlets":
                        $result['stepstype'] = "dance-single";
                    break;
                    case "double":
                    case "doubles":
                    case "doublays":
                        $result['stepstype'] = "dance-double";
                    break;
                    case "beginner":
                        $result['difficulty'] = "Beginner";
                    break;
                    case "easy":
                    case "basic":
                    case "light":
                        $result['difficulty'] = "Easy";
                    break;
                    case "medium":
                    case "standard":
                        $result['difficulty'] = "Medium";
                    break;
                    case "hard":
                    case "heavy":
                    case "expert":
                        $result['difficulty'] = "Hard";
                    break;
                    case "challenge":
                    case "oni":
                        $result['difficulty'] = "Challenge";
                    break;
                    case "edit":
                        $result['difficulty'] = "Edit";
                    break;
                    default:
                        die("$user gave invalid steps-type or difficulty.");
                }
            }elseif(is_numeric($arg)){
                //user specified a #meter instead of a difficulty name
                $result['meter'] = $arg;
            }
        }
    }

    //if stepstype is empty, check if sm_broadcast has one set globally
    if(empty($result['stepstype'])){
        $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
        $retval0 = mysqli_query( $conn, $sql0 );
        if(mysqli_num_rows($retval0) == 1){
            $row0 = mysqli_fetch_assoc($retval0);
            //set broadcaster limit as stepstype
            $result['stepstype'] = $row0["stepstype"];
        }
        if((!empty($result['difficulty']) || !empty($result['meter'])) && empty($result['stepstype'])){
            //no stepstype in sm_broadcaster and only difficulty specified
            die("@$user didn't specify a steps-type!");
        }
    }elseif(!empty($result['stepstype'])){
        //stepstype is specificied, check with broadcaster limits  
        $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster LIKE '$broadcaster'";
        $retval0 = mysqli_query( $conn, $sql0 );
        if(mysqli_num_rows($retval0) == 1){
            $row0 = mysqli_fetch_assoc($retval0);
            if($row0['stepstype'] != $result['stepstype'] && !empty($row0['stepstype'])){
                //specified stepstype != broadcaster limits 
                die("@$user => The broadcaster has limited requests to \"".$row0['stepstype']."\".");
           
            }
        }
    }

    return $result;
}

function meter_to_difficulty($song_id,$stepstype,$difficulty,$meter,$user){
    global $conn;

    if(empty($difficulty) && !empty($meter)){
        $sql = "SELECT * FROM sm_notedata WHERE song_id = '$song_id' AND stepstype LIKE '$stepstype' AND meter LIKE '$meter'";
        $retval = mysqli_query( $conn, $sql);
        if(mysqli_num_rows($retval) === 1){
            $difficulty = mysqli_fetch_assoc($retval)['difficulty'];
        }else{
            die("$user gave an invalid meter: $stepstype [$meter].");
        }
    }
    return $difficulty;
}

function display_ModeDiff($commandArgs){
    //for now we are assuming the game is always StepMania and dance-mode
    $displayModeDiff = "";
    if(!empty($commandArgs['stepstype'])){
        $stepstype = ucwords(str_ireplace("dance-","",$commandArgs['stepstype']));
        $displayModeDiff = " [".$stepstype;
        if(!empty($commandArgs['difficulty'])){
            $displayModeDiff = $displayModeDiff."/".ucwords($commandArgs['difficulty'])."] ";
        }else{
            $displayModeDiff = $displayModeDiff."] ";
        }
    }
    return $displayModeDiff;
}

function wh_log($log_msg){
    $log_filename = __DIR__."/log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0700, true);
    }
    $log_file_data = $log_filename.'/log_' . date('Y-m-d') . '.log';
    $log_msg = rtrim($log_msg); //remove line endings
    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
    file_put_contents($log_file_data, date("Y-m-d H:i:s") . " -- [" . strtoupper(basename(__FILE__)). "] : ". $log_msg . PHP_EOL, FILE_APPEND);
} 

function check_version($versionClient){
	//check the verion of the incoming scripts to the server version
	$versionFilename = __DIR__."/VERSION";
    $githubUrl = "https://github.com/MrTwinkles47/Stepmania-Stream-Tools-MrTwinkles/releases/latest";

	if(file_exists($versionFilename)){
		$versionServer = file_get_contents($versionFilename);
		$versionServer = json_decode($versionServer,TRUE);
		$versionServer = $versionServer['version'];

		if($versionServer > $versionClient){
			//wh_log("Script out of date. Client: ".$versionClient." | Server: ".$versionServer);
			echo("WARNING! Your client scripts are out of date! Download the latest release at " . PHP_EOL);
            echo("$githubUrl   Exiting... " . PHP_EOL);
            die();
		}
	}else{
		$versionServer = 0;
		die("Version check error!");
		//wh_log("Server version not found or unexpected value. Check VERSION file in server root directory.");
	}
	return FALSE;
}

mysqli_close($conn);
?>