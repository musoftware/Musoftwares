# Deploy Build Script - PowerShell
# Build npm على السيرفر عبر SSH

param(
    [switch]$NoPassword
)

# قراءة ملف الاعدادات
$configFile = Join-Path $PSScriptRoot ".ssh-config"
$config = @{}

if (-not (Test-Path $configFile)) {
    Write-Host "خطأ: ملف الاعدادات غير موجود" -ForegroundColor Red
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
Write-Host "=== Deploy Build ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Username: $SSH_USER" -ForegroundColor Gray
Write-Host "Server: $SSH_HOST`:$SSH_PORT" -ForegroundColor Gray
Write-Host "Path: $REMOTE_PATH" -ForegroundColor Gray
Write-Host ""

# إعداد وتثبيت Node.js إذا لم يكن موجوداً ثم البناء
$setupNode = 'export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"; if ! command -v npm &> /dev/null; then echo "Node.js not found. Installing NVM and Node 20..."; curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash; export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"; nvm install 20; nvm use 20; sleep 3; fi;'
$command = "bash -c `"$setupNode cd $REMOTE_PATH && sed -i 's/C:..tools..php83..php.exe/php/g' package.json && sleep 2 && npm install --legacy-peer-deps --no-audit --no-fund --loglevel info && npm run build`""

Write-Host "Command: $command" -ForegroundColor Cyan
Write-Host ""
Write-Host "Connecting..." -ForegroundColor Cyan
Write-Host ""

# اختبر sshpass
$hasSshpass = $null -ne (Get-Command sshpass -ErrorAction SilentlyContinue)

if ($hasSshpass -and $SSH_PASSWORD -and -not $NoPassword) {
    $env:SSHPASS = $SSH_PASSWORD
    & sshpass -e ssh -p $SSH_PORT -o StrictHostKeyChecking=no -o PubkeyAuthentication=no "$SSH_USER@$SSH_HOST" $command
    if ($env:SSHPASS) {
        Remove-Item env:SSHPASS -ErrorAction SilentlyContinue
    }
}
else {
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $command
}

# النتيجة
Write-Host ""
if ($LASTEXITCODE -eq 0) {
    Write-Host "=== SUCCESS ===" -ForegroundColor Green
    Write-Host "Build completed!" -ForegroundColor Green
}
else {
    Write-Host "=== FAILED ===" -ForegroundColor Red
    Write-Host "Build failed (exit code: $LASTEXITCODE)" -ForegroundColor Red
    exit 1
}
