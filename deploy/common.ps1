# Shared helpers for the deploy scripts. Dot-source it:  . "$PSScriptRoot\common.ps1"
# Works on Windows PowerShell 5.1.
#
# Auth order:
#   1. SSH_KEY in deploy/.ssh-config (a .ppk key uses PuTTY, any other key uses OpenSSH)
#   2. SSH_PASSWORD with PuTTY (deprecated, prints a warning)
#   3. Plain OpenSSH (ssh-agent or ~/.ssh keys)
# Host keys are always checked. Connect once by hand to accept a new server key.

function Read-SshConfig {
    $configFile = Join-Path $PSScriptRoot ".ssh-config"
    if (-not (Test-Path $configFile)) {
        Write-Host " [FAIL] deploy/.ssh-config not found. Copy deploy/.ssh-config.example and fill it in." -ForegroundColor Red
        exit 1
    }

    $config = @{}
    Get-Content $configFile | Where-Object { $_ -notmatch "^\s*#" -and $_ -notmatch "^\s*$" } | ForEach-Object {
        $parts = $_ -split "=", 2
        if ($parts.Count -eq 2) {
            $config[$parts[0].Trim()] = $parts[1].Trim().Trim('"')
        }
    }

    $port = $config["SSH_PORT"]
    if ([string]::IsNullOrEmpty($port)) { $port = "22" }
    $remotePath = "$($config["REMOTE_PATH"])".TrimEnd('/')

    return [pscustomobject]@{
        User       = $config["SSH_USER"]
        Host       = $config["SSH_HOST"]
        Port       = $port
        RemotePath = $remotePath
        Password   = $config["SSH_PASSWORD"]
        KeyFile    = $config["SSH_KEY"]
    }
}

function Test-CommandExists($name) {
    return $null -ne (Get-Command $name -ErrorAction SilentlyContinue)
}

# Returns "putty-key", "openssh-key", "putty-password" or "openssh".
function Resolve-SshMode($cfg, [switch]$NoPassword) {
    $hasPutty = (Test-CommandExists "plink") -and (Test-CommandExists "pscp")

    if ($cfg.KeyFile) {
        if (-not (Test-Path $cfg.KeyFile)) {
            Write-Host " [FAIL] SSH_KEY file not found: $($cfg.KeyFile)" -ForegroundColor Red
            exit 1
        }
        if ($cfg.KeyFile -like "*.ppk") {
            if (-not $hasPutty) {
                Write-Host " [FAIL] SSH_KEY is a .ppk file but plink/pscp are not installed." -ForegroundColor Red
                exit 1
            }
            return "putty-key"
        }
        return "openssh-key"
    }

    if ($hasPutty -and $cfg.Password -and -not $NoPassword) {
        Write-Host " [WARN] Using SSH password auth. Set SSH_KEY in deploy/.ssh-config to use a key file instead." -ForegroundColor Yellow
        return "putty-password"
    }

    return "openssh"
}

function Get-PuttyAuthArgs($cfg, $mode) {
    if ($mode -eq "putty-key") { return @("-i", $cfg.KeyFile) }
    return @("-pw", $cfg.Password)
}

function Get-OpenSshAuthArgs($cfg, $mode) {
    if ($mode -eq "openssh-key") { return @("-i", $cfg.KeyFile) }
    return @()
}

# Runs a command on the server. Returns its output lines. Sets $LASTEXITCODE.
function Invoke-Remote($cfg, $mode, [string]$command) {
    $target = "$($cfg.User)@$($cfg.Host)"
    if ($mode -like "putty-*") {
        $auth = Get-PuttyAuthArgs $cfg $mode
        return (& plink.exe -batch -T -P $cfg.Port @auth $target $command)
    }
    $auth = Get-OpenSshAuthArgs $cfg $mode
    return (& ssh -p $cfg.Port @auth $target $command)
}

# Uploads one local file to a full remote path. Sets $LASTEXITCODE.
function Send-RemoteFile($cfg, $mode, [string]$localFile, [string]$remoteFile) {
    $target = "$($cfg.User)@$($cfg.Host):$remoteFile"
    if ($mode -like "putty-*") {
        $auth = Get-PuttyAuthArgs $cfg $mode
        & pscp.exe -sftp -batch -P $cfg.Port @auth $localFile $target
        return
    }
    $auth = Get-OpenSshAuthArgs $cfg $mode
    & scp -P $cfg.Port @auth $localFile $target
}

# Makes the user type an exact word to continue. Returns $true when typed.
function Confirm-Typed([string]$message, [string]$word) {
    Write-Host ""
    Write-Host $message -ForegroundColor Yellow
    $answer = Read-Host "Type $word to continue (anything else aborts)"
    return ($answer -ceq $word)
}

function Get-LocalPhp {
    if (Test-Path "C:\tools\php83\php.exe") { return "C:\tools\php83\php.exe" }
    return "php"
}

# Runs the full Pest suite locally. Returns $true when it passes.
function Invoke-LocalTests([string]$projectRoot) {
    $php = Get-LocalPhp
    Push-Location $projectRoot
    try {
        & $php -d memory_limit=2G vendor\bin\pest --parallel
        return ($LASTEXITCODE -eq 0)
    } finally {
        Pop-Location
    }
}

# Shared gate for -SkipTests. Exits unless the user types SKIP-TESTS.
function Assert-TestsOrConfirmedSkip([string]$projectRoot, [switch]$SkipTests) {
    if ($SkipTests) {
        $ok = Confirm-Typed "You are about to deploy to PRODUCTION without running the test suite." "SKIP-TESTS"
        if (-not $ok) {
            Write-Host " [FAIL] Aborted. Nothing was uploaded." -ForegroundColor Red
            exit 1
        }
        Write-Host " [WARN] Tests skipped by user." -ForegroundColor Yellow
        return
    }

    Write-Host "-> Running Pest locally before deploy (use -SkipTests to bypass)..." -ForegroundColor Yellow
    if (-not (Invoke-LocalTests $projectRoot)) {
        Write-Host " [FAIL] Tests failed. Deploy aborted. Nothing was uploaded." -ForegroundColor Red
        exit 1
    }
    Write-Host " [OK] All tests passed." -ForegroundColor Green
}
