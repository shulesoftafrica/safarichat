<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\WaSenderService;
use App\Services\OpenAiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessAppointmentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:process-reminders {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Process and send intelligent, context-aware appointment reminders via WhatsApp';

    private $openAiService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->openAiService = app(OpenAiService::class);
        $isDryRun = $this->option('dry-run');
        $this->info('🗓️ Processing intelligent appointment reminders...');
        
        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No messages will be sent');
        }
        
        $now = now();
        $remindersProcessed = 0;
        $remindersSent = 0;
        $remindersSkipped = 0;
        $errorsCount = 0;
        
        try {
            // Get appointments that need reminders
            $appointments = $this->getAppointmentsNeedingReminders($now);
            
            $this->info("📋 Found {$appointments->count()} appointments requiring reminders");
            
            foreach ($appointments as $appointment) {
                $remindersProcessed++;

                try {
                    // Respect cancellation / reschedule intents in recent chat.
                    if (!$this->isReminderRelevant($appointment)) {
                        $remindersSkipped++;
                        $this->line("⏭️ Skipped (not relevant): {$appointment->title}");
                        continue;
                    }

                    // Which stage(s) are due now? 24h-before and ~1h-before (near time).
                    $stage = $this->dueReminderStage($appointment, $now);
                    if (!$stage) {
                        $remindersSkipped++;
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line("📅 [{$stage}] Would remind customer + team for: {$appointment->title}");
                        $remindersSent++;
                        continue;
                    }

                    $sent = $this->sendAppointmentReminder($appointment, $stage);
                    if ($sent) {
                        $remindersSent++;
                        $this->line("✅ [{$stage}] Reminder sent: {$appointment->title}");
                    } else {
                        $errorsCount++;
                        $this->error("❌ [{$stage}] Failed reminder for appointment #{$appointment->id}");
                    }
                } catch (\Exception $e) {
                    $errorsCount++;
                    $this->error("💥 Error processing appointment #{$appointment->id}: " . $e->getMessage());
                    Log::error('Appointment reminder error', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Summary
            $this->info("📊 Summary:");
            $this->info("   • Processed: {$remindersProcessed}");
            $this->info("   • Sent: {$remindersSent}");
            $this->info("   • Skipped: {$remindersSkipped}");
            if ($errorsCount > 0) {
                $this->error("   • Errors: {$errorsCount}");
            }
            
        } catch (\Exception $e) {
            $this->error("🚨 Command failed: " . $e->getMessage());
            Log::error('Appointment reminder command failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get appointments that need reminders
     */
    private function getAppointmentsNeedingReminders(Carbon $now)
    {
        // Get appointments where:
        // - Status is confirmed or pending
        // - Scheduled time is within next 24 hours
        // - Reminder hasn't been sent yet
        // - Appointment is in the future
        
        // Any live meeting within the next 24h that still has at least one reminder
        // stage (24h or near-time) pending. A small negative margin lets the near-time
        // stage still fire if the cron runs a few minutes after the hour mark.
        return Appointment::with(['lead.businessContact', 'lead.business', 'createdBy'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('scheduled_at', '>', $now->copy()->subMinutes(15))
            ->where('scheduled_at', '<=', $now->copy()->addHours(24))
            ->where(function ($query) {
                $query->whereNull('reminder_24h_sent_at')
                      ->orWhereNull('reminder_1h_sent_at');
            })
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Decide which reminder stage (if any) is due for this appointment right now.
     * Returns '24h', '1h', or null. Near-time wins when both are technically due.
     */
    private function dueReminderStage(Appointment $appointment, Carbon $now): ?string
    {
        $scheduledAt = Carbon::parse($appointment->scheduled_at);
        $minutesAway = $now->diffInMinutes($scheduledAt, false); // negative if already started

        // Near-time: from ~90 min before up to ~15 min after start, once.
        if ($minutesAway <= 90 && $minutesAway >= -15 && empty($appointment->reminder_1h_sent_at)) {
            return '1h';
        }

        // Day-before: anytime from now up to 24h before, once.
        if ($minutesAway > 90 && $minutesAway <= (24 * 60) && empty($appointment->reminder_24h_sent_at)) {
            return '24h';
        }

        return null;
    }
    
    /**
     * Check if reminder was already sent today
     */
    private function reminderSentToday(Appointment $appointment): bool
    {
        $cacheKey = "reminder_sent_today_{$appointment->id}";
        
        if (Cache::has($cacheKey)) {
            return true;
        }
        
        // Check if reminder was sent in the last 24 hours
        if ($appointment->reminder_sent_at && 
            $appointment->reminder_sent_at->isToday()) {
            Cache::put($cacheKey, true, now()->endOfDay());
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if reminder is still relevant based on conversation context
     */
    private function isReminderRelevant(Appointment $appointment): bool
    {
        $lead = $appointment->lead;
        
        // Check if appointment was cancelled or lead is lost
        if (in_array($appointment->status, ['cancelled', 'no_show'])) {
            return false;
        }
        
        if (in_array($lead->status, ['lost', 'unqualified'])) {
            return false;
        }
        
        // Check recent conversations - if customer recently mentioned rescheduling or cancellation
        $recentConversations = Conversation::where('lead_id', $lead->id)
            ->where('created_at', '>=', now()->subDays(2))
            ->latest()
            ->limit(5)
            ->get();
            
        $combinedMessages = $recentConversations->pluck('customer_message')
            ->merge($recentConversations->pluck('message_content'))
            ->filter()
            ->implode(' ');
        
        $cancelIndicators = ['cancel', 'reschedule', 'postpone', 'not coming', 'can\'t make it', 'won\'t be able'];
        $messageLower = strtolower($combinedMessages);
        
        foreach ($cancelIndicators as $indicator) {
            if (strpos($messageLower, $indicator) !== false) {
                Log::info('Reminder skipped - cancellation indicators detected', [
                    'appointment_id' => $appointment->id,
                    'indicator' => $indicator
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Send appointment reminder
     */
    private function sendAppointmentReminder(Appointment $appointment, string $stage): bool
    {
        $lead = $appointment->lead;
        $ownerUserId = $appointment->created_by ?? ($lead->business->user_id ?? $lead->user_id ?? null);
        $instance = $this->resolveInstance($ownerUserId);

        if (!$instance) {
            Log::warning('No WhatsApp instance to send appointment reminder', [
                'appointment_id' => $appointment->id,
                'owner_user_id'  => $ownerUserId,
            ]);
            return false;
        }

        $waSender = app(WaSenderService::class);
        $anySent = false;

        // 1) Customer reminder --------------------------------------------------
        // Internal follow-ups (e.g. "call the customer back") remind the TEAM only;
        // we never text the customer a reminder for those.
        $contact = $lead->businessContact ?? $lead->contact ?? null;
        $customerPhone = $contact->guest_phone ?? $lead->phone_number ?? null;

        if (!$appointment->is_internal && $customerPhone) {
            try {
                $message = $this->generateIntelligentReminderMessage($appointment);
                if ($stage === '1h') {
                    $message = "⏰ Starting soon!\n\n" . $message;
                }
                $res = $waSender->sendMessage($customerPhone, $message, [], $instance, $ownerUserId);
                $anySent = $anySent || (bool) ($res['success'] ?? false);
            } catch (\Throwable $e) {
                Log::warning('Customer appointment reminder failed', [
                    'appointment_id' => $appointment->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        // 2) Team reminder (owner + sales person) -------------------------------
        $teamMessage = $this->buildTeamReminderMessage($appointment, $stage);
        foreach ($this->teamPhones($appointment) as $uid => $phone) {
            try {
                $inst = $this->resolveInstance($uid) ?: $instance;
                $res = $waSender->sendMessage($phone, $teamMessage, [], $inst, $uid);
                $anySent = $anySent || (bool) ($res['success'] ?? false);
            } catch (\Throwable $e) {
                Log::warning('Team appointment reminder failed', [
                    'appointment_id' => $appointment->id, 'user_id' => $uid, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark the stage done so it fires at most once.
        $appointment->forceFill([
            'reminder_sent'    => true,
            'reminder_sent_at' => now(),
            ($stage === '1h' ? 'reminder_1h_sent_at' : 'reminder_24h_sent_at') => now(),
        ])->save();

        return $anySent;
    }

    /**
     * Resolve a usable WhatsApp instance for a user (prefer a connected one).
     */
    private function resolveInstance(?int $userId): ?WhatsappInstance
    {
        if (!$userId) { return null; }

        return WhatsappInstance::where('user_id', $userId)
                ->whereIn('status', ['connected', 'ready'])
                ->latest()->first()
            ?: WhatsappInstance::where('user_id', $userId)->latest()->first();
    }

    /**
     * Owner + sales-person phones to alert, de-duplicated by normalized number.
     */
    private function teamPhones(Appointment $appointment): array
    {
        $lead = $appointment->lead;
        $ids = array_filter([
            $appointment->created_by ?? null,
            $lead->business->user_id ?? null,
            $lead->user_id ?? null,
        ]);

        $out = [];
        $seen = [];
        foreach ($ids as $uid) {
            $u = User::find($uid);
            if ($u && !empty($u->phone)) {
                $norm = preg_replace('/\D+/', '', $u->phone);
                if ($norm && !isset($seen[$norm])) {
                    $seen[$norm] = true;
                    $out[$u->id] = $u->phone;
                }
            }
        }
        return $out;
    }

    /**
     * Concise internal reminder for the owner / sales person.
     */
    private function buildTeamReminderMessage(Appointment $appointment, string $stage): string
    {
        $lead = $appointment->lead;
        $contact = $lead->businessContact ?? $lead->contact ?? null;
        $customer = $contact->guest_name ?? $lead->name ?? 'A customer';
        $phone = $contact->guest_phone ?? $lead->phone_number ?? '';
        $when = Carbon::parse($appointment->scheduled_at)->format('l, M j \a\t g:i A');

        // Internal follow-ups are a prompt to reach out to the customer.
        if ($appointment->is_internal) {
            $head = $stage === '1h' ? "⏰ *Follow-up due (~1 hour)*" : "🗓️ *Follow-up reminder*";
            $msg  = "{$head}\n\nReach out to {$customer} — {$appointment->title}\n{$when}\n";
            if ($phone !== '') { $msg .= "📞 {$phone}\n"; }
            $msg .= "\nDon't let this one slip.";
            return $msg;
        }

        $head = $stage === '1h' ? "⏰ *Meeting in ~1 hour*" : "🗓️ *Meeting reminder (tomorrow)*";
        $msg  = "{$head}\n\n{$customer} — {$appointment->title}\n{$when}\n";
        if ($phone !== '') { $msg .= "📞 {$phone}\n"; }
        $msg .= "\nPlease be ready.";
        return $msg;
    }
    
    
    /**
     * Generate INTELLIGENT, context-aware reminder message
     * STRICT CONDITIONS:
     * 1. NEVER send the same message daily
     * 2. Check conversation context and determine what's missing
     * 3. Adopt the customer's language from previous conversations
     */
    private function generateIntelligentReminderMessage(Appointment $appointment): string
    {
        $lead = $appointment->lead;
        $scheduledDate = Carbon::parse($appointment->scheduled_at);
        $businessName = $lead->businessContact->business->name ?? 'SafariChat';
        
        // Step 1: Get conversation history and context
        $conversationContext = $this->analyzeConversationHistory($lead);
        
        // Step 2: Detect customer's language preference
        $customerLanguage = $this->detectCustomerLanguage($lead);
        
        // Step 3: Identify what's missing or what benefit hasn't been introduced
        $missingContext = $this->identifyMissingContext($lead, $appointment, $conversationContext);
        
        // Step 4: Generate unique, AI-powered contextual reminder
        try {
            $reminder = $this->generateAiContextualReminder(
                $appointment,
                $lead,
                $conversationContext,
                $missingContext,
                $customerLanguage,
                $businessName
            );
            
            if ($reminder) {
                return $reminder;
            }
        } catch (\Exception $e) {
            Log::warning('AI reminder generation failed, using smart fallback', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Step 5: Smart fallback with context awareness
        return $this->generateSmartFallbackReminder(
            $appointment,
            $lead,
            $missingContext,
            $customerLanguage,
            $businessName
        );
    }
    
    /**
     * Analyze conversation history to understand context
     */
    private function analyzeConversationHistory(Lead $lead): array
    {
        $conversations = Conversation::where('lead_id', $lead->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        $context = [
            'total_conversations' => $conversations->count(),
            'last_conversation_date' => $conversations->first()?->created_at,
            'topics_discussed' => [],
            'pain_points_mentioned' => [],
            'features_discussed' => [],
            'objections_raised' => [],
            'interests_shown' => [],
            'sentiment' => 'neutral'
        ];
        
        $allMessages = $conversations->pluck('customer_message')
            ->merge($conversations->pluck('message_content'))
            ->filter()
            ->implode(' ');
        
        $messageLower = strtolower($allMessages);
        
        // Extract pain points
        $painPoints = ['expensive', 'complex', 'time', 'budget', 'difficult'];
        foreach ($painPoints as $pain) {
            if (strpos($messageLower, $pain) !== false) {
                $context['pain_points_mentioned'][] = $pain;
            }
        }
        
        // Extract interests
        $interests = ['demo', 'pricing', 'features', 'integration', 'support', 'trial'];
        foreach ($interests as $interest) {
            if (strpos($messageLower, $interest) !== false) {
                $context['interests_shown'][] = $interest;
            }
        }
        
        // Detect sentiment
        if (strpos($messageLower, 'interested') !== false || strpos($messageLower, 'excited') !== false) {
            $context['sentiment'] = 'positive';
        } elseif (strpos($messageLower, 'not sure') !== false || strpos($messageLower, 'maybe') !== false) {
            $context['sentiment'] = 'uncertain';
        }
        
        return $context;
    }
    
    /**
     * Detect customer's language from conversation history
     */
    private function detectCustomerLanguage(Lead $lead): string
    {
        $conversations = Conversation::where('lead_id', $lead->id)
            ->where('message_type', Conversation::TYPE_CUSTOMER)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $customerMessages = $conversations->pluck('customer_message')->filter();
        
        // Language detection patterns
        $languageIndicators = [
            'swahili' => ['habari', 'asante', 'karibu', 'mambo', 'poa', 'sawa', 'nzuri', 'shikamoo', 'sijambo'],
            'french' => ['bonjour', 'merci', 'oui', 'non', 'comment', 'ça va', 'salut', 's\'il vous plaît'],
            'arabic' => ['مرحبا', 'شكرا', 'نعم', 'لا', 'كيف', 'السلام عليكم'],
            'portuguese' => ['olá', 'obrigado', 'sim', 'não', 'como', 'bom dia', 'por favor'],
            'spanish' => ['hola', 'gracias', 'sí', 'no', 'cómo', 'buenos días', 'por favor']
        ];
        
        foreach ($customerMessages as $message) {
            $messageLower = mb_strtolower($message);
            
            foreach ($languageIndicators as $language => $indicators) {
                foreach ($indicators as $indicator) {
                    if (mb_strpos($messageLower, $indicator) !== false) {
                        Log::info("Detected customer language: {$language}", ['lead_id' => $lead->id]);
                        return $language;
                    }
                }
            }
        }
        
        return 'english'; // Default
    }
    
    /**
     * Identify what's missing from previous conversations
     */
    private function identifyMissingContext(Lead $lead, Appointment $appointment, array $conversationContext): array
    {
        $missing = [
            'benefits_not_mentioned' => [],
            'features_not_discussed' => [],
            'value_propositions' => []
        ];
        
        // Common benefits that could be highlighted
        $allBenefits = ['time_saving', 'cost_reduction', 'automation', 'scalability', 'support', 'integration'];
        $discussedBenefits = $conversationContext['interests_shown'] ?? [];
        
        $missing['benefits_not_mentioned'] = array_diff($allBenefits, $discussedBenefits);
        
        // If appointment has notes, that's context to emphasize
        if ($appointment->notes) {
            $missing['appointment_context'] = $appointment->notes;
        }
        
        // If they mentioned pain points but we haven't addressed solutions
        if (!empty($conversationContext['pain_points_mentioned']) && 
            empty($conversationContext['features_discussed'])) {
            $missing['value_propositions'][] = 'solution_to_pain_points';
        }
        
        return $missing;
    }
    
    /**
     * Generate AI-powered contextual reminder using OpenAI
     */
    private function generateAiContextualReminder(
        Appointment $appointment,
        Lead $lead,
        array $conversationContext,
        array $missingContext,
        string $language,
        string $businessName
    ): ?string {
        $scheduledDate = Carbon::parse($appointment->scheduled_at);
        $timeUntil = $scheduledDate->diffForHumans(now());
        $formattedDate = $scheduledDate->format('l, M j, Y');
        $formattedTime = $scheduledDate->format('g:i A');
        
        // Build AI prompt for contextual reminder
        $prompt = $this->buildReminderPrompt(
            $lead,
            $appointment,
            $conversationContext,
            $missingContext,
            $language,
            $timeUntil,
            $formattedDate,
            $formattedTime,
            $businessName
        );
        
        try {
            $response = $this->openAiService->client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an intelligent appointment reminder system. Generate unique, contextual, personalized reminders that NEVER repeat the same message. Each reminder must be different, add value, and consider the customer\'s conversation history.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.8, // Higher creativity for variety
            ]);
            
            $reminderMessage = $response->choices[0]->message->content;
            
            // Validate that message is not too generic
            if ($this->isMessageTooGeneric($reminderMessage)) {
                Log::warning('AI generated generic message, retrying', [
                    'appointment_id' => $appointment->id
                ]);
                return null;
            }
            
            return trim($reminderMessage);
            
        } catch (\Exception $e) {
            Log::error('AI reminder generation error', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Build prompt for AI reminder generation
     */
    private function buildReminderPrompt(
        Lead $lead,
        Appointment $appointment,
        array $conversationContext,
        array $missingContext,
        string $language,
        string $timeUntil,
        string $formattedDate,
        string $formattedTime,
        string $businessName
    ): string {
        $prompt = "Generate a UNIQUE appointment reminder message with these STRICT requirements:\n\n";
        
        $prompt .= "**CUSTOMER DETAILS:**\n";
        $prompt .= "- Name: {$lead->name}\n";
        $prompt .= "- Language: {$language}\n";
        $prompt .= "- Sentiment: {$conversationContext['sentiment']}\n";
        $prompt .= "- Previous conversations: {$conversationContext['total_conversations']}\n\n";
        
        $prompt .= "**APPOINTMENT:**\n";
        $prompt .= "- Title: {$appointment->title}\n";
        $prompt .= "- Time: {$formattedDate} at {$formattedTime}\n";
        $prompt .= "- Time until: {$timeUntil}\n";
        if ($appointment->location) {
            $prompt .= "- Location: {$appointment->location}\n";
        }
        if ($appointment->meeting_link) {
            $prompt .= "- Meeting Link: {$appointment->meeting_link}\n";
        }
        $prompt .= "\n";
        
        $prompt .= "**CONVERSATION CONTEXT:**\n";
        if (!empty($conversationContext['interests_shown'])) {
            $prompt .= "- Interests: " . implode(', ', $conversationContext['interests_shown']) . "\n";
        }
        if (!empty($conversationContext['pain_points_mentioned'])) {
            $prompt .= "- Pain points: " . implode(', ', $conversationContext['pain_points_mentioned']) . "\n";
        }
        $prompt .= "\n";
        
        $prompt .= "**WHAT TO EMPHASIZE (not yet discussed):**\n";
        if (!empty($missingContext['benefits_not_mentioned'])) {
            $prompt .= "- Benefits to introduce: " . implode(', ', array_slice($missingContext['benefits_not_mentioned'], 0, 2)) . "\n";
        }
        if (isset($missingContext['appointment_context'])) {
            $prompt .= "- Appointment context: {$missingContext['appointment_context']}\n";
        }
        $prompt .= "\n";
        
        $prompt .= "**REQUIREMENTS:**\n";
        $prompt .= "1. Write in {$language} language\n";
        $prompt .= "2. NEVER use generic phrases like 'This is a friendly reminder'\n";
        $prompt .= "3. Reference their specific interests or pain points\n";
        $prompt .= "4. Introduce ONE benefit or feature they haven't heard about yet\n";
        $prompt .= "5. Make it conversational and personalized\n";
        $prompt .= "6. Keep it under 150 words\n";
        $prompt .= "7. End with a question or confirmation request\n";
        $prompt .= "8. Include appointment details naturally\n";
        $prompt .= "9. Sign off as '{$businessName}'\n\n";
        
        $prompt .= "Generate the reminder message now:";
        
        return $prompt;
    }
    
    /**
     * Check if AI message is too generic
     */
    private function isMessageTooGeneric(string $message): bool
    {
        $genericPhrases = [
            'friendly reminder',
            'just a reminder',
            'this is to remind you',
            'don\'t forget',
            'reminder about your appointment'
        ];
        
        $messageLower = strtolower($message);
        
        foreach ($genericPhrases as $phrase) {
            if (strpos($messageLower, $phrase) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate smart fallback reminder when AI fails
     */
    private function generateSmartFallbackReminder(
        Appointment $appointment,
        Lead $lead,
        array $missingContext,
        string $language,
        string $businessName
    ): string {
        $scheduledDate = Carbon::parse($appointment->scheduled_at);
        $timeUntil = $scheduledDate->diffForHumans(now());
        $formattedDate = $scheduledDate->format('l, M j');
        $formattedTime = $scheduledDate->format('g:i A');
        
        // Language-specific greetings and templates
        $templates = $this->getLanguageTemplates($language);
        
        // Pick a random variation to ensure uniqueness
        $variations = $templates['variations'];
        $selectedVariation = $variations[array_rand($variations)];
        
        // Build personalized message
        $message = str_replace(
            ['{name}', '{date}', '{time}', '{until}', '{title}', '{business}'],
            [$lead->name, $formattedDate, $formattedTime, $timeUntil, $appointment->title, $businessName],
            $selectedVariation
        );
        
        // Add benefit if available
        if (!empty($missingContext['benefits_not_mentioned'])) {
            $benefit = $missingContext['benefits_not_mentioned'][0];
            $benefitText = $templates['benefits'][$benefit] ?? '';
            if ($benefitText) {
                $message .= "\n\n" . $benefitText;
            }
        }
        
        // Add location/link if available
        if ($appointment->meeting_link) {
            $message .= "\n\n{$templates['meeting_link']}: {$appointment->meeting_link}";
        } elseif ($appointment->location) {
            $message .= "\n\n{$templates['location']}: {$appointment->location}";
        }
        
        $message .= "\n\n{$templates['confirmation']}";
        
        return $message;
    }
    
    /**
     * Get language-specific templates
     */
    private function getLanguageTemplates(string $language): array
    {
        $templates = [
            'english' => [
                'variations' => [
                    "Hi {name}! 👋\n\nLooking forward to our {title} on {date} at {time} ({until}).",
                    "Hey {name}! Quick heads up about our meeting on {date} at {time}. ({until}).",
                    "{name}, wanted to touch base about our {title} scheduled for {date} at {time}.",
                    "Hi {name}! Just confirming - we're set for {date} at {time} for {title}. That's {until}!",
                ],
                'benefits' => [
                    'time_saving' => "💡 Did you know this could save you hours each week?",
                    'cost_reduction' => "💰 We'll show you how this reduces your operational costs.",
                    'automation' => "⚡ Automation features that work while you sleep!",
                    'scalability' => "📈 Built to scale with your growing business.",
                    'support' => "🤝 Our team is here 24/7 to support you.",
                    'integration' => "🔗 Seamlessly integrates with your existing tools."
                ],
                'meeting_link' => "🔗 Join here",
                'location' => "📍 Location",
                'confirmation' => "Can you confirm you'll make it? 😊"
            ],
            'swahili' => [
                'variations' => [
                    "Habari {name}! 👋\n\nNasubiri mkutano wetu wa {title} tarehe {date} saa {time} ({until}).",
                    "Mambo {name}! Ukumbusho wa mkutano wetu {date} saa {time}. ({until}).",
                    "{name}, napenda kukuthibitishia mkutano wetu wa {title} tarehe {date} saa {time}.",
                ],
                'benefits' => [
                    'time_saving' => "💡 Unajua hii inaweza kukuokoa masaa kila wiki?",
                    'cost_reduction' => "💰 Tutakuonyesha jinsi hii inavyopunguza gharama.",
                    'automation' => "⚡ Vipengele vya kiotomatiki vinavyofanya kazi hata unapol ala!",
                ],
                'meeting_link' => "🔗 Jiunge hapa",
                'location' => "📍 Mahali",
                'confirmation' => "Unaweza kuthibitisha utakuja? 😊"
            ],
            'french' => [
                'variations' => [
                    "Bonjour {name}! 👋\n\nJ'attends avec impatience notre {title} le {date} à {time} ({until}).",
                    "Salut {name}! Un petit rappel pour notre réunion du {date} à {time}. ({until}).",
                ],
                'benefits' => [
                    'time_saving' => "💡 Saviez-vous que cela pourrait vous faire gagner des heures chaque semaine?",
                ],
                'meeting_link' => "🔗 Rejoindre ici",
                'location' => "📍 Lieu",
                'confirmation' => "Pouvez-vous confirmer votre présence? 😊"
            ],
        ];
        
        return $templates[$language] ?? $templates['english'];
    }
    
    /**
     * Generate reminder message text (OLD METHOD - DEPRECATED)
     * This method is kept for backward compatibility but should not be used
     * Use generateIntelligentReminderMessage instead
     */
    private function generateReminderMessage(Appointment $appointment): string
    {
        // This method is deprecated - calls now route to intelligent reminder
        return $this->generateIntelligentReminderMessage($appointment);
    }
}