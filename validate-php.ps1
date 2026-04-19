# ============================================
# SPACEX Trading Academy - PHP Auto-Validator
# Detects PHP, validates extensions, updates settings
# ============================================

param(
    [switch]$Fix
)

$ErrorActionPreference = "SilentlyContinue"

# ---- Configuration ----
$settingsPath = "$env:APPDATA\Antigravity\User\settings.json"
$requiredExtensions = @("pdo_mysql", "curl", "mbstring", "openssl", "json", "fileinfo")
$minPhpVersion = "7.4.0"

# ---- Styled Output Helpers ----
function Write-Header {
    param([string]$text)
    Write-Host ""
    Write-Host "  [*] $text" -ForegroundColor Cyan
    Write-Host ("  " + ("-" * 50)) -ForegroundColor DarkGray
}

function Write-Pass {
    param([string]$text)
    Write-Host "  [OK] $text" -ForegroundColor Green
}

function Write-Fail {
    param([string]$text)
    Write-Host "  [FAIL] $text" -ForegroundColor Red
}

function Write-Warn {
    param([string]$text)
    Write-Host "  [WARN] $text" -ForegroundColor Yellow
}

function Write-Info {
    param([string]$text)
    Write-Host "  [i] $text" -ForegroundColor Gray
}

# ---- Banner ----
Write-Host ""
Write-Host "  =======================================" -ForegroundColor Cyan
Write-Host "   SPACEX Trading Academy - PHP Validator " -ForegroundColor Cyan
Write-Host "  =======================================" -ForegroundColor Cyan

# ---- Step 1: Scan for PHP installations ----
Write-Header "Scanning for PHP installations..."

$searchPaths = @(
    "C:\xampp\php\php.exe",
    "C:\xampp2\php\php.exe",
    "C:\php\php.exe",
    "C:\php8\php.exe",
    "C:\php7\php.exe",
    "$env:ProgramFiles\PHP\php.exe",
    "${env:ProgramFiles(x86)}\PHP\php.exe",
    "$env:LOCALAPPDATA\Programs\php\php.exe",
    "$env:USERPROFILE\scoop\apps\php\current\php.exe"
)

$foundPhpPaths = @()

# Check explicit paths
foreach ($path in $searchPaths) {
    if (Test-Path $path) {
        $foundPhpPaths += $path
    }
}

# Check WAMP directories
$wampPaths = @("C:\wamp\bin\php", "C:\wamp64\bin\php")
foreach ($wp in $wampPaths) {
    if (Test-Path $wp) {
        $found = Get-ChildItem -Path $wp -Filter "php.exe" -Recurse -Depth 2 -ErrorAction SilentlyContinue
        foreach ($f in $found) {
            $foundPhpPaths += $f.FullName
        }
    }
}

# Check Laragon directories
$laragonPath = "C:\laragon\bin\php"
if (Test-Path $laragonPath) {
    $found = Get-ChildItem -Path $laragonPath -Filter "php.exe" -Recurse -Depth 2 -ErrorAction SilentlyContinue
    foreach ($f in $found) {
        $foundPhpPaths += $f.FullName
    }
}

# Check PATH
$pathPhp = Get-Command php -ErrorAction SilentlyContinue
if ($pathPhp) {
    $foundPhpPaths += $pathPhp.Source
}

# Deduplicate
$foundPhpPaths = $foundPhpPaths | Sort-Object -Unique

if ($foundPhpPaths.Count -eq 0) {
    Write-Fail "No PHP installation found on this system!"
    Write-Host ""
    Write-Warn "To install PHP, choose one of these options:"
    Write-Host ""
    Write-Info "Option 1 - XAMPP (Recommended for beginners):"
    Write-Info "  Download from: https://www.apachefriends.org/download.html"
    Write-Info "  Install to C:\xampp and start Apache + MySQL"
    Write-Host ""
    Write-Info "Option 2 - Standalone PHP:"
    Write-Info "  Download from: https://windows.php.net/download/"
    Write-Info "  Extract to C:\php and add to PATH"
    Write-Host ""
    Write-Info "Option 3 - Laragon (Modern alternative):"
    Write-Info "  Download from: https://laragon.org/download/"
    Write-Host ""
    Write-Info "After installing, run this script again:"
    Write-Info "  powershell -ExecutionPolicy Bypass -File validate-php.ps1 -Fix"
    Write-Host ""
    exit 1
}

Write-Pass "Found $($foundPhpPaths.Count) PHP installation(s):"
foreach ($p in $foundPhpPaths) {
    Write-Info "  -> $p"
}

# ---- Step 2: Validate best PHP installation ----
Write-Header "Validating PHP installations..."

$bestPhp = $null
$bestVersion = $null

foreach ($phpPath in $foundPhpPaths) {
    try {
        $versionOutput = & $phpPath -v 2>&1
        if ($versionOutput -match 'PHP (\d+\.\d+\.\d+)') {
            $version = $Matches[1]
            Write-Info "  $phpPath -> PHP $version"

            if ([version]$version -ge [version]$minPhpVersion) {
                if ($null -eq $bestVersion -or [version]$version -gt [version]$bestVersion) {
                    $bestPhp = $phpPath
                    $bestVersion = $version
                }
            } else {
                Write-Warn "  PHP $version is below minimum required $minPhpVersion"
            }
        }
    } catch {
        Write-Fail "  Failed to execute: $phpPath"
    }
}

if ($null -eq $bestPhp) {
    Write-Fail "No valid PHP >= $minPhpVersion found!"
    exit 1
}

Write-Pass "Best PHP: $bestPhp (v$bestVersion)"

# ---- Step 3: Check required extensions ----
Write-Header "Checking required PHP extensions..."

$extensionOutput = & $bestPhp -m 2>&1
$loadedExtensions = $extensionOutput | ForEach-Object { $_.Trim().ToLower() }
$missingExtensions = @()

foreach ($ext in $requiredExtensions) {
    if ($loadedExtensions -contains $ext.ToLower()) {
        Write-Pass "$ext"
    } else {
        Write-Fail "$ext - NOT LOADED"
        $missingExtensions += $ext
    }
}

if ($missingExtensions.Count -gt 0) {
    Write-Host ""
    Write-Warn "Missing extensions: $($missingExtensions -join ', ')"
    $phpDir = Split-Path $bestPhp
    $phpIni = Join-Path $phpDir "php.ini"
    Write-Info "Edit php.ini at: $phpIni"
    Write-Info "Uncomment these lines:"
    foreach ($ext in $missingExtensions) {
        Write-Info "  extension=$ext"
    }
}

# ---- Step 4: Update Antigravity Settings ----
Write-Header "Updating Antigravity settings..."

if (Test-Path $settingsPath) {
    try {
        $settingsContent = Get-Content $settingsPath -Raw | ConvertFrom-Json
        $currentPath = $settingsContent.'php.validate.executablePath'

        if ($currentPath -eq $bestPhp) {
            Write-Pass "Settings already configured correctly"
        } else {
            Write-Warn "Current setting: $currentPath"
            Write-Info "Will update to: $bestPhp"

            $settingsContent.'php.validate.executablePath' = $bestPhp

            if (-not ($settingsContent.PSObject.Properties.Name -contains 'php.validate.run')) {
                $settingsContent | Add-Member -NotePropertyName 'php.validate.run' -NotePropertyValue 'onSave'
            }

            $settingsContent | ConvertTo-Json -Depth 10 | Set-Content $settingsPath -Encoding UTF8
            Write-Pass "Settings updated successfully!"
        }
    } catch {
        Write-Fail "Failed to update settings: $_"
    }
} else {
    Write-Warn "Settings file not found at: $settingsPath"
    Write-Info "Creating settings file..."

    $settingsDir = Split-Path $settingsPath
    if (-not (Test-Path $settingsDir)) {
        New-Item -ItemType Directory -Path $settingsDir -Force | Out-Null
    }

    $newSettings = @{
        'files.autoSave' = 'afterDelay'
        'window.confirmSaveUntitledWorkspace' = $false
        'php.validate.executablePath' = $bestPhp
        'php.validate.run' = 'onSave'
    }

    $newSettings | ConvertTo-Json -Depth 10 | Set-Content $settingsPath -Encoding UTF8
    Write-Pass "Settings file created!"
}

# ---- Step 5: Project-level validation ----
Write-Header "Validating project setup..."

$projectDir = Split-Path $MyInvocation.MyCommand.Path
$backendDir = Join-Path $projectDir "backend"

$requiredDirs = @("backend\api", "backend\config", "backend\includes", "backend\sql")
foreach ($dir in $requiredDirs) {
    $fullPath = Join-Path $projectDir $dir
    if (Test-Path $fullPath) {
        Write-Pass "$dir\"
    } else {
        Write-Fail "$dir\ - MISSING"
    }
}

# ---- Step 6: Check MySQL ----
Write-Header "Checking database connectivity..."

$dbCheckCode = '<?php try { $p = new PDO("mysql:host=localhost;port=3306", "root", "", [PDO::ATTR_TIMEOUT => 3]); echo "DB_OK"; } catch (Exception $e) { echo "DB_FAIL:" . $e->getMessage(); }'
$tempFile = [System.IO.Path]::Combine([System.IO.Path]::GetTempPath(), "spacex_dbcheck.php")
$dbCheckCode | Set-Content $tempFile -Encoding UTF8
$dbResult = & $bestPhp $tempFile 2>&1
Remove-Item $tempFile -Force -ErrorAction SilentlyContinue

if ("$dbResult" -match "DB_OK") {
    Write-Pass "MySQL/MariaDB is accessible on localhost:3306"
} else {
    $errorMsg = "$dbResult" -replace "DB_FAIL:", ""
    Write-Fail "Database connection failed"
    Write-Info "  Error: $errorMsg"
    Write-Info "  Make sure MySQL/MariaDB is running"
}

# ---- Summary ----
Write-Host ""
Write-Host "  =======================================" -ForegroundColor Green
Write-Host "         Validation Complete              " -ForegroundColor Green
Write-Host "  =======================================" -ForegroundColor Green
Write-Host ""
Write-Info "PHP Path: $bestPhp"
Write-Info "PHP Version: $bestVersion"
if ($missingExtensions.Count -eq 0) {
    Write-Info "Missing Extensions: None"
} else {
    Write-Info "Missing Extensions: $($missingExtensions -join ', ')"
}
Write-Host ""
