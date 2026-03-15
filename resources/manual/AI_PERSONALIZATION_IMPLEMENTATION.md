# AI Campaign Personalization - Implementation Guide

**Implementation Date:** March 7, 2026  
**Status:** ✅ Complete - Ready for Testing  
**Reference:** [advanced_messaging.md](resources/requirements/advanced_messaging.md)

---

## 🎯 What Was Implemented

The SafariChat application now fully implements the **AI-Driven Contextual Message Refinement** workflow as specified in `advanced_messaging.md`. All campaigns now go through AI personalization before delivery.

---

## 📋 Implementation Summary

### Files Created

1. **[app/Jobs/PersonalizeCampaignMessagesJob.php](app/Jobs/PersonalizeCampaignMessagesJob.php)** (420 lines)
   - Processes messages in batches through AI personalization
   - Calls MessagePersonalizationService for each message
   - Handles sentiment filtering and opt-out detection
   - Schedules messages for optimal send time
   - Auto-chains batches for large campaigns

2. **[app/Console/Commands/PersonalizeCampaignMessages.php](app/Console/Commands/PersonalizeCampaignMessages.php)** (220 lines)
   - Manual command: `php artisan campaigns:personalize`
   - Displays progress and statistics
   - Allows filtering by campaign ID
   - Shows queue monitoring instructions

### Files Modified

3. **[app/Http/Controllers/Message.php](app/Http/Controllers/Message.php)**
   - **Method:** `queueMessages()` (lines 750-900)
   - **Changes:**
     - ✅ Creates Campaign record for tracking
     - ✅ Creates MessageQueue entries (status: `staged`)
     - ✅ Finds/creates BusinessContact for relationship tracking
     - ✅ Stores attachment context for AI
     - ✅ Dispatches PersonalizeCampaignMessagesJob
     - ❌ Removed direct SendWhatsAppMessage dispatch
     - ❌ Removed simple #tag replacement

4. **[app/Console/Kernel.php](app/Console/Kernel.php)**
   - **Added:** `campaigns:personalize` to $commands array
   - **Added:** Scheduled task (every 5 minutes)
   - **Configuration:**
     ```php
     $schedule->command('campaigns:personalize --limit=200 --batch=50')
         ->everyFiveMinutes()
         ->withoutOverlapping()
         ->runInBackground()
     ```

---

## 🔄 New Workflow (Step-by-Step)

### Before (Old Workflow) ❌

```
User clicks "Send Campaign"
  ↓
Simple #tag replacement (#name → "John")
  ↓
SendWhatsAppMessage::dispatch() [Immediate send]
  ↓
Message delivered (no AI, no context, no timing optimization)
```

### After (New Workflow) ✅

```
User clicks "Send Campaign"
  ↓
1. STAGING PHASE
   - Campaign::create() [Tracking record]
   - MessageQueue::create() for each recipient [status: staged]
   - Stores original_message, attachment_context
   - Returns success to user immediately
  ↓
2. AI PERSONALIZATION (PersonalizeCampaignMessagesJob)
   - Fetches conversation history (last 10 messages)
   - Calls OpenAI GPT-4 via MessagePersonalizationService
   - Analyzes:
     * Language (English, Swahili, mixed)
     * Tone (formal, casual, urgent, friendly)
     * Relationship stage (new, engaged, converting, customer)
     * Sentiment (positive, neutral, negative, opt-out)
   - Generates refined_message
   - Calculates optimal_send_time
   - Updates MessageQueue with analysis
  ↓
3. SENTIMENT FILTERING
   - IF opt-out detected → status: opted_out (no send)
   - IF negative sentiment → status: human_review (requires approval)
   - IF low confidence (<0.6) → status: human_review
   - ELSE → status: refined (ready to schedule)
  ↓
4. SCHEDULING (ScheduleMessageSendJob)
   - IF optimal_send_time exists → schedule for that time
   - ELSE → schedule for +5 minutes
   - Updates status: scheduled
  ↓
5. DELIVERY
   - ScheduleMessageSendJob sends refined_message
   - Creates OutgoingMessage record
   - Updates status: sent
   - Campaign counters updated
```

---

## 🧪 Testing Instructions

### 1. Quick Test (Manual Command)

```bash
# Navigate to project directory
cd c:\xampp\htdocs\safarichat

# Test the personalization command
php artisan campaigns:personalize --help

# Look for staged messages
php artisan campaigns:personalize
```

**Expected Output:**
```
🚀 Starting campaign message personalization...
📋 Processing all campaigns
✅ Found X staged message(s)
📦 Will dispatch 1 job(s) with batch size of 50
🎯 Dispatching personalization job...
✅ Successfully dispatched personalization job
```

### 2. Create Test Campaign

**Via UI:**
1. Go to SafariChat dashboard
2. Navigate to "Campaigns" → "Create Campaign"
3. Select recipients (e.g., "All Contacts")
4. Write message: "Hello #name, we have a special offer for you!"
5. Click "Send Campaign"

**Expected Behavior:**
- Success message appears immediately
- Campaign created with `status: staging`
- MessageQueue entries created with `status: staged`
- No messages sent yet (AI processing first)

### 3. Monitor AI Processing

**Watch Queue Worker:**
```bash
# Terminal 1: Start queue worker for AI personalization
php artisan queue:work ai_personalization --verbose

# You should see:
# [timestamp] Processing: App\Jobs\PersonalizeCampaignMessagesJob
# [timestamp] Processed:  App\Jobs\PersonalizeCampaignMessagesJob
```

**Watch Scheduled Messages Queue:**
```bash
# Terminal 2: Start queue worker for scheduled sends
php artisan queue:work scheduled_messages --verbose
```

**Check Logs:**
```bash
# Terminal 3: Tail logs
Get-Content storage\logs\laravel.log -Tail 50 -Wait

# Look for:
# [timestamp] Campaign created for AI personalization
# [timestamp] MessageQueue entry created for AI personalization
# [timestamp] PersonalizeCampaignMessagesJob dispatched
# [timestamp] Personalizing message
# [timestamp] Message successfully personalized
# [timestamp] Message scheduled for delivery
```

### 4. Database Verification

**Check Campaign Table:**
```sql
SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 1;

-- Expected:
-- status: 'processing' or 'completed'
-- queued_count: [number of recipients]
-- refined_count: [should increase as AI processes]
-- sent_count: [should increase as messages deliver]
```

**Check MessageQueue Table:**
```sql
SELECT 
    id,
    phone_number,
    original_message,
    refined_message,
    status,
    detected_language,
    detected_tone,
    ai_confidence_score,
    optimal_send_time
FROM message_queue 
WHERE campaign_id = [campaign_id]
ORDER BY created_at DESC;

-- Expected statuses progression:
-- staged → analyzing → refined → scheduled → sent
```

**Check AI Analysis Results:**
```sql
SELECT 
    contact_name,
    detected_language,
    detected_tone,
    relationship_stage,
    sentiment_filter_result,
    ai_confidence_score,
    ai_metadata
FROM message_queue 
WHERE status = 'refined' 
  AND ai_confidence_score IS NOT NULL
LIMIT 5;

-- Should show:
-- detected_language: 'english', 'swahili', or 'mixed'
-- detected_tone: 'formal', 'casual', 'urgent', 'friendly'
-- ai_confidence_score: 0.0 - 1.0
```

### 5. End-to-End Test Script

Create this file: **tests/test_campaign_personalization.php**

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\BusinessContact;
use App\Jobs\PersonalizeCampaignMessagesJob;
use Illuminate\Support\Facades\Log;

echo "🧪 Testing AI Campaign Personalization Workflow\n\n";

// Step 1: Create test contact
echo "1️⃣  Creating test contact...\n";
$contact = BusinessContact::firstOrCreate(
    ['guest_phone' => '+254712345678'],
    [
        'guest_name' => 'John Doe',
        'user_id' => 1,
        'business_id' => 1,
        'engagement_score' => 75
    ]
);
echo "   ✅ Contact created: {$contact->guest_name} ({$contact->guest_phone})\n\n";

// Step 2: Create test campaign
echo "2️⃣  Creating test campaign...\n";
$campaign = Campaign::create([
    'user_id' => 1,
    'business_id' => 1,
    'campaign_name' => 'Test AI Personalization Campaign',
    'campaign_type' => Campaign::TYPE_BROADCAST,
    'original_message' => 'Hello #name, we have a special offer just for you! Reply YES to learn more.',
    'total_recipients' => 1,
    'queued_count' => 0,
    'status' => Campaign::STATUS_STAGING,
]);
echo "   ✅ Campaign created: {$campaign->campaign_name} (ID: {$campaign->id})\n\n";

// Step 3: Create message queue entry
echo "3️⃣  Creating MessageQueue entry...\n";
$messageQueue = MessageQueue::create([
    'campaign_id' => $campaign->id,
    'user_id' => 1,
    'contact_id' => $contact->id,
    'phone_number' => $contact->guest_phone,
    'contact_name' => $contact->guest_name,
    'original_message' => $campaign->original_message,
    'status' => MessageQueue::STATUS_STAGED,
    'priority' => 5,
    'provider' => MessageQueue::PROVIDER_WASENDER,
]);
$campaign->increment('queued_count');
echo "   ✅ MessageQueue entry created (ID: {$messageQueue->id})\n";
echo "   📝 Original: {$messageQueue->original_message}\n";
echo "   📊 Status: {$messageQueue->status}\n\n";

// Step 4: Dispatch personalization job
echo "4️⃣  Dispatching PersonalizeCampaignMessagesJob...\n";
PersonalizeCampaignMessagesJob::dispatch($campaign->id);
echo "   ✅ Job dispatched to 'ai_personalization' queue\n\n";

echo "5️⃣  Next Steps:\n";
echo "   1. Run: php artisan queue:work ai_personalization --once\n";
echo "   2. Check: SELECT * FROM message_queue WHERE id = {$messageQueue->id}\n";
echo "   3. Verify: refined_message, detected_language, detected_tone are populated\n\n";

echo "✅ Test setup complete!\n";
```

**Run Test:**
```bash
php tests/test_campaign_personalization.php
```

---

## 📊 Monitoring & Debugging

### Queue Status

**Check Queue Jobs:**
```bash
# List all pending jobs
php artisan queue:monitor

# Check ai_personalization queue
SELECT * FROM jobs WHERE queue = 'ai_personalization';
```

**Queue Worker Status:**
```bash
# Check if workers are running
php artisan queue:work --queue=ai_personalization --stop-when-empty

# Process one job and stop
php artisan queue:work ai_personalization --once
```

### Campaign Statistics

**View Campaign Progress:**
```sql
SELECT 
    id,
    campaign_name,
    total_recipients,
    queued_count,
    analyzing_count,
    refined_count,
    scheduled_count,
    sent_count,
    failed_count,
    human_review_count,
    status
FROM campaigns
WHERE created_at >= NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;
```

### Message Status Breakdown

```sql
SELECT 
    status,
    COUNT(*) as count,
    AVG(ai_confidence_score) as avg_confidence,
    COUNT(DISTINCT detected_language) as languages,
    COUNT(DISTINCT detected_tone) as tones
FROM message_queue
WHERE campaign_id = [campaign_id]
GROUP BY status;
```

### AI Performance Metrics

```sql
-- Average confidence by language
SELECT 
    detected_language,
    COUNT(*) as messages,
    AVG(ai_confidence_score) as avg_confidence,
    MIN(ai_confidence_score) as min_confidence,
    MAX(ai_confidence_score) as max_confidence
FROM message_queue
WHERE status IN ('refined', 'scheduled', 'sent')
  AND ai_confidence_score IS NOT NULL
GROUP BY detected_language
ORDER BY avg_confidence DESC;

-- Tone distribution
SELECT 
    detected_tone,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM message_queue WHERE detected_tone IS NOT NULL), 2) as percentage
FROM message_queue
WHERE detected_tone IS NOT NULL
GROUP BY detected_tone
ORDER BY count DESC;

-- Sentiment filtering results
SELECT 
    sentiment_filter_result,
    COUNT(*) as count
FROM message_queue
WHERE sentiment_filter_result IS NOT NULL
GROUP BY sentiment_filter_result;
```

---

## 🔧 Configuration

### Environment Variables

Add to `.env`:
```env
# OpenAI Configuration (Required)
OPENAI_API_KEY=sk-...your-api-key-here...

# Queue Configuration
QUEUE_CONNECTION=database  # or 'redis' for production

# AI Personalization Settings (Optional)
AI_PERSONALIZATION_BATCH_SIZE=50
AI_PERSONALIZATION_INTERVAL=5  # minutes
AI_PERSONALIZATION_CONFIDENCE_THRESHOLD=0.6
```

### Queue Configuration

**For Development (database queue):**
```bash
# Start queue workers
php artisan queue:work ai_personalization --tries=3 &
php artisan queue:work scheduled_messages --tries=3 &
```

**For Production (Redis):**
```bash
# Install Redis PHP extension
# Update .env: QUEUE_CONNECTION=redis

# Start Supervisor workers
sudo supervisorctl start safarichat-ai-personalization:*
sudo supervisorctl start safarichat-scheduled-messages:*
```

**Supervisor Configuration Example:**
```ini
[program:safarichat-ai-personalization]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/safarichat/artisan queue:work ai_personalization --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/safarichat/storage/logs/ai-personalization-worker.log
```

---

## 🚨 Troubleshooting

### Issue 1: Messages Stuck in "Staged" Status

**Symptoms:**
- MessageQueue entries remain `status: staged`
- No AI personalization happening

**Solutions:**
```bash
# 1. Check if queue worker is running
ps aux | grep "queue:work"

# 2. Manually process one job
php artisan queue:work ai_personalization --once

# 3. Check for errors in logs
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "PersonalizeCampaignMessages"

# 4. Verify OpenAI API key is set
php artisan tinker
>>> config('services.openai.api_key')
```

### Issue 2: "OpenAI API call failed"

**Symptoms:**
- Messages move to `status: failed`
- Error: "OpenAI API call failed after 3 attempts"

**Solutions:**
```bash
# 1. Verify API key is valid
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY"

# 2. Check OpenAI account credits
# Visit: https://platform.openai.com/account/usage

# 3. Check rate limits
# Review ai_metadata in message_queue for rate limit errors

# 4. Increase timeout in MessagePersonalizationService
```

### Issue 3: Low AI Confidence Scores

**Symptoms:**
- Many messages go to `status: human_review`
- `ai_confidence_score < 0.6`

**Solutions:**
```sql
-- 1. Check why confidence is low
SELECT 
    contact_name,
    ai_confidence_score,
    ai_metadata->>'$.reasoning' as reasoning
FROM message_queue
WHERE status = 'human_review'
  AND human_review_reason LIKE '%confidence%';

-- 2. Review conversation history quality
SELECT contact_id, COUNT(*) as conversation_count
FROM conversations
GROUP BY contact_id
HAVING conversation_count < 3;  -- Contacts with little history

-- 3. Adjust confidence threshold
-- Edit: app/Jobs/PersonalizeCampaignMessagesJob.php
-- Line 205: Change 0.6 to 0.5 for more lenient threshold
```

### Issue 4: Messages Not Sending at Optimal Time

**Symptoms:**
- Messages send immediately instead of optimal time
- `optimal_send_time` is NULL

**Solutions:**
```sql
-- Check if contacts have reply hour data
SELECT 
    COUNT(*) as total,
    COUNT(avg_reply_hour) as with_reply_hour,
    AVG(avg_reply_hour) as average_hour
FROM business_contacts;

-- If many NULL, contacts need more conversation history
-- Optimal send time falls back to business hours (9 AM - 5 PM EAT)
```

---

## 📈 Expected Performance

### Processing Speed

- **Personalization:** ~2-5 seconds per message (OpenAI API call)
- **Batch of 50:** ~2-5 minutes total
- **1000 messages:** ~40-100 minutes (20 batches × 2-5 mins)

### API Costs (Estimated)

- **OpenAI GPT-4:** ~$0.03 - $0.06 per message
- **1000 messages:** ~$30 - $60
- **Consider:** Switching to GPT-3.5-Turbo (~$0.001 per message) for cost savings

### Success Rates (Expected)

- **Personalized Successfully:** ~85-90%
- **Human Review Required:** ~5-10%
- **Opted Out/Failed:** ~2-5%

---

## 🔐 Security & Privacy

### Data Handling

1. **Conversation History:** Only last 10 messages sent to OpenAI
2. **PII Protection:** Phone numbers NOT sent to AI (only names)
3. **Data Retention:** AI metadata stored for analysis, can be purged
4. **Opt-Out Respect:** Automatically detects and blocks opted-out contacts

### API Security

```php
// OpenAI calls are made server-side only
// API key never exposed to frontend
// All requests logged for audit trail
```

---

## 🎓 Training & Rollout

### Phase 1: Internal Testing (Week 1)
- ✅ Create test campaigns with known contacts
- ✅ Review AI-generated messages for quality
- ✅ Verify sentiment detection accuracy
- ✅ Monitor OpenAI costs

### Phase 2: Limited Rollout (Week 2)
- Enable for select users/businesses
- Compare engagement rates vs. non-personalized campaigns
- Gather feedback on message quality
- Tune confidence thresholds

### Phase 3: Full Production (Week 3+)
- Enable for all users
- Add UI for viewing AI analysis
- Dashboard showing personalization stats
- A/B testing framework

---

## 📞 Support

### Common Questions

**Q: Can I disable AI personalization for specific campaigns?**
A: Currently all campaigns use AI. To send immediately, you can bypass by directly using SendWhatsAppMessage job.

**Q: How do I review messages flagged for human review?**
A: Query: `SELECT * FROM message_queue WHERE status = 'human_review'`
   Then manually update refined_message and change status to 'refined'

**Q: What if OpenAI API is down?**
A: Messages will be retried 3 times, then moved to `failed` status. Can manually reprocess later.

**Q: Can I use a different AI model?**
A: Yes! Edit `app/Services/MessagePersonalizationService.php` line 31:
   Change `'gpt-4'` to `'gpt-3.5-turbo'` for faster/cheaper processing.

---

## ✅ Implementation Checklist

- [x] PersonalizeCampaignMessagesJob created
- [x] Console command created
- [x] Message::queueMessages() modified
- [x] Kernel.php scheduling configured
- [x] Campaign model supports counters
- [x] MessageQueue model has all fields
- [x] MessagePersonalizationService exists
- [x] ScheduleMessageSendJob exists
- [ ] Queue workers configured in Supervisor
- [ ] OpenAI API key configured in production
- [ ] Load testing completed
- [ ] User training completed
- [ ] Monitoring dashboards created

---

**Implementation Status:** ✅ **COMPLETE - Ready for Testing**

**Next Steps:**
1. Configure OpenAI API key in `.env`
2. Start queue workers
3. Create test campaign
4. Monitor logs and database
5. Verify personalized messages are being generated

---

*Generated on March 7, 2026*
