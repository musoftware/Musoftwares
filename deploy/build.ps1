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
$ZIP_PATH = "$PROJECT_ROOT\build.zip"

Write-Host ""
Write-Host "=== Deploy Build (Local to Remote) ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

# 1. Local Build
Write-Host "[1/4] Running local npm build..." -ForegroundColor Yellow
Set-Location $PROJECT_ROOT
cmd.exe /c "npm run build"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error in local build" -ForegroundColor Red
    exit 1
}

# 2. Zip Files
Write-Host "[2/4] Zipping build files..." -ForegroundColor Yellow
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }
Compress-Archive -Path "$BUILD_DIR\*" -DestinationPath $ZIP_PATH -Force

# 3. Upload Zip
Write-Host "[3/4] Uploading build.zip to server..." -ForegroundColor Yellow
$remoteZip = "$REMOTE_PATH/public/build.zip"

$hasSshpass = $null -ne (Get-Command sshpass -ErrorAction SilentlyContinue)
if ($hasSshpass -and $SSH_PASSWORD -and -not $NoPassword) {
    $env:SSHPASS = $SSH_PASSWORD
    & sshpass -e scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
} else {
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
}

# 4. Extract on Server
Write-Host "[4/4] Extracting on server..." -ForegroundColor Yellow
$unzipCmd = "cd $REMOTE_PATH/public && rm -rf build/* && unzip -o build.zip -d build/ && rm build.zip"

if ($hasSshpass -and $SSH_PASSWORD -and -not $NoPassword) {
    & sshpass -e ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $unzipCmd
    if ($env:SSHPASS) { Remove-Item env:SSHPASS -ErrorAction SilentlyContinue }
} else {
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $unzipCmd
}

# Clean local zip
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Build and upload completed successfully!" -ForegroundColor Green
