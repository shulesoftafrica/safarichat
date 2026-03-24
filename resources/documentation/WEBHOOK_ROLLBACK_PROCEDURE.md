# Webhook Rollback Procedure
## Emergency Recovery Steps for Failed Deployment

**Version:** 1.0  
**Last Updated:** March 24, 2026  
**Decision Making:** Only rollback if CRITICAL issues affect payment processing

---

## 🚨 When to Rollback

### Critical Issues (Immediate Rollback Required)

- ✅ **All webhooks failing** (0% success rate)
- ✅ **Database corruption** or data loss detected
- ✅ **Payment processing completely broken**
- ✅ **Application down** (500 errors on all requests)
- ✅ **Duplicate credit additions** (customers charged 2x+)
- ✅ **Security breach** detected

### Non-Critical Issues (Monitor, Don't Rollback)

- ❌ Single webhook failure (<5% failure rate)
- ❌ Slow response times (performance degradation)
- ❌ Cosmetic UI issues
- ❌ Non-critical feature bugs
- ❌ Logging issues

**Rule of Thumb:** If customers can still complete payments and receive credits reliably (>95% success rate), monitor instead of rolling back.

---

## ⏱️ Time-Critical Actions

### Immediate (0-5 minutes)

1. **Disable incoming webhooks** at the source
2. **Enable maintenance mode** (optional)
3. **Notify stakeholders**

### Short-term (5-30 minutes)

4. **Assess impact** and decide rollback vs. hotfix
5. **Execute rollback** if decided
6. **Verify recovery**

### Post-recovery (30+ minutes)

7. **Root cause analysis**
8. **Manual payment processing** if needed
9. **Plan fix** and re-deployment

---

## Step-by-Step Rollback Procedure

### Step 1: Disable Incoming Webhooks (Immediate)

**Action:** Prevent new webhooks while fixing issues

**Option A: Disable at Billing Platform**
1. Login: https://api.safaribank.africa/dashboard
2. Navigate: Settings → Webhooks
3. Find: SafariChat webhook endpoint
4. Click: "Disable" or "Pause"
5. Confirm: Webhook status shows "Disabled"

**Option B: Temporary IP Block (if platform doesn't support disable)**
```bash
# SSH to production server
ssh user@safarichat.com

# Block billing platform IPs temporarily
sudo iptables -A INPUT -s 41.59.0.0/16 -j DROP
sudo iptables -A INPUT -s 197.156.0.0/16 -j DROP

# Verify rules added
sudo iptables -L -n
```

**Verification:**
- Send test webhook → Should be blocked or disabled
- Check Laravel logs → No new webhook entries

---

### Step 2: Enable Maintenance Mode (Optional)

**When:** If the entire application is affected (not just webhooks)

```bash
ssh user@safarichat.com
cd /var/www/safarichat

# Enable maintenance mode with secret bypass
php artisan down --retry=60 --secret="$(openssl rand -hex 16)"

# Save the secret for admin access
echo "Maintenance mode bypass: https://safarichat.com/$(openssl rand -hex 16)"
```

**Result:** Users see maintenance page, but admins can still access with secret URL

---

### Step 3: Notify Stakeholders

**Who to notify:**
- Development team
- Operations team
- Customer support team
- Management (if revenue-impacting)

**Slack message template:**
```
🚨 PRODUCTION ALERT 🚨

Issue: Webhook deployment failure
Severity: High
Status: Rollback in progress

Actions taken:
- Webhooks disabled at billing platform
- Maintenance mode: [Yes/No]
- Estimated recovery: 15-30 minutes

Impact:
- New payments: [Describe impact]
- Existing customers: [Not affected/Affected]

Updates every 10 minutes in thread ⬇️
```

---

### Step 4: Determine Current State

**Check what git commit is deployed:**
```bash
cd /var/www/safarichat
git log --oneline -n 5

# Output example:
# abc1234 (HEAD) Phase 2: Security enhancements
# def5678 Phase 1: Database fixes
# ghi9012 Previous working version
```

**Identify last known good commit:**
- Check deployment history
- Review git tags: `git tag -l`
- Last stable: Usually the commit before Phase 1 changes

---

### Step 5: Database Rollback (If Needed)

**⚠️ CRITICAL:** Only rollback database if migrations corrupted data

**Check if migration can be rolled back safely:**
```bash
php artisan migrate:status

# Look for Phase 1 migration:
# 2026_03_25_000000_fix_billing_webhook_schema.php
```

**Option A: Rollback Migration (Preferred)**
```bash
# This reverses the Phase 1 migration
php artisan migrate:rollback --step=1

# Verify tables removed/columns dropped
php artisan tinker
>>> Schema::hasTable('billing_webhook_events')  # Should be false
>>> exit
```

**Option B: Restore Database Backup (If migration rollback fails)**
```bash
# List available backups
ls -lh /var/backups/safarichat/*.sql

# Restore backup (⚠️ DESTRUCTIVE - all data since backup will be lost)
php artisan db:restore --filename="production_20260324_120000.sql"

# WARNING: This loses all webhook events and account updates since backup!
```

**⚠️ Database Rollback Impact:**
- Option A (Rollback migration): Safe, just removes new fields/table
- Option B (Restore backup): **LOSES ALL DATA SINCE BACKUP**

**Decision criteria:**
- Migration broke database → Use Option A
- Data corrupted beyond repair → Use Option B (last resort)
- Just code bugs, database OK → Skip database rollback entirely

---

### Step 6: Code Rollback

**Option A: Git Revert (Preserves history)**
```bash
cd /var/www/safarichat

# Revert the last commit (creates a new commit that undoes changes)
git revert HEAD --no-edit

# If multiple commits need reverting
git revert HEAD~2..HEAD --no-edit

# Push revert commit
git push origin main
```

**Option B: Git Reset (Destroys history - use with caution)**
```bash
cd /var/www/safarichat

# Find last good commit
git log --oneline -n 10

# Reset to that commit (⚠️ DESTRUCTIVE)
git reset --hard ghi9012  # Replace with actual commit hash

# Force push (⚠️ OVERWRITES REMOTE)
git push origin main --force
```

**Option C: Restore from Backup**
```bash
# Extract previous backup
cd /var/www/safarichat
tar -xzf /var/backups/safarichat/safarichat_20260324_120000.tar.gz

# Note: This overwrites all files including .env, storage, etc.
# Be careful with configs!
```

**Recommendation:** Use Option A (git revert) - safest, preserves history

---

### Step 7: Reinstall Dependencies

```bash
cd /var/www/safarichat

# Install correct composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Rebuild caches with correct code
php artisan config:cache
php artisan route:cache
```

---

### Step 8: Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart queue workers
php artisan queue:restart

# Restart web server
sudo systemctl restart nginx

# If using Supervisor for queues
sudo supervisorctl restart all
```

---

### Step 9: Verify Recovery

**Check application health:**
```bash
# Test application responds
curl -I https://safarichat.com/health

# Expected: HTTP/2 200 OK
```

**Check webhook endpoint:**
```bash
curl -X POST https://safarichat.com/api/billing/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "rollback_verification"}'

# Expected: 401 Unauthorized (signature missing)
# Bad: 500 Internal Server Error
```

**Check database connectivity:**
```bash
php artisan tinker
>>> DB::connection()->getPdo()  # Should return PDO object, not error
>>> exit
```

**Smoke tests:**
- [ ] Login to admin panel works
- [ ] Create test user works
- [ ] Test payment flow works (even if webhook disabled)
- [ ] No 500 errors in Laravel logs

---

### Step 10: Disable Maintenance Mode

```bash
cd /var/www/safarichat
php artisan up
```

**Verify:**
- Application accessible to all users
- No maintenance page showing

---

### Step 11: Re-enable Webhooks (Carefully)

**Only after confirming application stable!**

**Option A: Re-enable at Billing Platform**
1. Login: https://api.safaribank.africa/dashboard
2. Settings → Webhooks
3. Find SafariChat webhook
4. Click "Enable"

**Option B: Remove IP block**
```bash
# Remove temporary iptables rules
sudo iptables -D INPUT -s 41.59.0.0/16 -j DROP
sudo iptables -D INPUT -s 197.156.0.0/16 -j DROP
```

**Test with single webhook:**
- Send test webhook from platform
- Verify it processes correctly
- Check no errors in logs

---

### Step 12: Monitor Closely

**First hour after recovery:**
- Watch Laravel logs in real-time: `tail -f storage/logs/laravel.log`
- Check webhook health: `./scripts/monitor-webhooks.sh 1`
- Monitor success rate: Should be back to >99%

**If issues persist:**
- Disable webhooks again
- Investigate root cause more thoroughly
- Consider reverting to even older version

---

## Manual Payment Processing During Outage

**If webhooks disabled for extended period:**

### Step 1: Export Pending Payments

Contact billing platform support to export list of:
- Successful payments during outage window
- Transaction IDs
- Customer IDs
- Amounts paid

### Step 2: Manual Credit Addition

```bash
php artisan tinker

# For each payment, run:
>>> $account = \App\Models\BillingAccount::where('customer_id', 123)->first();
>>> $account->addCredits(10000, 'Manual addition - webhook outage on 2026-03-24');
>>> $account->subscription_status = 'active';
>>> $account->subscription_expires_at = now()->addDays(30);
>>> $account->save();
```

### Step 3: Document Manual Additions

Create CSV log:
```csv
transaction_id,customer_id,credits_added,reason,processed_by,processed_at
TXN_123,456,10000,Webhook outage manual credit,john@safarichat.com,2026-03-24 15:30:00
```

---

## Post-Rollback Actions

### Immediate (Within 1 hour)

- [ ] Root cause analysis meeting
- [ ] Document what went wrong
- [ ] Update rollback procedure with lessons learned
- [ ] Notify customers if payments were affected

### Short-term (Within 24 hours)

- [ ] Fix the issue that caused rollback
- [ ] Test fix thoroughly on staging
- [ ] Create detailed test plan for re-deployment
- [ ] Review with team before attempting re-deploy

### Long-term (Within 1 week)

- [ ] Implement additional safeguards
- [ ] Add more comprehensive tests
- [ ] Update deployment checklist
- [ ] Train team on rollback procedures

---

## Root Cause Analysis Template

```markdown
## Rollback Incident Report

**Date:** 2026-03-XX
**Duration:** XX minutes
**Severity:** High/Critical

### What Happened
[Brief description of the issue]

### Timeline
- 14:00 - Deployment started
- 14:15 - Issue detected
- 14:20 - Rollback initiated
- 14:35 - Service restored

### Root Cause
[Technical explanation of what went wrong]

### Impact
- Webhooks affected: XX
- Customers affected: XX
- Revenue impact: $XX
- Payments manually processed: XX

### What Went Well
- Rollback procedure worked as documented
- Team responded quickly
- No data lost

### What Went Wrong
- [Issue 1]
- [Issue 2]

### Actions to Prevent Recurrence
1. [Action item 1] - Owner: [Name] - Due: [Date]
2. [Action item 2] - Owner: [Name] - Due: [Date]

### Follow-up
Next review: [Date]
```

---

## Rollback Decision Matrix

| Issue | Severity | Rollback? | Alternative |
|-------|----------|-----------|-------------|
| All webhooks failing | Critical | ✅ Yes | None |
| <5% failure rate | Low | ❌ No | Monitor + hotfix |
| Duplicate credits | Critical | ✅ Yes | None |
| Slow performance | Medium | ❌ No | Optimize code |
| Security vulnerability | Critical | ✅ Yes | None |
| Database corruption | Critical | ✅ Yes | None |
| One event type failing | Medium | ❌ No | Disable that event type |

---

## Emergency Contacts

**During rollback, contact:**

- **On-call Developer:** [Phone number]
- **DevOps Lead:** [Phone number]
- **Database Admin:** [Phone number]
- **Billing Platform Support:** support@safaribank.africa

**Escalation path:**
1. Developer (0-15 min)
2. Tech Lead (15-30 min)
3. CTO (30+ min if not resolved)

---

## Rollback Verification Checklist

After completing rollback, verify:

- [ ] Application accessible (200 OK on homepage)
- [ ] Admin panel works
- [ ] Database queries working
- [ ] No 500 errors in logs for 15 minutes
- [ ] Webhook endpoint responds (even if disabled)
- [ ] Queue workers running
- [ ] Test payment completes successfully
- [ ] Stakeholders notified of recovery
- [ ] Monitoring shows healthy metrics
- [ ] Backup of failed deployment state saved

**Only declare recovery complete when ALL items checked.**

---

## Prevention Checklist for Next Deployment

- [ ] More comprehensive testing on staging
- [ ] Longer staging soak test (24 hours minimum)
- [ ] Feature flags for gradual rollout
- [ ] Database migration tested on production clone
- [ ] Rollback procedure tested on staging
- [ ] Team trained on rollback steps
- [ ] Monitoring alerts configured before deployment
- [ ] Off-hours deployment scheduled
- [ ] on-call developer assigned
- [ ] Communication plan prepared
