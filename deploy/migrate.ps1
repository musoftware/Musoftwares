# Deploy Migrate Script - PowerShell
# Syncs the schema and runs database migrations on the remote server.
# Auth and host-key rules live in deploy/common.ps1 (SSH_KEY preferred, host keys always checked).

param(
    [switch]$NoPassword
)

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT
. "$PSScriptRoot\common.ps1"

$cfg = Read-SshConfig
$sshMode = Resolve-SshMode $cfg -NoPassword:$NoPassword
$REMOTE_PATH = $cfg.RemotePath
$PHP_BIN = Get-LocalPhp

# Pipes text into a remote command's stdin.
function Invoke-RemoteWithInput([string]$inputText, [string]$command) {
    $target = "$($cfg.User)@$($cfg.Host)"
    if ($sshMode -like "putty-*") {
        $auth = Get-PuttyAuthArgs $cfg $sshMode
        $inputText | & plink.exe -batch -T -P $cfg.Port @auth $target $command
        return
    }
    $auth = Get-OpenSshAuthArgs $cfg $sshMode
    $inputText | & ssh -p $cfg.Port @auth $target $command
}

function Stop-OnRemoteError([string]$what) {
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: $what failed on the server." -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "=== Run Migrations and Schema Sync ===" -ForegroundColor Cyan
Write-Host "Server: $($cfg.User)@$($cfg.Host):$($cfg.Port) (SSH mode: $sshMode)" -ForegroundColor Gray
Write-Host ""

Write-Host "[1/5] Running local migrations to ensure source of truth..." -ForegroundColor Yellow
& $PHP_BIN artisan migrate --env=local

Write-Host "[2/5] Exporting local schema structure..." -ForegroundColor Yellow
& $PHP_BIN artisan schema:export --out="deploy\schema.b64" --env=local

if (-not (Test-Path "deploy\schema.b64")) {
    Write-Host "Error: Schema export failed." -ForegroundColor Red
    exit 1
}

$schemaBase64 = Get-Content "deploy\schema.b64" -Raw

Write-Host "Uploading schema commands to production..." -ForegroundColor Yellow
foreach ($cmdFile in @("SchemaExportCommand.php", "SchemaSyncCommand.php")) {
    Send-RemoteFile $cfg $sshMode "app/Console/Commands/$cmdFile" "$REMOTE_PATH/app/Console/Commands/$cmdFile"
    Stop-OnRemoteError "Upload of $cmdFile"
}

Write-Host "[3/5] Running remote schema sync..." -ForegroundColor Yellow
Invoke-RemoteWithInput $schemaBase64 "cd $REMOTE_PATH && php artisan schema:sync --stdin"
Stop-OnRemoteError "schema:sync"

Write-Host "[4/5] Running migrations on remote server..." -ForegroundColor Yellow
Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && php artisan migrate --force"
Stop-OnRemoteError "migrate"

Write-Host "[5/5] Running optimize:clear on remote server..." -ForegroundColor Yellow
Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && php artisan optimize:clear"
Stop-OnRemoteError "optimize:clear"

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Schema Sync, Migrations, and Optimize:Clear completed successfully!" -ForegroundColor Green
