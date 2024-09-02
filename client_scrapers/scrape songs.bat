::update path to php installation
@echo off
cd "C:\php"
cls
php.exe "%~dp0scrape_songs_cache.php"
pause
