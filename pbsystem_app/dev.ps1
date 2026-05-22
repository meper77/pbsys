# Live-preview / hot-reload entry point for NEO V-TRACK Flutter app on Windows.
#
# Boots the Pixel_API_36 AVD if no emulator is running, then runs `flutter run`
# pointed at the local PHP dev server via Android's host-loopback alias 10.0.2.2.
#
# Run from this directory:
#   .\dev.ps1
#
# Console shortcuts once Flutter is up:
#   r  = hot reload (preserves state)
#   R  = hot restart (resets state)
#   p  = toggle debug paint
#   o  = toggle target platform
#   q  = quit

param(
    [string]$Avd  = 'Pixel_API_36',
    [int]$Port    = 8000,
    [string]$HostAddr = '10.0.2.2'  # Android emulator's alias for the host machine's loopback
)

$ErrorActionPreference = 'Stop'

$sdk    = "$env:LOCALAPPDATA\Android\Sdk"
$adb    = "$sdk\platform-tools\adb.exe"
$emuExe = "$sdk\emulator\emulator.exe"
$base   = "http://${HostAddr}:$Port"

if (-not (Test-Path $adb))    { throw "adb not found at $adb" }
if (-not (Test-Path $emuExe)) { throw "emulator.exe not found at $emuExe" }

function Get-RunningEmulator {
    return (& $adb devices) -match '^emulator-\d+\s+device$'
}

if (-not (Get-RunningEmulator)) {
    Write-Host "[dev] No emulator running. Booting $Avd ..." -ForegroundColor Cyan
    Start-Process -FilePath $emuExe -ArgumentList "-avd", $Avd -WindowStyle Minimized | Out-Null

    $deadline = (Get-Date).AddMinutes(3)
    while ((Get-Date) -lt $deadline) {
        if (Get-RunningEmulator) {
            # Wait for the device to finish booting (sys.boot_completed=1)
            $boot = (& $adb shell getprop sys.boot_completed 2>$null).Trim()
            if ($boot -eq '1') { break }
        }
        Start-Sleep -Seconds 3
    }

    if (-not (Get-RunningEmulator)) { throw "Emulator failed to come online within 3 minutes." }
    Write-Host "[dev] Emulator online." -ForegroundColor Green
} else {
    Write-Host "[dev] Emulator already running." -ForegroundColor Green
}

Write-Host "[dev] API base URL: $base" -ForegroundColor Cyan
Write-Host "[dev] Make sure the PHP dev server is running on the host (port $Port) and MySQL on 3306." -ForegroundColor DarkYellow

flutter run --dart-define=API_BASE_URL=$base
