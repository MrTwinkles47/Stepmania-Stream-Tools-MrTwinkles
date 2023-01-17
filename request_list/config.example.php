<?php

//Check for docker variables to use first

if(getenv("MYSQL_DATABASE") != ""){
    $dbname = getenv("MYSQL_DATABASE");
    define('dbhost', 'mysql');
    define('db', $dbname);
}else{
    //Your database host. 'localhost' if you are running the DB on the same machine as the web server.
    define('dbhost', '');
    //Your database name.
    define('db', '');
}

if(getenv("MYSQL_USER") != ""){
    $dbuser = getenv("MYSQL_USER");
    define('dbuser', $dbuser);
}else{
    //Your database username.
    define('dbuser', '');
}

if(getenv("MYSQL_PASSWORD") != ""){
    $dbpass = getenv("MYSQL_PASSWORD");
    define('dbpass', $dbpass);
}else{
    //Your database password.
    define('dbpass', '');
}

if(getenv("SECRET_KEY") != ""){
    $security_key = getenv("SECRET_KEY");
}else{
    //Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
    //This way people can't hit your endpoints directly without permission.
    $security_key = "any-secret-here";
}

if(getenv("BANNER_DIR") != ""){
    $uploaddir = getenv("BANNER_DIR");
}else{
	//Upload directory for banner pack images (absolute directory on server)
	$uploaddir = __DIR__.'/images/packs';
}

//List of games or channel categories that must be set as the "current game" on Twitch for the bot to work.
//This is used as a backup if your bot does not support per game custom commands.
$categoryGame = array('StepMania','Project OutFox','Etterna');

//Broadcaster List. Define an array to associate broadcaster names with StepMania profile names.
//This is only required if your StepMania setup is used by more than 1 twitch account
//or you scrape more than 1 local profile.
$broadcasters = array(
						//twitch id			//SM5 profile
						'ddrdave' 		=> 	'Dave',
						'mrtwinkles'    => 	'MRT'
					 );

//User request cooldown interval. This value is a multiplier of active global requests.
//Ex: cooldown minutes = (number of active requests) * ($cooldownMultiplier)
//A value of 0.5 equates to 30 seconds of cooldown per active global request.
$cooldownMultiplier = 0.4;

//Length of the on-stream request widget (max # of visible requests).
//The default value is 10.
$requestWidgetLength = 10;

//Max requests. Maximum active requests before requests are halted.
//This number should be 10 or less.
$maxRequests = 10;

//Scoring type. This is the scoring type that will be visible for score-based random commands.
//This does not change how any scores are determined, only which score type is displayed.
//Values can be "itg" or "ddr". ITG scores are in percentage and DDR scores are a number out of 1M.
$scoreType = "itg";

//limit to how many random songs can be requested at once
$max_num = 3;

//Enable webhook check functionality 
$enableWebHook = false;

?>
