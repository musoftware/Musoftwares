@echo off
echo Building WordPress Plugin and Bumping Version...

REM Ensure we are in the correct directory
cd /d "%~dp0"

REM Run the PHP build script which increments version and zips the file
php build.php
