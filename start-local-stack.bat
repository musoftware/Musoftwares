@echo off
REM ============================================================================
REM  start-local-stack.bat
REM  Boots Musoftwares (:8000) + GoldSaverSys (:8001) for local cross-talk.
REM
REM  Strategy: each `php artisan serve --env=local` reads `.env.local` directly.
REM  No swap, no backup. Just align cross-talk vars in .env.local.
REM
REM  Prereqs:
REM    - MySQL running on 127.0.0.1:3306
REM    - .env.local present in BOTH project roots with local-DB credentials
REM    - composer install run for both projects
REM    - PHP 8.3+ (Laravel needs Random\Randomizer) - we use C:\tools\php83
REM
REM  Optional env vars:
REM    MIGRATE_ON_BOOT=0  -> skip pending migrations
REM    NO_BROWSER=1       -> don't auto-open browser tabs
REM ============================================================================

setlocal ENABLEDELAYEDEXPANSION

set "MONO_DIR=D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares"
set "GOLD_DIR=D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares-GoldSaverSys"

set "MONO_URL=http://127.0.0.1:8000"
set "GOLD_URL=http://127.0.0.1:8001"
set "SHARED_SECRET=local-shared-secret-change-me"

set "PHP=C:\tools\php83\php.exe"

title Musoftwares + GoldSaverSys Local Stack

echo.
echo Using PHP:
call "%PHP%" -v | findstr /R "^PHP"
echo.

echo ============================================================
echo   1. Pre-flight checks
echo ============================================================

powershell -NoProfile -Command "$ok=Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue; if ($ok) { Write-Host '[OK]  MySQL 127.0.0.1:3306 reachable' -ForegroundColor Green } else { Write-Host '[ERR] MySQL 127.0.0.1:3306 NOT reachable' -ForegroundColor Red; exit 2 }"
if errorlevel 2 exit /b 2

if not exist "%MONO_DIR%\artisan" ( echo [ERR] %MONO_DIR%\artisan missing & exit /b 1 )
if not exist "%GOLD_DIR%\artisan" ( echo [ERR] %GOLD_DIR%\artisan missing & exit /b 1 )
if not exist "%MONO_DIR%\.env.local" ( echo [ERR] %MONO_DIR%\.env.local missing & exit /b 1 )
if not exist "%GOLD_DIR%\.env.local" ( echo [ERR] %GOLD_DIR%\.env.local missing & exit /b 1 )

echo [OK]  All required env files and Laravel projects present

echo.
echo ============================================================
echo   2. Align cross-talk variables inside .env.local files
echo ============================================================

call :ensure_env "%MONO_DIR%\.env.local" "APP_URL=%MONO_URL%"
call :ensure_env "%MONO_DIR%\.env.local" "GOLDSAVERSYS_URL=%GOLD_URL%"
call :ensure_env "%GOLD_DIR%\.env.local" "APP_URL=%GOLD_URL%"
call :ensure_env "%GOLD_DIR%\.env.local" "MONOLITH_URL=%MONO_URL%"
call :ensure_env "%GOLD_DIR%\.env.local" "MONOLITH_SHARED_SECRET=%SHARED_SECRET%"

echo.
echo ============================================================
echo   3. Run pending migrations (set MIGRATE_ON_BOOT=0 to skip)
echo ============================================================

if "%MIGRATE_ON_BOOT%"=="0" (
  echo [SKIP] MIGRATE_ON_BOOT=0
) else (
  echo -- Musoftwares
  cd /d "%MONO_DIR%"
  call "%PHP%" artisan migrate --env=local --force
  echo.
  echo -- GoldSaverSys
  cd /d "%GOLD_DIR%"
  call "%PHP%" artisan migrate --env=local --force
)

echo.
echo ============================================================
echo   4. Clear caches for both
echo ============================================================

cd /d "%MONO_DIR%"
call "%PHP%" artisan config:clear --env=local  >nul 2>&1
call "%PHP%" artisan route:clear  --env=local  >nul 2>&1
call "%PHP%" artisan cache:clear  --env=local  >nul 2>&1
call "%PHP%" artisan view:clear   --env=local  >nul 2>&1

cd /d "%GOLD_DIR%"
call "%PHP%" artisan config:clear --env=local  >nul 2>&1
call "%PHP%" artisan route:clear  --env=local  >nul 2>&1
call "%PHP%" artisan cache:clear  --env=local  >nul 2>&1
call "%PHP%" artisan view:clear   --env=local  >nul 2>&1
echo [OK]  Caches cleared

echo.
echo ============================================================
echo   5. Launch both dev servers
echo ============================================================

REM Free ports if anything is bound
for /f "tokens=5" %%P in ('netstat -aon 2^>nul ^| findstr ":8000 " ^| findstr LISTENING') do (
  taskkill /F /PID %%P >nul 2>&1
)
for /f "tokens=5" %%P in ('netstat -aon 2^>nul ^| findstr ":8001 " ^| findstr LISTENING') do (
  taskkill /F /PID %%P >nul 2>&1
)

cd /d "%MONO_DIR%"
start "Musoftwares :8000" cmd /k ""%PHP%" artisan serve --env=local --host=127.0.0.1 --port=8000"

cd /d "%GOLD_DIR%"
start "GoldSaverSys :8001" cmd /k ""%PHP%" artisan serve --env=local --host=127.0.0.1 --port=8001"

echo Waiting 5 seconds for servers to bind...
timeout /t 5 /nobreak >nul

echo.
echo ============================================================
echo   6. HTTP smoke tests
echo ============================================================

powershell -NoProfile -Command ^
  "try { $r1=Invoke-WebRequest -Uri '%MONO_URL%' -UseBasicParsing -TimeoutSec 10; Write-Host ('[OK]  Musoftwares  -> HTTP '+$r1.StatusCode) -ForegroundColor Green } catch { Write-Host ('[ERR] Musoftwares  -> '+$_.Exception.Message) -ForegroundColor Red }; ^
   try { $r2=Invoke-WebRequest -Uri '%GOLD_URL%' -UseBasicParsing -TimeoutSec 10; Write-Host ('[OK]  GoldSaverSys -> HTTP '+$r2.StatusCode) -ForegroundColor Green } catch { Write-Host ('[ERR] GoldSaverSys -> '+$_.Exception.Message) -ForegroundColor Red }"

echo.
echo ============================================================
echo   7. Subscription-sync diagnostic (requires at least one user)
echo ============================================================

cd /d "%GOLD_DIR%"
echo require base_path("scripts/diag_subscribe.php"); | "%PHP%" artisan tinker --env=local --no-interaction

echo.
echo ============================================================
echo   8. Done.
echo ============================================================
echo   Musoftwares    : %MONO_URL%
echo   GoldSaverSys   : %GOLD_URL%
echo.
echo   Close this window OR the server windows to stop them.
echo ============================================================

if not "%NO_BROWSER%"=="1" (
  start "" "%MONO_URL%"
  start "" "%GOLD_URL%"
)

pause
endlocal
exit /b 0

REM ---------------------------------------------------------------------------
:ensure_env
set "FILE=%~1"
set "KV=%~2"
if not exist "%FILE%" (
  echo [WARN] %FILE% missing
  exit /b 0
)
findstr /b /c:"%~2" "%FILE%" >nul 2>&1
if errorlevel 1 (
  echo.>> "%FILE%"
  echo %KV%>> "%FILE%"
  echo [SET]  %KV%
) else (
  echo [HAVE] %KV%
)
exit /b 0
