#!/bin/bash

###############################################################################
# Webhook Health Monitor
# Checks webhook processing status and alerts on issues
#
# Usage: ./scripts/monitor-webhooks.sh [hours]
# Example: ./scripts/monitor-webhooks.sh 24  (monitor last 24 hours)
###############################################################################

# Configuration
HOURS=${1:-24}  # Default: last 24 hours
ALERT_THRESHOLD=95  # Alert if success rate below this percentage
SLACK_WEBHOOK_URL="${SLACK_BILLING_WEBHOOK_URL:-}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo ""
echo "========================================"
echo "Webhook Health Monitor - Last $HOURS Hours"
echo "========================================"
echo ""

# Change to app directory
cd "$(dirname "$0")/.." || exit 1

# Database query for webhook statistics
STATS=$(php artisan tinker --execute="
\$stats = DB::table('billing_webhook_events')
    ->select(
        DB::raw('processing_status'),
        DB::raw('COUNT(*) as count'),
        DB::raw('ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage')
    )
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->groupBy('processing_status')
    ->get();
echo json_encode(\$stats);
")

# Total webhook count
TOTAL=$(php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->count();
")

# Failed webhook count
FAILED=$(php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('processing_status', 'failed')
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->count();
")

# Success webhook count
SUCCESS=$(php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('processing_status', 'success')
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->count();
")

# Processing webhook count
PROCESSING=$(php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('processing_status', 'processing')
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->count();
")

# Calculate success rate
if [ "$TOTAL" -gt 0 ]; then
    SUCCESS_RATE=$(echo "scale=2; $SUCCESS * 100 / $TOTAL" | bc)
else
    SUCCESS_RATE=0
fi

# Display statistics
echo -e "${BLUE}Total Webhooks:${NC} $TOTAL"
echo -e "${GREEN}Successful:${NC} $SUCCESS (${SUCCESS_RATE}%)"
echo -e "${YELLOW}Processing:${NC} $PROCESSING"
echo -e "${RED}Failed:${NC} $FAILED"
echo ""

# Status indicator
if (( $(echo "$SUCCESS_RATE >= $ALERT_THRESHOLD" | bc -l) )); then
    echo -e "${GREEN}✓ Status: HEALTHY${NC}"
    STATUS="HEALTHY"
else
    echo -e "${RED}✗ Status: DEGRADED${NC}"
    STATUS="DEGRADED"
fi

echo ""

# Show recent failures if any
if [ "$FAILED" -gt 0 ]; then
    echo "========================================"
    echo "Recent Failures (Last 10):"
    echo "========================================"
    
    php artisan tinker --execute="
    \$failures = DB::table('billing_webhook_events')
        ->select('id', 'event_type', 'error_message', 'created_at')
        ->where('processing_status', 'failed')
        ->where('created_at', '>=', now()->subHours($HOURS))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach (\$failures as \$failure) {
        echo \"ID: {\$failure->id} | Event: {\$failure->event_type} | Time: {\$failure->created_at}\n\";
        echo \"Error: {\$failure->error_message}\n\";
        echo \"---\n\";
    }
    "
    echo ""
fi

# Show stuck webhooks (processing > 5 minutes)
STUCK=$(php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('processing_status', 'processing')
    ->where('created_at', '<', now()->subMinutes(5))
    ->count();
")

if [ "$STUCK" -gt 0 ]; then
    echo "========================================"
    echo -e "${YELLOW}⚠ Warning: $STUCK Stuck Webhooks${NC}"
    echo "========================================"
    echo "Webhooks stuck in 'processing' state for >5 minutes"
    echo ""
    
    php artisan tinker --execute="
    \$stuck = DB::table('billing_webhook_events')
        ->select('id', 'event_type', 'created_at')
        ->where('processing_status', 'processing')
        ->where('created_at', '<', now()->subMinutes(5))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach (\$stuck as \$webhook) {
        echo \"ID: {\$webhook->id} | Event: {\$webhook->event_type} | Stuck since: {\$webhook->created_at}\n\";
    }
    "
    echo ""
fi

# Event type distribution
echo "========================================"
echo "Event Type Distribution:"
echo "========================================"

php artisan tinker --execute="
\$distribution = DB::table('billing_webhook_events')
    ->select('event_type', DB::raw('COUNT(*) as count'))
    ->where('created_at', '>=', now()->subHours($HOURS))
    ->groupBy('event_type')
    ->orderBy('count', 'desc')
    ->get();

foreach (\$distribution as \$row) {
    echo str_pad(\$row->event_type, 25) . \": \" . \$row->count . \"\n\";
}
"

echo ""

# Send Slack alert if configured and status is degraded
if [ -n "$SLACK_WEBHOOK_URL" ] && [ "$STATUS" = "DEGRADED" ]; then
    echo "Sending Slack alert..."
    
    MESSAGE="🚨 *Webhook Health Alert*\n\nSuccess Rate: ${SUCCESS_RATE}% (below ${ALERT_THRESHOLD}% threshold)\n\nTotal: $TOTAL\nSuccess: $SUCCESS\nFailed: $FAILED\nProcessing: $PROCESSING\n\nLast $HOURS hours"
    
    curl -X POST "$SLACK_WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -d "{\"text\":\"$MESSAGE\"}" \
        --silent --output /dev/null
    
    echo "✓ Slack alert sent"
    echo ""
fi

echo "========================================"
echo "Recommendations:"
echo "========================================"

if [ "$FAILED" -gt 0 ]; then
    echo "- Review failed webhooks in admin panel"
    echo "- Check Laravel logs: tail -f storage/logs/laravel.log"
    echo "- Verify database connectivity"
fi

if [ "$STUCK" -gt 0 ]; then
    echo "- Restart queue workers: php artisan queue:restart"
    echo "- Check queue:work processes running"
    echo "- Review timeout settings"
fi

if [ "$TOTAL" -eq 0 ]; then
    echo "- No webhooks received in last $HOURS hours"
    echo "- Verify webhook registered with billing platform"
    echo "- Check firewall/IP whitelist settings"
    echo "- Test webhook endpoint: curl -X POST https://safarichat.com/api/billing/webhook"
fi

if (( $(echo "$SUCCESS_RATE >= $ALERT_THRESHOLD" | bc -l) )) && [ "$TOTAL" -gt 0 ]; then
    echo "✓ System operating normally"
fi

echo ""
