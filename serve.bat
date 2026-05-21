@echo off
title MuSoftware Dev Server

echo =============================================
echo   MuSoftware - Starting Development Servers
echo   PHP: C:\tools\php83\php.exe (v8.5.6)
echo =============================================
echo.

:: Change to project directory
cd /d "%~dp0"

:: Set PHP 8.5 path
set PHP_BIN=C:\tools\php83\php.exe

:: Start Laravel PHP server in a new window
echo [1/3] Starting Laravel PHP server...
start "Laravel Server" cmd /k "%PHP_BIN% artisan serve --env=local"

:: Wait a moment before starting npm
timeout /t 2 /nobreak >nul

:: Start npm run dev in a new window
echo [2/3] Starting Vite (npm run dev)...
start "Vite Dev Server" cmd /k "npm run dev"

:: Start Musoftware Runtime agent
echo [3/3] Starting Musoftware Runtime agent...
start "Musoftware Runtime" cmd /k "cd ..\newmusoftwareTools\musoftware-runtime && npm run dev"

echo.
echo =============================================
echo   All 3 servers are running in separate windows
echo   Laravel: http://127.0.0.1:8000
echo   Vite: http://127.0.0.1:5174
echo   Runtime: http://127.0.0.1:18400
echo   Close those windows to stop the servers.
echo =============================================
echo.
pause
