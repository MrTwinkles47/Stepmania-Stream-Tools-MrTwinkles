<?php

require_once ('config.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key || empty($_GET["security_key"])){
        die("Error: Missing or incorrect security key.");
}

if(!isset($_GET["banuser"]) && !isset($_GET["whitelist"])){
	die();
}

if((isset($_GET["banuser"]) && empty($_GET["banuser"])) || (isset($_GET["whitelist"]) && empty($_GET["whitelist"]))){
        die("No user specified!");
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function clean_user($user){
        global $conn;
        $user = urldecode(trim($user));
        $user = mysqli_real_escape_string($conn,$user);
        $user = strtolower($user);
        if (strpos($user,'@') === 0){
                $user = substr($user,1);
        }
        return $user;
}

function toggle_ban($user){

    global $conn;

	$sql0 = "SELECT * FROM sm_requestors WHERE name = '$user'";
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);
        if($numrows == 0){
                echo "User has to request a song before being banned, or be manually added.";
	}

        if($numrows == 1){
                $row0 = mysqli_fetch_assoc($retval0);
		$id = $row0["id"];
                $banned = $row0["banned"];
		if($banned == "true"){
			$value = "false";
			$response = "Unbanned $user. Don't be a dick.";
		}else{
			$value = "true";
			$response = "Banned $user. I'm sorry. it's for the best.";
		}

	        $sql = "UPDATE sm_requestors SET banned=\"$value\" WHERE id=\"$id\" LIMIT 1";
        	mysqli_query( $conn, $sql );

		echo "$response";

	}

}

function toggle_whitelist($user){

        global $conn;

        $sql0 = "SELECT * FROM sm_requestors WHERE name = '$user'";
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);
        if($numrows == 0){
                echo "User has to request a song before being whitelisted, or be manually added.";
        }

        if($numrows == 1){
                $row0 = mysqli_fetch_assoc($retval0);
                $id = $row0["id"];
                $banned = $row0["whitelisted"];
                if($banned == "true"){
                        $value = "false";
                        $response = "Unwhitelisted $user. Hope you like cooldowns.";
                }else{
                        $value = "true";
                        $response = "Whitelisted $user. With great power comes great responsibility.";
                }

                $sql = "UPDATE sm_requestors SET whitelisted=\"$value\" WHERE id=\"$id\" LIMIT 1";
                mysqli_query( $conn, $sql );

                echo "$response";

        }

}

//get user who sent the command
if(!isset($_GET["user"])){
	die("Error");
}elseif(isset($_GET["user"])){
        $user = mysqli_real_escape_string($conn,$_GET["user"]);
}

//special rules for broadcaster
if(isset($_GET["broadcaster"]) && !empty($_GET["broadcaster"])){
	$broadcaster = mysqli_real_escape_string($conn,$_GET["broadcaster"]);
}

if(isset($_GET["banuser"]) && !empty($_GET["banuser"])){
        $banUser = clean_user($_GET["banuser"]);
        if(strtolower($user) != strtolower($broadcaster) && $banUser == strtolower($broadcaster)){
                die("@$user => You don't have that kind of power here!");
        }elseif(strtolower($user) != strtolower($broadcaster) && $banUser == strtolower($user)){
                die("@$user => You can't (un)ban yourself!");
        }
	toggle_ban($banUser);
        die();
}

if(isset($_GET["whitelist"]) && !empty($_GET["whitelist"])){
        $whitelistUser = clean_user($_GET["whitelist"]);
        if(strtolower($user) != strtolower($broadcaster) && $whitelistUser == strtolower($broadcaster)){
                die("@$user => You don't have that kind of power here!");
        }elseif(strtolower($user) != strtolower($broadcaster) && $whitelistUser == strtolower($user)){
                die("@$user => You can't (un)whitelist yourself!");
        }
        toggle_whitelist($whitelistUser);
        die();
}

mysqli_close($conn);
?>
