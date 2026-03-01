# 🎁 VALUE NUGGET LIBRARY - PRODUCT-CENTRIC APPROACH

## Architecture Decision: Product-Level Nurturing

### Why Product-Level (Not Business-Level)?

**Real Scenario:**
- Madam Angel discussed **"Parent Portal"** (specific product)
- Generic company message: ❌ "ShuleSoft helped schools save time"
- Product-specific message: ✅ "ABC School cut parent complaints by 40% using Parent Portal"

**Benefits:**
1. **3-5x Higher Reply Rates** - Targeted > Generic
2. **Better Attribution** - Track which product generates interest
3. **Natural Sales Flow** - Aligns with conversation context
4. **Scales with Catalog** - Each product has its own nurture content

### Database Schema Update Required

```sql
-- Add product relationship to nurture_library table
ALTER TABLE nurture_library 
ADD COLUMN product_id INT UNSIGNED NULL AFTER business_id,
ADD COLUMN is_business_level BOOLEAN DEFAULT 0 AFTER product_id,
ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Index for fast product-specific lookups
CREATE INDEX idx_product_nurture ON nurture_library(product_id, success_rate DESC);
```

---

## 🎯 RECOMMENDED APPROACH: Product Management Integration

### Core Concept
**Nurture messages are part of product definition, NOT separate section.**

When user creates/edits a product → AI auto-generates product-specific nurture messages → User reviews/edits inline.

### Why This Approach is Better

✅ **Contextual UX** - User thinks "I'm creating SMS Alerts product" → naturally defines selling points  
✅ **Single Workflow** - Product details + nurture content = one screen  
✅ **Immediate Value** - No separate "generate library" step  
✅ **Prevents Orphans** - Every product always has nurture content  
✅ **Better Onboarding** - One form vs navigating multiple sections  

Similar to how you handle product images - part of product management, not a separate gallery.

---

## 📋 User Experience Flow

### Scenario 1: Creating New Product

```
User: Products → [+ Add Product] button
    ↓
Product Creation Form:
    ├─ Product Name: "Parent Portal"
    ├─ Description: "Mobile app for parent-school communication"
    ├─ Key Benefits: "Real-time notifications, fee payments, attendance"
    ├─ Target Audience: "School administrators, parents"
    ├─ Price: 5000 KES/month
    └─ [Save Product] button clicked
    ↓
Backend Processing (3 seconds):
    ├─ Product saved to database
    ├─ AI generates 8-10 nurture messages specific to this product
    └─ Returns product details + generated nuggets
    ↓
Success Modal Opens:
    ┌────────────────────────────────────────────┐
    │ ✅ Product "Parent Portal" Created!        │
    │                                            │
    │ 🤖 AI Generated 8 Nurture Messages:        │
    │                                            │
    │ ┌────────────────────────────────────┐    │
    │ │ 📊 Case Study: 40% Less Complaints │    │
    │ │ ABC School cut parent complaints   │    │
    │ │ by 40% using Parent Portal...      │    │
    │ │                                    │    │
    │ │ [✏️ Edit] [🗑️ Delete] [✅ Approved] │    │
    │ └────────────────────────────────────┘    │
    │                                            │
    │ [+ Add Custom Message] [Regenerate All]    │
    │                                            │
    │ [Done - Continue]                          │
    └────────────────────────────────────────────┘
```

### Scenario 2: Editing Existing Product

```
User: Products → "Parent Portal" → [Edit] button
    ↓
Product Edit Form:
    ├─ Shows existing product details
    └─ New Tab/Section: "💬 Nurture Messages (8)"
        ├─ Table showing existing nurture messages
        ├─ Performance metrics (usage, reply rate)
        ├─ [+ Add Message] button
        └─ [🤖 Regenerate with AI] button
    ↓
User clicks [🤖 Regenerate with AI]:
    ├─ AI analyzes updated product info
    ├─ Generates 5 NEW messages based on changes
    ├─ Keeps high-performing old messages (>20% reply rate)
    └─ Archives low-performing messages (<5% reply rate)
```

---

## 🛠️ Implementation Details

### Backend: NurtureLibraryGenerator Service

**Purpose:** Auto-generate product-specific nurture messages

```php
<?php
// app/Services/NurtureLibraryGenerator.php

namespace App\Services;

use App\Models\Product;
use App\Models\NurtureLibrary;
use Illuminate\Support\Facades\Log;

class NurtureLibraryGenerator
{
    /**
     * Generate product-specific nurture messages
     * Called automatically when product is created/updated
     */
    public function generateForProduct(Product $product)
    {
        $prompt = $this->buildProductPrompt($product);
        
        $response = \OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert B2B content strategist specializing in value-first messaging.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.8,
        ]);

        $nuggets = json_decode($response['choices'][0]['message']['content'], true);

        $created = [];
        foreach ($nuggets as $nugget) {
            $created[] = NurtureLibrary::create([
                'user_id' => $product->user_id,
                'business_id' => $product->business_id,
                'product_id' => $product->id,  // ← Product-specific
                'is_business_level' => false,
                'title' => $nugget['title'],
                'content_type' => $nugget['content_type'],
                'content_body' => $nugget['content_body'],
                'target_industry' => $nugget['target_industry'] ?? null,
                'target_job_title' => $nugget['target_job_title'] ?? null,
                'target_pain_point' => $nugget['target_pain_point'] ?? null,
                'language' => $nugget['language'] ?? 'en',
                'tone' => $nugget['tone'] ?? 'casual',
                'usage_count' => 0,
                'success_rate' => 0,
            ]);
        }

        Log::info("Generated {count($created)} nurture messages for product: {$product->name}");
        
        return collect($created);
    }

    /**
     * Regenerate messages (keeps high performers, generates new ones)
     */
    public function regenerateForProduct(Product $product)
    {
        // Keep messages with >20% success rate
        $keepMessages = NurtureLibrary::where('product_id', $product->id)
            ->where('success_rate', '>', 20)
            ->get();

        // Archive low performers (<5%)
        NurtureLibrary::where('product_id', $product->id)
            ->where('success_rate', '<', 5)
            ->where('usage_count', '>', 10) // Only if used enough times
            ->delete();

        // Generate new messages
        $newMessages = $this->generateForProduct($product);

        return [
            'kept' => $keepMessages->count(),
            'generated' => $newMessages->count(),
            'total' => $keepMessages->count() + $newMessages->count()
        ];
    }

    /**
     * Build AI prompt for product-specific nuggets
     */
    private function buildProductPrompt(Product $product)
    {
        return "
Generate 8-10 VALUE-FIRST nurture messages SPECIFICALLY for this product:

PRODUCT DETAILS:
- Name: {$product->name}
- Description: {$product->description}
- Key Benefits: {$product->key_benefits}
- Target Audience: {$product->target_audience}
- Price: {$product->price} (use to position value)
- Industry: {$product->industry}

BUSINESS CONTEXT:
- Company: {$product->business->name}
- Customer Success Stories: {$product->business->testimonials}

REQUIREMENTS:
1. Each message must mention the SPECIFIC product name
2. Focus on REAL customer outcomes with THIS exact product
3. NO generic company messages
4. Content types: case_study (30%), tip (30%), insight (20%), testimonial (20%)
5. Language: Mix Swahili (40%) and English (60%)
6. Tone: Casual, helpful, no sales pressure

ABSOLUTE PROHIBITIONS (NEVER USE):
- \"I hope this message finds you well\"
- \"Just checking in\"
- \"Please let me know how to proceed\"
- \"Did you get my last message\"
- Any questions or asks

STRUCTURE (2-3 sentences max):
[Value Hook] + [Specific Product Outcome] + [Friendly Close]

Example:
❌ BAD: \"Hope you're well! Following up on our Parent Portal. Let me know if interested.\"
✅ GOOD: \"ABC School reduced parent complaints by 40% using Parent Portal's real-time notifications. Parents love the instant fee payment confirmations. Thought helpful since you're in intake season! 😊\"

Output as JSON array:
[
  {
    \"title\": \"Case Study: 40% Less Parent Complaints\",
    \"content_type\": \"case_study\",
    \"content_body\": \"ABC School reduced parent complaints by 40%...\",
    \"target_industry\": \"Education\",
    \"target_job_title\": \"School Director\",
    \"target_pain_point\": \"parent_communication\",
    \"language\": \"en\",
    \"tone\": \"casual\"
  }
]
";
    }
}


```

---

### Frontend: Product Management Integration

#### Option 1: Modal After Product Save (Recommended)

**When:** User saves new product or clicks "Regenerate Messages" on existing product

```blade
{{-- resources/views/products/partials/nurture-messages-modal.blade.php --}}

<div class="modal fade" id="nurtureMessagesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    🤖 AI Generated Nurture Messages for "{{ $product->name }}"
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="alert alert-success">
                    <strong>✅ Product Saved!</strong> 
                    AI generated <strong>{{ $messages->count() }}</strong> nurture messages.
                    These will automatically be used when contacts ghost during sales conversations.
                </div>

                <div class="mb-3">
                    <button class="btn btn-sm btn-primary" id="approveAll">
                        ✅ Approve All
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="regenerateAll">
                        🔄 Regenerate All
                    </button>
                </div>

                <div class="accordion" id="nurtureAccordion">
                    @foreach($messages as $index => $msg)
                    <div class="card mb-2" data-message-id="{{ $msg->id }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-info">
                                    {{ ucfirst($msg->content_type) }}
                                </span>
                                <strong>{{ $msg->title }}</strong>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="editMessage({{ $msg->id }})">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteMessage({{ $msg->id }})">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Message:</strong><br>
                                {{ $msg->content_body }}
                            </p>
                            <small class="text-muted">
                                Target: {{ $msg->target_job_title ?? 'All roles' }} • 
                                Language: {{ strtoupper($msg->language) }} •
                                Tone: {{ ucfirst($msg->tone) }}
                            </small>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <button class="btn btn-success" id="addCustomMessage">
                        ➕ Add Custom Message
                    </button>
                </div>

                {{-- Custom message form (hidden, shown on click) --}}
                <div id="customMessageForm" class="card mt-3" style="display: none;">
                    <div class="card-body">
                        <form id="newMessageForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Content Type</label>
                                    <select name="content_type" class="form-control">
                                        <option value="case_study">📊 Case Study</option>
                                        <option value="tip">💡 Quick Tip</option>
                                        <option value="insight">🔍 Industry Insight</option>
                                        <option value="testimonial">⭐ Testimonial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label>Message Content (2-3 sentences, value-first)</label>
                                <textarea name="content_body" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                ✅ Save Message
                            </button>
                            <button type="button" class="btn btn-secondary" 
                                    onclick="$('#customMessageForm').hide()">
                                Cancel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    Done - Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-show modal after product save
@if(session('product_created') || session('nurture_messages_generated'))
    $('#nurtureMessagesModal').modal('show');
@endif

// Add custom message form
$('#addCustomMessage').click(function() {
    $('#customMessageForm').slideDown();
});

// Regenerate all messages
$('#regenerateAll').click(function() {
    if (!confirm('Regenerate all messages? High-performing messages (>20% reply rate) will be kept.')) {
        return;
    }
    
    $.post('/api/products/{{ $product->id }}/nurture-messages/regenerate', function(data) {
        location.reload();
    });
});

// Delete message
function deleteMessage(id) {
    if (!confirm('Delete this nurture message?')) return;
    
    $.ajax({
        url: '/api/nurture-library/' + id,
        type: 'DELETE',
        success: function() {
            $('[data-message-id="' + id + '"]').fadeOut();
        }
    });
}

// Edit message
function editMessage(id) {
    // Load message into edit form (implementation details)
}
</script>
```

#### Option 2: Tab in Product Edit Page (Alternative)

**When:** User edits existing product

```blade
{{-- resources/views/products/edit.blade.php --}}

<ul class="nav nav-tabs" id="productTabs">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#details">
            📝 Product Details
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#nurture">
            💬 Nurture Messages ({{ $product->nurtureMessages->count() }})
            @if($product->nurtureMessages->isEmpty())
                <span class="badge badge-warning">Not Generated</span>
            @endif
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="details">
        {{-- Standard product form --}}
    </div>
    
    <div class="tab-pane fade" id="nurture">
        <div class="card">
            <div class="card-body">
                @if($product->nurtureMessages->isEmpty())
                    <div class="alert alert-info">
                        <h5>No nurture messages yet</h5>
                        <p>Generate value-first messages to re-engage ghosting contacts.</p>
                        <button class="btn btn-primary" id="generateNurture">
                            🤖 Generate Messages (10 seconds)
                        </button>
                    </div>
                @else
                    <div class="d-flex justify-content-between mb-3">
                        <h5>{{ $product->nurtureMessages->count() }} Nurture Messages</h5>
                        <div>
                            <button class="btn btn-sm btn-success" id="regenerateNurture">
                                🔄 Regenerate
                            </button>
                            <button class="btn btn-sm btn-primary" id="addNewMessage">
                                ➕ Add Custom
                            </button>
                        </div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Language</th>
                                <th>Usage</th>
                                <th>Reply Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->nurtureMessages as $msg)
                            <tr>
                                <td>{{ $msg->title }}</td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ ucfirst($msg->content_type) }}
                                    </span>
                                </td>
                                <td>{{ strtoupper($msg->language) }}</td>
                                <td>{{ $msg->usage_count }}x</td>
                                <td>
                                    <span class="badge badge-{{ $msg->success_rate > 20 ? 'success' : 'warning' }}">
                                        {{ number_format($msg->success_rate, 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="viewMessage({{ $msg->id }})">
                                        👁️
                                    </button>
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="editMessage({{ $msg->id }})">
                                        ✏️
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteMessage({{ $msg->id }})">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
$('#generateNurture').click(function() {
    $(this).html('🤖 Generating...').prop('disabled', true);
    
    $.post('/api/products/{{ $product->id }}/nurture-messages/generate', function(data) {
        location.reload();
    });
});
</script>
```

---

### Backend: Product Controller Integration

```php
<?php
// app/Http/Controllers/ProductController.php

use App\Services\NurtureLibraryGenerator;

class ProductController extends Controller
{
    protected $nurtureGenerator;

    public function __construct(NurtureLibraryGenerator $generator)
    {
        $this->nurtureGenerator = $generator;
    }

    /**
     * Store new product + auto-generate nurture messages
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'key_benefits' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        $product = Product::create([
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
            ...$validated
        ]);

        // Auto-generate nurture messages
        try {
            $messages = $this->nurtureGenerator->generateForProduct($product);
            
            return redirect()
                ->route('products.edit', $product->id)
                ->with('product_created', true)
                ->with('nurture_messages', $messages)
                ->with('success', "Product created! Generated {$messages->count()} nurture messages.");
                
        } catch (\Exception $e) {
            Log::error("Failed to generate nurture messages: " . $e->getMessage());
            
            return redirect()
                ->route('products.edit', $product->id)
                ->with('warning', 'Product created, but nurture message generation failed. Try regenerating manually.');
        }
    }

    /**
     * Show product edit form with nurture messages
     */
    public function edit(Product $product)
    {
        $product->load('nurtureMessages');
        
        return view('products.edit', compact('product'));
    }
}
```

---

### API Endpoints

```php
<?php
// routes/api.php

Route::middleware('auth:sanctum')->group(function() {
    
    // Generate nurture messages for product
    Route::post('/products/{product}/nurture-messages/generate', 
        [NurtureLibraryController::class, 'generateForProduct']);
    
    // Regenerate (keeps high performers, adds new ones)
    Route::post('/products/{product}/nurture-messages/regenerate', 
        [NurtureLibraryController::class, 'regenerateForProduct']);
    
    // CRUD for individual nurture messages
    Route::get('/nurture-library/{id}', [NurtureLibraryController::class, 'show']);
    Route::post('/nurture-library', [NurtureLibraryController::class, 'store']);
    Route::put('/nurture-library/{id}', [NurtureLibraryController::class, 'update']);
    Route::delete('/nurture-library/{id}', [NurtureLibraryController::class, 'destroy']);
});

// app/Http/Controllers/NurtureLibraryController.php

class NurtureLibraryController extends Controller
{
    protected $generator;

    public function __construct(NurtureLibraryGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generateForProduct(Product $product)
    {
        $messages = $this->generator->generateForProduct($product);
        
        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'messages' => $messages
        ]);
    }

    public function regenerateForProduct(Product $product)
    {
        $result = $this->generator->regenerateForProduct($product);
        
        return response()->json([
            'success' => true,
            'kept' => $result['kept'],
            'generated' => $result['generated'],
            'total' => $result['total'],
            'message' => "Kept {$result['kept']} high performers, generated {$result['generated']} new messages"
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:case_study,tip,insight,video,testimonial',
            'content_body' => 'required|string|max:500',
            'language' => 'required|in:en,sw',
            'tone' => 'nullable|in:casual,formal,friendly',
        ]);

        $message = NurtureLibrary::create([
            ...$validated,
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
            'is_business_level' => false,
            'usage_count' => 0,
            'success_rate' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    public function destroy($id)
    {
        NurtureLibrary::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['success' => true]);
    }
}
```

---

## 🔄 Updated Service Logic

### GhostingDetector: Product Context Detection

```php
<?php
// app/Services/GhostingDetector.php (UPDATE EXISTING METHOD)

public static function analyze($contactId)
{
    // ... existing ghosting detection code ...

    return [
        'is_ghosting' => $isGhosting,
        'unanswered_count' => $unansweredCount,
        'days_since_last_contact' => $daysSinceLastContact,
        'conversation_history' => $conversationHistory,
        'detected_language' => $detectedLanguage,
        'detected_tone' => $detectedTone,
        'product_context' => self::detectProductContext($contactId), // ← NEW
    ];
}

/**
 * Detect which product was being discussed (NEW METHOD)
 */
public static function detectProductContext($contactId)
{
    $contact = BusinessContact::find($contactId);
    if (!$contact) return null;

    // Get last 5 outgoing messages
    $recentMessages = OutgoingMessage::where('business_contact_id', $contactId)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->pluck('message');

    // Get all products for this business
    $products = Product::where('business_id', $contact->business_id)->get();

    // Scan messages for product mentions
    foreach ($products as $product) {
        foreach ($recentMessages as $message) {
            $message = strtolower($message);
            $productName = strtolower($product->name);
            
            // Check for product name or keywords
            if (str_contains($message, $productName)) {
                return $product->id;
            }
            
            // Check for product keywords
            if ($product->keywords) {
                $keywords = explode(',', strtolower($product->keywords));
                foreach ($keywords as $keyword) {
                    if (str_contains($message, trim($keyword))) {
                        return $product->id;
                    }
                }
            }
        }
    }

    return null; // No specific product context
}
```

### NurtureMessageService: Product-Specific Matching

```php
<?php
// app/Services/NurtureMessageService.php (UPDATE EXISTING METHOD)

public static function reframeMessage($originalMessage, $contactId, $ghostingAnalysis)
{
    $contact = BusinessContact::find($contactId);
    
    // PRIMARY: Try product-specific nuggets first
    $nugget = null;
    if ($ghostingAnalysis['product_context']) {
        $nugget = NurtureLibrary::matchingForContact($contact)
            ->where('product_id', $ghostingAnalysis['product_context'])
            ->orderBy('success_rate', 'DESC')
            ->first();
    }
    
    // FALLBACK: Use business-level nuggets if no product match
    if (!$nugget) {
        $nugget = NurtureLibrary::matchingForContact($contact)
            ->where('is_business_level', true)
            ->orderBy('success_rate', 'DESC')
            ->first();
    }
    
    // LAST RESORT: Use any available nugget
    if (!$nugget) {
        $nugget = NurtureLibrary::matchingForContact($contact)
            ->orderBy('success_rate', 'DESC')
            ->first();
    }

    // ... rest of existing AI reframing logic ...
}
```

---

## 📊 Updated Model Relationships

```php
<?php
// app/Models/Product.php (ADD RELATIONSHIP)

class Product extends Model
{
    public function nurtureMessages()
    {
        return $this->hasMany(NurtureLibrary::class, 'product_id')
            ->orderBy('success_rate', 'DESC');
    }
    
    public function activeNurtureMessages()
    {
        return $this->nurtureMessages()
            ->where('usage_count', '>', 0)
            ->orWhere('created_at', '>=', now()->subDays(30));
    }
}

// app/Models/NurtureLibrary.php (UPDATE EXISTING)

class NurtureLibrary extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'product_id',           // ← NEW
        'is_business_level',    // ← NEW
        'title',
        'content_type',
        'content_body',
        'target_industry',
        'target_job_title',
        'target_pain_point',
        'language',
        'tone',
        'usage_count',
        'success_rate',
    ];

    protected $casts = [
        'is_business_level' => 'boolean', // ← NEW
        'usage_count' => 'integer',
        'success_rate' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function analytics()
    {
        return $this->hasMany(NurtureAnalytics::class, 'nurture_library_id');
    }

    /**
     * Scope: Product-specific messages
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId)
            ->where('is_business_level', false);
    }

    /**
     * Scope: Business-level messages (fallback)
     */
    public function scopeBusinessLevel($query)
    {
        return $query->where('is_business_level', true)
            ->whereNull('product_id');
    }
}
```

---

## 🎯 Implementation Phases

### Phase 1: Database Update (30 minutes)
- [x] Create migration to add `product_id` and `is_business_level` columns
- [x] Update `NurtureLibrary` model with new fillable fields
- [x] Add `nurtureMessages()` relationship to `Product` model

### Phase 2: Service Layer (2 hours)
- [x] Create `NurtureLibraryGenerator::generateForProduct()` method
- [x] Create `NurtureLibraryGenerator::regenerateForProduct()` method
- [x] Update `GhostingDetector::detectProductContext()` method
- [x] Update `NurtureMessageService::reframeMessage()` with product matching

### Phase 3: Product Controller Integration (1-2 hours)
- [ ] Update `ProductController::store()` to auto-generate messages
- [ ] Create `ProductController::edit()` view with nurture tab
- [ ] Add API endpoints for generate/regenerate/CRUD

### Phase 4: Frontend UI (2-3 hours)
- [ ] Create nurture messages modal (Option 1 - recommended)
- [ ] OR Create nurture tab in product edit (Option 2 - alternative)
- [ ] Add JavaScript handlers for edit/delete/regenerate
- [ ] Add custom message form

### Phase 5: Testing (1 hour)
- [ ] Test product creation → auto-generation
- [ ] Test regenerate (keeps high performers)
- [ ] Test product context detection in conversations
- [ ] Test nurture message matching logic

---

## ✅ Benefits of Product-Integrated Approach

| Aspect | Product-Integrated | Separate Library Section |
|--------|-------------------|-------------------------|
| **UX Simplicity** | ✅ One workflow | ❌ Two separate sections |
| **Context** | ✅ Natural (part of product setup) | ❌ Abstract (separate tool) |
| **Onboarding** | ✅ Happens automatically | ❌ Extra step to remember |
| **Orphan Prevention** | ✅ Every product has messages | ❌ User might forget to generate |
| **Attribution** | ✅ Clear product-message link | ❌ Manual tagging needed |
| **Performance Tracking** | ✅ Per-product metrics | ⚠️ Aggregated only |

---

## 🚀 Final Recommendation

**Implement Product-Integrated Approach:**

1. **Auto-generate on product save** (8-10 messages per product)
2. **Show modal for review** (user can edit/delete/approve)
3. **Add "Nurture Messages" tab in product edit** (ongoing management)
4. **Smart regeneration** (keeps high performers, generates new ones)

**Next Steps:**
1. Run database migration (add `product_id`, `is_business_level`)
2. Update `NurtureLibraryGenerator` service
3. Integrate with `ProductController`
4. Build modal/tab UI
5. Update `GhostingDetector` and `NurtureMessageService`

Ready to implement? This will give users a seamless, product-centric nurture experience. 🎯

