# WhatsApp Instance Onboarding - Step-by-Step Technical Guide

This document provides a detailed technical walkthrough of how WhatsApp instance onboarding works in SafariChat, including form submission, controller processing, and QR code generation/display. This guide is designed to help WaSender developers debug QR code scanning issues.

## Overview of the Process

The WhatsApp instance onboarding process consists of:
1. **User Form Submission** - User enters phone number in modal form
2. **Payment Reference Creation** - System creates payment reference 
3. **Instance Creation** - Creates WhatsApp instance record in database
4. **Session Creation** - Creates WaSender session via API
5. **QR Code Generation** - Generates QR code for WhatsApp connection
6. **Frontend Display** - Shows QR code to user for scanning

---

## Step 1: Frontend Form Design

### HTML Form Structure
Located in: `resources/views/message/channel.blade.php`

```html
<!-- WhatsApp Integration Modal -->
<div class="modal fade" id="whatsapp" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('enable/upgrade_whatsapp_integration') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card-body" id="whatsapp_content">
                    <div class="pricingTable1 text-center">
                        <!-- Pricing Information -->
                        <div class="p-3 m-2">
                            <h3 class="amount amount-border d-inline-block">
                                TSH <?= number_format($whatsapp_price) ?>
                            </h3>
                            <small class="font-12 text-muted">{{ __('per_one_sender') }}</small>
                        </div>
                        
                        <!-- Features List -->
                        <ul class="list-unstyled pricing-content-2 text-left py-3">
                            <li><i class="fa fa-check text-success"></i> Send and receive messages via WhatsApp</li>
                            <li><i class="fa fa-check text-success"></i> Send media files (photos, audio, videos)</li>
                            <li><i class="fa fa-check text-success"></i> Global messaging capabilities</li>
                            <li><i class="fa fa-check text-success"></i> Unlimited messages per day</li>
                            
                            <!-- PHONE NUMBER INPUT FIELD -->
                            <li>{{ __('enter_your_whatsapp_number_below_to_proceed') }}
                                <input 
                                    value="{{ Auth::user()->phone }}" 
                                    type="text" 
                                    style="border: 1px solid #00a65a !important" 
                                    placeholder="{{ __('enter_your_whatsapp_number_here') }}" 
                                    class="form-control" 
                                    id="whatsapp_phone_number" 
                                />
                            </li>
                        </ul>
                        
                        <!-- Submit Button -->
                        <div class="pricing_footer">
                            <a href="javascript:void(0);" 
                               class="btn btn-success btn-block" 
                               id="enable_whatsapp_integration" 
                               role="button">
                                {{ __('enable_whatsapp_integration') }} <span>{{ __('now!') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Key Form Elements:
- **Input Field**: `#whatsapp_phone_number` - Pre-populated with user's phone
- **Submit Button**: `#enable_whatsapp_integration` - Triggers AJAX request
- **Content Container**: `#whatsapp_content` - Replaced with payment/QR interface

---

## Step 2: JavaScript Form Handler

### AJAX Request Code
Located in: `resources/views/message/channel.blade.php` (bottom of file)

```javascript
$('#enable_whatsapp_integration').mousedown(function () {
    var phone = $('#whatsapp_phone_number').val();
    
    // Validation
    if (phone == '') {
        $('#whatsapp_phone_number')
            .css('border', '2px solid red')
            .after("<b class='text-red'>{{ __('please_enter_your_whatsapp_number_to_proceed') }}</b>");
    } else {
        // Create payment reference via AJAX
        $.ajax({
            type: 'POST',
            url: "<?= url('payment/createReference') ?>",
            data: {
                phone: phone, 
                type: 'whatsapp'
            },
            dataType: "html",
            beforeSend: function () {
                $('#whatsapp_content').html('loading.....');
            },
            success: function (data) {
                // Replace modal content with payment interface
                $('#whatsapp_content').html(data);
            }
        });
    }
});
```

### JavaScript Flow:
1. **Validation** - Checks if phone number is provided
2. **Loading State** - Shows loading message
3. **AJAX Request** - Posts to payment reference creation endpoint
4. **Content Replacement** - Replaces modal content with payment interface

---

## Step 3: Payment Reference Creation

### Controller Method
Located in: `app/Http/Controllers/PaymentController.php` (assumed)

```php
public function createReference(Request $request)
{
    $phone = $request->phone;
    $type = $request->type; // 'whatsapp'
    $user = Auth::user();
    
    // Validate phone number
    $cleanPhone = $this->cleanPhoneNumber($phone);
    
    // Create payment reference record
    $paymentRef = PaymentReference::create([
        'user_id' => $user->id,
        'phone_number' => $cleanPhone,
        'service_type' => 'whatsapp',
        'amount' => $whatsappPrice,
        'status' => 'pending',
        'reference_id' => $this->generateReferenceId()
    ]);
    
    // Return payment interface HTML
    return view('payment.whatsapp_payment', [
        'reference' => $paymentRef,
        'phone' => $cleanPhone
    ]);
}
```

---

## Step 4: WhatsApp Instance Creation

### Instance Creation Method
Located in: `app/Http/Controllers/WhatsappInstanceController.php`

```php
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'schema_name' => 'required|string|max:255|regex:/^[a-z][a-z0-9_]*$/',
        'display_name' => 'nullable|string|max:255',
        'purpose' => 'nullable|string|in:sales,support,marketing,personal,other',
        'description' => 'nullable|string|max:1000'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = Auth::user();
    $schemaName = $request->schema_name;

    // Check for unique schema name
    $existingInstance = WhatsappInstance::where('user_id', $user->id)
        ->where('schema_name', $schemaName)
        ->first();

    if ($existingInstance) {
        return response()->json([
            'success' => false,
            'message' => 'Schema name already exists'
        ], 422);
    }

    try {
        // Create new WhatsApp instance
        $instance = WhatsappInstance::create([
            'user_id' => $user->id,
            'schema_name' => $schemaName,
            'display_name' => $request->display_name,
            'purpose' => $request->purpose,
            'description' => $request->description,
            'phone_number' => $request->phone_number, // From payment flow
            'is_primary' => false,
            'is_active' => false,
            'status' => 'pending'
        ]);

        // Trigger session creation
        $sessionResult = $this->createWaSenderSession($instance);
        
        return response()->json([
            'success' => true,
            'message' => 'WhatsApp instance created successfully',
            'instance' => $instance,
            'session_result' => $sessionResult
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating instance: ' . $e->getMessage()
        ], 500);
    }
}
```

---

## Step 5: WaSender Session Creation & QR Generation

### Main Session Creation Method
Located in: `app/Http/Controllers/WaSenderController.php`

```php
public function createSessionLegacy(Request $request)
{
    try {
        // Step 1: Validate phone number format
        $request->validate([
            'phone_number' => 'required|string',
            'instance_name' => 'nullable|string|max:50',
            'auth_method' => 'nullable|string|in:qr'
        ]);

        $user = Auth::user();
        $phoneNumber = $this->cleanPhoneNumber($request->phone_number);
        
        // Step 2: Validate phone number format
        if (!$this->isValidPhoneNumber($phoneNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format'
            ], 422);
        }

        $authMethod = $request->auth_method ?: 'qr'; // Default to QR code
        $instanceName = $request->instance_name ?: "Session_" . time();

        Log::info('Creating WhatsApp session', [
            'user_id' => $user->id,
            'phone_number' => $phoneNumber,
            'auth_method' => $authMethod
        ]);

        // Step 3: Create session via Unified Service
        $unifiedService = app(\App\Services\UnifiedNotificationService::class);
        $sessionResult = $unifiedService->createSession([
            'name' => $instanceName,
            'phone_number' => $phoneNumber
        ]);

        if (!$sessionResult['success']) {
            throw new \Exception('Failed to create session: ' . $sessionResult['message']);
        }

        $sessionId = $sessionResult['session_id'];
        
        Log::info('Session created successfully', [
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber
        ]);

        // Step 4: Save instance to database
        $instance = WhatsappInstance::updateOrCreate(
            [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber
            ],
            [
                'session_id' => $sessionId,
                'instance_name' => $instanceName,
                'status' => 'pending',
                'connect_status' => 'connecting',
                'auth_method' => $authMethod,
                'created_via_api' => true
            ]
        );

        // Step 5: Generate QR code for connection
        if ($authMethod === 'qr') {
            $qrCode = $this->generateQRCode($sessionId);
            
            Log::info('QR Code generation result', [
                'session_id' => $sessionId,
                'qr_code_type' => is_string($qrCode) ? 'string' : gettype($qrCode),
                'qr_code_preview' => is_string($qrCode) ? substr($qrCode, 0, 100) . '...' : $qrCode
            ]);
            
            // Check if already connected
            if ($qrCode === 'ALREADY_CONNECTED') {
                $instance->update([
                    'status' => 'connected',
                    'connect_status' => 'ready',
                    'connected_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp session is already connected',
                    'status' => 'connected',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id
                ]);
            }
            
            // Handle QR generation failure
            if ($qrCode === 'QR_GENERATION_FAILED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id
                ]);
            }

            // Success - return QR code
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session created successfully. Please scan QR code.',
                'qr_code' => $qrCode,
                'session_id' => $sessionId,
                'instance_id' => $instance->id,
                'status' => 'pending_qr_scan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp session created successfully',
            'session_id' => $sessionId,
            'instance_id' => $instance->id
        ]);

    } catch (\Exception $e) {
        Log::error('Error creating WhatsApp session', [
            'user_id' => $user->id ?? null,
            'phone_number' => $phoneNumber ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error creating session: ' . $e->getMessage()
        ], 500);
    }
}
```

### QR Code Generation Method

```php
/**
 * Generate QR code for session connection via unified notification service
 */
private function generateQRCode($sessionId)
{
    try {
        Log::info('Generating QR code via unified service', ['session_id' => $sessionId]);
        
        $unifiedService = app(\App\Services\UnifiedNotificationService::class);
        $result = $unifiedService->getQRCode($sessionId);
        
        if ($result['success'] ?? false) {
            Log::info('QR code retrieved from unified service', [
                'session_id' => $sessionId,
                'has_qr_code' => !empty($result['qr_code'])
            ]);
            return $result['qr_code']; // Returns base64 encoded image
        } else {
            Log::warning('Unified service QR generation failed, using fallback', [
                'session_id' => $sessionId,
                'error' => $result['message'] ?? 'Unknown error'
            ]);
            return $this->generatePlaceholderQR($sessionId);
        }
    } catch (\Exception $e) {
        Log::error('Error generating QR via unified service', [
            'session_id' => $sessionId,
            'error' => $e->getMessage()
        ]);
        return $this->generatePlaceholderQR($sessionId);
    }
}
```

---

## Step 6: Frontend QR Code Display

### QR Code Display JavaScript
Located in: `resources/views/unified-notification-test.blade.php` (example implementation)

```javascript
async function connectAndGetQR(sessionId) {
    try {
        // Connect session
        await NotificationAPI.connectSession(sessionId);
        
        // Get QR code
        const qrResult = await NotificationAPI.getQRCode(sessionId);
        
        if (qrResult.success && qrResult.data.qr_code) {
            // Display QR code image
            document.getElementById('qrCodeContainer').innerHTML = `
                <img src="${qrResult.data.qr_code}" alt="QR Code" style="max-width: 300px;">
                <p class="mt-2">Scan this QR code with WhatsApp to connect</p>
            `;
        } else {
            document.getElementById('qrCodeContainer').innerHTML = '<p>Failed to generate QR code</p>';
        }
    } catch (error) {
        showNotification('Error generating QR code: ' + error.message, 'error');
    }
}
```

### QR Code HTML Container

```html
<div id="qrCodeContainer" class="qr-code text-center">
    <p>Click "Connect Session" to generate QR code</p>
</div>
```

---

## Step 7: API Endpoints Used

### Main API Routes
Located in: `routes/api.php`

```php
// WaSender Routes
Route::prefix('wasender')->group(function() {
    // Session Management
    Route::post('/sessions', 'WaSenderController@createSession');
    Route::get('/sessions/{id}', 'WaSenderController@getSession');
    Route::get('/sessions/{id}/qrcode', 'WaSenderController@getQRCode');
    Route::post('/sessions/{id}/connect', 'WaSenderController@connectSession');
    Route::delete('/sessions/{id}', 'WaSenderController@deleteSession');
    
    // Instance Management
    Route::get('/instances', 'WhatsappInstanceController@index');
    Route::post('/instances', 'WhatsappInstanceController@store');
    Route::get('/instances/{id}', 'WhatsappInstanceController@show');
});

// Payment Routes
Route::post('/payment/createReference', 'PaymentController@createReference');
```

---

## Debugging QR Code Issues

### Common Issues & Solutions:

1. **QR Code Not Generating**
   - Check `logs/laravel.log` for UnifiedNotificationService errors
   - Verify WaSender API endpoint is accessible
   - Check session creation was successful

2. **QR Code Not Displaying**
   - Verify base64 image format in response
   - Check browser console for JavaScript errors
   - Ensure QR code container element exists

3. **QR Code Scan Not Working**
   - Verify session_id is correctly passed to QR generation
   - Check if session is in correct state (pending/connecting)
   - Test QR code with online QR readers

### Debug Logging Points:

```php
// Add these logs in WaSenderController
Log::info('Session creation initiated', ['phone' => $phoneNumber]);
Log::info('Unified service session result', $sessionResult);
Log::info('QR generation started', ['session_id' => $sessionId]);
Log::info('QR code response', ['has_qr' => !empty($qrCode), 'type' => gettype($qrCode)]);
```

### Testing QR Code Generation:

```bash
# Test session creation directly
curl -X POST http://your-domain.com/api/wasender/sessions \
  -H "Content-Type: application/json" \
  -d '{"name":"test_session","phone_number":"+1234567890"}'

# Test QR code retrieval
curl -X GET http://your-domain.com/api/wasender/sessions/{session_id}/qrcode
```

---

## Summary

The WhatsApp onboarding process follows this flow:
1. **Modal Form** → Phone number input
2. **AJAX Request** → Payment reference creation  
3. **Instance Creation** → Database record created
4. **Session API** → WaSender session established
5. **QR Generation** → Base64 QR code returned
6. **Frontend Display** → QR code shown to user

The most critical debugging points are:
- Session creation success in UnifiedNotificationService
- QR code generation response format
- Frontend JavaScript QR display implementation

This technical documentation should help WaSender developers identify where QR code scanning issues occur in the process.
