# Billing Notification System Implementation

## Overview
The notification system proactively alerts account owners when subscription limits are approached or exceeded. It provides both real-time SMS/WhatsApp notifications and dashboard visual warnings.

---

## Components

### 1. **AccountNotificationService** (`app/Services/AccountNotificationService.php`)
Handles SMS/WhatsApp notifications via the unified notification API.

#### Methods:
- `notifyContactLimitReached()` - Contact limit reached
- `notifyLowCredits()` - AI credits below 20%
- `notifyCreditsDepletion()` - AI credits depleted (0%)
- `notifyTrialExpiringSoon()` - Trial expires in ≤3 days
- `notifyTrialExpired()` - Trial has ended
- `notifySubscriptionInactive()` - Subscription status inactive/cancelled
- `notifyBookingLimitReached()` - Monthly booking limit exceeded

#### Notification Format:
Messages are sent via WhatsApp using the WaSenderService:
- **Icon prefix**: 🚨 (critical), ⚠️ (warning), ⏰ (time-based)
- **Structured content**: Alert type, details, statistics, action link
- **Priority**: High for all limit notifications
- **Metadata**: Includes user_id, notification_type, timestamp

#### Usage Example:
```php
$notificationService = app(\App\Services\AccountNotificationService::class);
$notificationService->notifyContactLimitReached($user, $phoneNumber, $limitCheck);
```

---

### 2. **BillingAlertService** (`app/Services/BillingAlertService.php`)
Generates dashboard alert data for visual warnings.

#### Methods:
- `getActiveAlerts($userId)` - Returns array of all active alerts
- `getBillingSummary($userId)` - Returns subscription statistics for dashboard widget

#### Alert Severity Levels:
- **critical** - Immediate action required (red)
- **warning** - Attention needed (yellow)
- **info** - Informational (blue)

#### Alert Structure:
```php
[
    'severity' => 'critical|warning|info',
    'type' => 'unique_identifier',
    'title' => 'Short title',
    'message' => 'Detailed message',
    'action' => [
        'text' => 'Button text',
        'url' => 'Action URL'
    ],
    'icon' => '🚨',
    'stats' => [
        'current' => 8,
        'max' => 10,
        'percentage' => 80
    ]
]
```

#### Alert Triggers:
| Alert Type | Trigger Condition | Severity |
|-----------|------------------|----------|
| Subscription Inactive | status = 'inactive' or 'cancelled' | critical |
| Trial Expired | trial_ends_at < now | critical |
| Trial Expiring | trial_ends_at ≤ 3 days | warning |
| Credits Depleted | ai_credits_balance = 0 | critical |
| Credits Critical | ai_credits_balance ≤ 10% | critical |
| Credits Low | ai_credits_balance ≤ 20% | warning |
| Contact Limit Reached | current_contacts >= max_contacts | critical |
| Contact Limit Warning | current_contacts >= 90% | warning |
| Contact Usage Info | current_contacts >= 80% | info |
| Calendar Limit | current_calendars >= max_calendars | warning |

---

### 3. **BillingService Updates** (`app/Services/BillingService.php`)

#### New Method: `checkAndNotifyLowCredits()`
Automatically called after every `deductCredits()` operation.

**Notification Thresholds:**
- **0% (Depleted)**: Notify max once per 6 hours
- **10% (Critical)**: Notify max once per 24 hours
- **20% (Warning)**: Notify max once per 2 days

**Cache Keys** (prevent spam):
- `credit_notification_depleted_{userId}`
- `credit_notification_critical_{userId}`
- `credit_notification_warning_{userId}`

#### Modified Method: `deductCredits()`
```php
public static function deductCredits($user, int $credits, string $reason = null): bool
{
    $billingAccount = self::getBillingAccountForUser($user);
    // ... existing code ...
    $result = $billingAccount->deductCredits($credits, $reason);
    
    // NEW: Check and notify if credits are low
    if ($result) {
        $userId = is_numeric($user) ? $user : $user->id;
        self::checkAndNotifyLowCredits($userId);
    }
    
    return $result;
}
```

---

### 4. **AiWhatsAppService Updates** (`app/Services/AiWhatsAppService.php`)

#### Modified Method: `notifyOwnerAboutContactLimit()`
Now sends SMS/WhatsApp notification in addition to logging:

```php
private function notifyOwnerAboutContactLimit($userId, $phone, $limitCheck)
{
    // ... existing logging code ...
    
    // NEW: Send SMS/WhatsApp notification
    $notificationService = app(\App\Services\AccountNotificationService::class);
    $notificationService->notifyContactLimitReached($user, $phone, $limitCheck);
}
```

---

### 5. **Dashboard Alerts Component** (`resources/views/components/billing-alerts.blade.php`)

#### Features:
- **Alert Banners**: Color-coded based on severity
- **Progress Bars**: Visual representation of usage percentage
- **Action Buttons**: Direct links to upgrade/settings
- **Dismissible**: Users can dismiss non-critical alerts
- **Icons**: Emoji icons for quick visual identification

#### Severity Styling:
- **Critical**: Red background (`alert-danger`), 4px red left border
- **Warning**: Yellow background (`alert-warning`), 4px yellow left border
- **Info**: Blue background (`alert-info`), 4px blue left border

#### Billing Summary Widget:
- **AI Credits**: Progress bar with color coding (green > 20%, yellow 10-20%, red < 10%)
- **Contacts**: Progress bar (green < 80%, yellow 80-95%, red ≥ 95%)
- **Calendars**: Current/max display
- **Trial Countdown**: Human-readable expiration time
- **Plan Badge**: Current subscription plan

---

### 6. **Layout Integration** (`resources/views/layouts/app.blade.php`)

Added billing alerts section above main content:

```blade
@auth
@php
    $billingAlertService = app(\App\Services\BillingAlertService::class);
    $billingAlerts = $billingAlertService->getActiveAlerts(auth()->id());
@endphp
@include('components.billing-alerts', ['billingAlerts' => $billingAlerts])
@endauth

@yield('content')
```

**Performance Consideration**: Alerts are generated on page load. For high-traffic applications, consider caching alerts for 1-5 minutes.

---

## Notification Flow

### Example: Contact Limit Reached

1. **User attempts to add contact** via WhatsApp message
2. `AiWhatsAppService::processWhatsappEvent()` checks `BillingService::canAddContact()`
3. If limit exceeded:
   ```
   a. Contact is NOT stored in database
   b. Log warning with user details
   c. AccountNotificationService::notifyContactLimitReached() sends WhatsApp message
   d. WaSenderService sends message to owner's phone number
   ```
4. **Next page load**:
   ```
   a. BillingAlertService::getActiveAlerts() generates alert array
   b. Dashboard displays red banner: "Contact Limit Reached (8/10)"
   c. Progress bar shows 100% (red)
   d. "Upgrade Plan" button links to settings
   ```

### Example: AI Credits Low

1. **AI processes message** in `OpenAiService`
2. `BillingService::deductCredits()` subtracts credits
3. `BillingService::checkAndNotifyLowCredits()` runs:
   ```
   a. Calculate remaining percentage
   b. If ≤ 20% and cache key doesn't exist:
      - Send SMS/WhatsApp via AccountNotificationService
      - Cache notification key for 2 days
   ```
4. **Dashboard** shows yellow/red alert banner

---

## Testing Scenarios

### 1. Test Contact Limit Notification
```php
// Manually trigger notification
$user = \App\Models\User::find(1);
$limitCheck = ['current' => 10, 'max' => 10, 'plan' => 'starter'];
$notificationService = app(\App\Services\AccountNotificationService::class);
$notificationService->notifyContactLimitReached($user, '+255700000000', $limitCheck);
```

### 2. Test Low Credits Dashboard Alert
```php
// Set credits to 10% of limit
$billingAccount = \App\Models\BillingAccount::where('user_id', 1)->first();
$planLimit = config('safarichat_billing.plans.starter.ai_credits'); // 69000
$billingAccount->ai_credits_balance = $planLimit * 0.10; // 6900 (10%)
$billingAccount->save();

// Refresh dashboard - should show yellow warning
```

### 3. Test Trial Expiration Alert
```php
// Set trial to expire in 2 days
$subscription = \App\Models\Subscription::where('user_id', 1)->first();
$subscription->trial_ends_at = now()->addDays(2);
$subscription->save();

// Dashboard should show "Trial expires in 2 days" warning
```

---

## Notification Rate Limiting

**Purpose**: Prevent notification spam when limits are repeatedly hit.

**Implementation**: Laravel Cache with TTL (Time To Live)

| Notification Type | Cache Duration | Max Frequency |
|------------------|----------------|---------------|
| Credits Depleted (0%) | 6 hours | 4/day |
| Credits Critical (≤10%) | 24 hours | 1/day |
| Credits Warning (≤20%) | 2 days | 1/2 days |
| Contact Limit | No cache | Every occurrence |
| Trial Expiring | No cache | Every page load |

**Clearing Cache** (if needed for testing):
```php
\Cache::forget('credit_notification_depleted_1');
\Cache::forget('credit_notification_critical_1');
\Cache::forget('credit_notification_warning_1');
```

---

## Configuration

### Settings in `config/safarichat_billing.php`

```php
'plans' => [
    'trial' => [
        'max_contacts' => 5,
        'ai_credits' => 1000,
        'booking_calendars' => 0,
    ],
    'starter' => [
        'max_contacts' => 50,
        'ai_credits' => 69000,
        'booking_calendars' => 1,
    ],
    // ... other plans
]
```

### Notification API Settings in `config/notifications.php` (or equivalent)

```php
'unified_api' => [
    'base_url' => env('NOTIFICATION_API_URL', 'https://notifications.shulesoft.africa/api'),
    'bearer_token' => env('NOTIFICATION_API_TOKEN'),
]
```

---

## Dashboard Display Logic

### Alert Display Rules

1. **Multiple Alerts**: All active alerts display in stack (most critical first)
2. **Dismissible**: All alerts can be dismissed via close button
3. **Color Coding**:
   - Critical: Red border-left, red button
   - Warning: Yellow border-left, blue button
   - Info: Blue border-left, blue button

4. **Progress Bars** (when stats available):
   - Show percentage visually
   - Color changes based on threshold (green/yellow/red)
   - Small text shows "X remaining" or "X/Y used"

### Widget Display (Sidebar/Dashboard Card)

The billing summary widget shows:
- **Plan Type**: Badge with plan name (TRIAL, STARTER, PRO, PREMIUM)
- **Subscription Status**: Active/Inactive
- **AI Credits**: Progress bar (if not unlimited)
- **Contacts**: Progress bar (if not unlimited)
- **Calendars**: Counter or "Unlimited" badge
- **Trial Expiration**: Countdown if applicable

---

## Future Enhancements

### Planned Features:
1. **Email Notifications**: Complement SMS with email alerts
2. **Configurable Preferences**: Let users choose notification channels
3. **Notification History**: Log all notifications in database
4. **Webhook Integrations**: Slack/Discord for team notifications
5. **Predictive Alerts**: "At current usage, limit will be reached in X days"
6. **Multi-language Support**: Translate notifications based on user locale
7. **Notification Dashboard**: Dedicated page to view all past alerts

---

## Troubleshooting

### Notifications Not Sending

**Check 1: User has phone number**
```php
$user = \App\Models\User::find(1);
dd($user->phone_number ?? $user->phone);
```

**Check 2: WaSenderService configuration**
```php
$config = config('notifications.unified_api');
dd($config); // Should have base_url and bearer_token
```

**Check 3: Check logs**
```bash
tail -f storage/logs/laravel.log | grep -i "notification"
```

### Dashboard Alerts Not Showing

**Check 1: User is authenticated**
```php
// In blade template
@auth
    User ID: {{ auth()->id() }}
@endauth
```

**Check 2: Billing account exists**
```php
$billingAccount = \App\Models\BillingAccount::where('user_id', auth()->id())->first();
dd($billingAccount);
```

**Check 3: Alerts are generated**
```php
$billingAlertService = app(\App\Services\BillingAlertService::class);
$alerts = $billingAlertService->getActiveAlerts(auth()->id());
dd($alerts); // Should return array
```

### Credits Not Triggering Notification

**Check 1: Credits are actually low**
```php
$billingAccount = \App\Models\BillingAccount::where('user_id', 1)->first();
$plan = $billingAccount->subscription->plan_type;
$limit = config("safarichat_billing.plans.{$plan}.ai_credits");
$percentage = ($billingAccount->ai_credits_balance / $limit) * 100;
echo "Credits: {$billingAccount->ai_credits_balance} ({$percentage}%)";
```

**Check 2: Cache isn't blocking**
```php
// Clear all notification cache
\Cache::forget('credit_notification_depleted_1');
\Cache::forget('credit_notification_critical_1');
\Cache::forget('credit_notification_warning_1');
```

---

## Code References

### Key Files Modified/Created:
1. `app/Services/AccountNotificationService.php` - NEW
2. `app/Services/BillingAlertService.php` - NEW
3. `app/Services/BillingService.php` - MODIFIED (added checkAndNotifyLowCredits)
4. `app/Services/AiWhatsAppService.php` - MODIFIED (added SMS notification)
5. `resources/views/components/billing-alerts.blade.php` - NEW
6. `resources/views/layouts/app.blade.php` - MODIFIED (added alerts section)

### Dependencies:
- `WaSenderService` - WhatsApp/SMS messaging
- `BillingService` - Limit checking
- `BillingAccount` model - Subscription data
- `Subscription` model - Plan and status
- Laravel Cache - Notification throttling
- Laravel Blade - Dashboard rendering

---

## Summary

The notification system provides comprehensive, proactive alerts for:
- ✅ Contact limits (SMS + dashboard)
- ✅ AI credit depletion (SMS + dashboard)
- ✅ Trial expiration (SMS + dashboard)
- ✅ Subscription status (dashboard)
- ✅ Booking calendar limits (dashboard)

**User Benefits**:
- Never lose customers due to silent limit blocks
- Immediate awareness of critical issues
- Clear upgrade path with one-click buttons
- Visual progress tracking

**Business Benefits**:
- Increase subscription upgrades
- Reduce churn from frustrated users
- Improve customer satisfaction
- Proactive account management
