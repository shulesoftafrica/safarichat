# WhatsApp API Documentation

## Overview
This API replaces the legacy wasender service with a unified endpoint for sending all types of WhatsApp messages (text, audio, image, etc.).

**Base URL:** `https://notifcations.shulesoft.africa/api`

**Authentication:** All requests require Bearer token authentication
```
Authorization: Bearer LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn
```

---

## A. Sending WhatsApp Messages

### 1. Send Single WhatsApp Message

**Endpoint:** `POST /notifications/send`

#### Request Parameters

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `schema_name` | ✅ Yes | string | User UUID from users table |
| `channel` | ✅ Yes | string | Always "whatsapp" |
| `to` | ✅ Yes | string | Phone number with country code (e.g., +1234567890) |
| `message` | ✅ Yes | string | Message text content |
| `provider` | ❌ No | string | Default: "wasender" |
| `type` | ❌ No | string | Message type |
| `priority` | ❌ No | string | Options: low, normal, high, urgent |
| `scheduled_at` | ❌ No | datetime | Schedule send time (ISO 8601 format) |
| `template_id` | ❌ No | string | Template identifier |
| `template_data` | ❌ No | object | Template variables |
| `metadata` | ❌ No | object | Custom metadata |
| `webhook_url` | ❌ No | string | Callback URL for status updates |
| `tags` | ❌ No | array | Message categorization tags |
| `attachment` | ❌ No | string | Base64 encoded file content |
| `attachment_name` | ❌ No | string | File name with extension |
| `attachment_type` | ❌ No | string | MIME type (e.g., application/pdf) |

#### Example Request
```json
{
    "schema_name": "user-uuid-123",
    "channel": "whatsapp",
    "to": "+255714825469",
    "message": "Hello, this is a test message",
    "priority": "normal"
}
```

#### Success Response (200)
```json
{
    "success": true,
    "message_id": 15,
    "external_id": "a045e57f-f606-45a5-9d97-2e871bfae453",
    "status": "sent",
    "provider": "wasender",
    "data": {
        "id": 15,
        "channel": "whatsapp",
        "recipient": "+255714825469",
        "status": "sent",
        "provider": "wasender",
        "priority": "normal",
        "sent_at": "2025-12-05T05:38:45.000000Z",
        "external_id": "a045e57f-f606-45a5-9d97-2e871bfae453",
        "retry_count": null,
        "metadata": {
            "schema_name": "shulesoft",
            "wasender_api_key": "de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2",
            "sms_sender_name": null
        },
        "duration_ms": null,
        "created_at": "2025-12-05T05:38:45.000000Z",
        "updated_at": "2025-12-05T05:38:45.000000Z",
        "is_scheduled": false,
        "is_delivered": false,
        "is_failed": false,
        "delivery_status": {
            "code": "sent",
            "label": "Sent",
            "color": "green",
            "description": "Message has been sent to provider"
        },
        "formatted_duration": null
    }
}
```

#### Error Response (400/500)
```json
{
    "success": false,
    "error": "WaSender session not found or X-API-Key unavailable",
    "message": "No active WaSender session found for schema: client_tenant_demo",
    "message_id": 123
}
```

---

### 2. Send Bulk WhatsApp Messages

**Endpoint:** `POST /notifications/bulk/send`

#### Request Parameters
All single message parameters apply, plus:

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `rate_limit` | ❌ No | integer | Messages per minute (default: 60) |
| `batch_size` | ❌ No | integer | Messages per batch (default: 50) |
| `messages` | ✅ Yes | array | Array of message objects |

#### Example Request
```json
{
    "schema_name": "user-uuid-123",
    "channel": "whatsapp",
    "priority": "normal",
    "rate_limit": 60,
    "batch_size": 50,
    "messages": [
        {
            "to": "+1234567890",
            "message": "Hello John, special offer for you!",
            "metadata": { "customer_id": "cust_123" }
        },
        {
            "to": "+1987654321",
            "message": "Hi Jane, check our new products!",
            "metadata": { "customer_id": "cust_456" }
        }
    ]
}
```

#### Success Response (200)
```json
{
    "success": true,
    "batch_id": 456,
    "queued_messages": 2,
    "failed_messages": 0,
    "estimated_delivery_time": "2025-12-01T10:05:00Z",
    "rate_limit_info": {
        "messages_per_minute": 60,
        "batch_size": 50
    }
}
```

---

### 3. Get Message Status

**Endpoint:** `GET /notifications/{id}`

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "channel": "whatsapp",
        "recipient": "+1234567890",
        "message": "Your notification message",
        "status": "delivered",
        "sent_at": "2025-12-01T10:30:45Z",
        "delivered_at": "2025-12-01T10:31:02Z"
    }
}
```

---

### 4. List All Messages

**Endpoint:** `GET /notifications`

#### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `channel` | string | Always "whatsapp" |
| `status` | string | Filter: pending, sent, delivered, failed |
| `from` | datetime | Start date (Y-m-d H:i:s) |
| `to` | datetime | End date (Y-m-d H:i:s) |
| `recipient` | string | Search by phone number |
| `per_page` | integer | Results per page (max: 100) |

#### Success Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "channel": "whatsapp",
            "recipient": "+1234567890",
            "status": "delivered",
            "sent_at": "2025-12-01T10:30:45Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 1,
        "last_page": 1
    }
}
```

---

## B. WaSender Session Management

### 1. Create WhatsApp Session

**Endpoint:** `POST /wasender/sessions/create`

#### Request Body
```json
{
    "schema_name": "user-uuid-123",
    "name": "My Business WhatsApp",
    "phone_number": "+1234567890",
    "account_protection": true,
    "log_messages": true,
    "read_incoming_messages": false,
    "webhook_url": "https://webhook.example.com/wasender",
    "webhook_enabled": true,
    "webhook_events": ["messages.received", "session.status", "messages.update"]
}
```

#### Success Response (200)
```json
{
    "success": true,
    "message": "WhatsApp session created successfully",
    "data": {
        "id": 1,
        "wasender_session_id": "ws_abc123",
        "name": "My Business WhatsApp",
        "phone_number": "+1234567890",
        "status": "disconnected",
        "api_key": "wa_key_xyz789",
        "created_at": "2025-12-01T10:00:00Z"
    }
}
```

---

### 2. Connect Session & Get QR Code

**Endpoint:** `POST /wasender/sessions/{id}/connect`

#### Success Response (200)
```json
{
    "success": true,
    "message": "Session connect request successful",
    "data": {
        "session": {
            "id": 1,
            "status": "connecting"
        },
        "qr_code": "data:image/png;base64,iVBORw0KGgo...",
        "status": "connecting"
    }
}
```

**Usage:** Scan the QR code with WhatsApp mobile app to connect.

---

### 3. Check Session Status

**Endpoint:** `GET /wasender/sessions/{id}/status`

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "session": {
            "id": 1,
            "status": "connected"
        },
        "status": "connected"
    },
    "api_response": {
        "status": "connected",
        "device_info": {
            "battery": 85,
            "connected": true
        }
    }
}
```

**Statuses:** `disconnected`, `connecting`, `connected`, `failed`

---

### 4. Get All Sessions

**Endpoint:** `GET /wasender/sessions`

#### Success Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "schema_name": "user-uuid-123",
            "wasender_session_id": "ws_abc123",
            "name": "My Business WhatsApp",
            "phone_number": "+1234567890",
            "status": "connected",
            "created_at": "2025-12-01T10:00:00Z"
        }
    ]
}
```

---

### 5. Get Single Session

**Endpoint:** `GET /wasender/sessions/{id}`

---

### 6. Get QR Code

**Endpoint:** `GET /wasender/sessions/{id}/qrcode`

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "qr_code": "data:image/png;base64,iVBORw0KGgo..."
    }
}
```

---

### 7. Update Session

**Endpoint:** `PUT /wasender/sessions/{id}`

#### Request Body (All fields optional)
```json
{
    "name": "Updated Business Name",
    "phone_number": "+1987654321",
    "webhook_url": "https://new-webhook.example.com",
    "webhook_enabled": true
}
```

---

### 8. Delete Session

**Endpoint:** `DELETE /wasender/sessions/{id}`

#### Success Response (200)
```json
{
    "success": true,
    "message": "WhatsApp session deleted successfully",
    "data": {
        "deleted_local_id": 1,
        "deleted_wasender_id": "ws_abc123"
    }
}
```

---

## C. Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "schema_name": ["Schema name is required"],
        "channel": ["Message channel must be 'whatsapp'"]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "error": "Session not found",
    "message": "The requested session could not be found"
}
```

### Server Error (500)
```json
{
    "success": false,
    "error": "Failed to send notification",
    "message": "An internal server error occurred"
}
```

---

## D. Implementation Guide

### Quick Start Flow

1. **Create a WaSender Session**
     ```
     POST /wasender/sessions/create
     ```

2. **Connect the Session**
     ```
     POST /wasender/sessions/{id}/connect
     ```

3. **Scan QR Code**
     - Use the returned QR code image
     - Scan with WhatsApp mobile app
     - Wait for status to change to "connected"

4. **Send Messages**
     ```
     POST /notifications/send
     ```

### Important Notes

- **Schema Name:** Use the `uuid` column from your users table
- **Multi-Tenancy:** Sessions are linked to schemas for tenant isolation
- **Rate Limiting:** Bulk messages default to 60/minute, adjustable per request
- **Webhooks:** Configure callback URLs to receive delivery status updates
- **Phone Format:** Always include country code (e.g., +1234567890)

### Webhook Events

Available webhook events for session monitoring:
- `messages.received` - Incoming message notifications
- `session.status` - Connection status changes
- `messages.update` - Delivery status updates

---

## E. Implementation Phases for Laravel Integration

### Phase 1: Enhance Existing Tables (No New Tables Required)

#### Step 1.1: Enhance `outgoing_messages` Table (Already Perfect!)
The existing `outgoing_messages` table already contains all necessary fields for the notification API:
```sql
-- Current structure is ideal for notifications:
- id, user_id, instance_id, events_guest_id
- phone_number, message_body, message_type 
- media_path, media_url, caption
- status (pending, sent, delivered, read, failed)
- waapi_message_id, waapi_response
- scheduled_at, sent_at, error_message, retry_count
- timestamps and proper indexes
```

#### Step 1.2: Enhance `whatsapp_instances` Table (WaSender Sessions)
The existing `whatsapp_instances` table already handles WaSender sessions:
```sql
-- Current structure covers WaSender functionality:
- user_id, instance_id, instance_name, phone_number
- webhook_url, status, metadata, last_seen
- qr_code fields (from wasender enhancement)
- api_key, device_info, connection tracking
```

#### Step 1.3: Enhance `events_guests` Table (Contact Management)  
The existing `events_guests` table already serves as the contacts/recipients table:
```sql
-- Current structure perfect for contacts:
- event_id, guest_name, guest_email, guest_phone
- event_guest_category_id, guest_pledge
- contacted_for_sales, contacted_at (sales tracking)
- Foreign keys to users and events
```

#### Step 1.4: Optional: Add Metadata Fields (Minimal Changes)
```bash
# Only add if needed for bulk operations
php artisan make:migration add_notification_metadata_to_outgoing_messages
```

```php
// Optional enhancement for bulk tracking
Schema::table('outgoing_messages', function (Blueprint $table) {
    $table->string('batch_id')->nullable()->after('waapi_response');
    $table->json('metadata')->nullable()->after('batch_id'); 
    $table->string('priority', 20)->default('normal')->after('metadata');
    $table->string('provider', 50)->default('wasender')->after('priority');
    $table->string('external_id')->nullable()->after('provider'); // For API response tracking
});
```

### Phase 2: Service Layer Implementation (Use Existing Models)

#### Step 2.1: Enhance Existing Models
```php
// app/Models/OutgoingMessage.php (Already exists - enhance)
class OutgoingMessage extends Model {
    // Add notification-specific methods
    public function scopePending($query) {
        return $query->where('status', 'pending');
    }
    
    public function scopeForUser($query, $userId) {
        return $query->where('user_id', $userId);
    }
    
    public function scopeBatch($query, $batchId) {
        return $query->where('batch_id', $batchId);
    }
    
    // Relationships already exist
    public function user() { return $this->belongsTo(User::class); }
    public function eventsGuest() { return $this->belongsTo(EventsGuest::class); }
}

// app/Models/WhatsappInstance.php (Already exists - enhance)  
class WhatsappInstance extends Model {
    // Add WaSender-specific methods
    public function scopeActive($query) {
        return $query->whereIn('status', ['connected', 'active']);
    }
    
    public function scopeForUser($query, $userId) {
        return $query->where('user_id', $userId);
    }
    
    // Relationships
    public function outgoingMessages() {
        return $this->hasMany(OutgoingMessage::class, 'instance_id', 'instance_id');
    }
}

// app/Models/EventsGuest.php (Already exists - enhance)
class EventsGuest extends Model {
    // Add notification-specific methods
    public function outgoingMessages() {
        return $this->hasMany(OutgoingMessage::class);
    }
    
    public function incomingMessages() {
        return $this->hasMany(IncomingMessage::class);
    }
}
```

#### Step 2.2: Create Unified Notification Service
```php
// app/Services/UnifiedNotificationService.php (New service)
class UnifiedNotificationService {
    protected $baseUrl = 'https://notifcations.shulesoft.africa/api';
    protected $bearerToken = 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn';
    
    public function sendNotification($data) {
        // Call unified notification API
        // Store response in outgoing_messages table
        return $this->makeApiCall('/notifications/send', $data);
    }
    
    public function sendBulkNotifications($data) {
        // Call bulk notification API
        return $this->makeApiCall('/notifications/bulk/send', $data);
    }
    
    private function makeApiCall($endpoint, $data) {
        return Http::withToken($this->bearerToken)
            ->post($this->baseUrl . $endpoint, $data);
    }
}
```

### Phase 3: API Controller Implementation (Use Existing Structure)

#### Step 3.1: Create Notification Controller
```bash
# Create API controller following existing pattern
php artisan make:controller Api/NotificationController --api
```

```php
// app/Http/Controllers/Api/NotificationController.php
class NotificationController extends Controller {
    protected $notificationService;
    
    public function __construct(UnifiedNotificationService $notificationService) {
        $this->notificationService = $notificationService;
    }
    
    public function send(Request $request) {
        // Validate request
        $validated = $request->validate([
            'schema_name' => 'required|string',
            'to' => 'required|string',
            'message' => 'required|string',
            'channel' => 'required|in:whatsapp',
        ]);
        
        // Create local record for tracking
        $outgoingMessage = OutgoingMessage::create([
            'user_id' => $this->resolveUserId($validated['schema_name']),
            'phone_number' => $validated['to'],
            'message_body' => $validated['message'],
            'status' => 'pending',
            'provider' => 'unified_api',
        ]);
        
        // Send via unified API
        $response = $this->notificationService->sendNotification($validated);
        
        // Update local record with API response
        $outgoingMessage->update([
            'external_id' => $response['external_id'] ?? null,
            'status' => 'sent',
            'waapi_response' => $response->json(),
        ]);
        
        return response()->json($response->json());
    }
    
    public function sendBulk(Request $request) {
        // Similar implementation for bulk sending
    }
    
    private function resolveUserId($schemaName) {
        return User::where('uuid', $schemaName)->value('id');
    }
}
```

#### Step 3.2: Enhance Existing WaSender Controller
```php
// app/Http/Controllers/WaSenderController.php (Already exists)
// Add session management methods for unified API
public function createSession(Request $request) {
    $validated = $request->validate([
        'schema_name' => 'required|string',
        'name' => 'required|string',
        'phone_number' => 'required|string',
    ]);
    
    // Call unified API for session creation
    $response = Http::withToken('LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn')
        ->post('https://notifcations.shulesoft.africa/api/wasender/sessions/create', $validated);
    
    if ($response->successful()) {
        // Create local WhatsappInstance record
        $instance = WhatsappInstance::create([
            'user_id' => $this->resolveUserId($validated['schema_name']),
            'instance_id' => $response['data']['wasender_session_id'],
            'instance_name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'status' => 'disconnected',
            'api_key' => $response['data']['api_key'] ?? null,
        ]);
    }
    
    return response()->json($response->json());
}

public function getQRCode($id) {
    $instance = WhatsappInstance::findOrFail($id);
    
    // Get QR code from unified API
    $response = Http::withToken('LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn')
        ->get("https://notifcations.shulesoft.africa/api/wasender/sessions/{$instance->instance_id}/qrcode");
    
    return response()->json($response->json());
}
```

### Phase 4: API Routes Integration (Extend Existing)

```php
// routes/api.php (Add to existing API routes)
Route::middleware(['auth:sanctum'])->group(function () {
    // Notification endpoints using existing table structure
    Route::prefix('notifications')->group(function () {
        Route::post('/send', [Api\NotificationController::class, 'send']);
        Route::post('/bulk/send', [Api\NotificationController::class, 'sendBulk']);
        Route::get('/{id}', [Api\NotificationController::class, 'show']);
        Route::get('/', [Api\NotificationController::class, 'index']);
    });
    
    // WaSender session endpoints using whatsapp_instances table
    Route::prefix('wasender/sessions')->group(function () {
        Route::post('/create', [WaSenderController::class, 'createSession']);
        Route::get('/', [WaSenderController::class, 'getSessions']);
        Route::get('/{id}', [WaSenderController::class, 'getSession']);
        Route::post('/{id}/connect', [WaSenderController::class, 'connectSession']);
        Route::get('/{id}/status', [WaSenderController::class, 'getSessionStatus']);
        Route::get('/{id}/qrcode', [WaSenderController::class, 'getQRCode']);
        Route::put('/{id}', [WaSenderController::class, 'updateSession']);
        Route::delete('/{id}', [WaSenderController::class, 'deleteSession']);
    });
});
```

### Phase 5: Database Relationships & Schema Mapping

#### Step 5.1: Schema Name Mapping
```php
// Map API schema_name to existing user structure
class NotificationService {
    public function resolveUser($schemaName) {
        // schema_name can be:
        // 1. user.uuid (if users table has uuid column)
        // 2. user.id (direct user ID)
        // 3. event.uid (if using event-based tenancy)
        
        return User::where('uuid', $schemaName)
            ->orWhere('id', $schemaName)
            ->firstOrFail();
    }
    
    public function resolveEventGuest($userId, $phoneNumber) {
        // Find or create EventsGuest record
        $userEvent = UsersEvent::where('user_id', $userId)->first();
        
        return EventsGuest::firstOrCreate([
            'event_id' => $userEvent->event_id,
            'guest_phone' => $phoneNumber,
        ], [
            'guest_name' => 'Auto-created',
            'event_guest_category_id' => 1,
            'guest_pledge' => 0,
        ]);
    }
}
```

#### Step 5.2: Message Status Mapping
```php
// Map API status to existing outgoing_messages.status
class MessageStatusMapper {
    public static function mapToApi($dbStatus) {
        $mapping = [
            'pending' => 'sent',      // Message queued
            'sent' => 'sent',         // Message sent to provider
            'delivered' => 'delivered', // Delivered to recipient  
            'read' => 'read',         // Read by recipient
            'failed' => 'failed',     // Failed to send
        ];
        return $mapping[$dbStatus] ?? $dbStatus;
    }
}
```

### Phase 6: Queue Job Enhancement (Use Existing Jobs)

#### Step 6.1: Enhance Existing Job Classes
```php
// app/Jobs/SendWhatsAppMessage.php (Already exists - enhance)
class SendWhatsAppMessage implements ShouldQueue {
    public function handle() {
        // Load OutgoingMessage record
        // Update with external_id from API response
        // Update status and metadata
        // Handle retries using existing retry_count field
    }
}

// app/Jobs/SendWhatsAppMediaMessage.php (Already exists - enhance)
class SendWhatsAppMediaMessage implements ShouldQueue {
    public function handle() {
        // Handle file attachments
        // Store media_url in existing field
        // Update message status
    }
}
```

### Phase 7: Frontend Integration (Use Existing Views)

#### Step 7.1: Enhance Existing Interfaces
```php
// resources/views/service/index.blade.php (Already exists)
// Add notification API testing interface

// resources/views/guest/index.blade.php (Already exists) 
// Add bulk WhatsApp sending using API

// public/js/whatsapp-manager.js (Enhance existing JS)
class NotificationAPI {
    constructor() {
        this.baseUrl = 'https://notifcations.shulesoft.africa/api';
        this.bearerToken = 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn';
    }
    
    async sendMessage(data) {
        const response = await fetch(`${this.baseUrl}/notifications/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.bearerToken}`,
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
    
    async createSession(data) {
        const response = await fetch(`${this.baseUrl}/wasender/sessions/create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.bearerToken}`,
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
}
```

### Phase 8: Configuration Integration (Minimal Changes)

#### Step 8.1: Use Unified API Configuration
```php
// config/notifications.php (New configuration file)
return [
    'unified_api' => [
        'base_url' => 'https://notifcations.shulesoft.africa/api',
        'bearer_token' => 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn',
    ],
    'defaults' => [
        'provider' => 'unified_api',
        'channel' => 'whatsapp',
        'priority' => 'normal',
        'rate_limit' => 60,
        'batch_size' => 50,
    ],
];
```

### Phase 9: Testing with Existing Data

#### Step 9.1: Use Existing Test Structure
```php
// tests/Feature/NotificationApiTest.php
class NotificationApiTest extends TestCase {
    public function test_send_notification_creates_outgoing_message() {
        // Test API creates record in existing outgoing_messages table
        // Verify EventsGuest relationship
        // Check WhatsappInstance integration
    }
}
```

---

## F. Table Mapping Summary

### 🔄 **Existing Tables Usage:**

| **API Concept** | **Existing Table** | **Purpose** |
|----------------|-------------------|-------------|
| **Notifications** | `outgoing_messages` | Store all WhatsApp messages sent via API |
| **WaSender Sessions** | `whatsapp_instances` | Manage WhatsApp session connections |  
| **Recipients/Contacts** | `events_guests` | Store contact information and phone numbers |
| **Users/Tenants** | `users` + `users_events` | Multi-tenancy via user/event relationships |
| **Message Templates** | `outgoing_messages.message_body` | Store template content directly |
| **Status Tracking** | `outgoing_messages.status` | Track delivery status |
| **Bulk Operations** | `outgoing_messages.batch_id` | Group related messages |
| **Webhooks** | `incoming_messages` | Handle webhook callbacks |

### ✅ **Key Benefits of This Approach:**

- **Zero Database Migration Risk**: Uses existing proven table structure
- **Seamless Integration**: Leverages existing relationships and indexes
- **Data Consistency**: Maintains existing data integrity
- **Performance Optimized**: Uses existing indexes and foreign keys  
- **Backward Compatibility**: Existing functionality remains unchanged
- **Immediate Implementation**: No database downtime or schema changes

### 🚀 **Quick Start Implementation:**

1. **Enhance Models**: Add new methods to existing `OutgoingMessage`, `WhatsappInstance`, `EventsGuest` models
2. **Create Unified Service**: Build `UnifiedNotificationService` to handle API communication
3. **Create API Controllers**: Build notification controllers using unified API service
4. **Add API Routes**: Extend existing API routes with notification endpoints
5. **Optional Metadata**: Add minimal `batch_id`, `metadata`, `priority` fields if needed
6. **Update Configuration**: Set unified API base URL and bearer token

### 🔧 **Environment Setup:**

```env
# .env additions for unified API
UNIFIED_API_BASE_URL="https://notifcations.shulesoft.africa/api"
UNIFIED_API_BEARER_TOKEN="LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn"
```

This approach provides full notification API functionality using the unified notification service while maintaining your existing database architecture and avoiding any risks associated with new table creation.

