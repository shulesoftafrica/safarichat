#!/bin/bash

###############################################################################
# SafariChat Production Deployment Script
# Phase 5: Production Deployment
#
# Usage: ./scripts/deploy-production.sh
# Run on production server as deployment user
###############################################################################

set -e  # Exit on error

echo "========================================"
echo "SafariChat Production Deployment"
echo "========================================"
echo ""

# Configuration
APP_DIR="/var/www/safarichat"
BACKUP_DIR="/var/backups/safarichat"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Pre-deployment checks
log_info "Running pre-deployment checks..."

# Check if running as correct user
if [ "$USER" != "www-data" ] && [ "$USER" != "deploy" ]; then
    log_warn "Not running as www-data or deploy user. Current user: $USER"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check if app directory exists
if [ ! -d "$APP_DIR" ]; then
    log_error "Application directory not found: $APP_DIR"
    exit 1
fi

cd "$APP_DIR"

# Check git status
log_info "Checking git status..."
if [ -n "$(git status --porcelain)" ]; then
    log_warn "Working directory has uncommitted changes"
    git status --short
    read -p "Continue deployment? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Backup current state
log_info "Creating backup..."
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/safarichat_$TIMESTAMP.tar.gz" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    .
log_info "Backup created: $BACKUP_DIR/safarichat_$TIMESTAMP.tar.gz"

# Database backup
log_info "Backing up database..."
php artisan db:backup --filename="production_$TIMESTAMP.sql" 2>/dev/null || true

# Enable maintenance mode
log_info "Enabling maintenance mode..."
php artisan down --retry=60 --secret="$(openssl rand -hex 16)"

# Pull latest code
log_info "Pulling latest code from main branch..."
git fetch origin
git pull origin main

# Install dependencies
log_info "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches before migration
log_info "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run database migrations
log_info "Running database migrations..."
php artisan migrate --force

# Optimize application
log_info "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
log_info "Restarting queue workers..."
php artisan queue:restart

# Disable maintenance mode
log_info "Disabling maintenance mode..."
php artisan up

# Post-deployment checks
log_info "Running post-deployment checks..."

# Check if application is accessible
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://safarichat.com/health 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    log_info "Health check passed (HTTP $HTTP_CODE)"
else
    log_error "Health check failed (HTTP $HTTP_CODE)"
    log_error "Application may not be accessible!"
fi

# Check webhook endpoint
WEBHOOK_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST https://safarichat.com/api/billing/webhook 2>/dev/null || echo "000")
if [ "$WEBHOOK_CODE" = "401" ] || [ "$WEBHOOK_CODE" = "400" ]; then
    log_info "Webhook endpoint responding (HTTP $WEBHOOK_CODE)"
else
    log_warn "Webhook endpoint returned unexpected code: $WEBHOOK_CODE"
fi

# Summary
echo ""
echo "========================================"
log_info "Deployment completed successfully!"
echo "========================================"
echo ""
echo "Next steps:"
echo "  1. Monitor logs: tail -f storage/logs/laravel.log"
echo "  2. Check webhook admin: https://safarichat.com/admin/billing/webhooks"
echo "  3. Test with small payment"
echo "  4. Monitor for 24 hours"
echo ""
echo "Rollback command (if needed):"
echo "  tar -xzf $BACKUP_DIR/safarichat_$TIMESTAMP.tar.gz -C $APP_DIR"
echo ""
