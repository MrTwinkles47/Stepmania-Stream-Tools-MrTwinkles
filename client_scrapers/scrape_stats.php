<?php

/////
//SM5 Stats.xml scraper
//Call this scraper each time the Stats.xml file(s) are modified.
//The scraper will not run with out specifying at least one profile ID! 
//You can run the scraper in auto-run mode, which will run the script each time a Stats.xml file changes.
//To run in auto-run mode: add "-auto" as an argument along with the profileID(s).
//For each profile you want scraped, pass the profile ID/folder as an arguement:
//Example for the Stats.xml file in the first folder, "00000000": "php scrape_stats.php 00000000".
//Add additional profile IDs by space [_]: "php scrape_stats.php 00000000 00000001".
/////

//Welcome message
$versionClient = get_version();
echo "  ____  __  __ ____                            _       " . PHP_EOL;
echo " / ___||  \/  |  _ \ ___  __ _ _   _  ___  ___| |_ ___ " . PHP_EOL;
echo " \___ \| |\/| | |_) / _ \/ _\` | | | |/ _ \/ __| __/ __|" . PHP_EOL;
echo "  ___) | |  | |  _ <  __/ (_| | |_| |  __/\__ \ |_\__ \\" . PHP_EOL;
echo " |____/|_|  |_|_| \_\___|\__, |\__,_|\___||___/\__|___/" . PHP_EOL;
echo "                            |_|                        " . PHP_EOL;
echo "" . PHP_EOL;
echo "Version: $versionClient";
echo "" . PHP_EOL;
echo "StepMania Stats.XML Scraper" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

//Config
if(file_exists(__DIR__."/config.php") && is_file(__DIR__."/config.php")){
	require ('config.php');
}else{
	wh_log("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.");
	die("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.".PHP_EOL);
}

if (php_sapi_name() == "cli") {
	// In cli-mode
	//process command arguments as profile IDs and run mode
	$profileIDs = array();
	$autoRun = TRUE;
	$frequency = 5;
	$fileTime = "";

	if ($argc > 1){
		$argv = array_splice($argv,1);
		foreach ($argv as $arg){
			if (is_numeric($arg) && strlen($arg) == 8){
				$profileIDs[] = $arg;
			}elseif ($arg == "-auto"){
				$autoRun = FALSE;
			}
		}
		if($USBProfile){
			if (empty($profileIDs)){die("Please specify at least 1 profile ID! Usage: scrape_stats.php [-auto] 00000000");}
		}
	}else{
		die("No arguments! Usage: scrape_stats.php [-auto] 00000000");
	}

} else {
	// Not in cli-mode
	if (!isset($_GET['security_key']) || $_GET['security_key'] != $security_key || empty($_GET['security_key'])){die("Fuck off");}
	$security_key = $GET['security_key'];

	//check for profile IDS
	if (!isset($_GET['id']) || empty($_GET['id'])){die("Please specify at least 1 profile ID! Usage: &id=00000000+00000001");}
	//check if no arguments are specified
	if (!isset($_GET['auto']) && !isset($_GET['id'])){die("No arguments! Usage: scrape_stats.php?id=00000000[+00000001]&[auto]");}

	//process command arguments as profile IDs and run mode
	$profileIDs = array();
	$autoRun = TRUE;
	$frequency = 5;
	$fileTime = '';

	//get auto mode
	if (isset($_GET['auto'])){$autoRun = FALSE;}
	//get profile IDs
	if (isset($_GET['id'])){
		$getIds = explode("+",isset($_GET['id']));
		foreach ($getIds as $getId){
			if (is_numeric($getId) && strlen($getId) == 8){
				$profileIDs[] = $getId;
			}
		}
	}
}

//

//check for offline mode in the config
if ($autoRun == FALSE && $offlineMode == TRUE){die("[-auto] and \"Offline Mode\" cannot be set at the same time!");}

function check_environment(){
	//check for a php.ini file
	$iniPath = php_ini_loaded_file();

	if(!$iniPath){
		//no config found
		wh_log("ERROR: A php.ini configuration file was not found. Refer to the documentation on how to configure your php envirnment for SMRequests.");
		die("A php.ini configuration file was not found. Refer to the documentation on how to configure your php envirnment for SMRequests." . PHP_EOL);
	}else{
		//config found. check for enabled extensions
		$expectedExts = array('curl','json','mbstring','SimpleXML');
		$loadedPhpExt = get_loaded_extensions();

		foreach ($expectedExts as $ext){
			if(!in_array($ext,$loadedPhpExt)){
				wh_log("ERROR: $ext extension not enabled. Please enable the extension in your config file: \"$iniPath\"");
				die("$ext extension not enabled. Please enable the extension in your config file: \"$iniPath\"" . PHP_EOL);
			}
		}
	}
}

function wh_log($log_msg){
    $log_filename = __DIR__."/log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_' . date('Y-m-d') . '.log';
	$log_msg = rtrim($log_msg); //remove line endings
    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
    file_put_contents($log_file_data, date("Y-m-d H:i:s") . " -- [" . strtoupper(basename(__FILE__)) . "] : ". $log_msg . PHP_EOL, FILE_APPEND);
}

function get_version(){
	//check the version of this script against the server
	$versionFilename = __DIR__."/VERSION";

	if(file_exists($versionFilename)){
		$versionClient = file_get_contents($versionFilename);
		$versionClient = json_decode($versionClient,TRUE);
		$versionClient = $versionClient['version'];

//		if($versionServer > $versionClient){
//			wh_log("Script out of date. Client: ".$versionClient." | Server: ".$versionServer);
//			die("WARNING! Your client scripts are out of date! Update your scripts to the latest version! Exiting..." . PHP_EOL);
//		}
	}else{
		$versionClient = 0;
		wh_log("Client version not found or unexpected value. Check VERSION file in client scrapers folder.");
	}
	return $versionClient;
}

function fixEncoding($line){
	//detect and convert ascii, et. al directory string to UTF-8 (Thanks, StepMania!)
	$encoding = mb_detect_encoding($line,'UTF-8,CP1252,ASCII,ISO-8859-1');
	if($encoding != 'UTF-8'){
		wh_log( "Invalid UTF-8 detected ($encoding). Converting...");
		$line = mb_convert_encoding($line,'UTF-8',$encoding);
		wh_log("New Text: ".$line);
	}elseif($encoding == FALSE || empty($encoding)){
		//encoding not detected, assuming 'ISO-8859-1', again, thanks, StepMania.
		$encoding = 'ISO-8859-1';
		wh_log("Invalid UTF-8 detected ($encoding) (fallback). Converting...");
		$line = mb_convert_encoding($line,'UTF-8',$encoding);
		wh_log( "New Text: ".$line);
	}
	//afer conversion we check AGAIN to confirm the new line is encoded as UTF-8
	if(!mb_check_encoding($line,'UTF-8')){
		//string still has invalid characters, give up and remove them completely
		$line = mb_convert_encoding($line,'UTF-8','UTF-8');
		wh_log("Failed additional check. UTF-8,UTF-8 converted line: $line");
	}
	return $line;
}

function parseXmlErrors($errors,$xmlArray){
	foreach ($errors as $error){
		if ($error->code == 9){
			//error code: 9 is "Invalid UTF-8 encoding detected"
			echo "Oh look! StepMania left us invalid UTF-8 characters in an XML file.".PHP_EOL;
			echo "I recommend removing all special characters from this song's directory name!".PHP_EOL;
			wh_log("Oh look! StepMania left us invalid UTF-8 characters in an XML file. I recommend removing all special characters from this song's directory name!");
			//get line number of the invalid character(s)
			$lineNo = $error->line - 1;
			//open file, fix encoding, and write a new line
			echo "Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xml[$lineNo])."] Fixing (Temporarily)...".PHP_EOL;
			wh_log("Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xml[$lineNo])."] Fixing (Temporarily)...");
			$xmlArray[$lineNo] = fixEncoding($xmlArray[$lineNo]);
		}elseif($error->code != 9){
			//error code is not "9"
			wh_log(implode(PHP_EOL,$errors)); 
			print_r($errors);
		}
	}
	//write back changes to the file in memory and save as string
	$xmlStr = implode(PHP_EOL,$xmlArray);
	return $xmlStr;
}

function find_statsxml($directory,$profileIDs){
	//look for any Stats.xml files in the profile directory
	$file_arr = array();
	$i = 0;
	foreach ($profileIDs as $profileID){
		foreach (glob($directory."/".$profileID."/Stats.xml",GLOB_BRACE) as $xml_file){
			//build array of file directory, IDs, modified file times, and set the inital timestamp to "0"
			$file_arr[$i]['id'] = $profileID;
			$file_arr[$i]['file'] = $xml_file;
			$file_arr[$i]['ftime'] = '';
			$file_arr[$i]['mtime'] = filemtime($xml_file);
			$file_arr[$i]['timestampLastPlayed'] = 0;
			$i++;
		}
		if (empty($file_arr)){
			wh_log("Stats.xml file(s) not found! LocalProfiles directory not found in Stepmania Save directory. Also, if you are not running Stepmania in portable mode, your Stepmania Save directory may be in \"AppData\".");
			exit ("Stats.xml file(s) not found! LocalProfiles directory not found in Stepmania Save directory. Also, if you are not running Stepmania in portable mode, your Stepmania Save directory may be in \"AppData\".");
		}
	}
	return $file_arr;
}

function statsXMLtoArray ($xml_file,$timestampLastPlayed){
	//create array to store xml file
	$statsLastPlayed = array();
	$statsHighScores = array();
	$stats_arr = array();
	unset ($xml,$xmlArray,$xmlStr,$errors);
	
	//open xml file
	libxml_clear_errors();
	libxml_use_internal_errors(TRUE);
	$xml = simplexml_load_file($xml_file);

	//check for errors with the xml file that will prevent a successful parse
	$errors = libxml_get_errors();
	if (!empty($errors)){
		//attempt to fix errors in memory then load xml (fixed) via string
		//not a great solution, but blame StepMania, not me!
		$xmlArray = file($xml_file);
		$xmlStr = parseXmlErrors($errors,$xmlArray);
		$xml = FALSE;
		wh_log("Loading Stats.xml file as a string (after correcting for UTF-8 errors).");
		while (!$xml){
			libxml_clear_errors();
			$xml = simplexml_load_string($xmlStr);
			$errors = libxml_get_errors();
			if (!empty($errors)){
				$xmlArray = explode(PHP_EOL,$xmlStr);
				$xmlStr = parseXmlErrors($errors,$xmlArray);
				$xml = FALSE;
			}
		}
	}

	//die if too many errors
	if(!$xml){wh_log("Too many errors with Stats.xml file."); die ("Too many errors with Stats.xml file." . PHP_EOL);}

	// Example xml structure of Stats.xml file:
	// $xml->SongScores->Song[11]['Dir'];
	// $xml->SongScores->Song[11]->Steps['Difficulty'];
	// $xml->SongScores->Song[11]->Steps['StepsType'];
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Grade;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Score;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->PercentDP;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Modifiers;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->DateTime;

	$display_name = (string)$xml->GeneralData->DisplayName;
	$playerGuid = (string)$xml->GeneralData->Guid;

	foreach ($xml->SongScores->Song as $song){
		$song_dir = (string)$song['Dir'];
		
		foreach ($song->Steps as $steps){		
			$steps_type = (string)$steps['StepsType']; //dance-single, dance-double, etc.
			$difficulty = (string)$steps['Difficulty']; //Beginner, Medium, Expert, etc.
			$chartHash = (string)$steps['Hash']; //OutFox chart hash
			$stepsDescription = (string)$steps['Description']; //OutFox steps description
			
			foreach ($steps->HighScoreList as $high_score_lists){
				$num_played = (string)$high_score_lists->NumTimesPlayed; //useful for getting popular songs
				$last_played = (string)$high_score_lists->LastPlayed; //date the song/difficulty was last played

				$dateTimeHS = array(null);
				$highScores = array();

				foreach ($high_score_lists->HighScore as $high_score){				
					$highScores[] = $high_score;
					$dateTimeHS[] = (string)$high_score->DateTime;
				}

				$dateTimeMax = max($dateTimeHS);
				if (strtotime($dateTimeMax) > strtotime($last_played)){
					$last_played = $dateTimeMax;
				}
				
				if (!empty($highScores)){
					foreach ($highScores as $highScoreSingle){
						if((string)strtotime($highScoreSingle->DateTime) > strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
							$statsHighScores[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played, 'HighScore' => $highScoreSingle);
						}
					}
				}
				if(strtotime($last_played) >= strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
					$statsLastPlayed[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played);
					$timestampLastPlayedArr[] = $last_played;
				}
			}
		}
	}

	$timestampLastPlayed = max($timestampLastPlayedArr);
	$stats_arr = array('LastPlayed' => $statsLastPlayed, 'HighScores' => $statsHighScores, 'timestampLastPlayed' => $timestampLastPlayed);
	return $stats_arr; 
}

function curlPost($postSource, $array){
	global $target_url;
	global $security_key;
	global $offlineMode;
	$versionClient = get_version();
	//add the security_key to the array
	$jsMicro = microtime(true);
	$jsonArray = array('security_key' => $security_key, 'source' => $postSource, 'version' => $versionClient, 'offline' => $offlineMode, 'data' => $array);
	//encode array as json
	$post = json_encode($jsonArray);
	wh_log ("Creating JSON took: " . round(microtime(true) - $jsMicro,3) . " secs.");
	$errorJson = json_last_error();
	if($errorJson != "JSON_ERROR_NONE"){
		//there was an error with the json string
		wh_log(json_last_error_msg());
		die(json_last_error_msg().PHP_EOL);
	}
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$target_url."/status.php?$postSource");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //if true, must specify cacert.pem location in php.ini
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_POST,1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$result = curl_exec ($ch);
	//$error = curl_strerror(curl_errno($ch));
	if(curl_exec($ch) === FALSE){
		wh_log("Curl error: ".curl_error($ch));
		echo 'Curl error: '.curl_error($ch) . PHP_EOL;
	}
	if(curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400){
		echo $result; //echo from the server-side script
		wh_log($result);
		wh_log("cURL exec took: " . round(curl_getinfo($ch)['total_time_us'] / 1000000,3)." secs");
	}else{
		echo "There was an error communicating with $target_url.".PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
	}
	curl_close ($ch);
	unset($ch,$result,$post,$jsonArray);
}

//check php environment
check_environment();

//find stats.xml files
$file_arr = find_statsxml ($profileDir,$profileIDs);

if (!$autoRun){
	echo "\\\\\\\\\\\\\\\\\\AUTO MODE ENABLED////////" . PHP_EOL;
	wh_log("AUTO MODE ENABLED");
}

//endless loop
for (;;){

	foreach ($file_arr as &$file){

		$file['mtime'] = filemtime($file['file']);
		if ($file['ftime'] <> $file['mtime']) {
			echo PHP_EOL;
			$startMicro = microtime(true);
			echo "Starting scrape of profile ".$file['id']."..." . PHP_EOL;
			wh_log("Starting scrape of profile ".$file['id']);
			//parse stats.xml file to an array
			$statsMicro = microtime(true);
			$stats_arr = statsXMLtoArray ($file['file'], $file['timestampLastPlayed']);
			//save the last played timestamp in the $file array
			$file['timestampLastPlayed'] = $stats_arr['timestampLastPlayed'];
			wh_log ("Stats.XML parse of " . $file['id'] . " took: " . round(microtime(true) - $statsMicro,3) . " secs.");
			//LastPlayed
			$lpMicro = microtime(true);
			wh_log("Uploading " . count($stats_arr['LastPlayed']) . " lastplayed records.");
			curlPost("lastplayed", $stats_arr['LastPlayed']);
			wh_log ("POST and processing of LastPlayed of " . $file['id'] . " took: " . round(microtime(true) - $lpMicro,3) . " secs.");
			//HighScores
			$hsMicro = microtime(true);
			wh_log("Uploading " . count($stats_arr['HighScores']) . " highscores records.");
			curlPost("highscores", $stats_arr['HighScores']);
			wh_log ("POST and processing of HighScores of " . $file['id'] . " took: " . round(microtime(true) - $hsMicro,3) . " secs.");
			echo "Done " . PHP_EOL;
			wh_log ("Done. Scrape of " . $file['id'] . " took: " . round(microtime(true) - $startMicro,3) . " secs.");
			unset($stats_arr);
		}
		$file['ftime'] = $file['mtime'];
	}
	if ($autoRun){
		//autorun was not set, break the loop
		break;
	}
	echo ".";
	clearstatcache();
	sleep($frequency);
}
exit();
?>
