<?php

//  ____  __  __ ____                            _       
// / ___||  \/  |  _ \ ___  __ _ _   _  ___  ___| |_ ___ 
// \___ \| |\/| | |_) / _ \/ _\`| | | |/ _ \/ __| __/ __|
//  ___) | |  | |  _ <  __/ (_| | |_| |  __/\__ \ |_\__ \
// |____/|_|  |_|_| \_\___|\__, |\__,_|\___||___/\__|___/
//                            |_|                        
////
// PHP StepMania song pack banner uploader
// This script finds each banner image for each song group/pack and uploads it to SMR.
// 'file_uploads' must be enabled on the server for this script to work correctly
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
$whichScript = "StepMania Song Pack Banner Uploader";
cli_set_process_title("SMRequests v$versionClient | $whichScript");

function findFiles(string $directory) {
	//find all directories in a directory and sort by modified time
    $dir_paths = array ();
	foreach(glob("$directory/*", GLOB_ONLYDIR) as $filename) {
        $dir_paths[] = $filename;
	}
	usort( $dir_paths, function( $a, $b ) { return filemtime($b) - filemtime($a); } );
    
	return (array)$dir_paths;
}

function add_additional_songs(array $packDirs){
	//add any additional songs folder(s)
	global $addSongsDir;

	if(empty($addSongsDir)){
		return((array) $packDirs);
	}
	
	if(!is_array($addSongsDir)){
		$addSongsDir = array($addSongsDir);
	}

	foreach($addSongsDir as $directory){
		if(is_dir($directory)){
			$packDirs[] = findFiles($directory);
		}else{
			wh_log("Additional songs directory: \"$directory\" does not exist. Skipping...");
		}
	}

	return((array) $packDirs);
}

function isIgnoredPack($pack){
	global $packsIgnore;
	global $packsIgnoreRegex;

	$return = FALSE;
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
	return $return;
}

function get_banner($img_path){
	//look for banners, reject known not banners
	foreach($img_path as $img){
		$filename = pathinfo($img,PATHINFO_FILENAME);
		if(stripos($filename,'banner') !== FALSE){
			$return = $img;
			break;
		}elseif(stripos($filename,'bn') !== FALSE){
			$return = $img;
			break;
		}elseif(stripos($filename,'ban') !== FALSE){
			$return = $img;
			break;
		}elseif(stripos($filename,'jacket') !== FALSE){
			continue;
		}elseif(stripos($filename,'cdtitle') !== FALSE){
			continue;
		}else{
			$return = $img;
		}
	}
	return $return;
}

/* function does_banner_exist($file,$pack_name){
	//quick check to see if the banner is on the server
	global $targetURL;
	$return = FALSE;
	unset($ch);

	$imgName = urlencode($pack_name.'.'.strtolower(pathinfo($file,PATHINFO_EXTENSION)));
	$ch = curl_init($targetURL."/images/packs/".$imgName);
	curl_setopt($ch, CURLOPT_NOBODY, TRUE);
	curl_exec($ch);
	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($retcode == 200){$return = TRUE;}
	return $return;
} */

function clean_filename(string $filename){
	//Trim
	$filename = trim($filename);
	// Replaces all spaces with underscores. 
    $filename = str_replace(' ', '_', $filename); 
    // Removes special chars. 
    $filename = preg_replace('/[^A-Za-z0-9\_]/', '', $filename); 
    // Replaces multiple underscores with single one. 
    $filename = preg_replace('/_+/', '_', $filename);
	
	return (string) $filename;
}

function curlPostUpload(string $postSource, string $file, string $pack_name, string $pack_name_old = null){
	global $targetURL;
	global $security_key;
	$versionClient = get_version();

	//add the security_key to the http header
	if(!isset($security_key) || empty($security_key)){
		die("No security_key found! Check the \"security_key\" value in your config.php file" . PHP_EOL);
	}
	$security_keyToken = base64_encode($security_key);
	//add post data and file upload to the POST array
	$metadata = array('source' => $postSource, 'version' => $versionClient, 'pack_name' => $pack_name, 'pack_name_old' => $pack_name_old, 'file_size' => filesize($file));
	$metadata = json_encode($metadata);
	//process file
	if($postSource === "upload"){
		//special curl function to create the information needed to upload files
		//renaming the banner images to be consistent with the pack name
		$cFile = curl_file_create($file,'',$pack_name.'.'.strtolower(pathinfo($file,PATHINFO_EXTENSION)));
		$post = array('metadata' => $metadata, 'file_contents'=> $cFile);
	}else{
		$post = array('metadata' => $metadata);
	}

	//setup curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL."/banners.php?" . $postSource);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Key: $security_keyToken"));
	curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_POST, TRUE); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	$result = curl_exec ($ch);
	if($result === FALSE){
		echo "Curl error: ".curl_error($ch) . PHP_EOL;
		wh_log("Curl error: ".curl_error($ch));
	}
	if(curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400){
		//good response from the server
		//echo $result; //echo from the server-side script
		//wh_log($result);
	}else{
		//some kind of error
		echo "There was an error communicating with $targetURL." . PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
		$result = FALSE;
	}
	curl_close ($ch);
	unset($ch);

	return $result;
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

//check php environment
check_environment();

//check for valid target URL
check_target_url();

echo "Finding and uploading pack banner images..." . PHP_EOL;
$startTime = microtime(TRUE);

//ready variables
$banners_copied = $notFoundBanners = $cPacks = 0;
$fileSizeMax = 5242880; //5MB

// find all the pack/group folders
//songDir valid?
if(empty($songsDir) || !file_exists($songsDir)){
	wh_log("StepMania song directory is empty or invalid. Check your config.php.");
	die("StepMania song directory is empty or invalid. Check your config.php." . PHP_EOL);
}
$packDirs = findFiles($songsDir);

// append any "Additional Songs" directories
$packDirs = add_additional_songs($packDirs);

if (count($packDirs) === 0){
	wh_log("No pack/group folders found. Your StepMania /Songs directory may be located in \"AppData\""); 
	die ("No pack/group folders found. Your StepMania /Songs directory may be located in \"AppData\"" . PHP_EOL);
}

echo(count($packDirs) . " packs found..." . PHP_EOL);

$img_arr = array();

foreach ($packDirs as $path){
	
	$pack_name = $img_path = "";
	//get pack name from folder
	$pack_name = basename($path);
	//$pack_name = substr($path,strrpos($path,"/")+1);

	//check if pack is to be ignored and skip if it is
	if(isIgnoredPack($pack_name)){
		//pack is ignored. Skip it
		continue;
	}
	
	//clean up pack name and replace spaces with underscore
	$pack_name_old = strtolower(preg_replace('/\s+/', '_', trim($pack_name)));
	$pack_name = strtolower(clean_filename($pack_name));
	//look for any picture file in the pack directory
	$img_path = glob("$path/*{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,bmp,BMP}",GLOB_BRACE);
	
	if(!isset($img_path) || empty($img_path) || $img_path === FALSE){
		//no image found for banner or error occured
		echo "No banner image for ".$pack_name. PHP_EOL;
		wh_log("No banner image for ".$pack_name);
		$notFoundBanners++;
		continue;
	}
	
	if(count($img_path) > 1){
		//more than 1 image found, let's search file names for which one is the banner
		$img_path = get_banner($img_path);
	}else{
		//use the first result as the pack banner
		$img_path = $img_path[0];
	}

	//check for filesize
	$imgFileSize = filesize($img_path);
	if ($imgFileSize == FALSE || $imgFileSize > $fileSizeMax){
		echo($pack_name . "'s image file is too large: " . (round($imgFileSize / 1024 / 1024,2)) . "MB. Max size: " . ($fileSizeMax / 1024 / 1024) . "MB!" . PHP_EOL);
		wh_log($pack_name . "'s image file is too large: " . (round($imgFileSize / 1024 / 1024,2)) . "MB. Max size: " . ($fileSizeMax / 1024 / 1024) . "MB!");
		continue;
	}

	//finally, add the image and metadate to the array
	$img_arr[] = array('img_path' => $img_path,'pack_name' => $pack_name,'pack_name_old' => $pack_name_old);
	
}

if(empty($img_arr)){
	die("No banners found" . PHP_EOL);
}

foreach ($img_arr as $img){
	//upload banner images
	$result = curlPostUpload("upload",$img['img_path'],$img['pack_name'],$img['pack_name_old']);
	if($result !== FALSE){
		echo $result;
		$banners_copied++;
	}
/* 	//check if banner exists
	$bannerExist = curlPost("exist",$img['img_path'],$img['pack_name']);
	if($bannerExist !== FALSE){
		//Attempt to decode POST data.
		$bannerExist = json_decode($bannerExist,TRUE);
		//If json_decode failed, the JSON is invalid.
		if(!is_array($bannerExist)){
			echo('Received content contained invalid JSON!' . PHP_EOL);
		}
		if($bannerExist['banner'] == "FALSE"){
			//upload banner images
			$result = curlPost("upload",$img['img_path'],$img['pack_name'],$img['pack_name_old']);
			if($result !== FALSE){
				echo $result;
				$banners_copied++;
			}
		}

	} */

}

//STATS!
$cPacks = count($packDirs) - $notFoundBanners;
$endTime = microtime(TRUE) - $startTime;

switch(TRUE){
	case($endTime > 60):
		$endTime = round($endTime / 60, 1);
		$endTime = $endTime . " mins";
		break;
	case($endTime <= 60 && $endTime > 1):
		$endTime = round($endTime, 0);
		$endTime = $endTime . " secs";
		break;
	case($endTime < 1):
		$endTime = round($endTime * 100, 0);
		$endTime = $endTime . "ms";
		break;
}

echo "Uploaded $banners_copied of $cPacks banner images in $endTime. Banners were not found for $notFoundBanners packs." . PHP_EOL;
wh_log("Uploaded $banners_copied of $cPacks banner images in $endTime. Banners were not found for $notFoundBanners packs.");

exit();
?>
