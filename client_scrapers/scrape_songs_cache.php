<?php

// PHP "Song scraper" for Stepmania
// This script scrapes your Stepmania cache directory for songs and posts each unique song to a mysql database table.
// It cleans [TAGS] from the song titles and it saves a "search ready" version of each song title (without spaces or special characters) to the "strippedtitle" column.
// This way you can have another script search/parse your entire song library - for example to make song requests.
// You only need to re-run this script any time you add new songs and Stepmania has a chance to build its cache. It'll skip songs that already exist in the DB.
// The same exact song title is allowed to exist in different packs.

// Configuration

if (php_sapi_name() != "cli") {
	// Not in cli-mode
	die("Only support cli mode.");
}
// In cli-mode
$versionClient = get_version();
cli_set_process_title("SMRequests v$versionClient | StepMania Song Cache Scraper");
    
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
echo "StepMania Song Cache Scraper" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

//start logging and cleanup old logs
wh_log("Starting SMRequests v$versionClient Song Cache Scraper...");
wh_log_purge();
//

if(!file_exists(__DIR__."/config.php") && !is_file(__DIR__."/config.php")){
	wh_log("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.");
	die("config.php file not found! You must configure these scripts before running. You can find an example config.php file at config.example.php.".PHP_EOL);
}else{
	require_once ('config.php');
	if(CONFIG_VERSION != $versionClient || empty(CONFIG_VERSION) || empty($versionClient)){
		wh_log("config.php file is from a previous version! You must build a new config.php from the current config.example.php file. Exiting...");
		die("config.php file is from a previous version! You must build a new config.php from the current config.example.php file. Exiting...".PHP_EOL);
	}
}

// Code

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

function wh_log_purge(){
	//clean up old logs in /log older than 6 months
	//FIXME: timezones in Windows?
	$log_folder = __DIR__."/log";
    if (!file_exists($log_folder)){
		//no log folder, exit
		return;
	}
	//find all log files older than 6 months
	$fileSystemIterator = new FilesystemIterator($log_folder);
	$now = time();
	$countPurgedLogs = 0;
	foreach ($fileSystemIterator as $file) {
		$filename = $file->getFilename();
    	if (($file->isFile()) && ($now - $file->getMTime() > 6 * 30 * 24 * 60 * 60) && preg_match('/^log_.+/i',$filename)) { // 6 months
        	//file is a log file older than 6 months
			unlink($log_folder."/".$filename);
			$countPurgedLogs++;
		}
	}
	wh_log("Purged $countPurgedLogs log files.");
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

function fixEncoding(string $line){
	//detect and convert ascii, et. al directory string to UTF-8 (Thanks, StepMania!)
	//96.69% of the time, the encoding error is in a Windows filename
	//Project OutFox Alpha 4.12 fixed most of the character encoding issues, but this function will remain for legacy support
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
	return (string) $line;
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
					$lines['#DISPLAYBPM'] = intval(round(min($display_bpmSplit),0)) . "-" . intval(round(max($display_bpmSplit),0));
				}else{
					$lines['#DISPLAYBPM'] = intval(round($lines['#DISPLAYBPM'],0));
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
			$songFilenameAbs = mb_convert_encoding('ISO-8859-1','UTF-8');
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
				$songFilenameAbs = mb_convert_encoding('ISO-8859-1','UTF-8');
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

function parseJsonErrors(string $error, array $jsonArray){
	if($error == "JSON_ERROR_UTF8" || $error == 5){
		//json error because of bad utf-8
		echo json_last_error_msg().PHP_EOL;
		echo "One of these files has an error. Correct the special character in the song folder name and re-run the script.".PHP_EOL;
		wh_log("One of these files has an error. Correct the special character in the song folder name and re-run the script.");
		foreach($jsonArray['data'] as $cacheFile){
			$songFilename = $cacheFile['metadata']['#SONGFILENAME'];
			foreach($cacheFile['metadata'] as $metaDataLine){
				if(!json_encode($metaDataLine)){
					//specific error line found
					echo("json encoding error for song $songFilename at the following line: $metaDataLine" . PHP_EOL);
					wh_log("json encoding error for song $songFilename at the following line: $metaDataLine");
				}
			}
		}
		die();
	}else{
		wh_log("Json encode error: " . json_last_error_msg());
		die("Json encode error: " . json_last_error_msg() . PHP_EOL . " Exiting." . PHP_EOL);
	}
}

function curlPost(string $postSource, array $postData){
	global $targetURL;
	global $security_key;
	$versionClient = get_version();
	//add the security_key to the http header
	if(!isset($security_key) || empty($security_key)){
		die("No security_key found! Check the \"security_key\" value in your config.php file" . PHP_EOL);
	}
	$security_keyToken = base64_encode($security_key);
	$jsonArray = array('source' => $postSource, 'version' => $versionClient, 'data' => $postData);
	//encode array as json
	$post = json_encode($jsonArray);
	$errorJson = json_last_error();
	if($errorJson != "JSON_ERROR_NONE"){
		//there was an error with the json string
		parseJsonErrors($errorJson,$jsonArray);
		die();
	}
	unset($postData,$jsonArray);
	//compress post data
	$post = gzencode($post,6);
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL."/status.php?$postSource");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Encoding: gzip', "Key: $security_keyToken"));
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //if true, must specify cacert.pem location in php.ini
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
		//echo (curl_getinfo($ch, CURLINFO_TOTAL_TIME) . " secs." . PHP_EOL);
		wh_log(curl_getinfo($ch, CURLINFO_TOTAL_TIME) . " secs");
	}else{
		echo "There was an error communicating with $targetURL.".PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
	}
	curl_close ($ch);

	return $result;
}

//get start time
$microStart = microtime(true);

//check php environment setup
check_environment();

//check for valid target URL
check_target_url();

//find cache files
$files = array ();
foreach(glob("{$cacheDir}/*", GLOB_BRACE) as $file) {
    $files[] = $file;
}

if(count($files) == 0){
	wh_log("No files. Songs cache directory not found in Stepmania directory. You must start Stepmania before running this software. Also, if you are not running Stepmania in portable mode, your Stepmania directory may be in \"AppData\"."); 
	die("No files. Songs cache directory not found in Stepmania directory. You must start Stepmania before running this software. Also, if you are not running Stepmania in portable mode, your Stepmania directory may be in \"AppData\".");
}

$i = 0;
$chunk = 573; //69 and 420 were too small

$firstRun = prepare_for_scraping();

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
	unset($cache_array,$cache_file,$metadata,$notedata_array); //unset or get memory leaks
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

//

// Let's clean up the sm_songs db, removing records that are not installed, have never been requested, never played, or don't have a recorded score
	//echo "Purging song database and cleaning up...";
	//$sql_purge = "DELETE FROM sm_songs 
	//			WHERE NOT EXISTS(SELECT NULL FROM sm_requests WHERE sm_requests.song_id = sm_songs.id LIMIT 1) AND NOT EXISTS (SELECT NULL FROM sm_scores WHERE sm_scores.song_id = sm_songs.id LIMIT 1) AND NOT EXISTS (SELECT NULL FROM sm_songsplayed WHERE sm_songsplayed.song_id = sm_songs.id LIMIT 1) AND sm_songs.installed<>1";
	//if (!mysqli_query($conn, $sql_purge)) {
	//		echo "Error: " . $sql_purge . "\n" . mysqli_error($conn);
	//	}

//
exit();

?>
