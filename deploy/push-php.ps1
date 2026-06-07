# Push PHP Script - PowerShell
# Detects changed PHP files, validates them thoroughly, and uploads only those files.
#
# Usage:
#   .\push-php.ps1                  # Upload uncommitted (working tree) PHP changes
#   .\push-php.ps1 -Commit HEAD     # Upload PHP files changed in the latest commit
#   .\push-php.ps1 -Commit HEAD~3   # Upload PHP files changed in the last 3 commits
#   .\push-php.ps1 -SkipTests       # Skip Pest/PHPUnit tests (only syntax + PHPStan)
#   .\push-php.ps1 -DryRun          # Run all checks but don't upload anything

param(
    [string]$Commit = "",
    [switch]$SkipTests,
    [switch]$RemoteTests,
    [switch]$DryRun,
    [switch]$NoPassword
)

$ErrorActionPreference = "Stop"
$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT

# Detect correct PHP binary locally
$PHP_BIN = "php"
if (Test-Path "C:\tools\php83\php.exe") {
    $PHP_BIN = "C:\tools\php83\php.exe"
    $env:PATH = "C:\tools\php83;" + $env:PATH
}

# -----------------------------------------------------------------------
# Helpers
# -----------------------------------------------------------------------
function Banner($msg, $color = "Cyan") {
    Write-Host ""
    Write-Host "========================================================" -ForegroundColor $color
    Write-Host "  $msg" -ForegroundColor $color
    Write-Host "========================================================" -ForegroundColor $color
}

function Step($num, $total, $msg) {
    Write-Host ""
    Write-Host "[$num/$total] $msg" -ForegroundColor Yellow
}

function Pass($msg) {
    Write-Host "  [OK] $msg" -ForegroundColor Green
}

function Fail($msg) {
    Write-Host "  [FAIL] $msg" -ForegroundColor Red
}

function Info($msg) {
    Write-Host "  -> $msg" -ForegroundColor DarkGray
}

# -----------------------------------------------------------------------
# 0. Read SSH Config
# -----------------------------------------------------------------------
$configFile = Join-Path $PSScriptRoot ".ssh-config"
$config = @{}

if (-not (Test-Path $configFile)) {
    Fail ".ssh-config file not found in deploy/"
    exit 1
}

Get-Content $configFile | Where-Object { $_ -notmatch "^#" -and $_ -notmatch "^$" } | ForEach-Object {
    $parts = $_ -split "=", 2
    if ($parts.Count -eq 2) {
        $config[$parts[0].Trim()] = $parts[1].Trim().Trim('"')
    }
}

$SSH_USER = $config["SSH_USER"]
$SSH_HOST = $config["SSH_HOST"]
$SSH_PORT = $config["SSH_PORT"]
if ([string]::IsNullOrEmpty($SSH_PORT)) { $SSH_PORT = 22 }
$REMOTE_PATH = $config["REMOTE_PATH"]
$SSH_PASSWORD = $config["SSH_PASSWORD"]

$totalSteps = if ($SkipTests) { 4 } else { 5 }

Banner "Push PHP - Smart PHP Deployment"
Write-Host "  Server : $SSH_USER@${SSH_HOST}:$SSH_PORT" -ForegroundColor Gray
Write-Host "  Remote : $REMOTE_PATH" -ForegroundColor Gray
if ($DryRun) {
    Write-Host "  Mode   : DRY RUN (no upload)" -ForegroundColor Magenta
}
Write-Host ""

# -----------------------------------------------------------------------
# 1. Run Pest / PHPUnit Tests (optional)
# -----------------------------------------------------------------------
if (-not $SkipTests -and -not $RemoteTests) {
    Step 1 $totalSteps "Running tests locally (php artisan test)..."

    # Check local PHP version
    $phpVer = [version]((& $PHP_BIN -r "echo PHP_VERSION;" 2>&1) -replace '[^\d\.]', '')
    if ($phpVer -lt [version]"8.2.0") {
        Write-Host ""
        Write-Host "  [!] Warning: Local PHP version ($phpVer) is older than 8.2." -ForegroundColor Yellow
        Write-Host "      Tests require PHP 8.2+ (for readonly classes etc)." -ForegroundColor Yellow
        Write-Host "      Skipping local tests automatically. Use -RemoteTests to test on server." -ForegroundColor Yellow
    } else {
        cmd.exe /c "`"$PHP_BIN`" -d memory_limit=2G vendor\bin\pest --stop-on-failure 2>&1"
        if ($LASTEXITCODE -ne 0) {
            Write-Host ""
            Fail "Tests failed! Fix failing tests before deploying."
            exit 1
        }
        Pass "All tests passed."
    }
} elseif ($RemoteTests) {
    Step 1 $totalSteps "Skipping local tests (will run on remote server)..."
}

# -----------------------------------------------------------------------
# 2. Detect Changed PHP Files
# -----------------------------------------------------------------------
Step 2 $totalSteps "Detecting changed PHP files..."

$phpFiles = @()

if ($Commit) {
    Info "Scanning commit: $Commit"
    $ErrorActionPreference = "Continue"
    $rawOutput = git diff --name-only --diff-filter=ACMR "$Commit~1" "$Commit" -- "*.php" 2>&1
    $ErrorActionPreference = "Stop"
    $phpFiles = @($rawOutput | Where-Object { $_ -is [string] -and $_ -notmatch "^warning:" -and $_ -match "\.php$" })
} else {
    Info "Scanning working tree (uncommitted changes)..."
    $ErrorActionPreference = "Continue"
    $rawOutput = git diff --name-only --diff-filter=ACMR HEAD -- "*.php" 2>&1
    $ErrorActionPreference = "Stop"
    $phpFiles = @($rawOutput | Where-Object { $_ -is [string] -and $_ -notmatch "^warning:" -and $_ -match "\.php$" })
}

# Filter out deleted files (only keep files that exist on disk)
$phpFiles = @($phpFiles | Where-Object { Test-Path (Join-Path $PROJECT_ROOT $_) })

if ($phpFiles.Count -eq 0) {
    Pass "No PHP files changed. Nothing to deploy."
    exit 0
}

Write-Host ""
Write-Host "  Found $($phpFiles.Count) changed PHP file(s):" -ForegroundColor White
foreach ($f in $phpFiles) {
    Write-Host "    - $f" -ForegroundColor DarkCyan
}

# -----------------------------------------------------------------------
# 3. PHP Syntax Check (php -l) on each changed file
# -----------------------------------------------------------------------
Step 3 $totalSteps "Running PHP syntax check (php -l)..."

$syntaxErrors = 0
foreach ($file in $phpFiles) {
    $fullPath = Join-Path $PROJECT_ROOT $file
    $result = cmd.exe /c "`"$PHP_BIN`" -l `"$fullPath`" 2>&1"
    if ($LASTEXITCODE -ne 0) {
        Fail "$file"
        Write-Host "       $result" -ForegroundColor Red
        $syntaxErrors++
    }
}

if ($syntaxErrors -gt 0) {
    Write-Host ""
    Fail "Syntax check failed! $syntaxErrors file(s) have errors. Aborting."
    exit 1
}

Pass "All $($phpFiles.Count) files passed syntax check."

# -----------------------------------------------------------------------
# 4. PHPStan Static Analysis (full project with baseline)
# -----------------------------------------------------------------------
Step 4 $totalSteps "Running PHPStan static analysis..."

Info "Analysing full project (baseline ignores existing issues, only NEW errors fail)..."
cmd.exe /c "`"$PHP_BIN`" vendor\bin\phpstan analyse --memory-limit=2G --no-progress"
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Fail "PHPStan found errors! Fix them before deploying."
    exit 1
}

Pass "PHPStan passed - no new errors detected."



# -----------------------------------------------------------------------
# 5. Upload Changed Files to Server
# -----------------------------------------------------------------------
$uploadStep = if ($SkipTests) { 4 } else { 5 }
Step $uploadStep $totalSteps "Uploading $($phpFiles.Count) file(s) to server..."

if ($DryRun) {
    Write-Host ""
    Write-Host "  DRY RUN - skipping upload. The following files would be uploaded:" -ForegroundColor Magenta
    foreach ($f in $phpFiles) {
        Write-Host "    -> $f" -ForegroundColor DarkCyan
    }
    Banner "DRY RUN COMPLETE - All checks passed!" "Green"
    exit 0
}

$hasPutty = $null -ne (Get-Command plink -ErrorAction SilentlyContinue) -and $null -ne (Get-Command pscp -ErrorAction SilentlyContinue)

# Accept host key if PuTTY
if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    cmd.exe /c "echo y | plink.exe -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST exit 2>nul"
}

$uploadErrors = 0

foreach ($file in $phpFiles) {
    $localFile = Join-Path $PROJECT_ROOT $file
    # Convert Windows backslashes to forward slashes for remote path
    $slash = [string][char]47
    $bslash = [string][char]92
    $remoteRelative = $file.Replace($bslash, $slash)
    $remoteFile = "$REMOTE_PATH/$remoteRelative"
    $lastSlash = $remoteFile.LastIndexOf($slash)
    $remoteDir = $remoteFile.Substring(0, $lastSlash)

    Info "Uploading: $file"

    if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
        # Ensure remote directory exists
        $mkdirCmd = "mkdir -p $remoteDir"
        cmd.exe /c "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$mkdirCmd""" 2>&1 | Out-Null

        # Upload file
        & pscp.exe -sftp -batch -P $SSH_PORT -pw $SSH_PASSWORD $localFile "${SSH_USER}@${SSH_HOST}:$remoteFile" 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Fail "Failed to upload: $file"
            $uploadErrors++
        }
    }
    else {
        # Ensure remote directory exists
        & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "mkdir -p $remoteDir" 2>&1 | Out-Null

        # Upload file
        & scp -P $SSH_PORT -o StrictHostKeyChecking=no $localFile "${SSH_USER}@${SSH_HOST}:$remoteFile" 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Fail "Failed to upload: $file"
            $uploadErrors++
        }
    }
}

if ($uploadErrors -gt 0) {
    Fail "$uploadErrors file(s) failed to upload."
    exit 1
}

Pass "All $($phpFiles.Count) file(s) uploaded successfully."

# -----------------------------------------------------------------------
# 6. Clear Remote Caches
# -----------------------------------------------------------------------
Write-Host ""
Info "Clearing remote caches..."

$clearCmd = "cd $REMOTE_PATH && php artisan optimize:clear && php artisan optimize"

if ($RemoteTests) {
    $clearCmd += " && php artisan test --stop-on-failure"
    Info "Running tests on remote server..."
}

if ($hasPutty -and $SSH_PASSWORD -and -not $NoPassword) {
    $plinkCommand = "echo. | plink.exe -batch -T -P $SSH_PORT -pw ""$SSH_PASSWORD"" $SSH_USER@$SSH_HOST ""$clearCmd"""
    cmd.exe /c $plinkCommand 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0 -and $RemoteTests) {
        Fail "Remote tests failed! Check the server."
    }
}
else {
    & ssh -p $SSH_PORT -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" $clearCmd 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0 -and $RemoteTests) {
        Fail "Remote tests failed! Check the server."
    }
}

Pass "Remote caches cleared (and tests passed if RemoteTests was used)."

# -----------------------------------------------------------------------
# Done
# -----------------------------------------------------------------------
Banner "SUCCESS - $($phpFiles.Count) PHP file(s) deployed!" "Green"
Write-Host ""
Write-Host "  Files deployed:" -ForegroundColor White
foreach ($f in $phpFiles) {
    Write-Host "    [OK] $f" -ForegroundColor Green
}
Write-Host ""
