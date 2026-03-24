# OAuth Error Fix Summary

## Date: March 14, 2026

## Problem Identified

The error you encountered:
```
[2026-03-14 17:39:01] local.ERROR: Failed to refresh Shulesoft access token: Failed to create OAuth client:
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "oauth_clients" does not exist
```

### Root Cause

**This is NOT a problem with your code.** This is a **server-side database issue on the Shulesoft Billing API**.

When your application tries to create an OAuth client (Step 2 of the OAuth flow), the Shulesoft API server attempts to insert data into a database table called `oauth_clients`, but **that table doesn't exist** on their PostgreSQL database.

The error stack trace shows this is happening on their server:
- Server: 127.0.0.1:5996 (Shulesoft's internal PostgreSQL)
- Database: shulesoft2024
- Missing table: `o_authclients`
- File: `/usr/share/nginx/html/billing/app/Http/Controllers/Auth/ClientCredentialsController.php` (on their server)

## Solution Implemented

I've updated your system to **gracefully handle this OAuth server error** and automatically fall back to using the static token from your `.env` file.

### Changes Made

#### 1. **Enhanced Error Detection** (`ShulesoftAuthService.php`)
- Added `isOAuthServerError()` method to detect database/OAuth unavailability errors
- Detects PostgreSQL error codes (42P01), missing table names, and endpoint failures
- Automatically disables OAuth for 1 hour when server errors are detected

#### 2. **Automatic Fallback** (`ShulesoftAuthService.php`)
- Modified `getAccessToken()` to return `null` when OAuth is unavailable
- Added cache flag `shulesoft_oauth_disabled` to prevent repeated failed requests
- System automatically switches to static token fallback

#### 3. **Improved Token Handling** (`BillingService.php` & `BillingApiController.php`)
- Updated both services to check for `null` return from OAuth
- Seamlessly fall back to static token from `config('services.billing.access_token')`
- **Added OAuth disabled check in retry logic** - prevents retry attempts when OAuth is down
- Clear logging when fallback is used

#### 4. **Enhanced Testing** (`TestShulesoftAuth.php`)
- Added `--enable-oauth` option to re-enable OAuth when API is fixed
- Status command now shows whether OAuth is disabled
- Clear warnings when using fallback authentication

### Current Behavior

✅ **System is working correctly:**

1. **Initial Request**: Attempts OAuth authentication
2. **Detects Error**: Identifies server-side database issue
3. **Logs Warning**: Records that OAuth is unavailable
4. **Auto-Fallback**: Switches to static token from BILLING_ACCESS_TOKEN
5. **Temporary Disable**: Disables OAuth attempts for 1 hour to avoid repeated failures
6. **Continues Working**: Your billing operations continue using the static token

## Testing Commands

### Check Current Status
```bash
php artisan shulesoft:test-auth --status
```

### Clear Cache and Re-test OAuth
```bash
php artisan shulesoft:test-auth --clear
```

### Re-enable OAuth (after API is fixed)
```bash
php artisan shulesoft:test-auth --enable-oauth
```

### Full Authentication Test
```bash
php artisan shulesoft:test-auth
```

## Current Status

**OAuth Status**: ⚠️ Disabled (API server database issue)  
**Fallback**: ✅ Active (using BILLING_ACCESS_TOKEN)  
**System Impact**: ✅ Minimal - billing operations continue normally

## Next Steps

### Option 1: Contact Shulesoft (Recommended)

Contact the Shulesoft API team about this issue:

**Issue Details:**
- Missing database table: `oauth_clients`
- Endpoint affected: `POST /v1/oauth/clients`
- Error code: PostgreSQL 42P01 (Undefined table)
- Database: shulesoft2024 (Port 5996)

They need to:
1. Run migrations to create the `oauth_clients` table
2. Or inform you if OAuth isn't ready yet

### Option 2: Continue with Static Token

If OAuth isn't ready on their end, you can continue using the static token:

1. Ensure `BILLING_ACCESS_TOKEN` in `.env` is valid
2. The system will automatically use it for all billing operations
3. No code changes needed - fallback is automatic

### Option 3: Re-test OAuth Later

When Shulesoft fixes their database:

```bash
# Re-enable OAuth and clear the disabled flag
php artisan shulesoft:test-auth --enable-oauth

# Test authentication
php artisan shulesoft:test-auth
```

## Log Monitoring

Monitor logs for authentication status:

```powershell
# View recent authentication logs
Get-Content storage\logs\laravel.log -Tail 50 | Select-String "shulesoft|oauth"

# Watch real-time logs
Get-Content storage\logs\laravel.log -Wait | Select-String "shulesoft|oauth"
```

### Log Messages to Look For

**✅ Successful OAuth:**
```
[INFO] User login successful
[INFO] OAuth client created successfully
[INFO] Shulesoft access token refreshed successfully
```

**⚠️ OAuth Disabled (Expected currently):**
```
[WARNING] OAuth not available on Shulesoft API server (database table missing)
[WARNING] OAuth unavailable, using static token fallback
```

**❌ Static Token Issues:**
```
[ERROR] Failed to get access token
[ERROR] API call returned 401 Unauthenticated
```

## Environment Variables

Ensure these are set in your `.env` file:

### OAuth Credentials (for when server is fixed)
```env
SHULESOFT_API_URL=https://shulesoftapi.shulesoft.africa/api/v1
SHULESOFT_AUTH_EMAIL=admin@techsoft-solutions.com
SHULESOFT_AUTH_PASSWORD=password123
SHULESOFT_ORGANIZATION_EMAIL=shulesoftcompany@gmail.com
```

### Static Token Fallback (current)
```env
BILLING_ACCESS_TOKEN=your_static_token_here
```

## Benefits of This Implementation

✅ **Resilient**: Automatically handles API server issues  
✅ **No Downtime**: Seamless fallback to static token  
✅ **Self-Healing**: Re-attempts OAuth after cooldown period  
✅ **Clear Logging**: Easy to diagnose issues  
✅ **Manual Control**: Commands to manage OAuth status  
✅ **Zero Code Impact**: Billing operations work unchanged

## Technical Details

### Cache Keys Used
- `shulesoft_oauth_disabled` - Boolean flag (1-hour TTL)
- `shulesoft_access_token` - OAuth access token (89-day TTL)
- `shulesoft_client_id` - OAuth client ID (permanent)
- `shulesoft_client_secret` - OAuth client secret (permanent)
- `shulesoft_token_expires_at` - Token expiration timestamp

### OAuth Flow (when working)
1. Login: `POST /v1/auth/login` → user token
2. Create Client: `POST /v1/oauth/clients` → client_id & client_secret ❌ (fails here currently)
3. Get Token: `POST /v1/oauth/token` → access token

### Error Detection Logic
```php
// Detects PostgreSQL missing table errors
strpos($message, 'oauth_clients') !== false
strpos($message, 'Undefined table') !== false
strpos($message, '42P01') !== false  // PostgreSQL error code
```

## Summary

Your system is **now production-ready** with automatic error handling:
- ✅ Detects OAuth server issues
- ✅ Falls back to static token automatically
- ✅ Logs clear warnings
- ✅ Provides commands for manual control
- ✅ Will automatically re-attempt OAuth when available

**No further action required unless:**
1. You want to contact Shulesoft about the database issue
2. Your static token expires and needs renewal
3. Shulesoft fixes their OAuth and you want to re-enable it

---

**Created by**: GitHub Copilot  
**Date**: March 14, 2026  
**Status**: ✅ Fix Complete
