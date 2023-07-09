<?php

require_once ('config.php');
require_once ('misc_functions.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}

if((!isset($_GET["bansong"]) || !isset($_GET["bansongid"])) && !isset($_GET["user"])){
	die();
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function toggle_ban_song($id,$type){
	//1 = ban from all
	//2 = ban from random

    global $conn;

	$sql0 = "SELECT * FROM sm_songs WHERE id = \"$id\"";
        $retval0 = mysqli_query( $conn, $sql0 );

        if(mysqli_num_rows($retval0) == 1){
            $row0 = mysqli_fetch_assoc($retval0);
            $title = $row0["title"];
			$subtitle = $row0["subtitle"];
            $pack = $row0["pack"];
            $banned = $row0["banned"];
		if($banned != "0"){
			$value = "0";
			$response = "Unbanned $title $subtitle from $pack";
		}elseif($type == "song"){
			$value = "1";
			$response = "Banned $title $subtitle from $pack";
		}elseif($type == "random"){
			$value = "2";
			$response = "Banned (random) $title $subtitle from $pack";
		}

	        $sql = "UPDATE sm_songs SET banned=$value WHERE id=$id LIMIT 1";
        	mysqli_query( $conn, $sql );

		echo "$response";

	}else{
		echo "Something went wrong.";
	}

}

//die if the command did not come from the broadcaster
//$user = $_GET["user"];
//$broadcaster = $_GET["broadcaster"];
//if(strtolower($user)!==$broadcaster){die("That's gonna be a no from me, dawg.");}

function parseBanType($argsStr){
	//parse #args to determine if the ban type is for "random"

    $result = array('song'=>'','ban'=>'');
    $args = explode("#",$argsStr,2);
    $args = array_map("trim",$args);
    $result['song'] = $args[0];

	foreach ($args as $arg){
		switch (strtolower($arg)){
			case "random":
			case "randoms":
			case "rand":
			case "rnd":
				$result['ban'] = "random";
			break;
			default:
				$result['ban'] = "song";
		}
	}

    return $result;
}

if(isset($_GET["bansongid"])){
	$commandArgs = parseBanType($_GET["bansongid"]);
	$song = clean($commandArgs["song"]);
	$type = $commandArgs["ban"];

    //lookup by ID
	$sql = "SELECT * FROM sm_songs WHERE id = '$song' ORDER BY title ASC";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    	while($row = mysqli_fetch_assoc($retval)) {
        	toggle_ban_song($song,$type);
        	die();
    	}
	} else {
        echo "Didn't find any songs matching that id!";
        die();
	}

die();
}

if(isset($_GET["bansong"])){
	$commandArgs = parseBanType($_GET["bansong"]);
	$song = clean($commandArgs["song"]);
	$type = $commandArgs["ban"];

	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
	$sql = "SELECT * FROM sm_songs WHERE strippedtitle='$song' ORDER BY title ASC";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        	toggle_ban_song($row["id"],$type);
    	}
	die();
	//end exact match
	}

	$sql = "SELECT * FROM sm_songs WHERE strippedtitle LIKE '%$song%' ORDER BY title ASC, pack ASC";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
			toggle_ban_song($row["id"],$type);
		}
	die();
	//end one match
	}
	//no one match
	if (mysqli_num_rows($retval) > 0) {
		echo "@$user => No exact match (!bansongid [id]):";
		$i=1;
		while($row = mysqli_fetch_assoc($retval)) {
			if($i>4){die();}
			echo " [ ".$row["id"]. " -> " .trim($row["title"]." ".$row["subtitle"])." from ".$row["pack"]." ]";
			$i++;
		}
	}

die();
}

mysqli_close($conn);

?>
