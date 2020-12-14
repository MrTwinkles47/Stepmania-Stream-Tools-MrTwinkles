<?php

include("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
    die("Fuck off");
}

if(!isset($_GET["broadcaster"]) && (!isset($_GET["stepstyle"]) || !isset($_GET["meter"]) || !isset($_GET["requesttoggle"]))){
	die();
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function add_broadcaster($broadcaster){
    
    global $conn;

	$sql = "INSERT INTO sm_broadcaster (broadcaster, request_toggle) VALUES (\"$broadcaster\", \"ON\")";
	$retval = mysqli_query( $conn, $sql );
	$the_id = mysqli_insert_id($conn);

	return($the_id);
}

function getMeterRange(){
    global $conn;
    $sql0 = "SELECT MIN(meter) AS min, MAX(meter) AS max FROM sm_notedata";
    $retval0 = mysqli_query( $conn, $sql0 );
    $row0 = mysqli_fetch_assoc($retval0);
    $meters = array('min'=>$row0['min'], 'max'=>$row0['max']);
    return $meters;
}

function toggle_requests($broadcaster,$message){

    global $conn;

	$sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster = \"$broadcaster\"";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);
    if($numrows == 0){
        //settings for this broadcaster are not in the db. Let's set up a blank profile
        add_broadcaster($broadcaster);
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);    
    }

        if($numrows == 1){
            $row0 = mysqli_fetch_assoc($retval0);
		    $id = $row0["id"];
            $toggle = $row0["request_toggle"];
            if($toggle == "ON"){
                $value = "OFF";
                if(empty($message)){
                    $message = "";
                    $response = "Requests are disabled.";
                }else{
                    $response = "Requests are disabled: ".$message;
                }
            }else{
                $value = "ON";
                $response = "Requests are enabled.";
                $message = "";
            }

                $sql = "UPDATE sm_broadcaster SET request_toggle=\"$value\", message=\"$message\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );

            echo ("$response");

	}

}

function limit_stepstype($broadcaster,$stepstype){

    global $conn;

    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster = \"$broadcaster\"";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);
    if($numrows == 0){
        //settings for this broadcaster are not in the db. Let's set up a blank profile
        add_broadcaster($broadcaster);
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0); 
    }

    if($numrows == 1){
        $row0 = mysqli_fetch_assoc($retval0);
        $id = $row0["id"];
        $stepstype_db = $row0["stepstype"];
        if(empty($stepstype) && empty($stepstype_db)){
            $response = "No limit present.";
        }elseif(empty($stepstype) && !empty($stepstype_db)){
            $response = "Stepstype currently limited to $stepstype_db";
        }elseif(!empty($stepstype)){
            if($stepstype == "-1"|| $stepstype == "disable" || $stepstype == "off" || $stepstype == "remove" || $stepstype == "stop"){
                $response = "Removing stepstype limit.";
                $sql = "UPDATE sm_broadcaster SET stepstype=\"\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );
            }elseif($stepstype == "singles" || $stepstype == "doubles"){ 
                $response = "Changing stepstype to $stepstype";
                $stepstype = "dance-".substr($stepstype,0,-1);
                $sql = "UPDATE sm_broadcaster SET stepstype=\"$stepstype\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );
            }else{
                $response = "Invalid stepstype. Useage: \"singles\", \"doubles\", or \"off\".";
            }  
        }          
            echo "$response";
    }

}

function change_meter($broadcaster,$meter){

    global $conn;

    $sql0 = "SELECT * FROM sm_broadcaster WHERE broadcaster = \"$broadcaster\"";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);
    if($numrows == 0){
        //settings for this broadcaster are not in the db. Let's set up a blank profile
        add_broadcaster($broadcaster);
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0); 
    }

    if($numrows == 1){
        $row0 = mysqli_fetch_assoc($retval0);
        $id = $row0["id"];
        $meter_db = $row0["meter_max"];
        if(empty($meter) && empty($meter_db)){
            $response = "No difficulty limit present.";
        }elseif(empty($meter) && !empty($meter_db)){
            $response = "Difficulty meter currently limited to $meter_db";
        }elseif(!empty($meter)){
            if($meter == "-1" || $meter == "disable" || $meter == "off" || $meter == "remove" || $meter == "stop"){
                $response = "Removing difficulty meter limit.";
                $sql = "UPDATE sm_broadcaster SET meter_max=\"\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );
            }else{
                $response = "Changing max difficulty meter to $meter";
                $sql = "UPDATE sm_broadcaster SET meter_max=\"$meter\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );
            }
        }            
            echo "$response";
    }

}

if(isset($_GET["requesttoggle"])){
    if(strlen($_GET["requesttoggle"]) >= 40){
        die("Custom message must be 40 characters or less.");
    }
    $message = trim(mysqli_real_escape_string($conn, $_GET["requesttoggle"]));
	toggle_requests(strtolower($_GET["broadcaster"]),$message);
}

if(isset($_GET["stepstype"])){
    $stepstype = trim(mysqli_real_escape_string($conn,$_GET["stepstype"]));
    limit_stepstype($_GET["broadcaster"],$stepstype);
}

if(isset($_GET["meter"])){
    $meter = trim(mysqli_real_escape_string($conn,$_GET["meter"]));
    if(!is_numeric($meter) || $meter > getMeterRange()['max']){
        die("Invalid meter given.");
    }
    change_meter($_GET["broadcaster"],$meter);
}

mysqli_close($conn);
die();
?>