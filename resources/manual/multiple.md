# Multiple WhatsApp Instance Support Requirements

## Overview
This document outlines the requirements and implementation plan to enhance the SafariChat AI Sales Agent system to support multiple WhatsApp instances under a single user account without confusion or data conflicts.

## Current System Analysis

### ✅ Components That Already Work Well
- **AI Sales Agent Configuration**: Shared across instances with consistent personality
- **Product Catalog**: Unified product/service catalog for all instances
- **Lead Management**: Separate lead tracking per phone number (no collision)
- **Knowledge Base**: Shared FAQs and documents across all instances

### ⚠️ Areas Requiring Enhancement

#### 1. Instance Tracking in Messages
**Problem**: No way to identify which WhatsApp instance sent/received a message
**Impact**: Mixed analytics, unclear message routing, loss of instance context

**Current State:**
```php
OutgoingMessage::create([
    'user_id' => $user_id,
    'phone_number' => $phone,
    'message_content' => $message,
    // Missing: instance_id tracking
]);
```

#### 2. AI Context Lacks Instance Awareness
**Problem**: AI doesn't know which WhatsApp line the customer contacted
**Impact**: Generic responses, no instance-specific messaging

**Current State:**
```php
// AI context building has no instance awareness
private function buildSystemPrompt(AiSalesAgent $agent, Lead $lead, ?Product $product): string
{
    // No instance-specific context available
}
```

#### 3. Session Management Issues
**Problem**: System randomly selects active instance
**Impact**: User confusion, messages sent from wrong number

**Current State:**
```php
$whatsappInstance = WhatsappInstance::where('user_id', Auth::id())
    ->where('status', 'connected')
    ->first(); // Gets random instance, not user's choice
```

#### 4. Analytics & Reporting Confusion
**Problem**: All metrics mixed together regardless of instance
**Impact**: Cannot track performance per WhatsApp line

## Required System Enhancements

### 1. Database Schema Updates

#### A. Add Instance Tracking to Messages
```sql
-- Migration: add_instance_tracking_to_messages.php
ALTER TABLE outgoing_messages 
ADD COLUMN whatsapp_instance_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (whatsapp_instance_id) REFERENCES whatsapp_instances(id);

ALTER TABLE incoming_messages 
ADD COLUMN whatsapp_instance_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (whatsapp_instance_id) REFERENCES whatsapp_instances(id);

-- Create indexes for performance
CREATE INDEX idx_outgoing_messages_instance ON outgoing_messages(whatsapp_instance_id);
CREATE INDEX idx_incoming_messages_instance ON incoming_messages(whatsapp_instance_id);
```

#### B. Enhance WhatsApp Instance Model
```sql
-- Migration: enhance_whatsapp_instances_table.php
ALTER TABLE whatsapp_instances 
ADD COLUMN uuid VARCHAR(36) UNIQUE NOT NULL, -- New UUID for each instance
ADD COLUMN purpose VARCHAR(50) DEFAULT 'general',
ADD COLUMN instance_description TEXT NULL,
ADD COLUMN is_primary BOOLEAN DEFAULT false,
ADD COLUMN display_name VARCHAR(100) NULL;

-- Add indexes
CREATE INDEX idx_whatsapp_instances_purpose ON whatsapp_instances(purpose);
CREATE INDEX idx_whatsapp_instances_uuid ON whatsapp_instances(uuid);

-- Generate UUIDs for existing records
UPDATE whatsapp_instances SET uuid = UUID() WHERE uuid IS NULL;
```

#### C. Session-Based Instance Tracking
No additional table needed - using Laravel session storage to track active instance per user session.

### 2. Model Enhancements

#### A. WhatsappInstance Model Updates
```php
// app/Models/WhatsappInstance.php - Add new methods
class WhatsappInstance extends Model
{
    protected $fillable = [
        'user_id', 'instance_id', 'phone_number', 'status', 'webhook_url', 
        'api_key', 'qr_code', 'last_seen', 'uuid', 'purpose', 'instance_description',
        'is_primary', 'display_name'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_seen' => 'datetime'
    ];

    // Generate UUID on model creation
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    // New methods needed
    public function scopePrimary($query) {
        return $query->where('is_primary', true);
    }

    public function scopeByPurpose($query, $purpose) {
        return $query->where('purpose', $purpose);
    }

    public function getDisplayNameAttribute() {
        return $this->display_name ?: $this->phone_number;
    }

    // Schema name for message routing (replaces user UUID)
    public function getSchemaNameAttribute() {
        return $this->uuid;
    }

    public function outgoingMessages() {
        return $this->hasMany(OutgoingMessage::class);
    }

    public function incomingMessages() {
        return $this->hasMany(IncomingMessage::class);
    }
}
```

#### B. Message Model Updates
```php
// app/Models/OutgoingMessage.php - Add instance relationship
class OutgoingMessage extends Model
{
    protected $fillable = [
        'user_id', 'phone_number', 'message_content', 'status', 'sent_at',
        'whatsapp_instance_id' // New field
    ];

    public function whatsappInstance() {
        return $this->belongsTo(WhatsappInstance::class);
    }
}

// app/Models/IncomingMessage.php - Add instance relationship  
class IncomingMessage extends Model
{
    protected $fillable = [
        'user_id', 'phone_number', 'message_body', 'chat_id', 'message_id',
        'whatsapp_instance_id' // New field
    ];

    public function whatsappInstance() {
        return $this->belongsTo(WhatsappInstance::class);
    }
}
```

### 3. Service Layer Enhancements

#### A. Enhanced AiWhatsAppService
```php
// app/Services/AiWhatsAppService.php - Instance awareness
class AiWhatsAppService
{
    public function processMessage(
        IncomingMessage $incomingMessage, 
        WhatsappInstance $instance = null
    ): array {
        // Include instance in AI context
        $agent = $this->getAgentForUser($incomingMessage->user_id);
        $lead = $this->findOrCreateLead($incomingMessage);
        
        // Pass instance to AI service
        $response = $this->openAiService->generateSalesResponse(
            $incomingMessage->message_body,
            $agent,
            $lead,
            $this->getConversationHistory($lead),
            null,
            $instance // New parameter
        );

        // Store instance_id with outgoing message
        $this->sendResponse($response['response'], $incomingMessage, $instance);
        
        return $response;
    }

    private function sendResponse(
        string $response, 
        IncomingMessage $originalMessage, 
        ?WhatsappInstance $instance
    ): bool {
        // Include instance_id when creating outgoing message
        OutgoingMessage::create([
            'user_id' => $originalMessage->user_id,
            'phone_number' => $originalMessage->phone_number,
            'message_content' => $response,
            'whatsapp_instance_id' => $instance?->id, // New field
            'status' => 'pending'
        ]);
    }
}
```

#### B. Message Routing & Schema Name Updates
```php
// app/Services/WaSenderService.php - Updated for instance-specific routing
class WaSenderService 
{
    public function sendMessage($phoneNumber, $message, WhatsappInstance $instance)
    {
        // Use instance UUID instead of user UUID for schema_name
        $schemaName = $instance->uuid; // Changed from Auth::user()->uuid
        
        $payload = [
            'phone' => $phoneNumber,
            'message' => $message,
            'schema_name' => $schemaName, // Now uses instance UUID
            'instance_id' => $instance->instance_id
        ];
        
        return $this->makeApiCall($payload);
    }
    
    public function getInstanceBySchemaName($schemaName)
    {
        // Find instance by UUID instead of user UUID
        return WhatsappInstance::where('uuid', $schemaName)->first();
    }
}

// app/Http/Controllers/WebhookController.php - Updated routing logic  
class WebhookController extends Controller
{
    public function handleIncomingMessage(Request $request)
    {
        $schemaName = $request->input('schema_name');
        
        // Find WhatsApp instance by UUID (not user UUID)
        $instance = WhatsappInstance::where('uuid', $schemaName)->first();
        
        if (!$instance) {
            Log::error("Instance not found for schema_name: {$schemaName}");
            return response()->json(['error' => 'Instance not found'], 404);
        }
        
        // Create incoming message with instance tracking
        $incomingMessage = IncomingMessage::create([
            'user_id' => $instance->user_id,
            'whatsapp_instance_id' => $instance->id, // Track instance
            'phone_number' => $request->input('phone'),
            'message_body' => $request->input('message'),
            'chat_id' => $request->input('chat_id'),
            'message_id' => $request->input('message_id')
        ]);
        
        // Process with AI using specific instance
        $aiService = app(AiWhatsAppService::class);
        $response = $aiService->processMessage($incomingMessage, $instance);
        
        return response()->json(['success' => true]);
    }
}
```

#### B. Enhanced OpenAiService
```php
// app/Services/OpenAiService.php - Instance-aware context
class OpenAiService
{
    public function generateSalesResponse(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory = [],
        ?Product $product = null,
        ?WhatsappInstance $instance = null // New parameter
    ): array {
        $prompt = $this->buildPromptWithAgent(
            $customerMessage, $agent, $lead, $conversationHistory, $product, $instance
        );
        // ... rest of method
    }

    private function buildSystemPrompt(
        AiSalesAgent $agent, 
        Lead $lead, 
        ?Product $product,
        ?WhatsappInstance $instance = null // New parameter
    ): string {


        $prompt = "You are {$agent->name}, a professional sales consultant at {$agent->business_name}. ";
$prompt .= "Your goal is to assist prospects, understand their needs, and guide them toward the right solution.";

if ($instance) {
    $instanceName = $instance->display_name ?: $agent->name;

    $prompt .= " You are communicating via {$agent->name}'s WhatsApp contact";

    if ($instance->purpose && $instance->purpose !== 'general') {
        $prompt .= ", primarily handling {$instance->purpose} inquiries";
    }

    if ($instance->instance_description) {
        $prompt .= ". {$instance->instance_description}";
    }

    $prompt .= ". You may use smart assistance to respond faster, but always speak in first person as {$agent->name}.";
}

        
        // ... rest of prompt building
        return $prompt;
    }
}
```

#### C. Queue Job Updates for Instance-Specific Processing
```php
// app/Jobs/SendWhatsAppMessage.php - Updated for instance routing
class SendWhatsAppMessage implements ShouldQueue
{
    protected $message;
    protected $phoneNumber;
    protected $instanceId; // Changed from user_id to instance_id
    
    public function __construct($message, $phoneNumber, $instanceId)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
        $this->instanceId = $instanceId;
    }
    
    public function handle()
    {
        $instance = WhatsappInstance::find($this->instanceId);
        
        if (!$instance) {
            Log::error("WhatsApp instance not found: {$this->instanceId}");
            return;
        }
        
        // Use instance UUID for schema_name routing
        $waSender = new WaSenderService();
        $result = $waSender->sendMessage(
            $this->phoneNumber, 
            $this->message, 
            $instance // Pass full instance
        );
        
        // Update outgoing message status
        OutgoingMessage::where('whatsapp_instance_id', $this->instanceId)
            ->where('phone_number', $this->phoneNumber)
            ->where('message_content', $this->message)
            ->update(['status' => $result ? 'sent' : 'failed']);
    }
}
```

### 4. User Interface Enhancements

#### A. Instance Selection Component
```php
// Create new controller: app/Http/Controllers/WhatsappInstanceController.php
class WhatsappInstanceController extends Controller
{
    public function selectActiveInstance(Request $request) 
    {
        $instanceId = $request->instance_id;
        $user = Auth::user();
        
        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $instanceId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Store in session only
        session(['active_whatsapp_instance' => $instanceId]);
        
        return response()->json([
            'success' => true,
            'instance_name' => $instance->display_name,
            'phone_number' => $instance->phone_number
        ]);
    }

    public function getActiveInstance()
    {
        $sessionInstanceId = session('active_whatsapp_instance');
        
        if ($sessionInstanceId) {
            return WhatsappInstance::where('id', $sessionInstanceId)
                ->where('user_id', Auth::id())
                ->first();
        }
        
        // Fallback to primary instance or first available
        return WhatsappInstance::where('user_id', Auth::id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->first();
    }
}
```

#### B. Dashboard UI Updates
```html
<!-- Add to dashboard: resources/views/home.blade.php -->
<div class="instance-selector mb-3">
    <label class="form-label">Active WhatsApp Instance:</label>
    <select class="form-select" id="instanceSelector" onchange="changeActiveInstance(this.value)">
        <option value="">Select WhatsApp Line...</option>
        @foreach($whatsapp_instances as $instance)
            <option value="{{ $instance->id }}" 
                    {{ session('active_whatsapp_instance') == $instance->id ? 'selected' : '' }}>
                {{ $instance->display_name ?: $instance->phone_number }} 
                @if($instance->purpose !== 'general')
                    ({{ ucfirst($instance->purpose) }})
                @endif
            </option>
        @endforeach
    </select>
</div>

<script>
function changeActiveInstance(instanceId) {
    if (!instanceId) return;
    
    fetch('/api/whatsapp/select-instance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ instance_id: instanceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Switched to ${data.instance_name}`);
            // Refresh relevant dashboard sections
            location.reload();
        }
    });
}
</script>
```

### 5. Analytics & Reporting Updates

#### A. Instance-Specific Metrics
```php
// app/Http/Controllers/Home.php - Update dashboard metrics
public function index()
{
    $user_id = Auth::id();
    $activeInstanceId = session('active_whatsapp_instance');
    
    // Base query with optional instance filtering
    $messageQuery = function($model) use ($user_id, $activeInstanceId) {
        $query = $model::where('user_id', $user_id);
        if ($activeInstanceId) {
            $query->where('whatsapp_instance_id', $activeInstanceId);
        }
        return $query;
    };
    
    // Instance-aware metrics
    $this->data['messages_sent_today'] = $messageQuery(OutgoingMessage::class)
        ->whereDate('created_at', today())
        ->count();
        
    $this->data['active_conversations'] = $messageQuery(IncomingMessage::class)
        ->where('created_at', '>=', now()->subDays(30))
        ->distinct('phone_number')
        ->count();
    
    // Chart data - per instance if selected
    $this->data['reports'] = DB::select("
        SELECT 
            COUNT(*) as count,
            EXTRACT(month FROM created_at) as month,
            EXTRACT(year FROM created_at) as year
        FROM outgoing_messages 
        WHERE user_id = ? 
        AND created_at >= ?
        " . ($activeInstanceId ? "AND whatsapp_instance_id = $activeInstanceId" : "") . "
        GROUP BY month, year 
        ORDER BY year DESC, month DESC 
        LIMIT 12
    ", [$user_id, now()->subMonths(12)]);
    
    // Instance selector data
    $this->data['whatsapp_instances'] = WhatsappInstance::where('user_id', $user_id)
        ->orderBy('is_primary', 'desc')
        ->orderBy('created_at')
        ->get();
    
    return view('home', $this->data);
}
```

### 6. Configuration & Management Features

#### A. Instance Purpose Configuration
```php
// Add to WhatsApp instance management
class WhatsappInstanceManager
{
    public static $purposes = [
        'general' => 'General Business',
        'sales' => 'Sales Inquiries',
        'support' => 'Customer Support', 
        'marketing' => 'Marketing Campaigns',
        'orders' => 'Order Processing'
    ];

    public function updateInstanceConfiguration($instanceId, array $config)
    {
        return WhatsappInstance::where('id', $instanceId)->update([
            'purpose' => $config['purpose'],
            'instance_description' => $config['description'],
            'display_name' => $config['display_name'],
            'is_primary' => $config['is_primary'] ?? false
        ]);
    }
}
```

### 7. Schema Name Migration Strategy

#### A. Data Migration for Existing Systems
```php
// app/Console/Commands/MigrateSchemaNames.php
class MigrateSchemaNames extends Command
{
    protected $signature = 'whatsapp:migrate-schema-names';
    protected $description = 'Migrate from user UUID to instance UUID for schema names';
    
    public function handle()
    {
        $this->info('Starting schema name migration...');
        
        // Step 1: Generate UUIDs for all WhatsApp instances
        $instances = WhatsappInstance::whereNull('uuid')->get();
        
        foreach ($instances as $instance) {
            $instance->uuid = (string) \Illuminate\Support\Str::uuid();
            $instance->save();
            $this->info("Generated UUID for instance {$instance->id}: {$instance->uuid}");
        }
        
        // Step 2: Update webhook configurations
        $this->updateWebhookConfigurations();
        
        // Step 3: Update any cached schema references
        $this->clearSchemaCache();
        
        $this->info('Schema name migration completed successfully!');
    }
    
    private function updateWebhookConfigurations()
    {
        // Update webhook URLs with new instance UUIDs
        $instances = WhatsappInstance::whereNotNull('uuid')->get();
        
        foreach ($instances as $instance) {
            $webhookUrl = config('app.url') . "/webhook/whatsapp/{$instance->uuid}";
            $instance->update(['webhook_url' => $webhookUrl]);
            $this->info("Updated webhook URL for instance {$instance->id}");
        }
    }
    
    private function clearSchemaCache()
    {
        // Clear any Redis/cache entries using old user UUIDs
        \Illuminate\Support\Facades\Cache::flush();
        $this->info('Cleared schema cache');
    }
}
```

#### B. Webhook URL Updates
```php
// app/Http/Controllers/WhatsAppSetupController.php
class WhatsAppSetupController extends Controller
{
    public function setupInstance(Request $request)
    {
        $instance = WhatsappInstance::create([
            'user_id' => Auth::id(),
            'instance_id' => $request->instance_id,
            'phone_number' => $request->phone_number,
            'uuid' => (string) \Illuminate\Support\Str::uuid(), // Generate UUID
            'status' => 'pending'
        ]);
        
        // Generate webhook URL using instance UUID (not user UUID)
        $webhookUrl = config('app.url') . "/webhook/whatsapp/{$instance->uuid}";
        
        // Configure webhook with external WhatsApp API
        $this->configureWebhook($instance->instance_id, $webhookUrl);
        
        $instance->update(['webhook_url' => $webhookUrl]);
        
        return response()->json([
            'success' => true,
            'instance_id' => $instance->id,
            'webhook_url' => $webhookUrl,
            'schema_name' => $instance->uuid // Return instance UUID as schema name
        ]);
    }
    
    private function configureWebhook($instanceId, $webhookUrl)
    {
        // Configure webhook with WhatsApp API provider
        // This will now use instance-specific UUID routing
    }
}
```

#### C. External API Integration Updates
```php
// All external API calls that previously used users.uuid now use whatsapp_instances.uuid

// Example: WhatsApp API configuration
class WhatsAppApiClient 
{
    public function configureInstance(WhatsappInstance $instance)
    {
        return [
            'instance_id' => $instance->instance_id,
            'schema_name' => $instance->uuid, // Changed from user UUID
            'webhook_url' => route('webhook.whatsapp', ['schema' => $instance->uuid]),
            'phone_number' => $instance->phone_number
        ];
    }
    
    public function sendMessage($phoneNumber, $message, WhatsappInstance $instance)
    {
        $payload = [
            'phone' => $phoneNumber,
            'message' => $message,
            'schema_name' => $instance->uuid, // Instance-specific routing
            'instance_id' => $instance->instance_id
        ];
        
        return $this->makeApiCall('/send-message', $payload);
    }
}
```

## Implementation Priority

### Phase 1: Core Instance Tracking (High Priority)
1. **Database migrations** for instance tracking and UUID generation
2. **Schema name migration** from user UUID to instance UUID
3. **Update message models** to include instance relationships
4. **Modify webhook routing** to use instance UUID
5. **Update WaSenderService** for instance-specific routing

### Phase 2: AI Context Enhancement (High Priority)  
1. **Update OpenAiService** to accept instance parameter
2. **Enhance system prompts** with instance-specific context
3. **Modify AiWhatsAppService** to track instances
4. **Update queue jobs** for instance-based processing
5. **Test AI responses** for instance awareness

### Phase 3: User Interface (Medium Priority)
1. **Add instance selector** to dashboard
2. **Create instance management** interface
3. **Update analytics** to show per-instance metrics
4. **Add instance configuration** forms

### Phase 4: Advanced Features (Low Priority)
1. **Instance-specific business rules**
2. **Advanced analytics and reporting**
3. **Instance performance comparisons**
4. **Bulk instance management** tools

## Testing Requirements

### Functional Testing
- [ ] Multiple instances can operate simultaneously without data mixing
- [ ] **Schema name routing works correctly with instance UUIDs**
- [ ] **Webhook routing directs messages to correct instance**  
- [ ] AI context includes correct instance information
- [ ] Analytics separate correctly by instance
- [ ] Message routing works for each instance
- [ ] Session management preserves instance selection

### Integration Testing  
- [ ] **WhatsApp API integration uses instance UUID instead of user UUID**
- [ ] **External webhook callbacks route to correct instance**
- [ ] WhatsApp webhook routing to correct instance
- [ ] AI responses maintain instance context
- [ ] Database queries filter by instance correctly
- [ ] User interface updates reflect active instance
- [ ] **Queue jobs process messages with correct instance context**

### Performance Testing
- [ ] System handles multiple concurrent instances
- [ ] Database performance with instance filtering
- [ ] Memory usage with multiple active instances
- [ ] **Webhook routing performance with instance lookup**

### Migration Testing
- [ ] **Existing user UUIDs migrate successfully to instance UUIDs**
- [ ] **All webhook URLs update correctly**
- [ ] **No message routing breaks during migration**
- [ ] **External API configurations update properly**

## Success Criteria

1. **No Data Confusion**: Messages, leads, and analytics clearly separated by instance
2. **Consistent AI Behavior**: AI maintains appropriate context for each WhatsApp line
3. **Clear User Experience**: Users can easily distinguish and manage multiple instances
4. **Performance Maintained**: System performance remains stable with multiple instances
5. **Accurate Analytics**: Reporting correctly reflects per-instance metrics
6. **Proper Message Routing**: Each instance routes messages using its unique UUID**
7. **Seamless Migration**: Existing systems migrate from user UUID to instance UUID without disruption**

## Risk Mitigation

### Data Integrity Risks
- **Risk**: Existing data without instance tracking
- **Mitigation**: Migration script to assign historical data to primary instance
- **Risk**: Schema name conflicts during migration
- **Mitigation**: Generate unique UUIDs for all instances before migration

### Performance Risks
- **Risk**: Additional joins may slow queries
- **Mitigation**: Proper indexing and query optimization
- **Risk**: Webhook routing lookup overhead
- **Mitigation**: Redis caching for instance UUID lookups

### User Confusion Risks
- **Risk**: Users unclear about which instance is active
- **Mitigation**: Clear UI indicators and confirmation messages

### Migration Risks
- **Risk**: External API integrations break during UUID switch
- **Mitigation**: Gradual rollout with backward compatibility period
- **Risk**: Webhook URLs become invalid
- **Mitigation**: Update all webhook configurations before activating new routing

This document serves as the complete roadmap for implementing robust multiple WhatsApp instance support in the SafariChat AI Sales Agent system, including the critical migration from user-based UUID schema names to instance-based UUID routing for proper message handling and isolation.