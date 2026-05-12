#!/bin/bash

# Flutter Live Preview Script
# Quick launcher for hot reload development

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔═══════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Flutter Live Preview for Android     ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════╝${NC}"

# Check if Flutter is installed
if ! command -v flutter &> /dev/null; then
    echo -e "${RED}✗ Flutter not found. Please install Flutter and add it to PATH.${NC}"
    exit 1
fi

# Check if in Flutter project
if [ ! -f "pubspec.yaml" ]; then
    echo -e "${RED}✗ Not in a Flutter project directory (no pubspec.yaml found).${NC}"
    exit 1
fi

# List available devices
echo -e "${YELLOW}📱 Available devices:${NC}"
flutter devices || {
    echo -e "${RED}✗ No devices found. Please connect a device or start an emulator.${NC}"
    exit 1
}

# Get device selection
echo ""
if [ -z "$1" ]; then
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0                           (run on default device)"
    echo "  $0 <device-id>               (run on specific device)"
    echo "  $0 --list                    (list available devices)"
    echo ""
    echo -e "${BLUE}Running on default device...${NC}"
    DEVICE_FLAG=""
else
    if [ "$1" = "--list" ]; then
        flutter devices --machine
        exit 0
    fi
    DEVICE_FLAG="-d $1"
fi

# Clean previous builds (optional)
echo -e "${YELLOW}🧹 Cleaning previous builds...${NC}"
flutter clean || true

# Run the app with hot reload enabled
echo -e "${GREEN}▶ Starting Flutter app with hot reload...${NC}"
echo -e "${GREEN}Shortcuts: r=reload, R=restart, q=quit, h=help${NC}"
echo ""

flutter run $DEVICE_FLAG
