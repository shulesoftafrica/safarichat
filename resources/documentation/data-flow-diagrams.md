# Data Flow Diagrams

## Complete Sales Process Flow

```
1. INITIAL SALES OUTREACH
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  [products] ──────┐                                                        │
│       │           │                                                        │
│       ▼           ▼                                                        │
│  [ai_sales_agents] ────► AI Message Generation                             │
│       │                        │                                           │
│       │                        ▼                                           │
│       │                 Personalized Message                               │
│       │                        │                                           │
│       ▼                        ▼                                           │
│  [whatsapp_instances] ──► [SendWhatsAppMessage Job]                        │
│       │                        │                                           │
│       │                        ▼                                           │
│       │                 WaSenderService                                    │
│       │                        │                                           │
│       │                        ▼                                           │
│       │                 WaSender API                                       │
│       │                        │                                           │
│       ▼                        ▼                                           │
│  [outgoing_messages] ◄──── Message Sent                                   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

2. CUSTOMER RESPONSE PROCESSING
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  Customer Reply ──► WaSender Webhook                                        │
│                            │                                               │
│                            ▼                                               │
│                    WaSenderController                                      │
│                            │                                               │
│                            ▼                                               │
│                    [incoming_messages] ──────┐                            │
│                                              │                            │
│                                              ▼                            │
│                                    ProcessIncomingMessage Job             │
│                                              │                            │
│                                              ▼                            │
│                                    AiWhatsAppService                      │
│                                              │                            │
│                                              ▼                            │
│                                    [conversations] ────┐                  │
│                                              │          │                 │
│                                              │          ▼                 │
│                                              │    [leads] (update)        │
│                                              │                            │
│                                              ▼                            │
│                                    AI Response Generation                 │
│                                              │                            │
│                                              ▼                            │
│                                    [outgoing_messages]                    │
│                                              │                            │
│                                              ▼                            │
│                                    SendWhatsAppMessage Job                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Database Table Interaction Flow

### When Starting to Sell

```
Step 1: System checks [users] table
        │
        ▼ (user_id)
Step 2: Query [ai_sales_agents] 
        WHERE user_id = ? AND status = 'active'
        │
        ▼ (agent configuration)
Step 3: Query [products]
        WHERE user_id = ? AND ai_enabled = 1
        │
        ▼ (product data)
Step 4: Query [leads]
        WHERE status IN ('new', 'interested', 'warm')
        AND last_contact_at < NOW() - INTERVAL ? HOUR
        │
        ▼ (target customers)
Step 5: Query [whatsapp_instances]
        WHERE user_id = ? AND status = 'connected'
        │
        ▼ (WhatsApp connection)
Step 6: INSERT INTO [conversations]
        (lead_id, message, sender_type, ai_agent_id)
        │
        ▼ (conversation record)
Step 7: INSERT INTO [outgoing_messages]
        (phone_number, message, status, instance_id)
        │
        ▼ (message queued)
Step 8: Job dispatched to queue
        SendWhatsAppMessage::dispatch()
```

### When Customer Replies

```
Step 1: Webhook received from WaSender
        │
        ▼
Step 2: INSERT INTO [incoming_messages]
        (phone_number, message_body, chat_id, instance_id)
        │
        ▼ (message_id)
Step 3: SELECT FROM [leads]
        WHERE phone_number = ?
        │
        ▼ (lead_id, or CREATE new lead)
Step 4: INSERT INTO [conversations]
        (lead_id, message, sender_type='customer')
        │
        ▼ (conversation_id)
Step 5: AI Processing
        - Analyze message intent
        - Get conversation history
        - Generate appropriate response
        │
        ▼
Step 6: INSERT INTO [conversations]
        (lead_id, message, sender_type='ai', ai_agent_id)
        │
        ▼
Step 7: UPDATE [leads]
        SET status=?, last_contact_at=NOW(), score=?
        │
        ▼
Step 8: INSERT INTO [outgoing_messages]
        (phone_number, message, status='pending')
        │
        ▼
Step 9: SendWhatsAppMessage::dispatch()
```

## Cron Job Execution Timeline

### Every Minute (High Frequency)

```
00:00 ──► Process::run() ──────────┐
  │                                │
  │    ┌── Check [reminders] ──────┼── Send scheduled messages
  │    │                           │
  │    ├── Process failed jobs ────┼── Retry failed deliveries
  │    │                           │
  │    └── Followup processing ────┼── Send scheduled followups
  │                                │
  └──── processScheduledFollowups() ─┘
            │
            ▼
        SELECT FROM [conversations]
        WHERE followup_scheduled_at <= NOW()
        AND followup_sent = false
```

### Every 5 Minutes

```
Command: ai:process-failed-messages
    │
    ▼
SELECT FROM failed_jobs 
WHERE queue IN ('ai_standard', 'messages')
    │
    ▼
Retry jobs ──► Update job status
```

### Every 30 Minutes (Business Hours Only)

```
Check overdue handoffs:
    │
    ▼
SELECT FROM [conversations] 
WHERE handoff_requested_at < NOW() - INTERVAL 2 HOUR
AND handoff_completed = false
    │
    ▼
Send notifications to admins
```

### Daily Tasks (08:00 AM)

```
1. Update lead scores:
   UPDATE [leads] 
   SET score = CALCULATE_ENGAGEMENT_SCORE()
   
2. Send daily summaries:
   SELECT COUNT(*) as stats
   FROM [conversations]
   WHERE DATE(created_at) = CURDATE()
   
3. Clean up old data:
   DELETE FROM [conversations]
   WHERE created_at < NOW() - INTERVAL 90 DAY
```

## Method Call Flow

### Starting Sales Process

```
Kernel::schedule() 
    │
    ▼
Message::process() 
    │
    ├── getUsersWithActiveAgents()
    │   └── DB::table('users')->join('ai_sales_agents')...
    │
    ├── getTargetLeads($userId)
    │   └── DB::table('leads')->where('user_id', $userId)...
    │
    ├── generateMessage($lead, $product, $agent)
    │   └── OpenAiService::generateSalesMessage()
    │
    ├── queueMessage($message, $phoneNumber)
    │   └── SendWhatsAppMessage::dispatch()
    │
    └── updateLeadStatus($leadId, 'contacted')
        └── Lead::find($leadId)->update(['status' => 'contacted'])
```

### Processing Customer Reply

```
Webhook received ──► WaSenderController::handleWebhook()
    │
    ├── validateWebhookSignature()
    │
    ├── storeIncomingMessage()
    │   └── IncomingMessage::create([...])
    │
    ├── findOrCreateLead()
    │   └── Lead::firstOrCreate(['phone_number' => $phone])
    │
    ├── queueForProcessing()
    │   └── ProcessIncomingMessage::dispatch($message)
    │
    └── return response()->json(['success' => true])

ProcessIncomingMessage::handle()
    │
    ├── AiWhatsAppService::processIncomingMessage($message)
    │   │
    │   ├── analyzeMessageIntent($message)
    │   │   └── OpenAiService::analyzeText($message->message_body)
    │   │
    │   ├── getConversationHistory($lead)
    │   │   └── Conversation::where('lead_id', $lead->id)->latest(10)
    │   │
    │   ├── generateResponse($intent, $history, $lead)
    │   │   └── OpenAiService::generateResponse($context)
    │   │
    │   ├── storeConversation($response, $lead)
    │   │   └── Conversation::create([...])
    │   │
    │   ├── updateLeadScore($lead, $intent)
    │   │   └── Lead::calculateEngagementScore($interactions)
    │   │
    │   └── scheduleFollowup($lead, $nextContactTime)
    │       └── Conversation::create(['followup_scheduled_at' => $time])
    │
    └── sendResponse($response, $lead->phone_number)
        └── WaSenderService::sendTextMessage($phone, $response)
```

## Queue System Flow

### Job Priority and Routing

```
High Priority Queue (Real-time responses):
    ├── Customer reply responses
    ├── Purchase confirmations
    └── Urgent notifications

Standard Queue (Regular messaging):
    ├── Sales outreach messages
    ├── Follow-up messages
    └── Daily reminders

Bulk Queue (Mass messaging):
    ├── Newsletter broadcasts
    ├── Promotional campaigns
    └── Event announcements

Failed Queue (Retry mechanism):
    ├── Failed API calls
    ├── Network timeouts
    └── Invalid phone numbers
```

### Queue Processing Logic

```php
// Job Dispatch Logic
if ($messageType === 'customer_response') {
    $queue = 'high_priority';
    $delay = 0; // Immediate
} elseif ($messageType === 'followup') {
    $queue = 'standard';
    $delay = calculateOptimalTiming($customerTimezone);
} elseif ($messageType === 'bulk') {
    $queue = 'bulk';
    $delay = distributeBulkLoad($totalMessages);
}

SendWhatsAppMessage::dispatch($message)
    ->onQueue($queue)
    ->delay(now()->addSeconds($delay));
```

## Error Handling Flow

### Failed Message Processing

```
Message Send Attempt
    │
    ├── Success ──► Update outgoing_messages.status = 'sent'
    │              └── Log success metrics
    │
    └── Failure ──► Retry Logic
                    │
                    ├── Attempt 1 (30s delay)
                    ├── Attempt 2 (1m delay)  
                    ├── Attempt 3 (3m delay)
                    │
                    └── Final Failure
                        │
                        ├── Update status = 'failed'
                        ├── Log to failed_jobs table
                        ├── Send admin notification
                        └── Update lead.status = 'unreachable'
```

### System Health Monitoring

```
Every 10 Minutes:
    │
    ├── Check queue sizes
    │   └── If > 1000 messages ──► Alert admin
    │
    ├── Check delivery rates
    │   └── If < 90% success ──► Investigate issues
    │
    ├── Check AI response times
    │   └── If > 30 seconds ──► Scale AI processing
    │
    └── Check instance status
        └── If disconnected ──► Attempt reconnection
```

## Configuration Dependencies

### Environment Variables Flow

```
.env file ──► config/services.php ──► WaSenderService::__construct()
    │               │                     │
    │               │                     ├── base_url
    │               │                     ├── api_key
    │               │                     └── default_instance_id
    │               │
    │               └── config/ai_sales_agent.php ──► AI Settings
    │                           │
    │                           ├── openai_model
    │                           ├── max_tokens
    │                           └── response_timeout
    │
    └── config/queue.php ──► Queue Configuration
                │
                ├── redis_connection
                ├── queue_priorities
                └── retry_settings
```

This visual documentation provides a comprehensive understanding of how data flows through the system, which tables are accessed in what order, and how the various components interact during both sales outreach and customer response processing.