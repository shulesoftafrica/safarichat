# Shulesoft OAuth Authentication - Smart Error Handling & Backoff Strategy

## Problem Summary

Your application was experiencing **repeated OAuth authentication errors** despite having error handling in place. The errors kept happening on every request because:

1. **Invalid/Test Credentials** - The configured credentials (`admin@techsoft-solutions.com` / `password123`) don't work with the production Shulesoft API
2. **API Returns HTML not JSON** - When authentication fails, the API returns the Shulesoft website HTML instead of a proper JSON error
3. **No Failure Memory** - The system didn't remember that OAuth failed and kept retrying on every request
4. **No Backoff Strategy** - Without a backoff mechanism, the system made hundreds of failed attempts

## Solution Implemented

### 1. Smart Backoff Strategy

The system now implements **exponential backoff** with failure tracking:

- **First failure**: Wait 5 minutes before retrying
- **Second failure**: Wait 10 minutes
- **Third failure**: Wait 20 minutes
- **Fourth+ failure**: Wait 40 minutes (capped at 1 hour)

During backoff periods, the system **automatically uses the static token fallback** without logging errors repeatedly.

### 2. Enhanced Error Detection

The system now detects and handles:

- **HTML responses** (indicates wrong credentials or API issues)
- **OAuth server errors** (database/table missing)
- **Network timeouts**
- **Invalid JSON responses**

When HTML is detected, a clear warning is logged with troubleshooting hints.

### 3. Improved Logging

Instead of cryptic stack traces on every request, you now get:

```
OAuth in backoff period, 287s remaining, using static token
```

Or when failures occur:

```
⚠️ OAuth API returning HTML instead of JSON - Check credentials and API endpoints
```

## How to Use

### Check OAuth Status

```bash
php artisan shulesoft:auth-status
```

This shows:
- Current configuration
- Token status
- Failure count and backoff status
- Last error message
- Recommendations for fixing

### Test Authentication

```bash
php artisan shulesoft:auth-status --test
```

Tests if your credentials work with the Shulesoft API.

### Reset After Fixing Credentials

After updating credentials in `.env`:

```bash
php artisan shulesoft:auth-status --enable
```

This clears the backoff and forces a retry immediately.

### Clear All Auth Cache

```bash
php artisan shulesoft:auth-status --reset
```

## Configuration Required

### Step 1: Get Valid Credentials

Contact Shulesoft support to get valid production credentials. Update your `.env`:

```env
# Shulesoft OAuth Authentication
SHULESOFT_API_URL=https://api.safaribank.africa/api/v1
SHULESOFT_AUTH_EMAIL=your-actual-email@example.com
SHULESOFT_AUTH_PASSWORD=your-actual-password
SHULESOFT_ORGANIZATION_EMAIL=your-organization@example.com

# Static Token Fallback (REQUIRED)
BILLING_ACCESS_TOKEN=your-static-token-here
```

**IMPORTANT**: The `BILLING_ACCESS_TOKEN` is critical - it's used during OAuth backoff periods.

### Step 2: Test Configuration

```bash
php artisan shulesoft:auth-status --test
```

### Step 3: Monitor in Production

The backoff system means you'll see far fewer errors in production logs. OAuth failures are expected and handled gracefully.

## What Happens Now

### Before (Without Backoff)

```
[11:59:55] ERROR: Failed to refresh Shulesoft access token...
[11:59:57] ERROR: Failed to refresh Shulesoft access token...
[11:59:59] ERROR: Failed to refresh Shulesoft access token...
[12:00:01] ERROR: Failed to refresh Shulesoft access token...
[12:00:03] ERROR: Failed to refresh Shulesoft access token...
// Repeats hundreds of times... 
```

### After (With Backoff)

```
[11:59:55] WARNING: OAuth unavailable: Invalid login response
            failure_count: 1, backoff_seconds: 300, retry_after: 2026-04-10 12:04:55
[12:04:55] WARNING: OAuth unavailable: Invalid login response
            failure_count: 2, backoff_seconds: 600, retry_after: 2026-04-10 12:14:55
[12:14:55] WARNING: OAuth unavailable: Invalid login response
            failure_count: 3, backoff_seconds: 1200, retry_after: 2026-04-10 12:34:55
// System uses static token between retries - no repeated errors
```

## Backoff Behavior Chart

| Failure # | Backoff Time | Retry After        |
|-----------|--------------|-------------------|
| 1         | 5 minutes    | First failure + 5m |
| 2         | 10 minutes   | Second failure + 10m |
| 3         | 20 minutes   | Third failure + 20m |
| 4         | 40 minutes   | Fourth failure + 40m |
| 5+        | 60 minutes   | Capped at 1 hour |

## Troubleshooting

### Error: "API returning HTML instead of JSON"

**Cause**: Invalid credentials or wrong API endpoint

**Fix**:
1. Verify `SHULESOFT_AUTH_EMAIL` and `SHULESOFT_AUTH_PASSWORD` in `.env`
2. Contact Shulesoft support for correct production credentials
3. Verify API URL is correct: `https://api.safaribank.africa/api/v1`

### OAuth Stuck in Backoff

If you've fixed credentials but OAuth is still in backoff:

```bash
php artisan shulesoft:auth-status --enable
```

This immediately clears backoff and retries authentication.

### Static Token Not Working

Check your `.env` has valid `BILLING_ACCESS_TOKEN`:

```bash
php artisan config:clear
php artisan cache:clear
grep BILLING_ACCESS_TOKEN .env
```

## Code Changes Summary

### New Constants in ShulesoftAuthService

```php
const CACHE_KEY_OAUTH_FAILED = 'shulesoft_oauth_failed';
const CACHE_KEY_FAILURE_COUNT = 'shulesoft_oauth_failure_count';
const CACHE_KEY_LAST_FAILURE_TIME = 'shulesoft_oauth_last_failure';
const INITIAL_BACKOFF = 300; // 5 minutes
const MAX_BACKOFF = 3600; // 1 hour
const MAX_CONSECUTIVE_FAILURES = 3;
```

### New Methods Added

- `isInBackoffPeriod()` - Check if currently in backoff
- `getBackoffRemainingTime()` - Get remaining backoff seconds
- `calculateBackoffTime($failureCount)` - Exponential backoff calculation
- `recordOAuthFailure($exception)` - Track failures
- `clearOAuthFailures()` - Reset on success
- `isHtmlResponse($message)` - Detect HTML errors

### Enhanced Methods

- `getAccessToken()` - Now checks backoff before attempting OAuth
- `refreshAccessToken()` - Clears failures on success
- `getAuthStatus()` - Added backoff info
- `clearAuthCache()` - Clears failure tracking
- `enableOAuth()` - Clears backoff

## Benefits

✅ **Reduced Log Spam** - Failures logged once per backoff period, not on every request

✅ **Automatic Recovery** - System automatically falls back to static token

✅ **Self-Healing** - Automatically retries with increasing delays

✅ **Better Diagnostics** - Clear error messages with actionable hints

✅ **Production Ready** - Gracefully handles API outages without impacting users

✅ **Zero Downtime** - Static token keeps billing working during OAuth issues

## Next Steps

1. **Get Valid Credentials** - Contact Shulesoft for production OAuth credentials
2. **Update .env** - Set `SHULESOFT_AUTH_EMAIL` and `SHULESOFT_AUTH_PASSWORD`
3. **Test** - Run `php artisan shulesoft:auth-status --test`
4. **Enable** - Run `php artisan shulesoft:auth-status --enable` if currently in backoff
5. **Monitor** - Check logs to ensure errors are reduced

## Support

If OAuth continues failing after configuring valid credentials:

1. Check the status: `php artisan shulesoft:auth-status`
2. Review the full error in logs: `tail -f storage/logs/laravel.log`
3. Contact Shulesoft support with:
   - Your organization email
   - The "Last Error" from the status command
   - Confirmation that credentials work on their web portal

---

**Last Updated**: April 10, 2026
**Version**: 1.0
**Status**: ✅ Deployed and Active
