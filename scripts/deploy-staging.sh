#!/bin/bash

###############################################################################
# SafariChat Staging Deployment Script
# Phase 5: Staging Deployment & Testing
#
# Usage: ./scripts/deploy-staging.sh
# Run on staging server as deployment user
###############################################################################

set -e  # Exit on error

echo "========================================"
echo "SafariChat Staging Deployment"
echo "========================================"
echo ""

# Configuration
APP_DIR="/var/www/safarichat-staging"
STAGING_URL="https://staging.safarichat.com"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Navigate to app directory
cd "$APP_DIR"

# Pull latest code
log_info "Pulling latest code..."
git fetch origin
git pull origin main

# Install dependencies
log_info "Installing dependencies..."
composer install --optimize-autoloader --no-interaction

# Clear caches
log_info "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
log_info "Running migrations..."
php artisan migrate --force

# Cache configurations
log_info "Caching configurations..."
php artisan config:cache
php artisan route:cache

# Run tests
log_info "Running automated tests..."
php artisan test --filter=BillingWebhook --stop-on-failure

# Check webhook endpoint
log_info "Testing webhook endpoint..."
WEBHOOK_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$STAGING_URL/api/billing/webhook" || echo "000")
if [ "$WEBHOOK_RESPONSE" = "401" ] || [ "$WEBHOOK_RESPONSE" = "400" ]; then
    log_info "Webhook endpoint responding correctly (HTTP $WEBHOOK_RESPONSE)"
else
    log_warn "Unexpected webhook response: $WEBHOOK_RESPONSE"
fi

# Summary
echo ""
echo "========================================"
log_info "Staging deployment completed!"
echo "========================================"
echo ""
echo "Next steps:"
echo "  1. Test webhook with billing platform test mode"
echo "  2. Send test webhook: $STAGING_URL/api/billing/webhook"
echo "  3. Review logs: tail -f storage/logs/laravel.log"
echo "  4. Check admin panel: $STAGING_URL/admin/billing/webhooks"
echo ""
