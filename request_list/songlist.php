<?php
//get domain and subdomain
$fulldomain = $_SERVER['SERVER_NAME'];
$domain = substr($fulldomain,strpos($fulldomain,'.')+1,strrpos($fulldomain,'.'));
if($domain == 'smrequests.com' || $domain == 'smrequests.dev'){
	$subdomain = substr($fulldomain,0,strpos($fulldomain,'.'));
	if( substr(strrev($subdomain),0,1)=="s"){
		$subdomain = ucfirst($subdomain.'\'');
	}else{
		$subdomain = ucfirst($subdomain.'\'s');
	}
	//setup dynamic strings for smrequests hosts
	$pageTitle = $subdomain." Songlist";
	$googleTag = "<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-R45DT9VTL6\"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());
	
	  gtag('config', 'G-R45DT9VTL6');
	</script>";
	$hostingFooter = "Hosting provided by <a href=\"https://$domain\" target=\"_blank\">SMRequests</a>.";
}else{
	$pageTitle = "SM5 Songlist";
	$googleTag = "";
	$hostingFooter = "";
}
?>

<html>
<head>
<title><?php echo $pageTitle; ?></title>
<link rel="stylesheet" 
	href="w3.css">
<link rel="stylesheet" 
	href="w3-theme-dark-grey.css">
<link rel="icon" 
      type="image/png" 
      href="images/ddr_arrow.png">
<meta name="robots" content="noindex,nofollow">	  
<style>
	body {
		/*background-image: url("images/background.jpg");*/
		background-color:#303030;
	}
</style>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
  $("tr").click(function(){
	details_id = $(this).attr('id');
	song_id = $(this).attr('song_id');
	$("#"+song_id).toggle();
	$("#"+details_id).toggle();
  });
});
</script>
<?php echo $googleTag.PHP_EOL; ?>
</head>

<body>
<div class="w3-container w3-theme-dark">

<center><h1><a href="songlist.php"><img src="images/ddr_arrow.png" align="float:left" width="35px" style="margin:5px"></a><strong><?php echo $pageTitle; ?></strong></h1>
</center>

<?php

//

//THIS FILE IS A MESS. I WELCOME ANYONE TO IMPROVE IT.

//

require_once ('config.php');

//create connection
$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
$conn->set_charset("utf8mb4");

function escape_string($str){
	global $conn;
	$str = htmlspecialchars($str);
	$str = mysqli_real_escape_string($conn, $str);
return $str;	
}

//if no variables are set, order table by random to encourage song discovery
if(!isset($_GET['order']) && !isset($_GET['query']) && !isset($_GET['sort']) && !isset($_GET['pack'])){
	$order = 'RAND()';
	$query = "";
	$pack = "";
	$sort = 'ASC';
	//$_GET['random'] = "Random";
}else{

	//query get setup
	if(isset($_GET['query'])){
		$query = escape_string($_GET['query']);
	}else{
		$query = "";
	}

	//setup order and sorts GET	
	if(isset($_GET['order'])){
		$order = escape_string($_GET['order']);
	}else{
		$order = 'PACK';
	}

	if(isset($_GET['sort'])){
		$sort = escape_string($_GET['sort']);
	}else{
		$sort = 'ASC';
	}
	
	//get selected pack from dropdown
	if(isset($_GET['pack']) && isset($_GET['order'])){
		$pack = escape_string($_GET['pack']);
	}elseif(isset($_GET['pack']) && !isset($_GET['order'])){
		$pack = escape_string($_GET['pack']);
		$order = 'TITLE';
	}else{
		$pack = "";
	}
}

//pagination setup
if(isset($_GET['pageno'])){
	$pageno = escape_string($_GET['pageno']);
}else{
	$pageno = 1;
}

$no_of_records_per_page = 50;
$offset = ($pageno-1) * $no_of_records_per_page;

//was the random button clicked?		
if(isset($_GET['random'])){
	$order = "RAND()";
}

//get total songs and packs
$no_of_songs_sql = "SELECT COUNT(*) FROM sm_songs WHERE installed = 1";
$result = mysqli_query($conn, $no_of_songs_sql);
$no_of_songs = mysqli_fetch_array($result)[0];
		
$no_of_packs_sql = "SELECT COUNT(DISTINCT pack) FROM sm_songs WHERE installed = 1";
$result = mysqli_query($conn, $no_of_packs_sql);
$no_of_packs = mysqli_fetch_array($result)[0];

// output songlist statistics
echo '<center><h3>' . number_format($no_of_songs,0,0,",") . ' songs in ' . number_format($no_of_packs,0,0,",") . ' packs</h3></center>';

//show how to request a song and other commands
echo '<center><h4>To request a song, type <strong>!request [<i>songname</i>]</strong> into the chat or <strong>!requestid [<i>id</i>]</strong>, if you know the ID# of the song.<br>
Made an Oops!, use <strong>!cancel</strong> to cancel your last request.<br>
Feeling lucky, use <strong>!random</strong> for a random song or <strong>!top</strong> for a top 100 song.
</h4></center>';

//get distinct packs and # of songs from db and set as array
$packlist = array();
if(strlen($query)>0){
	$packlist_sql = "SELECT pack, COUNT(id) AS id FROM sm_songs WHERE installed = 1 AND (title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') GROUP BY pack";
}else{
	$packlist_sql = "SELECT pack, COUNT(id) AS id FROM sm_songs WHERE installed = 1 GROUP BY pack";
}
$result = mysqli_query($conn, $packlist_sql);
while( $row = mysqli_fetch_assoc($result)){
	$packlist = array_merge($packlist, array($row['pack'] => $row['id']));
}

//show input field for searching database
echo '<center><div class="w3-center w3-input w3-border w3-light-grey" style="width:60%">
		<form method="GET">
		<input type="TEXT" style="width:100%" name="query" value="';
echo isset($_GET['query']) ? $_GET['query'] : "";
echo '" placeholder="Input a song title or artist" autofocus="AutoFocus"/>';
echo  '<select name="pack" id="pack" style="width:100%" class="w3-input w3-padding-small w3-border">
		<option value="none" selected disabled>Select a pack...</option>';
		foreach( $packlist as $key=>$value){
			echo '<option value="'.$key.'"';
			if (isset($_GET['pack']) && $_GET['pack'] == $key){echo ' selected';}
			echo '>'.$key.' ['.$value.']</option>';
		}
echo '</select>';
echo '<input type="SUBMIT" value="Search" class="w3-btn w3-border"/>
	  <input type="SUBMIT" name="random" value="Random" class="w3-btn w3-border"/>
	  <a href="songlist.php">Reset</a>';
echo '</form>';
echo '</div></center>';

//substitute spaces for % to widen the query results
$query = str_replace(" ","%",$query);

//get most recent date from database
$max_date_sql = mysqli_query($conn,"SELECT DATE(MAX(added)) AS max_date FROM sm_songs WHERE installed = 1");
$row = mysqli_fetch_array($max_date_sql);
$updated_date = $row["max_date"];

//find total number of pages of rows
$total_pages_sql = "SELECT COUNT(*) FROM sm_songs WHERE installed = 1 and ((title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') AND (pack LIKE '%{$pack}'))";
$result = mysqli_query($conn, $total_pages_sql);
$total_rows = mysqli_fetch_array($result)[0];
$total_pages = ceil($total_rows / $no_of_records_per_page);

//build mysql query as a string
$base_sql = "SELECT sm_songs.id AS id,trim(concat(title,' ',subtitle,IF(bga=1,'  [V]',''))) AS title,music_length,added,artist,pack,sec_to_time(music_length) AS LENGTH,IF(sm_songs.display_bpm>0,sm_songs.display_bpm,NULL) AS BPM, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.credit END) AS credit_BSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.credit END) AS credit_ESP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.credit END) AS credit_MSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.credit END) AS credit_HSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.credit END) AS credit_CSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.credit END) AS credit_XSP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.credit END) AS credit_BDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.credit END) AS credit_EDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.credit END) AS credit_MDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.credit END) AS credit_HDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.credit END) AS credit_CDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.credit END) AS credit_XDP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.chart_name END) AS chartname_BSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.chart_name END) AS chartname_ESP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.chart_name END) AS chartname_MSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.chart_name END) AS chartname_HSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.chart_name END) AS chartname_CSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.chart_name END) AS chartname_XSP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.chart_name END) AS chartname_BDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.chart_name END) AS chartname_EDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.chart_name END) AS chartname_MDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.chart_name END) AS chartname_HDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.chart_name END) AS chartname_CDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.chart_name END) AS chartname_XDP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.description END) AS description_BSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.description END) AS description_ESP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.description END) AS description_MSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.description END) AS description_HSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.description END) AS description_CSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.description END) AS description_XSP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.description END) AS description_BDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.description END) AS description_EDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.description END) AS description_MDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.description END) AS description_HDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.description END) AS description_CDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.description END) AS description_XDP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.radar_values END) AS radar_BSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.radar_values END) AS radar_ESP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.radar_values END) AS radar_MSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.radar_values END) AS radar_HSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.radar_values END) AS radar_CSP, 
max(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.radar_values END) AS radar_XSP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.radar_values END) AS radar_BDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.radar_values END) AS radar_EDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.radar_values END) AS radar_MDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.radar_values END) AS radar_HDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.radar_values END) AS radar_CDP, 
max(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.radar_values END) AS radar_XDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.meter END) AS meter_BSP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.meter END) AS meter_ESP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.meter END) AS meter_MSP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.meter END) AS meter_HSP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.meter END) AS meter_CSP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.meter END) AS meter_XSP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.meter END) AS meter_BDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.meter END) AS meter_EDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.meter END) AS meter_MDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.meter END) AS meter_HDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.meter END) AS meter_CDP, 
MAX(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.meter END) AS meter_XDP
FROM sm_songs
JOIN sm_notedata ON sm_songs.id=sm_notedata.song_id
WHERE stepstype NOT LIKE 'lights-cabinet' AND sm_songs.installed = 1 AND (
				(title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') 
				AND (pack LIKE '%{$pack}')
				) 
GROUP BY sm_songs.id 
ORDER BY {$order} {$sort} LIMIT {$offset}, {$no_of_records_per_page}";

$result = mysqli_query($conn, $base_sql);

//undo query % formatting
$query = str_replace("%"," ",$query);

//toggle sorts
$sortp = $sort;
$pack = stripslashes($pack);
$query = stripslashes($query);

//show summary of results above table
if (strlen($query)<1 && strlen($pack)<1){
		echo '<div class="w3padding-small"><h3>All songs sorted ';
			if($order == 'RAND()') {
			echo 'randomly'; 
			}else{ echo 'by '.$order.'';
			}
		echo ':</h3></div>';
	}else{
		echo '<div class="w3padding-small"><h3>Found ' .number_format($total_rows,0,0,",").' song(s) ';
		if(strlen($query)>0){
			echo 'for "'.$query.'"';
		}
			if(strlen($pack)>0){
			echo ' in '.$pack;
			}
			echo ' sorted ';
			if($order == 'RAND()') {
				echo 'randomly'; 
				}else{ echo 'by '.$order.'';
				}
		
			echo ':</h3></div>';
	}

//showing property
echo '<table class="w3-table-all w3-margin-top" id="myTable">
	<colgroup>
	<col style="width: 2%">
	<col style="width: 27%">
	<col style="width: 27%">
	<col style="width: 27%">
	<col style="width: 5%">
	<col style="width: 2%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	</colgroup>
	<tbody>
      <tr class="data-heading">';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=ID&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">ID</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=TITLE&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">TITLE</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=ARTIST&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">ARTIST</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=PACK&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">PACK</a></th>';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=LENGTH&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">LENGTH</a></th>';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=BPM&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">BPM</a></th>';
echo '<th colspan="6" class="w3-center">SINGLE</th>';
echo '<th colspan="6" class="w3-center">DOUBLE</th>';
echo '</tr>';
echo '<tr>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_bsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_beginner.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_esp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_light.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_msp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_standard.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_hsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_heavy.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_csp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_challenge.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_xsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_edit.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_bdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_beginner.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_edp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_light.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_mdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_standard.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_hdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_heavy.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_cdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_challenge.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_xdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_edit.gif"></a></th>';
echo '</tr>';

$songs = Array();
$charts = Array("BSP", "ESP", "MSP", "HSP", "CSP", "XSP", "BDP", "EDP", "MDP", "HDP", "CDP", "XDP");

while ($row = mysqli_fetch_array($result)) {

	$s_id = $row["id"];

	$songs["$s_id"]["id"]=$row["id"];
	$songs["$s_id"]["title"]=$row["title"];
	$songs["$s_id"]["artist"]=$row["artist"];
	$songs["$s_id"]["pack"]=$row["pack"];
	$songs["$s_id"]["bpm"]=$row["BPM"];
	$songs["$s_id"]["length"]=$row["LENGTH"];

	
	foreach($charts as $difficulty){
		$songs["$s_id"]["charts"]["$difficulty"]["meter"]=$row["meter_$difficulty"];
		$songs["$s_id"]["charts"]["$difficulty"]["credit"]=$row["credit_$difficulty"];
		$songs["$s_id"]["charts"]["$difficulty"]["chartname"]=$row["chartname_$difficulty"];
		$songs["$s_id"]["charts"]["$difficulty"]["description"]=$row["description_$difficulty"];

		$songs["$s_id"]["charts"]["$difficulty"]["radar"]=$row["radar_$difficulty"];
		if($songs["$s_id"]["charts"]["$difficulty"]["radar"] != ""){
			$exploded_radar = explode(",",$songs["$s_id"]["charts"]["$difficulty"]["radar"]);
			$songs["$s_id"]["charts"]["$difficulty"]["groove_radar"] = "$exploded_radar[0], $exploded_radar[4], $exploded_radar[3], $exploded_radar[2], $exploded_radar[1]";
			$songs["$s_id"]["charts"]["$difficulty"]["steps"] = round($exploded_radar[6]);
			$songs["$s_id"]["charts"]["$difficulty"]["jumps"] = round($exploded_radar[7]);
			$songs["$s_id"]["charts"]["$difficulty"]["holds"] = round($exploded_radar[8]);
			$songs["$s_id"]["charts"]["$difficulty"]["mines"] = round($exploded_radar[9]);
			$songs["$s_id"]["charts"]["$difficulty"]["hands"] = round($exploded_radar[10]);
			$songs["$s_id"]["charts"]["$difficulty"]["rolls"] = round($exploded_radar[11]);
			switch($difficulty){
				case "BSP": $songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Beginner"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single"; break;
				case "ESP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Easy"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single"; break;
				case "MSP": $songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Medium"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single";	break;
				case "HSP": $songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Hard"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single"; break;
				case "CSP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Challenge"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single"; break;
				case "XSP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Edit"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-single"; break;
				case "BDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Beginner"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				case "EDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Easy"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				case "MDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Medium"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				case "HDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Hard"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				case "CDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Challenge"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				case "XDP":	$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "Edit"; $songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "dance-double"; break;
				default:
					echo "";
			}
		}else{
			$songs["$s_id"]["charts"]["$difficulty"]["groove_radar"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["steps"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["jumps"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["holds"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["mines"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["hands"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["rolls"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["difficulty"] = "";
			$songs["$s_id"]["charts"]["$difficulty"]["stepstype"] = "";
		}
	}

}

foreach($songs as $song){

	echo "
<tr song_id=\"{$song["id"]}\">
	<td>{$song["id"]}</td>
	<td>{$song["title"]}</td>
	<td>{$song["artist"]}</td>
	<td>{$song["pack"]}</td>
	<td>{$song["length"]}</td>
	<td>{$song["bpm"]}</td>
	<td style=\"background-color: rgba(0, 255, 255, 0.2);\">{$song["charts"]["BSP"]["meter"]}</td>
	<td style=\"background-color: rgba(251, 169, 0, 0.2);\">{$song["charts"]["ESP"]["meter"]}</td>
	<td style=\"background-color: rgba(250, 0, 160, 0.2);\">{$song["charts"]["MSP"]["meter"]}</td>
	<td style=\"background-color: rgba(102, 250, 0, 0.2);\">{$song["charts"]["HSP"]["meter"]}</td>
	<td style=\"background-color: rgba(112, 104, 250, 0.2);\">{$song["charts"]["CSP"]["meter"]}</td>
	<td style=\"background-color: rgba(150, 150, 150, 0.2);\">{$song["charts"]["XSP"]["meter"]}</td>
	<td style=\"background-color: rgba(0, 255, 255, 0.2);\">{$song["charts"]["BDP"]["meter"]}</td>
	<td style=\"background-color: rgba(251, 169, 0, 0.2);\">{$song["charts"]["EDP"]["meter"]}</td>
	<td style=\"background-color: rgba(250, 0, 160, 0.2);\">{$song["charts"]["MDP"]["meter"]}</td>
	<td style=\"background-color: rgba(102, 250, 0, 0.2);\">{$song["charts"]["HDP"]["meter"]}</td>
	<td style=\"background-color: rgba(112, 104, 250, 0.2);\">{$song["charts"]["CDP"]["meter"]}</td>
	<td style=\"background-color: rgba(150, 150, 150, 0.2);\">{$song["charts"]["XDP"]["meter"]}</td>";

	echo "</tr>
	<tr style=\"display:none;\" id=\"{$song["id"]}\">
		<td colspan=2>
		<table class=\"w3-small\" style=\"padding: 0px 0px\">
		<tr><td colspan=4 class=\"w3-center\"><b>DANCE-SINGLE</b></td></tr>";

	foreach($song["charts"] as $difficulty=>$chart){
		if($chart["meter"] != "" && $chart["stepstype"] == "dance-single"){		
			//There is a chart for $difficulty.
			switch ($chart["difficulty"]) {
					case "Beginner":
					  $color = "0, 255, 255";
					  break;
					case "Easy":
					  $color = "251, 169, 0";
					  break;
					case "Medium":
					  $color = "250, 0, 160";
					  break;
					case "Hard":
					  $color = "102, 250, 0";
					  break;
					case "Challenge":
					  $color = "112, 104, 250";
					  break;
					default:
					  $color = "55, 55, 55";
				  }
			echo "<tr style=\"background-color: rgba({$color}, 0.2);\"><td style=\"text-align: right;\">{$chart["difficulty"]}:</td><td style=\"text-align: center;\"><b>{$chart["meter"]}</b></td><td colspan=2>";
			if($chart["credit"] != ""){echo " ({$chart["credit"]})</b></td>";}else{echo "</td></tr>";}
			echo "<tr><td><img src=\"images/steps.png\" alt=\"Steps\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["steps"]}</td>";
			echo "<td><img src=\"images/mines.png\" alt=\"Mines\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["mines"]}</td></tr>";
			echo "<tr><td><img src=\"images/jumps.png\" alt=\"Jumps\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["jumps"]}</td>";
			echo "<td><img src=\"images/hands.png\" alt=\"Hands\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["hands"]}</td></tr>";
			echo "<tr><td><img src=\"images/holds.png\" alt=\"Holds\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["holds"]}</td>";
			echo "<td><img src=\"images/rolls.png\" alt=\"Rolls\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["rolls"]}</td></tr>";
		}
	}
		
	echo "</table></td><td colspan=1><table class=\"w3-small\" style=\"padding: 0px 0px\">
		<tr><td colspan=4 class=\"w3-center\"><b>DANCE-DOUBLE</b></td></tr>";

	foreach($song["charts"] as $difficulty=>$chart){
		if($chart["meter"] != "" && $chart["stepstype"] == "dance-double"){		
			//There is a chart for $difficulty.
			switch ($chart["difficulty"]) {
					case "Beginner":
					  $color = "0, 255, 255";
					  break;
					case "Easy":
					  $color = "251, 169, 0";
					  break;
					case "Medium":
					  $color = "250, 0, 160";
					  break;
					case "Hard":
					  $color = "102, 250, 0";
					  break;
					case "Challenge":
					  $color = "112, 104, 250";
					  break;
					default:
					  $color = "55, 55, 55";
				  }
			echo "<tr style=\"background-color: rgba({$color}, 0.2);\"><td style=\"text-align: right;\">{$chart["difficulty"]}:</td><td style=\"text-align: center;\"><b>{$chart["meter"]}</b></td><td colspan=2>";
			if($chart["credit"] != ""){echo " ({$chart["credit"]})</b></td>";}else{echo "</td></tr>";}
			echo "<tr><td><img src=\"images/steps.png\" alt=\"Steps\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["steps"]}</td>";
			echo "<td><img src=\"images/mines.png\" alt=\"Mines\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["mines"]}</td></tr>";
			echo "<tr><td><img src=\"images/jumps.png\" alt=\"Jumps\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["jumps"]}</td>";
			echo "<td><img src=\"images/hands.png\" alt=\"Hands\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["hands"]}</td></tr>";
			echo "<tr><td><img src=\"images/holds.png\" alt=\"Holds\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["holds"]}</td>";
			echo "<td><img src=\"images/rolls.png\" alt=\"Rolls\" width=\"16\" height=\"16\" align=\"right\"></td><td>{$chart["rolls"]}</td></tr>";
		}
	}

		//dance-single radar
		echo "</table></td>
		<td colspan=1></td>
		<td colspan=6 style=\"height:1px;\">
		<div class=\"chart-container\" style=\"position:relative; height:100%; min-height:200px; border:1px #000;\">
			<canvas id=\"grooveRadar-dance-single-{$song["id"]}\"></canvas>
		</div>";

		echo "<script>
		var ctx = document.getElementById('grooveRadar-dance-single-{$song["id"]}');
		var myChart = new Chart(ctx, {
			type: 'radar',
			data: {
				labels: ['Stream', 'Chaos', 'Freeze', 'Air', 'Voltage'],
				datasets: [";

		foreach($song["charts"] as $difficulty=>$chart){
			if($chart["meter"] != "" && $chart["stepstype"] == "dance-single"){
				switch ($difficulty) {
					case "BSP":
					  $color = "0, 255, 255";
					  break;
					case "ESP":
					  $color = "251, 169, 0";
					  break;
					case "MSP":
					  $color = "250, 0, 160";
					  break;
					case "HSP":
					  $color = "102, 250, 0";
					  break;
					case "CSP":
					  $color = "112, 104, 250";
					  break;
					default:
					  $color = "55, 55, 55";
				  }
				  echo "{
					data: [{$chart["groove_radar"]}],
					backgroundColor: [
						'rgba($color, 0.4)'
					],
					borderColor: [
						'rgba($color, 1)'
					],
					borderWidth: 2,
					pointRadius: 0
				},";
			}
		}
		
		echo "
		]
			},
			options: {
				scale: {
					angleLines: {
						display: true
					},
					ticks: {
						suggestedMin: 0,
						suggestedMax: 1,
						display: false
					}
				},
				legend: {
					display: false
				},
				tooltips: {
					enabled: false
				},
				responsive:true,
				maintainAspectRatio: false
			}
		});
		</script>";
		
	//dance-double radar
	echo "</td>
		
		<td colspan=8 style=\"height:1px;\">
		<div class=\"chart-container\" style=\"position:relative; height:100%; min-height:200px; border:1px #000;\">
			<canvas id=\"grooveRadar-dance-double-{$song["id"]}\"></canvas>
		</div>";

		echo "<script>
		var ctx = document.getElementById('grooveRadar-dance-double-{$song["id"]}');
		var myChart = new Chart(ctx, {
			type: 'radar',
			data: {
				labels: ['Stream', 'Chaos', 'Freeze', 'Air', 'Voltage'],
				datasets: [";

		foreach($song["charts"] as $difficulty=>$chart){
			if($chart["meter"] != "" && $chart["stepstype"] == "dance-double"){
				switch ($difficulty) {
					case "BDP":
					  $color = "0, 255, 255";
					  break;
					case "EDP":
					  $color = "251, 169, 0";
					  break;
					case "MDP":
					  $color = "250, 0, 160";
					  break;
					case "HDP":
					  $color = "102, 250, 0";
					  break;
					case "CDP":
					  $color = "112, 104, 255";
					  break;
					default:
					  $color = "55, 55, 55";
				  }
				  echo "{
					data: [{$chart["groove_radar"]}],
					backgroundColor: [
						'rgba($color, 0.4)'
					],
					borderColor: [
						'rgba($color, 1)'
					],
					borderWidth: 2,
					pointRadius: 0
				},";
			}
		}
		
		echo "
		]
			},
			options: {
				scale: {
					angleLines: {
						display: true
					},
					ticks: {
						suggestedMin: 0,
						suggestedMax: 1,
						display: false
					}
				},
				legend: {
					display: false
				},
				tooltips: {
					enabled: false
				},
				responsive:true,
				maintainAspectRatio: false
			}
		});
		</script>";

	echo "</td>	
	</tr>
	";

}
echo "</tbody></table>";
mysqli_close($conn);
?>

<div class="w3-left">
	Songlist last updated: <?php echo $updated_date; ?>
</div>

<div class="w3-right">	
	Records <?php echo number_format(($pageno*$no_of_records_per_page)-$no_of_records_per_page+1,0,0,','); ?>-<?php if($pageno==$total_pages){echo number_format($total_rows,0,0,','); } else { echo number_format($pageno*$no_of_records_per_page,0,0,',');} ?> of 
	<?php echo number_format($total_rows,0,0,','); ?>
</div>

<div class="w3-bar w3-border w3-round">
  <a href="<?php { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=1"; } ?>" class="w3-button">&laquo; First</a>
  
  <a href="<?php if($pageno <= 1){ echo '#'; } else { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".($pageno - 1); } ?>" class="w3-button">&#10094; Previous</a>
  
  <a href="<?php { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".$total_pages; } ?>" class="w3-button w3-right">Last &raquo;</a>
  
  <a href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".($pageno + 1); } ?>" class="w3-button w3-right">Next &#10095;</a>
</div>
</div>

<div class="w3-padding-small w3-container w3-theme w3-center">
SMRequests is a song request and hosted songlist tool for live streaming StepMania 5. Check out the current project on <a href="https://github.com/MrTwinkles47/Stepmania-Stream-Tools-MrTwinkles" target="_blank">Github</a>. Thanks to <a href="https://twitch.tv/ddrdave" target="_blank">ddrDave</a> for the original project and concept.
<?php echo $hostingFooter.PHP_EOL; ?>
</div>

</html>
</body>
<?php die(); ?>
