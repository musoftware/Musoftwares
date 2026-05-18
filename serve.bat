@echo off
title MuSoftware Dev Server

echo =============================================
echo   MuSoftware - Starting Development Servers
echo =============================================
echo.

:: Change to project directory
cd /d "%~dp0"

:: Start Laravel PHP server in a new window
echo [1/2] Starting Laravel PHP server...
start "Laravel Server" cmd /k "php artisan serve --env=local"

:: Wait a moment before starting npm
timeout /t 2 /nobreak >nul

:: Start npm run dev in a new window
echo [2/2] Starting Vite (npm run dev)...
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo =============================================
echo   Both servers are running in separate windows
echo   Close those windows to stop the servers.
echo =============================================
echo.
pause
