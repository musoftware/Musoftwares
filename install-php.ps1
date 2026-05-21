# =============================================
#  Install Chocolatey + PHP 8.3
#  Run this script as Administrator!
# =============================================

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell -> Run as administrator, then try again." -ForegroundColor Yellow
    pause
    exit 1
}

# Step 1: Clean up broken Chocolatey install (if any)
Write-Host "`n[1/3] Cleaning up previous Chocolatey install..." -ForegroundColor Cyan
if (Test-Path "C:\ProgramData\chocolatey") {
    Remove-Item "C:\ProgramData\chocolatey" -Recurse -Force
    Write-Host "  Removed old directory." -ForegroundColor Green
}
[Environment]::SetEnvironmentVariable("ChocolateyInstall", $null, "User")
[Environment]::SetEnvironmentVariable("ChocolateyInstall", $null, "Machine")

# Step 2: Install Chocolatey
Write-Host "`n[2/3] Installing Chocolatey..." -ForegroundColor Cyan
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Refresh PATH for current session
$env:Path += ";C:\ProgramData\chocolatey\bin"

# Verify choco installed
if (Get-Command choco -ErrorAction SilentlyContinue) {
    Write-Host "  Chocolatey installed successfully!" -ForegroundColor Green
    choco --version
} else {
    Write-Host "  ERROR: Chocolatey installation failed!" -ForegroundColor Red
    pause
    exit 1
}

# Step 3: Install PHP 8.3
Write-Host "`n[3/3] Installing PHP 8.3..." -ForegroundColor Cyan
choco install php --version=8.3.0 -y

# Verify PHP
$env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "`n=============================================" -ForegroundColor Green
    Write-Host "  SUCCESS! PHP installed:" -ForegroundColor Green
    php -v
    Write-Host "  Close and reopen your terminals to use php." -ForegroundColor Yellow
    Write-Host "=============================================" -ForegroundColor Green
} else {
    Write-Host "`nPHP installed but may need a terminal restart." -ForegroundColor Yellow
}

pause
