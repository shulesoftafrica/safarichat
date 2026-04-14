# 🔒 SSL Certificate Problem Fix

## Problem
On production Linux server, getting error:
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

This happens when PHP/cURL cannot verify the SSL certificate of `api.safaribank.africa` because your server lacks proper CA (Certificate Authority) certificates.

## ⚠️ Quick Test (NOT for Production Use)

To verify this is the SSL issue, temporarily disable SSL verification:

1. **Edit `.env` on production server:**
```bash
BILLING_VERIFY_SSL=false
```

2. **Clear config cache:**
```bash
php artisan config:cache
```

3. **Test authentication:**
```bash
php artisan shulesoft:auth-status --test
```

If it works now, you've confirmed it's an SSL certificate issue. **Immediately proceed to proper fix below!**

---

## ✅ Proper Production Fix

### Option 1: Install CA Certificates (Recommended)

Most Linux distributions come with CA certificates, but they may be missing or outdated.

**For Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install ca-certificates
sudo update-ca-certificates
```

**For CentOS/RHEL:**
```bash
sudo yum install ca-certificates
sudo update-ca-trust
```

**For Alpine Linux (Docker):**
```bash
apk add --no-cache ca-certificates
```

After installation, **re-enable SSL verification** in `.env`:
```bash
BILLING_VERIFY_SSL=true
```

Then clear cache and test:
```bash
php artisan config:cache
php artisan shulesoft:auth-status --test
```

---

### Option 2: Download Mozilla CA Bundle

If Option 1 doesn't work, manually download the CA certificate bundle:

1. **Download the certificate bundle:**
```bash
cd /var/www/html/safarichat  # or your app directory
mkdir -p storage/certs
cd storage/certs
wget https://curl.se/ca/cacert.pem
chmod 644 cacert.pem
```

2. **Configure `.env` to use the bundle:**
```bash
BILLING_VERIFY_SSL=true
BILLING_CACERT_PATH=/var/www/html/safarichat/storage/certs/cacert.pem
```

3. **Clear cache and test:**
```bash
php artisan config:cache
php artisan shulesoft:auth-status --test
```

---

### Option 3: Update PHP's OpenSSL Configuration

If PHP is using outdated OpenSSL settings:

1. **Find your `php.ini`:**
```bash
php --ini
```

2. **Edit `php.ini` and set:**
```ini
openssl.cafile=/etc/ssl/certs/ca-certificates.crt
openssl.capath=/etc/ssl/certs/
```

3. **Restart PHP-FPM or web server:**
```bash
sudo systemctl restart php8.1-fpm  # Adjust version as needed
sudo systemctl restart nginx       # or apache2
```

---

## 🧪 Verification Steps

After applying any fix:

1. **Check SSL verification is enabled:**
```bash
grep BILLING_VERIFY_SSL .env
# Should show: BILLING_VERIFY_SSL=true (or not present)
```

2. **Clear all caches:**
```bash
php artisan config:cache
php artisan cache:clear
```

3. **Test authentication:**
```bash
php artisan shulesoft:auth-status --test
```

4. **Check logs for SSL errors:**
```bash
tail -f storage/logs/laravel.log | grep -i "ssl\|curl"
```

---

## 🔍 Troubleshooting

### Still Getting SSL Error?

**Check PHP cURL SSL info:**
```php
<?php
$ch = curl_init('https://api.safaribank.africa/api/v1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$result = curl_exec($ch);
echo curl_error($ch);
curl_close($ch);
```

Save as `test_ssl.php` and run: `php test_ssl.php`

**Check OpenSSL version:**
```bash
php -r "echo OPENSSL_VERSION_TEXT;"
openssl version
```

If OpenSSL is very old (< 1.0.2), consider upgrading PHP/OpenSSL.

---

## 📝 What Changed in Your Code

The following files now support SSL configuration:

### 1. `config/services.php`
Added SSL settings under `shulesoft_billing`:
```php
'verify_ssl' => env('BILLING_VERIFY_SSL', true),
'cacert_path' => env('BILLING_CACERT_PATH'),
```

### 2. `app/Services/ShulesoftAuthService.php`
Added `getHttpClient()` method that respects SSL settings:
```php
private static function getHttpClient()
{
    $http = Http::timeout(...);
    
    if (!$verifySSL) {
        $http = $http->withOptions(['verify' => false]);
    } elseif ($cacertPath) {
        $http = $http->withOptions(['verify' => $cacertPath]);
    }
    
    return $http;
}
```

### 3. `app/Services/BillingService.php`
Same `getHttpClient()` method and all HTTP requests now use it.

---

## 🎯 Summary

| Environment | Issue | Solution |
|------------|-------|----------|
| **Local (XAMPP)** | Invalid credentials | Get correct OAuth credentials from Shulesoft |
| **Production** | SSL certificate error | Install CA certificates OR provide cacert.pem path |

**Current State:**
- ✅ Backoff system prevents log spam
- ✅ Application works via static token fallback
- ✅ SSL configuration infrastructure in place
- ⏳ Awaiting proper SSL certificates on production server

---

## 🚀 Next Steps

1. **Short-term:** Use static token fallback (already working)
2. **Medium-term:** Install CA certificates on production server
3. **Long-term:** Get proper OAuth credentials from Shulesoft team

---

**Questions?**
- Check logs: `storage/logs/laravel.log`
- Run diagnostic: `php artisan shulesoft:auth-status`
- Test auth: `php artisan shulesoft:auth-status --test`
