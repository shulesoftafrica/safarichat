# Performance Fix Recommendations

## Issue
Maximum execution time of 30 seconds exceeded on first page load, caused by Sentry initialization timeout and database query performance.

## Immediate Fixes

### 1. Disable or Configure Sentry Properly

**Option A: Disable Sentry (Quick Fix)**
If you're not actively using Sentry error tracking, disable it:

```bash
# In your .env file
SENTRY_LARAVEL_DSN=
# Or remove the line entirely
```

**Option B: Configure Sentry with Timeout**
Add to `config/sentry.php`:

```php
'http_proxy' => null,
'http_timeout' => 2, // Add this - timeout after 2 seconds
'http_connect_timeout' => 1, // Add this - connection timeout
```

### 2. Optimize View Composer Database Query

**Problem:** Complex query runs on every page load in `AppServiceProvider.php`

**Solution 1: Add Caching** (Recommended)
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Cache;

public function boot() {
    View::composer('layouts.nav', function ($view) {
        if (Auth::check() && Auth::user()->business) {
            $cacheKey = 'pending_appointments_' . Auth::user()->business->id;
            
            $pendingAppointmentsCount = Cache::remember($cacheKey, 60, function () {
                return Appointment::whereHas('lead', function ($query) {
                    $query->where('business_id', Auth::user()->business->id);
                })
                ->where('status', 'pending')
                ->where('scheduled_at', '>=', now())
                ->count();
            });
            
            $view->with('pendingAppointmentsCount', $pendingAppointmentsCount);
        }
    });
}
```

**Solution 2: Add Database Index**
```sql
-- Run this in your PostgreSQL database
CREATE INDEX IF NOT EXISTS idx_appointments_status_scheduled 
ON appointments(status, scheduled_at) 
WHERE status = 'pending';

CREATE INDEX IF NOT EXISTS idx_leads_business_id 
ON leads(business_id);
```

**Solution 3: Optimize Query** (Alternative)
Replace the `whereHas` with a join for better performance:
```php
$pendingAppointmentsCount = Appointment::join('leads', 'appointments.lead_id', '=', 'leads.id')
    ->where('leads.business_id', Auth::user()->business->id)
    ->where('appointments.status', 'pending')
    ->where('appointments.scheduled_at', '>=', now())
    ->count();
```

### 3. Increase PHP Execution Time (Temporary)

While fixing the root causes, increase the timeout in `php.ini`:
```ini
max_execution_time = 60
```

Or in your code (app/Http/Kernel.php middleware):
```php
set_time_limit(60);
```

### 4. Enable OpCache (Production Performance)

In your `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  # Set to 0 in production
```

## Implementation Priority

1. **Immediate:** Disable Sentry or add timeout configuration
2. **Short-term:** Add caching to View Composer (60 second cache)
3. **Medium-term:** Add database indexes
4. **Long-term:** Consider moving View Composer to View Components or lazy loading

## Testing

After implementing fixes, test with:

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Test first load performance
curl -w "@curl-format.txt" -o /dev/null -s http://your-app-url
```

Create `curl-format.txt`:
```
time_total: %{time_total}s
```

## Monitoring

Add to your error handler to catch future timeouts:
```php
// app/Exceptions/Handler.php
if ($exception instanceof \Symfony\Component\ErrorHandler\Error\FatalError) {
    Log::warning('Fatal Error Occurred', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
}
```
