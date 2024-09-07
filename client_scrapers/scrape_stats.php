<?php

//  ____  __  __ ____                            _       
// / ___||  \/  |  _ \ ___  __ _ _   _  ___  ___| |_ ___ 
// \___ \| |\/| | |_) / _ \/ _\`| | | |/ _ \/ __| __/ __|
//  ___) | |  | |  _ <  __/ (_| | |_| |  __/\__ \ |_\__ \
// |____/|_|  |_|_| \_\___|\__, |\__,_|\___||___/\__|___/
//                            |_|                        
////
// SM5 Stats.xml scraper
// The scraper will not run with out specifying at least one profile ID in config.php! 
// Run this script in the background while you play. Each time the Stats.xml file changes,
// SMR will recieve the data it needs to auto-complete requests.
////

if (php_sapi_name() != "cli") {
	// Not in cli-mode
	die("Only support cli mode.");
}
// In cli-mode

// include functions.php file
if(is_readable(__DIR__."/functions.php") && !is_dir(__DIR__."/functions.php")){
	require_once('functions.php');
}else{
	die("functions.php file not found!".PHP_EOL);
}

//process command arguments
$whichScript = "StepMania Stats.XML Scraper";
$frequency = 5;
$fileTime = "";
$statsXMLfilename = "Stats.xml";

$versionClient = get_version();
cli_set_process_title("SMRequests v$versionClient | $whichScript");

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
			if(!is_dir($dir)){
				//failed to find the usb drive/directory
				wh_log("USB Profile directory: \"$dir\" does not exist! Check that the USB drive is inserted and the drive letter is correct.");
				die("USB Profile directory: \"$dir\" does not exist! Check that the USB drive is inserted and the drive letter is correct." . PHP_EOL);
			}
		}
	}
	if(!is_array($USBProfileDir)){$USBProfileDir = array($USBProfileDir);}
	return (array)$USBProfileDir;
}

function parseXmlErrors($errors, string $xml_file){
	global $statsXMLfilename;
	//open xml as generic file as an array
	$xmlArray = file($xml_file);
	foreach ($errors as $error){
		if ($error->code == 9){
			//error code: 9 is "Invalid UTF-8 encoding detected"
			echo "Oh look! StepMania left us invalid UTF-8 characters in an XML file.".PHP_EOL;
			echo "I recommend removing all special characters from this song's directory name!".PHP_EOL;
			wh_log("Oh look! StepMania left us invalid UTF-8 characters in an XML file. I recommend removing all special characters from this song's directory name!");
			//get line number of the invalid character(s)
			$lineNo = $error->line - 1;
			//fix encoding, and write a new line
			echo "Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xmlArray[$lineNo])."] Fixing (Temporarily)...".PHP_EOL;
			wh_log("Line ".$lineNo.": [".str_replace(array("\n","\r"),'',$xmlArray[$lineNo])."] Fixing (Temporarily)...");
			$xmlArray[$lineNo] = fixEncoding($xmlArray[$lineNo]);
		}elseif($error->code != 9){
			//error code is not "9"
			//other errors haven't really popped up, so here, have the raw output!
			wh_log(implode(PHP_EOL,(array) $errors)); 
			print_r($errors);
			die();
		}
	}
	//write back changes to a new file
	//$xmlStr = implode(PHP_EOL,$xmlArray);
	$xmlFileFixed = str_replace($statsXMLfilename,$statsXMLfilename . ".fixed",$xml_file);
	if(file_exists($xmlFileFixed) && !is_dir($xmlFileFixed)){
		//delete existing file
		if(!unlink($xmlFileFixed)){
			wh_log("Failed to delete existing \"fixed\" Stats XML file. Maybe a permissions error?");
		}
	}
	$filesizeXmlOrg = filesize($xml_file);
	$writtenBytes = file_put_contents($xmlFileFixed,implode('',$xmlArray));
	if($writtenBytes === FALSE || ($writtenBytes > $filesizeXmlOrg * 1.1 || $writtenBytes < $filesizeXmlOrg * 0.9)){
		//failed to write file or written file is > +- 10% the size of the org
		wh_log("Failed to write temporary Stats XML file after correcting for UTF-8 errors.");
		die("Failed to write temporary Stats XML file after correcting for UTF-8 errors.".PHP_EOL);
	}

	return (string) $xmlFileFixed;
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

	//Stepmania & OutFox "steps hash" implementation was changed 3+ times so far, it can be either:
	$stepsHash = array('StepsHash','ChartHash','Hash','OnlineHash');
	$stepsDesc = array('Description','OnlineDescription');
	
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
		$xml = FALSE;
		wh_log("Loading temp fixed $statsXMLfilename file after correcting for UTF-8 errors.");
		while (!$xml){
			$xmlFileFixed = parseXmlErrors($errors,$xml_file);
			//php's simplexml loader, stops after the first error. We can't fix all the errors at one time.
			//as long as the $xml is FALSE, we loop one fix at a time
			libxml_clear_errors();
			$xml = simplexml_load_file($xmlFileFixed); //switched to loading the *hopefully* fixed temp file
			$errors = libxml_get_errors();
			if (!empty($errors)){
				$xml = FALSE;
			}
		}
	}
	//unset ($xmlArray,$xmlStr,$errors); //without unsetting thses variables, we get a memory leak over time

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
			$stepsType = (string)$steps['StepsType']; //dance-single, dance-double, etc.
			$difficulty = (string)$steps['Difficulty']; //Beginner, Medium, Expert, etc.
			$chartHash = "";
			foreach ($stepsHash as $hash){
				if(!empty($steps[$hash])){
					$chartHash = (string)$steps[$hash];
					break;
				}
			}
			$stepsDescription = "";
			foreach ($stepsDesc as $desc){
				if(!empty($steps[$desc])){
					$stepsDescription = (string)$steps[$desc];
					break;
				}
			}
			
			foreach ($steps->HighScoreList as $high_score_lists){
				$num_played = (string)$high_score_lists->NumTimesPlayed; //integer count of times a song is played
				$last_played = (string)$high_score_lists->LastPlayed; //date the song/difficulty was last played

				$dateTimeHS = array();
				$highScores = array();

				foreach ($high_score_lists->HighScore as $high_score){				
					//loop through each highscore section
					$highScores[] = $high_score;
					$dateTimeHS[] = (string)$high_score->DateTime; //store a separate datetime value
				}

				//last_played date for the song isn't always the latest due to not having a time element.
				//assume that, if the most recent highscore is greater than the lasted time played date,
				//we can replace the last_played date with the date/time from the highscore
				if(!empty($dateTimeHS)){
					$dateTimeMax = max($dateTimeHS);
					if (strtotime($dateTimeMax) > strtotime($last_played)){
						$last_played = $dateTimeMax;
					}
				}
				
				if (!empty($highScores)){
					foreach ($highScores as $highScoreSingle){
						if(strtotime($highScoreSingle->DateTime) > strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
							//highscore date/time is greater than the stored lastPlayed timestamp, add it to the array
							$statsHighScores[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'ProfileID' => $profileID, 'ProfileType' => $profileType, 'SongDir' => $song_dir, 'StepsType' => $stepsType, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played, 'HighScore' => $highScoreSingle);
						}
					}
				}
				if(strtotime($last_played) >= strtotime(date("Y-m-j",strtotime($timestampLastPlayed)))){
					//lastplayed date/time is greater than the stored lastPlayed timestamp, add it to the array
					$statsLastPlayed[] = array('DisplayName' => $display_name, 'PlayerGuid' => $playerGuid, 'ProfileID' => $profileID, 'ProfileType' => $profileType, 'SongDir' => $song_dir, 'StepsType' => $stepsType, 'Difficulty' => $difficulty, 'ChartHash' => $chartHash, 'StepsDescription' => $stepsDescription, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played);
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

// show welcome message
show_welcome_message($versionClient,$whichScript);

//start logging and cleanup old logs
wh_log("Starting SMRequests v$versionClient $whichScript...");
wh_log_purge();

// include config.php file
if(is_readable(__DIR__."/config.php") && !is_dir(__DIR__."/config.php")){
	require_once('config.php');
	//check version of config.php
	if(CONFIG_VERSION != $versionClient || empty(CONFIG_VERSION) || empty($versionClient)){
		wh_log("config.php file is from a previous version! You must build a new config.php from the current config.example.php file. Exiting...");
		die("config.php file is from a previous version! You must build a new config.php from the current config.example.php file. Exiting...".PHP_EOL);
	}
}else{
	// config.php not found
	wh_log("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.");
	die("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.".PHP_EOL);
}

//check php environment
check_environment();

//check for valid target URL
check_target_url();

//process ProfileIDs
if((empty($profileID) || $profileID == "") && !$USBProfile){
	//no profile ID(s) / USB profiles not used
	wh_log("No LocalProfile ID specified! You must specify at least 1 profile ID in config.php.");
	die("No LocalProfile ID specified! You must specify at least 1 profile ID in config.php." . PHP_EOL);
}
//process local profiles
$profileID = process_profileIDs($profileID);

//process USB Profiles
$USBProfileDir = process_USBProfileDir($USBProfileDir);

//find stats.xml files
//saveDir valid?
if(empty($saveDir) || !is_dir($saveDir)){
	wh_log("StepMania /Save directory is empty or invalid. Check your config.php.");
	die("StepMania /Save directory is empty or invalid. Check your config.php." . PHP_EOL);
}
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

	clearstatcache(); //file times are cached, this clears it
	//sleep($frequency); //wait for # seconds
	for($y=0; $y<=$frequency; $y++){
		sleep(1);
		echo "."; //what's a group of dots called?
	}
	echo "\33[2K\r"; // clears current line and moves cursor to beginning
}
exit();

?>