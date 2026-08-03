param(
    [ValidateSet("auto", "emulator", "device")]
    [string]$Target = "auto",

    [switch]$Install
)

# Update Backend IP Script for Tenant Pro Android App
# auto mode uses emulator localhost when an emulator is connected; otherwise it
# detects the active LAN IP for a physical Android device.

Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "  Tenant Pro - Update Backend IP" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

$backendHost = "10.0.2.2"
$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"

if ($Target -eq "auto") {
    $Target = "device"
    if (Test-Path $adb) {
        $adbDevices = & $adb devices 2>$null
        $hasEmulator = $adbDevices | Where-Object { $_ -match "^emulator-\d+\s+device" }
        $hasPhysicalDevice = $adbDevices | Where-Object { $_ -match "^\S+\s+device" -and $_ -notmatch "^emulator-" }

        if ($hasEmulator -and -not $hasPhysicalDevice) {
            $Target = "emulator"
        }
    }
}

if ($Target -eq "emulator") {
    Write-Host "Using emulator host loopback mapping: 10.0.2.2" -ForegroundColor Green
    if (Test-Path $adb) {
        & $adb reverse tcp:3000 tcp:3000 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "ADB reverse enabled for tcp:3000" -ForegroundColor Green
        } else {
            Write-Host "ADB reverse not available (continuing with 10.0.2.2 fallback)." -ForegroundColor Yellow
        }
    } else {
        Write-Host "ADB not found. Continuing with 10.0.2.2 host mapping." -ForegroundColor Yellow
    }
    Write-Host ""
} else {
    Write-Host "Detecting your network IP address for physical device..." -ForegroundColor Yellow

    $networkConfig = Get-NetIPConfiguration |
        Where-Object {
            $_.IPv4Address -and
            $_.IPv4DefaultGateway -and
            $_.NetAdapter.Status -eq "Up" -and
            $_.InterfaceAlias -notmatch "vEthernet|Virtual|Loopback|Docker|WSL"
        } |
        Sort-Object @{ Expression = { if ($_.InterfaceAlias -match "Wi-?Fi|Wireless|WLAN") { 0 } else { 1 } } } |
        Select-Object -First 1

    $networkIP = $networkConfig.IPv4Address.IPAddress

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
    } elseif ($line -match "^backend\.baseUrl=") {
        $newContent += "backend.baseUrl=http\://$backendHost\:3000/api/"
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

if (-not ($newContent | Where-Object { $_ -match "^backend\.baseUrl=" })) {
    $newContent += "backend.baseUrl=http\://$backendHost\:3000/api/"
}

# Write back to file
$newContent | Set-Content $localPropsPath -Encoding UTF8

Write-Host "SUCCESS: Updated local.properties" -ForegroundColor Green
Write-Host "Backend Host: $backendHost" -ForegroundColor Cyan
Write-Host "Backend Port: 3000" -ForegroundColor Cyan
Write-Host "Full URL: http://${backendHost}:3000/api/" -ForegroundColor Cyan
Write-Host ""

if ($Install) {
    $gradle = Join-Path $scriptDir "gradlew.bat"
    if (-not (Test-Path $gradle)) {
        Write-Host "ERROR: gradlew.bat not found, cannot install debug APK." -ForegroundColor Red
        exit 1
    }

    Write-Host "Rebuilding and installing debug APK..." -ForegroundColor Yellow
    Push-Location $scriptDir
    try {
        & $gradle ":app:installDebug"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "ERROR: Gradle install failed." -ForegroundColor Red
            exit $LASTEXITCODE
        }
    } finally {
        Pop-Location
    }
}

Write-Host "Next Steps:" -ForegroundColor Yellow
if ($Install) {
    Write-Host "1. Open the app on your device/emulator" -ForegroundColor White
} else {
    Write-Host "1. Rebuild and reinstall the Android app so BuildConfig.BASE_URL is refreshed" -ForegroundColor White
    Write-Host "2. Or run this script with -Install to do that automatically" -ForegroundColor White
    Write-Host "3. Open the app on your device/emulator" -ForegroundColor White
}
Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""
