<?php

require('config.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}
$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function format_pack($pack){
	$pack = str_ireplace("Dance Dance Revolution","DDR",$pack);
	$pack = str_ireplace("DanceDanceRevolution","DDR",$pack);
	$pack = str_ireplace("Dancing Stage","DS",$pack);
	$pack = str_ireplace("DancingStage","DS",$pack);
	$pack = str_ireplace("In The Groove","ITG",$pack);
	$pack = str_ireplace("InTheGroove","ITG",$pack);
	$pack = str_ireplace("Ben Speirs","BS",$pack);
	$pack = str_ireplace("JBEAN Exclusives","JBEAN...",$pack);
	$pack = preg_replace("/(\(.*\).\(.*\))$/","",$pack,1);
	if(strlen($pack) > 25)
		{$pack = trim(substr($pack,0,18))."...".trim(substr($pack,strlen($pack)-7));
	}
return $pack;
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
        $sql = "SELECT * FROM sm_requests WHERE id > $id AND state = \"requested\" AND broadcaster LIKE \"{$broadcaster}\" ORDER by id ASC";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $requests = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                
		$request_id = $row["id"];
		$requestor = $row["requestor"];
		$song_id = $row["song_id"];
		$request_time = $row["request_time"];
		$request_type = $row["request_type"];
		$stepstype = $row["stepstype"];
		$difficulty = $row["difficulty"];
		
	        $sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\"";
        	$retval2 = mysqli_query( $conn, $sql2 ) or die(mysqli_error($conn2));
           		while($row2 = mysqli_fetch_assoc($retval2)) {
					$request["id"] = $request_id;
					$request["song_id"] = $song_id;
					$request["requestor"] = $requestor;
					$request["request_time"] = $request_time;
					$request["request_type"] = $request_type;
					$request["stepstype"] = $stepstype;
					$request["difficulty"] = $difficulty;
					$request["title"] = $row2["title"];
					$request["subtitle"] = $row2["subtitle"];
					$request["artist"] = $row2["artist"];
					$request["pack"] = format_pack($row2["pack"]);
					$pack_img = strtolower(preg_replace('/\s+/', '_', trim($row2["pack"])));
					$pack_img = glob("images/packs/".$pack_img.".{jpg,jpeg,png,gif}", GLOB_BRACE);
					if (!$pack_img){
						$request["img"] = "images/packs/unknown.png";
					}else{
						$request["img"] = "images/packs/".urlencode(basename($pack_img[0]));
					}
				}

                array_push($requests, $request);
        }

        return $requests;

}

function get_demands_since($id,$oldid,$broadcaster){

	global $conn;
	$sql = "SELECT * FROM sm_requests WHERE id >= $oldid AND state = \"demanded\" AND broadcaster LIKE \"{$broadcaster}\" ORDER by id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$requests = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
			
	$request_id = $row["id"];
	$requestor = $row["requestor"];
	$song_id = $row["song_id"];
	$request_time = $row["request_time"];
	$request_type = $row["request_type"];
	$stepstype = $row["stepstype"];
	$difficulty = $row["difficulty"];
	
		$sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\"";
		$retval2 = mysqli_query( $conn, $sql2 ) or die(mysqli_error($conn2));
			   while($row2 = mysqli_fetch_assoc($retval2)) {
				$demand["id"] = $request_id;
				$demand["song_id"] = $song_id;
				$demand["requestor"] = $requestor;
				$demand["request_time"] = $request_time;
				$demand["request_type"] = $request_type;
				$demand["stepstype"] = $stepstype;
				$demand["difficulty"] = $difficulty;
				$demand["title"] = $row2["title"];
				$demand["subtitle"] = $row2["subtitle"];
				$demand["artist"] = $row2["artist"];
				$demand["pack"] = format_pack($row2["pack"]);
				$pack_img = strtolower(preg_replace('/\s+/', '_', trim($row2["pack"])));
				$pack_img = glob("images/packs/".$pack_img.".{jpg,jpeg,png,gif}", GLOB_BRACE);
				if (!$pack_img){
					$demand["img"] = "images/packs/unknown.png";
				}else{
					$demand["img"] = "images/packs/".urlencode(basename($pack_img[0]));
				}
			}

			array_push($demands, $demand);
	}

	return $demands;

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

	if(!empty($_GET["broadcaster"])){
		$broadcaster = $_GET["broadcaster"];
	}else{
		$broadcaster = "%";
	}

	$cancels = get_cancels_since($id,$oldid,$broadcaster);

	$requests = get_requests_since($id,$oldid,$broadcaster);

	$demands = get_demands_since($id,$oldid,$broadcaster);

	$completions = get_completions_since($id,$oldid,$broadcaster);

	$skips = get_skips_since($id,$oldid,$broadcaster);

	$output["cancels"] = $cancels;
	$output["requests"] = $requests;
	$output["demands"] = $demands;
	$output["completions"] = $completions;
	$output["skips"] = $skips;
}

$output = json_encode($output);

echo "$output";

mysqli_close($conn);

?>