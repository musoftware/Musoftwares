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
Write-Host "=== Run Migrations and Schema Sync ===" -ForegroundColor Cyan
Write-Host "Server: $SSH_USER@$SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host ""

Write-Host "[1/4] Running local migrations to ensure source of truth..." -ForegroundColor Yellow
php artisan migrate --env=local

Write-Host "[2/4] Exporting local schema structure..." -ForegroundColor Yellow
php artisan schema:export --out="deploy\schema.b64" --env=local

if (-not (Test-Path "deploy\schema.b64")) {
    Write-Host "Error: Schema export failed." -ForegroundColor Red
    exit 1
}

$schemaBase64 = Get-Content "deploy\schema.b64" -Raw

$syncCmd = "cd $REMOTE_PATH && php artisan schema:sync --stdin"
$migrateCmd = "cd $REMOTE_PATH && php artisan migrate --force"
$optimizeCmd = "cd $REMOTE_PATH && php artisan optimize:clear"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "Uploading new schema commands to production..." -ForegroundColor Yellow
    & pscp.exe -P $SSH_PORT -pw $SSH_PASSWORD app/Console/Commands/SchemaExportCommand.php "$SSH_USER@$SSH_HOST`:$REMOTE_PATH/app/Console/Commands/"
    & pscp.exe -P $SSH_PORT -pw $SSH_PASSWORD app/Console/Commands/SchemaSyncCommand.php "$SSH_USER@$SSH_HOST`:$REMOTE_PATH/app/Console/Commands/"

    Write-Host "[3/4] Running remote schema sync via PuTTY (plink)..." -ForegroundColor Yellow
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    # Save the base64 string to a temp file, then type it to plink
    $tempFile = [System.IO.Path]::GetTempFileName()
    Set-Content -Path $tempFile -Value $schemaBase64 -NoNewline
    $syncPlink = "type ""$tempFile"" | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$syncCmd"""
    cmd.exe /c $syncPlink
    Remove-Item -Path $tempFile -ErrorAction SilentlyContinue

    Write-Host "[4/5] Running migrations on remote server..." -ForegroundColor Yellow
    $migratePlink = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$migrateCmd"""
    cmd.exe /c $migratePlink

    Write-Host "[5/5] Running optimize:clear on remote server..." -ForegroundColor Yellow
    $optimizePlink = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$optimizeCmd"""
    cmd.exe /c $optimizePlink
} else {
    Write-Host "[3/4] Running remote schema sync via SSH..." -ForegroundColor Yellow
    $tempFile = [System.IO.Path]::GetTempFileName()
    Set-Content -Path $tempFile -Value $schemaBase64 -NoNewline
    cmd.exe /c "type ""$tempFile"" | ssh -p $SSH_PORT -o StrictHostKeyChecking=no ""$SSH_USER@$SSH_HOST"" ""$syncCmd"""
    Remove-Item -Path $tempFile -ErrorAction SilentlyContinue

    Write-Host "[4/5] Running migrations on remote server..." -ForegroundColor Yellow
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $migrateCmd

    Write-Host "[5/5] Running optimize:clear on remote server..." -ForegroundColor Yellow
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $optimizeCmd
}

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Schema Sync, Migrations, and Optimize:Clear completed successfully!" -ForegroundColor Green
