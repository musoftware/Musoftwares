# Deploy Build Script - PowerShell
# Zips and uploads the public/build folder (compiled JS/CSS assets) to the server

param(
    [switch]$NoPassword
)

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
$BUILD_PATH = "$PROJECT_ROOT\public\build"
$STAGING_PARENT = Join-Path $env:TEMP "musoftwares-deploy"
$STAGING_DIR = Join-Path $STAGING_PARENT "contents"
$ZIP_PATH = Join-Path $STAGING_PARENT "build.zip"

Write-Host "=== Deploy Musoftware Build ===" -ForegroundColor Cyan
Write-Host "Source: $BUILD_PATH" -ForegroundColor Yellow

if (-not (Test-Path $BUILD_PATH)) {
    Write-Host "Error: Build folder not found at $BUILD_PATH" -ForegroundColor Red
    Write-Host "Run 'npm run build' first to generate the compiled assets." -ForegroundColor Red
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
Write-Host "Remote: $REMOTE_PATH/public/build/" -ForegroundColor Gray
Write-Host ""

# Stage build folder as 'build/<contents>' so it unzips directly into public/build
if (Test-Path $STAGING_PARENT) {
    Remove-Item -LiteralPath $STAGING_PARENT -Recurse -Force
}
New-Item -ItemType Directory -Path $STAGING_DIR -Force | Out-Null
Copy-Item -Path (Join-Path $BUILD_PATH "*") -Destination $STAGING_DIR -Recurse -Force

# Create zip
if (Test-Path $ZIP_PATH) {
    Remove-Item -LiteralPath $ZIP_PATH -Force
}

Add-Type -AssemblyName "System.IO.Compression.FileSystem"
[System.IO.Compression.ZipFile]::CreateFromDirectory($STAGING_DIR, $ZIP_PATH)

Write-Host "-> Created $ZIP_PATH ($(('{0:N2}' -f ((Get-Item $ZIP_PATH).Length / 1MB))) MB)" -ForegroundColor DarkGray

# Ensure remote directory exists
$remoteBuild = "$REMOTE_PATH/public/build"
$mkdirCmd = "mkdir -p $remoteBuild"

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    Write-Host "[Uploading via PuTTY (pscp) automatically...]" -ForegroundColor Yellow

    # Accept host key automatically
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"

    # Create dir if not exists
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$mkdirCmd"""
    cmd.exe /c $plinkCommand

    Write-Host "-> Uploading build.zip..." -ForegroundColor DarkGray
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $ZIP_PATH "$SSH_USER@$SSH_HOST`:/tmp/build.zip"

    Write-Host "-> Extracting build.zip on remote..." -ForegroundColor DarkGray
    $extractCmd = "cd $REMOTE_PATH/public/build && find . -mindepth 1 -delete && unzip -oq /tmp/build.zip && rm -f /tmp/build.zip && test -f manifest.json && echo OK_MANIFEST || echo MISSING_MANIFEST"
    $plinkExtract = "plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$extractCmd"""
    cmd.exe /c $plinkExtract
}
else {
    Write-Host "[Uploading via standard SSH (scp)...]" -ForegroundColor Yellow

    # Create dir if not exists
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $mkdirCmd

    Write-Host "-> Uploading build.zip..." -ForegroundColor DarkGray
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $ZIP_PATH "$SSH_USER@$SSH_HOST`:/tmp/build.zip"

    Write-Host "-> Extracting build.zip on remote..." -ForegroundColor DarkGray
    $extractCmd = "cd $REMOTE_PATH/public/build && find . -mindepth 1 -delete && unzip -oq /tmp/build.zip && rm -f /tmp/build.zip && test -f manifest.json && echo OK_MANIFEST || echo MISSING_MANIFEST"
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $extractCmd
}

# Cleanup staging
Remove-Item -LiteralPath $STAGING_PARENT -Recurse -Force

Write-Host ""
Write-Host "=== SUCCESS ===" -ForegroundColor Green
Write-Host "Build folder uploaded successfully!" -ForegroundColor Green
