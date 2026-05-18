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
echo [1/2] Starting Laravel PHP server...
start "Laravel Server" cmd /k "%PHP_BIN% artisan serve --env=local"

:: Wait a moment before starting npm
timeout /t 2 /nobreak >nul

:: Start npm run dev in a new window
echo [2/2] Starting Vite (npm run dev)...
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo =============================================
echo   Both servers are running in separate windows
echo   Laravel: http://127.0.0.1:8000
echo   Close those windows to stop the servers.
echo =============================================
echo.
pause
