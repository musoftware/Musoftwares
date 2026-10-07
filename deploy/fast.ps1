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
#   .\deploy\fast.ps1 -AllowDirty     # Also deploys uncommitted working-tree changes
#   .\deploy\fast.ps1 -SkipTests      # Skips the local Pest run (asks you to type a confirmation)
#
# Safety gates, in order:
#   1. Refuses uncommitted changes unless -AllowDirty is passed.
#   2. Runs the Pest suite locally. A failure aborts the deploy.
#   3. After upload, shows pending migrations on the server and asks before migrate --force.

param(
    [switch]$Build,
    [switch]$PHPOnly,
    [switch]$AssetsOnly,
    [string]$Commit = "",
    [switch]$DryRun,
    [switch]$NoPassword,
    [switch]$AllowDirty,
    [switch]$SkipTests
)

$ErrorActionPreference = "Stop"
$startTime = Get-Date

$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT
. "$PSScriptRoot\common.ps1"

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
$cfg = Read-SshConfig
$REMOTE_PATH = $cfg.RemotePath

Info "Target : $($cfg.User)@$($cfg.Host):$($cfg.Port)"
Info "Remote : $REMOTE_PATH"

# 2. Refuse uncommitted changes unless -AllowDirty
$dirtyLines = @(& git status --porcelain 2>$null)
if ($dirtyLines.Count -gt 0 -and -not $AllowDirty) {
    Fail "Working tree has $($dirtyLines.Count) uncommitted change(s). Commit them first, or pass -AllowDirty."
    $dirtyLines | Select-Object -First 20 | ForEach-Object { Info $_ }
    exit 1
}
if ($dirtyLines.Count -gt 0 -and $AllowDirty) {
    Write-Host " [WARN] -AllowDirty: uncommitted files will be deployed." -ForegroundColor Yellow
}

# 3. Optional Frontend Build
if ($Build) {
    Step "Compiling frontend assets (npm run build)..."
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        Fail "Frontend build failed! Aborting deploy."
        exit 1
    }
    Pass "Frontend build finished."
}

# 4. Detect Changed Files
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
        # Unstaged + staged changes (empty unless -AllowDirty was passed)
        foreach ($line in $dirtyLines) {
            if ($line.Length -ge 4) {
                $status = $line.Substring(0, 2).Trim()
                $relPath = $line.Substring(3).Trim().Trim('"')
                if ($status -ne "D") {
                    $rawGitFiles += $relPath
                }
            }
        }
        # Also include files from recent commits to ensure newly committed assets are deployed
        $recentCommitFiles = & git diff --name-only HEAD~2 HEAD 2>$null
        if ($recentCommitFiles) {
            $rawGitFiles += $recentCommitFiles
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
        $file = $file.Trim().Trim("`r", "`n")
        if ([string]::IsNullOrWhiteSpace($file)) { continue }
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
            if (-not $filesToUpload.Contains($file)) {
                $filesToUpload.Add($file)
            }
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

$migrationFiles = @($filesToUpload | Where-Object { ($_ -replace '\\', '/') -match '(^|/)database/migrations/[^/]+\.php$' })
if ($migrationFiles.Count -gt 0) {
    Write-Host ""
    Write-Host "Migration files in this upload:" -ForegroundColor Yellow
    foreach ($m in $migrationFiles) {
        Write-Host "  [M] $m" -ForegroundColor Yellow
    }
}

if ($DryRun) {
    Banner "DRY RUN COMPLETE - No files were uploaded." "Magenta"
    exit 0
}

# 5. Run the test suite locally (or confirm skipping it)
Assert-TestsOrConfirmedSkip $PROJECT_ROOT -SkipTests:$SkipTests

# 6. Stage Archive
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

    # Auto-sanitize UTF-8 BOM if present
    $fileBytes = [System.IO.File]::ReadAllBytes($src)
    if ($fileBytes.Length -ge 3 -and $fileBytes[0] -eq 0xEF -and $fileBytes[1] -eq 0xBB -and $fileBytes[2] -eq 0xBF) {
        Write-Host "  [!] Stripping UTF-8 BOM from $relFile" -ForegroundColor Yellow
        $cleanBytes = $fileBytes[3..($fileBytes.Length - 1)]
        [System.IO.File]::WriteAllBytes($dest, $cleanBytes)
    } else {
        Copy-Item -Path $src -Destination $dest -Force
    }
}

# Copy public/build into staging if requested
if ($includeBuildFolder) {
    $buildDest = Join-Path $stageContents "public\build"
    New-Item -ItemType Directory -Path $buildDest -Force | Out-Null
    Copy-Item -Path (Join-Path $PROJECT_ROOT "public\build\*") -Destination $buildDest -Recurse -Force
}

# Compress staging into zip (uses tar.exe for POSIX forward slash paths)
if ($null -ne (Get-Command tar.exe -ErrorAction SilentlyContinue)) {
    & tar.exe -a -cf $zipFile -C $stageContents .
} else {
    Add-Type -AssemblyName "System.IO.Compression.FileSystem"
    [System.IO.Compression.ZipFile]::CreateFromDirectory($stageContents, $zipFile)
}
$zipSizeMB = '{0:N2}' -f ((Get-Item $zipFile).Length / 1MB)
Pass "Archive created: $zipFile ($zipSizeMB MB)"

# 7. Single Transfer & Server Extraction
Step "Uploading and extracting on remote server..."

$sshMode = Resolve-SshMode $cfg -NoPassword:$NoPassword
Info "SSH mode: $sshMode"
$remoteZip = "/tmp/fast_deploy.zip"

Send-RemoteFile $cfg $sshMode $zipFile $remoteZip
if ($LASTEXITCODE -ne 0) {
    Fail "Archive upload failed."
    exit 1
}

Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && unzip -oq $remoteZip && rm -f $remoteZip" | ForEach-Object { Info $_ }
if ($LASTEXITCODE -ne 0) {
    Fail "Remote extraction failed."
    exit 1
}
Pass "Files extracted on server."

# 8. Show pending migrations and ask before migrate --force
Step "Checking pending migrations on the server..."
$statusOutput = @(Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && php artisan migrate:status --pending --no-ansi")
if ($LASTEXITCODE -ne 0) {
    $statusOutput | ForEach-Object { Info $_ }
    Fail "Could not read migration status on the server. Migrations were NOT run."
    exit 1
}
$pendingRows = @($statusOutput | Where-Object { $_ -match "Pending" -and $_ -notmatch "No pending" })

if ($pendingRows.Count -eq 0) {
    Pass "No pending migrations."
} else {
    Write-Host "Pending migrations on PRODUCTION:" -ForegroundColor Yellow
    $pendingRows | ForEach-Object { Write-Host "  $_" -ForegroundColor Yellow }
    if (Confirm-Typed "These migrations will run on the PRODUCTION database with --force." "MIGRATE") {
        Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && php artisan migrate --force --no-ansi" | ForEach-Object { Info $_ }
        if ($LASTEXITCODE -ne 0) {
            Fail "Remote migration failed. Check the server now."
            exit 1
        }
        Pass "Migrations finished."
    } else {
        Write-Host " [WARN] Migrations skipped. New code is live but the database is NOT migrated." -ForegroundColor Yellow
    }
}

Invoke-Remote $cfg $sshMode "cd $REMOTE_PATH && php artisan optimize:clear --no-ansi" | ForEach-Object { Info $_ }
if ($LASTEXITCODE -ne 0) {
    Fail "Remote cache clear failed."
    exit 1
}

# 9. Cleanup Staging
Remove-Item -LiteralPath $stageDir -Recurse -Force

$elapsed = [math]::Round(((Get-Date) - $startTime).TotalSeconds, 1)

Banner "FAST DEPLOY COMPLETED SUCCESSFULLY in ${elapsed}s" "Green"
Write-Host "Your changes are now live on https://www.musoftwares.com" -ForegroundColor Green
Write-Host ""
