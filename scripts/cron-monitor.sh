#!/bin/bash

# SafariChat Cron Monitor Script
# Usage: ./cron-monitor.sh [status|health|logs|start|stop]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Check if Laravel is in the correct directory
if [ ! -f "$LARAVEL_ROOT/artisan" ]; then
    print_error "Laravel artisan not found. Make sure you're in the correct directory."
    exit 1
fi

# Change to Laravel root directory
cd "$LARAVEL_ROOT"

case "${1:-status}" in
    "status")
        print_header "Cron Job Status"
        php artisan cron:monitor --action=status
        ;;
    "health")
        print_header "Cron Health Check"
        php artisan cron:monitor --action=health
        ;;
    "logs")
        print_header "Recent Cron Logs"
        php artisan cron:monitor --action=logs
        ;;
    "start")
        print_header "Starting Laravel Scheduler"
        print_status "Checking if scheduler is already running..."
        
        # Check if scheduler is running
        if pgrep -f "artisan schedule:run" > /dev/null; then
            print_warning "Laravel scheduler appears to be already running"
        else
            print_status "Starting Laravel scheduler in background..."
            nohup php artisan schedule:run >> storage/logs/scheduler.log 2>&1 &
            print_status "Scheduler started. Check storage/logs/scheduler.log for output"
        fi
        
        # Also run schedule:work if available (Laravel 8+)
        print_status "Starting schedule worker..."
        nohup php artisan schedule:work >> storage/logs/schedule-work.log 2>&1 &
        print_status "Schedule worker started"
        ;;
    "stop")
        print_header "Stopping Laravel Scheduler"
        pkill -f "artisan schedule:run"
        pkill -f "artisan schedule:work"
        print_status "Scheduler processes stopped"
        ;;
    "restart")
        print_header "Restarting Laravel Scheduler"
        pkill -f "artisan schedule:run"
        pkill -f "artisan schedule:work"
        sleep 2
        nohup php artisan schedule:run >> storage/logs/scheduler.log 2>&1 &
        nohup php artisan schedule:work >> storage/logs/schedule-work.log 2>&1 &
        print_status "Scheduler restarted"
        ;;
    "install-cron")
        print_header "Installing System Cron Job"
        
        # Add Laravel scheduler to system crontab
        cron_entry="* * * * * cd $LARAVEL_ROOT && php artisan schedule:run >> /dev/null 2>&1"
        
        # Check if already exists
        if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
            print_warning "Cron job already exists in crontab"
        else
            # Add to crontab
            (crontab -l 2>/dev/null; echo "$cron_entry") | crontab -
            print_status "Cron job added to system crontab"
            print_status "Entry: $cron_entry"
        fi
        ;;
    "remove-cron")
        print_header "Removing System Cron Job"
        crontab -l 2>/dev/null | grep -v "artisan schedule:run" | crontab -
        print_status "Cron job removed from system crontab"
        ;;
    "test")
        print_header "Testing Cron Configuration"
        print_status "Running scheduler once..."
        php artisan schedule:run -v
        
        print_status "Checking log permissions..."
        if [ -w "storage/logs" ]; then
            print_status "Log directory is writable"
        else
            print_error "Log directory is not writable"
        fi
        
        print_status "Testing specific commands..."
        php artisan cron:monitor --action=health
        ;;
    *)
        print_header "SafariChat Cron Monitor"
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  status        - Show cron job status"
        echo "  health        - Run health check"
        echo "  logs          - Show recent logs"
        echo "  start         - Start Laravel scheduler"
        echo "  stop          - Stop Laravel scheduler"
        echo "  restart       - Restart Laravel scheduler"
        echo "  install-cron  - Install system cron job"
        echo "  remove-cron   - Remove system cron job"
        echo "  test          - Test cron configuration"
        echo ""
        echo "Example: $0 health"
        ;;
esac

exit 0