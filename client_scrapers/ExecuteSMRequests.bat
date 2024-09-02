::
:: ExecuteSMRequests.bat
::
:: Executes SMRequests scripts to scrape songs, scrape statistics, and upload banners.
:: Also allows a step game to be executed before or after the SMRequests scripts are executed.
:: Any combination of these five executions can be performed by passing the correct arguments.
::
@echo off


:: --------------------------------------------------------------
:: FILE PARAMETERS
:: --------------------------------------------------------------
set PHP_EXE=php.exe
set PHP_PATH=%~dp0php\
set SMREQUESTS_CONFIG=%~dp0config.php
set SMREQUESTS_VERSION=%~dp0VERSION
set SCRAPE_SONGS_PHP_SCRIPT=%~dp0scrape_songs_cache.php
set UPLOAD_BANNERS_PHP_SCRIPT=%~dp0upload_banners.php
set SCRAPE_STATS_PHP_SCRIPT=%~dp0scrape_stats.php
set STEP_GAME_EXE_DEFAULT=%~dp0..\itgmania\Program\itgmania.exe
set PHP_LINK=https://windows.php.net/downloads/releases/php-7.4.33-nts-Win32-vc15-x64.zip
set SMREQUESTS_LINK=https://github.com/MrTwinkles47/Stepmania-Stream-Tools-MrTwinkles/releases/latest


:parse_arguments
:: If you need to debug argument parsing, uncomment the following line
rem echo Found argument: '%1'

:: We accept '[/-]Songs', '[/-]Banners', '[/-]Stats', '[/-]Prestart', '[/-]Poststart' flags in any order
if /I "%~1"=="-Songs" (
    set SCRAPE_SONGS=true
    goto :next_argument
)
if /I "%~1"=="/Songs" (
     set SCRAPE_SONGS=true
    goto :next_argument
)
if /I "%~1"=="-Banners" (
    set UPLOAD_BANNERS=true
    goto :next_argument
)
if /I "%~1"=="/Banners" (
    set UPLOAD_BANNERS=true
    goto :next_argument
)
if /I "%~1"=="-Stats" (
    set SCRAPE_STATS=true
    goto :next_argument
)
if /I "%~1"=="/Stats" (
    set SCRAPE_STATS=true
    goto :next_argument
)
if /I "%~1"=="-Prestart" (
    set PRESTART_STEP_GAME=true
    goto :next_argument
)
if /I "%~1"=="/Prestart" (
    set PRESTART_STEP_GAME=true
    goto :next_argument
)
if /I "%~1"=="-Poststart" (
    set POSTSTART_STEP_GAME=true
    goto :next_argument
)
if /I "%~1"=="/Poststart" (
    set POSTSTART_STEP_GAME=true
    goto :next_argument
)

:: Any other non-empty argument is applied to step game path
if not "%~1"=="" (
    set STEP_GAME_EXE=%~1
    goto :next_argument
)

:next_argument
shift
if not "%~1"=="" goto :parse_arguments

:: Set default step game executable if not set by arguments
if not defined STEP_GAME_EXE (
    echo WARNING: Step game executable path was not set on the command line!
    echo The default path of '%STEP_GAME_EXE_DEFAULT%' will be used instead.
    set STEP_GAME_EXE=%STEP_GAME_EXE_DEFAULT%
)
goto :check_execution_flags


:check_execution_flags
if defined SCRAPE_SONGS goto :check_php_paths
if defined UPLOAD_BANNERS goto :check_php_paths
if defined SCRAPE_STATS goto :check_php_paths
if defined PRESTART_STEP_GAME goto :check_step_game_path
if defined POSTSTART_STEP_GAME goto :check_step_game_path
:: If we haven't gone to a checker function, no flags must have been set
goto :error_not_found_execution_flags


:check_php_paths
if not exist %PHP_PATH% goto :error__not_found_php_dir
pushd %PHP_PATH%
if not exist %PHP_EXE% goto :error__not_found_php_exe
goto :check_smrequests_files


:check_smrequests_files
if not exist %SMREQUESTS_CONFIG% goto :error__not_found_smrequests_config
if not exist %SMREQUESTS_VERSION% goto :error__not_found_smrequests_version
if defined PRESTART_STEP_GAME goto :check_step_game_path
if defined POSTSTART_STEP_GAME goto :check_step_game_path
goto :select_next_execution


:check_step_game_path
if not exist %STEP_GAME_EXE% goto :error__not_found_step_game_exe
goto :select_next_execution


:select_next_execution
if defined PRESTART_STEP_GAME goto :start_step_game
if defined SCRAPE_SONGS goto :scrape_songs
if defined UPLOAD_BANNERS goto :upload_banners
if defined SCRAPE_STATS goto :scrape_stats
if defined POSTSTART_STEP_GAME goto :start_step_game
goto :echo_success


:scrape_songs
echo Scraping songs for SMRequests...
if not exist %SCRAPE_SONGS_PHP_SCRIPT% (
    set MISSING_SMREQUESTS_SCRIPT=%SCRAPE_SONGS_PHP_SCRIPT%
    goto :error__not_found_smrequests_script
)
%PHP_EXE% "%SCRAPE_SONGS_PHP_SCRIPT%"

:: Clear our flag and begin the next cycle
set SCRAPE_SONGS=
goto :select_next_execution


:upload_banners
echo Uploading banners for SMRequests...
if not exist %UPLOAD_BANNERS_PHP_SCRIPT% (
    set MISSING_SMREQUESTS_SCRIPT=%UPLOAD_BANNERS_PHP_SCRIPT%
    goto :error__not_found_smrequests_script
)
%PHP_EXE% "%UPLOAD_BANNERS_PHP_SCRIPT%"

:: Clear our flag and begin the next cycle
set UPLOAD_BANNERS=
goto :select_next_execution


:scrape_stats
echo Scraping stats for SMRequests...
if not exist %SCRAPE_STATS_PHP_SCRIPT% (
    set MISSING_SMREQUESTS_SCRIPT=%SCRAPE_STATS_PHP_SCRIPT%
    goto :error__not_found_smrequests_script
)
:: Stat scraper needs to be started instead of called because it runs continuously
start %PHP_EXE% "%SCRAPE_STATS_PHP_SCRIPT%"

:: Clear our flag and begin the next cycle
set SCRAPE_STATS=
goto :select_next_execution


:start_step_game
echo Starting step game executable at '%STEP_GAME_EXE%'...
:: We call the step game inside this process so we can wait for it to finish
"%STEP_GAME_EXE%"

:: Clear our flag and begin the next cycle
if defined PRESTART_STEP_GAME (
    set PRESTART_STEP_GAME=
) else (
    set POSTSTART_STEP_GAME=
)
goto :select_next_execution


:error_not_found_execution_flags
echo ERROR: No execution flags were set on the command line!
echo Please set one or more execution flags: '-Songs', '-Stats', '-Banners', '-PreStart', or '-PostStart'.
goto :finish


:error__not_found_php_dir
echo ERROR: Unable to find PHP directory at '%PHP_PATH%'!
goto :error__echo_php_link


:error__not_found_php_exe
echo ERROR: Unable to find PHP executable at '%PHP_PATH%%PHP_EXE%'!
goto :error__echo_php_link


:error__echo_php_link
echo Please download PHP from '%PHP_LINK%' and install it into '%PHP_PATH%'.
goto :finish


:error__not_found_step_game_exe
echo ERROR: Unable to find step game executable at '%STEP_GAME_EXE%'!
echo Please make sure to either:
echo    Install your step game at '%STEP_GAME_EXE%', or
echo    Provide the full path to the step game exe to this script on the command line.
goto :finish


:error__not_found_smrequests_config
echo ERROR: Unable to find SMRequests configuration file at '%SMREQUESTS_CONFIG%'!
goto :error__echo_smrequests_link


:error__not_found_smrequests_script
echo ERROR: Unable to find SMRequests PHP script at '%MISSING_SMREQUESTS_SCRIPT%'!
goto :error__echo_smrequests_link


:error__not_found_smrequests_version
echo ERROR: Unable to find SMRequests versioning file at '%SMREQUESTS_VERSION%'!
goto :error__echo_smrequests_link


:error__echo_smrequests_link
echo Please download the latest SMRequests scripts at '%SMREQUESTS_LINK%' and extract them here.
goto :finish


:echo_success
echo SMRequests execution completed successfully.
goto :finish


:finish
popd
pause
