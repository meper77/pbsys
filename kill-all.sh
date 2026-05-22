#!/bin/bash
# Aggressive kill script - forces termination of all dev services

echo "Force killing all NEO V-TRACK services..."
echo ""

# Kill MySQL - multiple attempts
echo "[1] Killing MySQL..."
for i in {1..5}; do
  cmd /c "taskkill /F /IM mysqld.exe" 2>/dev/null
  sleep 0.5
done

# Kill PHP - multiple attempts
echo "[2] Killing PHP..."
for i in {1..5}; do
  cmd /c "taskkill /F /IM php.exe" 2>/dev/null
  sleep 0.5
done

# Kill emulator
echo "[3] Killing emulator..."
adb emu kill 2>/dev/null || true
cmd /c "taskkill /F /IM qemu-system-x86_64.exe" 2>/dev/null || true

# Kill Flutter
echo "[4] Killing Flutter..."
cmd /c "taskkill /F /IM dart.exe" 2>/dev/null || true
cmd /c "taskkill /F /IM flutter.exe" 2>/dev/null || true

# Kill any remaining Java (Android SDK tools)
echo "[5] Killing Java processes..."
cmd /c "taskkill /F /IM java.exe" 2>/dev/null || true

sleep 2

echo ""
echo "Verifying..."
if tasklist | grep -iqE "mysqld|php"; then
  echo "⚠ Some processes still running. Checking details..."
  tasklist | grep -iE "mysqld|php"
else
  echo "✓ All services terminated"
fi

echo ""
echo "Port status:"
netstat -ano 2>/dev/null | grep -E ":3306|:8000" || echo "✓ All ports cleared"
