<?php

include('config.php');
include('misc_functions.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}

if(!isset($_GET["user"])){
	die();
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

//check if the active channel category/game is StepMania, etc.
if(isset($_GET["game"]) && !empty($_GET["game"])){
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

//get broadcaster
if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
	$broadcasterQuery = $broadcaster;
	if (isset($_GET["song"]) || isset($_GET["songid"])){
		check_request_toggle($broadcaster);
	}
}else{
	$broadcaster = "";
	$broadcasterQuery = "%";
}

$userobj = check_user($twitchid, $user);

if(isset($_GET["demand"])){

    $sql = "SELECT * FROM sm_requests WHERE requestor = '{$user}' AND broadcaster LIKE '{$broadcasterQuery}' AND state <> 'canceled' AND state <> 'skipped' AND state <> 'completed' AND state <> 'demanded' ORDER BY request_time DESC LIMIT 1";
	$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {

			$request_id = $row["id"];
			$song_id = $row["song_id"];
			
			$sql2 = "SELECT * FROM sm_songs WHERE id = '{$song_id}' LIMIT 1";
			$retval2 = mysqli_query( $conn, $sql2 );
			while($row2 = mysqli_fetch_assoc($retval2)){
				$sql3 = "UPDATE sm_requests SET state = 'demanded' WHERE id = '{$request_id}'";
				$retval3 = mysqli_query( $conn, $sql3 );
				echo "{$user} demands ".trim($row2["title"]." ".$row2["subtitle"]);
			}
		}

	}else{
		echo "$user hasn't requested any songs!";
	}

die();
}

mysqli_close($conn);
die();
?>
