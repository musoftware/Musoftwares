# Deploy Runtime Script - PowerShell
# Updates version metadata and uploads the zipped runtime + latest.json to the server

param(
    [switch]$NoPassword
)

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
$JSON_PATH = "$PROJECT_ROOT\public\downloads\runtime\latest.json"
$CONTROLLER_PATH = "$PROJECT_ROOT\app\Http\Controllers\RuntimeDownloadController.php"
$ZIP_PATH = "$PROJECT_ROOT\public\downloads\runtime\windows\musoftware-runtime-win.zip"

$datePrefix = Get-Date -Format "yyyy.MM.dd"
$currentVersion = "$datePrefix.0"

if (Test-Path $JSON_PATH) {
    $json = Get-Content $JSON_PATH | ConvertFrom-Json
    $lastVersion = $json.version
    
    # Extract date prefix and minor count
    if ($lastVersion -match "^(\d{4}\.\d{2}\.\d{2})\.(\d+)$") {
        $lastPrefix = $matches[1]
        $lastMinor = [int]$matches[2]
        
        if ($lastPrefix -eq $datePrefix) {
            $nextMinor = $lastMinor + 1
            $currentVersion = "$datePrefix.$nextMinor"
        }
    }
}

$newVersion = $currentVersion

Write-Host "=== Deploy Musoftware Runtime ===" -ForegroundColor Cyan
Write-Host "Auto-generated Version: $newVersion" -ForegroundColor Yellow

# Check if ZIP exists
if (-not (Test-Path $ZIP_PATH)) {
    Write-Host "Error: Runtime ZIP not found at $ZIP_PATH" -ForegroundColor Red
    Write-Host "Please build the runtime first using build-runtime.bat!" -ForegroundColor Red
    exit 1
}

# 2. Update Version Metadata
if (-not [string]::IsNullOrWhiteSpace($newVersion)) {
    # Update latest.json
    if (Test-Path $JSON_PATH) {
        Write-Host "-> Updating latest.json..." -ForegroundColor DarkGray
        $json = Get-Content $JSON_PATH | ConvertFrom-Json
        $json.version = $newVersion
        $json.latest = $newVersion
        $json.released_at = (Get-Date -Format "yyyy-MM-dd")
        $json | ConvertTo-Json -Depth 10 | Set-Content $JSON_PATH -Encoding UTF8
    } else {
        Write-Host "Warning: latest.json not found at $JSON_PATH" -ForegroundColor Yellow
    }

    # Update Controller
    if (Test-Path $CONTROLLER_PATH) {
        Write-Host "-> Updating RuntimeDownloadController.php..." -ForegroundColor DarkGray
        $controllerContent = Get-Content $CONTROLLER_PATH -Raw
        $controllerContent = $controllerContent -replace "'version'\s*=>\s*'[0-9\.]+'", "'version' => '$newVersion'"
        Set-Content -Path $CONTROLLER_PATH -Value $controllerContent -Encoding UTF8
    } else {
        Write-Host "Warning: RuntimeDownloadController.php not found." -ForegroundColor Yellow
    }
}

# 3. Read config
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
Write-Host ""

# 4. Upload Files
$remoteZip = "$REMOTE_PATH/public/downloads/runtime/windows/musoftware-runtime-win.zip"
$remoteJson = "$REMOTE_PATH/public/downloads/runtime/latest.json"

# Ensure remote directories exist (just in case, though they usually do)
$mkdirCmd = "mkdir -p $REMOTE_PATH/public/downloads/runtime/windows"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[Uploading via PuTTY (pscp) automatically...]" -ForegroundColor Yellow
    # Accept host key automatically
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
    
    # Create dir if not exists
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$mkdirCmd"""
    cmd.exe /c $plinkCommand

    Write-Host "-> Uploading musoftware-runtime-win.zip..." -ForegroundColor DarkGray
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    
    Write-Host "-> Uploading latest.json..." -ForegroundColor DarkGray
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $JSON_PATH "$SSH_USER@$SSH_HOST`:$remoteJson"
}
else {
    Write-Host "[Uploading via standard SSH (scp)...]" -ForegroundColor Yellow
    
    # Create dir if not exists
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $mkdirCmd

    Write-Host "-> Uploading musoftware-runtime-win.zip..." -ForegroundColor DarkGray
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:$remoteZip"
    
    Write-Host "-> Uploading latest.json..." -ForegroundColor DarkGray
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $JSON_PATH "$SSH_USER@$SSH_HOST`:$remoteJson"
}

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Runtime uploaded successfully!" -ForegroundColor Green
