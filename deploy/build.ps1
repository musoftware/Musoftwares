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
$ZIP_PATH = "$PROJECT_ROOT\build.tar.gz"

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

# 2. Archive Files (Using tar.exe which is faster and avoids lock bugs)
Write-Host "[2/4] Archiving build files..." -ForegroundColor Yellow
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }
# Compress contents of public/build into a tar.gz file
cmd.exe /c "tar.exe -czf build.tar.gz -C public/build ."

# 3 & 4. Upload & Extract
$remoteZip = "$REMOTE_PATH/public/build.tar.gz"
$unzipCmd = "cd $REMOTE_PATH/public && rm -rf build/* && mkdir -p build && tar -xzf build.tar.gz -C build/ && rm build.tar.gz"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[3/4] Uploading via PuTTY (pscp) automatically..." -ForegroundColor Yellow
    # Accept host key automatically if not cached (no -batch here to allow echo y)
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    # Upload (using -sftp for safer binary transfer)
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    
    Write-Host "[4/4] Extracting via PuTTY (plink) automatically..." -ForegroundColor Yellow
    # Use -batch and -T to disable interactive prompts and pseudo-terminal allocation
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$unzipCmd"""
    cmd.exe /c $plinkCommand
} else {
    Write-Host "[3/4] Uploading build.tar.gz to server..." -ForegroundColor Yellow
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    
    Write-Host "[4/4] Extracting on server..." -ForegroundColor Yellow
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $unzipCmd
}

# Clean local archive
if (Test-Path $ZIP_PATH) { Remove-Item $ZIP_PATH -Force }

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Build and upload completed successfully without prompts!" -ForegroundColor Green
