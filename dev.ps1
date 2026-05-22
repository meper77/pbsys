param(
    [ValidateSet("start", "stop", "restart", "status")]
    [string]$Command = "status"
)

$XAMPP_PATH = "C:\xampp"
$PROJECT_ROOT = "C:\Users\User.J1-ALPHA-PENS\pbsys"
$PHP_PORT = 8000
$MYSQL_PORT = 3306
$EMULATOR_ID = "Pixel_API_36"

$LogDir = "$PROJECT_ROOT\.dev-logs"
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

# Helper functions
function Write-Status {
    param([string]$Message, [string]$Type = "INFO")
    $Color = @{
        "INFO"    = "Cyan"
        "OK"      = "Green"
        "ERROR"   = "Red"
        "WARNING" = "Yellow"
    }
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] [$Type] $Message" -ForegroundColor $Color[$Type]
}

function Is-ProcessRunning {
    param([string]$ProcessName)
    $proc = Get-Process $ProcessName -ErrorAction SilentlyContinue
    return $null -ne $proc
}

function Get-ProcessPort {
    param([int]$Port)
    $proc = netstat -ano | Select-String ":$Port\s" | Select-Object -First 1
    return $proc
}

function Start-Service {
    param([string]$Name, [scriptblock]$ScriptBlock)
    
    Write-Status "Starting $Name..."
    try {
        & $ScriptBlock
        Write-Status "$Name started successfully" "OK"
        return $true
    }
    catch {
        Write-Status "Failed to start $Name`: $($_.Exception.Message)" "ERROR"
        return $false
    }
}

# START COMMAND
function Invoke-Start {
    Write-Status "========================================" "INFO"
    Write-Status "NEO V-TRACK Dev Stack Starting" "INFO"
    Write-Status "========================================" "INFO"
    Write-Status ""

    # 1. MySQL
    Write-Status "1/4: Starting MySQL..."
    if (Is-ProcessRunning "mysqld") {
        Write-Status "MySQL already running" "WARNING"
    }
    else {
        $MySQLLog = "$LogDir\mysql.log"
        $MySQLProc = Start-Process -FilePath "$XAMPP_PATH\mysql\bin\mysqld.exe" `
            -ArgumentList "--port=$MYSQL_PORT", "--datadir=`"$XAMPP_PATH\mysql\data`"", "--default-storage-engine=InnoDB" `
            -WindowStyle Hidden `
            -RedirectStandardOutput $MySQLLog `
            -RedirectStandardError "$LogDir\mysql-error.log" `
            -PassThru
        
        Write-Status "MySQL PID: $($MySQLProc.Id)" "INFO"
        Start-Sleep -Seconds 3
        
        # Verify connection
        $Connected = $false
        for ($i = 1; $i -le 30; $i++) {
            try {
                $Output = & "$XAMPP_PATH\mysql\bin\mysql.exe" -u root -e "SELECT 1;" 2>$null
                if ($Output) {
                    Write-Status "MySQL is ready" "OK"
                    $Connected = $true
                    break
                }
            }
            catch { }
            Write-Status "Waiting for MySQL... (attempt $i/30)" "INFO"
            Start-Sleep -Seconds 1
        }
        
        if (-not $Connected) {
            Write-Status "MySQL did not respond" "WARNING"
        }
    }
    Write-Status ""

    # 2. PHP Server
    Write-Status "2/4: Starting PHP server on port $PHP_PORT..."
    if (Get-ProcessPort -Port $PHP_PORT) {
        Write-Status "Port $PHP_PORT already in use" "WARNING"
    }
    else {
        $PHPLog = "$LogDir\php.log"
        $PHPProc = Start-Process -FilePath "$XAMPP_PATH\php\php.exe" `
            -ArgumentList "-S", "localhost:$PHP_PORT" `
            -WorkingDirectory $PROJECT_ROOT `
            -WindowStyle Hidden `
            -RedirectStandardOutput $PHPLog `
            -RedirectStandardError "$LogDir\php-error.log" `
            -PassThru
        
        Write-Status "PHP server PID: $($PHPProc.Id)" "INFO"
        Start-Sleep -Seconds 2
        Write-Status "PHP server ready on http://localhost:$PHP_PORT" "OK"
    }
    Write-Status ""

    # 3. Emulator
    Write-Status "3/4: Launching Android emulator $EMULATOR_ID..."
    if (adb devices 2>$null | Select-String "emulator-" | Select-String "device") {
        Write-Status "Emulator already running" "WARNING"
    }
    else {
        $EmulatorLog = "$LogDir\emulator.log"
        $EmulatorProc = Start-Process -FilePath "flutter.exe" `
            -ArgumentList "emulators", "--launch", $EMULATOR_ID `
            -WindowStyle Hidden `
            -RedirectStandardOutput $EmulatorLog `
            -RedirectStandardError "$LogDir\emulator-error.log" `
            -PassThru
        
        Write-Status "Emulator PID: $($EmulatorProc.Id)" "INFO"
        Write-Status "Emulator booting (this may take 1-2 minutes)..." "INFO"
        
        # Wait for emulator to be ready
        $EmulatorReady = $false
        for ($i = 1; $i -le 120; $i++) {
            try {
                $Devices = adb devices 2>$null | Select-String "emulator-.*device"
                if ($Devices) {
                    Write-Status "Emulator online" "OK"
                    $EmulatorReady = $true
                    break
                }
            }
            catch { }
            
            if ($i % 10 -eq 0) {
                Write-Status "Waiting for emulator... ($i/120s)" "INFO"
            }
            Start-Sleep -Seconds 1
        }
        
        if (-not $EmulatorReady) {
            Write-Status "Emulator did not come online in time" "WARNING"
        }
    }
    Write-Status ""

    # 4. Flutter App
    Write-Status "4/4: Building and launching Flutter app..."
    $FlutterLog = "$LogDir\flutter.log"
    
    # Get the emulator device ID
    $EmulatorDevice = (adb devices 2>$null | Select-String "emulator-.*device" | ForEach-Object { $_.Line.Split()[0] } | Select-Object -First 1)
    
    if (-not $EmulatorDevice) {
        Write-Status "No emulator device found. Emulator may not be fully ready." "ERROR"
        Write-Status "Try running: flutter emulators --launch $EMULATOR_ID" "INFO"
        return
    }
    
    Write-Status "Found emulator: $EmulatorDevice" "INFO"
    
    $FlutterProc = Start-Process -FilePath "flutter.exe" `
        -ArgumentList "run", "-d", $EmulatorDevice, "--dart-define=API_BASE_URL=http://localhost:$PHP_PORT" `
        -WorkingDirectory "$PROJECT_ROOT\pbsystem_app" `
        -WindowStyle Normal `
        -PassThru
    
    Write-Status "Flutter PID: $($FlutterProc.Id)" "INFO"
    Write-Status "Flutter is building (check the Flutter window for progress)" "INFO"
    
    Write-Status ""
    Write-Status "========================================" "INFO"
    Write-Status "Dev Stack Started" "OK"
    Write-Status "========================================" "INFO"
    Write-Status "Services:" "INFO"
    Write-Status "  MySQL:      localhost:$MYSQL_PORT" "INFO"
    Write-Status "  PHP Server: http://localhost:$PHP_PORT" "INFO"
    Write-Status "  Emulator:   emulator-5554" "INFO"
    Write-Status "  Flutter:    (building...)" "INFO"
    Write-Status ""
    Write-Status "Logs directory: $LogDir" "INFO"
    Write-Status "To view logs: Get-Content $LogDir\*.log -Wait" "INFO"
    Write-Status ""
}

# STOP COMMAND
function Invoke-Stop {
    Write-Status "========================================" "WARNING"
    Write-Status "Stopping NEO V-TRACK Dev Stack" "WARNING"
    Write-Status "========================================" "WARNING"
    Write-Status ""

    # Kill Flutter
    Write-Status "Stopping Flutter..."
    Get-Process flutter -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 1
    Write-Status "Flutter stopped" "OK"

    # Kill PHP
    Write-Status "Stopping PHP server..."
    Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 1
    Write-Status "PHP stopped" "OK"

    # Kill Emulator
    Write-Status "Stopping Android emulator..."
    adb emu kill 2>$null
    Get-Process qemu-system-x86_64 -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 1
    Write-Status "Emulator stopped" "OK"

    # Kill MySQL
    Write-Status "Stopping MySQL..."
    Get-Process mysqld -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 2
    Write-Status "MySQL stopped" "OK"

    Write-Status ""
    Write-Status "========================================" "OK"
    Write-Status "All services stopped" "OK"
    Write-Status "========================================" "OK"
}

# STATUS COMMAND
function Invoke-Status {
    Write-Status "========================================" "INFO"
    Write-Status "NEO V-TRACK Dev Stack Status" "INFO"
    Write-Status "========================================" "INFO"
    Write-Status ""

    $StatusTable = @()

    # Check MySQL
    $MySQLRunning = Is-ProcessRunning "mysqld"
    $MySQLStatus = if ($MySQLRunning) { "✓ Running" } else { "✗ Stopped" }
    $StatusTable += [PSCustomObject]@{ Service = "MySQL"; Status = $MySQLStatus; Port = "3306" }

    # Check PHP
    $PHPRunning = Is-ProcessRunning "php"
    $PHPStatus = if ($PHPRunning) { "✓ Running" } else { "✗ Stopped" }
    $StatusTable += [PSCustomObject]@{ Service = "PHP Server"; Status = $PHPStatus; Port = "8000" }

    # Check Emulator
    $EmulatorRunning = $false
    try {
        $EmulatorRunning = (adb devices 2>$null | Select-String "emulator-.*device").Count -gt 0
    }
    catch { }
    $EmulatorStatus = if ($EmulatorRunning) { "✓ Running" } else { "✗ Stopped" }
    $StatusTable += [PSCustomObject]@{ Service = "Emulator"; Status = $EmulatorStatus; Port = "5554" }

    # Check Flutter
    $FlutterRunning = Is-ProcessRunning "flutter"
    $FlutterStatus = if ($FlutterRunning) { "✓ Running" } else { "✗ Stopped" }
    $StatusTable += [PSCustomObject]@{ Service = "Flutter"; Status = $FlutterStatus; Port = "N/A" }

    $StatusTable | Format-Table -AutoSize

    Write-Status ""
    Write-Status "Logs directory: $LogDir" "INFO"
    if (Test-Path $LogDir) {
        Write-Status "Recent logs:" "INFO"
        Get-ChildItem $LogDir -File | Sort-Object LastWriteTime -Descending | Select-Object -First 5 | ForEach-Object {
            Write-Host "  - $($_.Name) ($(($_.LastWriteTime).ToString('HH:mm:ss')))" -ForegroundColor Cyan
        }
    }
    Write-Status ""
}

# RESTART COMMAND
function Invoke-Restart {
    Invoke-Stop
    Write-Status ""
    Write-Status "Waiting 3 seconds before restart..." "INFO"
    Start-Sleep -Seconds 3
    Write-Status ""
    Invoke-Start
}

# Main dispatcher
switch ($Command) {
    "start"   { Invoke-Start }
    "stop"    { Invoke-Stop }
    "restart" { Invoke-Restart }
    "status"  { Invoke-Status }
    default   { 
        Write-Status "Unknown command: $Command" "ERROR"
        Write-Host ""
        Write-Host "Usage: .\dev.ps1 [start|stop|restart|status]" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\dev.ps1 start      # Start all services" -ForegroundColor Cyan
        Write-Host "  .\dev.ps1 stop       # Stop all services" -ForegroundColor Cyan
        Write-Host "  .\dev.ps1 restart    # Restart all services" -ForegroundColor Cyan
        Write-Host "  .\dev.ps1 status     # Show status of all services" -ForegroundColor Cyan
        exit 1
    }
}
