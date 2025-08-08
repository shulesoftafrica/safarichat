# Monthly Subscription Payment System

## Overview
This is a simple monthly subscription system for the SafariChat application where users pay TSH 50,000 per month via LIPA NAMBA.

## Key Features

### 1. Monthly Subscription Model
- **Fixed Fee**: TSH 50,000 per month
- **Payment Method**: LIPA NAMBA (TANQR)
- **New User Trial**: 3 days free trial for new registrations

### 2. Payment Rules
- **Exact Payment**: Users must pay at least TSH 50,000 to activate service
- **Underpayment**: Payments less than TSH 50,000 will not activate the service
- **Overpayment**: Excess amounts are credited to future months
- **Multiple Months**: Users can pay for multiple months at once

### 3. Trial Period
- **Duration**: 3 days for new users
- **Automatic**: Starts when user registers
- **Access**: Full access to all features during trial
- **Expiration**: After 3 days, payment is required to continue

## How It Works

### For Users:
1. **New Registration**: Get 3 days free trial
2. **Trial Expiration**: Payment modal appears when trial ends
3. **Payment Process**:
   - Send TSH 50,000 to LIPA NAMBA: 000-111-222
   - Enter reference number received from LIPA NAMBA
   - Enter amount paid
   - Click "Verify Payment"
4. **Subscription Active**: Immediate access for paid months

### For Administrators:
1. **Replace LIPA NAMBA Number**: Update the actual number in:
   - `resources/views/layouts/checkpayment.blade.php`
   - `resources/views/payment/subscription_status.blade.php`

2. **Monitor Payments**: View payments in `admin_payments` table

## Database Structure

### admin_payments Table
```sql
- id (primary key)
- user_id (foreign key to users)
- amount (decimal - amount paid)
- transaction_id (string - LIPA NAMBA reference)
- method (string - payment method)
- date (datetime - payment date)
- subscription_start (datetime - when subscription starts)
- subscription_end (datetime - when subscription ends)
- months_covered (integer - number of months paid for)
- excess_amount (decimal - overpayment amount)
- created_at, updated_at, deleted_at
```

## Configuration

### Trial Period
Set in `config/app.php`:
```php
'TRIAL_DAYS' => 3,
```

### Monthly Fee
Set in `Payment` controller:
```php
$monthlyFee = 50000; // TSH 50,000
```

## API Endpoints

### POST /payment/verify
Verifies payment using reference number and amount.

**Request:**
```json
{
    "reference_number": "REF123456789",
    "amount_paid": 50000
}
```

**Response Success:**
```json
{
    "success": true,
    "message": "Payment verified successfully! Your subscription is now active for 1 month(s) until 08 Sep 2025.",
    "subscription_end": "2025-09-08 12:00:00",
    "months_covered": 1
}
```

**Response Error:**
```json
{
    "success": false,
    "message": "This reference number has already been used."
}
```

### GET /payment/subscription
Shows user's subscription status and payment history.

## Helper Functions

### getPackage()
Returns active subscription for current user or null if none.

### is_trial()
Returns 1 if user is in trial period, 0 if trial expired or has paid subscription.

## Payment Verification Logic

1. **Validate Input**: Check reference number and amount
2. **Check Duplicates**: Ensure reference not used before
3. **Validate Amount**: Must be at least TSH 50,000
4. **Calculate Months**: Floor(amount / 50000)
5. **Calculate Dates**: Start from now or current subscription end
6. **Store Payment**: Save to database with all details
7. **Return Response**: Success/error with details

## Example Payment Scenarios

### Scenario 1: First Payment
- User pays: TSH 50,000
- Result: 1 month subscription (Aug 8 - Sep 8)

### Scenario 2: Overpayment
- User pays: TSH 125,000
- Result: 2 months subscription (Aug 8 - Oct 8), TSH 25,000 excess

### Scenario 3: Underpayment
- User pays: TSH 30,000
- Result: Payment rejected, no subscription activated

### Scenario 4: Renewal
- Current subscription ends: Sep 8
- User pays: TSH 50,000 on Sep 5
- Result: Extended to Oct 8

## Security Features

1. **Unique References**: Each LIPA NAMBA reference can only be used once
2. **Input Validation**: Server-side validation of all inputs
3. **Logging**: All payment attempts logged for audit
4. **Error Handling**: Graceful error handling with user-friendly messages

## Administration

### View Payments
```sql
SELECT u.name, ap.amount, ap.transaction_id, ap.subscription_end 
FROM admin_payments ap 
JOIN users u ON ap.user_id = u.id 
ORDER BY ap.created_at DESC;
```

### Check Active Subscriptions
```sql
SELECT u.name, ap.subscription_end 
FROM admin_payments ap 
JOIN users u ON ap.user_id = u.id 
WHERE ap.subscription_end >= NOW() 
ORDER BY ap.subscription_end DESC;
```

### Monthly Revenue
```sql
SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
       COUNT(*) as payments, 
       SUM(amount) as total_revenue 
FROM admin_payments 
GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
ORDER BY month DESC;
```

## Setup Instructions

1. **Update LIPA NAMBA Number**: Replace "000-111-222" with actual number
2. **Run Migrations**: `php artisan migrate`
3. **Set Trial Days**: Update `config/app.php`
4. **Test Payment Flow**: Use test reference numbers
5. **Monitor Logs**: Check Laravel logs for payment activities

## Future Enhancements

1. **Automatic Payment Verification**: Integration with LIPA NAMBA API
2. **Email Notifications**: Send confirmation emails
3. **SMS Alerts**: Payment confirmations via SMS
4. **Multiple Payment Methods**: Add mobile money options
5. **Annual Discounts**: Yearly subscription options
6. **Grace Period**: Allow few days after expiration
7. **Payment Reminders**: Auto-reminders before expiration
