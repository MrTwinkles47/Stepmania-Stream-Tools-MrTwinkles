<?php
//////
// SMRequests
// functions.php
//
// contains all common functions for local client scripts
//////

function show_welcome_message(string $versionClient, string $whichScript){
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
    echo $whichScript . PHP_EOL;
    echo "*********************************************************" . PHP_EOL;
    echo "" . PHP_EOL;
}

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
	switch(version_compare(PHP_VERSION,'7.4.33')){
		case 0:
			//version equal
			break;
		case -1:
			//version too low
			wh_log("Your PHP version is too low! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION);
			die("Your PHP version is too low! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION . PHP_EOL);
			break;
		case 1:
			//version higher than test
			switch(version_compare(PHP_VERSION,'8.0.0')){
				//php 8 support is in beta
				case 0:
					//version equal
				case -1:
					//version lower
					//case for some PHP 7.4 version greater than 7.4.33
					break;
				case 1:
					//version higher
					if(version_compare(PHP_VERSION, '8.3.0','<')){
						wh_log("PHP 8 support is in BETA! Please install the latest version of PHP 8.3. Your version is: " . PHP_VERSION);
						die("PHP 8 support is in BETA! Please install the latest version of PHP 8.3. Your version is: " . PHP_VERSION . PHP_EOL);
					}else{
						wh_log("PHP 8 support is in BETA! Please report any bugs!");
						echo("PHP 8 support is in BETA! Please report any bugs!" . PHP_EOL);
					}
					break;
			}
			break;
	}

	//set timezone
	if(!empty($timezone)){
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
		die("No target URL found! Check the \"targetURL\" value in your config.php file." . PHP_EOL);
	}elseif(filter_var($targetURL,FILTER_VALIDATE_URL) === FALSE){
		die("\"$targetURL\" is not a valid URL. Check the \"targetURL\" value in your config.php file." . PHP_EOL);
	}elseif(preg_match('/(smrequests\.)(com|dev)/',$targetURL)){
		//this is a hosted domain
		if(!preg_match('/(https:\/\/.+\.smrequests\.)(com|dev)(?!\/)/',$targetURL)){
			die("\"$targetURL\" is not a valid URL for the SMRequests hosted service. Check the \"targetURL\" value in your config.php file." . PHP_EOL);
		}
	}

	//reach out to URL to validate a connection
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
	$retries = 0;
	do{
		if($retries > 0){
			echo("Failed to connect to $targetURL  Retrying... ($retries)" . PHP_EOL);
			sleep(3);
		}
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$retries++;
	} while ( ($retcode != 200) && $retries <= 10);
	curl_close($ch);
	unset($ch);

	if($retcode != 200){
		die("Maximum retries attempting to reach $targetURL" . PHP_EOL);
	}
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

function parseJsonErrors(string $error, array $jsonArray){
	if($error == JSON_ERROR_UTF8){
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
	global $offlineMode;
	$return = FALSE;
	$jsTime = microtime(TRUE);
	$versionClient = get_version();
	//add the security_key to the http header
	if(!isset($security_key) || empty($security_key)){
		die("No security_key found! Check the \"security_key\" value in your config.php file" . PHP_EOL);
	}
	$security_keyToken = base64_encode($security_key);
	$jsonArray = array('source' => $postSource, 'version' => $versionClient, 'offline' => $offlineMode, 'data' => $postData);
	//encode array as json
	$post = json_encode($jsonArray);
	$errorJson = json_last_error();
	if($errorJson != JSON_ERROR_NONE){
		//there was an error with the json string
		parseJsonErrors($errorJson,$jsonArray);
		die();
	}
	wh_log ("Creating JSON took: " . round(microtime(true) - $jsTime,3) . " secs.");
	unset($postData,$jsonArray); //memory leak
	//compress post data
	$post = gzencode($post,6);
	//build cURL
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL."/status.php?$postSource");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Encoding: gzip', "Key: $security_keyToken"));
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
	curl_setopt($ch, CURLOPT_POST, TRUE); 
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
		wh_log("cURL total time: " . curl_getinfo($ch, CURLINFO_TOTAL_TIME) . " secs");
		$return = TRUE;
	}else{
		echo "There was an error communicating with $targetURL.".PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
	}
	curl_close ($ch);
	unset($ch);

	return (bool)$return;
}

?>