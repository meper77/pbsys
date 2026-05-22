@echo off
REM NEO V-TRACK Development Fullstack Startup (Windows Batch)
REM Launches: MySQL, PHP built-in server, Android emulator, Flutter app

setlocal enabledelayedexpansion

set XAMPP_PATH=C:\xampp
set PHP_PORT=8000
set EMULATOR_ID=Pixel_API_36

echo.
echo === NEO V-TRACK Dev Stack Startup ===
echo XAMPP: %XAMPP_PATH%
echo PHP port: %PHP_PORT%
echo.

REM 1. Start MySQL
echo [1/4] Starting MySQL...
start "MySQL Server" "%XAMPP_PATH%\mysql\bin\mysqld" --port=3306 --datadir="%XAMPP_PATH%\mysql\data" --default-storage-engine=InnoDB
timeout /t 3 /nobreak

echo Verifying MySQL connection...
for /l %%i in (1,1,30) do (
  "%XAMPP_PATH%\mysql\bin\mysql" -u root -e "SELECT 1" >nul 2>&1
  if !errorlevel! equ 0 (
    echo [OK] MySQL is ready
    goto mysql_done
  )
  echo  Attempt %%i/30...
  timeout /t 1 /nobreak
)
:mysql_done

REM 2. Start PHP server
echo.
echo [2/4] Starting PHP built-in server on port %PHP_PORT%...
cd /d "%cd%"
start "PHP Server" "%XAMPP_PATH%\php\php.exe" -S localhost:%PHP_PORT%
timeout /t 2 /nobreak
echo [OK] PHP server started (PID shown in window title)

REM 3. Launch Android emulator
echo.
echo [3/4] Checking Android emulator...
flutter emulators --launch %EMULATOR_ID% >nul 2>&1 &
echo Emulator launching (may take 1-2 minutes)...
timeout /t 30 /nobreak

REM 4. Launch Flutter app
echo.
echo [4/4] Launching Flutter app...
cd /d "%~dp0pbsystem_app"
echo.
echo Available devices:
flutter devices
echo.
flutter run -d android --dart-define=API_BASE_URL=http://localhost:%PHP_PORT%

pause
