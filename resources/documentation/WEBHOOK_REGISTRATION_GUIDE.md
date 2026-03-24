# Webhook Registration Guide
## Configuring SafariChat Webhook with Billing Platform

**Platform:** https://api.safaribank.africa  
**Date:** March 24, 2026  
**Estimated Time:** 15-20 minutes

---

## Prerequisites

Before starting this guide, ensure you have:

- [ ] Admin access to billing platform dashboard
- [ ] Production server with SafariChat deployed
- [ ] HTTPS certificate configured and valid
- [ ] IP whitelist configured in ValidateBillingWebhookIP.php
- [ ] Database migrations applied

---

## Step 1: Access Billing Platform Dashboard

1. Navigate to: **https://api.safaribank.africa/dashboard**
2. Login with your admin credentials
3. You should see the main dashboard

**Expected:** Dashboard loads with menu options visible

---

## Step 2: Navigate to Webhooks Settings

1. Look for **Settings** in the main menu (usually top-right or sidebar)
2. Click on **Settings** → **Webhooks** or **Integrations** → **Webhooks**
3. You should see a list of existing webhooks (may be empty)

**Expected:** Webhooks management page with "Add Webhook" or "Create Webhook" button

**Screenshot Location:** `resources/documentation/screenshots/billing_platform_webhooks.png`

---

## Step 3: Create New Webhook Endpoint

1. Click **"Add Webhook Endpoint"** or **"Create Webhook"** button
2. A form should appear with the following fields

---

## Step 4: Configure Webhook Details

Fill in the webhook configuration form:

### 4.1 Webhook URL

**Field:** Endpoint URL / Webhook URL  
**Value:** `https://safarichat.com/api/billing/webhook`

⚠️ **Important:**
- Use HTTPS (not HTTP)
- Do NOT include trailing slash
- Use production domain (not staging)
- Ensure URL is publicly accessible

### 4.2 Select Events

Select ALL of the following events:

- ✅ **payment.success** - Payment completed successfully
- ✅ **payment.failed** - Payment failed or declined
- ✅ **subscription.created** - New subscription created
- ✅ **subscription.renewed** - Subscription renewed/extended
- ✅ **subscription.cancelled** - User cancelled subscription
- ✅ **subscription.expired** - Subscription expired (no payment)
- ✅ **credits.purchased** - Standalone credit purchase

**Why all events?** SafariChat needs to be notified of all billing state changes to keep accounts synchronized.

### 4.3 Webhook Secret (Auto-generated)

**Field:** Secret Key / Signing Secret  
**Action:** 
- Platform should auto-generate a secret
- **COPY THIS VALUE IMMEDIATELY** - you'll need it for .env configuration
- Store securely (password manager recommended)

**Example format:** `whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

⚠️ **Critical:** This secret cannot be retrieved later. If lost, you'll need to regenerate it and update production .env

### 4.4 Additional Settings (if available)

- **Webhook Version:** v1 or latest (if option exists)
- **Retry Policy:** Enable automatic retries (recommended: 3-5 retries)
- **Timeout:** 30 seconds (default)
- **IP Whitelist:** Add your server IP (if available)

---

## Step 5: Save and Verify Configuration

1. Review all settings carefully
2. Click **"Save"** or **"Create Webhook"**
3. You should see a success message
4. Webhook should appear in the list with status "Active" or "Enabled"

**Expected Success Message:** "Webhook endpoint created successfully"

---

## Step 6: Copy Webhook Secret

1. Locate the webhook in the list
2. Find the **"Secret"** or **"Signing Key"** section
3. Click **"Reveal"** or **"Show"** to display the secret
4. Copy the full secret value

**Format check:** Secret should be 32-64 characters, alphanumeric + special chars

---

## Step 7: Update Production Environment

1. SSH into production server:
   ```bash
   ssh user@safarichat.com
   cd /var/www/safarichat
   ```

2. Edit `.env` file:
   ```bash
   nano .env
   ```

3. Add/update the webhook secret:
   ```env
   BILLING_WEBHOOK_SECRET=whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```

4. Save and exit (Ctrl+X, Y, Enter)

5. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

6. Verify configuration loaded:
   ```bash
   php artisan tinker
   >>> config('services.billing.webhook_secret')
   => "whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
   >>> exit
   ```

---

## Step 8: Test Webhook Delivery

### Option A: Using Billing Platform Test Feature

1. In billing platform webhook settings, look for **"Send Test Webhook"** button
2. Select event type: **payment.success**
3. Click **"Send Test"**
4. Check response:
   - ✅ **200 OK** - Webhook received and processed successfully
   - ❌ **401 Unauthorized** - Secret mismatch (check .env)
   - ❌ **403 Forbidden** - IP not whitelisted
   - ❌ **404 Not Found** - Incorrect URL
   - ❌ **500 Internal Server Error** - Application error (check logs)

### Option B: Using curl

```bash
# Generate test payload
cat > test_webhook.json <<EOF
{
  "event": "payment.success",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "customer_id": 1,
  "payment": {
    "transaction_id": "TEST_$(date +%s)",
    "amount": 50.00,
    "currency": "USD",
    "status": "completed",
    "payment_method": "card"
  },
  "subscription": {
    "plan": "premium",
    "duration_days": 30,
    "ai_credits": 10000
  }
}
EOF

# Generate signature (replace SECRET with your actual secret)
SECRET="whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
PAYLOAD=$(cat test_webhook.json)
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Send webhook
curl -X POST https://safarichat.com/api/billing/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $SIGNATURE" \
  -d "$PAYLOAD" \
  -v

# Expected: HTTP 200 OK with response body
```

---

## Step 9: Verify in Admin Panel

1. Open browser: https://safarichat.com/admin/billing/webhooks
2. Login as admin
3. Look for the test webhook in the list
4. Verify:
   - Event type: `payment.success` or `test.webhook`
   - Status: `success`
   - Timestamp: within last few minutes
   - No error messages

**Expected:** Test webhook appears with status "success" and recent timestamp

---

## Step 10: Monitor First Real Webhook

1. Create a small test payment (5000 TZS recommended)
2. Complete payment process
3. Wait 30-60 seconds for webhook delivery
4. Check admin panel for new webhook event
5. Verify account credits updated

**Success Criteria:**
- ✅ Webhook received within 60 seconds
- ✅ Status: success
- ✅ Credits added to account
- ✅ No duplicate entries
- ✅ No errors in Laravel logs

---

## Troubleshooting

### Issue 1: Webhook Returns 401 Unauthorized

**Cause:** Signature validation failed

**Solutions:**
1. Verify webhook secret in .env matches billing platform
2. Check for extra spaces or newlines in secret
3. Clear config cache: `php artisan config:clear && php artisan config:cache`
4. Verify secret loaded: `php artisan tinker >>> config('services.billing.webhook_secret')`

### Issue 2: Webhook Returns 403 Forbidden

**Cause:** Source IP not in whitelist

**Solutions:**
1. Check Laravel logs for rejected IP: `tail -f storage/logs/laravel.log | grep "Webhook IP rejected"`
2. Contact billing platform support for their IP ranges
3. Update `ValidateBillingWebhookIP.php` with correct IPs
4. Temporarily add rejecting IP to ALLOWED_IPS for testing
5. Deploy updated middleware

### Issue 3: Webhook Returns 400 Bad Request

**Cause:** Payload validation failed

**Solutions:**
1. Check Laravel logs for validation errors
2. Review BillingWebhookRequest validation rules
3. Verify event type is one of 7 supported types
4. Ensure required fields present (event, timestamp, customer_id OR business_id)
5. For payment events, ensure payment object included

### Issue 4: Webhook Returns 500 Internal Server Error

**Cause:** Application error during processing

**Solutions:**
1. Check Laravel logs: `tail -n 100 storage/logs/laravel.log`
2. Look for stack traces
3. Common issues:
   - Database connection failed
   - Missing database columns (run migrations)
   - Queue worker not running
   - Filesystem permissions
4. Enable debug mode temporarily (staging only): `APP_DEBUG=true`

### Issue 5: Webhook Not Received at All

**Cause:** Network/connectivity issue

**Solutions:**
1. Verify webhook URL publicly accessible: `curl -I https://safarichat.com/api/billing/webhook`
2. Check firewall rules allow inbound HTTPS from billing platform
3. Verify SSL certificate valid and not expired
4. Test with external tool: https://webhook.site
5. Check DNS resolves correctly: `nslookup safarichat.com`

### Issue 6: Duplicate Webhook Processing

**Cause:** Idempotency check not working

**Solutions:**
1. Verify migration applied: `SELECT * FROM billing_webhook_events LIMIT 1`
2. Check `transaction_id` unique constraint exists
3. Review webhook event logs for duplicate transaction IDs
4. Ensure BillingWebhookEvent::isProcessed() called before processing

---

## Getting IP Addresses from Billing Platform

If webhooks are being rejected with 403 Forbidden, you need the platform's IP addresses.

### Email Template

```
To: support@safaribank.africa
Subject: Request: Webhook Server IP Addresses

Dear Support Team,

We are configuring our production webhook endpoint for SafariChat billing integration.

Webhook URL: https://safarichat.com/api/billing/webhook

Our security policy requires IP whitelisting for incoming webhook requests. 
Could you please provide the IP address ranges or CIDR blocks from which your 
platform sends webhook notifications?

Current webhook endpoint: Active
Current status: IP whitelist pending

We need this information to complete our production deployment.

Thank you for your assistance.

Best regards,
SafariChat Technical Team
```

### Expected Response Format

They should provide IPs in one of these formats:

**Format 1: Single IPs**
```
41.59.123.45
197.156.234.56
```

**Format 2: CIDR Notation**
```
41.59.0.0/16
197.156.0.0/16
```

**Format 3: IP Ranges**
```
41.59.0.1 - 41.59.255.254
```

### Update Middleware

Once received, update `app/Http/Middleware/ValidateBillingWebhookIP.php`:

```php
private const ALLOWED_IPS = [
    '41.59.0.0/16',      // Billing Platform Primary
    '197.156.0.0/16',    // Billing Platform Backup
    '154.118.0.0/16',    // Billing Platform Failover (if provided)
];
```

Then deploy:
```bash
git add app/Http/Middleware/ValidateBillingWebhookIP.php
git commit -m "Update webhook IP whitelist with production IPs"
git push origin main
# SSH to production and pull changes
```

---

## Verification Checklist

After completing all steps, verify:

- [ ] Webhook registered in billing platform dashboard
- [ ] Webhook status: Active/Enabled
- [ ] Webhook secret saved securely
- [ ] Production .env has BILLING_WEBHOOK_SECRET configured
- [ ] Config cache cleared and rebuilt
- [ ] Test webhook sent successfully (200 OK)
- [ ] Test webhook appears in admin panel
- [ ] Real payment test completed successfully
- [ ] Credits added correctly without duplicates
- [ ] No errors in Laravel logs
- [ ] Monitoring enabled (Slack alerts if configured)

---

## Next Steps

1. **Monitor for 24 hours** - See PRODUCTION_CONFIGURATION_CHECKLIST.md
2. **Document any issues** - Create incident reports for failures
3. **Train support team** - Show them admin webhook panel
4. **Set up alerting** - Configure Slack notifications for failures
5. **Review regularly** - Weekly check of webhook success rate

---

## Reference Links

- **Billing Platform Docs:** https://api.safaribank.africa/api-docs
- **Webhook Admin Panel:** https://safarichat.com/admin/billing/webhooks
- **Phase 2 Security Config:** See PHASE_2_SECURITY_CONFIGURATION.md
- **Implementation Plan:** See BILLING_WEBHOOK_IMPLEMENTATION_PLAN.md

---

## Support Contacts

- **Billing Platform Support:** support@safaribank.africa
- **SafariChat DevOps:** [Add your contact]
- **On-call Developer:** [Add your contact]
