<?php
//config.php version
const CONFIG_VERSION = "1730";

//Local timezone
//Go here to find your timezone string: https://www.php.net/manual/en/timezones.php
//Example: "America/New_York"
$timezone = "";

//Local timezone
//Go here to find your timezone string: https://www.php.net/manual/en/timezones.php
//Example: "America/New_York"
$timezone = "";

//Your absolute path to StepMania's song cache folder. This could be in the following locations:
//SM5: "[AppData]/Roaming/StepMania 5/Cache/Songs"
//SM5.1: "[AppData]/Roaming/StepMania 5.1/Cache/Songs"
//ITGmania: "[AppData]/Roaming/itgmania/Cache/Songs"
//Project OutFox and portable installations: "[Program Directory]/Cache/Songs"
//$cacheDir = "C:/Users/[USER]/AppData/Roaming/StepMania 5.1/Cache/Songs";
$cacheDir = "";

//location of StepMania "Save" directory. This could be in the following locations:
//SM5: "[AppData]/Roaming/StepMania 5/Save"
//SM5.1: "[AppData]/Roaming/StepMania 5.1/Save"
//ITGmania: "[AppData]/Roaming/itgmania/Save"
//Project OutFox and portable installations: "[Program Directory]/Save"
//$saveDir = "C:/Users/[USER]/AppData/Roaming/StepMania 5.1/Save";
$saveDir = "";

//Profile ID(s) for Stats.xml files you would like to scrape. These are directories located in [/Save/LocalProfiles] with names like '00000000'.
//You must specify at least 1 ID, but can add more by separating the IDs with a comma.
//Example for 1 local profile: "00000000"
//Example for 2 local profiles: "00000000,00000001"
$profileID = "";

//If using USB Profiles, please configure the $USBProfileDir to point at your drive/share location for the USB Drive.
//This should match the value in your Preferences.ini file for 'MemoryCardProfileSubdir'
//Example: "R:/StepMania 5.3"
$USBProfileDir = "";

//location of StepMania songs folder. This could be the "/Songs" directory in your SM5 installation directory
//or in your [AppData] directory.
//$songsDir = "D:/StepMania 5.1/Songs";
$songsDir = "";
//location of your AdditionalSongsFolder(s). If you are using any AdditionalSongs folders in your Preferences.ini, add the folder(s) in relation to where you are running the PHP scripts (This may not be the same path in your Preferences.ini). If there is more than one directory, use an array.
$addSongsDir = "";

//list of song packs/groups to ignore while scraping. These packs will not show up on the songlist and will not be request-able.
//Example: array("~WIP","Secret Folder","MyDog's")
$packsIgnore = array("~WIP");
//RegEx matching for dynamic ignoring of pack names (https://regex101.com/)
//Example: "/.*secret.*/i"
$packsIgnoreRegex = "";

//Target URL for POSTING updates to the server. This is typically where your songlist is hosted.
//Example: "https://famoustwitchstreamer.smrequests.com" (No trailing '/' !!!)
$targetURL = "https://[URL]";

//Security key. Set this to anything. All incoming requests from the chatbot will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission. This MUST match the security key on the server-side.
$security_key = "";

//Offline Mode
//Set this to TRUE if you are running SMRequests on a separate machine that has no realtime access to the StepMania installation
//For a description of this operation mode, review the main README.
$offlineMode = FALSE;

//USB Profile Mode
//Set this to True if you are using a USB profile as your profile location 
$USBProfile = FALSE;

?>