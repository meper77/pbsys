#!/bin/bash

# NEO V-TRACK Development Fullstack Startup
# Launches: MySQL, PHP built-in server, Android emulator, Flutter app

set -e

XAMPP_PATH="/c/xampp"
PBSYS_ROOT="$(cd "$(dirname "$0")" && pwd)"
PHP_PORT=8000
EMULATOR_ID="Pixel_API_36"
DB_SOCKET="/tmp/mysql.sock"

echo "=== NEO V-TRACK Dev Stack Startup ==="
echo "XAMPP: $XAMPP_PATH"
echo "Project: $PBSYS_ROOT"
echo "PHP port: $PHP_PORT"
echo ""

# 1. Start MySQL
echo "[1/4] Starting MySQL..."
mkdir -p "$XAMPP_PATH/mysql/data"
"$XAMPP_PATH/mysql/bin/mysqld" --port=3306 --datadir="$XAMPP_PATH/mysql/data" \
  --socket="$DB_SOCKET" --default-storage-engine=InnoDB &
MYSQL_PID=$!
echo "MySQL PID: $MYSQL_PID"

# Wait for MySQL to be ready
sleep 3
echo "Waiting for MySQL to accept connections..."
for i in {1..30}; do
  if "$XAMPP_PATH/mysql/bin/mysql" -u root --socket="$DB_SOCKET" -e "SELECT 1" &>/dev/null; then
    echo "✓ MySQL is ready"
    break
  fi
  echo "  Attempt $i/30..."
  sleep 1
done

# 2. Start PHP built-in server
echo ""
echo "[2/4] Starting PHP built-in server on port $PHP_PORT..."
cd "$PBSYS_ROOT"
"$XAMPP_PATH/php/php.exe" -S localhost:$PHP_PORT > php-server.log 2>&1 &
PHP_PID=$!
echo "PHP server PID: $PHP_PID"
sleep 2

# Verify PHP is listening
if curl -s "http://localhost:$PHP_PORT/includes/connect.php" > /dev/null 2>&1 || true; then
  echo "✓ PHP server is responding"
else
  echo "⚠ PHP server may not be fully ready yet, continuing..."
fi

# 3. Launch Android emulator (if not already running)
echo ""
echo "[3/4] Checking Android emulator..."
if flutter emulators | grep -q "running"; then
  echo "✓ Emulator already running"
else
  echo "Launching emulator $EMULATOR_ID..."
  flutter emulators --launch "$EMULATOR_ID" > emulator.log 2>&1 &
  EMULATOR_PID=$!
  echo "Emulator PID: $EMULATOR_PID"
  echo "Waiting for emulator to boot (this may take 1-2 minutes)..."
  sleep 30
fi

# 4. Wait for devices and launch Flutter app
echo ""
echo "[4/4] Launching Flutter app..."
cd "$PBSYS_ROOT/pbsystem_app"

echo "Waiting for adb devices..."
adb wait-for-device 2>/dev/null || true
sleep 2

echo "Available devices:"
flutter devices

echo ""
echo "=== Starting Flutter app ==="
flutter run -d android --dart-define=API_BASE_URL=http://localhost:$PHP_PORT

# Cleanup on exit
trap "echo 'Shutting down...'; kill $MYSQL_PID $PHP_PID 2>/dev/null || true" EXIT
