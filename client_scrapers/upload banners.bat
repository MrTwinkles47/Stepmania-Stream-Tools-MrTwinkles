::update path to php installation and path the php script
@echo off
cd "C:\php"
cls
php.exe "%~dp0upload_banners.php"
pause
