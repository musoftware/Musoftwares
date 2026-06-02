# Deploy Migrate Script - PowerShell
# Run database migrations on the remote server automatically

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

Write-Host ""
Write-Host "=== Run Migrations (Remote) ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

# Command to run migrations
$migrateCmd = "cd $REMOTE_PATH && php artisan migrate --force"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[1/1] Running migrations via PuTTY (plink) automatically..." -ForegroundColor Yellow
    
    # Accept host key automatically if not cached
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    # Use -batch and -T to disable interactive prompts and pseudo-terminal allocation
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$migrateCmd"""
    cmd.exe /c $plinkCommand
} else {
    Write-Host "[1/1] Running migrations on server..." -ForegroundColor Yellow
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $migrateCmd
}

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Migrations completed successfully!" -ForegroundColor Green
