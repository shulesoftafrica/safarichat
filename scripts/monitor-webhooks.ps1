# Webhook Health Monitor (PowerShell)
# Checks webhook processing status and reports health metrics
#
# Usage: .\scripts\monitor-webhooks.ps1 [-Hours 24]

param(
    [int]$Hours = 24
)

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Webhook Health Monitor - Last $Hours Hours" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get webhook statistics using Artisan Tinker
$statsJson = php artisan tinker --execute="
`$stats = DB::table('billing_webhook_events')
    ->where('created_at', '>=', now()->subHours($Hours))
    ->selectRaw('processing_status, COUNT(*) as count')
    ->groupBy('processing_status')
    ->get();
echo json_encode(`$stats);
" 2>$null

$stats = $statsJson | ConvertFrom-Json

# Calculate totals
$total = ($stats | Measure-Object -Property count -Sum).Sum
$success = ($stats | Where-Object { $_.processing_status -eq 'success' }).count
$failed = ($stats | Where-Object { $_.processing_status -eq 'failed' }).count
$processing = ($stats | Where-Object { $_.processing_status -eq 'processing' }).count

if ($null -eq $success) { $success = 0 }
if ($null -eq $failed) { $failed = 0 }
if ($null -eq $processing) { $processing = 0 }
if ($null -eq $total) { $total = 0 }

# Calculate success rate
if ($total -gt 0) {
    $successRate = [math]::Round(($success / $total) * 100, 2)
} else {
    $successRate = 0
}

# Display statistics
Write-Host "Total Webhooks:  $total" -ForegroundColor White
Write-Host "Successful:      $success ($successRate%)" -ForegroundColor Green
Write-Host "Processing:      $processing" -ForegroundColor Yellow
Write-Host "Failed:          $failed" -ForegroundColor Red
Write-Host ""

# Health status
$alertThreshold = 95
if ($successRate -ge $alertThreshold -or $total -eq 0) {
    Write-Host "[OK] Status: HEALTHY" -ForegroundColor Green
    $status = "HEALTHY"
} else {
    Write-Host "[!!] Status: DEGRADED" -ForegroundColor Red
    $status = "DEGRADED"
}

Write-Host ""

# Show recent failures
if ($failed -gt 0) {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Recent Failures (Last 10):" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    
    $failuresJson = php artisan tinker --execute="
    `$failures = DB::table('billing_webhook_events')
        ->select('id', 'event_type', 'error_message', 'created_at')
        ->where('processing_status', 'failed')
        ->where('created_at', '>=', now()->subHours($Hours))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    echo json_encode(`$failures);
    " 2>$null
    
    $failures = $failuresJson | ConvertFrom-Json
    
    foreach ($failure in $failures) {
        Write-Host "ID: $($failure.id) | Event: $($failure.event_type)" -ForegroundColor Yellow
        Write-Host "Time: $($failure.created_at)" -ForegroundColor Gray
        Write-Host "Error: $($failure.error_message)" -ForegroundColor Red
        Write-Host "---" -ForegroundColor Gray
    }
    
    Write-Host ""
}

# Check for stuck webhooks
$stuckCount = php artisan tinker --execute="
echo DB::table('billing_webhook_events')
    ->where('processing_status', 'processing')
    ->where('created_at', '<', now()->subMinutes(5))
    ->count();
" 2>$null

$stuckCount = [int]$stuckCount

if ($stuckCount -gt 0) {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "[WARNING] $stuckCount Stuck Webhooks" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Webhooks stuck in 'processing' status for >5 minutes" -ForegroundColor Yellow
    Write-Host ""
    
    $stuckJson = php artisan tinker --execute="
    `$stuck = DB::table('billing_webhook_events')
        ->select('id', 'event_type', 'created_at')
        ->where('processing_status', 'processing')
        ->where('created_at', '<', now()->subMinutes(5))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    echo json_encode(`$stuck);
    " 2>$null
    
    $stuck = $stuckJson | ConvertFrom-Json
    
    foreach ($webhook in $stuck) {
        Write-Host "ID: $($webhook.id) | Event: $($webhook.event_type) | Stuck since: $($webhook.created_at)" -ForegroundColor Yellow
    }
    
    Write-Host ""
}

# Event type distribution
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Event Type Distribution:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$distributionJson = php artisan tinker --execute="
`$distribution = DB::table('billing_webhook_events')
    ->select('event_type', DB::raw('COUNT(*) as count'))
    ->where('created_at', '>=', now()->subHours($Hours))
    ->groupBy('event_type')
    ->orderBy('count', 'desc')
    ->get();
echo json_encode(`$distribution);
" 2>$null

$distribution = $distributionJson | ConvertFrom-Json

foreach ($row in $distribution) {
    $eventType = $row.event_type.PadRight(25)
    Write-Host "$eventType : $($row.count)" -ForegroundColor White
}

Write-Host ""

# Recommendations
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Recommendations:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

if ($failed -gt 0) {
    Write-Host "- Review failed webhooks in admin panel" -ForegroundColor Yellow
    Write-Host "- Check Laravel logs: Get-Content storage\logs\laravel.log -Tail 100" -ForegroundColor Yellow
    Write-Host "- Verify database connectivity" -ForegroundColor Yellow
}

if ($stuckCount -gt 0) {
    Write-Host "- Restart queue workers: php artisan queue:restart" -ForegroundColor Yellow
    Write-Host "- Check queue:work processes running" -ForegroundColor Yellow
    Write-Host "- Review timeout settings" -ForegroundColor Yellow
}

if ($total -eq 0) {
    Write-Host "- No webhooks received in last $Hours hours" -ForegroundColor Yellow
    Write-Host "- Verify webhook registered with billing platform" -ForegroundColor Yellow
    Write-Host "- Check firewall/IP whitelist settings" -ForegroundColor Yellow
    Write-Host "- Test webhook endpoint manually" -ForegroundColor Yellow
}

if ($status -eq "HEALTHY" -and $total -gt 0) {
    Write-Host "[OK] System operating normally" -ForegroundColor Green
}

Write-Host ""

# Export report to file
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$reportFile = "storage\logs\webhook_health_$timestamp.txt"

@"
Webhook Health Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Period: Last $Hours hours

Statistics:
- Total Webhooks: $total
- Successful: $success ($successRate%)
- Failed: $failed
- Processing: $processing
- Stuck: $stuckCount
- Status: $status

"@ | Out-File -FilePath $reportFile

Write-Host "Report saved: $reportFile" -ForegroundColor Gray
Write-Host ""
