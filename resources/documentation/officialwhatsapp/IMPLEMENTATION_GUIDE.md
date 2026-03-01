# Meta WhatsApp Integration - Implementation Guide

## Overview
This implementation provides a clean, production-ready integration with Meta's Official WhatsApp Business API, with automatic fallback to WaSender for reliability.

## Files Created

### 1. Configuration
**File**: `config/meta_whatsapp.php`

Contains all Meta WhatsApp settings including:
- API credentials (phone number ID, access token, business account ID)
- Webhook configuration
- Message type priority (which messages use Meta vs WaSender)
- Pre-approved template names
- Rate limiting settings
- Error handling and fallback configuration

### 2. Service Class
**File**: `app/Services/MetaWhatsAppService.php`

Main service class providing methods:
- `sendOtpTemplate()` - Send OTP verification codes
- `sendTextMessage()` - Send plain text messages
- `sendImage()` - Send images with captions
- `sendDocument()` - Send PDF/documents
- `sendLocation()` - Share locations
- `sendTemplate()` - Send pre-approved templates
- `markAsRead()` - Mark messages as read
- `sendTypingIndicator()` - Show typing status

**Features**:
- Automatic fallback to WaSender on failures
- Comprehensive error logging
- Phone number formatting
- Health status checking

### 3. Updated System Service
**File**: `app/Services/SystemWhatsAppService.php` (Updated)

Modified to use Meta WhatsApp for critical messages:
- OTP verification now uses Meta first
- Password reset uses Meta first
- Falls back to WaSender if Meta is not configured or fails

### 4. Documentation
**File**: `resources/documentation/officialwhatsapp/meta.md`

Comprehensive documentation including:
- API endpoints and payloads
- Implementation examples
- Error handling strategies
- Webhook integration guide
- Testing checklist
- Migration plan

## Setup Instructions

### Step 1: Environment Variables
Add these to your `.env` file:

```env
META_WHATSAPP_PHONE_NUMBER_ID=1083367458184137
META_WHATSAPP_BUSINESS_ACCOUNT_ID=981178058418111
META_WHATSAPP_ACCESS_TOKEN=EAAGxxxxxxxxxxxxx
META_WHATSAPP_API_VERSION=v24.0
META_WHATSAPP_VERIFY_TOKEN=your_secure_random_token
```

### Step 2: Create Message Templates in Meta Business Manager

1. Go to https://business.facebook.com
2. Navigate to WhatsApp Manager → Message Templates
3. Create an OTP template with name `otp`:

```
Template Name: otp
Category: Authentication
Language: English

Body:
Your verification code is {{1}}

Button Type: URL
Button Text: Verify
URL: https://yourapp.com/verify?code={{1}}
```

4. Submit for approval (usually approved within 24 hours)

### Step 3: Test the Integration

```php
// In tinker or test controller
use App\Services\MetaWhatsAppService;

$service = app(MetaWhatsAppService::class);

// Test health status
$health = $service->getHealthStatus();
dd($health);

// Test OTP sending
$response = $service->sendOtpTemplate('+255714825469', '123456');
dd($response);
```

### Step 4: Monitor Logs

Check `storage/logs/laravel.log` for Meta WhatsApp operations:
- Look for "Meta WhatsApp API Request" - outgoing requests
- Look for "Meta WhatsApp API Response" - API responses
- Look for "OTP sent via Meta WhatsApp" - successful deliveries
- Look for "fallback_to_wasender" - fallback scenarios

## Usage Examples

### Sending OTP (Automatic - via SystemWhatsAppService)

```php
use App\Services\SystemWhatsAppService;

$systemService = app(SystemWhatsAppService::class);
$result = $systemService->sendOtpVerification('+255714825469', '123456', 'John Doe');

// This will:
// 1. Try Meta WhatsApp first
// 2. Fallback to WaSender if Meta fails
// 3. Return true/false for success
```

### Sending Custom Text Message (Direct MetaWhatsAppService)

```php
use App\Services\MetaWhatsAppService;

$metaService = app(MetaWhatsAppService::class);
$response = $metaService->sendTextMessage(
    '+255714825469',
    'Hello! Your order #123 has been shipped.',
    true  // Enable URL preview
);

if ($response['success']) {
    $messageId = $response['data']['messages'][0]['id'];
    echo "Message sent with ID: $messageId";
} else {
    echo "Error: " . $response['error'];
}
```

### Sending Image

```php
$response = $metaService->sendImage(
    '+255714825469',
    'https://example.com/product-image.jpg',
    'Check out this amazing product!'
);
```

### Sending Document (Invoice)

```php
$response = $metaService->sendDocument(
    '+255714825469',
    'https://example.com/invoices/INV-2024-001.pdf',
    'Invoice-January-2024.pdf',
    'Your invoice for January 2024'
);
```

### Sending Location

```php
$response = $metaService->sendLocation(
    '+255714825469',
    -6.7924,  // Dar es Salaam latitude
    39.2083,  // Dar es Salaam longitude
    'SafariChat Office',
    '123 Uhuru Street, Dar es Salaam'
);
```

## Architecture Flow

```
User Action (e.g., Register)
    ↓
SystemWhatsAppService::sendOtpVerification()
    ↓
Check: Is Meta WhatsApp configured?
    ├─ Yes → MetaWhatsAppService::sendOtpTemplate()
    │         ├─ Success → Log & Return
    │         ├─ API Error → Check fallback enabled
    │         │               ├─ Yes → WaSenderService::sendMessage()
    │         │               └─ No → Return error
    │         └─ Exception → Fallback to legacy method ↓
    │
    └─ No → Legacy WaSender method (via SendWhatsAppMessage job)
              ↓
         Queue message to WaSender
              ↓
         Return success
```

## Error Handling

### Automatic Fallback Scenarios

Meta WhatsApp will automatically fallback to WaSender when:
1. Access token is invalid or expired
2. Rate limit is hit (133016 error code)
3. Phone number is invalid
4. Template not approved
5. Network timeout
6. Any 4xx/5xx HTTP errors

### Manual Error Checking

```php
$response = $metaService->sendOtpTemplate($phone, $otp);

if (!$response['success']) {
    // Check if it was a fallback success
    if ($response['via'] === 'wasender' && $response['fallback']) {
        Log::warning('Meta failed, WaSender succeeded', [
            'meta_error' => $response['meta_error']
        ]);
    } else {
        // Both failed
        Log::error('Message sending completely failed', [
            'meta_error' => $response['meta_error'] ?? null,
            'wasender_error' => $response['wasender_error'] ?? null
        ]);
    }
}
```

## Configuration Options

### Disable Fallback (Test Mode)

In `config/meta_whatsapp.php`:
```php
'settings' => [
    'enable_fallback' => false,  // Disable fallback to see Meta errors
],
```

### Change Message Type Priority

In `config/meta_whatsapp.php`:
```php
'message_type_priority' => [
    'welcome_message' => 'wasender',  // Use WaSender instead of Meta
],
```

### Adjust Rate Limiting

```php
'rate_limits' => [
    'messages_per_second' => 1000,  // For Business tier
    'enabled' => true,
],
```

## Monitoring & Analytics

### Check Service Health

```php
$metaService = app(MetaWhatsAppService::class);
$health = $metaService->getHealthStatus();

/*
Returns:
[
    'configured' => true,
    'access_token' => true,
    'phone_number_id' => true,
    'fallback_enabled' => true,
    'api_version' => 'v24.0'
]
*/
```

### System Stats

```php
$systemService = app(SystemWhatsAppService::class);
$stats = $systemService->getSystemStats(30);  // Last 30 days

/*
Returns:
[
    'instance_id' => 1,
    'total_messages' => 1500,
    'successful_messages' => 1450,
    'failed_messages' => 50,
    'message_types' => [
        'otp_verification' => ['total_sent' => 800, 'successful' => 795],
        'payment_reminder' => ['total_sent' => 400, 'successful' => 380],
        ...
    ]
]
*/
```

## Troubleshooting

### Issue: "Access token not configured"

**Solution**: Add `META_WHATSAPP_ACCESS_TOKEN` to `.env` file

### Issue: "Template not found"

**Solution**: Create and get approval for templates in Meta Business Manager

### Issue: Messages always falling back to WaSender

**Check**:
1. Is access token valid? Test in Graph API Explorer
2. Is phone number ID correct?
3. Check logs for specific error codes
4. Verify templates are approved

### Issue: "Phone number not registered"

**Solution**: The recipient must have WhatsApp installed and the number must be registered with WhatsApp

## Best Practices

1. **Always use Meta for OTP** - More reliable, faster delivery
2. **Use WaSender for bulk messages** - Cost effective
3. **Monitor logs daily** - Catch issues early
4. **Keep templates updated** - Meta policy changes
5. **Test in development mode** - Use Meta test numbers
6. **Set up alerts** - Monitor fallback rates > 10%
7. **Cache template IDs** - Reduce API calls
8. **Validate phone numbers** - Before sending

## Security Considerations

1. **Never log access tokens** - Already implemented in service
2. **Rotate tokens regularly** - Every 90 days recommended
3. **Use webhook verify token** - Prevent unauthorized webhooks
4. **Validate all phone numbers** - Prevent spam
5. **Rate limit your app** - Prevent abuse
6. **Encrypt sensitive data** - OTP codes in logs should be masked

## Cost Optimization

### Meta WhatsApp Pricing (Approximate)
- Authentication messages (OTP): $0.005 per message
- Service messages: $0.01 per message
- Marketing messages: $0.03 per message

### Recommendations
1. Use Meta for OTP and critical transactional messages
2. Use WaSender for bulk/marketing messages
3. Cache message templates
4. Batch notifications when possible
5. Use templates for repeated messages (no charge per message after approval)

## Next Steps

1. ✅ Configuration complete
2. ✅ Service implementation complete
3. ⏳ Create templates in Meta Business Manager
4. ⏳ Configure webhook endpoint
5. ⏳ Test with real phone numbers
6. ⏳ Monitor and optimize

## Support Resources

- **Meta Developer Docs**: https://developers.facebook.com/docs/whatsapp/cloud-api
- **Business Manager**: https://business.facebook.com
- **Status Page**: https://status.fb.com
- **Support**: https://developers.facebook.com/support/

---

**Version**: 1.0  
**Last Updated**: February 27, 2026  
**Maintained By**: SafariChat Development Team
