<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\BusinessContact;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\AiSalesAgent;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MigrateCrmDataCommand extends Command
{
    protected $signature = 'admin:migrate-crm-data 
                            {--user-id= : Target SafariChat user ID}
                            {--limit=100 : Number of clients to process per batch}
                            {--dry-run : Preview migration without making changes}';

    protected $description = 'Migrate client data from admin_crm database to safarichat with AI-powered conversation context generation';

    private $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        parent::__construct();
        $this->openAiService = $openAiService;
    }

    public function handle()
    {
        $userId = $this->option('user-id');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('🚀 Starting Admin CRM to SafariChat Data Migration');
        $this->newLine();

        // Validate user ID
        if (!$userId) {
            $this->error('❌ User ID is required. Use --user-id=<user_id>');
            return 1;
        }

        // Step 1: Initialize Migration Context
        $this->info("📋 Initializing migration context...");
        
        try {
            $migrationContext = $this->initializeMigrationContext($userId);
            
            $this->info("✅ Migration context initialized:");
            $this->line("   👤 User: {$migrationContext['user']->name} (ID: {$migrationContext['user']->id})");
            $this->line("   🏢 Business: {$migrationContext['business']->name} (ID: {$migrationContext['business']->id})");
            $this->line("   🤖 AI Agent: {$migrationContext['aiSalesAgent']->name} (ID: {$migrationContext['aiSalesAgent']->id})");
            
        } catch (Exception $e) {
            $this->error("❌ Failed to initialize migration context: " . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Step 2: Client Data Migration
        $this->info("📊 Starting client data migration...");
        $this->info("   📈 Batch limit: {$limit} clients");
        $this->info("   🔍 Dry run: " . ($dryRun ? 'Yes' : 'No'));
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No actual changes will be made');
        }

        $this->newLine();

        try {
            $results = $this->migrateClientsData(
                $migrationContext['user'], 
                $migrationContext['business'], 
                $migrationContext['aiSalesAgent'], 
                $limit, 
                $dryRun
            );

            // Display results
            $this->displayMigrationResults($results);

            $this->newLine();
            $this->info('🎉 Migration completed successfully!');
            
            return 0;

        } catch (Exception $e) {
            $this->error("💥 Migration failed: " . $e->getMessage());
            Log::error('CRM Migration failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Initialize migration context (user, business, AI agent)
     */
    private function initializeMigrationContext($userId): array
    {
        // Get target user and business context
        $user = User::findOrFail($userId);
        $business = $user->business;
        
        if (!$business) {
            throw new Exception("User {$userId} does not have an associated business");
        }

        $aiSalesAgent = AiSalesAgent::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        // Create AI agent if none exists
        if (!$aiSalesAgent) {
            $this->info("🤖 No active AI sales agent found, creating new one...");
            
            $aiSalesAgent = AiSalesAgent::create([
                'user_id' => $userId,
                'assistant_name' => 'CRM Import Agent',
                'status' => 'active',
                'always_available' => true,
                'allow_outreach' => true,
                'business_hours_start' => '09:00',
                'business_hours_end' => '17:00',
                'timezone' => 'UTC',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return [
            'user' => $user,
            'business' => $business,
            'aiSalesAgent' => $aiSalesAgent
        ];
    }

    /**
     * Main client data migration logic
     */
    private function migrateClientsData($user, $business, $aiSalesAgent, $limit, $dryRun): array
    {
        $results = [
            'processed' => 0,
            'business_contacts_created' => 0,
            'leads_created' => 0,
            'conversations_created' => 0,
            'errors' => 0,
            'error_details' => []
        ];

        // Connect to admin_crm database and fetch clients
        $adminClients = DB::connection('admin_crm')
            ->table('clients')
            ->limit($limit)
            ->get();

        if ($adminClients->isEmpty()) {
            throw new Exception("No clients found in admin_crm database");
        }

        $this->info("📊 Found {$adminClients->count()} clients to process");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($adminClients->count());
        $progressBar->start();

        foreach ($adminClients as $client) {
            try {
                $results['processed']++;

                if ($dryRun) {
                    // Dry run - just show what would be processed
                    $this->processDryRun($client);
                    $results['business_contacts_created']++;
                    $results['leads_created']++;
                    $results['conversations_created']++;
                } else {
                    // Actual migration
                    $migrationResult = $this->processClientMigration($client, $user, $business, $aiSalesAgent);
                    
                    if ($migrationResult['business_contact']) {
                        $results['business_contacts_created']++;
                    }
                    if ($migrationResult['lead']) {
                        $results['leads_created']++;
                    }
                    if ($migrationResult['conversation']) {
                        $results['conversations_created']++;
                    }
                }

                $progressBar->advance();

            } catch (Exception $e) {
                $results['errors']++;
                $results['error_details'][] = "Client {$client->id} ({$client->name}): " . $e->getMessage();
                
                Log::error('Client migration error', [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'error' => $e->getMessage()
                ]);

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Process individual client migration
     */
    private function processClientMigration($client, $user, $business, $aiSalesAgent): array
    {
        $result = [
            'business_contact' => null,
            'lead' => null,
            'conversation' => null
        ];

        // Check if client already exists by phone or email
        $normalizedPhone = $this->normalizePhone($client->phone);
        $existingContact = BusinessContact::where(function($query) use ($normalizedPhone, $client) {
            if (!empty($normalizedPhone)) {
                $query->where('guest_phone', $normalizedPhone);
            }
            if (!empty($client->email)) {
                $query->orWhere('guest_email', $client->email);
            }
        })->first();

        if ($existingContact) {
            // Skip this client - already exists
            return $result;
        }

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
        
        $result['business_contact'] = $businessContact;

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

        $result['lead'] = $lead;

        // STEP 2C: Generate AI Context Summary from CRM Messages
        $conversation = $this->createConversationContextFromCRM($client, $lead, $aiSalesAgent, $businessContact);
        $result['conversation'] = $conversation;

        return $result;
    }

    /**
     * Dry run processing - show what would be migrated
     */
    private function processDryRun($client)
    {
        // Just simulate processing for dry run mode
        return true;
    }

    /**
     * Create conversation context from CRM messages
     */
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
            return null; // No conversation history to import
        }

        // Generate AI context summary
        $aiContextSummary = $this->generateClientContextSummary($client, $crmMessages);

        // Create single comprehensive conversation entry
        return Conversation::create([
            'lead_id' => $lead->id,
            'ai_sales_agent_id' => $aiSalesAgent->id,
            'business_contact_id' => $businessContact->id,
            'message_content' => $aiContextSummary,
            'message_type' => 'AI_AGENT',
            'sender_type' => 'ai_agent',
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

    /**
     * Generate AI-powered client context summary
     */
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
        $totalStudents = $this->getTotalStudents($client) ?? 'Unknown';
        $prompt = "
        Analyze this CRM client interaction history and create a comprehensive context summary 
        for an AI sales agent in the education sector.
        
        CLIENT: {$client->name}
        BUSINESS TYPE: School/Education Institution
        TOTAL STUDENTS: {$totalStudents}
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
            $response = $this->openAiService->generateContextSummary($prompt);
            return $response['summary'] ?? $this->generateFallbackSummary($client, $crmMessages);
            
        } catch (Exception $e) {
            Log::warning('AI summary generation failed, using fallback', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->generateFallbackSummary($client, $crmMessages);
        }
    }

    /**
     * Generate fallback summary if AI fails
     */
    private function generateFallbackSummary($client, $crmMessages)
    {
        $messageCount = count($crmMessages);
        $firstMessage = $crmMessages->first();
        $lastMessage = $crmMessages->last();

        $engagementLevel = $messageCount > 50 ? 'HIGHLY ENGAGED' : 
                          ($messageCount > 10 ? 'MODERATELY ENGAGED' : 'LIMITED ENGAGEMENT');

        $totalStudents = $this->getTotalStudents($client) ?? 'Unknown';
        $firstContact = $firstMessage->created_at ?? 'Unknown';
        $lastContact = $lastMessage->created_at ?? 'Unknown';
        $statusDescription = $this->getStatusDescription($client->status, $client->type);
        $churnWarning = $client->status == 3 ? '⚠️ ATTENTION: Previously churned - use win-back approach' : '✅ Active potential - continue nurturing relationship';

        return "
📊 CRM CLIENT CONTEXT SUMMARY

🏫 CLIENT PROFILE:
- Institution: {$client->name}
- Industry: Education Sector
- Size: {$totalStudents} students
- Contact: {$client->phone} | {$client->email}

📈 ENGAGEMENT HISTORY:
- Total CRM Interactions: {$messageCount}
- Engagement Level: {$engagementLevel}
- First Contact: {$firstContact}
- Last Contact: {$lastContact}

🎯 STATUS & APPROACH:
- Current Status: {$statusDescription}
- Industry Focus: Educational services and solutions
- Import Source: Legacy CRM system

💡 AI AGENT NOTES:
- Historical client with documented interaction history
- Continue engagement based on education sector needs
- {$churnWarning}
- Reference CRM history for personalized conversations
        ";
    }

    /**
     * Map CRM client status to SafariChat lead status
     */
    private function mapClientStatusToLeadStatus($status, $type)
    {
        if ($status == 3 && $type == 2) {
            return Lead::STATUS_CHURNED;
        } elseif ($status == 1 && $type == 2) {
            return Lead::STATUS_CLOSED;
        } elseif ($status == 1 || $status == 2) {
            return Lead::STATUS_NEW;
        }
        
        return Lead::STATUS_NEW; // Default fallback
    }

    /**
     * Get status description for display
     */
    private function getStatusDescription($status, $type)
    {
        if ($status == 3 && $type == 2) return 'CHURNED - Requires win-back strategy';
        if ($status == 1 && $type == 2) return 'CLOSED - Successfully converted';
        if ($status == 1 || $status == 2) return 'ACTIVE - Continue engagement';
        return 'PROSPECTS - Assess and nurture';
    }

    /**
     * Get total students for the client
     */
    private function getTotalStudents($client)
    {
        return $client->status == 1 && $client->type == 2 ? 
            DB::connection('admin_crm')
              ->table('shulesoft.student')
              ->where('schema_name', $client->username)
              ->where('status', 1)
              ->count() : $client->estimated_students;
    }

    /**
     * Normalize phone number
     */
    private function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Display migration results
     */
    private function displayMigrationResults($results)
    {
        $this->newLine();
        $this->info('📊 Migration Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Clients Processed', $results['processed']],
                ['Business Contacts Created', $results['business_contacts_created']],
                ['Leads Created', $results['leads_created']],
                ['Conversations Created', $results['conversations_created']],
                ['Errors', $results['errors']]
            ]
        );

        if ($results['errors'] > 0) {
            $this->newLine();
            $this->warn('⚠️ Errors encountered:');
            foreach ($results['error_details'] as $error) {
                $this->line("   • {$error}");
            }
        }
    }
}