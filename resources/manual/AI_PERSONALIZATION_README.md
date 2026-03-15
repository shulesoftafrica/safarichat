# AI Message Personalization System

**Quick start guide for developers working with SafariChat's AI-powered message personalization.**

## 🚀 Quick Start

### 1. Configure OpenAI API Key

```bash
# Add to .env file
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_MODEL=gpt-4o  # Optional: defaults to gpt-4o
OPENAI_MAX_TOKENS=1000  # Optional: defaults to 1000
```

**Get API Key:** https://platform.openai.com/api-keys

### 2. Run Test Script

```bash
php tests/test_personalization.php
```

**Expected output:**
- ✓ Personalized message with AI analysis
- ✓ Confidence score (should be >60%)
- ✓ Optimal send time calculated
- ✓ Contact preferences updated

### 3. Start Queue Workers

```bash
# Terminal 1: High-priority queue
php artisan queue:work --queue=high-priority

# Terminal 2: Personalization queue (for campaigns)
php artisan queue:work --queue=personalization

# Terminal 3: Default queue
php artisan queue:work --queue=default
```

**Production:** Use Laravel Horizon for better queue management.

## 📋 Common Use Cases

### Personalize a Single Message

```php
use App\Services\MessagePersonalizationService;
use App\Models\MessageQueue;

$service = new MessagePersonalizationService();
$message = MessageQueue::find(123);

$result = $service->personalizeMessage($message);

echo "Original: {$message->original_message}\n";
echo "Refined: {$result['refined_message']}\n";
echo "Confidence: {$result['analysis']['ai_confidence_score']}%\n";
echo "Send at: {$result['analysis']['optimal_send_time']}\n";
```

### Dispatch Personalization Job (Async)

```php
use App\Jobs\ProcessPersonalizationJob;

// Single message
ProcessPersonalizationJob::dispatch($messageQueue);

// High priority message (urgent)
$message->priority = 9;
ProcessPersonalizationJob::dispatch($message);

// Delayed start (in 5 minutes)
ProcessPersonalizationJob::dispatch($message)
    ->delay(now()->addMinutes(5));
```

### Launch Campaign with Batch Personalization

```php
use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Jobs\ProcessPersonalizationJob;

// 1. Create campaign
$campaign = Campaign::create([
    'user_id' => $user->id,
    'name' => 'Product Launch Promo',
    'message_template' => 'Hi {{name}}! Check out our new product...',
    'status' => Campaign::STATUS_STAGING
]);

// 2. Create message queue for all recipients
$contacts = BusinessContact::where('opt_out_status', false)->get();

foreach ($contacts as $contact) {
    MessageQueue::create([
        'campaign_id' => $campaign->id,
        'business_contact_id' => $contact->id,
        'user_id' => $user->id,
        'original_message' => str_replace('{{name}}', $contact->name, $campaign->message_template),
        'status' => MessageQueue::STATUS_STAGED,
        'provider' => 'wasender',
        'priority' => 5
    ]);
    $campaign->incrementCounter('queued_count');
}

// 3. Start batch personalization (50 messages at a time)
ProcessPersonalizationJob::dispatch(null, $campaign, 50);

// Campaign status will auto-update:
// staging → processing → scheduled
```

### Check Campaign Progress

```php
$campaign = Campaign::find(123);

echo "Total Recipients: {$campaign->total_recipients}\n";
echo "Queued: {$campaign->queued_count}\n";
echo "Analyzing: {$campaign->analyzing_count}\n";
echo "Personalized: {$campaign->refined_count}\n";
echo "Scheduled: {$campaign->scheduled_count}\n";
echo "Sent: {$campaign->sent_count}\n";
echo "Delivered: {$campaign->delivered_count}\n";
echo "Failed: {$campaign->failed_count}\n";
echo "Human Review: {$campaign->human_review_count}\n";

$progress = ($campaign->refined_count / $campaign->total_recipients) * 100;
echo "Progress: {$progress}%\n";
```

### Manual Review Queue

```php
// Get messages flagged for human review
$reviewQueue = MessageQueue::where('status', MessageQueue::STATUS_HUMAN_REVIEW)
    ->with('contact', 'campaign')
    ->orderBy('created_at', 'asc')
    ->paginate(20);

foreach ($reviewQueue as $message) {
    echo "Contact: {$message->contact->name}\n";
    echo "Original: {$message->original_message}\n";
    echo "Refined: {$message->refined_message}\n";
    echo "Confidence: {$message->ai_confidence_score}%\n";
    echo "Reason: {$message->ai_metadata['reasoning']}\n";
    
    // Admin can approve or reject
    // Approve: $message->approve();
    // Reject: $message->rejectAndUseOriginal();
}
```

### Approve/Reject from Review Queue

```php
// Add to MessageQueue model
public function approve()
{
    $this->update([
        'status' => self::STATUS_PERSONALIZED,
        'human_reviewed' => true,
        'human_reviewed_at' => now()
    ]);
    
    $this->campaign->decrementCounter('human_review_count');
    $this->campaign->incrementCounter('refined_count');
    
    // Schedule for sending
    ProcessPersonalizationJob::scheduleMessageForSending($this);
}

public function rejectAndUseOriginal()
{
    $this->update([
        'status' => self::STATUS_PERSONALIZED,
        'refined_message' => $this->original_message,  // Use original
        'human_reviewed' => true,
        'human_reviewed_at' => now(),
        'ai_confidence_score' => 0  // Mark as non-AI
    ]);
    
    $this->campaign->decrementCounter('human_review_count');
    $this->campaign->incrementCounter('refined_count');
}
```

## 🧠 Understanding AI Analysis

### Confidence Scores

| Score | Meaning | Action |
|-------|---------|--------|
| 90-100% | Excellent | Auto-approve, high quality |
| 70-89% | Good | Auto-approve, standard quality |
| 60-69% | Acceptable | Auto-approve, may need monitoring |
| 40-59% | Low | **Human review required** |
| 0-39% | Very Low | **Human review required** |

**Auto-Review Threshold:** 60% (configurable in service)

### Detected Languages

- `english` - Pure English message
- `swahili` - Pure Swahili message (Kenyan)
- `mixed` - Mix of English and Swahili (common in Kenya)

**AI adapts message to match contact's preferred language.**

### Detected Tones

- `formal` - Professional, business-like
- `casual` - Friendly, conversational
- `urgent` - Time-sensitive, action-oriented
- `friendly` - Warm, personal

**AI matches contact's communication style.**

### Relationship Stages

- `new` - First interaction, no history
- `engaged` - Active conversation, interested
- `converting` - Close to purchase/action
- `customer` - Already purchased, relationship established
- `inactive` - Not engaged recently

**AI adjusts message depth based on stage.**

### Sentiment Filter Results

- `positive` - Happy, satisfied contact → continue
- `neutral` - No strong emotion → continue
- `negative` - Unhappy, frustrated → **human review**
- `opt_out_detected` - Explicit opt-out request → **human review + flag**

**AI protects against sending to unhappy contacts.**

## 📊 Contact Learning

### How It Works

Every time a message is personalized, the system learns about the contact:

```php
// Before first personalization
BusinessContact {
    preferred_language: null,
    preferred_tone: null,
    last_message_sentiment: null,
    engagement_score: 50,
    avg_reply_hour: null
}

// After AI analysis
BusinessContact {
    preferred_language: 'english',      // AI detected from conversation
    preferred_tone: 'casual',           // AI detected from style
    last_message_sentiment: 'positive', // Latest analysis
    engagement_score: 58.5,             // Boosted by 85% confidence (+8.5)
    avg_reply_hour: 14                  // Learned from reply patterns (2 PM)
}
```

### Engagement Score Calculation

```php
// Base score starts at 50
$newScore = min(100, $currentScore + ($aiConfidence * 10));

// Examples:
// 50 + (0.85 * 10) = 58.5
// 60 + (0.90 * 10) = 69.0
// 95 + (0.80 * 10) = 100 (max capped)
```

**Higher engagement scores indicate better AI personalization quality over time.**

### Optimal Send Time

AI calculates best time to send based on:

1. **Contact's learned pattern** (priority 1):
   - `avg_reply_hour` from previous conversations
   - Example: Contact always replies around 10 AM → send at 10 AM

2. **Conversation history analysis** (priority 2):
   - Extract hours from all incoming messages
   - Calculate average reply time
   - Update `avg_reply_hour` for future use

3. **Business hours fallback** (priority 3):
   - Kenya timezone: 9 AM - 5 PM EAT
   - Before 9 AM → schedule at 9 AM today
   - After 5 PM → schedule at 9 AM tomorrow

## 🔧 Configuration Options

### MessagePersonalizationService Settings

**In the service class:**

```php
private $maxTokens = 500;      // Max tokens per API call (cost control)
private $temperature = 0.7;    // AI creativity (0 = deterministic, 1 = creative)
private $cacheTtl = 3600;      // Cache conversation history for 1 hour
private $model = 'gpt-4';      // OpenAI model to use

// Adjust in constructor or via config
public function __construct()
{
    $this->model = config('services.openai.model', 'gpt-4');
    $this->maxTokens = config('services.openai.max_tokens', 500);
}
```

**Environment variables:**

```env
OPENAI_MODEL=gpt-4o           # gpt-4, gpt-4o, gpt-3.5-turbo
OPENAI_MAX_TOKENS=500         # 100-2000 (higher = more detailed but costly)
```

### ProcessPersonalizationJob Settings

```php
// In job class
public $tries = 3;            // Number of retry attempts
public $timeout = 60;         // Maximum execution time (seconds)
public $batchSize = 50;       // Messages per batch (default)

// Auto-pause threshold
private $failureThreshold = 0.1;  // Pause campaign if >10% fail rate
```

### Queue Configuration

**config/queue.php:**

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

**Multiple workers:**

```bash
# config/horizon.php
'environments' => [
    'production' => [
        'high-priority' => [
            'connection' => 'redis',
            'queue' => ['high-priority'],
            'balance' => 'simple',
            'processes' => 5,
            'tries' => 3,
        ],
        'personalization' => [
            'connection' => 'redis',
            'queue' => ['personalization'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

## 💰 Cost Management

### Track OpenAI API Usage

```php
// After personalization
$result = $service->personalizeMessage($message);

$tokensUsed = $result['analysis']['ai_metadata']['tokens_used'];
$cost = ($tokensUsed / 1000) * 0.05;  // Approximate cost for GPT-4

Log::info("AI personalization cost", [
    'message_id' => $message->id,
    'tokens' => $tokensUsed,
    'cost_usd' => $cost
]);
```

### Campaign Cost Estimation

```php
$campaign = Campaign::find(123);

// Estimate OpenAI costs
$totalMessages = $campaign->total_recipients;
$avgTokensPerMessage = 500;
$costPerThousandTokens = 0.05;  // GPT-4 average

$estimatedOpenAICost = ($totalMessages * $avgTokensPerMessage / 1000) * $costPerThousandTokens;

// Estimate total costs
$wasenderCost = $totalMessages * 3;  // 3 credits per message
$totalCreditCost = $wasenderCost + ($estimatedOpenAICost * 100);  // Convert to credits

echo "Estimated OpenAI Cost: $" . number_format($estimatedOpenAICost, 2) . "\n";
echo "Estimated WaSender Credits: " . number_format($wasenderCost, 0) . "\n";
echo "Total Credits Needed: " . number_format($totalCreditCost, 0) . "\n";
```

### Cost Optimization Tips

1. **Increase confidence threshold:**
   ```php
   // Accept lower confidence = fewer API retries
   if ($analysis['ai_confidence_score'] >= 0.5) {  // Down from 0.6
       // Accept
   }
   ```

2. **Reduce max tokens:**
   ```php
   // Less detailed but cheaper
   private $maxTokens = 300;  // Down from 500
   ```

3. **Cache similar messages:**
   ```php
   // If message template is identical for multiple contacts
   $cacheKey = md5($originalMessage);
   Cache::remember($cacheKey, 3600, function() {
       return $service->personalizeMessage($message);
   });
   ```

4. **Batch size tuning:**
   ```php
   // Larger batches = fewer job dispatches
   ProcessPersonalizationJob::dispatch(null, $campaign, 100);  // Up from 50
   ```

## 🐛 Debugging & Troubleshooting

### Check Job Status (Laravel Horizon)

```bash
# Install Horizon (if not already)
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Start Horizon
php artisan horizon

# View dashboard: http://localhost/horizon
```

### View Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Filter for personalization logs
tail -f storage/logs/laravel.log | grep "Personalization"
```

### Common Issues

#### ❌ "OpenAI API key not configured"

**Fix:**
```bash
# Add to .env
OPENAI_API_KEY=sk-your-key-here

# Clear config cache
php artisan config:clear
```

#### ❌ "cURL error 28: Operation timed out"

**Fix:**
```php
// Increase timeout in service
private $timeout = 60;  // Up from 30
```

#### ❌ "Queue worker not processing jobs"

**Fix:**
```bash
# Restart queue workers
php artisan queue:restart

# Check queue status
php artisan queue:work --once  # Test single job
```

#### ❌ "Too many API requests (rate limit)"

**Fix:**
```php
// Reduce batch size
ProcessPersonalizationJob::dispatch(null, $campaign, 25);  // Down from 50

// Add delay between batches (already implemented: 5 seconds)
```

#### ❌ "Low confidence scores (30-50%)"

**Possible causes:**
- No conversation history → AI has no context
- Generic message template → hard to personalize
- Contact has no preferences set → AI guessing

**Fix:**
```php
// Enrich conversation history first
// Or accept lower threshold for new contacts
if ($contact->conversations()->count() < 3) {
    $confidenceThreshold = 0.4;  // Lower for new contacts
}
```

### Debug Mode

```php
// Enable detailed logging in service
Log::info("AI Prompt", ['prompt' => $prompt]);
Log::info("AI Response", ['response' => $apiResponse]);
Log::info("Parsed Analysis", ['analysis' => $analysis]);
```

## 📈 Monitoring & Metrics

### Key Metrics to Track

```php
// Campaign performance
$campaign = Campaign::find(123);

$metrics = [
    'total_recipients' => $campaign->total_recipients,
    'personalization_rate' => ($campaign->refined_count / $campaign->total_recipients) * 100,
    'human_review_rate' => ($campaign->human_review_count / $campaign->total_recipients) * 100,
    'failure_rate' => ($campaign->failed_count / $campaign->total_recipients) * 100,
    'avg_confidence' => MessageQueue::where('campaign_id', $campaign->id)
                            ->avg('ai_confidence_score'),
    'processing_time' => $campaign->started_at?->diffInMinutes($campaign->completed_at)
];
```

### Create Dashboard

```php
// Example Blade component
<div class="campaign-stats">
    <div class="stat">
        <h3>{{ $campaign->refined_count }}</h3>
        <p>Personalized</p>
    </div>
    <div class="stat">
        <h3>{{ number_format($avgConfidence, 1) }}%</h3>
        <p>Avg Confidence</p>
    </div>
    <div class="stat">
        <h3>{{ $campaign->human_review_count }}</h3>
        <p>Needs Review</p>
    </div>
    <div class="stat">
        <h3>${{ number_format($estimatedCost, 2) }}</h3>
        <p>AI Cost</p>
    </div>
</div>
```

## 🔐 Security Best Practices

### Protect API Keys

```bash
# NEVER commit .env to version control
echo ".env" >> .gitignore

# Use environment-specific keys
# .env.production - Production key
# .env.staging - Staging key
# .env.local - Local key
```

### Validate User Input

```php
// Before creating campaign
$validated = $request->validate([
    'message_template' => 'required|string|max:1000',
    'recipient_ids' => 'required|array|max:100000',
]);

// Sanitize user input
$message = strip_tags($validated['message_template']);
```

### Rate Limiting

```php
// Add to routes/api.php
Route::middleware(['auth', 'throttle:100,1'])->group(function() {
    Route::post('/campaigns/launch', [CampaignController::class, 'launch']);
});
```

## 📚 Additional Resources

### Documentation
- [OpenAI API Docs](https://platform.openai.com/docs)
- [Laravel Queues](https://laravel.com/docs/queues)
- [Laravel Horizon](https://laravel.com/docs/horizon)

### Internal Docs
- [PHASE_1_COMPLETION_REPORT.md](./PHASE_1_COMPLETION_REPORT.md) - Database foundation
- [PHASE_2_COMPLETION_REPORT.md](./PHASE_2_COMPLETION_REPORT.md) - AI integration (this phase)
- [ADVANCED_MESSAGING_DETAILED.md](./ADVANCED_MESSAGING_DETAILED.md) - Full system specification

### Testing
- **Test Script:** `tests/test_personalization.php`
- **Unit Tests:** `tests/Unit/Services/MessagePersonalizationServiceTest.php` (TODO)
- **Feature Tests:** `tests/Feature/CampaignPersonalizationTest.php` (TODO)

---

**Need Help?** Check the [PHASE_2_COMPLETION_REPORT.md](./PHASE_2_COMPLETION_REPORT.md) for detailed technical documentation.

**Version:** 1.0  
**Last Updated:** February 27, 2026  
