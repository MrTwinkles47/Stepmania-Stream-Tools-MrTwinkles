<?php

require ('config.php');

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

if(!empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
}else{
	$broadcaster = "%";
}

echo '<html>
<head>
<link rel="stylesheet" href="style.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="scripts.js"></script>
</head>

<body>
<audio id="new" src="new.mp3" type="audio/mpeg"></audio>
<audio id="cancel" src="cancel.mp3" type="audio/mpeg"></audio>
<div id="top">
<div id="middle">';

	if(empty($requestWidgetLength) || !is_numeric($requestWidgetLength) || $requestWidgetLength > 50){
		$requestWidgetLength = 10;
	}

        //$sql = "SELECT * FROM sm_requests WHERE state=\"requested\" OR state=\"completed\" ORDER BY request_time DESC LIMIT 10";
        $sql = "SELECT * FROM sm_requests WHERE ((state=\"requested\" OR state=\"demanded\" OR state=\"completed\") AND broadcaster LIKE \"{$broadcaster}\") ORDER BY FIELD(state,'demanded'), request_time DESC LIMIT $requestWidgetLength";
        $retval = mysqli_query( $conn, $sql );
		  $i=0;

    while($row = mysqli_fetch_assoc($retval)) {

	$request_id = $row["id"];
	$song_id = $row["song_id"];
	$request_time = $row["request_time"];
	$requestor = $row["requestor"];
	$request_type = $row["request_type"];
	$stepstype = $row["stepstype"];
	$difficulty = $row["difficulty"];

	switch ($request_type){
		case "normal":
			$request_type = '';
			break;
		case "random":
			$request_type = '<img src="images/d205.png" class="type">';
			break;
		case "top":
			$request_type = '<img src="images/top.png" class="type">';
			break;
		case "portal":
			$request_type = '<img src="images/portal.png" class="type">';
			break;
		case "gitgud":
			$request_type = '<img src="images/gitgud.png" class="type">';
			break;
		case "theusual":
			$request_type = '<img src="images/theusual.png" class="type">';
			break;
		case "itg":
			$request_type = '<img src="images/itg.png" class="type">';
			break;
		case "ddr":
			$request_type = '<img src="images/ddr.png" class="type">';
			break;
		case "gimmick":
			$request_type = '<img src="images/gimmick.png" class="type">';
			break;
		case "ben":
			$request_type = '<img src="images/ben.png" class="type">';
			break;
		case "bgs":
			$request_type = '<img src="images/bgs.png" class="type">';
			break;
		case "hkc":
			$request_type = '<img src="images/hkc.png" class="type">';
			break;
        case "weeb":
            $request_type = '<img src="images/weeb.png" class="type">';
            break;
        case "miku":
            $request_type = '<img src="images/miku.png" class="type">';
			break;
		default:
			$request_type = '<img src="images/d205.png" class="type">';;
			break;
	}

	switch ($stepstype){
		case "dance-single":
			$stepstype = '<img src="images/singles.png" class="dance single">';
			break;
		case "dance-double":
			$stepstype = '<img src="images/doubles.png" class="dance double">';
			break;
		default:
			$stepstype = "";
			break;
	}

	switch ($difficulty){
		case "Beginner":
			$difficulty = '<div class="difficulty beginner"></div>';
			break;
		case "Easy":
			$difficulty = '<div class="difficulty easy"></div>';
			break;
		case "Medium":
			$difficulty = '<div class="difficulty medium"></div>';
			break;
		case "Hard":
			$difficulty = '<div class="difficulty hard"></div>';
			break;
		case "Challenge":
			$difficulty = '<div class="difficulty challenge"></div>';
			break;
		case "Edit":
			$difficulty = '<div class="difficulty edit"></div>';
			break;
		default:
			$difficulty = '<div class="difficulty"></div>';
			break;
	}

	if(empty($stepstype)){$difficulty = "";}
	
	if($i == 0){
		echo "<span id=\"lastid\" style=\"display:none;\">$request_id</span>\n";
		echo "<span id=\"security_key\" style=\"display:none;\">".urlencode($_GET["security_key"])."</span>\n";
		echo "<span id=\"broadcaster\" style=\"display:none;\">".urlencode($broadcaster)."</span>\n";
		if(isset($_GET['admin'])){echo "<span id=\"admin\" style=\"display:none;\">admin</span>\n";}
		echo "\n";
	}

	$sql2 = "SELECT * FROM sm_songs WHERE id=\"$song_id\" LIMIT 1";
	$retval2 = mysqli_query( $conn, $sql2 );
	    while($row2 = mysqli_fetch_assoc($retval2)) {
		$title = $row2["title"];
		$subtitle = $row2["subtitle"];
		$pack = format_pack($row2["pack"]);
		$pack_img = strtolower(preg_replace('/\s+/', '_', trim($row2["pack"])));
	   }

	$pack_img = glob("images/packs/".$pack_img.".{jpg,jpeg,png,gif}", GLOB_BRACE);
	if (!$pack_img){
		$pack_img = "images/packs/unknown.png";
	}else{
		$pack_img = "images/packs/".urlencode(basename($pack_img[0]));
	}
	
echo "<div class=\"songrow\" id=\"request_".$request_id."\">			
<h2>$title<h2a>$subtitle</h2a></h2>
<h3>$pack</h3>
<h4>$requestor</h4>";
echo $request_type."\n";
echo $difficulty."\n";
echo $stepstype."\n";
echo "<img class=\"songrow-bg\" src=\"{$pack_img}\" />
</div>\n";
if(isset($_GET['admin'])){
	echo "<div class=\"admindiv\" id=\"requestadmin_".$request_id."\">
	<button class=\"adminbuttons\" style=\"margin-left:4vw; background-color:rgb(0, 128, 0);\" type=\"button\" onclick=\"MarkCompleted(".$request_id.")\">Mark Complete</button>\n
	<button class=\"adminbuttons\" style=\"background-color:rgb(153, 153, 0);\" type=\"button\" onclick=\"MarkSkipped(".$request_id.")\">Mark Skipped</button>
	<button class=\"adminbuttons\" style=\"margin-right:4vw; float:right; background-color:rgb(178, 34, 34);\" type=\"button\" onclick=\"MarkBanned(".$request_id.")\">Mark Banned</button>
	</div>\n";
}

	$ids[] = $request_id;
	$i++;
    }

if(!is_array($ids) || empty($ids)){
	$oldid = 0;
}else{
	$oldid = min($ids);
}
	
echo "<span id=\"oldid\" style=\"display:none;\">{$oldid}</span>\n";
echo "
</div>
</html>";

mysqli_close($conn);
?>