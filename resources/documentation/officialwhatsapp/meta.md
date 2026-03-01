# Official WhatsApp Business API Integration

## Overview
This document defines the implementation requirements for integrating Meta's Official WhatsApp Business API with SafariChat. This integration works alongside the WaSender integration to provide reliable message delivery through Meta's infrastructure.

## Architecture

### Service Class
**Location**: `app/Services/MetaWhatsAppService.php`

This service provides a clean, reusable interface for all Meta WhatsApp Business API operations.

### Configuration
**Location**: `config/meta_whatsapp.php`

Environment variables:
```env
META_WHATSAPP_PHONE_NUMBER_ID=1083367458184137
META_WHATSAPP_BUSINESS_ACCOUNT_ID=981178058418111
META_WHATSAPP_ACCESS_TOKEN=EAAGxxxxx
META_WHATSAPP_API_VERSION=v24.0
META_WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
```

### Fallback Strategy
The system will use Meta WhatsApp as the primary channel for critical messages (OTP, payment reminders). If Meta API fails or returns errors, the system will automatically fallback to WaSender.

**Priority Order**:
1. **Primary**: Meta WhatsApp (for OTP, transactional messages)
2. **Fallback**: WaSender (if Meta fails or for bulk/marketing messages)

---

## Implementation Methods

### 1. OTP Verification

**Use Case**: Send OTP codes for user authentication (login, registration, verification)

**Priority**: Always use Meta WhatsApp first. Only fallback to WaSender if Meta returns errors.

**Service Method**:
```php
MetaWhatsAppService::sendOtpTemplate(string $phoneNumber, string $otpCode, ?string $otpCode2 = null): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Headers**:
```json
{
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json"
}
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "to": "+255714825469",
  "type": "template",
  "template": {
    "name": "otp",
    "language": {
      "code": "en"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          {
            "type": "text",
            "text": "23323"
          }
        ]
      },
      {
        "type": "button",
        "sub_type": "url",
        "index": "0",
        "parameters": [
          {
            "type": "text",
            "text": "23323"
          }
        ]
      }
    ]
  }
}
```

**Implementation Notes**:
- Template name `otp` must be pre-approved in Meta Business Manager
- Phone number must include country code (+255...)
- Button parameter is for one-tap autofill on Android/iOS
- OTP codes must be numeric (4-8 digits recommended)

---

### 2. Send Text Message

**Use Case**: Send regular text messages (notifications, reminders, customer service)

**Service Method**:
```php
MetaWhatsAppService::sendTextMessage(string $phoneNumber, string $message, bool $previewUrl = false): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "+255714825469",
  "type": "text",
  "text": {
    "preview_url": false,
    "body": "Hello! This is your message content."
  }
}
```

**Implementation Notes**:
- `preview_url`: Set to `true` to generate link previews for URLs in message
- Maximum message length: 4096 characters
- Supports emojis and Unicode characters
- URLs are automatically detected and clickable

---

### 3. Send Image

**Use Case**: Send images with optional caption (product photos, receipts, infographics)

**Service Method**:
```php
MetaWhatsAppService::sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "to": "+255714825469",
  "type": "image",
  "image": {
    "link": "https://example.com/image.jpg",
    "caption": "Product Image with Description"
  }
}
```

**Implementation Notes**:
- Supported formats: JPEG, PNG
- Maximum file size: 5MB
- Image URL must be publicly accessible or use Media Upload API
- Caption is optional, max 1024 characters

---

### 4. Send Document

**Use Case**: Send PDF files, invoices, contracts, reports

**Service Method**:
```php
MetaWhatsAppService::sendDocument(string $phoneNumber, string $documentUrl, string $filename, ?string $caption = null): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "to": "+255714825469",
  "type": "document",
  "document": {
    "link": "https://example.com/invoice.pdf",
    "caption": "Your invoice for January 2024",
    "filename": "invoice_jan_2024.pdf"
  }
}
```

**Implementation Notes**:
- Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT
- Maximum file size: 100MB
- Filename is required and shown to recipient
- Caption is optional

---

### 5. Send Location

**Use Case**: Share business location, delivery address, meeting point

**Service Method**:
```php
MetaWhatsAppService::sendLocation(string $phoneNumber, float $latitude, float $longitude, string $name, string $address): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "+255714825469",
  "type": "location",
  "location": {
    "latitude": -6.7924,
    "longitude": 39.2083,
    "name": "SafariChat Office",
    "address": "123 Uhuru Street, Dar es Salaam"
  }
}
```

**Implementation Notes**:
- Latitude: -90 to 90
- Longitude: -180 to 180
- Name and address are displayed in the map pin
- Recipients can open in Google Maps/WhatsApp Maps

---

### 6. Send Template Message

**Use Case**: Send pre-approved marketing/notification templates

**Service Method**:
```php
MetaWhatsAppService::sendTemplate(string $phoneNumber, string $templateName, string $languageCode, array $components = []): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Request Payload**:
```json
{
  "messaging_product": "whatsapp",
  "to": "+255714825469",
  "type": "template",
  "template": {
    "name": "payment_reminder",
    "language": {
      "code": "en"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          {
            "type": "text",
            "text": "John Doe"
          },
          {
            "type": "currency",
            "currency": {
              "fallback_value": "TZS 50,000",
              "code": "TZS",
              "amount_1000": 50000000
            }
          }
        ]
      },
      {
        "type": "button",
        "sub_type": "quick_reply",
        "index": "0",
        "parameters": [
          {
            "type": "payload",
            "payload": "CONFIRM_PAYMENT"
          }
        ]
      }
    ]
  }
}
```

**Implementation Notes**:
- Templates must be pre-approved in Meta Business Manager
- Language codes: en, sw, ar, etc.
- Components include: header, body, footer, buttons
- Use for marketing messages outside 24-hour window

---

### 7. Mark Message as Read & Show Typing Indicator

**Use Case**: Improve user experience by showing read receipts and typing status

**Service Methods**:
```php
MetaWhatsAppService::markAsRead(string $messageId): array
MetaWhatsAppService::sendTypingIndicator(string $phoneNumber, string $status = 'typing'): array
```

**Meta API Endpoint**:
```
POST https://graph.facebook.com/v24.0/{phone_number_id}/messages
```

**Mark as Read Payload**:
```json
{
  "messaging_product": "whatsapp",
  "status": "read",
  "message_id": "wamid.HBgLMTY1MDUwNzY1OTAVAgARGBI5QTNDQTVCM0Q0Q0Q2RTY3RTcA"
}
```

**Typing Indicator Payload** (Custom Extension):
```json
{
  "messaging_product": "whatsapp",
  "to": "+255714825469",
  "status": "typing"
}
```

**Implementation Notes**:
- Mark as read requires the incoming message ID from webhook
- Typing indicator is not officially documented but may work
- Use for chatbot/customer service scenarios

---

## Error Handling & Fallback Logic

### Error Detection
The service will detect Meta API failures:
- HTTP status codes: 4xx, 5xx
- Response errors: rate limiting, invalid token, phone number issues
- Network timeouts

### Automatic Fallback
```php
// Pseudocode
try {
    $response = MetaWhatsAppService::sendOtpTemplate($phone, $otp);
    if (!$response['success']) {
        // Fallback to WaSender
        WaSenderService::sendMessage($phone, "Your OTP: $otp");
    }
} catch (Exception $e) {
    // Fallback to WaSender
    WaSenderService::sendMessage($phone, "Your OTP: $otp");
}
```

### Logging
All Meta WhatsApp operations will be logged:
- Success: Message ID, timestamp, recipient
- Failure: Error code, error message, fallback status
- Log location: `storage/logs/meta_whatsapp.log`

---

## Webhook Integration

### Incoming Messages
**Webhook URL**: `/api/webhooks/meta-whatsapp`

**Verification Request** (GET):
```php
// Meta sends verification challenge
if (request('hub_mode') === 'subscribe' && 
    request('hub_verify_token') === config('meta_whatsapp.verify_token')) {
    return response(request('hub_challenge'));
}
```

**Message Received** (POST):
```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "15551234567",
          "phone_number_id": "PHONE_NUMBER_ID"
        },
        "messages": [{
          "from": "255714825469",
          "id": "wamid.ID",
          "timestamp": "1234567890",
          "text": {
            "body": "Hello"
          },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}
```

---

## Testing & Validation

### Test Checklist
- [ ] OTP delivery (< 5 seconds)
- [ ] Text message with URL preview
- [ ] Image with caption
- [ ] PDF document delivery
- [ ] Location sharing
- [ ] Template message with parameters
- [ ] Read receipts
- [ ] Fallback to WaSender on failure
- [ ] Webhook message reception
- [ ] Error logging

### Test Phone Numbers
Meta provides test numbers in development mode. Use `+1 555-0100` through `+1 555-0199` for testing without sending real messages.

---

## Service Method Reference

```php
namespace App\Services;

class MetaWhatsAppService
{
    // OTP & Authentication
    public function sendOtpTemplate(string $phoneNumber, string $otpCode, ?string $otpCode2 = null): array;
    
    // Regular Messages
    public function sendTextMessage(string $phoneNumber, string $message, bool $previewUrl = false): array;
    public function sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null): array;
    public function sendDocument(string $phoneNumber, string $documentUrl, string $filename, ?string $caption = null): array;
    public function sendLocation(string $phoneNumber, float $latitude, float $longitude, string $name, string $address): array;
    
    // Template Messages
    public function sendTemplate(string $phoneNumber, string $templateName, string $languageCode, array $components = []): array;
    
    // Interaction
    public function markAsRead(string $messageId): array;
    public function sendTypingIndicator(string $phoneNumber, string $status = 'typing'): array;
    
    // Utility
    protected function formatPhoneNumber(string $phoneNumber): string;
    protected function makeApiRequest(string $endpoint, array $payload): array;
    protected function handleApiError(array $response): void;
    protected function logOperation(string $operation, array $data): void;
}
```

---

## Migration Plan

### Phase 1: Setup (Week 1)
1. Create Meta WhatsApp Business Account
2. Get Phone Number ID and Access Token
3. Configure environment variables
4. Create `MetaWhatsAppService` class
5. Create configuration file

### Phase 2: OTP Integration (Week 1-2)
1. Modify `SystemWhatsAppService` to use Meta for OTP
2. Implement fallback logic
3. Test OTP delivery
4. Monitor success rates

### Phase 3: Full Integration (Week 2-3)
1. Implement all message types
2. Setup webhook handling
3. Add read receipts
4. Implement template messages

### Phase 4: Production (Week 4)
1. Load testing
2. Monitor error rates
3. Tune fallback thresholds
4. Documentation update

---

## Support & Resources

- **Meta Developer Docs**: https://developers.facebook.com/docs/whatsapp/cloud-api
- **API Reference**: https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
- **Template Guidelines**: https://developers.facebook.com/docs/whatsapp/message-templates/guidelines
- **Business Manager**: https://business.facebook.com/

---

*Last Updated: February 27, 2026*
*Version: 1.0*