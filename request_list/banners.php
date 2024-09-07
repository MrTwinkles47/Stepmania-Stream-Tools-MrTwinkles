<?php

require_once ('config.php');
require_once ('misc_functions.php');
	
// recieve upload of banner images via POST, FILES
//Make sure that it is a POST request.
if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
    die("Request method must be POST!" . PHP_EOL);
}

//Get access token/security key from http header
if(isset($_SERVER['HTTP_KEY'])){
	$keyToken = trim($_SERVER['HTTP_KEY']);
	if(empty($keyToken)){
		die("Error: No secrity key from client! Check your config.php." . PHP_EOL);
	}
	$keyToken = base64_decode($keyToken);
	if($keyToken != $security_key){
		die("Error: Incorrect security key: \"$keyToken\"! Check your config.php." . PHP_EOL);
	}
}else{
	die("No valid HTTP security_key header" . PHP_EOL);
}

function does_banner_exist(string $pack_name, string $filesize){
	global $uploaddir;
	$return = FALSE;

	$existingFile = $uploaddir . "/" . $pack_name;

	if(file_exists($existingFile)){
		if(filesize($existingFile) == $filesize){
			$return = TRUE;
		}
	}
	return((bool) $return);
}

function processBannerUpload($metadata){
	global $uploaddir;
	global $_FILES;
	$fileSizeMax = 5242880; //5MB

	if($_FILES['file_contents']['size'] > $fileSizeMax){
		die($_FILES['file_contents']['name']."'s image file is too large (max size: ". $fileSizeMax / 1024^2 ."MB)!".PHP_EOL);
	}

	$uploadfile = $uploaddir .'/'. $_FILES['file_contents']['name'];

	if(!file_exists($uploadfile)){
		//check if any image exists for the old pack name
		if(isset($metadata['pack_name_old'])){
			$files = glob($uploaddir."/".$metadata['pack_name_old'].".{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,bmp,BMP}",GLOB_BRACE);
			if(count($files) > 0){
				//an image exists for the old pack name, let's remove it
				array_map('unlink',$files);
				echo "Removed previous banner image for ".$metadata['pack_name_old'].PHP_EOL;
			}
		}
		
		//No banner exists, move the temp uploaded file to the banner directory
		if (move_uploaded_file($_FILES['file_contents']['tmp_name'], $uploadfile)) {
			echo "Successfully uploaded banner for ".$_FILES['file_contents']['name'].PHP_EOL;
		}else{
			echo "Possible file upload attack!".PHP_EOL;
		}
	}else{
		//a banner has been found, check if the filesize has changed
		if(filesize($uploadfile) == $_FILES['file_contents']['size']){
			//filesize is the same, *probably* the same image
			echo "File already exists for ".$_FILES['file_contents']['name'].PHP_EOL;
		}else{
			//check if any image exists for the pack name
			$files = glob($uploaddir."/".pathinfo($_FILES['file_contents']['name'],PATHINFO_FILENAME).".{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,bmp,BMP}",GLOB_BRACE);
			if(count($files) > 0){
				//an image exists for this pack name, but is a different file, remove it
				array_map('unlink',$files);
				echo "Removed previous banner image for ".$_FILES['file_contents']['name'].PHP_EOL;
			}
			//update banner image with the newly updated file
			if(move_uploaded_file($_FILES['file_contents']['tmp_name'], $uploadfile)) {
				echo "Successfully updated banner for ".$_FILES['file_contents']['name'].PHP_EOL;
			}else{
				echo "Possible file upload attack!".PHP_EOL;
			}
		}	
	}
}

//Attempt to decode POST data.
$metadata = json_decode($_POST['metadata'], true, JSON_INVALID_UTF8_IGNORE);
//If json_decode failed, the JSON is invalid.
if(!is_array($metadata)){
    die('Received content contained invalid JSON!' . PHP_EOL);
}

//get version of client
if(isset($metadata['version'])){
	$versionClient = $metadata['version'];
}else{
	$versionClient = 0;
}
//is the client script up to date?
check_version($versionClient);

switch ($metadata['source']){
	case "exist":
		//check if banner exists before uploading
		$bannerExist = does_banner_exist($metadata['pack_name'], $metadata['filesize']);
		if($bannerExist){
			echo json_encode(array("banner" => "TRUE"));
		}
	break;
	case "upload":
	default:
		//upload banner
		processBannerUpload($metadata);
		//die("Invalid JSON recieved!" . PHP_EOL);
}

die();
?>