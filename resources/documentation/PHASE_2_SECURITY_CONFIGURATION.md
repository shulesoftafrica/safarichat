# Phase 2 Security Configuration Guide

## Environment Variables Required

Add these variables to your `.env` file:

### Production Environment (.env)
```env
# Webhook Signature Validation (REQUIRED FOR PRODUCTION)
BILLING_WEBHOOK_SECRET=your_production_secret_from_billing_platform

# This will be provided by the billing platform when you register your webhook URL
# Keep this secret secure - do not commit to version control
```

### Local/Testing Environment (.env.local or .env)
```env
# Webhook Test Secret for Local Development
BILLING_WEBHOOK_TEST_SECRET=test_secret_key

# Use this secret when testing webhooks locally
# The test script uses this secret to generate signatures
```

## IP Whitelist Configuration

### Step 1: Get Billing Platform IP Ranges

Contact billing platform support to get their server IP addresses/ranges. You'll need these IPs:
- Production webhook servers
- Backup/failover servers
- Testing/staging servers (if different)

### Step 2: Update IP Whitelist

Edit `app/Http/Middleware/ValidateBillingWebhookIP.php`:

```php
private const ALLOWED_IPS = [
    // Replace these examples with actual IPs from billing platform
    '41.59.123.45',           // Single IP
    '197.156.0.0/16',         // IP range (CIDR notation)
    '102.15.0.0/16',          // Another range
];
```

**CIDR Notation Examples:**
- `41.59.123.45` - Single IP address
- `41.59.0.0/16` - Range: 41.59.0.0 to 41.59.255.255 (65,536 IPs)
- `192.168.1.0/24` - Range: 192.168.1.0 to 192.168.1.255 (256 IPs)

### Step 3: Testing IP Whitelist

Localhost (127.0.0.1) is automatically allowed in local/testing environments.

To test with real webhook calls from external servers:
1. Deploy to staging server
2. Register webhook URL with billing platform
3. Send test webhook from billing platform dashboard
4. Check logs: `storage/logs/laravel.log` for validation results

## Rate Limiting

Webhook endpoint is configured with:
- **60 requests per minute** per IP address
- Prevents webhook flooding attacks
- Legitimate retries are handled by billing platform's exponential backoff

To adjust rate limit, edit `routes/api.php`:
```php
->middleware(['throttle:100,1', 'billing.webhook.ip'])  // 100 requests per minute
```

## Security Validation Flow

When a webhook arrives:

1. **IP Validation** (ValidateBillingWebhookIP middleware)
   - Checks if request comes from allowed IP
   - Rejects: 403 Forbidden

2. **Rate Limiting** (Laravel throttle middleware)
   - Checks requests per minute limit
   - Rejects: 429 Too Many Requests

3. **Payload Validation** (BillingWebhookRequest)
   - Validates JSON structure and data types
   - Rejects: 400 Bad Request

4. **Signature Validation** (Controller)
   - Verifies HMAC SHA256 signature
   - Rejects: 401 Unauthorized

5. **Idempotency Check** (Controller)
   - Prevents duplicate processing
   - Returns: 200 OK (already processed)

6. **Process Event** (Controller)
   - Updates subscription/credits
   - Returns: 200 OK

## Testing Phase 2 Security

### Test 1: Valid Webhook (Should Pass)
```powershell
.\tests\manual\test_webhook_locally.ps1
```

### Test 2: Invalid Signature (Should Fail with 401)
```powershell
.\tests\manual\test_webhook_locally.ps1 -WebhookSecret "wrong_secret"
```

### Test 3: Invalid Payload (Should Fail with 400)
Send webhook with missing required fields:
```json
{
  "event": "payment.success"
  // Missing: timestamp, customer_id, payment data
}
```

### Test 4: Rate Limiting (Should Fail with 429)
Send 61 webhooks within 1 minute - 61st should be rejected.

## Production Deployment Checklist

### Before Going Live:

- [ ] Set `BILLING_WEBHOOK_SECRET` in production `.env`
- [ ] Update IP whitelist with actual billing platform IPs
- [ ] Remove any test IPs from whitelist
- [ ] Test webhook delivery from billing platform staging
- [ ] Verify signature validation works with production secret
- [ ] Monitor logs for unauthorized access attempts
- [ ] Set up alerts for failed webhooks

### After Going Live:

- [ ] Monitor first 10 webhooks closely
- [ ] Check `billing_webhook_events` table for success rate
- [ ] Verify no duplicate processing (check credits)
- [ ] Review IP validation logs for any issues

## Troubleshooting

### Problem: Webhooks rejected with 403 Forbidden

**Cause:** IP not whitelisted

**Solution:**
1. Check webhook source IP in logs: `storage/logs/laravel.log`
2. Add IP to whitelist in `ValidateBillingWebhookIP.php`
3. Clear config cache: `php artisan config:clear`

### Problem: Webhooks rejected with 401 Unauthorized

**Cause:** Signature validation failed

**Solution:**
1. Verify `BILLING_WEBHOOK_SECRET` matches billing platform
2. Check if using test secret in production (wrong environment)
3. Ensure webhook payload not modified in transit
4. Check for proxy/CDN issues modifying request

### Problem: Webhooks rejected with 400 Bad Request

**Cause:** Payload validation failed

**Solution:**
1. Check error details in response JSON
2. Verify billing platform sends all required fields
3. Check data types match expected format
4. Review `BillingWebhookRequest` validation rules

### Problem: Getting 429 Too Many Requests

**Cause:** Rate limit exceeded

**Solution:**
1. Check if billing platform retrying too aggressively
2. Increase rate limit in `routes/api.php` if legitimate traffic
3. Check for webhook flooding attack in logs

## Security Best Practices

1. **Never log full webhook secrets** - Only log first 10 characters
2. **Rotate secrets periodically** - Update every 6-12 months
3. **Monitor failed attempts** - Set up alerts for repeated 401/403 errors
4. **Use HTTPS only** - Never allow HTTP webhooks in production
5. **Validate timestamps** - Reject webhooks older than 5 minutes (future enhancement)
6. **Keep IP whitelist tight** - Only add necessary IPs
7. **Review logs weekly** - Check for suspicious patterns

## Phase 2 Implementation Complete! ✅

Your webhook system now has:
- ✅ IP whitelisting (only authorized servers can send webhooks)
- ✅ Rate limiting (60 requests/minute per IP)
- ✅ Enhanced payload validation (strict data type checking)
- ✅ Separate secrets for dev/prod (no more development bypass)
- ✅ Comprehensive logging (track all validation failures)

Next: **Phase 3** - Automated Testing (unit tests + integration tests)
