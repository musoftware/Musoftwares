# Deploy Master Launcher - PowerShell
# Unified CLI & Interactive deployment controller for Musoftwares
#
# Usage:
#   .\deploy\deploy.ps1                  # Interactive menu
#   .\deploy\deploy.ps1 -Mode QuickPHP   # Fast PHP push (changed files)
#   .\deploy\deploy.ps1 -Mode Full       # Full build + PHP + Assets + Tests
#   .\deploy\deploy.ps1 -Mode Migrate    # Remote schema sync & migrations
#   .\deploy\deploy.ps1 -Mode Cache      # Clear & optimize remote cache
#   .\deploy\deploy.ps1 -Mode Assets     # Build & upload compiled JS/CSS assets

param(
    [ValidateSet("Full", "QuickPHP", "Migrate", "Cache", "Assets", "Menu")]
    [string]$Mode = "Full",
    [string]$Commit = "",
    [switch]$SkipTests,
    [switch]$DryRun,
    [switch]$NoPassword
)

$ErrorActionPreference = "Stop"
$PROJECT_ROOT = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $PROJECT_ROOT

function Show-Header {
    Write-Host "========================================================" -ForegroundColor Cyan
    Write-Host "         MUSOFTWARES AUTOMATED DEPLOYMENT              " -ForegroundColor Cyan
    Write-Host "========================================================" -ForegroundColor Cyan
    Write-Host " Project: $PROJECT_ROOT" -ForegroundColor Gray
    Write-Host ""
}

function Show-Menu {
    Show-Header
    Write-Host " Select Deployment Action:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  [1] Full Production Deploy (Checks + Build Assets + Push) [DEFAULT]" -ForegroundColor Green
    Write-Host "  [2] Quick PHP Push (Upload changed PHP files + Clear Cache)" -ForegroundColor Green
    Write-Host "  [3] Run Migrations & Schema Sync (Remote DB update)" -ForegroundColor Green
    Write-Host "  [4] Upload Build Assets (JS/CSS assets compile & push)" -ForegroundColor Green
    Write-Host "  [5] Clear & Re-optimize Remote Cache" -ForegroundColor Green
    Write-Host "  [6] Check Missing Translations (Local scan)" -ForegroundColor Green
    Write-Host "  [Q] Quit" -ForegroundColor Red
    Write-Host ""

    $choice = Read-Host " Enter option [1-6, Q]"
    return $choice
}

if ($Mode -eq "Menu") {
    $choice = Show-Menu
    switch ($choice.ToUpper()) {
        "1" { $Mode = "Full" }
        "2" { $Mode = "QuickPHP" }
        "3" { $Mode = "Migrate" }
        "4" { $Mode = "Assets" }
        "5" { $Mode = "Cache" }
        "6" {
            php artisan translations:check
            exit 0
        }
        "Q" {
            Write-Host "Deployment cancelled." -ForegroundColor Yellow
            exit 0
        }
        default {
            Write-Host "Invalid option selected." -ForegroundColor Red
            exit 1
        }
    }
}

Show-Header
Write-Host " Executing Mode: $Mode" -ForegroundColor White
Write-Host ""

switch ($Mode) {
    "QuickPHP" {
        $params = @{}
        if ($Commit) { $params["Commit"] = $Commit }
        if ($SkipTests) { $params["SkipTests"] = $true }
        if ($DryRun) { $params["DryRun"] = $true }
        if ($NoPassword) { $params["NoPassword"] = $true }
        
        & "$PSScriptRoot\push-php.ps1" @params
    }
    "Full" {
        $params = @{}
        if ($NoPassword) { $params["NoPassword"] = $true }

        & "$PSScriptRoot\build.ps1" @params
    }
    "Migrate" {
        $params = @{}
        if ($NoPassword) { $params["NoPassword"] = $true }

        & "$PSScriptRoot\migrate.ps1" @params
    }
    "Assets" {
        $params = @{}
        if ($NoPassword) { $params["NoPassword"] = $true }

        & "$PSScriptRoot\upload-build.ps1" @params
    }
    "Cache" {
        $params = @{}
        if ($NoPassword) { $params["NoPassword"] = $true }

        & "$PSScriptRoot\clear-cache.ps1" @params
    }
}
