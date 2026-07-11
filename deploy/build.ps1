# Deploy Build Script - PowerShell
# Build locally, zip, and upload automatically to avoid server memory limits

param(
    [switch]$NoPassword
)

# Read config
$configFile = Join-Path $PSScriptRoot ".ssh-config"
$config = @{}

if (-not (Test-Path $configFile)) {
    Write-Host "Error: .ssh-config file not found" -ForegroundColor Red
    exit 1
}

Get-Content $configFile | Where-Object { $_ -notmatch "^#" -and $_ -notmatch "^$" } | ForEach-Object {
    $parts = $_ -split "=", 2
    if ($parts.Count -eq 2) {
        $config[$parts[0].Trim()] = $parts[1].Trim().Trim('"')
    }
}

$SSH_USER = $config['SSH_USER']
$SSH_HOST = $config['SSH_HOST']
$SSH_PORT = $config['SSH_PORT']
if ([string]::IsNullOrEmpty($SSH_PORT)) { $SSH_PORT = 22 }
$REMOTE_PATH = $config['REMOTE_PATH']
$SSH_PASSWORD = $config['SSH_PASSWORD']

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
$BUILD_DIR = "$PROJECT_ROOT\public\build"
$uniqueId = Get-Date -Format "yyyyMMddHHmmss"
$ZIP_PATH = "$PROJECT_ROOT\build_$uniqueId.tar.gz"

Write-Host ""
Write-Host "=== Deploy Build (Local to Remote) ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

# 1. Static Analysis
Write-Host "[1/5] Running static analysis..." -ForegroundColor Yellow
Set-Location $PROJECT_ROOT

Write-Host "-> Checking PHP Code (PHPStan)..." -ForegroundColor DarkGray
cmd.exe /c "C:\tools\php83\php.exe vendor\bin\phpstan analyse --memory-limit=2G"
if ($LASTEXITCODE -ne 0) {
    Write-Host "PHPStan check failed! Upload aborted. Please fix the PHP errors." -ForegroundColor Red
    exit 1
}

Write-Host "-> Checking TypeScript..." -ForegroundColor DarkGray
cmd.exe /c "npx tsc --noEmit"
if ($LASTEXITCODE -ne 0) {
    Write-Host "TypeScript check failed! Upload aborted." -ForegroundColor Red
    exit 1
}

Write-Host "-> Checking Translations..." -ForegroundColor DarkGray
cmd.exe /c "C:\tools\php83\php.exe artisan translations:check"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Translation check failed! Upload aborted. Please complete all missing translations." -ForegroundColor Red
    exit 1
}

Write-Host "-> Checking ESLint..." -ForegroundColor DarkGray
cmd.exe /c "npm run lint -- --fix"
if ($LASTEXITCODE -ne 0) {
    Write-Host "ESLint check failed! Attempting auto-fix script..." -ForegroundColor Yellow
    cmd.exe /c "npm run lint -- --format json -o lint-results.json"
    cmd.exe /c "node fix_lint.cjs"
    Write-Host "-> Re-checking ESLint after auto-fix..." -ForegroundColor DarkGray
    cmd.exe /c "npm run lint -- --fix"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ESLint check still failed after auto-fix! But continuing upload..." -ForegroundColor Red
    }
}

# 2. Local Build
Write-Host "[2/5] Running local npm build..." -ForegroundColor Yellow
$originalPath = $env:PATH
$env:PATH = "C:\tools\php83;" + $env:PATH
cmd.exe /c "npm run build"
$env:PATH = $originalPath
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error in local build! Upload aborted." -ForegroundColor Red
    exit 1
}

# 2.5 Run E2E Smoke & Console Error Tests
Write-Host "[2.5/5] Running E2E Smoke / Console Error Tests..." -ForegroundColor Yellow

$dbStatus = "OFFLINE"
try {
    $dbStatus = cmd.exe /c "C:\tools\php83\php.exe -r `"try { require 'vendor/autoload.php'; `$app = require 'bootstrap/app.php'; `$kernel = `$app->make(Illuminate\Contracts\Console\Kernel::class); `$kernel->bootstrap(); DB::connection()->getPdo(); echo 'ONLINE'; } catch (Exception `$e) { echo 'OFFLINE'; }`""
} catch {
    $dbStatus = "OFFLINE"
}

if ($dbStatus -eq "ONLINE") {
    Write-Host "-> Database connection is ONLINE. Preparing E2E test users..." -ForegroundColor DarkGray
    cmd.exe /c "C:\tools\php83\php.exe scripts/ensure_e2e_users.php"

    $serverAlreadyRunning = $false
    $tcpConnection = New-Object System.Net.Sockets.TcpClient
    try {
        $tcpConnection.Connect("127.0.0.1", 8000)
        $serverAlreadyRunning = $true
        $tcpConnection.Close()
        Write-Host "-> Local web server is already running on port 8000." -ForegroundColor DarkGray
    } catch {
        # Not running
    }

    $serverProcess = $null
    $testExitCode = 0
    try {
        if (-not $serverAlreadyRunning) {
            Write-Host "-> Starting local Laravel server on port 8000..." -ForegroundColor DarkGray
            $serverProcess = Start-Process "C:\tools\php83\php.exe" -ArgumentList "artisan serve --host=127.0.0.1 --port=8000 --env=local" -PassThru -NoNewWindow
            Start-Sleep -Seconds 3
        }

        Write-Host "-> Running Playwright console error test suite..." -ForegroundColor DarkGray
        cmd.exe /c "npx playwright test tests/E2E/console_errors.spec.ts"
        $testExitCode = $LASTEXITCODE
    } finally {
        if (-not $serverAlreadyRunning -and $serverProcess) {
            Write-Host "-> Stopping local Laravel server..." -ForegroundColor DarkGray
            Stop-Process -Id $serverProcess.Id -Force -ErrorAction SilentlyContinue
        }
    }

    if ($testExitCode -ne 0) {
        Write-Host "E2E Smoke / Console Error check failed! Upload aborted. Please fix the console errors shown above." -ForegroundColor Red
        exit 1
    } else {
        Write-Host "-> E2E Smoke / Console Error check passed successfully." -ForegroundColor Green
    }
} else {
    Write-Host "WARNING: Database connection is OFFLINE. Skipping E2E console error verification tests!" -ForegroundColor Yellow
}

# 3. Archive Files (Using tar.exe which is faster and avoids lock bugs)
Write-Host "[3/5] Archiving build files..." -ForegroundColor Yellow
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }
# Compress contents of public/build into a tar.gz file
cmd.exe /c "tar.exe -czf build_$uniqueId.tar.gz -C public/build ."

# 4 & 5. Upload & Extract
$timestamp = $uniqueId
$remoteZip = "$REMOTE_PATH/public/build_$uniqueId.tar.gz"
$unzipCmd = "cd $REMOTE_PATH/public && mkdir -p build_$timestamp && tar -xzf build_$uniqueId.tar.gz -C build_$timestamp/ && rsync -a --delete build_$timestamp/ build/ && rm -rf build_$timestamp build_$uniqueId.tar.gz"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[4/5] Uploading via PuTTY (pscp) automatically..." -ForegroundColor Yellow
    # Accept host key automatically if not cached (no -batch here to allow echo y)
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    # Upload (using -sftp for safer binary transfer)
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    if ($LASTEXITCODE -ne 0) { Write-Host "Upload failed!" -ForegroundColor Red; exit 1 }
    
    Write-Host "[5/5] Extracting via PuTTY (plink) automatically..." -ForegroundColor Yellow
    # Use -batch and -T to disable interactive prompts and pseudo-terminal allocation
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$unzipCmd"""
    cmd.exe /c $plinkCommand
    if ($LASTEXITCODE -ne 0) { Write-Host "Extraction failed!" -ForegroundColor Red; exit 1 }
}
else {
    Write-Host "[4/5] Uploading build_$uniqueId.tar.gz to server..." -ForegroundColor Yellow
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    if ($LASTEXITCODE -ne 0) { Write-Host "Upload failed!" -ForegroundColor Red; exit 1 }
    
    Write-Host "[5/5] Extracting on server..." -ForegroundColor Yellow
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $unzipCmd
    if ($LASTEXITCODE -ne 0) { Write-Host "Extraction failed!" -ForegroundColor Red; exit 1 }
}

# Clean local archive
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Build and upload completed successfully without prompts!" -ForegroundColor Green
