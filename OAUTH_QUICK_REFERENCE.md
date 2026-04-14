# 🚀 OAuth Smart Backoff - Quick Reference

## ✅ What's Fixed

**BEFORE**: Your logs were filled with repeated OAuth errors on every request
```
[11:59:55] ERROR: Failed to refresh Shulesoft access token...  
[11:59:57] ERROR: Failed to refresh Shulesoft access token...  
[11:59:59] ERROR: Failed to refresh Shulesoft access token...
(hundreds of times...)
```

**NOW**: Smart backoff prevents spam and automatically uses static token
```
[12:16:56] WARNING: OAuth unavailable  
            {"failure_count":1,"backoff_seconds":300,"retry_after":"2026-04-10 12:21:56"}
(Won't try again for 5 minutes - no more spam!)
```

## 📊 Quick Commands

### Check OAuth Status
```bash
php artisan shulesoft:auth-status
```
Shows: Configuration, token status, failures, backoff time, recommendations

### Test Authentication  
```bash
php artisan shulesoft:auth-status --test
```
Tests if your credentials work (triggers backoff if they fail)

### Fix and Retry (After updating .env)
```bash
php artisan shulesoft:auth-status --enable
```
Clears backoff and forces immediate retry

### Full Reset
```bash
php artisan shulesoft:auth-status --reset  
```
Clears all cache and tests authentication

## 🔧 Fix the Root Cause

The **real problem** is invalid credentials. To fix permanently:

### 1. Get Valid Credentials from Shulesoft
Contact Shulesoft support for production OAuth credentials

### 2. Update .env
```env
SHULESOFT_AUTH_EMAIL=your-real-email@example.com
SHULESOFT_AUTH_PASSWORD=your-real-password
```

### 3. Clear Backoff and Test
```bash
php artisan shulesoft:auth-status --enable
```

## ⏱️ Backoff Schedule

| Failure | Wait Time | What It Means |
|---------|-----------|---------------|
| 1st     | 5 min     | Maybe temporary issue |
| 2nd     | 10 min    | OAuth likely broken |
| 3rd     | 20 min    | Definitely broken |
| 4th     | 40 min    | Stop hammering the API |
| 5th+    | 60 min    | Maximum backoff |

## 🛡️ Safety Features

✅ **Auto-Fallback**: Uses static token (`BILLING_ACCESS_TOKEN`) during backoff  
✅ **Reduced Logging**: One warning per backoff period (not one per request)  
✅ **Smart Recovery**: Automatically clears backoff on successful auth  
✅ **HTML Detection**: Alerts you when API returns HTML (wrong credentials)

## ⚠️ Current Status

Run this to see your current status:
```bash
php artisan shulesoft:auth-status
```

Look for:
- **"IN BACKOFF PERIOD"** = System is sleeping due to failures
- **"ACTIVE (OAuth in backoff)"** = Currently using static token
- **Last Error** = Shows what went wrong

## 🎯 Most Common Issue

**Problem**: "Invalid login response, no access token received"

**Cause**: The credentials in `.env` are test/demo credentials

**Solution**:
1. Get real production credentials from Shulesoft
2. Update `SHULESOFT_AUTH_EMAIL` and `SHULESOFT_AUTH_PASSWORD` in `.env`
3. Run: `php artisan shulesoft:auth-status --enable`
4. Verify: `php artisan shulesoft:auth-status`

## 📞 Still Having Issues?

If you've set valid credentials but it still fails:

1. **Check the status**:
   ```bash
   php artisan shulesoft:auth-status
   ```

2. **Look at "Last Error"** - it tells you what's wrong

3. **Common fixes**:
   - Wrong API URL → Check `SHULESOFT_API_URL` in `.env`
   - Wrong credentials → Verify they work on Shulesoft portal
   - API down → Wait for backoff to expire

4. **Contact Shulesoft** with:
   - Your organization email
   - The "Last Error" message
   - Confirmation credentials work on their portal

---

**Remember**: The backoff system is *working as designed*. It prevents your logs from being flooded with errors. Fix the credentials and it will self-heal! 🎉
