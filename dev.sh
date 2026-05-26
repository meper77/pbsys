#!/bin/bash

# NEO V-TRACK Dev Stack Control Script
# Usage: bash dev.sh start|stop|restart|status

COMMAND="${1:-status}"
XAMPP_PATH="/c/xampp"
PROJECT_ROOT="$(pwd)"
PHP_PORT=8000
MYSQL_PORT=3306
EMULATOR_ID="Pixel_API_36"

LOG_DIR="$PROJECT_ROOT/.dev-logs"
mkdir -p "$LOG_DIR" 2>/dev/null || true

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Helper functions
log_info() {
    echo -e "${CYAN}[$(date '+%H:%M:%S')] [INFO] $1${NC}"
}

log_ok() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')] [OK] $1${NC}"
}

log_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] [ERROR] $1${NC}"
}

log_warn() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] [WARNING] $1${NC}"
}

is_port_open() {
    local port=$1
    netstat -tuln 2>/dev/null | grep -q ":$port " && return 0 || return 1
}

is_process_running() {
    local name=$1
    ps aux | grep -v grep | grep -q "$name" && return 0 || return 1
}

# START
start_all() {
    log_info "========================================"
    log_info "NEO V-TRACK Dev Stack Starting"
    log_info "========================================"
    echo ""

    # 1. MySQL
    log_info "1/4: Starting MySQL..."
    if is_process_running "mysqld"; then
        log_warn "MySQL already running"
    else
        cd "$XAMPP_PATH/mysql/bin"
        ./mysqld.exe --port=$MYSQL_PORT \
            --datadir="$XAMPP_PATH/mysql/data" \
            --default-storage-engine=InnoDB \
            > "$LOG_DIR/mysql.log" 2>&1 &
        
        MYSQL_PID=$!
        log_info "MySQL PID: $MYSQL_PID"
        sleep 3
        
        # Verify connection
        for i in {1..30}; do
            if "$XAMPP_PATH/mysql/bin/mysql" -u root -e "SELECT 1;" >/dev/null 2>&1; then
                log_ok "MySQL is ready"
                break
            fi
            log_info "Waiting for MySQL... (attempt $i/30)"
            sleep 1
        done
    fi
    echo ""

    # 2. PHP Server
    log_info "2/4: Starting PHP server on port $PHP_PORT..."
    if is_port_open $PHP_PORT; then
        log_warn "Port $PHP_PORT already in use"
    else
        cd "$PROJECT_ROOT"
        "$XAMPP_PATH/php/php.exe" -S 127.0.0.1:$PHP_PORT -t "$PROJECT_ROOT" \
            > "$LOG_DIR/php.log" 2>&1 &
        
        PHP_PID=$!
        log_info "PHP server PID: $PHP_PID"
        sleep 2
        log_ok "PHP server ready on http://localhost:$PHP_PORT"
    fi
    echo ""

    # 3. Emulator
    log_info "3/4: Launching Android emulator $EMULATOR_ID..."
    if adb devices 2>/dev/null | grep -q "emulator-.*device"; then
        log_warn "Emulator already running"
    else
        flutter emulators --launch $EMULATOR_ID \
            > "$LOG_DIR/emulator.log" 2>&1 &
        
        EMULATOR_PID=$!
        log_info "Emulator PID: $EMULATOR_PID"
        log_info "Emulator booting (this may take 1-2 minutes)..."
        
        # Wait for emulator
        for i in {1..120}; do
            if adb devices 2>/dev/null | grep -q "emulator-.*device"; then
                log_ok "Emulator online"
                break
            fi
            if [ $((i % 10)) -eq 0 ]; then
                log_info "Waiting for emulator... ($i/120s)"
            fi
            sleep 1
        done
    fi
    echo ""

    # 4. Flutter App
    log_info "4/4: Building and launching Flutter app..."
    cd "$PROJECT_ROOT/pbsystem_app"
    flutter run -d android --dart-define=API_BASE_URL=http://10.0.2.2:$PHP_PORT
    
    echo ""
    log_ok "========================================"
    log_ok "Dev Stack Started"
    log_ok "========================================"
    log_info "Services:"
    log_info "  MySQL:      localhost:$MYSQL_PORT"
    log_info "  PHP Server: http://localhost:$PHP_PORT"
    log_info "  Emulator:   emulator-5554"
    log_info "  Flutter:    (see window above)"
    echo ""
    log_info "Logs directory: $LOG_DIR"
    echo ""
}

# STOP
stop_all() {
    log_warn "========================================"
    log_warn "Stopping NEO V-TRACK Dev Stack"
    log_warn "========================================"
    echo ""

    log_info "Stopping Flutter..."
    ps aux | grep -v grep | grep "flutter run" | awk '{print $2}' | xargs -r kill -9 2>/dev/null
    sleep 1
    log_ok "Flutter stopped"

    log_info "Stopping PHP server..."
    ps aux | grep -v grep | grep "php.exe" | awk '{print $2}' | xargs -r kill -9 2>/dev/null
    sleep 1
    log_ok "PHP stopped"

    log_info "Stopping Android emulator..."
    adb emu kill 2>/dev/null || true
    ps aux | grep -v grep | grep "qemu-system" | awk '{print $2}' | xargs -r kill -9 2>/dev/null
    sleep 1
    log_ok "Emulator stopped"

    log_info "Stopping MySQL..."
    ps aux | grep -v grep | grep "mysqld" | awk '{print $2}' | xargs -r kill -9 2>/dev/null
    sleep 2
    log_ok "MySQL stopped"

    echo ""
    log_ok "========================================"
    log_ok "All services stopped"
    log_ok "========================================"
    echo ""
}

# STATUS
show_status() {
    log_info "========================================"
    log_info "NEO V-TRACK Dev Stack Status"
    log_info "========================================"
    echo ""

    # MySQL
    if is_process_running "mysqld"; then
        echo -e "${GREEN}✓ MySQL${NC}        Running (port 3306)"
    else
        echo -e "${RED}✗ MySQL${NC}        Stopped"
    fi

    # PHP
    if is_process_running "php.exe"; then
        echo -e "${GREEN}✓ PHP Server${NC}   Running (http://localhost:8000)"
    else
        echo -e "${RED}✗ PHP Server${NC}   Stopped"
    fi

    # Emulator
    if adb devices 2>/dev/null | grep -q "emulator-.*device"; then
        echo -e "${GREEN}✓ Emulator${NC}     Running"
    else
        echo -e "${RED}✗ Emulator${NC}     Stopped"
    fi

    # Flutter
    if is_process_running "flutter run"; then
        echo -e "${GREEN}✓ Flutter${NC}      Running"
    else
        echo -e "${RED}✗ Flutter${NC}      Stopped"
    fi

    echo ""
    log_info "Logs directory: $LOG_DIR"
    if [ -d "$LOG_DIR" ]; then
        log_info "Recent logs:"
        ls -lt "$LOG_DIR"/*.log 2>/dev/null | head -5 | while read -r line; do
            echo -e "  ${CYAN}$line${NC}"
        done
    fi
    echo ""
}

# RESTART
restart_all() {
    stop_all
    log_info "Waiting 3 seconds before restart..."
    sleep 3
    echo ""
    start_all
}

# Main dispatcher
case "$COMMAND" in
    start)
        start_all
        ;;
    stop)
        stop_all
        ;;
    restart)
        restart_all
        ;;
    status)
        show_status
        ;;
    *)
        echo ""
        log_warn "Unknown command: $COMMAND"
        echo ""
        echo "Usage: $0 [start|stop|restart|status]"
        echo ""
        echo "Examples:"
        echo -e "  ${CYAN}$0 start${NC}      # Start all services"
        echo -e "  ${CYAN}$0 stop${NC}       # Stop all services"
        echo -e "  ${CYAN}$0 restart${NC}    # Restart all services"
        echo -e "  ${CYAN}$0 status${NC}     # Show status of all services"
        echo ""
        exit 1
        ;;
esac
