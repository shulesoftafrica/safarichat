# AI Sales Agent Implementation Plan for SafariChat

## Project Overview
This document provides a complete implementation roadmap for building a high-efficiency, multi-stage AI Sales Agent system within the existing SafariChat Laravel application.

**Updated Requirements Integration:**
- ✅ **Contact-Based Leads** - Each lead references an existing contact (`events_guests` table)
- ✅ **Product-Specific Sales** - Each lead targets a specific product/service from the `products` table
- ✅ **Multiple Products Per Contact** - One contact can have multiple leads for different products
- ✅ **Sale Completion Tracking** - CLOSED state added for successful purchases
- ✅ **Price Negotiation** - AI can quote prices and apply discounts within limits
- ✅ **Win-Back Campaigns** - Dedicated churned customer handling with specialized messaging and separate outreach schedule
- ✅ **Churn Tracking** - Track churn date, reason, and time-based win-back eligibility
- ✅ **Instant AI Responses** - Webhook-based instant processing with smart business hour handling

**Current Stack Analysis:**
- ✅ Laravel Framework (v8.12) - Already configured
- ✅ WhatsApp Integration - Existing WaSender API Service 
- ✅ Queue System - Laravel Horizon configured
- ✅ Contact Management - Existing `events_guests` table
- ✅ Product Catalog - Existing `products` table
- ⚠️ Database - Currently MySQL, needs PostgreSQL migration
- ⚠️ AI Integration - Need to add OpenAI API
- ⚠️ New Database Schema - Requires new tables for AI agent

**Target Implementation Stack:**
- Laravel 8.12 (Backend/Cron Jobs)
- PostgreSQL (Database - migration required)
- Existing WhatsApp API Integration (Enhanced)
- OpenAI GPT-4o/4.1 API (New AI Core)
- Laravel Horizon (Queue Management)
- Existing EventsGuest (Contact Management)
- Existing Product (Product/Service Catalog)

## 1. Database Schema Implementation (PostgreSQL Migration)

### 1.1. Database Migration Strategy

**Step 1: Configure OpenAI API Support**
```php
// Add to composer.json
"require": {
    "php": "^7.3|^8.0",
    // existing packages...
    "doctrine/dbal": "^3.0",
    "ext-pgsql": "*",
    "openai-php/client": "^0.8.5"
}
```

**Step 2: Environment Configuration**
```env
# Add to .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=safarichat_ai
DB_USERNAME=postgres
DB_PASSWORD=your_password

# OpenAI API Configuration
OPENAI_API_KEY=your_openai_api_key
OPENAI_API_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o
OPENAI_TEMPERATURE=0.7
OPENAI_MAX_TOKENS=1024
```

### 1.2. New Database Tables

**Migration Files Required:**
- `2025_11_19_000001_create_products_table.php`
- `2025_11_19_000002_create_leads_table.php`
- `2025_11_19_000003_create_lead_products_table.php`
- `2025_11_19_000004_create_conversations_table.php`  
- `2025_11_19_000005_create_handoffs_table.php`
- `2025_11_19_000006_create_outreach_variants_table.php`
- `2025_11_19_000007_create_ai_sales_agents_table.php` (NEW - Agent configurations)
- `2025_11_19_000008_update_incoming_messages_table.php` (NEW - Webhook support)
- `2025_11_19_000009_create_ai_agent_configs_table.php`

### 1.3. products Table Implementation

```php
// database/migrations/2025_11_19_000001_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->text('ai_description')->nullable(); // AI-optimized description for conversations
    $table->decimal('base_price', 10, 2);
    $table->decimal('max_discount_percentage', 5, 2)->default(0); // Max discount allowed (0-100)
    $table->string('category', 100)->nullable();
    $table->string('target_industry', 100)->nullable();
    $table->json('key_features')->nullable(); // Array of key selling points
    $table->json('common_objections')->nullable(); // Common objections and responses
    $table->integer('sales_cycle_days')->default(30); // Expected sales cycle length
    $table->boolean('is_active')->default(true);
    $table->boolean('requires_demo')->default(false);
    $table->boolean('has_trial')->default(false);
    $table->integer('trial_days')->nullable();
    $table->decimal('setup_fee', 10, 2)->nullable();
    $table->string('billing_frequency')->default('monthly'); // monthly, yearly, one-time
    $table->json('upsell_products')->nullable(); // Array of related product IDs
    $table->timestamps();
    
    $table->index(['is_active', 'category']);
    $table->index('target_industry');
});
```

### 1.4. leads Table Implementation

```php
// database/migrations/2025_11_19_000002_create_leads_table.php
Schema::create('leads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('events_guest_id')->constrained('events_guests')->onDelete('cascade'); // Reference to existing contact
    $table->string('company_name')->nullable();
    $table->string('industry', 100)->nullable();
    $table->integer('lead_score')->default(0);
    $table->enum('status', [
        'NEW', 'QUEUED', 'OUTREACHED', 'REPLIED', 
        'HANDED_OFF', 'DO_NOT_CONTACT', 'CLOSED', 'WIN_BACK'
    ])->default('NEW');
    $table->boolean('is_churned')->default(false);
    $table->date('churn_date')->nullable();
    $table->string('churn_reason')->nullable();
    $table->timestamp('last_outreach_at')->nullable();
    $table->string('timezone', 50)->default('Africa/Nairobi');
    $table->string('source')->nullable(); // Lead source tracking
    $table->json('metadata')->nullable(); // Additional lead data
    $table->timestamps();
    
    $table->index(['status', 'lead_score']);
    $table->index(['status', 'last_outreach_at']);
    $table->index('events_guest_id');
    $table->unique('events_guest_id'); // One lead per contact (products tracked separately)
});
```

### 1.5. lead_products Table Implementation

```php
// database/migrations/2025_11_19_000003_create_lead_products_table.php
Schema::create('lead_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->enum('status', [
        'INTERESTED', 'PITCHED', 'DEMO_REQUESTED', 'DEMO_COMPLETED',
        'PROPOSAL_SENT', 'NEGOTIATING', 'OBJECTION_RAISED', 'CLOSED_WON', 
        'CLOSED_LOST', 'ON_HOLD', 'FOLLOW_UP_LATER'
    ])->default('INTERESTED');
    $table->decimal('quoted_price', 10, 2)->nullable();
    $table->decimal('final_price', 10, 2)->nullable();
    $table->integer('discount_applied', 5, 2)->default(0); // Percentage discount
    $table->json('objections_raised')->nullable(); // Track specific objections
    $table->json('features_discussed')->nullable(); // Features that were highlighted
    $table->timestamp('last_interaction_at')->nullable();
    $table->date('demo_scheduled_date')->nullable();
    $table->date('proposal_sent_date')->nullable();
    $table->date('follow_up_date')->nullable();
    $table->text('notes')->nullable(); // Sales notes
    $table->boolean('is_primary_product')->default(false); // Main product focus
    $table->integer('interaction_count')->default(0);
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->unique(['lead_id', 'product_id']); // One record per lead per product
    $table->index(['lead_id', 'status']);
    $table->index(['product_id', 'status']);
    $table->index(['status', 'last_interaction_at']);
    $table->index('is_primary_product');
});
```

### 1.6. conversations Table Implementation  

```php
// database/migrations/2025_11_19_000004_create_conversations_table.php
Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Product context
    $table->enum('message_type', ['AI', 'CUSTOMER']);
    $table->text('message_content');
    $table->string('outbound_ref')->nullable(); // WhatsApp API message ID
    $table->enum('conversation_state', [
        'INTRO', 'DISCOVERY', 'OBJECTION_HANDLING', 
        'SOFT_CLOSE', 'HARD_STOP', 'CLOSED'
    ])->default('INTRO');
    $table->timestamp('followup_attempt_at')->nullable();
    $table->timestamp('followup_scheduled_by_customer')->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('ai_model_used')->nullable(); // Track which AI model
    $table->json('ai_response_metadata')->nullable(); // AI response data
    $table->timestamps();
    
    $table->index(['lead_id', 'created_at']);
    $table->index(['is_active', 'followup_attempt_at']);
    $table->index('conversation_state');
});
```

### 1.7. handoffs Table Implementation

```php
// database/migrations/2025_11_19_000005_create_handoffs_table.php
Schema::create('handoffs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Product being handed off
    $table->enum('handoff_reason', [
        'CALL_REQUEST', 'MEETING_REQUEST', 
        'AI_HARDSHIP', 'QUALIFIED_WIN'
    ]);
    $table->text('ai_summary');
    $table->jsonb('meeting_invite_data')->nullable();
    $table->enum('status', [
        'PENDING', 'CLAIMED', 'CLOSED_WON', 'CLOSED_LOST'
    ])->default('PENDING');
    $table->foreignId('claimed_by_user_id')->nullable()
          ->constrained('users')->onDelete('set null');
    $table->timestamp('claimed_at')->nullable();
    $table->timestamp('sla_deadline'); // 4 hours from creation
    $table->boolean('sla_breached')->default(false);
    $table->timestamps();
    
    $table->index(['status', 'created_at']);
    $table->index('sla_deadline');
    $table->index('lead_id');
});
```

### 1.8. outreach_variants Table Implementation

```php
// database/migrations/2025_11_19_000006_create_outreach_variants_table.php
Schema::create('outreach_variants', function (Blueprint $table) {
    $table->id();
    $table->string('variant_key', 10); // A, B, C
    $table->string('variant_name');
    $table->text('message_template');
    $table->boolean('is_active')->default(true);
    $table->integer('usage_count')->default(0);
    $table->decimal('conversion_rate', 5, 2)->default(0);
    $table->timestamps();
    
    $table->unique('variant_key');
});
```

### 1.8. AI Sales Agent Configuration Table

```php
// database/migrations/2025_11_19_000007_create_ai_sales_agents_table.php
Schema::create('ai_sales_agents', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    
    // Basic Information
    $table->string('assistant_name');
    $table->enum('target_audience', ['small-businesses', 'medium-businesses', 'enterprises', 'individuals', 'mixed']);
    $table->json('target_user_types'); // Array of user_type IDs
    $table->json('industries')->nullable(); // ['retail', 'hospitality', 'healthcare', 'education', 'finance', 'technology', 'other']
    $table->enum('communication_tone', ['professional', 'friendly', 'consultative', 'direct']);
    $table->text('personality_description')->nullable();
    
    // Working Hours
    $table->boolean('always_available')->default(false);
    $table->json('business_days')->nullable(); // ['monday', 'tuesday', ...]
    $table->time('start_time')->nullable();
    $table->time('end_time')->nullable();
    $table->string('timezone')->default('UTC');
    $table->text('out_of_hours_message')->nullable();
    
    // Languages
    $table->enum('primary_language', ['en', 'sw', 'fr', 'ar', 'pt', 'am']);
    $table->json('additional_languages')->nullable(); // ['sw', 'fr', 'ar', 'pt', 'am', 'yo', 'ig', 'ha']
    $table->boolean('auto_detect_language')->default(false);
    $table->text('language_fallback_message')->nullable();
    
    // Negotiation Settings
    $table->boolean('allow_negotiation')->default(true);
    $table->integer('max_discount_allowed')->nullable(); // 0-50%
    $table->boolean('accept_installments')->default(false);
    $table->integer('max_installments')->nullable(); // 2-12
    $table->integer('min_down_payment')->nullable(); // 10-100%
    $table->boolean('stop_orders_low_stock')->default(false);
    $table->integer('low_stock_threshold')->nullable(); // 1-100
    $table->text('negotiation_script')->nullable();
    
    // Fallback & Escalation
    $table->string('fallback_number', 20);
    $table->string('fallback_person')->nullable();
    $table->json('escalation_triggers')->nullable(); // ['complex-questions', 'complaints', 'large-orders', 'payment-issues', 'angry-customer']
    $table->decimal('large_order_threshold', 10, 2)->nullable();
    
    // Follow-up Settings
    $table->boolean('auto_followup')->default(true);
    $table->integer('followup_delay')->nullable(); // 1-168 hours (max 1 week)
    $table->integer('max_followups')->nullable(); // 1-5
    $table->text('followup_message')->nullable();
    
    // Notifications
    $table->boolean('notify_on_deal')->default(true);
    $table->json('notification_methods')->nullable(); // ['whatsapp', 'email', 'sms']
    $table->json('additional_notifications')->nullable(); // ['new-lead', 'escalation', 'errors']
    
    // Status & Terms
    $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
    $table->boolean('accepted_terms')->default(false);
    $table->timestamp('terms_accepted_at')->nullable();
    
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->index(['user_id', 'status']);
    $table->index('target_audience');
});
```

### 1.9. Enhanced incoming_messages Table (For Webhook Fallback)

```php
// database/migrations/2025_11_19_000007_update_incoming_messages_table.php
Schema::table('incoming_messages', function (Blueprint $table) {
    // Add fields for webhook processing tracking
    $table->string('processing_method')->default('webhook')->after('processed'); // 'webhook', 'cron_fallback'
    $table->timestamp('failed_instant_at')->nullable()->after('processing_method');
    $table->integer('processing_attempts')->default(0)->after('failed_instant_at');
    $table->string('failure_reason')->nullable()->after('processing_attempts');
    $table->json('webhook_response')->nullable()->after('failure_reason');
    
    $table->index(['processed', 'processing_method']);
    $table->index('failed_instant_at');
});

// Add helper methods to IncomingMessage model
public function markAsProcessed($reason = null)
{
    $this->update([
        'processed' => true,
        'processed_at' => now(),
        'failure_reason' => $reason
    ]);
}

public function isWebhookFallback()
{
    return $this->processing_method === 'cron_fallback';
}

public function hasExceededAttempts($maxAttempts = 3)
{
    return $this->processing_attempts >= $maxAttempts;
}
```


## 2. Eloquent Models Implementation

### 2.1. Product Model

```php
// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'ai_description', 'base_price', 'max_discount_percentage',
        'category', 'target_industry', 'key_features', 'common_objections',
        'sales_cycle_days', 'is_active', 'requires_demo', 'has_trial', 'trial_days',
        'setup_fee', 'billing_frequency', 'upsell_products'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'max_discount_percentage' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'key_features' => 'array',
        'common_objections' => 'array',
        'upsell_products' => 'array',
        'is_active' => 'boolean',
        'requires_demo' => 'boolean',
        'has_trial' => 'boolean',
    ];

    // Relationships
    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function handoffs()
    {
        return $this->hasMany(Handoff::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForIndustry($query, $industry)
    {
        return $query->where('target_industry', $industry)
                    ->orWhereNull('target_industry');
    }

    // Helper methods
    public function getAiDescription()
    {
        return $this->ai_description ?: $this->description;
    }

    public function getMaxDiscountAmount()
    {
        return ($this->base_price * $this->max_discount_percentage) / 100;
    }

    public function getMinimumPrice()
    {
        return $this->base_price - $this->getMaxDiscountAmount();
    }

    public function canApplyDiscount($discountPercentage)
    {
        return $discountPercentage <= $this->max_discount_percentage;
    }

    public function calculateDiscountedPrice($discountPercentage)
    {
        if (!$this->canApplyDiscount($discountPercentage)) {
            return $this->base_price;
        }
        
        $discountAmount = ($this->base_price * $discountPercentage) / 100;
        return $this->base_price - $discountAmount;
    }

    public function getKeyFeaturesAsString()
    {
        return $this->key_features ? implode(', ', $this->key_features) : '';
    }

    public function hasUpsellProducts()
    {
        return !empty($this->upsell_products);
    }

    public function getUpsellProducts()
    {
        if (!$this->hasUpsellProducts()) {
            return collect();
        }
        
        return self::whereIn('id', $this->upsell_products)->active()->get();
    }

    // Analytics methods
    public function getConversionRate()
    {
        $totalLeadProducts = $this->leadProducts()->count();
        $closedWon = $this->leadProducts()->where('status', 'CLOSED_WON')->count();
        
        return $totalLeadProducts > 0 ? ($closedWon / $totalLeadProducts) * 100 : 0;
    }

    public function getAverageSellingPrice()
    {
        return $this->leadProducts()
                   ->where('status', 'CLOSED_WON')
                   ->whereNotNull('final_price')
                   ->avg('final_price') ?: $this->base_price;
    }

    public function getAverageSalesCycle()
    {
        $closedDeals = $this->leadProducts()
                           ->where('status', 'CLOSED_WON')
                           ->with('lead')
                           ->get();
        
        if ($closedDeals->count() === 0) {
            return $this->sales_cycle_days;
        }
        
        $totalDays = 0;
        foreach ($closedDeals as $deal) {
            $totalDays += $deal->created_at->diffInDays($deal->updated_at);
        }
        
        return $totalDays / $closedDeals->count();
    }
}
```

### 2.2. LeadProduct Model

```php
// app/Models/LeadProduct.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadProduct extends Model
{
    protected $fillable = [
        'lead_id', 'product_id', 'status', 'quoted_price', 'final_price',
        'discount_applied', 'objections_raised', 'features_discussed',
        'last_interaction_at', 'demo_scheduled_date', 'proposal_sent_date',
        'follow_up_date', 'notes', 'is_primary_product', 'interaction_count', 'metadata'
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'objections_raised' => 'array',
        'features_discussed' => 'array',
        'last_interaction_at' => 'datetime',
        'demo_scheduled_date' => 'date',
        'proposal_sent_date' => 'date',
        'follow_up_date' => 'date',
        'is_primary_product' => 'boolean',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_INTERESTED = 'INTERESTED';
    const STATUS_PITCHED = 'PITCHED';
    const STATUS_DEMO_REQUESTED = 'DEMO_REQUESTED';
    const STATUS_DEMO_COMPLETED = 'DEMO_COMPLETED';
    const STATUS_PROPOSAL_SENT = 'PROPOSAL_SENT';
    const STATUS_NEGOTIATING = 'NEGOTIATING';
    const STATUS_OBJECTION_RAISED = 'OBJECTION_RAISED';
    const STATUS_CLOSED_WON = 'CLOSED_WON';
    const STATUS_CLOSED_LOST = 'CLOSED_LOST';
    const STATUS_ON_HOLD = 'ON_HOLD';
    const STATUS_FOLLOW_UP_LATER = 'FOLLOW_UP_LATER';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED_WON, self::STATUS_CLOSED_LOST]);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary_product', true);
    }

    public function scopeReadyForDemo($query)
    {
        return $query->where('status', self::STATUS_DEMO_REQUESTED);
    }

    public function scopeInNegotiation($query)
    {
        return $query->whereIn('status', [self::STATUS_NEGOTIATING, self::STATUS_OBJECTION_RAISED]);
    }

    // Helper methods
    public function recordInteraction($notes = null, $featuresDiscussed = [])
    {
        $this->increment('interaction_count');
        $this->update([
            'last_interaction_at' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
            'features_discussed' => array_unique(array_merge($this->features_discussed ?: [], $featuresDiscussed))
        ]);
    }

    public function addObjection($objection)
    {
        $objections = $this->objections_raised ?: [];
        $objections[] = [
            'objection' => $objection,
            'raised_at' => now()->toISOString()
        ];
        
        $this->update([
            'objections_raised' => $objections,
            'status' => self::STATUS_OBJECTION_RAISED
        ]);
    }

    public function scheduleDemo($date)
    {
        $this->update([
            'demo_scheduled_date' => $date,
            'status' => self::STATUS_DEMO_REQUESTED
        ]);
    }

    public function markDemoCompleted()
    {
        $this->update(['status' => self::STATUS_DEMO_COMPLETED]);
    }

    public function sendProposal($quotedPrice, $discountApplied = 0)
    {
        $this->update([
            'status' => self::STATUS_PROPOSAL_SENT,
            'proposal_sent_date' => now()->toDateString(),
            'quoted_price' => $quotedPrice,
            'discount_applied' => $discountApplied
        ]);
    }

    public function closeAsWon($finalPrice)
    {
        $this->update([
            'status' => self::STATUS_CLOSED_WON,
            'final_price' => $finalPrice
        ]);
    }

    public function closeAsLost($reason = null)
    {
        $metadata = $this->metadata ?: [];
        $metadata['lost_reason'] = $reason;
        $metadata['lost_date'] = now()->toISOString();
        
        $this->update([
            'status' => self::STATUS_CLOSED_LOST,
            'metadata' => $metadata
        ]);
    }

    public function makePrimary()
    {
        // Remove primary flag from other products for this lead
        self::where('lead_id', $this->lead_id)
           ->where('id', '!=', $this->id)
           ->update(['is_primary_product' => false]);
           
        $this->update(['is_primary_product' => true]);
    }

    public function isActive()
    {
        return !in_array($this->status, [self::STATUS_CLOSED_WON, self::STATUS_CLOSED_LOST]);
    }

    public function getDaysInCurrentStatus()
    {
        return $this->updated_at->diffInDays(now());
    }
}
```

### 2.3. AiSalesAgent Model

```php
// app/Models/AiSalesAgent.php
<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Carbon\\Carbon;

class AiSalesAgent extends Model
{
    protected $fillable = [
        'user_id', 'assistant_name', 'target_audience', 'target_user_types', 'industries',
        'communication_tone', 'personality_description', 'always_available', 'business_days',
        'start_time', 'end_time', 'timezone', 'out_of_hours_message', 'primary_language',
        'additional_languages', 'auto_detect_language', 'language_fallback_message',
        'allow_negotiation', 'max_discount_allowed', 'accept_installments', 'max_installments',
        'min_down_payment', 'stop_orders_low_stock', 'low_stock_threshold', 'negotiation_script',
        'fallback_number', 'fallback_person', 'escalation_triggers', 'large_order_threshold',
        'auto_followup', 'followup_delay', 'max_followups', 'followup_message',
        'notify_on_deal', 'notification_methods', 'additional_notifications',
        'status', 'accepted_terms', 'terms_accepted_at'
    ];

    protected $casts = [
        'target_user_types' => 'array',
        'industries' => 'array',
        'business_days' => 'array',
        'additional_languages' => 'array',
        'escalation_triggers' => 'array',
        'notification_methods' => 'array',
        'additional_notifications' => 'array',
        'always_available' => 'boolean',
        'auto_detect_language' => 'boolean',
        'allow_negotiation' => 'boolean',
        'accept_installments' => 'boolean',
        'stop_orders_low_stock' => 'boolean',
        'auto_followup' => 'boolean',
        'notify_on_deal' => 'boolean',
        'accepted_terms' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'max_discount_allowed' => 'integer',
        'max_installments' => 'integer',
        'min_down_payment' => 'integer',
        'low_stock_threshold' => 'integer',
        'followup_delay' => 'integer',
        'max_followups' => 'integer',
        'large_order_threshold' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    // Scopes  
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Business logic methods
    public function isAvailableNow()
    {
        if ($this->always_available) {
            return true;
        }

        $now = Carbon::now($this->timezone);
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        return in_array($currentDay, $this->business_days ?? []) &&
               $currentTime >= $this->start_time &&
               $currentTime <= $this->end_time;
    }

    public function canNegotiate($requestedDiscount)
    {
        return $this->allow_negotiation && 
               ($this->max_discount_allowed >= $requestedDiscount);
    }

    public function shouldEscalate($trigger)
    {
        return in_array($trigger, $this->escalation_triggers ?? []);
    }

    public function getPersonalityPrompt()
    {
        $tone = match($this->communication_tone) {
            'professional' => 'Maintain a professional, business-focused tone',
            'friendly' => 'Be warm, approachable, and conversational',
            'consultative' => 'Act as a trusted advisor, asking thoughtful questions',
            'direct' => 'Be clear, concise, and straight to the point'
        };
        
        $personality = $this->personality_description ?: "I am {$this->assistant_name}, a helpful sales assistant.";
        
        return "{$personality} {$tone}. Target audience: {$this->target_audience}.";
    }

    public function getOutOfHoursResponse()
    {
        return $this->out_of_hours_message ?: 
               "Thank you for your message! I'm currently unavailable, but I'll respond during business hours: {$this->start_time} - {$this->end_time} {$this->timezone}.";
    }
}
```

### 2.4. Lead Model (Updated for AI Agent Integration)

```php
// app/Models/Lead.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Lead extends Model
{
    protected $fillable = [
        'events_guest_id', 'ai_sales_agent_id', 'company_name', 'industry',
        'lead_score', 'status', 'last_outreach_at', 'timezone',
        'source', 'metadata', 'is_churned', 'churn_date', 'churn_reason'
    ];

    protected $casts = [
        'last_outreach_at' => 'datetime',
        'churn_date' => 'date',
        'metadata' => 'array',
        'is_churned' => 'boolean',
    ];

    // Status constants
    const STATUS_NEW = 'NEW';
    const STATUS_QUEUED = 'QUEUED';
    const STATUS_OUTREACHED = 'OUTREACHED';
    const STATUS_REPLIED = 'REPLIED';
    const STATUS_HANDED_OFF = 'HANDED_OFF';
    const STATUS_DO_NOT_CONTACT = 'DO_NOT_CONTACT';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_WIN_BACK = 'WIN_BACK';

    // Relationships
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function handoffs()
    {
        return $this->hasMany(Handoff::class);
    }

    public function contact()
    {
        return $this->belongsTo(EventsGuest::class, 'events_guest_id');
    }

    public function aiSalesAgent()
    {
        return $this->belongsTo(AiSalesAgent::class);
    }

    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lead_products')
                   ->withPivot(['status', 'quoted_price', 'final_price', 'discount_applied', 
                               'is_primary_product', 'last_interaction_at'])
                   ->withTimestamps();
    }

    // Scopes
    public function scopeNewLeads($query)
    {
        return $query->where('status', self::STATUS_NEW)
                    ->where('lead_score', '>', 0);
    }

    public function scopeForOutreach($query, $limit = 50)
    {
        return $query->newLeads()
                    ->whereNotIn('status', [self::STATUS_DO_NOT_CONTACT, self::STATUS_CLOSED])
                    ->where(function($q) {
                        $q->whereNull('last_outreach_at')
                          ->orWhere('last_outreach_at', '<', now()->startOfDay());
                    })
                    ->orderBy('lead_score', 'desc')
                    ->limit($limit);
    }

    public function scopeOutreachedWithoutReply($query, $days = 5)
    {
        return $query->where('status', self::STATUS_OUTREACHED)
                    ->where('last_outreach_at', '<', now()->subDays($days));
    }

    public function scopeChurnedLeads($query)
    {
        return $query->where('is_churned', true)
                    ->where('status', self::STATUS_WIN_BACK);
    }

    public function scopeWinBackEligible($query, $daysSinceChurn = 30)
    {
        return $query->churnedLeads()
                    ->where('churn_date', '>=', now()->subDays($daysSinceChurn * 6)) // Max 6 months back
                    ->where('churn_date', '<=', now()->subDays($daysSinceChurn)) // Min 30 days back
                    ->where(function($q) {
                        $q->whereNull('last_outreach_at')
                          ->orWhere('last_outreach_at', '<', now()->subDays(14)); // Last outreach > 14 days
                    });
    }

    // Helper methods
    public function isInBusinessHours()
    {
        $localTime = Carbon::now($this->timezone);
        $hour = $localTime->hour;
        $minute = $localTime->minute;
        
        $startTime = 9.5; // 09:30
        $endTime = 16.5;  // 16:30
        $currentTime = $hour + ($minute / 60);
        
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function getNextBusinessHour()
    {
        $localTime = Carbon::now($this->timezone);
        
        // If it's the same day and before business hours, return business start time
        if ($localTime->hour < 9 || ($localTime->hour == 9 && $localTime->minute < 30)) {
            return $localTime->setTime(9, 30);
        }
        
        // If it's after business hours or weekend, return next business day 9:30 AM
        $nextBusinessDay = $localTime->addDay();
        
        // Skip weekends
        while ($nextBusinessDay->isWeekend()) {
            $nextBusinessDay->addDay();
        }
        
        return $nextBusinessDay->setTime(9, 30);
    }

    public function getActiveConversation()
    {
        return $this->conversations()
                   ->where('is_active', true)
                   ->latest()
                   ->first();
    }

    public function getConversationHistory()
    {
        return $this->conversations()
                   ->orderBy('created_at')
                   ->get();
    }

    // Helper methods for contact information
    public function getContactName()
    {
        return $this->contact ? $this->contact->guest_name : 'Unknown';
    }

    public function getContactPhone()
    {
        return $this->contact ? $this->contact->guest_phone : null;
    }

    public function getContactEmail()
    {
        return $this->contact ? $this->contact->guest_email : null;
    }

    // Product-related helper methods
    public function getPrimaryProduct()
    {
        $primaryLeadProduct = $this->leadProducts()->where('is_primary_product', true)->first();
        return $primaryLeadProduct ? $primaryLeadProduct->product : $this->leadProducts()->first()?->product;
    }

    public function getPrimaryProductName()
    {
        $product = $this->getPrimaryProduct();
        return $product ? $product->name : 'No Product';
    }

    public function getPrimaryProductPrice()
    {
        $product = $this->getPrimaryProduct();
        return $product ? $product->base_price : 0;
    }

    public function getActiveProducts()
    {
        return $this->leadProducts()->active()->with('product')->get()->pluck('product');
    }

    public function addProduct($productId, $isPrimary = false)
    {
        // Check if product already exists for this lead
        $existingLeadProduct = $this->leadProducts()->where('product_id', $productId)->first();
        if ($existingLeadProduct) {
            return $existingLeadProduct;
        }

        $leadProduct = $this->leadProducts()->create([
            'product_id' => $productId,
            'status' => LeadProduct::STATUS_INTERESTED,
            'is_primary_product' => $isPrimary
        ]);

        if ($isPrimary) {
            $leadProduct->makePrimary();
        }

        return $leadProduct;
    }

    public function getProductStatus($productId)
    {
        $leadProduct = $this->leadProducts()->where('product_id', $productId)->first();
        return $leadProduct ? $leadProduct->status : null;
    }

    public function updateProductStatus($productId, $status, $additionalData = [])
    {
        $leadProduct = $this->leadProducts()->where('product_id', $productId)->first();
        if ($leadProduct) {
            $leadProduct->update(array_merge(['status' => $status], $additionalData));
            return $leadProduct;
        }
        return null;
    }

    public function hasActiveProductInterest()
    {
        return $this->leadProducts()->active()->exists();
    }

    public function getProductInterestSummary()
    {
        return $this->leadProducts()->with('product')->get()->map(function($lp) {
            return [
                'product_name' => $lp->product->name,
                'status' => $lp->status,
                'is_primary' => $lp->is_primary_product,
                'last_interaction' => $lp->last_interaction_at,
                'interaction_count' => $lp->interaction_count
            ];
        });
    }

    // Churned customer helper methods
    public function markAsChurned($churnReason, $churnDate = null)
    {
        $this->update([
            'is_churned' => true,
            'churn_date' => $churnDate ?: now()->toDateString(),
            'churn_reason' => $churnReason,
            'status' => self::STATUS_WIN_BACK,
            'lead_score' => $this->calculateWinBackScore($churnReason)
        ]);
    }

    public function calculateWinBackScore($churnReason)
    {
        $baseScore = 40; // Lower than new leads
        
        // Adjust based on churn reason
        switch (strtolower($churnReason)) {
            case 'price':
            case 'cost':
            case 'budget':
                $baseScore += 25; // Price objections can be addressed
                break;
            case 'features':
            case 'functionality':
                $baseScore += 20; // Feature gaps might be filled
                break;
            case 'support':
            case 'service':
                $baseScore += 10; // Service issues harder to overcome
                break;
            case 'competitor':
                $baseScore += 15; // Competition can be addressed
                break;
            default:
                $baseScore += 5;
        }
        
        // Adjust based on time since churn
        $daysSinceChurn = now()->diffInDays($this->churn_date);
        if ($daysSinceChurn < 60) {
            $baseScore += 10; // Recent churns are more responsive
        } elseif ($daysSinceChurn < 120) {
            $baseScore += 5;
        }
        
        return min($baseScore, 85); // Cap win-back scores lower than new leads
    }

    public function getDaysSinceChurn()
    {
        return $this->churn_date ? now()->diffInDays($this->churn_date) : null;
    }

    // Lead creation helper
    public static function createForContactWithProducts($contactId, $productIds, $primaryProductId = null, $additionalData = [])
    {
        $contact = EventsGuest::find($contactId);
        if (!$contact) {
            throw new \Exception('Invalid contact');
        }

        // Check if lead already exists for this contact
        $existingLead = self::where('events_guest_id', $contactId)
                          ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_DO_NOT_CONTACT])
                          ->first();
        
        if ($existingLead) {
            // Add new products to existing lead
            foreach ($productIds as $productId) {
                $isPrimary = ($productId == $primaryProductId);
                $existingLead->addProduct($productId, $isPrimary);
            }
            return $existingLead;
        }

        // Create new lead
        $lead = self::create(array_merge([
            'events_guest_id' => $contactId,
            'lead_score' => 50, // Default score
            'timezone' => 'Africa/Nairobi', // Default timezone
            'source' => 'system'
        ], $additionalData));

        // Add products to lead
        foreach ($productIds as $productId) {
            $isPrimary = ($productId == $primaryProductId) || (count($productIds) === 1);
            $lead->addProduct($productId, $isPrimary);
        }

        return $lead;
    }

    // Legacy method for backward compatibility
    public static function createForContactAndProduct($contactId, $productId, $additionalData = [])
    {
        return self::createForContactWithProducts($contactId, [$productId], $productId, $additionalData);
    }
}
```

### 2.2. Enhanced EventsGuest Model Integration

```php
// Add this to app/Models/EventsGuest.php or create if needed
// Add these relationships to work with the lead system

// In EventsGuest model, add these methods:
public function leads()
{
    return $this->hasMany(Lead::class, 'events_guest_id');
}

public function activeLeads()
{
    return $this->leads()
               ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_DO_NOT_CONTACT]);
}

public function hasActiveLeadForProduct($productId)
{
    return $this->activeLeads()
               ->where('product_id', $productId)
               ->exists();
}

public function getLeadForProduct($productId)
{
    return $this->leads()
               ->where('product_id', $productId)
               ->latest()
               ->first();
}

// Helper to clean phone number for WhatsApp
public function getCleanPhone()
{
    return preg_replace('/[^0-9]/', '', $this->guest_phone);
}
```

### 2.3. Enhanced Product Model Integration

```php
// Add this to app/Models/Product.php or enhance existing
// Add these relationships and methods for AI agent integration

// In Product model, add these methods:
public function leads()
{
    return $this->hasMany(Lead::class);
}

public function activeLeads()
{
    return $this->leads()
               ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_DO_NOT_CONTACT]);
}

public function getConversionRate()
{
    $totalLeads = $this->leads()->count();
    $closedLeads = $this->leads()->where('status', Lead::STATUS_CLOSED)->count();
    
    return $totalLeads > 0 ? ($closedLeads / $totalLeads) * 100 : 0;
}

public function getAverageSellingPrice()
{
    return $this->leads()
               ->where('status', Lead::STATUS_CLOSED)
               ->whereNotNull('final_price')
               ->avg('final_price') ?: $this->retail_price;
}

public function canBeDiscountedTo($targetPrice)
{
    $maxDiscountAmount = ($this->retail_price * $this->max_discount) / 100;
    $minimumPrice = $this->retail_price - $maxDiscountAmount;
    
    return $targetPrice >= $minimumPrice;
}

// Get AI-optimized product description
public function getAiDescription()
{
    return $this->ai_generated_description 
           ? $this->description 
           : $this->minimal_description ?: $this->description;
}
```

### 2.2. Conversation Model

```php
// app/Models/Conversation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'lead_id', 'message_type', 'message_content', 'outbound_ref',
        'conversation_state', 'followup_attempt_at', 
        'followup_scheduled_by_customer', 'is_active',
        'ai_model_used', 'ai_response_metadata'
    ];

    protected $casts = [
        'followup_attempt_at' => 'datetime',
        'followup_scheduled_by_customer' => 'datetime',
        'is_active' => 'boolean',
        'ai_response_metadata' => 'array',
    ];

    // Message type constants
    const TYPE_AI = 'AI';
    const TYPE_CUSTOMER = 'CUSTOMER';

    // Conversation state constants
    const STATE_INTRO = 'INTRO';
    const STATE_DISCOVERY = 'DISCOVERY';
    const STATE_OBJECTION_HANDLING = 'OBJECTION_HANDLING';
    const STATE_SOFT_CLOSE = 'SOFT_CLOSE';
    const STATE_HARD_STOP = 'HARD_STOP';
    const STATE_CLOSED = 'CLOSED';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // Scopes
    public function scopeActiveConversations($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePendingFollowup($query)
    {
        return $query->activeConversations()
                    ->whereNotNull('followup_attempt_at')
                    ->where('followup_attempt_at', '<=', now());
    }

    public function scopeAiMessages($query)
    {
        return $query->where('message_type', self::TYPE_AI);
    }

    public function scopeCustomerMessages($query)
    {
        return $query->where('message_type', self::TYPE_CUSTOMER);
    }
}
```

### 2.3. Handoff Model

```php
// app/Models/Handoff.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Handoff extends Model
{
    protected $fillable = [
        'lead_id', 'handoff_reason', 'ai_summary', 'meeting_invite_data',
        'status', 'claimed_by_user_id', 'claimed_at', 'sla_deadline',
        'sla_breached'
    ];

    protected $casts = [
        'meeting_invite_data' => 'array',
        'claimed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_CLAIMED = 'CLAIMED';
    const STATUS_CLOSED_WON = 'CLOSED_WON';
    const STATUS_CLOSED_LOST = 'CLOSED_LOST';

    // Handoff reason constants
    const REASON_CALL_REQUEST = 'CALL_REQUEST';
    const REASON_MEETING_REQUEST = 'MEETING_REQUEST';
    const REASON_AI_HARDSHIP = 'AI_HARDSHIP';
    const REASON_QUALIFIED_WIN = 'QUALIFIED_WIN';
    const REASON_SALE_COMPLETED = 'SALE_COMPLETED';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function leadProduct()
    {
        return $this->hasOne(LeadProduct::class, function($query) {
            $query->where('lead_id', $this->lead_id)
                  ->where('product_id', $this->product_id);
        });
    }

    public function claimedByUser()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    // Boot method to set SLA deadline
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($handoff) {
            $handoff->sla_deadline = Carbon::now()->addHours(4);
        });
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSlaBreached($query)
    {
        return $query->where('sla_deadline', '<', now())
                    ->where('sla_breached', false);
    }

    public function scopeUnclaimed($query)
    {
        return $query->pending()->whereNull('claimed_by_user_id');
    }

    // Helper methods
    public function claim($userId)
    {
        $this->update([
            'status' => self::STATUS_CLAIMED,
            'claimed_by_user_id' => $userId,
            'claimed_at' => now()
        ]);
    }

    public function isOverdue()
    {
        return $this->sla_deadline->isPast() && $this->status === self::STATUS_PENDING;
    }
}
```

## 3. AI Integration Services

### 3.1. OpenAI API Service

```php
// app/Services/OpenAiService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Lead;
use App\Models\Conversation;

class OpenAiService
{
    protected $apiKey;
    protected $apiUrl;
    protected $model;
    protected $temperature;
    protected $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = config('services.openai.api_url');
        $this->model = config('services.openai.model', 'gpt-4o');
        $this->temperature = config('services.openai.temperature', 0.7);
        $this->maxTokens = config('services.openai.max_tokens', 1024);
    }

    /**
     * AI Role 1: Response Generator (Core Chat Logic)
     */
    public function generateResponse(Lead $lead, $productId, $customerMessage = null, $currentState = 'INTRO')
    {
        try {
            $conversationHistory = $this->buildConversationHistory($lead);
            $prompt = $this->buildResponsePrompt($lead, $conversationHistory, $customerMessage, $currentState);
            
            $response = $this->callOpenAiApi($prompt);
            
            // Parse JSON response from AI
            $aiResponse = json_decode($response, true);
            
            if (!$aiResponse || !isset($aiResponse['message_text'])) {
                throw new \Exception('Invalid AI response format');
            }

            return [
                'message_text' => $aiResponse['message_text'],
                'new_conversation_state' => $aiResponse['new_conversation_state'] ?? $currentState,
                'handoff_flag' => $aiResponse['handoff_flag'] ?? false,
                'handoff_reason_code' => $aiResponse['handoff_reason_code'] ?? null,
                'raw_response' => $response
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Response Generation Failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'customer_message' => $customerMessage
            ]);
            throw $e;
        }
    }

    /**
     * AI Role 2: Handoff Summarizer  
     */
    public function generateHandoffSummary(Lead $lead, $productId, $handoffReason)
    {
        try {
            $conversationHistory = $this->buildConversationHistory($lead, $productId);
            $prompt = $this->buildSummaryPrompt($lead, $productId, $conversationHistory, $handoffReason);
            
            $summary = $this->callOpenAiApi($prompt);
            
            return trim($summary);

        } catch (\Exception $e) {
            Log::error('OpenAI Summary Generation Failed', [
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'handoff_reason' => $handoffReason
            ]);
            throw $e;
        }
    }

    /**
     * Build conversation history for AI context
     */
    private function buildConversationHistory(Lead $lead, $productId = null)
    {
        $query = $lead->conversations()->orderBy('created_at');
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        $conversations = $query->get();
        
        $history = [];
        foreach ($conversations as $conv) {
            $history[] = [
                'type' => $conv->message_type,
                'message' => $conv->message_content,
                'timestamp' => $conv->created_at->toISOString(),
                'state' => $conv->conversation_state
            ];
        }
        
        return $history;
    }

    /**
     * Build prompt for response generation
     */
    private function buildResponsePrompt(Lead $lead, $conversationHistory, $customerMessage, $currentState)
    {
        $isChurned = $lead->is_churned;
        
        $systemInstruction = $isChurned ? 
            "Act as a professional, empathetic, and understanding B2B sales representative conducting a WIN-BACK campaign. " .
            "This customer previously used our service but left for a specific reason. Your goal is to acknowledge their past relationship, " .
            "understand what went wrong, and present solutions or improvements that address their concerns. " .
            "DO NOT treat them as a new prospect - acknowledge their history with us. " .
            "Focus on what has changed or improved since they left. Be humble about past issues and confident about current solutions. " .
            "If they show interest in returning, offer special win-back incentives or trial periods."
            :
            "Act as a professional, empathetic, and persistent B2B sales development representative. " .
            "Your goal is to qualify the lead and close the sale or book a meeting. " .
            "You can negotiate price up to the maximum discount allowed for the product. " .
            "If customer agrees to purchase, set conversation state to CLOSED and trigger handoff for order processing. " .
            "If the customer shows any sign of hardship, explicit resistance, or requests a specific meeting/call time, trigger a Handoff Flag. " .
            "If the lead is unresponsive or resistant, pivot by asking for a specific future date and time for follow-up.";

        // Get current product and lead product relationship
        $currentProduct = Product::find($productId);
        $leadProduct = $lead->leadProducts()->where('product_id', $productId)->first();
        
        $leadContext = "Lead Information:\n" .
                      "- Name: " . $lead->getContactName() . "\n" .
                      "- Company: " . ($lead->company_name ?: 'Unknown') . "\n" .
                      "- Industry: " . ($lead->industry ?: 'Unknown') . "\n" .
                      "- Phone: " . $lead->getContactPhone() . "\n" .
                      "- Current Conversation State: " . $currentState . "\n" .
                      "- Is Churned Customer: " . ($lead->is_churned ? 'YES' : 'NO') . "\n" .
                      ($lead->is_churned ? 
                        "- Churn Date: " . $lead->churn_date . "\n" .
                        "- Churn Reason: " . ($lead->churn_reason ?: 'Unknown') . "\n" .
                        "- Days Since Churn: " . $lead->getDaysSinceChurn() . "\n"
                        : '') . "\n" .
                      "CURRENT PRODUCT FOCUS:\n" .
                      "- Product: " . ($currentProduct ? $currentProduct->name : 'Unknown') . "\n" .
                      "- Description: " . ($currentProduct ? $currentProduct->getAiDescription() : 'N/A') . "\n" .
                      "- Base Price: $" . ($currentProduct ? $currentProduct->base_price : 0) . "\n" .
                      "- Max Discount: " . ($currentProduct ? $currentProduct->max_discount_percentage : 0) . "%\n" .
                      "- Key Features: " . ($currentProduct ? $currentProduct->getKeyFeaturesAsString() : 'N/A') . "\n" .
                      "- Requires Demo: " . ($currentProduct && $currentProduct->requires_demo ? 'YES' : 'NO') . "\n" .
                      "- Has Trial: " . ($currentProduct && $currentProduct->has_trial ? 'YES (' . $currentProduct->trial_days . ' days)' : 'NO') . "\n" .
                      ($leadProduct ? 
                        "- Current Product Status: " . $leadProduct->status . "\n" .
                        "- Interactions Count: " . $leadProduct->interaction_count . "\n" .
                        "- Last Interaction: " . ($leadProduct->last_interaction_at ? $leadProduct->last_interaction_at->format('Y-m-d H:i') : 'Never') . "\n" .
                        ($leadProduct->quoted_price ? "- Quoted Price: $" . $leadProduct->quoted_price . "\n" : '') .
                        ($leadProduct->objections_raised ? "- Previous Objections: " . implode(', ', array_column($leadProduct->objections_raised, 'objection')) . "\n" : '') .
                        ($leadProduct->features_discussed ? "- Features Discussed: " . implode(', ', $leadProduct->features_discussed) . "\n" : '')
                        : '') . "\n" .
                      "OTHER PRODUCTS OF INTEREST:\n" .
                      $this->buildOtherProductsContext($lead, $productId);

        $conversationContext = "Conversation History:\n";
        foreach ($conversationHistory as $msg) {
            $conversationContext .= "- {$msg['type']}: {$msg['message']} (State: {$msg['state']})\n";
        }

        $newMessageContext = $customerMessage ? "New Customer Message: " . $customerMessage : "Generate follow-up message";

        $outputFormat = "You must respond with a valid JSON object in this exact format:\n" .
                       '{"message_text": "Your response to the customer", ' .
                       '"new_conversation_state": "INTRO|DISCOVERY|OBJECTION_HANDLING|SOFT_CLOSE|HARD_STOP|CLOSED", ' .
                       '"handoff_flag": true|false, ' .
                       '"handoff_reason_code": "null|CALL_REQUEST|MEETING_REQUEST|AI_HARDSHIP|SALE_COMPLETED", ' .
                       '"quoted_price": null|number, ' .
                       '"discount_offered": null|number}';  // Add pricing info

        return $systemInstruction . "\n\n" . $leadContext . "\n\n" . $conversationContext . "\n\n" . 
               $newMessageContext . "\n\n" . $outputFormat;
    }

    /**
     * Build context for other products the lead has shown interest in
     */
    private function buildOtherProductsContext(Lead $lead, $currentProductId)
    {
        $otherProducts = $lead->leadProducts()
                             ->where('product_id', '!=', $currentProductId)
                             ->with('product')
                             ->get();
        
        if ($otherProducts->isEmpty()) {
            return "- No other products discussed\n";
        }
        
        $context = "";
        foreach ($otherProducts as $lp) {
            $context .= "- " . $lp->product->name . " (Status: " . $lp->status . ", Interactions: " . $lp->interaction_count . ")\n";
        }
        
        return $context;
    }

    /**
     * Updated generateResponse method calls
     */
    private function updateGenerateResponseCalls()
    {
        // Update all calls to generateResponse to include productId
        // Example: $this->generateResponse($lead, $productId, $customerMessage, $currentState);
    }

    /**
     * Build prompt for summary generation with product context
     */
    private function buildSummaryPrompt(Lead $lead, $productId, $conversationHistory, $handoffReason)
    {
        $product = Product::find($productId);
        $leadProduct = $lead->leadProducts()->where('product_id', $productId)->first();
        
        $systemInstruction = "You are an internal analyst. Review the provided chat history for a specific product conversation. " .
                           "Generate a concise, 3-point bulleted summary designed for a human sales agent " .
                           "to understand the context immediately.";

        $leadContext = "Lead being handed off:\n" .
                      "- Name: " . $lead->getContactName() . "\n" .
                      "- Company: " . ($lead->company_name ?: 'Unknown') . "\n" .
                      "- Industry: " . ($lead->industry ?: 'Unknown') . "\n" .
                      "- Product: " . ($product ? $product->name : 'Unknown') . "\n" .
                      "- Product Status: " . ($leadProduct ? $leadProduct->status : 'Unknown') . "\n" .
                      "- Handoff Reason: " . $handoffReason;

        $conversationContext = "Product-Specific Conversation History:\n";
        foreach ($conversationHistory as $msg) {
            $conversationContext .= "- {$msg['type']}: {$msg['message']}\n";
        }

        $outputFormat = "Provide exactly 3 bullet points summarizing:\n" .
                       "• Lead's interest level and specific needs for " . ($product ? $product->name : 'this product') . "\n" .
                       "• Key objections or concerns raised about this product\n" .
                       "• Recommended next action for human agent regarding this product";

        return $systemInstruction . "\n\n" . $leadContext . "\n\n" . $conversationContext . "\n\n" . $outputFormat;
    }

    /**
     * Make API call to OpenAI with retry logic
     */
    private function callOpenAiApi($prompt, $retries = 3)
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post("{$this->apiUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }

                throw new \Exception('API request failed: ' . $response->body());

            } catch (\Exception $e) {
                Log::warning("OpenAI API attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);

                if ($attempt === $retries) {
                    throw $e;
                }

                // Exponential backoff
                sleep(pow(2, $attempt));
            }
        }
    }
}
```

### 3.2. Enhanced WhatsApp Service for AI Agent

```php
// app/Services/AiWhatsAppService.php
<?php

namespace App\Services;

use App\Services\WaSender API Service;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class AiWhatsAppService integrates with WaSender API
{
    /**
     * Send AI response to lead
     */
    public function sendAiMessage(Lead $lead, $messageText, $conversationState = 'INTRO', $aiMetadata = [])
    {
        try {
            // Send message via parent WhatsApp service
            $result = $this->sendTextMessage($lead->getContactPhone(), $messageText);
            
            if ($result['success']) {
                // Store conversation record
                Conversation::create([
                    'lead_id' => $lead->id,
                    'product_id' => $aiMetadata['product_id'] ?? $lead->getPrimaryProduct()?->id,
                    'message_type' => Conversation::TYPE_AI,
                    'message_content' => $messageText,
                    'outbound_ref' => $result['message_id'],
                    'conversation_state' => $conversationState,
                    'is_active' => true,
                    'ai_model_used' => config('services.openai.model'),
                    'ai_response_metadata' => $aiMetadata
                ]);

                Log::info('AI message sent successfully', [
                    'lead_id' => $lead->id,
                    'message_id' => $result['message_id'],
                    'state' => $conversationState
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send AI message', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'message' => $messageText
            ]);
            throw $e;
        }
    }

    /**
     * Send immediate acknowledgment
     */
    public function sendImmediateAck(Lead $lead, $customerMessage = null)
    {
        $ackMessages = [
            "Thanks {$lead->getContactName()}! I received your message and I'm preparing a detailed response. Give me just a moment...",
            "Hi {$lead->getContactName()}! Got your message, let me get you the perfect response right away!",
            "Hello! I'm processing your message about {$lead->getPrimaryProductName()}. One moment please..."
        ];
        
        $message = str_replace('{name}', $lead->getContactName(), $ackMessages[array_rand($ackMessages)]);
        return $this->sendTextMessage($lead->getContactPhone(), $message);
    }

    /**
     * Send out-of-hours message
     */
    public function sendOutOfHoursMessage(Lead $lead)
    {
        $timezone = $lead->timezone ?? 'Africa/Nairobi';
        $message = "Thanks for your message about {$lead->getPrimaryProductName()}! I'll get back to you during business hours (9:30 AM - 4:30 PM {$timezone}). Your inquiry is important to us!";
        
        return $this->sendTextMessage($lead->getContactPhone(), $message);
    }

    /**
     * Send initial outreach message with A/B testing
     */
    public function sendOutreachMessage(Lead $lead, $variant = 'A')
    {
        $templates = [
            'A' => "Hi {name}, I noticed {company_name} might benefit from our {product_name}. It's currently priced at ${product_price}. Could we chat for 5 minutes this week?",
            'B' => "Hello {name}! Quick question - are you looking for a solution like {product_name}? We're offering it at ${product_price}. What do you think?",
            'C' => "Hi {name}, I'm reaching out because I believe {product_name} could benefit {company_name}. Starting at ${product_price}, would you like to hear more?"
        ];

        $template = $templates[$variant] ?? $templates['A'];
        
        // Replace placeholders
        $message = str_replace(
            ['{name}', '{company_name}', '{industry}', '{product_name}', '{product_price}'],
            [
                $lead->getContactName(),
                $lead->company_name ?: 'your company', 
                $lead->industry ?: 'your industry',
                $lead->getPrimaryProductName(),
                $lead->getPrimaryProductPrice()
            ],
            $template
        );

        return $this->sendAiMessage($lead, $message, Conversation::STATE_INTRO, [
            'variant' => $variant,
            'template_used' => $template,
            'is_outreach' => true
        ]);
    }

    /**
     * Send follow-up chase message
     */
    public function sendChaseMessage(Lead $lead)
    {
        $chaseMessage = "Hi again! Did you receive my last message about " . $lead->getPrimaryProductName() . "? I understand you might be busy, but I believe it could really help " . 
                       ($lead->company_name ?: "your business") . ". Could we schedule just 5 minutes to chat?";

        return $this->sendAiMessage($lead, $chaseMessage, Conversation::STATE_INTRO, [
            'is_chase_message' => true
        ]);
    }
}
```

### 3.3. WhatsApp Webhook Controller (NEW - Instant Processing)

```php
// app/Http/Controllers/WaSender API.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Lead;
use App\Models\EventsGuest;
use App\Models\Conversation;
use App\Models\IncomingMessage;
use App\Services\OpenAiService;
use App\Services\AiWhatsAppService;
use App\Services\HandoffService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WaSender API extends Controller
{
    protected $openAiService;
    protected $whatsappService;
    protected $handoffService;

    public function __construct()
    {
        $this->openAiService = new OpenAiService();
        $this->whatsappService = new AiWhatsAppService();
        $this->handoffService = new HandoffService();
    }

    /**
     * Handle incoming WhatsApp messages with INSTANT AI processing
     */
    public function handleIncomingMessage(Request $request): JsonResponse
    {
        try {
            $phoneNumber = $request->input('from');
            $messageBody = $request->input('body');
            $messageId = $request->input('id');
            $timestamp = $request->input('timestamp');

            Log::info('Incoming WhatsApp message received', [
                'phone' => $phoneNumber,
                'message_id' => $messageId,
                'processing_method' => 'instant_webhook'
            ]);

            // Quick lead lookup
            $lead = $this->getLeadByPhone($phoneNumber);
            
            if (!$lead) {
                // Handle non-lead messages with existing logic
                return $this->handleNonLeadMessage($request);
            }

            // **INSTANT AI PROCESSING** - No waiting for cron
            return $this->processLeadMessageInstantly($lead, $messageBody, $messageId, $timestamp);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            // Fallback: Store for cron processing
            $this->storeForCronFallback($request);
            return response()->json(['status' => 'queued_for_retry'], 200);
        }
    }

    /**
     * Process lead message with instant AI response
     */
    private function processLeadMessageInstantly(Lead $lead, string $messageBody, string $messageId, $timestamp): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            // 1. Determine product context from message
            $productId = $this->determineProductContext($lead, $messageBody);
            
            // 2. Store customer message immediately
            $conversation = Conversation::create([
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'message_type' => Conversation::TYPE_CUSTOMER,
                'message_content' => $messageBody,
                'conversation_state' => $lead->getActiveConversation()?->conversation_state ?? 'INTRO',
                'is_active' => true,
                'ai_response_metadata' => [
                    'webhook_message_id' => $messageId,
                    'processing_method' => 'instant',
                    'timestamp' => $timestamp
                ]
            ]);

            // 3. Update lead status if first reply
            if ($lead->status === Lead::STATUS_OUTREACHED) {
                $lead->update(['status' => Lead::STATUS_REPLIED]);
            }

            // 4. **BUSINESS HOURS CHECK** - Smart response timing
            if (!$lead->isInBusinessHours()) {
                // Send immediate out-of-hours acknowledgment
                $ackResult = $this->whatsappService->sendOutOfHoursMessage($lead);
                
                // Schedule AI response for business hours
                $conversation->update([
                    'followup_attempt_at' => $lead->getNextBusinessHour()
                ]);
                
                DB::commit();
                return response()->json([
                    'status' => 'acknowledged_out_of_hours',
                    'scheduled_for' => $lead->getNextBusinessHour(),
                    'ack_sent' => $ackResult['success'] ?? false
                ]);
            }

            // 5. Check if instant processing is appropriate
            if ($this->shouldProcessInstantly($lead, $messageBody)) {
                return $this->generateInstantAiResponse($lead, $productId, $messageBody, $conversation);
            }

            // 6. Send acknowledgment and queue for optimized AI processing
            $this->whatsappService->sendImmediateAck($lead, $messageBody);
            
            // Queue for near-instant processing (within 1-2 minutes)
            $conversation->update([
                'followup_attempt_at' => now()->addMinutes(1)
            ]);
            
            DB::commit();
            return response()->json([
                'status' => 'acknowledged_and_queued',
                'processing_in' => '1-2 minutes',
                'ack_sent' => true
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Instant message processing failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'message' => $messageBody
            ]);
            
            // Fallback to cron processing
            $this->storeForCronFallback(request());
            return response()->json(['status' => 'failed_queued_for_retry'], 500);
        }
    }

    /**
     * Generate instant AI response during business hours
     */
    private function generateInstantAiResponse(Lead $lead, int $productId, string $messageBody, Conversation $conversation): JsonResponse
    {
        try {
            // Generate AI response with product context
            $aiResponse = $this->openAiService->generateResponse(
                $lead,
                $productId,
                $messageBody,
                $conversation->conversation_state
            );

            // Send AI response immediately
            $result = $this->whatsappService->sendAiMessage(
                $lead,
                $aiResponse['message_text'],
                $aiResponse['new_conversation_state'],
                array_merge($aiResponse, [
                    'product_id' => $productId,
                    'processing_method' => 'instant'
                ])
            );

            // Update lead product status and pricing if provided
            if (isset($aiResponse['quoted_price']) && $aiResponse['quoted_price']) {
                $lead->updateProductStatus($productId, \App\Models\LeadProduct::STATUS_NEGOTIATING, [
                    'quoted_price' => $aiResponse['quoted_price']
                ]);
            }

            // Handle sales completion or handoffs
            if ($aiResponse['new_conversation_state'] === 'CLOSED') {
                // Mark the specific product as closed won
                $lead->updateProductStatus($productId, \App\Models\LeadProduct::STATUS_CLOSED_WON, [
                    'final_price' => $aiResponse['quoted_price'] ?? $lead->getPrimaryProductPrice()
                ]);
                
                // If all products are closed, update lead status
                if (!$lead->hasActiveProductInterest()) {
                    $lead->update(['status' => Lead::STATUS_CLOSED]);
                }
                
                // Create handoff for order processing
                $this->handoffService->processHandoff($lead, $productId, 'SALE_COMPLETED');
                
            } elseif ($aiResponse['handoff_flag']) {
                $this->handoffService->processHandoff(
                    $lead,
                    $productId,
                    $aiResponse['handoff_reason_code']
                );
            }

            DB::commit();
            
            return response()->json([
                'status' => 'processed_instantly',
                'ai_responded' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'conversation_state' => $aiResponse['new_conversation_state'],
                'handoff_triggered' => $aiResponse['handoff_flag'] ?? false
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Instant AI response generation failed', [
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            // Send fallback message
            $this->whatsappService->sendTextMessage(
                $lead->getContactPhone(), 
                "Thanks for your message! I'm experiencing a brief delay. I'll get back to you within a few minutes."
            );
            
            throw $e;
        }
    }

    /**
     * Determine if message should be processed instantly
     */
    private function shouldProcessInstantly(Lead $lead, string $message): bool
    {
        // Process instantly for urgent or positive keywords
        $urgentKeywords = ['urgent', 'asap', 'immediately', 'emergency', 'cancel', 'stop'];
        $positiveKeywords = ['yes', 'interested', 'buy', 'purchase', 'proceed', 'accept', 'agree'];
        $questionKeywords = ['price', 'cost', 'how much', 'when', 'demo'];
        
        $lowerMessage = strtolower($message);
        
        foreach (array_merge($urgentKeywords, $positiveKeywords, $questionKeywords) as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true; // Process immediately
            }
        }
        
        // Also process instantly during peak business hours
        $currentHour = now($lead->timezone)->hour;
        if ($currentHour >= 10 && $currentHour <= 15) {
            return true; // Peak hours - instant processing
        }
        
        // Short messages usually need quick responses
        if (strlen(trim($message)) < 50) {
            return true;
        }
        
        return false; // Use acknowledgment + optimized processing
    }

    /**
     * Handle non-lead messages (existing system logic)
     */
    private function handleNonLeadMessage(Request $request): JsonResponse
    {
        // Integrate with existing WhatsApp message handling
        // This maintains compatibility with current system
        try {
            // Call existing message processing logic
            $controller = new \App\Http\Controllers\Message();
            $result = $controller->webhook($request);
            
            return response()->json([
                'status' => 'processed_as_non_lead',
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Non-lead message processing failed', [
                'error' => $e->getMessage(),
                'phone' => $request->input('from')
            ]);
            
            return response()->json(['status' => 'non_lead_processing_failed'], 500);
        }
    }

    /**
     * Get lead by phone number
     */
    private function getLeadByPhone(string $phoneNumber): ?Lead
    {
        // Clean phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Find contact first, then get active leads
        $contact = EventsGuest::where('guest_phone', 'LIKE', '%' . substr($cleanPhone, -10))
                             ->orWhere('guest_phone', 'LIKE', '%' . substr($cleanPhone, -9))
                             ->first();
        
        if (!$contact) {
            return null;
        }
        
        // Return the most recent active lead for this contact
        return Lead::where('events_guest_id', $contact->id)
                  ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_DO_NOT_CONTACT])
                  ->latest()
                  ->first();
    }

    /**
     * Determine product context from customer message
     */
    private function determineProductContext(Lead $lead, string $customerMessage): int
    {
        // Get the primary product for this lead
        $primaryProduct = $lead->getPrimaryProduct();
        if ($primaryProduct) {
            return $primaryProduct->id;
        }
        
        // If no primary product, try to infer from message content
        $activeProducts = $lead->getActiveProducts();
        foreach ($activeProducts as $product) {
            if (stripos($customerMessage, $product->name) !== false) {
                return $product->id;
            }
        }
        
        // Default to first active product
        $firstProduct = $lead->leadProducts()->active()->first();
        return $firstProduct ? $firstProduct->product_id : $lead->leadProducts()->first()->product_id;
    }

    /**
     * Store message for cron fallback processing
     */
    private function storeForCronFallback(Request $request): void
    {
        IncomingMessage::create([
            'phone_number' => $request->input('from'),
            'message_body' => $request->input('body'),
            'message_id' => $request->input('id'),
            'webhook_data' => $request->all(),
            'processed' => false,
            'processing_method' => 'cron_fallback',
            'failed_instant_at' => now()
        ]);
    }
}
```

## 4. Laravel Console Commands (Cron Jobs)

### 4.1. Daily Outreach Command

```php
// app/Console/Commands/DailyOutreachCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\OutreachVariant;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;

class DailyOutreachCommand extends Command
{
    protected $signature = 'ai-agent:daily-outreach {--limit=50}';
    protected $description = 'Send daily outreach messages to new leads';

    public function handle()
    {
        $this->info('Starting daily outreach process...');
        
        $limit = $this->option('limit');
        $successCount = 0;
        $failureCount = 0;

        try {
            // Get leads for outreach
            $leads = Lead::forOutreach($limit)->get();
            
            $this->info("Found {$leads->count()} leads for outreach");

            foreach ($leads as $lead) {
                try {
                    // Check timezone and business hours
                    if (!$lead->isInBusinessHours()) {
                        $this->info("Skipping lead {$lead->id} - outside business hours");
                        continue;
                    }

                    // Skip DO_NOT_CONTACT leads
                    if ($lead->status === Lead::STATUS_DO_NOT_CONTACT) {
                        $this->info("Skipping lead {$lead->id} - DO_NOT_CONTACT status");
                        continue;
                    }

                    // A/B test variant selection
                    $variant = $this->selectOutreachVariant();
                    
                    // Get WhatsApp service for the lead's user
                    $whatsappService = AiWhatsAppService::forUser(1); // Assuming system user ID = 1
                    
                    // Send outreach message
                    $result = $whatsappService->sendOutreachMessage($lead, $variant);

                    if ($result['success']) {
                        // Update lead status
                        $lead->update([
                            'status' => Lead::STATUS_OUTREACHED,
                            'last_outreach_at' => now()
                        ]);

                        // Update variant usage
                        $this->updateVariantUsage($variant);

                        $successCount++;
                        $this->info("✓ Outreach sent to lead {$lead->id} (variant: {$variant})");
                    } else {
                        $failureCount++;
                        $this->error("✗ Failed to send outreach to lead {$lead->id}");
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    $this->error("Error processing lead {$lead->id}: " . $e->getMessage());
                    
                    Log::error('Daily outreach error', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Rate limiting - wait 2 seconds between messages
                sleep(2);
            }

            $this->info("Daily outreach completed. Success: {$successCount}, Failures: {$failureCount}");

        } catch (\Exception $e) {
            $this->error('Daily outreach failed: ' . $e->getMessage());
            Log::error('Daily outreach command failed', ['error' => $e->getMessage()]);
        }
    }

    private function selectOutreachVariant()
    {
        $variants = ['A', 'B', 'C'];
        return $variants[array_rand($variants)];
    }

    private function updateVariantUsage($variant)
    {
        OutreachVariant::where('variant_key', $variant)
                      ->increment('usage_count');
    }
}
```

### 4.2. Conversation Engine Command

```php
// app/Console/Commands/ConversationEngineCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\IncomingMessage;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use App\Services\HandoffService;
use Illuminate\Support\Facades\Log;

class ConversationEngineCommand extends Command
{
    protected $signature = 'ai-agent:conversation-engine';
    protected $description = 'Process scheduled follow-ups and lead scoring (instant replies handled by webhook)';

    protected $openAiService;
    protected $whatsappService;
    protected $handoffService;

    public function __construct()
    {
        parent::__construct();
        $this->openAiService = new OpenAiService();
        $this->handoffService = new HandoffService();
    }

    public function handle()
    {
        $this->info('Starting conversation engine for follow-ups...');

        try {
            $this->whatsappService = AiWhatsAppService::forUser(1);
            
            // Process scheduled follow-ups (new messages handled by webhook)
            $this->processScheduledFollowups();

            $this->info('Conversation engine completed successfully');

        } catch (\Exception $e) {
            $this->error('Conversation engine failed: ' . $e->getMessage());
            Log::error('Conversation engine failed', ['error' => $e->getMessage()]);
        }
    }

    // NOTE: New customer message processing is now handled by WaSender API integration
    // This method is kept for reference but not called in instant webhook architecture
    private function processNewReplies_DEPRECATED()
    {
        $this->info('Processing new customer replies...');
        
        // Get unprocessed incoming messages from leads with OUTREACHED status
        $newReplies = IncomingMessage::unprocessed()
                                   ->whereHas('lead', function($query) {
                                       $query->where('status', Lead::STATUS_OUTREACHED);
                                   })
                                   ->get();

        foreach ($newReplies as $incomingMessage) {
            try {
                $lead = $this->getLeadByPhone($incomingMessage->phone_number);
                
                if (!$lead) {
                    $this->warn("No lead found for phone: {$incomingMessage->phone_number}");
                    continue;
                }

                // Store customer message in conversation
                $conversation = Conversation::create([
                    'lead_id' => $lead->id,
                    'message_type' => Conversation::TYPE_CUSTOMER,
                    'message_content' => $incomingMessage->message_body,
                    'conversation_state' => $lead->getActiveConversation()?->conversation_state ?? 'INTRO',
                    'is_active' => true
                ]);

                // Update lead status
                $lead->update(['status' => Lead::STATUS_REPLIED]);

                // Generate AI response
                $currentState = $conversation->conversation_state;
                $aiResponse = $this->openAiService->generateResponse(
                    $lead, 
                    $productId,
                    $incomingMessage->message_body, 
                    $currentState
                );

                // Send AI response with product context
                $result = $this->whatsappService->sendAiMessage(
                    $lead,
                    $aiResponse['message_text'],
                    $aiResponse['new_conversation_state'],
                    array_merge($aiResponse, ['product_id' => $productId])
                );

                // Update lead product status and pricing if provided
                if (isset($aiResponse['quoted_price']) && $aiResponse['quoted_price']) {
                    $lead->updateProductStatus($productId, LeadProduct::STATUS_NEGOTIATING, [
                        'quoted_price' => $aiResponse['quoted_price']
                    ]);
                }
                if (isset($aiResponse['discount_offered']) && $aiResponse['discount_offered']) {
                    $leadProduct = $lead->leadProducts()->where('product_id', $productId)->first();
                    if ($leadProduct) {
                        $leadProduct->update(['discount_applied' => $aiResponse['discount_offered']]);
                    }
                }

                // Check for sale completion
                if ($aiResponse['new_conversation_state'] === 'CLOSED') {
                    $lead->update([
                        'status' => Lead::STATUS_CLOSED,
                        'final_price' => $aiResponse['quoted_price'] ?? $lead->getProductPrice()
                    ]);
                    
                    $this->handoffService->processHandoff(
                        $lead, 
                        Handoff::REASON_SALE_COMPLETED
                    );
                    
                    $this->info("Lead {$lead->id} CLOSED - Sale completed!");
                } elseif ($aiResponse['handoff_flag']) {
                    $this->handoffService->processHandoff(
                        $lead, 
                        $aiResponse['handoff_reason_code']
                    );
                    
                    $this->info("Lead {$lead->id} handed off - reason: {$aiResponse['handoff_reason_code']}");
                } else {
                    // Schedule next follow-up if needed
                    $this->scheduleFollowup($lead, $aiResponse['new_conversation_state']);
                }

                // Mark incoming message as processed
                $incomingMessage->markAsReplied($aiResponse['message_text']);

                $this->info("✓ Processed reply from lead {$lead->id}");

            } catch (\Exception $e) {
                $this->error("Error processing reply: " . $e->getMessage());
                Log::error('Reply processing error', [
                    'incoming_message_id' => $incomingMessage->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function processScheduledFollowups()
    {
        $this->info('Processing scheduled follow-ups...');
        
        $followups = Conversation::pendingFollowup()->get();

        foreach ($followups as $conversation) {
            try {
                $lead = $conversation->lead;

                // Skip if lead is handed off or do not contact
                if (in_array($lead->status, [Lead::STATUS_HANDED_OFF, Lead::STATUS_DO_NOT_CONTACT])) {
                    continue;
                }

                // Generate follow-up message
                $aiResponse = $this->openAiService->generateResponse(
                    $lead, 
                    $this->determineProductContext($lead, ''), // Determine product context
                    null, // No new customer message
                    $conversation->conversation_state
                );

                // Send follow-up message
                $result = $this->whatsappService->sendAiMessage(
                    $lead,
                    $aiResponse['message_text'],
                    $aiResponse['new_conversation_state'],
                    array_merge($aiResponse, ['is_followup' => true])
                );

                // Clear current followup and potentially schedule next one
                $conversation->update(['followup_attempt_at' => null]);
                
                // Check for handoff
                if ($aiResponse['handoff_flag']) {
                    $this->handoffService->processHandoff(
                        $lead, 
                        $aiResponse['handoff_reason_code']
                    );
                } else {
                    $this->scheduleFollowup($lead, $aiResponse['new_conversation_state']);
                }

                $this->info("✓ Follow-up sent to lead {$lead->id}");

            } catch (\Exception $e) {
                $this->error("Error processing follow-up: " . $e->getMessage());
                Log::error('Follow-up processing error', [
                    'conversation_id' => $conversation->id,
                    'lead_id' => $conversation->lead_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function determineProductContext($lead, $customerMessage)
    {
        // Get the primary product for this lead
        $primaryProduct = $lead->getPrimaryProduct();
        if ($primaryProduct) {
            return $primaryProduct->id;
        }
        
        // If no primary product, try to infer from message content
        $activeProducts = $lead->getActiveProducts();
        foreach ($activeProducts as $product) {
            if (stripos($customerMessage, $product->name) !== false) {
                return $product->id;
            }
        }
        
        // Default to first active product
        $firstProduct = $lead->leadProducts()->active()->first();
        return $firstProduct ? $firstProduct->product_id : null;
    }

    private function getLeadByPhone($phoneNumber)
    {
        // Find contact first, then get active leads for that contact
        $contact = EventsGuest::where('guest_phone', $phoneNumber)->first();
        if (!$contact) {
            return null;
        }
        
        // Return the most recent active lead for this contact
        return Lead::where('events_guest_id', $contact->id)
                  ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_DO_NOT_CONTACT])
                  ->latest()
                  ->first();
    }

    private function scheduleFollowup($lead, $conversationState)
    {
        // Schedule follow-up based on conversation state
        $followupHours = match($conversationState) {
            'INTRO' => 24,
            'DISCOVERY' => 48,
            'OBJECTION_HANDLING' => 72,
            'SOFT_CLOSE' => 24,
            'CLOSED' => null, // No follow-up needed for closed sales
            default => 48
        };

        if ($followupHours === null) {
            return; // Don't schedule follow-up for closed sales
        }

        $activeConversation = $lead->getActiveConversation();
        if ($activeConversation) {
            $activeConversation->update([
                'followup_attempt_at' => now()->addHours($followupHours)
            ]);
        }
    }
}
```

### 4.3. Win-Back Outreach Command

```php
// app/Console/Commands/WinBackOutreachCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;

class WinBackOutreachCommand extends Command
{
    protected $signature = 'ai-agent:win-back-outreach {--limit=25}';
    protected $description = 'Send win-back outreach messages to churned customers';

    public function handle()
    {
        $this->info('Starting win-back outreach process...');
        
        $limit = $this->option('limit');
        $successCount = 0;
        $failureCount = 0;

        try {
            $whatsappService = AiWhatsAppService::forUser(1);
            $openAiService = new OpenAiService();
            
            // Get churned leads eligible for win-back
            $leads = Lead::winBackEligible()->limit($limit)->get();
            
            $this->info("Found {$leads->count()} churned leads for win-back outreach");

            foreach ($leads as $lead) {
                try {
                    // Check timezone and business hours
                    if (!$lead->isInBusinessHours()) {
                        $this->warn("Skipping lead {$lead->id} - outside business hours");
                        continue;
                    }

                    // Send win-back message
                    $result = $this->sendWinBackMessage($lead, $whatsappService, $openAiService);

                    if ($result['success']) {
                        $lead->update([
                            'status' => Lead::STATUS_OUTREACHED,
                            'last_outreach_at' => now()
                        ]);

                        $successCount++;
                        $this->info("✓ Win-back message sent to lead {$lead->id}");
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    $this->error("Error processing win-back lead {$lead->id}: " . $e->getMessage());
                    Log::error('Win-back outreach error', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Rate limiting - wait 3 seconds between win-back messages
                sleep(3);
            }

            $this->info("Win-back outreach completed. Success: {$successCount}, Failures: {$failureCount}");

        } catch (\Exception $e) {
            $this->error('Win-back outreach failed: ' . $e->getMessage());
            Log::error('Win-back outreach command failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendWinBackMessage($lead, $whatsappService, $openAiService)
    {
        // Generate personalized win-back message using AI
        $aiResponse = $openAiService->generateResponse($lead, null, 'INTRO');
        
        return $whatsappService->sendAiMessage(
            $lead, 
            $aiResponse['message_text'], 
            'INTRO', 
            array_merge($aiResponse, [
                'is_win_back' => true,
                'churn_reason' => $lead->churn_reason,
                'days_since_churn' => $lead->getDaysSinceChurn()
            ])
        );
    }

    private function getWinBackTemplate($lead, $variant = 'A')
    {
        $templates = [
            'A' => "Hi {name}, I hope you're doing well. I noticed you previously tried {product_name} with us. " .
                   "We've made significant improvements since then, especially around {churn_focus}. " .
                   "Would you be open to a quick chat about what's changed?",
            
            'B' => "Hello {name}! It's been a while since you used {product_name}. " .
                   "I wanted to reach out because we've addressed many of the concerns that led customers to leave, " .
                   "particularly {churn_focus}. Could we have a brief conversation about how we've improved?",
            
            'C' => "Hi {name}, I understand {product_name} didn't work out perfectly for you before. " .
                   "We've listened to feedback and made substantial changes. " .
                   "I'd love to show you what's different now - would 10 minutes work for you this week?"
        ];

        $churnFocus = $this->getChurnFocusArea($lead->churn_reason);
        $template = $templates[$variant] ?? $templates['A'];
        
        return str_replace(
            ['{name}', '{product_name}', '{churn_focus}'],
            [
                $lead->getContactName(),
                $lead->getProductName(),
                $churnFocus
            ],
            $template
        );
    }

    private function getChurnFocusArea($churnReason)
    {
        $focusMap = [
            'price' => 'pricing flexibility and value',
            'cost' => 'cost-effectiveness',
            'features' => 'feature completeness',
            'support' => 'customer support experience',
            'usability' => 'ease of use',
            'performance' => 'system performance and reliability',
            'competitor' => 'competitive advantages'
        ];

        $reason = strtolower($churnReason ?: 'general');
        foreach ($focusMap as $key => $focus) {
            if (strpos($reason, $key) !== false) {
                return $focus;
            }
        }
        
        return 'overall customer experience';
    }
}
```

### 4.4. No Reply Chase Command

```php
// app/Console/Commands/NoReplyChaseCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Services\AiWhatsAppService;
use Illuminate\Support\Facades\Log;

class NoReplyChaseCommand extends Command
{
    protected $signature = 'ai-agent:no-reply-chase {--days=5}';
    protected $description = 'Send chase messages to leads who haven\'t replied';

    public function handle()
    {
        $this->info('Starting no-reply chase process...');
        
        $days = $this->option('days');
        $successCount = 0;
        $failureCount = 0;

        try {
            $whatsappService = AiWhatsAppService::forUser(1);
            
            // Get leads that need chasing
            $leads = Lead::outreachedWithoutReply($days)->get();
            
            $this->info("Found {$leads->count()} leads for chase messages");

            foreach ($leads as $lead) {
                try {
                    // Skip DO_NOT_CONTACT leads
                    if ($lead->status === Lead::STATUS_DO_NOT_CONTACT) {
                        continue;
                    }

                    // Send chase message
                    $result = $whatsappService->sendChaseMessage($lead);

                    if ($result['success']) {
                        // Update last outreach time and schedule next chase
                        $lead->update([
                            'last_outreach_at' => now()
                        ]);

                        // Schedule next chase in 5 days
                        $activeConversation = $lead->getActiveConversation();
                        if ($activeConversation) {
                            $activeConversation->update([
                                'followup_attempt_at' => now()->addDays(5)
                            ]);
                        }

                        $successCount++;
                        $this->info("✓ Chase message sent to lead {$lead->id}");
                    } else {
                        $failureCount++;
                        $this->error("✗ Failed to send chase message to lead {$lead->id}");
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    $this->error("Error chasing lead {$lead->id}: " . $e->getMessage());
                    
                    Log::error('No reply chase error', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Rate limiting
                sleep(2);
            }

            $this->info("No-reply chase completed. Success: {$successCount}, Failures: {$failureCount}");

        } catch (\Exception $e) {
            $this->error('No-reply chase failed: ' . $e->getMessage());
            Log::error('No-reply chase command failed', ['error' => $e->getMessage()]);
        }
    }
}
```

## 5. Handoff & Notification System

### 5.1. Handoff Service

```php
// app/Services/HandoffService.php
<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Handoff;
use App\Models\Conversation;
use App\Services\OpenAiService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class HandoffService
{
    protected $openAiService;
    protected $notificationService;

    public function __construct()
    {
        $this->openAiService = new OpenAiService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Process handoff for a lead and specific product
     */
    public function processHandoff(Lead $lead, $productId, $handoffReason)
    {
        try {
            // Generate AI summary with product context
            $aiSummary = $this->openAiService->generateHandoffSummary($lead, $productId, $handoffReason);

            // Create handoff record with product context
            $handoff = Handoff::create([
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'handoff_reason' => $handoffReason,
                'ai_summary' => $aiSummary,
                'status' => Handoff::STATUS_PENDING
            ]);

            // Update lead status only if no other active products
            if (!$lead->hasActiveProductInterest()) {
                $lead->update(['status' => Lead::STATUS_HANDED_OFF]);
            }

            // Update specific product status
            $lead->updateProductStatus($productId, LeadProduct::STATUS_ON_HOLD);

            // Deactivate conversations for this product
            $lead->conversations()
                 ->where('product_id', $productId)
                 ->where('is_active', true)
                 ->update(['is_active' => false]);

            // Send notification to sales team
            $this->notificationService->notifyHandoff($handoff);

            Log::info('Lead handed off successfully', [
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'handoff_id' => $handoff->id,
                'reason' => $handoffReason
            ]);

            return $handoff;

        } catch (\Exception $e) {
            Log::error('Handoff processing failed', [
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'reason' => $handoffReason,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check and process SLA breaches
     */
    public function processSlaBreaches()
    {
        $breachedHandoffs = Handoff::slaBreached()->get();

        foreach ($breachedHandoffs as $handoff) {
            $handoff->update(['sla_breached' => true]);
            
            // Send escalation notification
            $this->notificationService->notifySlaBreach($handoff);
            
            Log::warning('Handoff SLA breached', [
                'handoff_id' => $handoff->id,
                'lead_id' => $handoff->lead_id,
                'deadline' => $handoff->sla_deadline
            ]);
        }
    }

    /**
     * Claim a handoff by user
     */
    public function claimHandoff($handoffId, $userId)
    {
        $handoff = Handoff::findOrFail($handoffId);
        
        if ($handoff->status !== Handoff::STATUS_PENDING) {
            throw new \Exception('Handoff is not available for claiming');
        }

        $handoff->claim($userId);
        
        Log::info('Handoff claimed', [
            'handoff_id' => $handoff->id,
            'user_id' => $userId
        ]);

        return $handoff;
    }
}
```

### 5.2. Notification Service

```php
// app/Services/NotificationService.php
<?php

namespace App\Services;

use App\Models\Handoff;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify sales team of new handoff
     */
    public function notifyHandoff(Handoff $handoff)
    {
        try {
            $lead = $handoff->lead;
            
            // Email notification
            $this->sendHandoffEmail($handoff);
            
            // Slack notification (if configured)
            if (config('services.slack.webhook_url')) {
                $this->sendSlackNotification($handoff);
            }

            // SMS notification to sales manager (if configured)
            if (config('services.sms.sales_manager_number')) {
                $this->sendSmsNotification($handoff);
            }

        } catch (\Exception $e) {
            Log::error('Handoff notification failed', [
                'handoff_id' => $handoff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    private function sendHandoffEmail(Handoff $handoff)
    {
        $salesUsers = User::where('role', 'sales')->get();
        
        foreach ($salesUsers as $user) {
            Mail::send('emails.handoff-notification', [
                'handoff' => $handoff,
                'lead' => $handoff->lead,
                'user' => $user
            ], function($message) use ($handoff, $user) {
                $message->to($user->email)
                       ->subject("New Qualified Lead: {$handoff->lead->name} ({$handoff->handoff_reason})");
            });
        }
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification(Handoff $handoff)
    {
        $lead = $handoff->lead;
        $webhookUrl = config('services.slack.webhook_url');
        
        $message = [
            'text' => '🎯 New Qualified Lead Ready!',
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🎯 New Qualified Lead Ready!'
                    ]
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Lead Name:*\n{$lead->name}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Company:*\n{$lead->company_name}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Phone:*\n{$lead->phone_number}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Reason:*\n{$handoff->handoff_reason}"
                        ]
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*AI Summary:*\n{$handoff->ai_summary}"
                    ]
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Claim Lead'
                            ],
                            'url' => config('app.url') . "/handoffs/{$handoff->id}/claim",
                            'style' => 'primary'
                        ]
                    ]
                ]
            ]
        ];

        Http::post($webhookUrl, $message);
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification(Handoff $handoff)
    {
        $lead = $handoff->lead;
        $message = "New qualified lead: {$lead->name} ({$lead->company_name}) - {$handoff->handoff_reason}. " .
                  "Phone: {$lead->phone_number}. Claim at: " . config('app.url') . "/handoffs/{$handoff->id}";
        
        // Integrate with your SMS service (Twilio, etc.)
        // Implementation depends on your SMS provider
    }

    /**
     * Notify SLA breach
     */
    public function notifySlaBreach(Handoff $handoff)
    {
        // Send urgent notification for SLA breach
        $message = [
            'text' => '⚠️ URGENT: Handoff SLA Breached!',
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '⚠️ URGENT: Handoff SLA Breached!'
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "Handoff #{$handoff->id} for {$handoff->lead->name} has exceeded the 4-hour SLA!"
                    ]
                ]
            ]
        ];

        if (config('services.slack.webhook_url')) {
            Http::post(config('services.slack.webhook_url'), $message);
        }
    }
}
```

### 5.3. SLA Monitoring Command

```php
// app/Console/Commands/SlaMonitorCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HandoffService;

class SlaMonitorCommand extends Command
{
    protected $signature = 'ai-agent:sla-monitor';
    protected $description = 'Monitor and process handoff SLA breaches';

    public function handle()
    {
        $this->info('Checking for SLA breaches...');
        
        $handoffService = new HandoffService();
        $handoffService->processSlaBreaches();
        
        $this->info('SLA monitoring completed');
    }
}
```

## 6. Updated Console Kernel & Scheduling

### 6.1. Enhanced Console Kernel

```php
// app/Console/Kernel.php - Updated version
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DailyOutreachCommand::class,
        \App\Console\Commands\ConversationEngineCommand::class,
        \App\Console\Commands\NoReplyChaseCommand::class,
        \App\Console\Commands\WinBackOutreachCommand::class,
        \App\Console\Commands\SlaMonitorCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Existing schedules...
        $schedule->command('inspire')->hourly();
        
        // Existing message processing
        $schedule->call(function () {
            (new \App\Http\Controllers\Message())->process();
        })->everyMinute();

        // AI SALES AGENT CRON JOBS (Updated for Webhook Architecture)
        
        // 1. Daily outreach - every day at 08:00 UTC
        $schedule->command('ai-agent:daily-outreach')
                ->dailyAt('08:00')
                ->timezone('UTC')
                ->withoutOverlapping()
                ->runInBackground();

        // 2. Conversation engine - FOLLOW-UP ONLY (webhook handles instant customer replies)
        // Processes: scheduled follow-ups, fallback messages, and business hours queue
        $schedule->command('ai-agent:conversation-engine')
                ->everyFiveMinutes() // Reduced frequency since webhooks handle primary processing
                ->withoutOverlapping()
                ->runInBackground();

        // 3. No reply chase - daily at 14:00 UTC
        $schedule->command('ai-agent:no-reply-chase')
                ->dailyAt('14:00')
                ->timezone('UTC')
                ->withoutOverlapping()
                ->runInBackground();

        // 4. Win-back outreach - twice weekly (Tuesday & Friday at 10:00 UTC)
        $schedule->command('ai-agent:win-back-outreach')
                ->weeklyOn([2, 5], '10:00') // Tuesday and Friday
                ->timezone('UTC')
                ->withoutOverlapping()
                ->runInBackground();

        // 5. SLA monitoring - every 30 minutes
        $schedule->command('ai-agent:sla-monitor')
                ->everyThirtyMinutes()
                ->withoutOverlapping();

        // Existing reminder system
        $schedule->call(function () {
            $this->reminders();
        })->dailyAt('08:40');
    }

    // Existing methods remain unchanged...
    // reminders(), checkSchedule(), etc.
}
```

## 7. Configuration Updates Required

### 7.1. Route Configuration (NEW - Webhook Routes)

```php
// routes/api.php - Add instant webhook processing routes
Route::prefix('whatsapp')->group(function () {
    // Instant webhook processing for lead messages
    Route::post('/webhook/instant', [WaSender API::class, 'handleIncomingMessage'])
          ->name('whatsapp.webhook.instant');
    
    // Webhook verification (if required by WhatsApp)
    Route::get('/webhook/instant', [WaSender API::class, 'verifyWebhook'])
          ->name('whatsapp.webhook.verify');
    
    // Fallback webhook (existing system)
    Route::post('/webhook', [Message::class, 'webhook'])
          ->name('whatsapp.webhook.fallback');
});
```

### 7.2. Services Configuration

```php
// config/services.php - Add these configurations
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
    'model' => env('OPENAI_MODEL', 'gpt-4o'),
    'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    'max_tokens' => env('OPENAI_MAX_TOKENS', 1024),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'max_retries' => env('OPENAI_MAX_RETRIES', 3),
],

'slack' => [
    'webhook_url' => env('SLACK_WEBHOOK_URL'),
    'channel' => env('SLACK_CHANNEL', '#sales'),
],

'sms' => [
    'sales_manager_number' => env('SMS_SALES_MANAGER_NUMBER'),
],
```

### 7.2. Environment Variables

```env
# Add to .env file

# Database - PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=safarichat_ai
DB_USERNAME=postgres
DB_PASSWORD=your_password

# OpenAI API
OPENAI_API_KEY=your_openai_api_key
OPENAI_API_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o
OPENAI_TEMPERATURE=0.7
OPENAI_MAX_TOKENS=1024
OPENAI_TIMEOUT=30
OPENAI_MAX_RETRIES=3

# Notifications
SLACK_WEBHOOK_URL=your_slack_webhook_url
SLACK_CHANNEL=#sales
SMS_SALES_MANAGER_NUMBER=+1234567890

# Queue
QUEUE_CONNECTION=database
```

## 8. Implementation Steps Summary

### Phase 1: Database & Core Setup (Week 1)
1. **Database Migration**
   - Install PostgreSQL support: `composer require doctrine/dbal ext-pgsql`
   - Create and run new migration files
   - Seed outreach variants table

2. **Model Creation**
   - Implement Lead, Conversation, Handoff models
   - Add relationships and scopes

3. **Service Setup**
   - Configure OpenAI API integration
   - Enhance WhatsApp service for AI agent

### Phase 2: Webhook & AI Implementation (Week 2)
1. **Instant Webhook Processing**
   - Implement WaSender API integration for instant responses
   - Create intelligent message prioritization system
   - Build business hours detection and scheduling

2. **AI Services**
   - Implement OpenAiService with retry logic
   - Create AiWhatsAppService extension with acknowledgment features
   - Build smart context switching for multiple products

3. **Console Commands**
   - Build and test fallback/follow-up cron commands
   - Update console kernel with reduced scheduling frequency

### Phase 3: Handoff & Notifications (Week 3)
1. **Handoff System**
   - Implement HandoffService
   - Create notification integrations (Slack, Email)
   - Build web interface for human agents

2. **Webhook Integration**
   - Update webhook handling for lead conversations
   - Connect incoming messages to AI processing

### Phase 4: Testing & Deployment (Week 4)
1. **Testing**
   - Unit tests for all services
   - Integration testing with actual APIs
   - Load testing for high volume

2. **Monitoring & Optimization**
   - Set up logging and alerting
   - Performance optimization
   - Security review

## 9. Security & Compliance Notes

- **DO_NOT_CONTACT Enforcement**: Any lead with status = 'DO_NOT_CONTACT' must be automatically excluded from ALL cron jobs (daily outreach, conversation engine, no-reply chase, and win-back outreach)
- **Data Encryption**: All conversation data should be encrypted at rest
- **API Security**: Rate limiting and authentication for all external APIs with exponential backoff/retry logic (up to 3 attempts)
- **Privacy**: Implement data retention policies and GDPR compliance with data masking for sensitive conversation content
- **Access Control**: Role-based access for handoff management
- **Audit Trail**: Log all AI decisions and human interactions
- **Backup Strategy**: Regular backups of lead and conversation data
- **Error Handling**: Robust try...catch blocks around all API calls (WhatsApp, OpenAI) with clear logging and automated retry mechanisms

## 10. Performance Considerations

- **Database Indexing**: Proper indexes on frequently queried columns
- **Queue Processing**: Use Laravel Horizon for queue monitoring
- **API Rate Limiting**: Implement proper rate limiting for WhatsApp and OpenAI APIs
- **Caching**: Cache frequently accessed data like lead scores and conversation states
- **Monitoring**: Set up application performance monitoring (APM)

## 11. Next Steps for Development

1. **Immediate Actions**:
   - Set up PostgreSQL database
   - Install required Composer packages
   - Configure environment variables
   - Create database migrations

2. **Development Order**:
   - Start with database schema and models
   - Build AI services and test with sample data
   - Implement console commands one by one
   - Add handoff system and notifications
   - Create web interface for sales team

3. **Testing Strategy**:
   - Unit tests for all services and models
   - Integration tests for API connections
   - End-to-end tests for complete workflows
   - Load testing for production readiness

## 12. Key Implementation Summary

### **Strategic Changes Made:**

1. **📊 Database Structure Overhaul:**
   - **`products` table**: Centralized product/service catalog with AI-optimized descriptions
   - **`lead_products` table**: Many-to-many relationship tracking each product's sales status per lead
   - **Product-specific conversations**: Every conversation now linked to specific product context
   - **Product-specific handoffs**: Handoffs track which product discussion led to human involvement

2. **🤖 AI Context Enhancement:**
   - **Product-driven conversations**: AI receives full context of current product focus plus lead's history with other products
   - **Multi-product awareness**: AI knows what other products the lead has discussed and their status
   - **Product-specific pricing**: AI can negotiate within each product's discount limits
   - **Feature-based discussions**: AI references specific product features and handles product-specific objections

3. **📈 Sales Process Improvements:**
   - **Independent product tracking**: Each product has its own sales cycle status (INTERESTED → PITCHED → DEMO → CLOSED)
   - **Primary product focus**: System designates primary product while maintaining awareness of secondary interests
   - **Product-specific analytics**: Track conversion rates, average selling price, and sales cycle length per product
   - **Cross-sell opportunities**: AI can identify and suggest related products based on upsell relationships

### **Key Workflow Changes:**

**Before (Single Product):**
- Lead → Product (1:1)
- Conversation about "the product"
- Single sales cycle tracking

**After (Multiple Products):**
- Lead → Multiple Products (1:N through lead_products)
- Product-specific conversations with cross-product awareness
- Independent sales cycle per product
- Smart product context determination from customer messages

### **AI Prompt Enhancement:**
The AI now receives comprehensive product context including:
- Current product being discussed (name, features, pricing, trial options)
- Lead's interaction history with this specific product
- Status and progress with other products
- Product-specific objections and features already covered
- Upsell opportunities and related products

This creates **product-driven conversations** where the AI can intelligently navigate multiple product interests while maintaining focus on the current discussion topic.

## 13. Next Steps for Development

### 12.1. Multiple Products Per Contact Workflow

```php
// app/Services/LeadManagementService.php
<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\EventsGuest;
use App\Models\Product;

class LeadManagementService
{
    /**
     * Create leads for a contact across multiple products
     */
    public function createLeadsForContact($contactId, array $productIds, $leadData = [])
    {
        $contact = EventsGuest::findOrFail($contactId);
        $leads = [];

        foreach ($productIds as $productId) {
            $product = Product::findOrFail($productId);
            
            // Skip if lead already exists and is active
            if ($contact->hasActiveLeadForProduct($productId)) {
                continue;
            }

            $lead = Lead::createForContactAndProduct($contactId, $productId, array_merge($leadData, [
                'company_name' => $leadData['company_name'] ?? null,
                'industry' => $leadData['industry'] ?? $product->category,
                'lead_score' => $this->calculateLeadScore($contact, $product),
            ]));

            $leads[] = $lead;
        }

        return $leads;
    }

    /**
     * Calculate lead score based on contact and product
     */
    private function calculateLeadScore($contact, $product)
    {
        $baseScore = 50;
        
        // Increase score if contact has made purchases before
        if ($contact->payments()->exists()) {
            $baseScore += 20;
        }

        // Increase score based on product popularity
        $conversionRate = $product->getConversionRate();
        if ($conversionRate > 20) {
            $baseScore += 15;
        } elseif ($conversionRate > 10) {
            $baseScore += 10;
        }

        // Increase score if product price matches contact's previous spending
        $avgSpending = $contact->payments()->avg('amount') ?? 0;
        $priceDiff = abs($product->retail_price - $avgSpending);
        if ($priceDiff < 1000) { // Within 1000 of average spending
            $baseScore += 10;
        }

        return min($baseScore, 100); // Cap at 100
    }

    /**
     * Prioritize leads for outreach based on various factors
     */
    public function prioritizeLeadsForOutreach($limit = 50)
    {
        return Lead::newLeads()
                  ->with(['contact', 'product'])
                  ->get()
                  ->sortByDesc(function ($lead) {
                      $score = $lead->lead_score;
                      
                      // Boost score for high-value products
                      if ($lead->getProductPrice() > 5000) {
                          $score += 10;
                      }
                      
                      // Boost score for products with high conversion rates
                      if ($lead->product->getConversionRate() > 15) {
                          $score += 5;
                      }
                      
                      // Boost score for contacts with recent activity
                      $lastActivity = $lead->contact->updated_at;
                      if ($lastActivity && $lastActivity->diffInDays() < 30) {
                          $score += 5;
                      }
                      
                      return $score;
                  })
                  ->take($limit);
    }

    /**
     * Handle contact preference for DO_NOT_CONTACT
     */
    public function markContactAsDoNotContact($contactId, $reason = null)
    {
        $contact = EventsGuest::findOrFail($contactId);
        
        // Mark all active leads for this contact as DO_NOT_CONTACT
        $contact->activeLeads()->update([
            'status' => Lead::STATUS_DO_NOT_CONTACT,
            'metadata->do_not_contact_reason' => $reason,
            'metadata->do_not_contact_date' => now()
        ]);

        return $contact->activeLeads()->count();
    }

    /**
     * Reactivate leads for a contact (opt-in again)
     */
    public function reactivateContactLeads($contactId, array $productIds = [])
    {
        $contact = EventsGuest::findOrFail($contactId);
        
        $query = $contact->leads()->where('status', Lead::STATUS_DO_NOT_CONTACT);
        
        if (!empty($productIds)) {
            $query->whereIn('product_id', $productIds);
        }

        return $query->update([
            'status' => Lead::STATUS_NEW,
            'metadata->reactivated_date' => now()
        ]);
    }

    /**
     * Create win-back campaign for churned contacts
     */
    public function createWinBackCampaign($contactId, $productId, $churnReason, $churnDate = null)
    {
        $contact = EventsGuest::findOrFail($contactId);
        $product = Product::findOrFail($productId);
        
        // Check if there's an existing active lead for this contact-product
        $existingLead = Lead::where('events_guest_id', $contactId)
                          ->where('product_id', $productId)
                          ->whereNotIn('status', [Lead::STATUS_DO_NOT_CONTACT])
                          ->first();
        
        if ($existingLead) {
            // Mark existing lead as churned
            $existingLead->markAsChurned($churnReason, $churnDate);
            return $existingLead;
        }
        
        // Create new churned lead
        $lead = Lead::create([
            'events_guest_id' => $contactId,
            'product_id' => $productId,
            'is_churned' => true,
            'churn_date' => $churnDate ?: now()->toDateString(),
            'churn_reason' => $churnReason,
            'status' => Lead::STATUS_WIN_BACK,
            'lead_score' => (new Lead())->calculateWinBackScore($churnReason),
            'timezone' => 'Africa/Nairobi',
            'source' => 'churn_win_back'
        ]);
        
        return $lead;
    }

    /**
     * Bulk create win-back campaigns from churned customer data
     */
    public function bulkCreateWinBackCampaigns($churnedCustomers)
    {
        $created = [];
        
        foreach ($churnedCustomers as $customer) {
            try {
                $lead = $this->createWinBackCampaign(
                    $customer['contact_id'],
                    $customer['product_id'],
                    $customer['churn_reason'],
                    $customer['churn_date'] ?? null
                );
                
                $created[] = $lead;
                
            } catch (\Exception $e) {
                Log::error('Failed to create win-back campaign', [
                    'customer' => $customer,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $created;
    }

    /**
     * Get win-back campaign analytics
     */
    public function getWinBackAnalytics($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subMonth();
        $dateTo = $dateTo ?: now();

        $winBackLeads = Lead::churnedLeads()
                          ->whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'total_win_back_leads' => $winBackLeads->count(),
            'outreached' => $winBackLeads->where('status', '!=', Lead::STATUS_WIN_BACK)->count(),
            'replied' => $winBackLeads->where('status', Lead::STATUS_REPLIED)->count(),
            'won_back' => $winBackLeads->where('status', Lead::STATUS_CLOSED)->count(),
            'win_back_rate' => $this->calculateWinBackRate($winBackLeads),
            'avg_days_to_win_back' => $this->calculateAverageWinBackTime($winBackLeads),
            'top_churn_reasons' => $this->getTopChurnReasons($winBackLeads),
        ];
    }

    private function calculateWinBackRate($winBackLeads)
    {
        $outreached = $winBackLeads->where('status', '!=', Lead::STATUS_WIN_BACK)->count();
        $wonBack = $winBackLeads->where('status', Lead::STATUS_CLOSED)->count();
        
        return $outreached > 0 ? ($wonBack / $outreached) * 100 : 0;
    }

    private function calculateAverageWinBackTime($winBackLeads)
    {
        $wonBackLeads = $winBackLeads->where('status', Lead::STATUS_CLOSED)->get();
        
        if ($wonBackLeads->count() === 0) {
            return null;
        }
        
        $totalDays = 0;
        foreach ($wonBackLeads as $lead) {
            $winBackDate = $lead->conversations()->where('conversation_state', 'CLOSED')->first();
            if ($winBackDate && $lead->churn_date) {
                $totalDays += \Carbon\Carbon::parse($lead->churn_date)
                                         ->diffInDays($winBackDate->created_at);
            }
        }
        
        return $totalDays / $wonBackLeads->count();
    }

    private function getTopChurnReasons($winBackLeads)
    {
        return $winBackLeads->get()
                           ->groupBy('churn_reason')
                           ->map(function ($leads, $reason) {
                               return [
                                   'reason' => $reason ?: 'Unknown',
                                   'count' => $leads->count(),
                                   'win_back_rate' => $this->calculateWinBackRate($leads)
                               ];
                           })
                           ->sortByDesc('count')
                           ->take(5);
    }

    /**
     * Get lead performance analytics
     */
    public function getLeadPerformanceAnalytics($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subMonth();
        $dateTo = $dateTo ?: now();

        $leads = Lead::whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'total_leads' => $leads->count(),
            'outreached' => $leads->where('status', '!=', Lead::STATUS_NEW)->count(),
            'replied' => $leads->where('status', Lead::STATUS_REPLIED)->count(),
            'closed' => $leads->where('status', Lead::STATUS_CLOSED)->count(),
            'conversion_rate' => $this->calculateConversionRate($leads),
            'average_deal_size' => $leads->where('status', Lead::STATUS_CLOSED)->avg('final_price'),
            'top_performing_products' => $this->getTopPerformingProducts($leads),
        ];
    }

    private function calculateConversionRate($leads)
    {
        $outreached = $leads->where('status', '!=', Lead::STATUS_NEW)->count();
        $closed = $leads->where('status', Lead::STATUS_CLOSED)->count();
        
        return $outreached > 0 ? ($closed / $outreached) * 100 : 0;
    }

    private function getTopPerformingProducts($leads)
    {
        return $leads->where('status', Lead::STATUS_CLOSED)
                    ->with('product')
                    ->get()
                    ->groupBy('product_id')
                    ->map(function ($productLeads) {
                        return [
                            'product_name' => $productLeads->first()->product->name,
                            'sales_count' => $productLeads->count(),
                            'total_revenue' => $productLeads->sum('final_price'),
                            'average_price' => $productLeads->avg('final_price'),
                        ];
                    })
                    ->sortByDesc('sales_count')
                    ->take(5);
    }
}
```

### 12.2. Updated Console Commands for Contact-Product Integration

```php
// Updated Daily Outreach Command snippet
private function processContactForProducts($contact, $productIds)
{
    foreach ($productIds as $productId) {
        try {
            $lead = $contact->getLeadForProduct($productId);
            
            // Skip if no active lead exists for this product
            if (!$lead || $lead->status !== Lead::STATUS_NEW) {
                continue;
            }

            // Check timezone and business hours
            if (!$lead->isInBusinessHours()) {
                continue;
            }

            // Send product-specific outreach
            $variant = $this->selectOutreachVariant();
            $result = $this->whatsappService->sendOutreachMessage($lead, $variant);

            if ($result['success']) {
                $lead->update([
                    'status' => Lead::STATUS_OUTREACHED,
                    'last_outreach_at' => now()
                ]);

                $this->info("✓ Outreach sent for {$lead->getProductName()} to {$lead->getContactName()}");
            }

        } catch (\Exception $e) {
            $this->error("Error processing product {$productId} for contact {$contact->id}: " . $e->getMessage());
        }
    }
}
```

## 13. Implementation Workflow Example

### 13.1. Creating and Managing Multiple Leads for One Contact

```php
// Example: Create leads for a contact across multiple products
$leadManagement = new LeadManagementService();

// Contact interested in multiple products
$contactId = 123; // Existing contact from events_guests table
$productIds = [1, 3, 7]; // Multiple products they might be interested in

// Create leads with shared information
$leads = $leadManagement->createLeadsForContact($contactId, $productIds, [
    'company_name' => 'Tech Solutions Ltd',
    'industry' => 'Software',
    'source' => 'website_inquiry',
    'metadata' => [
        'campaign' => 'Q4_promotion',
        'referral_source' => 'linkedin'
    ]
]);

// Each lead will now independently track conversations and sales for each product
foreach ($leads as $lead) {
    echo "Created lead for {$lead->getProductName()} targeting {$lead->getContactName()}\n";
}
```

### 13.2. Handling Successful Sales

```php
// When AI successfully closes a sale
$aiResponse = [
    'message_text' => 'Great! I'll send you the invoice for our Premium Package at $2,500. Thank you for choosing us!',
    'new_conversation_state' => 'CLOSED',
    'handoff_flag' => true,
    'handoff_reason_code' => 'SALE_COMPLETED',
    'quoted_price' => 2500,
    'discount_offered' => 10 // 10% discount applied
];

// Lead status automatically updates to CLOSED
// Other active leads for the same contact remain active for other products
// Human sales team gets notification to process the order
```

This comprehensive update transforms the AI Sales Agent system to work with your existing contact and product infrastructure while adding sophisticated sales tracking, price negotiation, and multi-product sales capabilities per contact.

## 14. AI Sales Agent Configuration Integration Summary

### 14.1. Agent Configuration Impact on System Behavior

The **AI Sales Agent Configuration** system from `AiSalesAgentController.php` now drives all AI behavior throughout the system:

**🎯 Communication Personality & Tone:**
- **Professional**: "Maintain a professional, business-focused tone"
- **Friendly**: "Be warm, approachable, and conversational" 
- **Consultative**: "Act as a trusted advisor, asking thoughtful questions"
- **Direct**: "Be clear, concise, and straight to the point"

**⏰ Working Hours & Availability:**
- `always_available: boolean` - 24/7 operation or business hours only
- `business_days: array` - Specific days of operation (monday-sunday)
- `start_time/end_time` - Daily operating hours with timezone support
- `out_of_hours_message` - Custom message for outside business hours

**🌍 Multi-Language Support:**
- `primary_language` - Main communication language (en, sw, fr, ar, pt, am)
- `additional_languages` - Secondary languages (sw, fr, ar, pt, am, yo, ig, ha)
- `auto_detect_language` - Automatic language detection
- `language_fallback_message` - Response for unsupported languages

**💰 Negotiation & Pricing Controls:**
- `allow_negotiation: boolean` - Enable/disable price negotiations
- `max_discount_allowed: 0-50%` - Maximum discount AI can offer
- `accept_installments: boolean` - Allow payment plans
- `max_installments: 2-12` - Maximum payment splits
- `min_down_payment: 10-100%` - Minimum upfront payment required
- `negotiation_script` - Custom negotiation guidelines

**⚠️ Escalation & Handoff Triggers:**
- `escalation_triggers: array` - Auto-escalate for: complex-questions, complaints, large-orders, payment-issues, angry-customer
- `large_order_threshold` - Order value requiring human approval
- `fallback_number` - Human agent contact number
- `fallback_person` - Specific person to escalate to

**📞 Follow-up & Engagement:**
- `auto_followup: boolean` - Enable automatic follow-up sequences
- `followup_delay: 1-168 hours` - Time between follow-up attempts (max 1 week)
- `max_followups: 1-5` - Maximum follow-up attempts
- `followup_message` - Custom follow-up message template

**🔔 Notification Preferences:**
- `notify_on_deal: boolean` - Alert on successful sales
- `notification_methods: array` - whatsapp, email, sms
- `additional_notifications: array` - new-lead, escalation, errors

### 14.2. System Integration Points

**1. OpenAI Service Integration:**
```php
// All AI responses now respect agent configuration
$aiAgent = $lead->aiSalesAgent;
$prompt = $this->buildPromptWithAgent($lead, $product, $aiAgent, $customerMessage, $currentState);
$aiResponse = $this->applyAgentConstraints($aiResponse, $aiAgent, $product);
```

**2. WhatsApp Webhook Processing:**
```php
// Instant responses check agent availability
if (!$aiAgent->isAvailableNow()) {
    return $aiAgent->getOutOfHoursResponse();
}
```

**3. Cron Job Scheduling:**
```php
// Daily outreach respects agent working hours
foreach ($leadsToContact->groupBy('ai_sales_agent_id') as $agentLeads) {
    $aiAgent = $agentLeads->first()->aiSalesAgent;
    if ($this->isWithinWorkingHours($aiAgent)) {
        // Process outreach with agent's communication tone
    }
}
```

**4. Negotiation & Pricing Logic:**
```php
// AI cannot exceed agent's discount limits
if ($aiResponse['discount_offered'] > $aiAgent->max_discount_allowed) {
    $aiResponse['discount_offered'] = $aiAgent->max_discount_allowed;
}
```

### 14.3. Final Implementation Architecture

**📊 Database Schema:**
- `ai_sales_agents` table with 30+ configuration fields
- `leads` table references `ai_sales_agent_id`
- All conversations inherit agent personality and constraints
- Multi-language support with fallback handling

**🤖 AI Conversation Engine:**
- Agent personality drives OpenAI prompt construction
- Real-time availability checking with timezone support
- Automatic escalation based on agent trigger configuration
- Negotiation limits enforced at response generation level

**⚡ Webhook vs Cron Processing:**
- **Instant Webhook**: Customer messages processed immediately during agent business hours
- **Scheduled Cron**: Follow-ups, outreach campaigns, and after-hours processing
- **Hybrid Fallback**: Critical failures stored for cron processing backup

**🎯 Customer Experience:**
- Consistent personality across all touchpoints (outreach → conversation → follow-up)
- Respect for business hours with professional out-of-hours handling  
- Multi-language support with automatic detection and fallbacks
- Smart escalation to humans for complex issues or large orders

### 14.4. Development Ready Status

✅ **Complete Database Schema** - All tables with proper relationships and constraints  
✅ **Eloquent Models** - Full model relationships with business logic methods  
✅ **AI Service Integration** - OpenAI prompts respect all agent configurations  
✅ **Webhook Architecture** - Real-time processing with business hours intelligence  
✅ **Cron Job System** - Background processing for follow-ups and campaigns  
✅ **Negotiation Engine** - Price constraints and installment handling  
✅ **Escalation Logic** - Smart handoffs based on configurable triggers  
✅ **Multi-Language Support** - Primary/secondary languages with auto-detection  

**🚀 Ready for Implementation:** This requirement document now provides a complete, implementation-ready specification that integrates all AI Sales Agent configurations with the existing SafariChat infrastructure.

## 14. Webhook vs Cron Architecture Summary

### 14.1. Instant Message Processing (NEW)
- **Primary Path**: WaSender API processes customer messages instantly
- **Business Hours**: Messages during 9 AM - 6 PM local time get immediate AI responses
- **After Hours**: Messages queued for next business day processing
- **Fallback**: Critical errors stored for cron processing as backup

### 14.2. Cron Job Roles (UPDATED)
- **ConversationEngineCommand**: Follow-ups and scheduled messaging only
- **DailyOutreachCommand**: Initial outreach campaigns (unchanged)
- **NoReplyChaseCommand**: Chase sequences for non-responsive leads (unchanged)
- **WinBackOutreachCommand**: Churned customer re-engagement (unchanged)

### 14.3. Customer Experience Impact
- **Before**: 10-minute delays for all responses via cron processing
- **After**: Instant responses during business hours, professional out-of-hours handling
- **Reliability**: Hybrid webhook + cron architecture ensures no messages are lost
- **Performance**: Real-time AI conversations with full context awareness
