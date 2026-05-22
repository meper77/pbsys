# NEO V-TRACK full-stack local startup
# Starts MySQL (XAMPP), PHP built-in web server, Android emulator, and the Flutter app.
#
# Usage:
#   .\start-neovtrack.ps1                # web + app on Android emulator
#   .\start-neovtrack.ps1 -NoApp         # web only
#   .\start-neovtrack.ps1 -AppTarget chrome   # run Flutter on Chrome instead
#   .\start-neovtrack.ps1 -Port 8080     # custom web port

param(
    [int]$Port = 8000,
    [string]$AppTarget = 'emulator-5554',
    [string]$EmulatorId = 'Pixel_API_36',
    [switch]$NoApp,
    [switch]$NoOpen
)

$ErrorActionPreference = 'Stop'
$repo   = $PSScriptRoot
$xampp  = 'C:\xampp'
$mysqld = "$xampp\mysql\bin\mysqld.exe"
$php    = "$xampp\php\php.exe"

function Test-Port($p) {
    try { $c = New-Object Net.Sockets.TcpClient; $c.Connect('127.0.0.1', $p); $c.Close(); $true } catch { $false }
}

# 1) MySQL
if (Get-Process mysqld -ErrorAction SilentlyContinue) {
    Write-Host "[mysql ] already running" -ForegroundColor DarkGray
} else {
    Write-Host "[mysql ] starting..." -ForegroundColor Cyan
    Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=$xampp\mysql\bin\my.ini" -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

# 2) PHP web server at project root (so absolute / paths resolve)
if (Test-Port $Port) {
    Write-Host "[web   ] port $Port already in use - assuming server is up" -ForegroundColor DarkGray
} else {
    Write-Host "[web   ] starting PHP server on :$Port serving $repo" -ForegroundColor Cyan
    Start-Process -FilePath $php -ArgumentList '-S',"localhost:$Port",'-t',$repo -WindowStyle Hidden
    Start-Sleep -Seconds 1
}

$webUrl = "http://localhost:$Port/"
Write-Host "[web   ] $webUrl" -ForegroundColor Green
if (-not $NoOpen) { Start-Process $webUrl }

if ($NoApp) { Write-Host "[app   ] skipped (-NoApp)"; return }

# 3) Flutter app
$flutter = (Get-Command flutter -ErrorAction SilentlyContinue).Source
if (-not $flutter) { Write-Host "[app   ] flutter not in PATH - skipping" -ForegroundColor Yellow; return }

$appDir = Join-Path $repo 'pbsystem_app'
if (-not (Test-Path $appDir)) { Write-Host "[app   ] $appDir not found" -ForegroundColor Yellow; return }

if ($AppTarget -like 'emulator-*' -or $AppTarget -eq 'android') {
    $devices = & flutter devices 2>&1 | Out-String
    if ($devices -notmatch 'emulator-\d+') {
        Write-Host "[app   ] launching emulator $EmulatorId..." -ForegroundColor Cyan
        Start-Process -FilePath $flutter -ArgumentList 'emulators','--launch',$EmulatorId -WindowStyle Hidden
        Write-Host "[app   ] waiting for emulator boot..." -ForegroundColor Cyan
        $deadline = (Get-Date).AddMinutes(3)
        while ((Get-Date) -lt $deadline) {
            Start-Sleep -Seconds 5
            $devices = & flutter devices 2>&1 | Out-String
            if ($devices -match 'emulator-\d+') { break }
        }
        if ($devices -notmatch 'emulator-\d+') {
            Write-Host "[app   ] emulator did not appear - aborting app start" -ForegroundColor Yellow
            return
        }
    }
    if ($AppTarget -eq 'android') {
        # pick whichever emulator is up
        $AppTarget = ([regex]::Match($devices, 'emulator-\d+')).Value
    }
}

Write-Host "[app   ] flutter run -d $AppTarget (in new window)" -ForegroundColor Cyan
# API base URL override: emulator uses 10.0.2.2 to reach the host's PHP server.
$apiBase = if ($AppTarget -like 'emulator-*') { "http://10.0.2.2:$Port" } else { "http://localhost:$Port" }
$runArgs = "run -d $AppTarget --dart-define=API_BASE_URL=$apiBase"
Start-Process -FilePath 'powershell.exe' `
    -ArgumentList '-NoExit','-Command',"Set-Location '$appDir'; flutter $runArgs" `
    -WorkingDirectory $appDir

Write-Host ""
Write-Host "Stack up:" -ForegroundColor Green
Write-Host "  web : $webUrl"
Write-Host "  api : $apiBase  (used by Flutter app)"
Write-Host "  app : flutter run window opened for $AppTarget"
