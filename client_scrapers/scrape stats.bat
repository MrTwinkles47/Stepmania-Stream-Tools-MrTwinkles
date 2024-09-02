::update path to php installation
@echo off
cd "C:\php"
cls
::example:  php.exe "%~dp0scrape_stats.php"
php.exe "%~dp0scrape_stats.php"
pause