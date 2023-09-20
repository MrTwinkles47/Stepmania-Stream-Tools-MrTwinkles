# SMRequests Client Scraper Scripts

## What are these files?

These files contain PHP configuration files and scripts that scrape your local StepMania data (songs, banners, and statistics) and upload it to the SMRequests server. Executing these files is required for SMRequests to work.

### config.php

Contains configuration data for all of the SMRequests PHP scripts.

You _must_ fill this out with the required information for the PHP scripts to execute properly!

### ExecuteSMRequests.bat

The SMRequests executor is a convenient batch file wrapper for executing one or more of the SMRequests PHP scripts in Windows.

The batch file allows Stepmania and/or one or more of the PHP scripts to be run in-order. See the "Usage" section for more details.

### scrape_songs_cache.php

The cache scraper parses the song cache files to add/update songs to the sm_songs and sm_notedata tables on the SMRequests server.

This script should be executed after adding/removing songs or song caches and executing Stepmania to update the cache.

Configure the song and song cache directories to be parsed in your 'config.php' file.

### scrape_stats.php

The stats scraper is responsible for automatically grabbing all your high scores and recently played songs from your Stats.xml file(s) and sending them to the SMRequests server.  The score and play data is used to automatically complete open song requests and provide information for random commands (`!top`, `!gitgud`, etc.).

This script should be executed before starting Stepmania during your stream. The stats are updated after each song is played(usually at the song evaluation screen).

Configure the active local profile(s) or USB profile(s) in your 'config.php' file.

### upload_banners.php

This banner uploader finds the first image file in each pack and song folder, formats the name to match the name of the pack or song, and uploads it to the SMRequests server.

This script should be executed after adding/removing songs or song caches and executing Stepmania to update the cache.


## How do I execute these scripts?

### Prerequisites

In order to execute these scripts successfully, PHP must be installed, and both PHP and SMRequests must be configured.

#### Installing PHP

1. Download [PHP 7.4.33 NTS x86/x64 for Windows](https://windows.php.net/downloads/releases/php-7.4.33-nts-Win32-vc15-x64.zip).
2. Extract the zip file to the 'php/' subdirectory.

#### Configuring PHP

1. Enter the 'client_scrapers/php/' directory.
2. Duplicate 'php.ini-production' and rename it to 'php.ini'.
3. Open 'php.ini' and change the following lines:
    - `;extension_dir = "./"` to `extension_dir = "./ext"`
    - `;extension=curl` to `extension=curl`
    - `;extension=mbstring` to `extension=mbstring`

#### Configuring SMRequests

1. Enter the 'client_scrapers/' directory.
2. Duplicate 'config.example.php' and rename it to 'config.php'.
3. Open 'config.php' and change the following lines:
    - `$cacheDir` to the path to your StepMania song cache directory (e.g. `%APPDATA%/StepMania 5.1/Cache/Songs`)
    - `$saveDir` to the path to your StepMania save directory (e.g. `%APPDATA%/StepMania 5.1/Save`)
    - `$profileID` to the ID of the profile to scrape for stats, located in `Save/LocalProfiles` (e.g. `00000005`)
    - `$songsDir` to the location of the StepMania song directory (e.g. `%APPDATA%/StepMania 5.1/Songs`)
    - `$targetURL` to the target URL for sending updates to the SMRequests server (e.g. `https://famoustwitchstreamer.smrequests.com`)
    - `$security_key` to the security key needed to access your SMRequests server (e.g. `e984c38b4a5f84`)

### Usage

#### Batch File Command Line

The usage for 'ExecuteSMRequests.bat' is:
```
ExecuteSMRequests.bat [step options]
    step options:
        -PreStart "stepmania_executable_path"   Starts StepMania after running the SMRequests steps
        -Songs                                  Uploads song data from StepMania cache to SMRequests server
        -Banners                                Uploads banner images from Stepmania cache to SMRequests server
        -Stats                                  Uploads play statistics from Stepmania cache to SMRequests server
        -PostStart "stepmania_executable_path"  Starts StepMania after running the SMRequests steps
    stepmania_executable_path 
```
Any combination of or all of these steps can be done in a single execution of the batch file.

#### Examples

##### Updating Song and Banner Cache

1. Manually add/remove songs from StepMania and delete your cache file.
2. Run `ExecuteSMRequests.bat -PreStart "D:/StepMania 5.1/Program/StepMania.exe" -Songs -Banners`.
3. Wait for Stepmania to load.
4. Once Stepmania has finished loading, close it.
5. The SMRequests scripts will upload the cache changes to the server.

##### Running Stepmania with SMRequests

1. Run `ExecuteSMRequests.bat -Stats -PostStart "D:/StepMania 5.1/Program/StepMania.exe"`
2. Once StepMania starts, SMRequests should pick up your plays automatically!

### Tips

#### Using shortcuts

To make the batch file even easier to use, create a shortcut to 'ExecuteSMRequests.bat'. In the properties for the shortcut, enter the command you would like to run (e.g. `D:/Scripts/ExecuteSMRequests.bat -Stats -PostStart "D:/StepMania 5.1/Program/StepMania.exe`).

Then, double-click the shortcut to run the batch file with your commands automatically!
