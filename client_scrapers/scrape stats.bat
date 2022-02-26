::update path to php installation
@echo off
cd "C:\php"
cls
::you must add profile IDs and the optional "-auto" argument for this script to run
::example:  php.exe "%~dp0scrape_stats.php" -auto 00000000
::If using USBProfiles, the location should match what is in your Preferences.ini -> MemoryCardProfileSubdir=StepMania 5.3
::example:  php.exe "%~dp0scrape_stats.php" -auto "StepMania 5.3"
php.exe "%~dp0scrape_stats.php"
pause