# Deploy Assets Script - PowerShell
# Uploads the public/assets folder to the server

param(
    [switch]$NoPassword
)

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
$ASSETS_PATH = "$PROJECT_ROOT\public\assets"

Write-Host "=== Deploy Musoftware Assets ===" -ForegroundColor Cyan
Write-Host "Source: $ASSETS_PATH" -ForegroundColor Yellow

if (-not (Test-Path $ASSETS_PATH)) {
    Write-Host "Error: Assets folder not found at $ASSETS_PATH" -ForegroundColor Red
    exit 1
}

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

Write-Host ""
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host "Remote: $REMOTE_PATH/public/assets/" -ForegroundColor Gray
Write-Host ""

# Ensure remote directory exists
$remoteAssets = "$REMOTE_PATH/public/assets"
$mkdirCmd = "mkdir -p $remoteAssets"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[Uploading via PuTTY (pscp) automatically...]" -ForegroundColor Yellow

    # Accept host key automatically
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"

    # Create dir if not exists
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$mkdirCmd"""
    cmd.exe /c $plinkCommand

    Write-Host "-> Uploading assets/ (recursive)..." -ForegroundColor DarkGray
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD -r $ASSETS_PATH "$SSH_USER@$SSH_HOST`:$REMOTE_PATH/public/"
}
else {
    Write-Host "[Uploading via standard SSH (scp)...]" -ForegroundColor Yellow

    # Create dir if not exists
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $mkdirCmd

    Write-Host "-> Uploading assets/ (recursive)..." -ForegroundColor DarkGray
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no -r $ASSETS_PATH "$SSH_USER@$SSH_HOST`:$REMOTE_PATH/public/"
}

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Assets uploaded successfully!" -ForegroundColor Green
