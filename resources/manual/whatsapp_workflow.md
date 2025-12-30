# WhatsApp Instance Onboarding - Step-by-Step Technical Guide

This document provides a detailed technical walkthrough of how WhatsApp instance onboarding works in SafariChat, including form submission, controller processing, and QR code generation/display. This guide is designed to help WaSender developers debug QR code scanning issues.

## QR Code Generation Algorithm

This document outlines the step-by-step algorithm for WhatsApp QR code generation in SafariChat. Each step shows the core process and relevant code snippets.

### QR Code Generation Flow:
1. **Phone number submitted** - User submits phone via AJAX form
2. **Create WaSender session** - Generate session using phone number and API key
3. **Call QR generation API** - Request QR code from WaSender service
4. **Return QR as JSON** - Send base64 QR code back via AJAX response
5. **Display QR in HTML** - Show QR code image in frontend

---

## Step 1: Phone Number Submission

**Algorithm**: User submits phone number through modal form via AJAX

**Frontend Code**:
```javascript
$('#enable_whatsapp_integration').mousedown(function () {
    var phone = $('#whatsapp_phone_number').val();
    
    // Send phone number to controller
    $.ajax({
        type: 'POST',
        url: "<?= url('payment/createReference') ?>",
        data: {
            phone: phone, 
            type: 'whatsapp'
        },
        success: function (data) {
            $('#whatsapp_content').html(data);
        }
    });
});
```

**HTML Form**:
```html
<input value="{{ Auth::user()->phone }}" 
       type="text" 
       class="form-control" 
       id="whatsapp_phone_number" />
<a href="javascript:void(0);" 
   id="enable_whatsapp_integration">Enable WhatsApp</a>
```

---

## Step 2: Create WaSender Session

**Algorithm**: Controller receives phone number and creates WaSender session using API key

**Controller Code** (`WaSenderController.php`):
```php
public function createSession(Request $request)
{
    $phoneNumber = $this->cleanPhoneNumber($request->phone_number);
    $user = Auth::user();
    
    // Create session via Unified Service with API key
    $unifiedService = app(\App\Services\UnifiedNotificationService::class);
    $sessionResult = $unifiedService->createSession([
        'name' => "Session_" . time(),
        'phone_number' => $phoneNumber,
        'api_key' => config('wasender.api_key')
    ]);
    
    if (!$sessionResult['success']) {
        throw new \Exception('Failed to create session: ' . $sessionResult['message']);
    }
    
    $sessionId = $sessionResult['session_id'];
    
    // Save session to database
    $instance = WhatsappInstance::create([
        'user_id' => $user->id,
        'session_id' => $sessionId,
        'phone_number' => $phoneNumber,
        'status' => 'pending'
    ]);
    
    return $sessionId;
}
```

---

## Step 3: Call QR Generation API

**Algorithm**: Use session ID to call WaSender API for QR code generation

**QR Generation Code**:
```php
private function generateQRCode($sessionId)
{
    try {
        // Call unified service for QR generation
        $unifiedService = app(\App\Services\UnifiedNotificationService::class);
        $result = $unifiedService->getQRCode($sessionId);
        
        if ($result['success'] ?? false) {
            // Return base64 encoded QR image
            return $result['qr_code'];
        } else {
            return 'QR_GENERATION_FAILED';
        }
    } catch (\Exception $e) {
        Log::error('QR generation failed', [
            'session_id' => $sessionId,
            'error' => $e->getMessage()
        ]);
        return 'QR_GENERATION_FAILED';
    }
}
```

**UnifiedNotificationService API Call**:
```php
public function getQRCode($sessionId)
{
    $response = Http::timeout(30)->get($this->baseUrl . "/sessions/{$sessionId}/qrcode", [
        'api_key' => $this->apiKey
    ]);
    
    return $response->json();
}
```

---

## Step 4: Return QR Code as JSON

**Algorithm**: Controller returns QR code as JSON response via AJAX

**Controller Response Code**:
```php
public function createSessionLegacy(Request $request)
{
    // ... session creation code ...
    
    // Generate QR code
    $qrCode = $this->generateQRCode($sessionId);
    
    // Return JSON response with QR code
    return response()->json([
        'success' => true,
        'message' => 'WhatsApp session created successfully. Please scan QR code.',
        'qr_code' => $qrCode, // Base64 encoded image
        'session_id' => $sessionId,
        'instance_id' => $instance->id,
        'status' => 'pending_qr_scan'
    ]);
}
```

**JSON Response Format**:
```json
{
    "success": true,
    "message": "WhatsApp session created successfully. Please scan QR code.",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANS...",
    "session_id": "sess_123456789",
    "instance_id": 42,
    "status": "pending_qr_scan"
}
```

---

## Step 5: Display QR Code in HTML

**Algorithm**: Frontend receives JSON response and displays QR code image

**AJAX Success Handler**:
```javascript
// Handle successful QR generation response
$.ajax({
    url: '/api/wasender/sessions',
    method: 'POST',
    data: { phone_number: phone },
    success: function(response) {
        if (response.success && response.qr_code) {
            // Display QR code in HTML
            displayQRCode(response.qr_code, response.session_id);
        } else {
            showError('Failed to generate QR code');
        }
    }
});
```

**QR Display Function**:
```javascript
function displayQRCode(qrCodeBase64, sessionId) {
    const qrContainer = document.getElementById('qrCodeContainer');
    
    qrContainer.innerHTML = `
        <div class="qr-code-display">
            <img src="${qrCodeBase64}" 
                 alt="WhatsApp QR Code" 
                 style="max-width: 300px; border: 2px solid #25D366;">
            <p class="mt-2">Scan this QR code with WhatsApp to connect</p>
            <small>Session ID: ${sessionId}</small>
        </div>
    `;
}
```

**HTML Container**:
```html
<div id="qrCodeContainer" class="text-center">
    <p>Generating QR code...</p>
</div>
```

---

## Complete Flow Summary

```
User Form → AJAX → Controller → WaSender Session → QR API → JSON Response → HTML Display
    ↓          ↓         ↓            ↓              ↓          ↓             ↓
  Phone    POST    createSession   sessionId    qr_code   Base64 Image   <img>
```

### Key API Endpoints:
- **POST** `/payment/createReference` - Initial form submission
- **POST** `/api/wasender/sessions` - Create session & generate QR
- **GET** `/api/wasender/sessions/{id}/qrcode` - Direct QR retrieval

### Critical Debug Points:
1. **Phone validation** - Check if phone number format is correct
2. **Session creation** - Verify session ID is generated
3. **API response** - Check if QR code is returned as base64
4. **HTML rendering** - Ensure QR image displays properly

### Common QR Issues:
- **Empty QR response** - Check WaSender API connectivity
- **Invalid base64** - Verify image format in response
- **Session timeout** - Check if session expires before QR scan
