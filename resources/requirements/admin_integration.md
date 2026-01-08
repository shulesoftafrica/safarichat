# Admin CRM to SafariChat Data Migration

## Overview
This document outlines the requirements and implementation for migrating client data from `admin_crm` database to `safarichat` database using a single script approach with AI-powered conversation context generation.

## Database Structure

### Admin CRM Tables (Source):
1. **clients** - Client information and status
2. **tasks** - Client interaction messages/notes  
3. **tasks_clients** - Relationship between tasks and clients

### SafariChat Tables (Destination):
1. **business_contacts** - Client contact information
2. **leads** - Sales lead management
3. **conversations** - AI conversation context
4. **business** - Business account information
5. **ai_sales_agents** - AI agent assignments

## Migration Requirements

### Prerequisites
Before starting the migration, ensure these constants are established:
- **USER_ID** - Target SafariChat user account
- **BUSINESS_ID** - Target business where data will be imported
- **AI_SALES_AGENT_ID** - AI agent to handle imported leads

## Single Script Implementation

### Command Structure
```bash
php artisan admin:migrate-crm-data {--user-id=} {--limit=100} {--dry-run}
```

### Implementation Flow

#### Step 1: Initialize Migration Context
```php
// Get target user and business context
$user = User::findOrFail($userId);
$business = $user->business;
$aiSalesAgent = AiSalesAgent::where('user_id', $userId)
    ->where('is_active', true)
    ->first();

// Create AI agent if none exists
if (!$aiSalesAgent) {
    $aiSalesAgent = AiSalesAgent::create([
        'business_id' => $business->id,
        'name' => 'CRM Import Agent',
        'personality_type' => 'professional',
        'is_active' => true,
        'allow_outreach' => true
    ]);
}
```

#### Step 2: Client Data Migration Loop
```php
// Connect to admin_crm database and fetch clients
$adminClients = DB::connection('admin_crm')->table('clients')->get();

foreach ($adminClients as $client) {
    
    // STEP 2A: Create Business Contact
   

    $businessContact = BusinessContact::create([
        'guest_name' => $client->name,
        'guest_phone' => $this->normalizePhone($client->phone),
        'guest_email' => $client->email,
        'business_id' => $business->id,
        'user_id' => $user->id,
        'imported_from_crm' => true,
        'crm_data' => json_encode([
            'address' => $client->address,
            'username' => $client->username,
            'total_students' => $this->getTotalStudents($client),
            'crm_client_id' => $client->id,
            'import_date' => now()
        ])
    ]);
    
    // STEP 2B: Create Lead Record
    $leadStatus = $this->mapClientStatusToLeadStatus($client->status, $client->type);
    
    $lead = Lead::create([
        'business_contact_id' => $businessContact->id,
        'ai_sales_agent_id' => $aiSalesAgent->id,
        'business_id' => $business->id,
        'user_id' => $user->id,
        'source' => 'crm_import',
        'status' => $leadStatus,
        'company_name' => $client->name,
        'industry' => 'education',
        'is_churned' => ($client->status == 3 && $client->type == 2),
        'last_interaction_at' => now(),
        'metadata' => json_encode([
            'crm_client_id' => $client->id,
            'original_status' => $client->status,
            'original_type' => $client->type,
            'import_source' => 'admin_crm'
        ])
    ]);
    
    // STEP 2C: Generate AI Context Summary from CRM Messages
    $this->createConversationContextFromCRM($client, $lead, $aiSalesAgent, $businessContact);
}
```

#### Step 3: AI-Powered Conversation Context Generation
```php
private function createConversationContextFromCRM($client, $lead, $aiSalesAgent, $businessContact)
{
    // Get all CRM messages/tasks for this client
    $crmMessages = DB::connection('admin_crm')
        ->table('tasks')
        ->join('tasks_clients', 'tasks.id', '=', 'tasks_clients.task_id')
        ->where('tasks_clients.client_id', $client->id)
        ->orderBy('tasks.created_at')
        ->get();
    
    if ($crmMessages->isEmpty()) {
        return; // No conversation history to import
    }
    
    // Generate AI context summary
    $aiContextSummary = $this->generateClientContextSummary($client, $crmMessages);
    
    // Create single comprehensive conversation entry
    Conversation::create([
        'lead_id' => $lead->id,
        'ai_sales_agent_id' => $aiSalesAgent->id,
        'business_contact_id' => $businessContact->id,
        'message' => $aiContextSummary,
        'message_type' => 'ai_context_summary',
        'sender_type' => 'system',
        'conversation_stage' => 'CRM_CONTEXT',
        'status' => 'completed',
        'priority' => 5,
        'metadata' => json_encode([
            'crm_source' => 'admin_crm',
            'is_ai_summary' => true,
            'total_messages_analyzed' => $crmMessages->count(),
            'crm_client_id' => $client->id,
            'summary_generated_at' => now()
        ]),
        'created_at' => $crmMessages->last()->created_at ?? now()
    ]);
}

private function generateClientContextSummary($client, $crmMessages)
{
    // Prepare message history for AI analysis
    $messageHistory = $crmMessages->map(function($msg) {
        return [
            'date' => $msg->created_at,
            'content' => $msg->activity ?? $msg->description,
            'type' => 'crm_note'
        ];
    })->toArray();
    
    // AI prompt for comprehensive context generation
    $prompt = "
    Analyze this CRM client interaction history and create a comprehensive context summary 
    for an AI sales agent in the education sector.
    
    CLIENT: {$client->name}
    BUSINESS TYPE: School/Education Institution
    TOTAL STUDENTS: {$this->getTotalStudents($client) ?? 'Unknown'}
    TOTAL INTERACTIONS: " . count($messageHistory) . "
    
    CRM MESSAGE HISTORY:
    " . json_encode($messageHistory, JSON_PRETTY_PRINT) . "
    
    Create a structured summary covering:
    1. 📊 CLIENT PROFILE & BACKGROUND
    2. 🎯 KEY REQUIREMENTS & INTERESTS
    3. 📈 ENGAGEMENT TIMELINE & PATTERNS
    4. ⚠️ CHALLENGES & PAIN POINTS
    5. 💬 COMMUNICATION PREFERENCES
    6. 🚀 CURRENT STATUS & NEXT STEPS
    7. 💡 AI AGENT RECOMMENDATIONS
    
    Format as clear, actionable context for AI conversations.
    ";
    
    try {
        // Use OpenAI service to generate intelligent summary
        $response = app(OpenAiService::class)->generateContextSummary($prompt);
        return $response['summary'] ?? $this->generateFallbackSummary($client, $crmMessages);
        
    } catch (Exception $e) {
        Log::warning('AI summary generation failed, using fallback', [
            'client_id' => $client->id,
            'error' => $e->getMessage()
        ]);
        
        return $this->generateFallbackSummary($client, $crmMessages);
    }
}

private function generateFallbackSummary($client, $crmMessages)
{
    $messageCount = count($crmMessages);
    $firstMessage = $crmMessages->first();
    $lastMessage = $crmMessages->last();
    
    $engagementLevel = $messageCount > 50 ? 'HIGHLY ENGAGED' : 
                      ($messageCount > 10 ? 'MODERATELY ENGAGED' : 'LIMITED ENGAGEMENT');
    
    return "
📊 CRM CLIENT CONTEXT SUMMARY

🏫 CLIENT PROFILE:
- Institution: {$client->name}
- Industry: Education Sector
- Size: {$this->getTotalStudents($client) ?? 'Unknown'} students
- Contact: {$client->phone} | {$client->email}

📈 ENGAGEMENT HISTORY:
- Total CRM Interactions: {$messageCount}
- Engagement Level: {$engagementLevel}
- First Contact: " . ($firstMessage->created_at ?? 'Unknown') . "
- Last Contact: " . ($lastMessage->created_at ?? 'Unknown') . "

🎯 STATUS & APPROACH:
- Current Status: " . $this->getStatusDescription($client->status, $client->type) . "
- Industry Focus: Educational services and solutions
- Import Source: Legacy CRM system

💡 AI AGENT NOTES:
- Historical client with documented interaction history
- Continue engagement based on education sector needs
- " . ($client->status == 3 ? '⚠️ ATTENTION: Previously churned - use win-back approach' : '✅ Active potential - continue nurturing relationship') . "
- Reference CRM history for personalized conversations
    ";
}
```

#### Step 4: Status Mapping Logic
```php
private function mapClientStatusToLeadStatus($status, $type)
{
    // Map CRM client status to SafariChat lead status
    if ($status == 3 && $type == 2) {
        return Lead::STATUS_CHURNED;
    } elseif ($status == 1 && $type == 2) {
        return Lead::STATUS_CLOSED;
    } elseif ($status == 1 || $status == 2) {
        return Lead::STATUS_NEW;
    }
    
    return Lead::STATUS_NEW; // Default fallback
}

private function getStatusDescription($status, $type)
{
    if ($status == 3 && $type == 2) return 'CHURNED - Requires win-back strategy';
    if ($status == 1 && $type == 2) return 'CLOSED - Successfully converted';
    if ($status == 1 || $status == 2) return 'ACTIVE - Continue engagement';
    return 'PROSPECTS - Assess and nurture';
}

private function getTotalStudents($client)
{
   return  $total_students=$client->status==1 && $client->type==2 ? DB::connection('admin_crm')->table('shulesoft.student')->where('schema_name',$client->username)->where('status',1)->count() : $client->estimated_students;
}

private function normalizePhone($phone)
{
    return preg_replace('/[^0-9]/', '', $phone);
}
```

## Expected Results

### Performance Metrics:
- **33,499+ CRM messages** → **~200-500 AI context summaries**
- **Import time**: 2-3 minutes (vs hours with individual entries)
- **Database efficiency**: Minimal overhead, optimized for AI access
- **Context quality**: Rich, actionable insights for AI agents

### Data Integrity:
- All client information preserved in `business_contacts`
- Complete lead pipeline established with proper status mapping
- Comprehensive conversation context available for AI interactions
- Full audit trail maintained in metadata fields

### AI Benefits:
- Intelligent context understanding for each imported client
- Personalized conversation capabilities from day one  
- Historical insights inform future engagement strategies
- Seamless transition from CRM to AI-powered sales management