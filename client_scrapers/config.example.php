<?php

//Your absolute path to StepMania's song cache folder. This could be in the following locations:
//SM5: "[AppData]/Roaming/StepMania 5/Cache/Songs"
//SM5.1: "[AppData]/Roaming/StepMania 5.1/Cache/Songs"
//SM5.3 (OutFox) or portable installations: "[SM5]/Cache/Songs"
$cacheDir = "C:/Users/[USER]/AppData/Roaming/StepMania 5.1/Cache/Songs";

//location of StepMania "Save" directory. This could be in the following locations:
//SM5: "[AppData]/Roaming/StepMania 5/Save"
//SM5.1: "[AppData]/Roaming/StepMania 5.1/Save"
//SM5.3 (OutFox) or portable installations: "[SM5]/Save"
//If using USB Profile, please configure the $profileDir to point at your drive/share location for the USB Drive
$saveDir = "C:/Users/[USER]/AppData/Roaming/StepMania 5.1/Save";
$profileDir = $saveDir."/LocalProfiles";

//location of StepMania songs folder. This could be the "/Songs" directory in your SM5 installation directory
//or in your [AppData] directory.
$songsDir = "D:/StepMania 5.1/Songs";
//location of your AdditionalSongsFolder(s). If you are using any AdditionalSongs folders in your Preferences.ini, add the folder(s) in relation to where you are running the PHP scripts (This may not be the same path in your Preferences.ini). If there is more than one directory, use an array.
$addSongsDir = "";

//list of song packs/groups to ignore while scraping. These packs will not show up on the songlist and will not be request-able.
//Example: array("~WIP","Secret Folder","MyDog's")
//RegEx matching for dynamic ignoring of pack names
$packsIgnore = array("~WIP");
$packsIgnoreRegex = "";

//Target URL for POSTING updates to the server. This is typically where your songlist is hosted.
//Example: "https://famoustwitchstreamer.smrequests.com" (No trailing '/' !!!)
$target_url = "https://[URL]";

//Security key. Set this to anything. All incoming requests from the chatbot will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission. This MUST match the security key on the server-side.
$security_key = "any-secret-here";

//Offline Mode
//Set this to TRUE if you are running the request system on a separate machine that has no realtime access to the StepMania files
//For a description of this operation mode, review the main README.
$offlineMode = FALSE;

//USB Profile Mode
//Set this to True if you are using a USB profile as your profile location 
$USBProfile = FALSE;

?>