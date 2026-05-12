#!/bin/bash

# Flutter Dev Tools - Helper commands

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

show_menu() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════╗"
    echo "║  Flutter Development Tools         ║"
    echo "╚════════════════════════════════════╝${NC}"
    echo ""
    echo "1. 🚀 Start Live Preview (Hot Reload)"
    echo "2. 📱 List Connected Devices"
    echo "3. 🧹 Clean Build"
    echo "4. 📊 Open DevTools"
    echo "5. 🔍 Show Flutter Logs"
    echo "6. 🏗️  Build Release APK"
    echo "7. 💾 Build Debug APK"
    echo "8. 🧪 Run Tests"
    echo "9. 📋 Show Keyboard Commands"
    echo "0. ❌ Exit"
    echo ""
}

show_keyboard_commands() {
    echo -e "${BLUE}Keyboard Commands During Live Preview:${NC}"
    echo ""
    echo "  r  → Hot Reload (fast UI update)"
    echo "  R  → Hot Restart (full app restart)"
    echo "  h  → Show all commands"
    echo "  w  → Dump widget hierarchy"
    echo "  t  → Dump render tree"
    echo "  S  → Toggle stats overlay"
    echo "  U  → Toggle widget inspector"
    echo "  p  → Toggle performance overlay"
    echo "  M  → Print memory usage"
    echo "  q  → Quit"
    echo ""
}

while true; do
    show_menu
    read -p "Select option: " choice

    case $choice in
        1)
            echo -e "${GREEN}Starting Live Preview...${NC}"
            ./run-live-preview.sh
            ;;
        2)
            echo -e "${GREEN}Connected Devices:${NC}"
            flutter devices
            ;;
        3)
            echo -e "${GREEN}Cleaning build...${NC}"
            flutter clean
            echo -e "${GREEN}✓ Clean complete${NC}"
            ;;
        4)
            echo -e "${GREEN}Starting DevTools...${NC}"
            flutter pub global activate devtools
            flutter pub global run devtools
            ;;
        5)
            echo -e "${GREEN}Showing Flutter Logs...${NC}"
            flutter logs
            ;;
        6)
            echo -e "${GREEN}Building Release APK...${NC}"
            flutter build apk --release
            echo -e "${GREEN}✓ APK built: build/app/outputs/flutter-app.apk${NC}"
            ;;
        7)
            echo -e "${GREEN}Building Debug APK...${NC}"
            flutter build apk --debug
            echo -e "${GREEN}✓ APK built: build/app/outputs/flutter-app.apk${NC}"
            ;;
        8)
            echo -e "${GREEN}Running Tests...${NC}"
            flutter test
            ;;
        9)
            show_keyboard_commands
            ;;
        0)
            echo -e "${YELLOW}Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${YELLOW}Invalid option. Please try again.${NC}"
            ;;
    esac

    echo ""
    read -p "Press Enter to continue..."
done
