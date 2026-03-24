# Billing Webhook Local Testing Script
# Usage: .\test_webhook_locally.ps1

param(
    [string]$WebhookUrl = "http://localhost:8000/api/billing/webhook",
    [string]$WebhookSecret = "test_secret_key",
    [int]$UserId = 1,
    [string]$EventType = "payment.success"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Billing Webhook Local Testing Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Valid Payment Success Webhook
Write-Host "[Test 1] Testing valid payment.success webhook..." -ForegroundColor Yellow

$timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
$transactionId = "TEST_$(Get-Date -Format 'yyyyMMddHHmmss')"

$payload = @{
    event = "payment.success"
    timestamp = $timestamp
    customer_id = $UserId
    subscription = @{
        plan_id = "premium_monthly"
        plan = "premium"
        duration_days = 30
        ai_credits = 10000
        features = @{
            max_contacts = 500
            max_products = 50
            whatsapp_channels = 3
            customer_followups = $true
            customer_categorization = $true
            booking_calendars = $true
            sales_reports = $true
        }
    }
    payment = @{
        transaction_id = $transactionId
        amount = 49.99
        currency = "USD"
        status = "completed"
        method = "stripe"
    }
} | ConvertTo-Json -Depth 10

# Generate HMAC signature
$hmacsha = New-Object System.Security.Cryptography.HMACSHA256
$hmacsha.Key = [Text.Encoding]::UTF8.GetBytes($WebhookSecret)
$signature = [Convert]::ToHexString($hmacsha.ComputeHash([Text.Encoding]::UTF8.GetBytes($payload)))

try {
    $response = Invoke-WebRequest -Uri $WebhookUrl `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
            "X-Webhook-Signature" = $signature.ToLower()
        } `
        -Body $payload `
        -UseBasicParsing
    
    Write-Host "✓ Test 1 PASSED: Status $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  Response: $($response.Content)" -ForegroundColor Gray
} catch {
    Write-Host "✗ Test 1 FAILED: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "  Response: $responseBody" -ForegroundColor Gray
    }
}

Write-Host ""

# Test 2: Duplicate Webhook (Idempotency Check)
Write-Host "[Test 2] Testing duplicate webhook (same transaction_id)..." -ForegroundColor Yellow

Start-Sleep -Seconds 1

try {
    $response2 = Invoke-WebRequest -Uri $WebhookUrl `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
            "X-Webhook-Signature" = $signature.ToLower()
        } `
        -Body $payload `
        -UseBasicParsing
    
    $responseObj = $response2.Content | ConvertFrom-Json
    
    if ($responseObj.message -like "*already processed*" -or $responseObj.message -like "*idempotency*") {
        Write-Host "✓ Test 2 PASSED: Duplicate detected correctly (Status $($response2.StatusCode))" -ForegroundColor Green
        Write-Host "  Response: $($response2.Content)" -ForegroundColor Gray
    } else {
        Write-Host "✗ Test 2 FAILED: Duplicate not detected (processed again)" -ForegroundColor Red
        Write-Host "  Response: $($response2.Content)" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ Test 2 FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Invalid Signature
Write-Host "[Test 3] Testing invalid signature rejection..." -ForegroundColor Yellow

try {
    $response3 = Invoke-WebRequest -Uri $WebhookUrl `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
            "X-Webhook-Signature" = "invalid_signature_12345"
        } `
        -Body $payload `
        -UseBasicParsing -ErrorAction Stop
    
    Write-Host "✗ Test 3 FAILED: Invalid signature accepted (should be rejected)" -ForegroundColor Red
    Write-Host "  Status: $($response3.StatusCode)" -ForegroundColor Gray
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "✓ Test 3 PASSED: Invalid signature rejected (401 Unauthorized)" -ForegroundColor Green
    } else {
        Write-Host "✗ Test 3 FAILED: Wrong status code $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}

Write-Host ""

# Test 4: Credits Purchase (No Subscription Change)
Write-Host "[Test 4] Testing credits.purchased event..." -ForegroundColor Yellow

$creditsPayload = @{
    event = "credits.purchased"
    timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    customer_id = $UserId
    credits = @{
        amount = 5000
        price = 19.99
    }
    payment = @{
        transaction_id = "CREDITS_$(Get-Date -Format 'yyyyMMddHHmmss')"
        amount = 19.99
        currency = "USD"
        status = "completed"
    }
} | ConvertTo-Json -Depth 10

$hmacsha.Key = [Text.Encoding]::UTF8.GetBytes($WebhookSecret)
$creditsSignature = [Convert]::ToHexString($hmacsha.ComputeHash([Text.Encoding]::UTF8.GetBytes($creditsPayload)))

Start-Sleep -Seconds 1

try {
    $response4 = Invoke-WebRequest -Uri $WebhookUrl `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
            "X-Webhook-Signature" = $creditsSignature.ToLower()
        } `
        -Body $creditsPayload `
        -UseBasicParsing
    
    Write-Host "✓ Test 4 PASSED: Credits purchase processed (Status $($response4.StatusCode))" -ForegroundColor Green
    Write-Host "  Response: $($response4.Content)" -ForegroundColor Gray
} catch {
    Write-Host "✗ Test 4 FAILED: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "  Response: $responseBody" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Check database table 'billing_webhook_events' for audit trail" -ForegroundColor White
Write-Host "2. Check database table 'billing_accounts' for updated subscription" -ForegroundColor White
Write-Host "3. Verify that duplicate webhook did NOT add credits twice" -ForegroundColor White
Write-Host ""
Write-Host "Verification Query:" -ForegroundColor Yellow
Write-Host "  SELECT * FROM billing_webhook_events WHERE transaction_id LIKE 'TEST_%' ORDER BY created_at DESC;" -ForegroundColor Cyan
Write-Host ""
