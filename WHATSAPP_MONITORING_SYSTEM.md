# WhatsApp Instance Connection Monitoring System

## Overview
This system automatically monitors WhatsApp instance connection status every 15 minutes and displays warnings to users when their instances are disconnected.

## Components

### 1. Console Command
**File:** `app/Console/Commands/CheckWhatsappInstancesCommand.php`

**Purpose:** Checks all WhatsApp instances' connection status via WaSender API

**Schedule:** Runs every 15 minutes

**Features:**
- Fetches all WhatsApp instances from the database
- Checks connection status using `UnifiedNotificationService::getSessionStatus()`
- Updates database with current status
- Logs status changes (connection/disconnection events)
- Clears user cache for immediate UI updates
- Provides detailed console output and logging

**Usage:**
```bash
# Run manually
php artisan whatsapp:check-instances

# Runs automatically every 15 minutes via cron
```

**Log File:** `storage/logs/whatsapp-instances-check.log`

### 2. Scheduled Task
**File:** `app/Console/Kernel.php`

**Configuration:**
```php
$schedule->command('whatsapp:check-instances')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/whatsapp-instances-check.log'))
```

### 3. UI Warning Banner
**File:** `resources/views/layouts/whatsapp-connection-warning.blade.php`

**Purpose:** Displays a warning banner when WhatsApp instances are disconnected

**Features:**
- Shows only to authenticated users
- Cached for 5 minutes to reduce database queries
- Displays count of disconnected instances
- Provides "Reconnect Now" button linking to AI Agents page
- Dismissible alert
- Sticky positioning at top of page

**Design:**
- Orange warning color (#ff9800)
- FontAwesome exclamation triangle icon
- Bootstrap alert component
- Responsive design

### 4. Layout Integration
**File:** `resources/views/layouts/app.blade.php`

The warning banner is included after billing alerts to ensure visibility across all pages.

## How It Works

### Step 1: Cron Job (Every 15 Minutes)
1. Command fetches all WhatsApp instances from database
2. For each instance, calls `UnifiedNotificationService::getSessionStatus()`
3. Checks if status is `connected`, `ready`, or `open` (connected states)
4. Updates database with current status
5. Logs any status changes
6. Clears user's cache to trigger UI update

### Step 2: UI Display
1. When user loads any page, the warning banner component is included
2. Component checks cache for disconnected instances
3. If not cached, queries database for disconnected instances
4. Caches result for 5 minutes
5. Displays warning if any disconnected instances found

### Step 3: User Action
1. User sees the warning banner
2. Clicks "Reconnect Now" button
3. Redirected to `/service/ai-agents` page
4. Can scan QR code to reconnect WhatsApp instance

## Database Fields Updated

**Table:** `whatsapp_instances`

**Fields:**
- `connect_status` - Current connection status (connected/disconnected/failed/closed)
- `status` - Instance status
- `last_active_at` - Timestamp of last status check
- `disconnected_at` - Timestamp when disconnection was detected

## Connection Status Values

### Connected States
- `connected` - Instance is actively connected
- `ready` - Instance is ready to send messages
- `open` - Session is open

### Disconnected States
- `disconnected` - Instance is not connected
- `failed` - Connection attempt failed
- `closed` - Session was closed

## Cache Strategy

**Cache Key:** `whatsapp_disconnected_{user_id}`

**TTL:** 300 seconds (5 minutes)

**Benefits:**
- Reduces database queries on every page load
- Improves page load performance
- Still updates within reasonable time frame

**Cache Clearing:**
- Automatically cleared by cron job when status changes
- Ensures users see updates within 15 minutes + cache TTL

## Logging

### Success Logs
```
WhatsApp instance check completed
Successfully checked: X instances
Connected: Y instances
Disconnected: Z instances
```

### Connection Change Logs
```
WhatsApp instance reconnected
- instance_id: 123
- user_id: 45
- previous_status: disconnected
- new_status: connected
```

### Disconnection Logs
```
WhatsApp instance disconnected
- instance_id: 123
- user_id: 45
- previous_status: connected
- new_status: disconnected
```

## Testing

### Manual Test
```bash
# Run the command manually
php artisan whatsapp:check-instances

# Check the log file
tail -f storage/logs/whatsapp-instances-check.log
```

### Force Disconnection for Testing
```php
// In tinker or a test script
$instance = \App\Models\WhatsappInstance::find(1);
$instance->update(['connect_status' => 'disconnected', 'status' => 'disconnected']);

// Clear cache
Cache::forget('whatsapp_disconnected_' . $instance->user_id);

// Refresh page to see warning banner
```

## Monitoring

### Check Cron Execution
```bash
# View cron logs
tail -f storage/logs/laravel.log | grep "WhatsApp instances check"

# View specific check log
tail -f storage/logs/whatsapp-instances-check.log
```

### Check Database Status
```sql
SELECT id, user_id, instance_id, connect_status, status, last_active_at, disconnected_at
FROM whatsapp_instances
WHERE connect_status IN ('disconnected', 'failed', 'closed');
```

## Future Enhancements

1. **Email Notifications**: Send email alerts when instances disconnect
2. **SMS Notifications**: Send SMS for critical disconnections
3. **Reconnection Attempts**: Automatically attempt to reconnect
4. **Health Dashboard**: Create a dashboard showing all instance statuses
5. **Alerts History**: Track disconnection history and patterns
6. **Auto-reconnect**: Implement automatic QR code regeneration

## Troubleshooting

### Warning Not Showing
1. Check if user has disconnected instances in database
2. Clear cache: `Cache::forget('whatsapp_disconnected_' . $userId)`
3. Check if user is authenticated
4. Verify layout includes the warning component

### Cron Not Running
1. Check if cron is configured on server
2. Verify command is registered in Kernel.php
3. Check log file for errors
4. Run manually to test: `php artisan whatsapp:check-instances`

### API Errors
1. Check `UnifiedNotificationService` configuration
2. Verify WaSender API credentials
3. Check network connectivity
4. Review error logs in `storage/logs/laravel.log`

## Configuration

### Cron Schedule
To change the check frequency, edit `app/Console/Kernel.php`:

```php
// Every 15 minutes (default)
->everyFifteenMinutes()

// Every 30 minutes
->everyThirtyMinutes()

// Every hour
->hourly()
```

### Cache Duration
To change cache duration, edit `resources/views/layouts/whatsapp-connection-warning.blade.php`:

```php
// Default: 300 seconds (5 minutes)
Cache::remember($cacheKey, 300, function () { ... });

// Change to 10 minutes
Cache::remember($cacheKey, 600, function () { ... });
```

## Security

- Only authenticated users see warnings for their own instances
- Cache is user-specific to prevent data leakage
- Database queries are scoped to current user
- Sensitive data is not exposed in logs
