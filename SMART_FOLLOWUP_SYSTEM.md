# Smart AI Followup System

## Overview

The Smart AI Followup System is an intelligent customer follow-up solution that replaces generic messages with personalized, context-aware communications based on conversation history and customer language.

## Key Features

### 🎯 **Intelligent Lead Targeting**
- **Only non-closed leads**: Filters out `STATUS_CLOSED`, `STATUS_LOST`, `STATUS_DO_NOT_CONTACT`, `STATUS_CONVERTED`
- **Timing-based**: Targets leads with last interaction > 3 days ago  
- **No duplicate followups**: Skips leads that already received followup

### 🧠 **Smart Context Analysis**
- **Conversation history**: Analyzes up to 10 recent conversations
- **Customer interest detection**: Identifies engagement level
- **Budget/timeline mentions**: Detects pricing and urgency discussions
- **Conversation stage**: Determines if customer is initial, engaged, or advanced

### 🌍 **Multi-Language Support**
Automatically detects customer language from conversation history and responds in:
- **English** (default)
- **Swahili** - Habari, asante, karibu, etc.
- **French** - Bonjour, merci, oui, etc.
- **Arabic** - مرحبا, شكرا, نعم, etc.
- **Portuguese** - Olá, obrigado, sim, etc.
- **Spanish** - Hola, gracias, sí, etc.

### 🎨 **Dynamic Message Generation**

#### Message Types Based on Context:

1. **Follow-up** (Basic engagement)
   - English: "Hi {name}! I wanted to follow up on our conversation..."
   - Swahili: "Habari {name}! Nataka kufuatilia mazungumzo yetu..."

2. **Interested** (Customer showed interest)  
   - English: "Since you showed interest in our solution, I wanted to check..."
   - Swahili: "Kwa kuwa ulionyesha nia katika suluhisho letu..."

3. **Closing** (Advanced stage, ready to close)
   - English: "Based on our conversations, it seems like our solution could be a great fit..."
   - Swahili: "Kulingana na mazungumzo yetu, inaonekana suluhisho letu linaweza kuwa la kufaa..."

4. **Re-engage** (Long time since last contact)
   - English: "It's been a while since we last spoke..."
   - Swahili: "Imekuwa muda tangu tulipozungumza mwisho..."

## Implementation

### New Components

1. **`SmartFollowupService`** - Core intelligence engine
2. **`SmartFollowupCommand`** - CLI command for manual execution  
3. **Updated cron jobs** - Automatic smart followup processing

### System Integration

```php
// Old generic approach (REMOVED)
$followUpMessage = "Hi! Just following up on our previous conversation...";

// New smart approach  
$smartFollowupService = app(\App\Services\SmartFollowupService::class);
$smartFollowupService->processSmartFollowups();
```

### Language Detection Algorithm

```php
// Detects customer language from conversation patterns
$languageIndicators = [
    'swahili' => ['habari', 'asante', 'karibu', 'mambo', 'poa'],
    'french' => ['bonjour', 'merci', 'oui', 'non', 'comment'],
    // ... etc
];
```

### Context Analysis Features

- **Interest Detection**: Looks for keywords like "interested", "yes", "tell me more"
- **Budget Analysis**: Detects mentions of cost, price, budget, affordability  
- **Timeline Analysis**: Identifies urgency keywords like "when", "deadline", "urgent"
- **Conversation Stage**: Categorizes as initial/engaged/advanced based on message count and responses

## Usage

### Manual Execution

```bash
# Test with dry-run (no messages sent)
php artisan followup:smart --dry-run

# Execute smart followups  
php artisan followup:smart
```

### Automated Cron

The system automatically runs via existing cron jobs:
- `processScheduledFollowUps()` in Message controller
- `processScheduledFollowups()` in Console Kernel

### Command Output

```
🤖 Starting Smart Followup Processing...
📋 Processing requirements:
   ✓ Only leads NOT closed/lost/converted
   ✓ Analyzes conversation history  
   ✓ Detects customer language
   ✓ Generates personalized messages

✅ Smart followup processing completed successfully!
```

## Database Impact

### Lead Status Filtering
```sql
-- Only processes leads that are still active
WHERE status NOT IN ('CLOSED', 'LOST', 'DO_NOT_CONTACT', 'CONVERTED')
AND last_interaction_at < NOW() - INTERVAL 3 DAY  
AND follow_up_sent_at IS NULL
```

### Conversation Creation
```sql 
INSERT INTO conversations (
    lead_id,
    ai_sales_agent_id, 
    message_content,
    sender_type,
    created_at
) VALUES (?, ?, ?, 'ai_agent_followup', NOW());
```

## Business Benefits

### 🎯 **Higher Engagement**
- Personalized messages in customer's language
- Context-aware communication
- Appropriate timing and messaging

### 💰 **Better Conversion** 
- Targets only viable prospects (non-closed)
- Tailored approach based on conversation stage
- Push towards closing when appropriate

### ⚡ **Efficiency**
- Automated intelligent processing  
- No manual message crafting needed
- Scales across multiple languages

### 📊 **Analytics**
- Detailed logging of followup attempts
- Success/skip/error tracking
- Language detection insights

## Migration from Old System

### Replaced Functions:
- ❌ `sendFollowUpMessage()` - Generic message sender
- ❌ Generic scheduled followups in Kernel.php
- ❌ Basic template substitution

### New Architecture:
- ✅ `SmartFollowupService` - Intelligent processing
- ✅ Multi-language message templates  
- ✅ Conversation history analysis
- ✅ Context-aware messaging

## Configuration

### Environment Variables
```bash
# AI Follow-up Settings (existing)
AI_FOLLOWUP_DELAY=24
AI_MAX_FOLLOWUPS=3
AI_FOLLOWUP_DECAY=2.0
AI_FOLLOWUP_BUSINESS_HOURS=true
```

### Service Dependencies
- `AiWhatsAppService` - Message sending
- `WhatsappInstance` - WhatsApp connectivity
- Lead, Conversation, BusinessContact models

## Monitoring & Logging

### Log Entries
```
Smart followup: Found 15 leads needing followup
Smart followup sent to lead 123  
Smart followup summary - Success: 12, Skipped: 2, Errors: 1
```

### Error Handling
- Graceful failures with detailed logging
- Skip records with missing data
- Continue processing despite individual errors

## Future Enhancements

- **AI-powered message generation** using OpenAI/Claude
- **Sentiment analysis** of customer responses  
- **Optimal timing** based on customer time zones
- **A/B testing** of message templates
- **Integration** with CRM lead scoring

---

**Note**: This system ensures effective sales operations by providing personalized, contextual followup messages that consider conversation history and customer language, while only targeting leads that haven't been closed yet.