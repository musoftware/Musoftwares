# Push Vendor Script - PowerShell
# Zips local vendor folder and uploads it to the remote server to bypass server memory limits

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
$uniqueId = Get-Date -Format "yyyyMMddHHmmss"
$ZIP_NAME = "vendor_$uniqueId.tar.gz"
$ZIP_PATH = "$PROJECT_ROOT\$ZIP_NAME"

Write-Host ""
Write-Host "=== Sync Vendor Folder (Local to Remote) ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

try {
    # 1. Archive vendor folder
    Write-Host "1. Archiving local vendor folder to $ZIP_NAME..." -ForegroundColor Yellow
    Set-Location $PROJECT_ROOT
    cmd.exe /c "tar.exe --exclude=`.git`* -czf $ZIP_NAME -C vendor ."

    if (-not (Test-Path $ZIP_PATH)) {
        Write-Host "Failed to create vendor archive." -ForegroundColor Red
        exit 1
    }

    $remoteZip = "$REMOTE_PATH/$ZIP_NAME"
    $unzipCmd = "cd $REMOTE_PATH && rm -f bootstrap/cache/*.php && mkdir -p vendor_new && tar -xzf $ZIP_NAME -C vendor_new/ && rm -rf vendor && mv vendor_new vendor && rm -f $ZIP_NAME && php artisan config:cache && php artisan route:cache"

    $hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

    # 2. Upload
    Write-Host "2. Uploading $ZIP_NAME..." -ForegroundColor Yellow

    if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
        # Accept host key automatically if not cached
        cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
        
        & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
        if ($LASTEXITCODE -ne 0) { Write-Host "Upload failed!" -ForegroundColor Red; exit 1 }
        
        Write-Host "3. Extracting and replacing vendor folder on remote server..." -ForegroundColor Yellow
        $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$unzipCmd"""
        cmd.exe /c $plinkCommand
        if ($LASTEXITCODE -ne 0) { Write-Host "Extraction failed!" -ForegroundColor Red; exit 1 }
    }
    else {
        & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
        if ($LASTEXITCODE -ne 0) { Write-Host "Upload failed!" -ForegroundColor Red; exit 1 }
        
        Write-Host "3. Extracting and replacing vendor folder on remote server..." -ForegroundColor Yellow
        & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $unzipCmd
        if ($LASTEXITCODE -ne 0) { Write-Host "Extraction failed!" -ForegroundColor Red; exit 1 }
    }

    Write-Host ""
    Write-Host "=== SUCCESS ===" -ForegroundColor Green
    Write-Host "Vendor dependencies updated on the remote server!" -ForegroundColor Green
}
finally {
    # Clean local archive
    if (Test-Path $ZIP_PATH) { 
        Remove-Item $ZIP_PATH -Force -ErrorAction SilentlyContinue 
    }
}
