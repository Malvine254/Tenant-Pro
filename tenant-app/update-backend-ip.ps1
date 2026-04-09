param(
    [ValidateSet("emulator", "device")]
    [string]$Target = "emulator"
)

# Update Backend IP Script for Tenant Pro Android App
# Default target is emulator on the same laptop

Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "  Tenant Pro - Update Backend IP" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

$backendHost = "127.0.0.1"

if ($Target -eq "emulator") {
    Write-Host "Using emulator localhost with adb reverse: 127.0.0.1" -ForegroundColor Green
    $adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
    if (Test-Path $adb) {
        & $adb reverse tcp:3000 tcp:3000 | Out-Null
        Write-Host "ADB reverse enabled for tcp:3000" -ForegroundColor Green
    }
    Write-Host ""
} else {
    Write-Host "Detecting your network IP address for physical device..." -ForegroundColor Yellow

    $networkIP = Get-NetIPAddress -AddressFamily IPv4 | 
        Where-Object { $_.IPAddress -notlike "127.*" -and $_.IPAddress -notlike "169.*" } | 
        Select-Object -First 1 -ExpandProperty IPAddress

    if (-not $networkIP) {
        Write-Host ""
        Write-Host "ERROR: Could not detect network IP address." -ForegroundColor Red
        Write-Host "Make sure you're connected to a Wi-Fi or Ethernet network." -ForegroundColor Yellow
        Write-Host ""
        exit 1
    }

    $backendHost = $networkIP
    Write-Host "Found IP Address: $backendHost" -ForegroundColor Green
    Write-Host ""
}

# Update local.properties
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$localPropsPath = Join-Path $scriptDir "local.properties"

if (-not (Test-Path $localPropsPath)) {
    Write-Host "ERROR: local.properties not found at:" -ForegroundColor Red
    Write-Host $localPropsPath -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

# Read current content
$content = Get-Content $localPropsPath
$newContent = @()
$ipUpdated = $false

foreach ($line in $content) {
    if ($line -match "^backend\.host=") {
        $newContent += "backend.host=$backendHost"
        $ipUpdated = $true
    } else {
        $newContent += $line
    }
}

# If backend.host wasn't found, add it
if (-not $ipUpdated) {
    $newContent += ""
    $newContent += "# Backend API Configuration"
    $newContent += "# The app will automatically use this IP address"
    $newContent += "backend.host=$backendHost"
    $newContent += "backend.port=3000"
}

# Write back to file
$newContent | Set-Content $localPropsPath -Encoding UTF8

Write-Host "SUCCESS: Updated local.properties" -ForegroundColor Green
Write-Host "Backend Host: $backendHost" -ForegroundColor Cyan
Write-Host "Backend Port: 3000" -ForegroundColor Cyan
Write-Host "Full URL: http://${backendHost}:3000/api/" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Open Android Studio" -ForegroundColor White
Write-Host "2. File > Sync Project with Gradle Files" -ForegroundColor White
Write-Host "3. Build > Rebuild Project" -ForegroundColor White
Write-Host "4. Run the app on your device" -ForegroundColor White
Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""
