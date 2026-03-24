# Phase 5: Production Deployment - Quick Start Guide

**Status:** Ready for deployment  
**Prerequisites:** Phase 1 & 2 completed ✓  
**Estimated Time:** 3-4 hours (minimal path) or 11.5 hours (full path)  
**Last Updated:** March 24, 2026

---

## 🎯 Overview

Phase 5 deploys the billing webhook system to production. You have **two deployment paths**:

### Path A: Minimal Deployment (3-4 hours)
Fast track to production with essential steps only. **Choose this if:**
- You need webhooks working ASAP
- You can monitor manually for issues
- You trust the automated tests that already passed

### Path B: Full Deployment (11.5 hours)
Complete deployment with staging, thorough testing, and monitoring setup. **Choose this if:**
- This is critical payment infrastructure
- You want peace of mind
- You have time for proper verification

---

## 📋 Phase Status Check

Before starting, verify Phase 1 & 2 are complete:

```powershell
# Run status check
php artisan tinker --execute="
echo 'Database Check:\n';
echo Schema::hasTable('billing_webhook_events') ? '✓ billing_webhook_events table exists\n' : '✗ Missing billing_webhook_events table\n';
echo Schema::hasColumn('billing_accounts', 'subscription_status') ? '✓ subscription_status column exists\n' : '✗ Missing subscription_status column\n';
echo Schema::hasColumn('billing_accounts', 'last_transaction_id') ? '✓ last_transaction_id column exists\n' : '✗ Missing last_transaction_id column\n';
echo '\nMiddleware Check:\n';
echo file_exists('app/Http/Middleware/ValidateBillingWebhookIP.php') ? '✓ IP whitelist middleware exists\n' : '✗ Missing IP middleware\n';
echo file_exists('app/Http/Requests/BillingWebhookRequest.php') ? '✓ Form request validator exists\n' : '✗ Missing form request\n';
"
```

**Expected output:**
```
✓ billing_webhook_events table exists
✓ subscription_status column exists
✓ last_transaction_id column exists
✓ IP whitelist middleware exists
✓ Form request validator exists
```

If any ✗ appear, **DO NOT proceed**. Go back and complete Phase 1 & 2 first.

---

## 🚀 Path A: Minimal Deployment (3-4 hours)

### Step 1: Get Production IP Addresses (30 min)

**Action:** Email billing platform support

**Template:**
```
To: support@safaribank.africa
Subject: Webhook Server IP Addresses - SafariChat

Hello,

We're deploying our production webhook endpoint:
URL: https://safarichat.com/api/billing/webhook

Please provide the IP address ranges from which your platform 
sends webhook notifications for our IP whitelist configuration.

Thank you,
SafariChat Technical Team
```

**Wait for response**, then update:
```php
// File: app/Http/Middleware/ValidateBillingWebhookIP.php (line 15)
private const ALLOWED_IPS = [
    '41.59.0.0/16',      // Replace with real IPs from support
    '197.156.0.0/16',    // Add all IPs they provide
];
```

**Commit and push:**
```powershell
git add app/Http/Middleware/ValidateBillingWebhookIP.php
git commit -m "Update webhook IP whitelist with production IPs"
git push origin main
```

---

### Step 2: Register Webhook (30 min)

1. **Login:** https://api.safaribank.africa/dashboard
2. **Navigate:** Settings → Webhooks
3. **Click:** "Add Webhook Endpoint"
4. **Configure:**
   - URL: `https://safarichat.com/api/billing/webhook`
   - Events: Select ALL (payment.*, subscription.*, credits.*)
   - Save
5. **Copy Secret:** You'll see a webhook secret - **COPY IT NOW**

**Example:** `whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

---

### Step 3: Configure Production Server (30 min)

**SSH to production:**
```bash
ssh user@safarichat.com
cd /var/www/safarichat
```

**Update .env:**
```bash
nano .env

# Add this line (paste the secret you copied):
BILLING_WEBHOOK_SECRET=whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

# Save: Ctrl+X, Y, Enter
```

**Pull latest code:**
```bash
git pull origin main
```

**Clear caches:**
```bash
php artisan config:clear
php artisan config:cache
```

**Verify secret loaded:**
```bash
php artisan tinker --execute="echo config('services.billing.webhook_secret');"

# Should output: whsec_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

---

### Step 4: Test with Real Payment (1 hour)

**Create small test payment:**
1. Login to SafariChat as test user
2. Go to Billing → Wallet
3. Amount: **5,000 TZS** (small test amount)
4. Generate payment options
5. Complete payment via UCN

**Monitor webhook delivery:**
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep "Billing webhook"
```

**Verify success:**
Within 60 seconds, you should see:
```
[INFO] Billing webhook received: payment.success
[INFO] Idempotency check passed
[INFO] Credits added successfully
[INFO] Billing webhook processed successfully
```

**Check admin panel:** (if you have one)
- Navigate to webhook logs/admin panel
- Verify webhook event logged
- Status: success
- Credits added to test account

---

### Step 5: Monitor First 4 Hours (1 hour active monitoring)

**Hour 1 - Active monitoring:**
```bash
# Check webhook health
cd /var/www/safarichat
./scripts/monitor-webhooks.sh 1

# Watch for issues
tail -f storage/logs/laravel.log | grep -E "ERROR|CRITICAL|Billing webhook"
```

**Hour 2 - Check statistics:**
```bash
./scripts/monitor-webhooks.sh 2

# Should show >95% success rate
```

**Hour 4 - Verify stability:**
```bash
./scripts/monitor-webhooks.sh 4

# Check:
# - Success rate >99%
# - No stuck webhooks
# - No duplicate processing
```

**If issues found:** See [WEBHOOK_ROLLBACK_PROCEDURE.md](WEBHOOK_ROLLBACK_PROCEDURE.md)

---

### ✅ Path A Complete

**You now have:**
- ✓ Production IPs configured
- ✓ Webhook registered with billing platform
- ✓ Production secret configured
- ✓ Test payment successful
- ✓ 4 hours monitoring complete

**Next:** Monitor daily for first week, then weekly checks.

---

## 🛡️ Path B: Full Deployment (11.5 hours)

Complete deployment with staging environment and comprehensive testing.

### Step 1: Deploy to Staging (2 hours)

**If you have staging server:**
```bash
# On staging server
ssh user@staging.safarichat.com
cd /var/www/safarichat-staging

# Run staging deployment script
./scripts/deploy-staging.sh
```

**Verify staging:**
- Run automated tests
- Send test webhook from billing platform test mode
- Check logs for errors
- Verify all Phase 1 & 2 features work

**Staging soak test:** Leave staging running with test webhooks for 2-4 hours.

---

### Step 2: Get Production IP Addresses (1 hour)

Same as Path A Step 1 - email billing platform support.

**Difference:** Update staging first, test, then update production.

---

### Step 3: Register Webhook (1 hour)

Same as Path A Step 2, but:
- Register staging URL first: `https://staging.safarichat.com/api/billing/webhook`
- Test thoroughly on staging
- Then register production URL: `https://safarichat.com/api/billing/webhook`
- Use separate secrets for staging and production

---

### Step 4: Production Deployment (2 hours)

**Use automated deployment script:**
```bash
# On production server
ssh user@safarichat.com
cd /var/www/safarichat

# Run production deployment script
./scripts/deploy-production.sh
```

**Script handles:**
- Pre-deployment checks
- Database backup
- Code backup
- Maintenance mode
- Git pull
- Composer install
- Database migration
- Cache clearing/rebuilding
- Queue worker restart
- Health checks
- Post-deployment verification

**Manual verification after script:**
- Check health endpoint: 200 OK
- Check webhook endpoint: 401 Unauthorized (expected without signature)
- Review deployment logs

---

### Step 5: Test with Real Payment (1 hour)

Same as Path A Step 4:
- Small test payment (5000 TZS)
- Monitor logs
- Verify success
- Check admin panel

---

### Step 6: 24-Hour Monitoring (3 hours active, 21 hours passive)

**Hour 1 - Intensive:**
```bash
# Every 15 minutes, run:
./scripts/monitor-webhooks.sh 1

# Watch logs continuously
tail -f storage/logs/laravel.log | grep "Billing webhook"

# Check Slack for alerts (if configured)
```

**Hour 4:**
```bash
./scripts/monitor-webhooks.sh 4

# Verify:
# - At least 5 successful webhooks
# - Success rate >95%
# - No stuck webhooks
# - No duplicate entries
```

**Hour 8:**
```bash
./scripts/monitor-webhooks.sh 8

# Query database for anomalies
php artisan tinker --execute="
\$failed = DB::table('billing_webhook_events')
    ->where('processing_status', 'failed')
    ->where('created_at', '>=', now()->subHours(8))
    ->count();
echo \"Failed webhooks: \$failed\n\";

\$duplicates = DB::table('billing_webhook_events')
    ->select('transaction_id', DB::raw('COUNT(*) as count'))
    ->whereNotNull('transaction_id')
    ->groupBy('transaction_id')
    ->having('count', '>', 1)
    ->count();
echo \"Duplicate transaction IDs: \$duplicates\n\";
"
```

**Hour 12:**
- Check for customer support tickets about missing credits
- Verify payment count matches webhook count
- Review system resource usage (CPU, memory, disk)

**Hour 24 - Final Report:**
```bash
./scripts/monitor-webhooks.sh 24

# Generate summary report
php artisan tinker --execute="
\$stats = DB::table('billing_webhook_events')
    ->select(
        'processing_status',
        DB::raw('COUNT(*) as count'),
        DB::raw('ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage')
    )
    ->where('created_at', '>=', now()->subHours(24))
    ->groupBy('processing_status')
    ->get();

echo \"24-Hour Webhook Report:\n\";
foreach (\$stats as \$row) {
    echo \"{$row->processing_status}: {$row->count} ({$row->percentage}%)\n\";
}
"
```

**Success criteria:**
- Total webhooks: >10
- Success rate: >99%
- No duplicates: 0
- Stuck webhooks: 0
- Customer complaints: 0

---

### Step 7: Production Sign-off (30 min)

**Complete checklist:**
- [ ] Staging tests passed
- [ ] Production deployment successful
- [ ] Real payment test passed
- [ ] 24-hour monitoring complete
- [ ] Success rate >99%
- [ ] No critical issues
- [ ] Team trained on monitoring
- [ ] Rollback procedure tested (on staging)

**Sign-off documentation:**
See [PRODUCTION_CONFIGURATION_CHECKLIST.md](PRODUCTION_CONFIGURATION_CHECKLIST.md) - fill in the sign-off section.

---

### ✅ Path B Complete

**You now have:**
- ✓ Staging environment validated
- ✓ Production deployment through automated script
- ✓ Comprehensive 24-hour monitoring
- ✓ Success rate documented
- ✓ Team trained
- ✓ Production sign-off completed

---

## 📚 Reference Documents

All Phase 5 materials created:

| Document | Purpose | When to Use |
|----------|---------|-------------|
| **[PRODUCTION_CONFIGURATION_CHECKLIST.md](PRODUCTION_CONFIGURATION_CHECKLIST.md)** | Complete pre-deployment checklist | Before deploying |
| **[WEBHOOK_REGISTRATION_GUIDE.md](WEBHOOK_REGISTRATION_GUIDE.md)** | Step-by-step webhook setup | During Step 2 |
| **[WEBHOOK_ROLLBACK_PROCEDURE.md](WEBHOOK_ROLLBACK_PROCEDURE.md)** | Emergency recovery steps | If deployment fails |
| **[PHASE_2_SECURITY_CONFIGURATION.md](PHASE_2_SECURITY_CONFIGURATION.md)** | Security features documentation | Reference for security settings |
| **scripts/deploy-production.sh** | Automated production deployment | During Step 4 (Path B) |
| **scripts/deploy-staging.sh** | Automated staging deployment | During Step 1 (Path B) |
| **scripts/monitor-webhooks.sh** | Linux webhook health monitor | During monitoring |
| **scripts/monitor-webhooks.ps1** | Windows webhook health monitor | During monitoring (local) |

---

## 🚨 Emergency Procedures

### If Deployment Fails

1. **Check:** [WEBHOOK_ROLLBACK_PROCEDURE.md](WEBHOOK_ROLLBACK_PROCEDURE.md)
2. **Disable webhooks** at billing platform
3. **Decision:** Rollback vs. hotfix
4. **Execute:** Follow rollback procedure if needed
5. **Document:** What went wrong in incident report

### If Webhooks Failing in Production

**Under 5% failure:** Monitor, investigate individual failures
**5-20% failure:** Urgent - investigate, may need hotfix
**Over 20% failure:** Critical - disable webhooks, initiate rollback

**Quick diagnosis:**
```bash
tail -n 100 storage/logs/laravel.log | grep "ERROR"
./scripts/monitor-webhooks.sh 1
```

Common issues:
- 401 errors → Secret mismatch (check .env)
- 403 errors → IP not whitelisted (update middleware)
- 400 errors → Validation failure (check payload format)
- 500 errors → Application error (check logs)

---

## 📊 Success Metrics

**Week 1 targets:**
- Webhook success rate: >99%
- Average processing time: <500ms
- Duplicate processing: 0
- Manual credit additions: 0
- Customer complaints: 0

**Daily monitoring (first week):**
```bash
# Run once per day
./scripts/monitor-webhooks.sh 24

# Review report
cat storage/logs/webhook_health_*.txt
```

**Weekly monitoring (ongoing):**
```bash
# Run once per week  
./scripts/monitor-webhooks.sh 168

# Check trends over time
```

---

## ✅ Post-Deployment Checklist

After completing Phase 5 (either path):

- [ ] Production IP whitelist configured with real IPs
- [ ] Webhook registered in billing platform dashboard
- [ ] Production webhook secret in .env and loaded
- [ ] Test payment completed successfully
- [ ] Monitoring shows healthy metrics (>95% success)
- [ ] Team knows how to check webhook health
- [ ] Team knows where rollback procedure is
- [ ] Support team trained on admin panel
- [ ] On-call schedule established
- [ ] Slack alerts configured (if applicable)

---

## 🎓 Team Training

**Train your team on:**

1. **Checking webhook health:**
   ```bash
   ./scripts/monitor-webhooks.sh 24
   ```

2. **Reading Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Billing webhook"
   ```

3. **Admin panel access:**
   - URL: https://safarichat.com/admin/billing/webhooks
   - What to look for: failed webhooks, stuck webhooks, duplicates

4. **When to escalate:**
   - Success rate <95% → Urgent
   - Any customers reporting missing credits → Urgent
   - Webhook completely down → Critical

5. **Rollback authority:**
   - Who can authorize rollback?
   - Emergency contact numbers

---

## 📞 Support Contacts

**Add your contacts:**

- **Billing Platform Support:** support@safaribank.africa
- **Primary Developer:** ___________________
- **Backup Developer:** ___________________
- **DevOps/Infrastructure:** ___________________
- **On-call Manager:** ___________________

---

## 🎯 Next Phase (Optional)

**Phase 3: Automated Testing** (if not done yet)
- PHPUnit unit tests for webhook controller
- Integration tests for end-to-end flow
- Run in CI/CD pipeline

**Phase 4: Admin Dashboard** (if not done yet)
- Webhook monitoring UI
- Slack alert integration
- Webhook replay functionality
- Health metrics dashboard

---

## ✨ Congratulations!

If you've completed Phase 5, your billing webhook system is now:

✅ **Production-ready** - Deployed and processing real payments  
✅ **Secure** - IP whitelisted, rate limited, signature validated  
✅ **Reliable** - Idempotency prevents duplicates  
✅ **Monitored** - Health metrics and alerting in place  
✅ **Recoverable** - Rollback procedure documented and tested

**Your webhook implementation is complete!** 🎉

Monitor for the first week, then transition to routine weekly checks.
