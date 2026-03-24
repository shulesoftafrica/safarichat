# Production Configuration Checklist - Phase 5

**Last Updated:** March 24, 2026  
**Status:** Pre-deployment configuration required  

---

## ✅ Pre-Deployment Checklist

### 1. Environment Variables (.env)

**Location:** `/var/www/safarichat/.env` (production server)

```env
# === REQUIRED FOR WEBHOOKS ===
BILLING_WEBHOOK_SECRET=                    # ⚠️ GET FROM BILLING PLATFORM
BILLING_WEBHOOK_TEST_SECRET=test_secret_key

# === OPTIONAL BUT RECOMMENDED ===
SLACK_BILLING_WEBHOOK_URL=                 # Slack webhook URL for alerts

# === VERIFY THESE EXIST ===
APP_ENV=production
APP_DEBUG=false
APP_URL=https://safarichat.com
DB_CONNECTION=pgsql
QUEUE_CONNECTION=redis
```

**Action Required:**
- [ ] Get `BILLING_WEBHOOK_SECRET` from billing platform dashboard
- [ ] Set up Slack webhook for alerts (optional)
- [ ] Verify `APP_ENV=production` and `APP_DEBUG=false`

---

### 2. IP Whitelist Configuration

**File:** `app/Http/Middleware/ValidateBillingWebhookIP.php` (line 15)

**Current State (PLACEHOLDER):**
```php
private const ALLOWED_IPS = [
    '41.59.0.0/16',      // ⚠️ PLACEHOLDER
    '197.156.0.0/16',    // ⚠️ PLACEHOLDER
    '154.118.0.0/16',    // ⚠️ PLACEHOLDER
];
```

**Action Required:**
- [ ] Contact billing platform support: support@safaribank.africa
- [ ] Request: "IP address ranges for webhook delivery"
- [ ] Update ALLOWED_IPS with real IP ranges
- [ ] Commit and push changes

**Email Template:**
```
To: support@safaribank.africa
Subject: Webhook Server IP Addresses Request

Hello,

We are configuring our webhook endpoint for SafariChat billing integration:
Webhook URL: https://safarichat.com/api/billing/webhook

Please provide the IP address ranges from which your platform sends webhook 
notifications so we can whitelist them in our firewall.

Thank you,
SafariChat Technical Team
```

---

### 3. Database Migration Status

**Action Required:**
- [ ] Verify migration applied on staging: `php artisan migrate:status`
- [ ] Backup production database before migration
- [ ] Run migration on production: `php artisan migrate --force`
- [ ] Verify new tables exist:
  - `billing_webhook_events` table created
  - `billing_accounts` has new columns: `subscription_status`, `last_transaction_id`, `last_payment_at`, `last_payment_amount`

**Verification Command:**
```bash
php artisan tinker
>>> Schema::hasTable('billing_webhook_events')
>>> Schema::hasColumns('billing_accounts', ['subscription_status', 'last_transaction_id'])
```

---

### 4. Webhook Registration with Billing Platform

**Dashboard:** https://api.safaribank.africa/dashboard

**Steps:**
1. [ ] Login to billing platform dashboard
2. [ ] Navigate to: **Settings → Webhooks**
3. [ ] Click: **Add Webhook Endpoint**
4. [ ] Configure:
   - **URL:** `https://safarichat.com/api/billing/webhook`
   - **Events to receive:**
     - ✅ `payment.success`
     - ✅ `payment.failed`
     - ✅ `subscription.created`
     - ✅ `subscription.renewed`
     - ✅ `subscription.cancelled`
     - ✅ `subscription.expired`
     - ✅ `credits.purchased`
   - **Webhook Secret:** Copy this value!
5. [ ] Save webhook configuration
6. [ ] Copy secret to production `.env` as `BILLING_WEBHOOK_SECRET`

**Screenshot:** Save screenshot of webhook configuration for documentation

---

### 5. SSL/HTTPS Verification

**Action Required:**
- [ ] Verify SSL certificate is valid: `curl -I https://safarichat.com`
- [ ] Check certificate expiration: `openssl s_client -connect safarichat.com:443 -servername safarichat.com | openssl x509 -noout -dates`
- [ ] Ensure webhook endpoint accessible via HTTPS only
- [ ] Verify HTTP redirects to HTTPS

**Expected Results:**
```
HTTP/2 200 OK
SSL certificate valid
Not expired
```

---

### 6. Server Configuration

**Nginx Configuration:**
- [ ] Verify `/api/billing/webhook` route exists
- [ ] Check request body size limit: `client_max_body_size 2M;`
- [ ] Verify timeout settings: `proxy_read_timeout 60s;`

**PHP Configuration:**
- [ ] Check `max_execution_time` >= 30 seconds
- [ ] Check `memory_limit` >= 256M
- [ ] Verify `post_max_size` >= 2M

**Queue Workers:**
- [ ] Verify Laravel Horizon/queue workers running
- [ ] Check supervisor configuration for queue:work

---

### 7. Firewall Rules

**Action Required:**
- [ ] Allow inbound HTTPS (443) from billing platform IPs
- [ ] Verify webhook endpoint accessible from external network
- [ ] Test with curl from external server

**Test Command (from external server):**
```bash
curl -X POST https://safarichat.com/api/billing/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "connectivity"}'

# Expected: 401 Unauthorized (signature missing) or 400 Bad Request
# Bad: Connection timeout, certificate error
```

---

### 8. Permissions & Ownership

**Action Required:**
- [ ] Verify file ownership: `chown -R www-data:www-data /var/www/safarichat`
- [ ] Check storage permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Verify `.env` permissions: `chmod 600 .env`

---

### 9. Logging Configuration

**Action Required:**
- [ ] Verify log rotation configured: `/etc/logrotate.d/laravel`
- [ ] Check disk space: `df -h`
- [ ] Test logging: `php artisan tinker >>> Log::info('Test log entry')`
- [ ] Verify logs writable: `storage/logs/laravel.log`

---

### 10. Monitoring Setup

**Action Required:**
- [ ] Set up Slack notification channel (if using)
- [ ] Configure alert thresholds
- [ ] Test Slack notifications (if configured)
- [ ] Bookmark admin dashboard: `https://safarichat.com/admin/billing/webhooks`

---

## 🧪 Testing Checklist

### Staging Tests (Before Production)

- [ ] Deploy to staging server
- [ ] Run automated tests: `php artisan test --filter=BillingWebhook`
- [ ] Send test webhook from billing platform test mode
- [ ] Verify webhook logged in admin panel
- [ ] Check credits added correctly
- [ ] Verify no duplicate processing

### Production Tests (After Deployment)

- [ ] Health check passes: `curl https://safarichat.com/health`
- [ ] Webhook endpoint responds: `curl -X POST https://safarichat.com/api/billing/webhook`
- [ ] Create small test payment (5000 TZS)
- [ ] Verify webhook received within 60 seconds
- [ ] Check admin panel for webhook event
- [ ] Verify credits added to test account
- [ ] Confirm no duplicate webhook entries

---

## 📊 Monitoring Checklist (First 24 Hours)

### Hour 1
- [ ] Check Slack for alerts (should be 0)
- [ ] Review Laravel logs: `tail -f storage/logs/laravel.log | grep "Billing webhook"`
- [ ] Verify webhook endpoint accessible

### Hour 4
- [ ] Review admin dashboard: https://safarichat.com/admin/billing/webhooks
- [ ] Check webhook success rate (target: 100%)
- [ ] Verify at least 3-5 webhooks processed

### Hour 8
- [ ] Query database for failed webhooks:
  ```sql
  SELECT * FROM billing_webhook_events 
  WHERE processing_status = 'failed' 
  AND created_at >= NOW() - INTERVAL '8 hours';
  ```
- [ ] Investigate any failures
- [ ] Check for duplicate transaction IDs

### Hour 12
- [ ] Review payment vs webhook count (should match)
- [ ] Verify no customer support tickets about missing credits
- [ ] Check system resource usage

### Hour 24
- [ ] Generate summary report:
  ```sql
  SELECT 
    processing_status, 
    COUNT(*) as count,
    COUNT(*) * 100.0 / SUM(COUNT(*)) OVER() as percentage
  FROM billing_webhook_events 
  WHERE created_at >= NOW() - INTERVAL '24 hours'
  GROUP BY processing_status;
  ```
- [ ] Target: >99% success rate
- [ ] Document any issues encountered
- [ ] Create incident report if needed

---

## 🚨 Rollback Procedure

**If critical issues occur:**

### 1. Immediate Actions
- [ ] Disable webhook in billing platform dashboard
- [ ] Enable maintenance mode: `php artisan down`

### 2. Revert Code
```bash
cd /var/www/safarichat
git log --oneline -10  # Find previous commit hash
git revert HEAD  # Or: git reset --hard <commit-hash>
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 3. Restore Database (if needed)
```bash
# Restore from backup
php artisan db:restore --filename="production_YYYYMMDD_HHMMSS.sql"
```

### 4. Re-enable Application
```bash
php artisan up
```

### 5. Manual Payment Processing
- [ ] Export pending payments from billing platform
- [ ] Manually add credits via admin panel
- [ ] Notify affected customers

---

## 📝 Sign-off

**Completed By:** ___________________  
**Date:** ___________________  
**Verified By:** ___________________  
**Production Deploy Time:** ___________________  

**Notes:**
_________________________________________________________________________
_________________________________________________________________________
_________________________________________________________________________

---

## 📞 Emergency Contacts

- **Billing Platform Support:** support@safaribank.africa
- **System Administrator:** ___________________
- **Developer on-call:** ___________________
- **Rollback Decision Maker:** ___________________
