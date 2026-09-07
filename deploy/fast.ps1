# Fast Deploy Script - PowerShell
# Bundles changed backend files + compiled frontend build into a single archive
# and uploads in one rapid transfer (~5 to 10 seconds total).
#
# Usage:
#   .\deploy\fast.ps1                 # Auto-detects changes + uploads + clears remote cache
#   .\deploy\fast.ps1 -Build          # Runs 'npm run build' first, then uploads
#   .\deploy\fast.ps1 -PHPOnly        # Only uploads changed PHP/Blade files (skips public/build)
#   .\deploy\fast.ps1 -AssetsOnly     # Only uploads public/build folder
#   .\deploy\fast.ps1 -Commit HEAD    # Uploads files modified in latest git commit
#   .\deploy\fast.ps1 -DryRun         # Lists files that would be uploaded without uploading

param(
    [switch]$Build,
    [switch]$PHPOnly,
    [switch]$AssetsOnly,
    [string]$Commit = "",
    [switch]$DryRun,
    [switch]$NoPassword
)

$ErrorActionPreference = "Stop"
$startTime = Get-Date

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT

function Banner($msg, $color = "Cyan") {
    Write-Host ""
    Write-Host "========================================================" -ForegroundColor $color
    Write-Host "  $msg" -ForegroundColor $color
    Write-Host "========================================================" -ForegroundColor $color
}

function Step($msg) {
    Write-Host "-> $msg" -ForegroundColor Yellow
}

function Pass($msg) {
    Write-Host " [OK] $msg" -ForegroundColor Green
}

function Fail($msg) {
    Write-Host " [FAIL] $msg" -ForegroundColor Red
}

function Info($msg) {
    Write-Host "    $msg" -ForegroundColor DarkGray
}

Banner "Musoftware Fast Deploy"

# 1. Read SSH Config
$configFile = Join-Path $PSScriptRoot ".ssh-config"
if (-not (Test-Path $configFile)) {
    Fail ".ssh-config file not found in deploy/ directory"
    exit 1
}

$config = @{}
Get-Content $configFile | Where-Object { $_ -notmatch "^#" -and $_ -notmatch "^\s*$" } | ForEach-Object {
    $parts = $_ -split "=", 2
    if ($parts.Count -eq 2) {
        $config[$parts[0].Trim()] = $parts[1].Trim().Trim('"')
    }
}

$SSH_USER = $config["SSH_USER"]
$SSH_HOST = $config["SSH_HOST"]
$SSH_PORT = $config["SSH_PORT"]
if ([string]::IsNullOrEmpty($SSH_PORT)) { $SSH_PORT = 22 }
$REMOTE_PATH = $config["REMOTE_PATH"].TrimEnd('/')
$SSH_PASSWORD = $config["SSH_PASSWORD"]

Info "Target : $SSH_USER@${SSH_HOST}:$SSH_PORT"
Info "Remote : $REMOTE_PATH"

# 2. Optional Frontend Build
if ($Build) {
    Step "Compiling frontend assets (npm run build)..."
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        Fail "Frontend build failed! Aborting deploy."
        exit 1
    }
    Pass "Frontend build finished."
}

# 3. Detect Changed Files
$filesToUpload = [System.Collections.Generic.List[string]]::new()
$includeBuildFolder = -not $PHPOnly

if ($AssetsOnly) {
    $includeBuildFolder = $true
} else {
    Step "Scanning for modified files..."
    
    $rawGitFiles = @()
    if ($Commit) {
        $rawGitFiles = & git diff --name-only "$Commit^" "$Commit" 2>$null
    } else {
        # Unstaged + Staged changed files
        $statusOutput = & git status --porcelain 2>$null
        foreach ($line in $statusOutput) {
            if ($line.Length -ge 4) {
                $status = $line.Substring(0, 2).Trim()
                $relPath = $line.Substring(3).Trim().Trim('"')
                if ($status -ne "D") {
                    $rawGitFiles += $relPath
                }
            }
        }
    }

    $excludedPatterns = @(
        "^\.git",
        "^\.agents",
        "^\.specstory",
        "^tests/",
        "^node_modules/",
        "^vendor/",
        "^deploy/",
        "^\.env",
        "\.bat$",
        "\.ps1$",
        "^storage/logs/",
        "^storage/framework/"
    )

    $hasFrontendChanges = $false

    foreach ($file in $rawGitFiles) {
        $normalized = $file -replace '\\', '/'
        $skip = $false
        foreach ($pat in $excludedPatterns) {
            if ($normalized -match $pat) {
                $skip = $true
                break
            }
        }
        if ($skip) { continue }

        if ($normalized -match "^(resources/js/|resources/css/)") {
            $hasFrontendChanges = $true
        }

        if (Test-Path (Join-Path $PROJECT_ROOT $file) -PathType Leaf) {
            $filesToUpload.Add($file)
        }
    }

    if ($hasFrontendChanges -and -not $PHPOnly) {
        $includeBuildFolder = $true
    }
}

if ($includeBuildFolder) {
    $buildDir = Join-Path $PROJECT_ROOT "public\build"
    if (-not (Test-Path $buildDir)) {
        Fail "Build directory not found at $buildDir. Run 'npm run build' or pass -Build flag."
        exit 1
    }
}

if ($filesToUpload.Count -eq 0 -and -not $includeBuildFolder) {
    Write-Host "No modified files detected to upload." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Files to package and deploy:" -ForegroundColor Green
foreach ($f in $filesToUpload) {
    Write-Host "  [+] $f" -ForegroundColor DarkCyan
}
if ($includeBuildFolder) {
    Write-Host "  [+] public/build (compiled frontend assets)" -ForegroundColor Cyan
}

if ($DryRun) {
    Banner "DRY RUN COMPLETE - No files were uploaded." "Magenta"
    exit 0
}

# 4. Stage Archive
Step "Creating fast deployment package..."
$stageDir = Join-Path $env:TEMP "musoftwares-fast-deploy"
$stageContents = Join-Path $stageDir "contents"
$zipFile = Join-Path $stageDir "fast_deploy.zip"

if (Test-Path $stageDir) {
    Remove-Item -LiteralPath $stageDir -Recurse -Force
}
New-Item -ItemType Directory -Path $stageContents -Force | Out-Null

# Copy changed files into staging preserving structure
foreach ($relFile in $filesToUpload) {
    $src = Join-Path $PROJECT_ROOT $relFile
    $dest = Join-Path $stageContents $relFile
    $destFolder = Split-Path $dest -Parent
    if (-not (Test-Path $destFolder)) {
        New-Item -ItemType Directory -Path $destFolder -Force | Out-Null
    }
    Copy-Item -Path $src -Destination $dest -Force
}

# Copy public/build into staging if requested
if ($includeBuildFolder) {
    $buildDest = Join-Path $stageContents "public\build"
    New-Item -ItemType Directory -Path $buildDest -Force | Out-Null
    Copy-Item -Path (Join-Path $PROJECT_ROOT "public\build\*") -Destination $buildDest -Recurse -Force
}

# Compress staging into zip
Add-Type -AssemblyName "System.IO.Compression.FileSystem"
[System.IO.Compression.ZipFile]::CreateFromDirectory($stageContents, $zipFile)
$zipSizeMB = '{0:N2}' -f ((Get-Item $zipFile).Length / 1MB)
Pass "Archive created: $zipFile ($zipSizeMB MB)"

# 5. Single Transfer & Server Extraction
Step "Uploading and extracting on remote server..."

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)
$remoteZip = "/tmp/fast_deploy.zip"

$remoteExtractCmd = "cd $REMOTE_PATH && unzip -oq $remoteZip && rm -f $remoteZip && php artisan optimize:clear"

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    # Accept host key if not cached
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"

    # Upload zip in one go
    & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $zipFile "${SSH_USER}@${SSH_HOST}:$remoteZip"
    if ($LASTEXITCODE -ne 0) {
        Fail "Archive upload failed via pscp."
        exit 1
    }

    # Extract and clear cache
    $plinkCmd = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$remoteExtractCmd"""
    cmd.exe /c $plinkCmd
} else {
    & scp -P $SSH_PORT -o StrictHostKeyChecking=no $zipFile "${SSH_USER}@${SSH_HOST}:$remoteZip"
    if ($LASTEXITCODE -ne 0) {
        Fail "Archive upload failed via scp."
        exit 1
    }

    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $remoteExtractCmd
}

# 6. Cleanup Staging
Remove-Item -LiteralPath $stageDir -Recurse -Force

$elapsed = [math]::Round(((Get-Date) - $startTime).TotalSeconds, 1)

Banner "FAST DEPLOY COMPLETED SUCCESSFULLY in ${elapsed}s" "Green"
Write-Host "Your changes are now live on https://www.musoftwares.com" -ForegroundColor Green
Write-Host ""
