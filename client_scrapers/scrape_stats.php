<?php

/////
//SM5 Stats.xml scraper
//Call this scraper each time the Stats.xml file(s) are modified.
//The scraper will not run with out specifying at least one profile ID in config.php! 
//Run this script in the background while you play. Each time the Stats.xml file changes,
//SMR will recieve the data it needs to auto-complete requests.
/////

if (php_sapi_name() != "cli") {
	// Not in cli-mode
	die("Only support cli mode.");
}
// In cli-mode
$versionClient = get_version();
cli_set_process_title("SMRequests v$versionClient | StepMania Stats.XML Scraper");

//process command arguments
$frequency = 5;
$fileTime = "";
$statsXMLfilename = "Stats.xml";

if ($argc > 1){
	$argv = array_splice($argv,1);
	foreach ($argv as $arg){
		if ($arg == "-auto"){
			//inform user of changes to command arguments
			echo("\"-auto\" is no longer required. Please update your .bat file." . PHP_EOL);
		}else{
			//inform user of changes to command arguments
			die("Profile IDs are now configured in config.php!" . PHP_EOL);
		}
	}
}

//Welcome message
echo "  ____  __  __ ____                            _       " . PHP_EOL;
echo " / ___||  \/  |  _ \ ___  __ _ _   _  ___  ___| |_ ___ " . PHP_EOL;
echo " \___ \| |\/| | |_) / _ \/ _\`| | | |/ _ \/ __| __/ __|" . PHP_EOL;
echo "  ___) | |  | |  _ <  __/ (_| | |_| |  __/\__ \ |_\__ \\" . PHP_EOL;
echo " |____/|_|  |_|_| \_\___|\__, |\__,_|\___||___/\__|___/" . PHP_EOL;
echo "                            |_|                        " . PHP_EOL;
echo "" . PHP_EOL;
echo "Version: $versionClient";
echo "" . PHP_EOL;
echo "StepMania Stats.XML Scraper" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

//start logging and cleanup old logs
wh_log("Starting SMRequests v$versionClient StepMania Stats.XML Scraper...");
//

//Config
if(file_exists(__DIR__."/config.php") && is_file(__DIR__."/config.php")){
	require_once ('config.php');
}else{
	wh_log("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.");
	die("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.".PHP_EOL);
}

//////

function check_environment(){
	global $timezone;
	//check for a php.ini file
	$iniPath = php_ini_loaded_file();

	if(!$iniPath){
		//no config found
		wh_log("ERROR: A php.ini configuration file was not found. Refer to the documentation on how to configure your php envirnment for SMRequests.");
		die("A php.ini configuration file was not found. Refer to the documentation on how to configure your php envirnment for SMRequests." . PHP_EOL);
	}
	
	//check php version and dump to log
	switch(version_compare(PHP_VERSION,'7.4.26')){
		case -1:
			//version too low
			wh_log("Your PHP version is too low! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION);
			die("Your PHP version is too low! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION);
			break;
		case 1:
			//version higher than test
			if(version_compare(PHP_VERSION,'8.0.0','>=')){
				//php8 is not supported....yet
				wh_log("PHP 8 is not supported! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION);
				die("PHP 8 is not supported! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION);
			}
			//full steam ahead!	
			break;
		default:
			//versions match!
	}

	//set timezone
	if($timezone){
		if(!date_default_timezone_set($timezone)){
			wh_log("Timezone not set in config.php or invalid.");
			wh_log("Timezone set to: " . date_default_timezone_get() . ".");
		}
	}

	//config found. check for enabled extensions
	$expectedExts = array('curl','json','mbstring','SimpleXML');
	$loadedPhpExt = get_loaded_extensions();
	$missingExt = array();

	foreach ($expectedExts as $ext){
		if(!in_array($ext,$loadedPhpExt)){
			//expected extenstion not found
			$missingExt[] = $ext;
		}
	}
	if(count($missingExt) > 0){
		$ext = implode(', ',$missingExt);
		wh_log("ERROR: $ext extension(s) not enabled. Please enable the extension(s) in your PHP config file: \"$iniPath\"");
		die("$ext extension(s) not enabled. Please enable the extension(s) in your PHP config file: \"$iniPath\"" . PHP_EOL);
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

function check_target_url(){
	global $targetURL;
	global $target_url;

	if(isset($target_url) && !empty($target_url)){
		$targetURL = $target_url;
	}
	if(!isset($targetURL) || empty($targetURL)){
		die("No target URL found! Check the \"targetURL\" value in your config.php file" . PHP_EOL);
	}elseif(filter_var($targetURL,FILTER_VALIDATE_URL) === FALSE){
		die("\"$targetURL\" is not a valid URL. Check the \"targetURL\" value in your config.php file" . PHP_EOL);
	}elseif(preg_match('/(smrequests\.)(com|dev)/',$targetURL)){
		//this is a hosted domain
		if(!preg_match('/(https:\/\/.+\.smrequests\.)(com|dev)(?!\/)/',$targetURL)){
			die("\"$targetURL\" is not a valid URL for the SMRequests hosted service. Check the \"targetURL\" value in your config.php file" . PHP_EOL);
		}
	}
}

function process_profileIDs(string $profileID){
	if(!empty($profileID)){
		//split comma-separated string into an array
		$profileID = explode(',',$profileID);
		$profileID = array_map('trim',$profileID);
		//check for valid profile ID
		foreach($profileID as $id){
			if(strlen($id) != 8 && !is_numeric($id)){
				//valid profile IDs used by StepMania are 8-length numbers
				wh_log("$id is not a valid LocalProfile ID! Check your config.php configuration for profileIDs.");
				die("$id is not a valid LocalProfile ID! Check your config.php configuration for profileIDs." . PHP_EOL);
			}
		}
	}
	if(!is_array($profileID)){$profileID = array($profileID);}
	return (array)$profileID;
}

function process_USBProfileDir(string $USBProfileDir){
	global $USBProfile;

	if ($USBProfile){
		if(empty($USBProfileDir)){
			//no usb directory configured in config.php
			wh_log("USB Profiles are enabled, but no directory was configured in config.php!");
			die("USB Profiles are enabled, but no directory was configured in config.php!" . PHP_EOL);
		}
		//split comma-separated string into an array
		$USBProfileDir = explode(',',$USBProfileDir);
		$USBProfileDir = array_map('trim',$USBProfileDir);
		foreach($USBProfileDir as $dir){
			if(!file_exists($dir)){
				//failed to find the usb drive/directory
				wh_log("USB Profile directory: \"$dir\" does not exist! Check that the USB drive is inserted and the drive letter is correct.");
				die("USB Profile directory: \"$dir\" does not exist! Check that the USB drive is inserted and the drive letter is correct." . PHP_EOL);
			}
		}
	}
	if(!is_array($USBProfileDir)){$USBProfileDir = array($USBProfileDir);}
	return (array)$USBProfileDir;
}

function fixEncoding(string $line){
	//detect and convert ascii, et. al directory string to UTF-8 (Thanks, StepMania!)
	//96.69% of the time, the encoding error is in a Windows filename
	//Project OutFox Alpha 4.12 fixed most of the character encoding issues, but this function will remain for legacy support
	$encoding = mb_detect_encoding($line,'UTF-8,CP1252,ASCII,ISO-8859-1');
	$oldLine = $line;
	if($encoding != 'UTF-8'){
		wh_log( "Invalid UTF-8 detected ($encoding). Converting...");
		$line = mb_convert_encoding($line,'UTF-8',$encoding);
		wh_log("Text converted from: \"" . $oldLine . "\" to: \"" . $line . "\".");
	}elseif($encoding == FALSE || empty($encoding)){
		//encoding not detected, assuming 'ISO-8859-1', again, thanks, StepMania.
		$encoding = 'ISO-8859-1';
		wh_log("Invalid UTF-8 detected ($encoding) (fallback). Converting...");
		$line = mb_convert_encoding($line,'UTF-8',$encoding);
		wh_log("Text converted from: \"" . $oldLine . "\" to: \"" . $line . "\".");
	}
	//afer conversion we check AGAIN to confirm the new line is encoded as UTF-8
	if(!mb_check_encoding($line,'UTF-8')){
		//string still has invalid characters, give up and remove them completely
		$line = mb_convert_encoding($line,'UTF-8','UTF-8');
		wh_log("Failed additional check. UTF-8,UTF-8 converted line from: \"" . $oldLine . "\" to: \"" . $line . "\".");
	}
	return (string) $line;
}

function parseXmlErrors($errors,array $xmlArray){
	foreach ($errors as $error){
		if ($error->code == 9){
			//error code: 9 is "Invalid UTF-8 encoding detected"
			echo "Oh look! StepMania left us invalid UTF-8 characters in an XML file.".PHP_EOL;
			echo "I recommend removing all special characters from this song's directory name!".PHP_EOL;
			wh_log("Oh look! StepMania left us invalid UTF-8 characters in an XML file. I recommend removing all special characters from this song's directory name!");
			//get line number of the invalid character(s)
			$lineNo = $error->line - 1;
			//open file, fix encoding, and write a new line
			echo "Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xmlArray[$lineNo])."] Fixing (Temporarily)...".PHP_EOL;
			wh_log("Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xmlArray[$lineNo])."] Fixing (Temporarily)...");
			$xmlArray[$lineNo] = fixEncoding($xmlArray[$lineNo]);
		}elseif($error->code != 9){
			//error code is not "9"
			//other errors haven't really popped up, so here, have the raw output!
			wh_log(implode(PHP_EOL,(array) $errors)); 
			print_r($errors);
		}
	}
	//write back changes to the file in memory and save as string
	$xmlStr = implode(PHP_EOL,$xmlArray);
	return (string) $xmlStr;
}

function find_statsxml(string $saveDir, array $profileID, array $USBProfileDir){
	global $USBProfile;
	global $statsXMLfilename;
	//look for any Stats.xml files in the profile directory(ies)
	$saveDir = $saveDir . "/LocalProfiles";
	$file_arr = array();
	$i = 0;
	if(!empty($profileID)){
		foreach ($profileID as $id){
			foreach (glob($saveDir."/".$id."/".$statsXMLfilename,GLOB_BRACE) as $xml_file){
				//build array of file directory, IDs, modified file times, and set the inital timestamp to "0"
				$file_arr[$i]['id'] = $id; //id for tracking 
				$file_arr[$i]['file'] = $xml_file; //file directory
				$file_arr[$i]['ftime'] = ''; //populated later after the first scrape
				$file_arr[$i]['mtime'] = filemtime($xml_file); //current modified time of the file
				$file_arr[$i]['timestampLastPlayed'] = 0; //timestamp of the last played song from the parsed stats.xml file
				$file_arr[$i]['type'] = "local"; //type for later use?
				$i++;
			}
			if (empty($file_arr) && !$USBProfile){ //don't exit too early
				wh_log("$statsXMLfilename file(s) not found in $saveDir/$id! Also, if you are not running Stepmania in portable mode, your Stepmania Save directory may be in \"AppData\".");
				exit ("$statsXMLfilename file(s) not found in $saveDir/$id! LocalProfiles directory not found in Stepmania Save directory. Also, if you are not running Stepmania in portable mode, your Stepmania Save directory may be in \"AppData\"." . PHP_EOL);
			}
		}
	}
	if($USBProfile){
		//using usb profile(s)...
		foreach ($USBProfileDir as $dir){
			foreach (glob($dir."/".$statsXMLfilename,GLOB_BRACE) as $xml_file){
				//build array of file directory, IDs, modified file times, and set the inital timestamp to "0"
				$file_arr[$i]['id'] = $dir; //use the dir as the id for tracking
				$file_arr[$i]['file'] = $xml_file; //file directory
				$file_arr[$i]['ftime'] = ''; //populated later after the first scrape
				$file_arr[$i]['mtime'] = filemtime($xml_file); //current modified time of the file
				$file_arr[$i]['timestampLastPlayed'] = 0; //timestamp of the last played song from the parsed stats.xml file
				$file_arr[$i]['type'] = "usb"; //type for later use?
				$i++;
			}
			if (empty($file_arr)){
				wh_log("$statsXMLfilename file(s) not found on USB drive at \"$dir!\"");
				exit ("$statsXMLfilename file(s) not found on USB drive at \"$dir!\"" . PHP_EOL);
			}
		}
	}
	if (empty($file_arr)){
		wh_log("$statsXMLfilename file(s) not found!");
		exit ("$statsXMLfilename file(s) not found!" . PHP_EOL);
	}

	return (array) $file_arr;
}

function statsXMLtoArray (array $file){
	global $statsXMLfilename;
	//This is THE Stats.XML parser for StepMania. A lot of assumptions are made about the structure of the file, but considering it's generated by 
	//the game, I'm not too concerned about it breaking.

	//timestampLastPlayed will always be '0' the first-run, thus all records will be parsed from the xml file.
	//further runs will only parse the records since the last timestamp

	//create array to store xml file
	$statsLastPlayed = array();
	$statsHighScores = array();
	$stats_arr = array();

	//OutFox "steps hash" implementation was changed 3+ times so far, it can be either:
	$outFoxHash = array('StepsHash','ChartHash','Hash','OnlineHash');
	$outFoxDesc = array('Description','OnlineDescription');
	
	//open xml file
	libxml_clear_errors();
	libxml_use_internal_errors(TRUE);
	$xml_file = $file['file'];
	$xml = simplexml_load_file($xml_file);

	//check for errors with the xml file that will prevent a successful parse
	$errors = libxml_get_errors();
	if (!empty($errors)){
		//attempt to fix errors in memory then load xml (fixed) via string
		//not a great solution, but blame StepMania, not me!
		$xmlArray = file($xml_file);
		$xmlStr = parseXmlErrors($errors,$xmlArray);
		unset($xmlArray);
		$xml = FALSE;
		wh_log("Loading $statsXMLfilename file as a string (after correcting for UTF-8 errors).");
		while (!$xml){
			//php's simplexml loader, stops after the first error. We can't fix all the errors at one time.
			//as long as the $xml is FALSE, we loop one fix at a time
			libxml_clear_errors();
			$xml = simplexml_load_string($xmlStr); //switched to loading as a string instead of a file
			$errors = libxml_get_errors();
			if (!empty($errors)){
				$xmlArray = explode(PHP_EOL,$xmlStr);
				$xmlStr = parseXmlErrors($errors,$xmlArray);
				unset($xmlArray);
				$xml = FALSE;
			}
		}
	}
	unset ($xmlArray,$xmlStr,$errors); //without unsetting thses variables, we get a memory leak over time

	//die if too many errors
	if(!$xml){wh_log("Too many errors with $statsXMLfilename file."); die ("Too many errors with $statsXMLfilename file." . PHP_EOL);}

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
	$timestampLastPlayed = $file['timestampLastPlayed'];
	$profileID = $file['id'];
	$profileType = $file['type'];

	foreach ($xml->SongScores->Song as $song){
		$song_dir = (string)$song['Dir'];
		
		foreach ($song->Steps as $steps){		
			$steps_type = (string)$steps['StepsType']; //dance-single, dance-double, etc.
			$difficulty = (string)$steps['Difficulty']; //Beginner, Medium, Expert, etc.
			//$chartHash = (string)$steps['Hash']; //OutFox chart hash
			$chartHash = "";
			foreach ($outFoxHash as $hash){
				if(!empty($steps[$hash])){
					$chartHash = (string)$steps[$hash];
					break;
				}
			}
			//$stepsDescription = (string)$steps['Description']; //OutFox steps description
			$stepsDescription = "";
			foreach ($outFoxDesc as $desc){
				if(!empty($steps[$desc])){
					$stepsDescription = (string)$steps[$desc];
					break;
				}
			}
			
			foreach ($steps->HighScoreList as $high_score_lists){
				$num_played = (string)$high_score_lists->NumTimesPlayed; //integer count of times a song is played
				$last_played = (string)$high_score_lists->LastPlayed; //date the song/difficulty was last played

				$dateTimeHS = array(null);
				$highScores = array();

				foreach ($high_score_lists->HighScore as $high_score){				
					//loop through each highscore section
					$highScores[] = $high_score;
					$dateTimeHS[] = (string)$high_score->DateTime; //store a separate datetime value
				}

				//last_played date for the song isn't always the latest due to not having a time element.
				//assume that, if the most recent highscore is greater than the lasted time played date,
				//we can replace the last_played date with the date/time from the highscore
				$dateTimeMax = max($dateTimeHS);
				if (strtotime($dateTimeMax) > strtotime($last_played)){
					$last_played = $dateTimeMax;
				}
				
				if (!empty($highScores)){
					foreach ($highScores as $highScoreSingle){
						if((string)strtotime($highScoreSingle->DateTime) > strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
							//highscore date/time is greater than the stored lastPlayed timestamp, add it to the array
							$statsHighScores[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'ProfileID' => $profileID, 'ProfileType' => $profileType, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played, 'HighScore' => $highScoreSingle);
						}
					}
				}
				if(strtotime($last_played) >= strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
					//lastplayed date/time is greater than the stored lastPlayed timestamp, add it to the array
					$statsLastPlayed[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'ProfileID' => $profileID, 'ProfileType' => $profileType, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played);
					//add the last_played timestamp to an array for safe keeping
					$timestampLastPlayedArr[] = $last_played;
				}
			}
		}
	}

	if(!empty($timestampLastPlayedArr)){
		$timestampLastPlayed = max($timestampLastPlayedArr); //overwrite the lastplayed timestamp with the new (latest) value
	}
	//build the final array
	$stats_arr = array('LastPlayed' => $statsLastPlayed, 'HighScores' => $statsHighScores, 'timestampLastPlayed' => $timestampLastPlayed);

	return (array) $stats_arr; 
}

function curlPost(string $postSource, array $postData){
	global $targetURL;
	global $security_key;
	global $offlineMode;
	$return = FALSE;
	$versionClient = get_version();
	//add the security_key to the http header
	if(!isset($security_key) || empty($security_key)){
		die("No security_key found! Check the \"security_key\" value in your config.php file" . PHP_EOL);
	}
	$security_keyToken = base64_encode($security_key);
	$jsMicro = microtime(true);
	$jsonArray = array('source' => $postSource, 'version' => $versionClient, 'offline' => $offlineMode, 'data' => $postData);
	//encode array as json
	$post = json_encode($jsonArray);
	unset($postData,$jsonArray); //memory leak
	wh_log ("Creating JSON took: " . round(microtime(true) - $jsMicro,3) . " secs.");
	$errorJson = json_last_error();
	if($errorJson != "JSON_ERROR_NONE"){
		//there was an error with the json string
		wh_log(json_last_error_msg());
		die(json_last_error_msg().PHP_EOL);
	}
	//compress post data
	$post = gzencode($post,6);
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL."/status.php?$postSource");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Encoding: gzip', "Key: $security_keyToken"));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //if true, must specify cacert.pem location in php.ini
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_POST,1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$result = curl_exec ($ch);
	if($result === FALSE){
		echo "Curl error: ".curl_error($ch) . PHP_EOL;
		wh_log("Curl error: ".curl_error($ch));
	}
	if(curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400){
		echo $result; //echo from the server-side script
		wh_log($result);
		wh_log("cURL exec took: " . round(curl_getinfo($ch)['total_time_us'] / 1000000,3)." secs");
		$return = TRUE;
	}else{
		echo "There was an error communicating with $targetURL.".PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
	}
	curl_close ($ch);

	return (bool)$return;
}

//check php environment
check_environment();

//check for valid target URL
check_target_url();

//process ProfileIDs
if((empty($profileID) || $profileID == "") && !$USBProfile){
	//no profile ID(s) / USB profiles not used
	die("No LocalProfile ID specified! You must specify at least 1 profile ID in config.php." . PHP_EOL);
}
//process local profiles
$profileID = process_profileIDs($profileID);

//process USB Profiles
$USBProfileDir = process_USBProfileDir($USBProfileDir);

//find stats.xml files
$file_arr = find_statsxml ($saveDir,$profileID,$USBProfileDir);

//endless loop (the way PHP is SuPpOsEd to be used)
for (;;){

	foreach ($file_arr as &$file){ //the '&' writes back the modifications in the loop to the original file

		$file['mtime'] = filemtime($file['file']);
		if ($file['ftime'] != $file['mtime']) {
			//file has been modified. let's open it!
			echo PHP_EOL;
			$startMicro = microtime(true);
			echo "Starting scrape of profile \"".$file['id']."\"..." . PHP_EOL;
			wh_log("Starting scrape of profile \"".$file['id']."\"");
			//parse stats.xml file to an array
			$statsMicro = microtime(true);
			$stats_arr = statsXMLtoArray ($file);
			//save the last played timestamp in the $file array
			$file['timestampLastPlayed'] = $stats_arr['timestampLastPlayed'];
			wh_log ("$statsXMLfilename parse of \"" . $file['id'] . "\" took: " . round(microtime(true) - $statsMicro,3) . " secs.");
			$chunk = 1000;
			//LastPlayed
			if(($countLP = count($stats_arr['LastPlayed'])) > 0){
				$lpMicro = microtime(true);
				$countChunk = 0;
				$totalChunks = ceil($countLP / $chunk);
				$retries = 0;
				wh_log("Uploading $countLP lastplayed records.");
				foreach (array_chunk($stats_arr['LastPlayed'],$chunk,true) as $chunkArr){
					do{
						//post data via cURL, retry 3x on failure
						$curlSuccess = curlPost("lastplayed", $chunkArr);
						$retries++;
					}
					while((!$curlSuccess) && ($retries <= 3));
					$countChunk++;
					if($retries >= 3){wh_log ("POST and processing of chunk: $countChunk of LastPlayed of \"" . $file['id'] . "\" timed out after 3 retries.");}
					if($totalChunks > 1){echo("($countChunk/$totalChunks)") . PHP_EOL;}
				}
				wh_log ("POST and processing of $countChunk chunk(s) of LastPlayed of \"" . $file['id'] . "\" took: " . round(microtime(true) - $lpMicro,3) . " secs.");
			}
			//HighScores
			if(($countHS = count($stats_arr['HighScores'])) > 0){
				$hsMicro = microtime(true);
				$countChunk = 0;
				$totalChunks = ceil($countHS / $chunk);
				$retries = 0;
				wh_log("Uploading $countHS highscore records.");
				foreach (array_chunk($stats_arr['HighScores'],$chunk,true) as $chunkArr){
					do{
						//post data via cURL, retry 3x on failure
						$curlSuccess = curlPost("highscores", $chunkArr);
						$retries++;
					}
					while((!$curlSuccess) && ($retries <= 3));
					$countChunk++;
					if($retries >= 3){wh_log ("POST and processing of chunk: $countChunk of HighScores of \"" . $file['id'] . "\" timed out after 3 retries.");}
					if($totalChunks > 1){echo("($countChunk/$totalChunks)") . PHP_EOL;}
				}
				wh_log ("POST and processing of $countChunk chunk(s) of HighScores of \"" . $file['id'] . "\" took: " . round(microtime(true) - $hsMicro,3) . " secs.");
			}
			echo "Done " . PHP_EOL;
			wh_log ("Done. Scrape of \"" . $file['id'] . "\" took: " . round(microtime(true) - $startMicro,3) . " secs.");
			unset($stats_arr,$chunkArr);
		}
		$file['ftime'] = $file['mtime'];
	}

	echo "."; //what's a group of dots called?
	clearstatcache(); //file times are cached, this clears it
	sleep($frequency); //wait for # seconds
}
exit();

?>