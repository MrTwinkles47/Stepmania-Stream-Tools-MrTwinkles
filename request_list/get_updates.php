<?php

require_once ('config.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}
$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function format_pack($pack,$requestor){
	$length = 40;
	$length = $length - (strlen($requestor) * 0.8);

 	$pack = str_ireplace("Dance Dance Revolution","DDR",$pack);
	$pack = str_ireplace("DanceDanceRevolution","DDR",$pack);
	$pack = str_ireplace("Dancing Stage","DS",$pack);
	$pack = str_ireplace("DancingStage","DS",$pack);
	$pack = str_ireplace("In The Groove","ITG",$pack);
	$pack = str_ireplace("InTheGroove","ITG",$pack);
	//$pack = str_ireplace("Ben Speirs","BS",$pack);
	//$pack = str_ireplace("JBEAN Exclusives","JBEAN...",$pack);
	$pack = preg_replace("/(\(.*\).\(.*\))$/","",$pack,1);
	if(strlen($pack) > $length){
		//$pack = trim(substr($pack,0,18))."...".trim(substr($pack,strlen($pack)-7));
		$separator = "...";
		$maxLength = $length - strlen($separator);
		$startTrunc = $maxLength / 2;
		$truncLength =  strlen($pack) - $maxLength; 
		$pack = substr_replace($pack,$separator,$startTrunc,$truncLength);
	}
return $pack;
}   

function rand_gradient(string $pack){
	$brightness = 0.75; //match brightness applied to pack images
	$direction = 360 / str_word_count($pack); //To output a value between 0 and 360 in degrees to be given to the linear-gradient.
	
	$pack = strtolower(str_ireplace(" ","",$pack));
	$packMD5 = md5($pack);

	$colorHex = array();
	$colorHex[] = substr($packMD5, 0, 6); //first 6 characters
	$colorHex[] = substr($packMD5, -6); //last 6 characters

	$colorRGB = array();
	for($x = 0; $x <= 1; $x++){
		//for each 2 hex colors
		for($i = 0; $i <= 4; $i+=2){
			//split each 2 character hex value and convert to dec
			$colorRGB[$x][] = hexdec(substr($colorHex[$x],$i,2)) * $brightness;
		} 
	}
	
	// $r1 = hexdec(substr($colorHex[0],0,2)) * $brightness; 
    // $g1 = hexdec(substr($colorHex[0],2,2)) * $brightness;
    // $b1 = hexdec(substr($colorHex[0],4,2)) * $brightness;
	// $r2 = hexdec(substr($colorHex[1],0,2)) * $brightness; 
    // $g2 = hexdec(substr($colorHex[1],2,2)) * $brightness;
    // $b2 = hexdec(substr($colorHex[1],4,2)) * $brightness;
  
    //Giving values to the linear gradiant.
    //$background = "linear-gradient(${direction}deg, rgba(${r1},${g1},${b1},${a1}), rgba(${r2},${g2},${b2},${a2}))";
	$background = "linear-gradient(${direction}deg, rgb({$colorRGB[0][0]},{$colorRGB[0][1]},{$colorRGB[0][2]}), rgb({$colorRGB[1][0]},{$colorRGB[1][1]},{$colorRGB[1][2]}))";

    return (string)$background;
}

//Get new requests, cancels, and completions

function get_cancels_since($id,$oldid,$broadcaster){

	global $conn;
	$sql = "SELECT * FROM sm_requests WHERE id >= $oldid AND state =\"canceled\" AND broadcaster LIKE \"{$broadcaster}\" ORDER BY id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$cancels = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
        	$request_id = $row["id"];
		array_push($cancels, $request_id);
	}

	return $cancels;

}

function get_requests_since($id,$oldid,$broadcaster){

	global $conn;
	$requests = array();
	$sql = "SELECT sm_requests.id AS id, sm_requests.song_id AS song_id, title, subtitle, artist, pack, requestor, request_time, request_type, stepstype, difficulty 
			FROM sm_requests 
			JOIN sm_songs ON sm_songs.id = sm_requests.song_id 
			WHERE sm_requests.id > $id AND state = 'requested' AND broadcaster LIKE '$broadcaster' 
			ORDER by id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));

	while($request = mysqli_fetch_assoc($retval)) {
		
		//format pack name and find pack banner
		$pack_img = strtolower(preg_replace('/\s+/', '_', trim($request["pack"])));
		$pack_img = glob("images/packs/".$pack_img.".{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,bmp,BMP}", GLOB_BRACE);
		if (!$pack_img){
			$request["img"] = "";
		}else{
			$request["img"] = "images/packs/".urlencode(basename($pack_img[0]));
		}
		$request["background"]= "background:".rand_gradient($request['pack']);
		$request["pack"] = format_pack($request["pack"],$request["requestor"]);

		//format request type and find image
		$request["request_type"] = strtolower($request["request_type"]);
		if($request["request_type"] != "normal"){
			$request_img = glob("images/".$request["request_type"].".{png,PNG,gif,GIF}", GLOB_BRACE);
			if (!$request_img){
				$request["request_type"] = "images/random.png";
			}else{
				$request["request_type"] = "images/".urlencode(basename($request_img[0]));
			}
		}else{
			$request["request_type"] = "";
		}

		//format stepstype & difficulty
		$request["stepstype"] = strtolower($request["stepstype"]);
		$request["difficulty"] = strtolower($request["difficulty"]);

		array_push($requests, $request);

	}

	return $requests;

}

function get_completions_since($id,$oldid,$broadcaster){

        global $conn;
		//$id=$id-50;
        $sql = "SELECT id FROM sm_requests WHERE id >= $oldid AND state = \"completed\" AND broadcaster LIKE \"{$broadcaster}\"";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $completions = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                $request_id = $row["id"];
                array_push($completions, $request_id);
        }

        return $completions;

}

function get_skips_since($id,$oldid,$broadcaster){

	global $conn;
	$sql = "SELECT * FROM sm_requests WHERE id >= $oldid AND state =\"skipped\" AND broadcaster LIKE \"{$broadcaster}\" ORDER BY id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$skips = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
        	$request_id = $row["id"];
		array_push($skips, $request_id);
	}

	return $skips;

}

//mark completed or skipped for "offline mode"

function MarkCompleted($requestid){

	global $conn;
	$requestupdated = 0;

	$sql0 = "SELECT * FROM sm_requests WHERE id = \"$requestid\" AND state <> \"completed\"";
	$retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows == 0){
		die();	
		//die("Marked Complete request could not be found.");
	}

	if($numrows == 1){
			$row0 = mysqli_fetch_assoc($retval0);
			
			$sql = "UPDATE sm_requests SET state=\"completed\" WHERE id=\"$requestid\" LIMIT 1";
			$retval = mysqli_query( $conn, $sql );

			//echo "Request ".$requestid." updated to Completed";
			$requestupdated = 1;
	} else {
		//echo "Too many requests found.";
	}
	return $requestupdated;
}

function MarkSkipped($requestid){

	global $conn;
	$requestupdated = 0;

	$sql0 = "SELECT * FROM sm_requests WHERE id = \"$requestid\" AND state <> \"completed\"";
	$retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows == 0){
		die();	
		//die("Mark Skipped request could not be found.");
	}

	if($numrows == 1){
			$row0 = mysqli_fetch_assoc($retval0);
			
			$sql = "UPDATE sm_requests SET state=\"skipped\" WHERE id=\"$requestid\" LIMIT 1";
			$retval = mysqli_query( $conn, $sql );

			//echo "Request ".$requestid." updated to skipped";
			$requestupdated = 1;
	} else {
		//echo "Too many requests found.";
	}
	return $requestupdated;
}

function MarkBanned($requestid){

	global $conn;
	$requestupdated = 0;

	$sql0 = "SELECT * FROM sm_requests WHERE id = \"$requestid\" AND state <> \"completed\"";
	$retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows == 0){
		die();	
		//die("Mark Banned request could not be found.");
	}

	if($numrows == 1){
			$row0 = mysqli_fetch_assoc($retval0);
			$song_id = $row0['song_id'];
			
			$sql = "UPDATE sm_songs SET banned = 1 WHERE id=\"$song_id\" LIMIT 1";
			$retval = mysqli_query( $conn, $sql );

			$sql = "UPDATE sm_requests SET state=\"skipped\" WHERE id=\"$requestid\" LIMIT 1";
			$retval = mysqli_query( $conn, $sql );

			//echo "Song from request ".$requestid." updated to banned";
			$requestupdated = 1;
	} else {
		//echo "Too many requests found.";
	}
	return $requestupdated;
}

if(!isset($_GET["id"])){die("You must specify an id");}

$id = $_GET["id"];

$output = array();

if(isset($_GET["func"])){
	switch($_GET["func"]){
		case "MarkCompleted":
			$requestupdated = MarkCompleted($id);
		break;
		case "MarkSkipped":
			$requestupdated = MarkSkipped($id);
		break;
		case "MarkBanned":
			$requestupdated = MarkBanned($id);
		break;
		default:
			die();
			//die("Your function is in another castle.");
	}

	$output["requestsupdated"] = $requestupdated;

}elseif(!isset($_GET["func"])){
	if(!empty($_GET["oldid"])){
		$oldid = $_GET["oldid"];
	}else{
		$oldid = 0;
	}

	if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
		$broadcaster = $_GET["broadcaster"];
	}else{
		$broadcaster = "%";
	}

	$output["cancels"] = get_cancels_since($id,$oldid,$broadcaster);

	$output["requests"] = get_requests_since($id,$oldid,$broadcaster);

	$output["completions"] = get_completions_since($id,$oldid,$broadcaster);

	$output["skips"] = get_skips_since($id,$oldid,$broadcaster);

}

$output = json_encode($output);

echo "$output";

mysqli_close($conn);

?>