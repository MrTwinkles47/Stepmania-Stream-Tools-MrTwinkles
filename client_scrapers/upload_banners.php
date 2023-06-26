<?php

//
//PHP StepMania song pack banner uploader
//This script finds each banner image for each song group/pack and uploads it via POST to the server
//'file_uploads' must be enabled on the server for this script to work correctly
//

if (php_sapi_name() != "cli") {
	// Not in cli-mode
	die("Only support cli mode.");
}
// In cli-mode
$versionClient = get_version();
cli_set_process_title("SMRequests v$versionClient | StepMania Song Pack Banner Uploader");

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
echo "StepMania Song Pack Banner Uploader" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

//start logging and cleanup old logs
wh_log("Starting SMRequests v$versionClient Song Pack Banner Uploader...");
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
				die("PHP 8 is not supported! Please install the latest version of PHP 7.4. Your version is: " . PHP_VERSION );
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

function findFiles($directory) {
	//find all directories in a directory and sort by modified time
    $dir_paths = array ();
	foreach(glob("{$directory}/*", GLOB_ONLYDIR) as $filename) {
        $dir_paths[] = $filename;
	}
	usort( $dir_paths, function( $a, $b ) { return filemtime($b) - filemtime($a); } );
    return $dir_paths;
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

function does_banner_exist($file,$pack_name){
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
}

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

function curl_upload($file,$pack_name,$pack_name_old = null){
	global $targetURL;
	global $security_key;
	unset($ch,$post,$cFile);
	$versionClient = get_version();
	//add the security_key to the http header
	if(!isset($security_key) || empty($security_key)){
		die("No security_key found! Check the \"security_key\" value in your config.php file" . PHP_EOL);
	}
	$security_keyToken = base64_encode($security_key);
	//special curl function to create the information needed to upload files
	//renaming the banner images to be consistent with the pack name
	$cFile = curl_file_create($file,'',$pack_name.'.'.strtolower(pathinfo($file,PATHINFO_EXTENSION)));
	//add post data and file upload to the POST array
	$metadata = json_encode(array('version' => $versionClient, 'pack_name_old'=> $pack_name_old));
	$post = array('metadata' => $metadata, 'file_contents'=> $cFile);
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$targetURL."/banners.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Key: $security_keyToken"));
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
		//good response from the server
		echo $result; //echo from the server-side script
		wh_log($result);
		$return = 0;
	}else{
		//some kind of error
		echo "There was an error communicating with $targetURL." . PHP_EOL;
		wh_log("The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		echo "The server responded with error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
		$return = 1;
	}
	curl_close ($ch);
	return $return;
}

//check php environment
check_environment();

//check for valid target URL
check_target_url();

echo "Finding and uploading pack banner images..." . PHP_EOL;

//ready variables
$banners_copied = $notFoundBanners = $cPacks = 0;
$fileSizeMax = 5242880; //5MB

// find all the pack/group folders
//songDir valid?
if(empty($songsDir) || !file_exists($songsDir)){
	wh_log("StepMania song directory is empty or invalid. Check your config.php.".PHP_EOL);
	die("StepMania song directory is empty or invalid. Check your config.php.");
}
$packDirs = findFiles($songsDir);

//add any additional songs folder(s)
if(!empty($addSongsDir)){
	if(!is_array($addSongsDir)){
		$addSongsDir = array($addSongsDir);
	}
	foreach($addSongsDir as $directory){
		$packDirs[] = findFiles($directory);
	}
}

$cPacks = count($packDirs);

if ($cPacks == 0){
	wh_log("No pack/group folders found. Your StepMania /Songs directory may be located in \"AppData\""); 
	die ("No pack/group folders found. Your StepMania /Songs directory may be located in \"AppData\"" . PHP_EOL);
}

$img_arr = array();

foreach ($packDirs as $path){
	
	$pack_name = $img_path = "";
	//get pack name from folder
	$pack_name = substr($path,strrpos($path,"/")+1);
	//check if pack is to be ignored and skip if it is
	if(!isIgnoredPack($pack_name)){
		//pack is not ignored
		//clean up pack name and replace spaces with underscore
		$pack_name_old = strtolower(preg_replace('/\s+/', '_', trim($pack_name)));
		$pack_name = strtolower(clean_filename($pack_name));
		//look for any picture file in the pack directory
		$img_path = glob("{$path}/*{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,bmp,BMP}",GLOB_BRACE);
		
		if (isset($img_path) && !empty($img_path)){
			if(count($img_path) > 1){
				//more than 1 image found, let's search file names for which one is the banner
				$img_path = get_banner($img_path);
			}else{
				//use the first result as the pack banner
				$img_path = $img_path[0];
			}
			//check for filesize
			if (filesize($img_path) > $fileSizeMax){
				echo $pack_name."'s image file is too large (max size: ". $fileSizeMax / 1024^2 ."MB)!" . PHP_EOL;
				wh_log($pack_name."'s image file is too large (max size: ". $fileSizeMax / 1024^2 ."MB)!");
			}else{
				$img_arr[] = array('img_path' => $img_path,'pack_name' => $pack_name,'pack_name_old' => $pack_name_old);
			}
		}else{
			echo "No banner image for ".$pack_name. PHP_EOL;
			wh_log("No banner image for ".$pack_name);
			$notFoundBanners++;
		}
	}
}

foreach ($img_arr as $img){
	//check if banner already on server
	//if(does_banner_exist($img['img_path'],$img['pack_name'])){
	//	echo "Banner for ". $img['pack_name'] . " already exists. Skipping...".PHP_EOL;
	//}else{
		//upload banner images
		if(curl_upload($img['img_path'],$img['pack_name'],$img['pack_name_old']) === 0){
			$banners_copied++;
		}
	//}
}

$cPacks = $cPacks - $notFoundBanners;

//STATS!
echo "Uploaded ".$banners_copied." of ".$cPacks." banner images. Banners were not found for ".$notFoundBanners." packs." . PHP_EOL;
wh_log("Uploaded ".$banners_copied." of ".$cPacks." banner images. Banners were not found for ".$notFoundBanners." packs.");

exit();
?>
