# ==============================================================================
# Comprehensive E2E Playwright Action and Route Test Engine
# Automated GET, POST, Form Submissions and Dynamic Exploration
# ==============================================================================

param(
    [ValidateSet("all", "public", "client", "admin", "erp", "crm", "booking", "actions")]
    [string]$Module = "all",

    [switch]$Headed,
    [switch]$UI,
    [switch]$Debug,
    [int]$Port = 8000,
    [string]$Filter = "",
    [int]$Workers = 1,
    [int]$Retries = 0,
    [switch]$RestartServer
)

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT

Write-Host ""
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "         Musoftwares - Intelligent Playwright E2E Action Suite    " -ForegroundColor Cyan
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "  Target Server  : http://127.0.0.1:$Port" -ForegroundColor Gray
Write-Host "  Target Module  : $Module" -ForegroundColor Gray
Write-Host "  Headed Mode    : $(if ($Headed) { 'ON (Visible Browser)' } else { 'OFF (Headless)' })" -ForegroundColor Gray
Write-Host "  UI Inspector   : $(if ($UI) { 'ON' } else { 'OFF' })" -ForegroundColor Gray
Write-Host ""

# 1. Detect PHP 8.3 binary
$PHP_BIN = "php"
if (Test-Path "C:\tools\php83\php.exe") {
    $PHP_BIN = "C:\tools\php83\php.exe"
    $env:PATH = "C:\tools\php83;" + $env:PATH
} elseif ((Get-Command php -ErrorAction SilentlyContinue)) {
    $PHP_BIN = (Get-Command php).Source
}

# 2. Prepare & Verify Database
Write-Host "[1/4] Preparing and verifying database environment..." -ForegroundColor Yellow
$dbStatus = "OFFLINE"
try {
    $dbCheck = cmd.exe /c "`"$PHP_BIN`" -r `"try { require 'vendor/autoload.php'; `$app = require 'bootstrap/app.php'; `$kernel = `$app->make(Illuminate\Contracts\Console\Kernel::class); `$kernel->bootstrap(); DB::connection()->getPdo(); echo 'ONLINE'; } catch (Exception `$e) { echo 'OFFLINE'; }`""
    if ($dbCheck -match "ONLINE") { $dbStatus = "ONLINE" }
} catch {
    $dbStatus = "OFFLINE"
}

if ($dbStatus -eq "ONLINE") {
    Write-Host "  -> MySQL Connection is ONLINE." -ForegroundColor Green
} else {
    Write-Host "  -> MySQL is offline. Enabling SQLite Database mode for full local E2E actions..." -ForegroundColor Cyan
    $env:DB_CONNECTION = "sqlite"
    $env:DB_DATABASE = "$PROJECT_ROOT\database\database.sqlite"
    $env:SESSION_DRIVER = "file"
    cmd.exe /c "`"$PHP_BIN`" scripts/prepare_sqlite_e2e.php"
}

# 3. Dynamic Route Manifest & Test Users Seeding
Write-Host "[2/4] Discovering routes and seeding test credentials..." -ForegroundColor Yellow
cmd.exe /c "`"$PHP_BIN`" scripts/generate_e2e_manifest.php"
cmd.exe /c "`"$PHP_BIN`" scripts/ensure_e2e_users.php"

# 4. Check / Start Local Web Server
Write-Host "[3/4] Checking local web server status on port $Port..." -ForegroundColor Yellow
$serverAlreadyRunning = $false
$tcpConnection = New-Object System.Net.Sockets.TcpClient
try {
    $tcpConnection.Connect("127.0.0.1", $Port)
    $serverAlreadyRunning = $true
    $tcpConnection.Close()
} catch {}

if ($RestartServer -or ($dbStatus -ne "ONLINE" -and $serverAlreadyRunning)) {
    Write-Host "  -> Refreshing test server with active database configuration..." -ForegroundColor DarkGray
    Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    $serverAlreadyRunning = $false
    Start-Sleep -Seconds 1
}

if ($serverAlreadyRunning) {
    Write-Host "  -> Local web server is active on port $Port." -ForegroundColor Green
} else {
    Write-Host "  -> Launching local Laravel web server on port $Port..." -ForegroundColor DarkGray
}

$serverProcess = $null
$testExitCode = 0
$startTime = Get-Date

try {
    if (-not $serverAlreadyRunning) {
        $serveArgs = "artisan serve --host=127.0.0.1 --port=$Port"
        if ($dbStatus -ne "ONLINE") {
            $serveArgs += " --env=testing"
        }
        $serverProcess = Start-Process "$PHP_BIN" -ArgumentList $serveArgs -PassThru -NoNewWindow
        Start-Sleep -Seconds 3
    }

    # 5. Build Playwright Arguments
    Write-Host "[4/4] Executing Intelligent Playwright Action Engine..." -ForegroundColor Yellow
    $pwArgs = @("test", "tests/E2E/intelligent_actions.spec.ts")

    if ($UI) {
        $pwArgs += "--ui"
    } elseif ($Headed) {
        $pwArgs += "--headed"
    }

    if ($Debug) {
        $pwArgs += "--debug"
    }

    if ($Workers -gt 0) {
        $pwArgs += "--workers=$Workers"
    }

    if ($Retries -gt 0) {
        $pwArgs += "--retries=$Retries"
    }

    # Module filtering
    if ($Module -eq "public") {
        $pwArgs += @("-g", "Public Route")
    } elseif ($Module -eq "client") {
        $pwArgs += @("-g", "Client")
    } elseif ($Module -eq "admin") {
        $pwArgs += @("-g", "Admin")
    } elseif ($Filter -ne "") {
        $pwArgs += @("-g", "`"$Filter`"")
    }

    $pwCmd = "npx playwright " + ($pwArgs -join " ")
    Write-Host "  -> Running command: $pwCmd" -ForegroundColor DarkGray
    Write-Host ""

    cmd.exe /c $pwCmd
    $testExitCode = $LASTEXITCODE

} finally {
    if (-not $serverAlreadyRunning -and $serverProcess) {
        Write-Host ""
        Write-Host "-> Stopping local Laravel test server (PID: $($serverProcess.Id))..." -ForegroundColor DarkGray
        Stop-Process -Id $serverProcess.Id -Force -ErrorAction SilentlyContinue
    }
}

$duration = [math]::Round(((Get-Date) - $startTime).TotalSeconds, 1)

Write-Host ""
if ($testExitCode -eq 0) {
    Write-Host "==================================================================" -ForegroundColor Green
    Write-Host "  [SUCCESS] ALL PLAYWRIGHT ACTIONS AND ROUTES PASSED in ${duration}s" -ForegroundColor Green
    Write-Host "==================================================================" -ForegroundColor Green
} else {
    Write-Host "==================================================================" -ForegroundColor Red
    Write-Host "  [FAILURE] PLAYWRIGHT TEST SUITE FAILED (Code: $testExitCode) in ${duration}s" -ForegroundColor Red
    Write-Host "  Please review the test output or inspect Playwright trace logs." -ForegroundColor Red
    Write-Host "==================================================================" -ForegroundColor Red
}

exit $testExitCode
