<?php

//  ____  __  __ ____                            _       
// / ___||  \/  |  _ \ ___  __ _ _   _  ___  ___| |_ ___ 
// \___ \| |\/| | |_) / _ \/ _\`| | | |/ _ \/ __| __/ __|
//  ___) | |  | |  _ <  __/ (_| | |_| |  __/\__ \ |_\__ \
// |____/|_|  |_|_| \_\___|\__, |\__,_|\___||___/\__|___/
//                            |_|                        
////
// PHP "Song scraper" for Stepmania
// This script scrapes your Stepmania cache directory for songs and posts each unique song to a mysql database table.
// It cleans [TAGS] from the song titles and it saves a "search ready" version of each song title (without spaces or special characters) to the "strippedtitle" column.
// This way you can have another script search/parse your entire song library - for example to make song requests.
// You only need to re-run this script any time you add new songs and Stepmania has a chance to build its cache. It'll skip songs that already exist in the DB.
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

$versionClient = get_version();
$whichScript = "StepMania Song Cache Scraper";
cli_set_process_title("SMRequests v$versionClient | $whichScript");

function find_cache_files($cacheDir){
	//find cache files
	$files = array ();
	//songDir valid?
	if(empty($cacheDir) || !file_exists($cacheDir)){
		wh_log("StepMania song cache directory is empty or invalid. Check your config.php.");
		die("StepMania song cache directory is empty or invalid. Check your config.php." . PHP_EOL);
	}
	foreach(glob("$cacheDir/*", GLOB_BRACE) as $file) {
		$files[] = $file;
	}

	if(count($files) == 0){
		wh_log("No files. Songs cache directory not found in Stepmania directory. You must start Stepmania before running this software. Also, if you are not running Stepmania in portable mode, your Stepmania directory may be in \"AppData\"."); 
		die("No files. Songs cache directory not found in Stepmania directory. You must start Stepmania before running this software. Also, if you are not running Stepmania in portable mode, your Stepmania directory may be in \"AppData\"." . PHP_EOL);
	}elseif(in_array("$cacheDir/index.cache",$files)){
		//wrong cache folder
		wh_log("Invalid StepMania songs cache directory.");
		die("Invalid StepMania songs cache directory." . PHP_EOL);
	}

	return((array) $files);
}

function parseMetadata($file) {
	//parse StepMania song cache file METADATA
	//file structure looks like:
	//#TAG:value;
	//
	$file_arr = array();
	$lines = array();
	$delimiter = ":";
	$eol = ";";
	
	$data = file_get_contents($file);
	//keep only data before the #NOTEDATA section
	$data = substr($data,0,strpos($data,"//-------"));
	
	$file_arr = explode($eol,$data);
	
	foreach ($file_arr as $line){
		// if there is no $delimiter, set an empty string
			$line = trim($line);
			if (substr($line,0,1) == "#"){
				if (stripos($line,$delimiter)===FALSE){
					$key = $line;
					$value = "";
			// esle treat the line as normal with $delimiter
				}else{
					$key = substr($line,0,strpos($line,$delimiter));
					$value = substr($line,strpos($line,$delimiter)+1);
				}
				$value = fixEncoding($value);
				$value = stripslashes($value);
				$value = str_replace("\\","",$value);//sometimes sm/ssc files will have extra '\' escapes, for whatever reason
				
				//add key/value pair to array
				$lines[trim($key)] = trim($value);
			}
			
	}
	
	return (array) $lines;
}

function parseNotedata($file) {
	//parse StepMania song cache file NOTEDATA
	//everything after the metadata
	$file_arr = array();
	$lines = array();
	$delimiter = ":";
	$eol = ";";
	$notedata_array = array();
	
	$data = file_get_contents($file);

	if( strpos($data,"#NOTEDATA:") != FALSE){
		//looks like we've got some notedata, as expected
		//trim everything before the notedata
		$data = substr($data,strpos($data,"//-------"));
		$data = substr($data,strpos($data,"#"));
		
	//getting notedata info...
		$notedata_array = array();
		
			$notedata_total = substr_count($data,"#NOTEDATA:"); //how many step charts are there?
			$notedata_offset = 0;
			$notedata_next = 0;
			$notedata_count = 1;
			//start from the first occurance of notedata, set found data to array
			while ($notedata_count <= $notedata_total){ 
				$notedata_offset = strpos($data, "#NOTEDATA:",$notedata_next);
				$notedata_next = strpos($data, "#NOTEDATA:",$notedata_offset + strlen("#NOTEDATA:"));
					if ($notedata_next === FALSE){
						$notedata_next = strlen($data);
					}
				
				$data_sub = substr($data,$notedata_offset,$notedata_next-$notedata_offset);
				$file_arr = "";
				$file_arr = explode($eol,$data_sub);
				
				foreach ($file_arr as $line){
					$line = trim($line);
					//only process lines beginning with '#'
					if (substr($line,0,1) == "#"){
						// if there is no $delimiter, set an empty string
						if (stripos($line,$delimiter)===FALSE){
							$key = $line;
							$value = "";
					// esle treat the line as normal with $delimiter
						}else{
							$key = trim(substr($line,0,strpos($line,$delimiter)));
							$value = trim(substr($line,strpos($line,$delimiter)+1));
						}
						$value = fixEncoding($value);
						$value = stripslashes($value);
						$value = str_replace("\\","",$value);//sometimes sm/ssc files will have extra '\' escapes, for whatever reason

						//add key/value pair to array
						$lines[trim($key)] = trim($value);
					}	
				}
				
				//build array of notedata chart information
				
			//Not all chart files have these descriptors, so let's check if they exist to avoid notices/errors	
				array_key_exists('#CHARTNAME',$lines) 	? $lines['#CHARTNAME']	 : $lines['#CHARTNAME']   	= "";
				array_key_exists('#DESCRIPTION',$lines) ? $lines['#DESCRIPTION'] : $lines['#DESCRIPTION'] 	= "";
				array_key_exists('#CHARTSTYLE',$lines)  ? $lines['#CHARTSTYLE']	 : $lines['#CHARTSTYLE']  	= "";
				array_key_exists('#CREDIT',$lines)      ? $lines['#CREDIT']    	 : $lines['#CREDIT']      	= "";
				array_key_exists('#CHARTHASH',$lines)   ? $lines['#CHARTHASH']   : $lines['#CHARTHASH']     = "";
				array_key_exists('#DISPLAYBPM',$lines)  ? $lines['#DISPLAYBPM']  : $lines['#DISPLAYBPM']    = "";
				
				if( strpos($lines['#DISPLAYBPM'],':') > 0){
					//deal with split bpm values
					$display_bpmSplit = explode($delimiter,$lines['#DISPLAYBPM']);
					$lines['#DISPLAYBPM'] = intval(round(floatval(min($display_bpmSplit)),0)) . "-" . intval(round(floatval(max($display_bpmSplit)),0));
				}else{
					$lines['#DISPLAYBPM'] = intval(round(floatval($lines['#DISPLAYBPM']),0));
				}
								
				$notedata_array[] = array('chartname' => $lines['#CHARTNAME'], 'stepstype' => $lines['#STEPSTYPE'], 'description' => $lines['#DESCRIPTION'], 'chartstyle' => $lines['#CHARTSTYLE'], 'charthash' => $lines['#CHARTHASH'], 'difficulty' => $lines['#DIFFICULTY'], 'meter' => $lines['#METER'], 'radarvalues' => $lines['#RADARVALUES'], 'credit' => $lines['#CREDIT'], 'displaybpm' => $lines['#DISPLAYBPM'], 'stepfilename' => $lines['#STEPFILENAME']);

				$notedata_count++;
			}
	}
	
	return (array) $notedata_array;
}

function prepareCacheFiles(array $filesArr){
	//sort files by last modified date
	echo "Sorting cache files by modified date..." . PHP_EOL;
	wh_log("Sorting cache files by modified date...");
	$micros = microtime(true);
	usort( $filesArr, function( $a, $b ) { return filemtime($b) - filemtime($a); } );
	wh_log ("Sort time: ".round(microtime(true) - $micros,3)." secs." . PHP_EOL);

	return (array) $filesArr;
}

function isIgnoredPack(string $songFilename){
	global $packsIgnore;
	global $packsIgnoreRegex;

	$return = FALSE;
	if(!empty($songFilename)){
		//song has a an associated simfile
		$songFilename = fixEncoding($songFilename);
		$song_dir = substr($songFilename,1,strrpos($songFilename,"/")-1); //remove benginning slash and file extension

		//Get pack name
		$pack = substr($song_dir, 0, strripos($song_dir, "/"));
		$pack = substr($pack, strripos($pack, "/")+1);
		//if the pack is on ignore list, skip it
		if(!is_array($packsIgnore)){
			$packsIgnore = array($packsIgnore);
		}
		if (in_array($pack,$packsIgnore)){
			$return = TRUE;
		}elseif(!empty($packsIgnoreRegex)){
			if(preg_match($packsIgnoreRegex,$pack)){
				$return = TRUE;
			}
		}
	}
	return (bool) $return;
}

function doesFileExist(string $songFilename){
	global $songsDir;
	global $offlineMode;
	global $addSongsDir;

	//if offline mode is set, always return TRUE
	if($offlineMode){
		$return = TRUE;
		return $return;
	}

	$return = FALSE;

	//songDir valid?
	if(empty($songsDir) || !is_dir($songsDir)){
		wh_log("StepMania song directory is empty or invalid. Check your config.php.".PHP_EOL);
		die("StepMania song directory is empty or invalid. Check your config.php.");
	}

	//fix possible character encoding
	//convert string to UTF-8 then back to ISO-8859-1 so Windows can understand it
	$songFilenameOriginal = $songFilename;
	$songFilename = fixEncoding($songFilename);
	if($songFilenameOriginal <> $songFilename){
		echo "Song filename contains invalid character encodings. Check log for details." . PHP_EOL;
		wh_log("Song filename contains invalid character encodings:" . PHP_EOL . "$songFilenameOriginal changed to $songFilename");
	}

	//check if the chart file exists on the filesystem
	if(preg_match('/^\/Songs\//',$songFilename)){
		//file is in the normal "Songs" folder
		if(is_array($songsDir)){
			//incorrectly configured as an array. Use only the first directory.
			$songsDir = $songsDir[0];
			wh_log("StepMania supports *only* a single 'Songs' folder. Using the first directory in the array: \"".$songsDir."\"");
		}
		$songFilenameAbs = preg_replace('/^\/Songs\//',$songsDir."/",$songFilename);
		if(file_exists($songFilenameAbs)){
			$return = TRUE;
		}else{
			//try converting back to ISO-8859-1. Maybe there is a non-UTF-8 character found in a Windows filename?
			//$songFilenameAbs = utf8_decode($songFilenameAbs);
			$songFilenameAbs = mb_convert_encoding($songFilenameAbs,'ISO-8859-1','UTF-8');
			if(file_exists($songFilenameAbs)){
				$return = TRUE;
			}else{
				wh_log("'/Songs/' File Not Found: ".$songFilenameAbs);
			}
		}
	}elseif(preg_match('/^\/AdditionalSongs\//',$songFilename)){
		//file is in one of the "AdditionalSongs" folder(s)
		if(empty($addSongsDir)){
			//AdditionalSongsFolder is missing in config file. Exit.
			wh_log("It appears you are using an \"AdditionalSongsFolder\" and it was not specified in the configuration file! Please add the folder(s) to the config.php file.");
			die("It appears you are using an \"AdditionalSongsFolder\" and it was not specified in the configuration file! Please add the folder(s) to the config.php file.".PHP_EOL);
		}

		if(!is_array($addSongsDir)){
			$addSongsDir = array($addSongsDir);
		}
		foreach($addSongsDir as $dir){
			//loop through the "AdditionalSongsFolders"
			$songFilenameAbs = preg_replace('/^\/AdditionalSongs\//',$dir."/",$songFilename);
			if(file_exists($songFilenameAbs)){
				$return = TRUE;
				break;
			}else{
				//try converting back to ISO-8859-1. Maybe there is a non-UTF-8 character found in a Windows filename?
				//$songFilenameAbs = utf8_decode($songFilenameAbs);
				$songFilenameAbs = mb_convert_encoding($songFilenameAbs,'ISO-8859-1','UTF-8');
				if(file_exists($songFilenameAbs)){
					$return = TRUE;
					break;
				}else{
					//wh_log("File Not Found: ".$songFilename);
				}
			}
		}
		//looped through all AdditionSongs folders
		if(!$return){
			//did not find file in ANY folder
			wh_log("'/AdditionalSongs/' File Not Found: ".$songFilenameAbs);
		}
	}else{
		wh_log("Something is wrong with the song cache files. Songs must either be in '/Songs/' or '/AdditionalSongs/'");
		die("Something is wrong with the song cache files. Songs must either be in '/Songs/' or '/AdditionalSongs/'" . PHP_EOL);
	}

	return (bool) $return;

}

function prepare_for_scraping(){
	//prepare sm_songs database for scraping, check if this is a first-run, grab compare array, and version check
	echo "Preparing database for song scraping..." . PHP_EOL;
	wh_log("Preparing database for song scraping...");

	$songsStart = curlPost("songsStart",array(0));

	return (bool) $songsStart;
}

function get_progress($timeChunkStart, $currentChunk, $totalChunks, array $chunkTimes){
	$progress = array();

	$timeNow = microtime(true);
	$elapsedTime = $timeNow - $timeChunkStart;

	$chunkTimes[] = $elapsedTime;
	$chunksRemain = $totalChunks - $currentChunk;
	$percentChunk = round (($currentChunk / $totalChunks) * 100, 0); //"integer" percent

	$avgTimePerChunk = array_sum($chunkTimes) / count($chunkTimes);
	$timeRemain = $avgTimePerChunk * $chunksRemain; //seconds

	if($timeRemain > 60){
		$timeUnit = "mins";
		$timeRemain = round ($timeRemain / 60, 1); //minutes
	}elseif($timeRemain <= 60){
		$timeUnit = "secs";
		$timeRemain = round ($timeRemain, 0); //seconds
	}

	$progress = array('percent' => $percentChunk, 'time' => $timeRemain, 'unit' => $timeUnit, 'chunktimes' => $chunkTimes);

	return (array) $progress;
}

// show welcome message
show_welcome_message($versionClient,$whichScript);

//start logging and cleanup old logs
wh_log("Starting SMRequests v$versionClient $whichScript...");
wh_log_purge();
//

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

//get start time
$microStart = microtime(true);

//check php environment setup
check_environment();

//check for valid target URL
check_target_url();

$i = 0;
$chunk = 573; //69 and 420 were too small

$firstRun = prepare_for_scraping();

// find cache files
$files = find_cache_files($cacheDir);

//loop through cache files, process to json strings, and post to the webserver for further processing
$totalFiles = count($files);
echo "Looping through ".$totalFiles." cache files..." . PHP_EOL;
wh_log("Looping through ".$totalFiles." cache files...");
$totalChunks = ceil($totalFiles / $chunk);
$currentChunk = 1;
$chunkTimes = array(); //array of elapsed times for each chunk

if ($firstRun != TRUE){
	//only sort files if NOT first run
	$files = prepareCacheFiles($files);
}

$files = array_chunk($files,$chunk,true);
foreach ($files as $filesChunk){
	unset($cache_array); //unset or get memory leaks
	$timeChunkStart = microtime(true); //get start time of this chunk of files
	foreach ($filesChunk as $file){	
		//get md5 hash of file to determine if there are any updates
		$file_hash = md5_file($file);
		//get metadata of file
		$metadata = parseMetadata($file);
		$metadata['file_hash'] = $file_hash;
		$metadata['file'] = fixEncoding(basename($file));
		$notedata_array = parseNotedata($file);
		//sanity on the file, if no filename or notedata, ignore
		if (!isset($metadata['#SONGFILENAME']) && empty($metadata['#SONGFILENAME']) && empty($notedata_array)){
			//check if this file is in an ignored pack and that the chart file exists
			echo "There was an error with: [".$metadata['file']."]. No chartfile or NOTEDATA found! Skipping..." . PHP_EOL;
			wh_log("There was an error with: [".$metadata['file']."]. No chartfile or NOTEDATA found! Skipping...");
			continue;
		}

		if (isIgnoredPack($metadata['#SONGFILENAME'])){
			//song is in an ignored pack
			echo $metadata['file']." is in an Ignored Pack. Skipping..." . PHP_EOL;
			wh_log($metadata['file']." is in an Ignored Pack. Skipping...");
			continue;
		}

		if (!doesFileExist($metadata['#SONGFILENAME'])){
			//song sm/ssc file was not found
			echo $metadata['file']." original chart file is missing! Skipping..." . PHP_EOL;
			wh_log($metadata['file']." original chart file is missing! Skipping...");
			continue;
		}

		//everything checks out for this cache file
		$cache_file = array('metadata' => $metadata, 'notedata' => $notedata_array);
		$cache_array[] = $cache_file;
		unset($metadata, $notedata_array, $cache_file); //clear unneeded variables, for memory, etc.
		$i++;
	}
	echo "Sending ".$currentChunk." of ".$totalChunks." chunk(s) to SMRequests..." . PHP_EOL;
	wh_log("Sending ".$currentChunk." of ".$totalChunks." chunk(s) to SMRequests...");
	if(!empty($cache_array)){
		curlPost("songs", $cache_array);
	}
	//show progress of file chunks
	$progress = get_progress($timeChunkStart,$currentChunk,$totalChunks,$chunkTimes);
	echo $progress['percent'] . "% Complete  |  " . $progress['time'] . " " . $progress['unit'] . " remaining..." . PHP_EOL;
	wh_log ($progress['percent'] . "% Complete  |  " . $progress['time'] . " " . $progress['unit'] . " remaining...");
	$chunkTimes = $progress['chunktimes'];

	$currentChunk++;
}

//mark songs as (not)installed
echo "Finishing up..." . PHP_EOL;
wh_log("Finishing up...");
if($i > 0){
	curlPost("songsEnd",array($i));
}else{
	echo "No songs scraped!" . PHP_EOL;
	wh_log("No songs scraped!");
}

//display time
echo (PHP_EOL . "Total time: ". round((microtime(true) - $microStart)/60,1) . " mins." . PHP_EOL);
wh_log("Total time: ". round((microtime(true) - $microStart)/60,1) . " mins.");

exit();
?>
