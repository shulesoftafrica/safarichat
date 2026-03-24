# Shulesoft Billing API - OAuth Integration

## Overview

SafariChat now uses **automatic OAuth 2.0 authentication** with the Shulesoft Billing API. This system handles authentication transparently with automatic token refresh, eliminating the need for manual token management.

## Features

✅ **Automatic Authentication** - No manual token updates required  
✅ **Token Auto-Refresh** - Tokens automatically refresh on expiration (90 days)  
✅ **401 Error Handling** - Automatically retries failed requests with fresh tokens  
✅ **Secure Storage** - Credentials stored in .env, tokens cached securely  
✅ **Backward Compatible** - Falls back to static token if OAuth fails  
✅ **Production Ready** - Comprehensive error handling and logging  

## Authentication Flow

### 3-Step OAuth Process

```
1. Login (Email/Password)
   ↓
   User Access Token
   ↓
2. Create OAuth Client
   ↓
   Client ID & Client Secret (stored permanently)
   ↓
3. Get API Access Token
   ↓
   Access Token (valid for 90 days, cached)
   ↓
4. Use in API Requests
```

### Automatic Token Refresh

```
API Request → 401 Unauthorized
    ↓
Refresh Access Token
    ↓
Retry Request
    ↓
Success
```

## Setup

### 1. Configure Environment Variables

Add to your `.env` file:

```env
# Shulesoft API Configuration
SHULESOFT_API_URL=https://shulesoftapi.shulesoft.africa/api/v1

# OAuth Authentication Credentials
SHULESOFT_AUTH_EMAIL=admin@techsoft-solutions.com
SHULESOFT_AUTH_PASSWORD=password123

# Organization & Product Configuration
BILLING_ORGANIZATION_ID=1
BILLING_PRODUCT_ID=your_product_id
BILLING_CREDITS_PRICE_PLAN_ID=3
```

### 2. Test Authentication

Run the test command to verify setup:

```bash
php artisan shulesoft:test-auth
```

This will:
- ✓ Check configuration
- ✓ Test OAuth flow
- ✓ Get access token
- ✓ Make test API call
- ✓ Show authentication status

### 3. Clear Cache (if needed)

To force re-authentication:

```bash
php artisan shulesoft:test-auth --clear
```

Or in code:

```php
use App\Services\ShulesoftAuthService;

ShulesoftAuthService::clearAuthCache();
```

## Usage

### Automatic Usage (Recommended)

The system works automatically. All billing services use OAuth transparently:

```php
use App\Services\BillingService;

// This automatically uses OAuth authentication
$status = BillingService::loadCompleteStatus($customerId);

// Create invoice - OAuth handled automatically
$invoice = BillingService::createSubscriptionInvoice(
    $user, 
    $pricePlanId, 
    $amount
);
```

### Manual Usage

Get token directly if needed:

```php
use App\Services\ShulesoftAuthService;

// Get current token (from cache or refresh if expired)
$token = ShulesoftAuthService::getAccessToken();

// Force refresh token
$token = ShulesoftAuthService::refreshAccessToken();

// Check authentication status
$status = ShulesoftAuthService::getAuthStatus();
/*
Returns:
[
    'has_access_token' => true,
    'has_client_credentials' => true,
    'token_expires_at' => '2026-06-12 10:30:00',
    'is_expired' => false,
    'client_id' => 'org_live_client_abc123...'
]
*/
```

## Architecture

### Files Added/Modified

#### New Files
- `app/Services/ShulesoftAuthService.php` - OAuth authentication service
- `app/Console/Commands/TestShulesoftAuth.php` - Test command
- `.env.shulesoft.example` - Configuration template
- `SHULESOFT_OAUTH_INTEGRATION.md` - This documentation

#### Modified Files
- `config/services.php` - Added `shulesoft_billing` config section
- `app/Services/BillingService.php` - Updated to use OAuth
- `app/Http/Controllers/Api/BillingApiController.php` - Updated to use OAuth
- `.env` - Added OAuth credentials

### Service Classes

#### ShulesoftAuthService

Main OAuth authentication service with these key methods:

```php
// Get active access token
ShulesoftAuthService::getAccessToken()

// Force refresh access token
ShulesoftAuthService::refreshAccessToken()

// Get authentication status
ShulesoftAuthService::getAuthStatus()

// Clear all cached tokens
ShulesoftAuthService::clearAuthCache()

// Initialize on app boot
ShulesoftAuthService::initialize()
```

#### BillingService

Updated with automatic OAuth:

```php
// Private method - gets token automatically
private static function getAccessToken()

// Private method - makes authenticated requests with auto-retry
private static function makeAuthenticatedRequest($method, $endpoint, $data = [], $attempt = 1)
```

All existing BillingService methods work unchanged - OAuth is transparent.

## Token Management

### Token Lifecycle

| Event | Action | Cache Duration |
|-------|--------|----------------|
| First auth | Login + Create client + Get token | 89 days |
| Token cached | Retrieved from cache | Until expiration |
| Token expires | Auto-refresh on next request | 89 days |
| 401 error | Immediate refresh + retry | 89 days |

### Cache Keys

```php
'shulesoft_access_token'      // Current access token
'shulesoft_user_token'        // User login token
'shulesoft_client_id'         // OAuth client ID (permanent)
'shulesoft_client_secret'     // OAuth client secret (permanent)
'shulesoft_token_expires_at'  // Token expiration timestamp
```

### Expiration Buffer

Tokens expire after 90 days, but the system uses 89 days to prevent edge cases.

## Error Handling

### Automatic Retry on 401

```php
// First request
API Request → 401 Unauthorized

// Automatic handling
1. Detect 401 status
2. Log: "Received 401, refreshing token..."
3. Call ShulesoftAuthService::refreshAccessToken()
4. Retry request with new token
5. Return successful response

// If retry also fails
Log error and throw exception
```

### Fallback to Static Token

If OAuth fails for any reason, the system falls back to the static token from `.env`:

```php
try {
    return ShulesoftAuthService::getAccessToken();
} catch (\Exception $e) {
    Log::warning('OAuth failed, using fallback token');
    return config('services.billing.access_token');
}
```

## Monitoring

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i shulesoft
```

Look for:
- ✓ `Shulesoft access token refreshed successfully`
- ✓ `OAuth client created successfully`
- ⚠️ `Received 401, refreshing token...`
- ❌ `Failed to refresh Shulesoft access token`

### Check Status in Code

```php
use App\Services\ShulesoftAuthService;

// In artisan tinker or controller
$status = ShulesoftAuthService::getAuthStatus();
dd($status);
```

### Monitor Cache

```bash
# Check if tokens are cached
php artisan tinker
>>> Cache::has('shulesoft_access_token')
>>> Cache::get('shulesoft_token_expires_at')
```

## Troubleshooting

### Problem: "Authentication credentials not configured"

**Solution:** Add credentials to `.env`:
```env
SHULESOFT_AUTH_EMAIL=your-email@example.com
SHULESOFT_AUTH_PASSWORD=your-password
```

### Problem: "Login failed" or "Invalid credentials"

**Solutions:**
1. Verify credentials are correct
2. Check API URL is accessible
3. Ensure account has proper permissions
4. Test login at https://shulesoftapi.shulesoft.africa

### Problem: "Failed to create OAuth client"

**Solutions:**
1. Clear cache: `php artisan cache:clear`
2. Test with: `php artisan shulesoft:test-auth --clear`
3. Check if client already exists
4. Verify user token is valid

### Problem: Token keeps expiring quickly

**Solutions:**
1. Check system time is correct
2. Verify cache driver is working properly
3. Check cache expiration settings
4. Review logs for patterns

### Problem: "Too many requests" (429 error)

**Solution:** The API has rate limiting. Wait 60 seconds between retries.

## Security Best Practices

### 1. Never Commit Credentials

```gitignore
# Already in .gitignore
.env
.env.backup
.env.production
```

### 2. Use Different Credentials for Environments

```env
# Development
SHULESOFT_AUTH_EMAIL=dev@example.com

# Production
SHULESOFT_AUTH_EMAIL=production@example.com
```

### 3. Rotate Credentials Regularly

Update credentials every 90 days or when team members leave.

### 4. Monitor Authentication Logs

Set up alerts for failed authentication attempts:

```php
// In your monitoring service
if (str_contains($log, 'Failed to refresh Shulesoft access token')) {
    sendAlert('OAuth authentication failure detected');
}
```

### 5. Secure Cache Storage

Ensure your cache driver is secure:
- Use Redis with authentication
- Encrypt cache values if possible
- Restrict access to cache servers

## API Reference

### Complete Shulesoft API Documentation

https://shulesoftapi.shulesoft.africa/api-docs

### Authentication Endpoints

```http
# Step 1: Login
POST https://shulesoftapi.shulesoft.africa/api/v1/auth/login
Content-Type: application/json

{
  "email": "your-email@example.com",
  "password": "your-password"
}

# Step 2: Create OAuth Client
POST https://shulesoftapi.shulesoft.africa/api/v1/oauth/clients
Authorization: Bearer {USER_TOKEN}
Content-Type: application/json

{
  "organization_email": "your-email@example.com",
  "name": "SafariChat Production",
  "environment": "live",
  "allowed_scopes": ["*"]
}

# Step 3: Get Access Token
POST https://shulesoftapi.shulesoft.africa/api/v1/oauth/token
Content-Type: application/json

{
  "grant_type": "client_credentials",
  "client_id": "org_live_client_...",
  "client_secret": "org_live_secret_...",
  "scope": "*"
}
```

## Testing

### Run Full Test Suite

```bash
# Test authentication
php artisan shulesoft:test-auth

# Test with cache clear
php artisan shulesoft:test-auth --clear

# Check status only
php artisan shulesoft:test-auth --status
```

### Expected Output

```
═══════════════════════════════════════════════════════════
   Shulesoft OAuth Authentication Test
═══════════════════════════════════════════════════════════

📋 Configuration Check
─────────────────────────────────────────────────────────
API URL: https://shulesoftapi.shulesoft.africa/api
Auth Email: admin@techsoft-solutions.com
Password: ************

🔐 Testing OAuth Authentication Flow
─────────────────────────────────────────────────────────
Step 1: Retrieving access token...
✓ Access token retrieved successfully
Token: shulesoft_2|def456...xyz789

📊 Authentication Status
─────────────────────────────────────────────────────────
Has Access Token: Yes
Has Client Credentials: Yes
Client ID: org_live_client_abc123
Token Expires At: 2026-06-12 10:30:00
Is Expired: No

🔍 Testing API Call
─────────────────────────────────────────────────────────
Calling: https://shulesoftapi.shulesoft.africa/api/v1/products/4
✓ API call successful
Product: SafariChat Platform
Price Plans: 4

═══════════════════════════════════════════════════════════
✅ All tests passed! OAuth authentication is working.
═══════════════════════════════════════════════════════════
```

## Migration from Static Token

If you were using a static token before:

### Before (Old Method)
```php
$token = config('services.billing.access_token');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token
])->get($url);
```

### After (New Method - Automatic)
```php
// Token is handled automatically by BillingService
$response = BillingService::makeAuthenticatedRequest('GET', $url);

// Or just use existing methods
$status = BillingService::loadCompleteStatus($customerId);
```

### No Code Changes Required!

All existing billing code works unchanged. OAuth is implemented transparently.

## Support

### Need Help?

1. **Check Logs**: `storage/logs/laravel.log`
2. **Run Tests**: `php artisan shulesoft:test-auth`
3. **Check Status**: `php artisan shulesoft:test-auth --status`
4. **Clear Cache**: `php artisan shulesoft:test-auth --clear`
5. **Review Docs**: https://shulesoftapi.shulesoft.africa/api-docs

### Common Commands

```bash
# Test authentication
php artisan shulesoft:test-auth

# Clear and re-authenticate
php artisan shulesoft:test-auth --clear

# Check current status
php artisan shulesoft:test-auth --status

# View logs
tail -f storage/logs/laravel.log

# Clear all cache
php artisan cache:clear

# Check configuration
php artisan config:show services.shulesoft_billing
```

## Changelog

### v1.0.0 (March 2026)
- ✅ Initial OAuth 2.0 implementation
- ✅ Automatic token refresh on 401 errors
- ✅ Fallback to static token
- ✅ Test command added
- ✅ Comprehensive documentation
- ✅ Updated BillingService
- ✅ Updated BillingApiController

---

**Last Updated**: March 14, 2026  
**Version**: 1.0.0  
**Author**: SafariChat Development Team
