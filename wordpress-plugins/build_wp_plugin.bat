@echo off
echo Building WordPress Plugin...

REM Ensure we are in the correct directory (the one containing the batch file)
cd /d "%~dp0"

REM Create the destination directory if it doesn't exist
if not exist "..\public\plugins" mkdir "..\public\plugins"

REM Remove the old zip if it exists
if exist "..\public\plugins\musoftware-sms-gateway.zip" del /q "..\public\plugins\musoftware-sms-gateway.zip"

REM Zip the plugin folder using PowerShell
powershell -Command "Compress-Archive -Path musoftware-sms-gateway -DestinationPath ..\public\plugins\musoftware-sms-gateway.zip -Force"

echo Done!
