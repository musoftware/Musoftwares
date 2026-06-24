# Push Composer Script - PowerShell
# Uploads composer.json and composer.lock to the server and runs composer install

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
$COMPOSER_JSON = Join-Path $PROJECT_ROOT "composer.json"
$COMPOSER_LOCK = Join-Path $PROJECT_ROOT "composer.lock"

Write-Host ""
Write-Host "=== Sync Composer & Install ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

Write-Host "1. Uploading composer.json and composer.lock..." -ForegroundColor Yellow

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    # Accept host key automatically if not cached
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $COMPOSER_JSON "${SSH_USER}@${SSH_HOST}:$REMOTE_PATH/composer.json"
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $COMPOSER_LOCK "${SSH_USER}@${SSH_HOST}:$REMOTE_PATH/composer.lock"
}
else {
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $COMPOSER_JSON "${SSH_USER}@${SSH_HOST}:$REMOTE_PATH/composer.json"
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $COMPOSER_LOCK "${SSH_USER}@${SSH_HOST}:$REMOTE_PATH/composer.lock"
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "Failed to upload composer files." -ForegroundColor Red
    exit 1
}

Write-Host "2. Running 'composer install' on remote server..." -ForegroundColor Yellow

$remoteCmd = "cd $REMOTE_PATH && composer install --optimize-autoloader --no-dev --ignore-platform-reqs"

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$remoteCmd"""
    cmd.exe /c $plinkCommand
}
else {
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $remoteCmd
}

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Composer dependencies updated on the remote server!" -ForegroundColor Green
