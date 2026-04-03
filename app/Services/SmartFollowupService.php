<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Conversation;
use App\Models\BusinessContact;
use App\Services\AiWhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SmartFollowupService
{
    private $aiWhatsAppService;
    
    public function __construct(AiWhatsAppService $aiWhatsAppService)
    {
        $this->aiWhatsAppService = $aiWhatsAppService;
    }

    /**
     * Process smart followups for leads that are not closed yet
     */
    public function processSmartFollowups()
    {
        try {
            // Check if followups should only be sent during business hours
            $enforceBusinessHours = env('AI_FOLLOWUP_BUSINESS_HOURS', false);
            $now = Carbon::now();
            $businessStart = $now->copy()->setTime(8, 0, 0); // 8:00 AM
            $businessEnd = $now->copy()->setTime(18, 0, 0); // 6:00 PM
            if ($enforceBusinessHours && ($now->lt($businessStart) || $now->gte($businessEnd))) {
                Log::info('Smart followup: Skipped due to outside business hours (' . $now->format('H:i') . ')');
                return;
            }
            // Get leads that are NOT closed and need followup.
            // Only fetch leads whose AI agent has auto_followup enabled — filtering here
            // avoids fetching leads that will always be skipped and never updated.
            $leadsNeedingFollowUp = Lead::whereNotIn('status', [
                                        Lead::STATUS_CLOSED, 
                                        Lead::STATUS_LOST, 
                                        Lead::STATUS_DO_NOT_CONTACT,
                                        Lead::STATUS_CONVERTED
                                    ])
                                    ->whereHas('aiSalesAgent', function($query) {
                                        $query->where('auto_followup', true);
                                    })
                                    ->where(function($query) {
                                        $query->whereNull('last_interaction_at')
                                              ->orWhere('last_interaction_at', '<', now()->subDays(3));
                                    })
                                    ->where(function($query) {
                                        // Either never sent followup OR last followup > 7 days ago
                                        $query->whereNull('follow_up_sent_at')
                                              ->orWhere('follow_up_sent_at', '<', now()->subDays(7));
                                    })
                                    ->with(['contact', 'conversations', 'aiSalesAgent'])
                                    ->limit(20)
                                    ->get();

            Log::info("Smart followup: Found {$leadsNeedingFollowUp->count()} leads needing followup");

            $successCount = 0;
            $skipCount = 0;
            $errorCount = 0;

            foreach ($leadsNeedingFollowUp as $lead) {
                try {
                    // Check if followup already sent today (prevents duplicate sends)
                    $cacheKey = "smart_followup_sent_today_{$lead->id}";
                    if (Cache::has($cacheKey)) {
                        Log::info("Smart followup already sent today to lead {$lead->id}, skipping");
                        $skipCount++;
                        continue;
                    }
                    
                    // Safety guard — should never be true thanks to whereHas filter in query,
                    // but defend against edge cases (agent deleted mid-run, etc.)
                    if (!$lead->aiSalesAgent || !$lead->aiSalesAgent->auto_followup) {
                        Log::warning("Lead {$lead->id} has no auto_followup agent — stamping follow_up_sent_at to remove from queue");
                        $lead->update(['follow_up_sent_at' => now()]);
                        $skipCount++;
                        continue;
                    }

                    // Generate personalized followup message
                    $followupMessage = $this->generatePersonalizedFollowup($lead);
                    
                    if (!$followupMessage) {
                        // Stamp follow_up_sent_at so this lead exits the 7-day window and
                        // doesn't keep reappearing on every cron tick.
                        Log::warning("Could not generate followup message for lead {$lead->id} — stamping follow_up_sent_at to suppress for 7 days");
                        $lead->update(['follow_up_sent_at' => now()]);
                        $skipCount++;
                        continue;
                    }

                    // Send the followup
                    $sent = $this->sendSmartFollowup($lead, $followupMessage);
                    
                    if ($sent) {
                        $lead->update(['follow_up_sent_at' => now()]);
                        
                        // Cache the send to prevent duplicate sends today
                        Cache::put($cacheKey, true, now()->endOfDay());
                        
                        $successCount++;
                        Log::info("Smart followup sent to lead {$lead->id}");
                    } else {
                        $errorCount++;
                        Log::warning("Failed to send followup to lead {$lead->id}");
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Error processing followup for lead {$lead->id}: " . $e->getMessage());
                }
            }

            Log::info("Smart followup summary - Success: {$successCount}, Skipped: {$skipCount}, Errors: {$errorCount}");

        } catch (\Exception $e) {
            Log::error('Smart followup processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate truly personalized followup message based on conversation history and customer language
     */
    private function generatePersonalizedFollowup(Lead $lead)
    {
        try {
            // Get conversation history
            $conversations = $lead->conversations()
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get();

            // Detect customer's language from conversation history
            $customerLanguage = $this->detectCustomerLanguage($conversations);
            
            // Analyze conversation context
            $conversationContext = $this->analyzeConversationContext($conversations);
            
            // Generate contextual message based on lead status and history
            $followupMessage = $this->createContextualMessage($lead, $conversationContext, $customerLanguage);

            return $followupMessage;

        } catch (\Exception $e) {
            Log::error("Error generating personalized followup for lead {$lead->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect customer's language from conversation history
     */
    private function detectCustomerLanguage($conversations)
    {
        $customerMessages = $conversations->where('sender_type', 'customer')
                                          ->pluck('message_content');

        // Simple language detection based on common patterns
        $languageIndicators = [
            'swahili' => ['habari', 'asante', 'karibu', 'mambo', 'poa', 'sawa', 'nzuri', 'shikamoo'],
            'french' => ['bonjour', 'merci', 'oui', 'non', 'comment', 'ca va', 'salut'],
            'arabic' => ['مرحبا', 'شكرا', 'نعم', 'لا', 'كيف', 'السلام عليكم'],
            'portuguese' => ['olá', 'obrigado', 'sim', 'não', 'como', 'bom dia'],
            'spanish' => ['hola', 'gracias', 'sí', 'no', 'cómo', 'buenos días']
        ];

        foreach ($customerMessages as $message) {
            $messageLower = strtolower($message);
            
            foreach ($languageIndicators as $language => $indicators) {
                foreach ($indicators as $indicator) {
                    if (strpos($messageLower, $indicator) !== false) {
                        return $language;
                    }
                }
            }
        }

        return 'english'; // Default to English
    }

    /**
     * Analyze conversation context to understand customer's situation
     */
    private function analyzeConversationContext($conversations)
    {
        $lastMessage = $conversations->where('sender_type', 'customer')->first();
        $conversationTopics = $conversations->pluck('message_content')->implode(' ');
        
        // Determine if this is a brand new contact with no conversation history
        $hasConversations = $conversations->count() > 0;
        
        $context = [
            'last_customer_message' => $lastMessage?->message_content ?? '',
            'days_since_last_contact' => $lastMessage 
                ? Carbon::parse($lastMessage->created_at)->diffInDays(now()) 
                : 0,
            'has_shown_interest' => $hasConversations ? $this->hasShownInterest($conversationTopics) : false,
            'mentioned_budget' => $hasConversations ? $this->mentionedBudget($conversationTopics) : false,
            'mentioned_timeline' => $hasConversations ? $this->mentionedTimeline($conversationTopics) : false,
            'conversation_stage' => $this->determineConversationStage($conversations),
            'is_new_contact' => !$hasConversations // Flag for contacts never contacted before
        ];

        return $context;
    }

    /**
     * Check if customer has shown interest
     */
    private function hasShownInterest($conversationTopics)
    {
        $interestKeywords = ['interested', 'yes', 'tell me more', 'how much', 'when', 'schedule', 'demo', 'price'];
        $topicsLower = strtolower($conversationTopics);
        
        foreach ($interestKeywords as $keyword) {
            if (strpos($topicsLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if budget was mentioned
     */
    private function mentionedBudget($conversationTopics)
    {
        $budgetKeywords = ['budget', 'cost', 'price', 'expensive', 'cheap', 'afford', 'money', '$', 'usd'];
        $topicsLower = strtolower($conversationTopics);
        
        foreach ($budgetKeywords as $keyword) {
            if (strpos($topicsLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if timeline was mentioned
     */
    private function mentionedTimeline($conversationTopics)
    {
        $timelineKeywords = ['when', 'deadline', 'urgent', 'soon', 'next month', 'next week', 'asap'];
        $topicsLower = strtolower($conversationTopics);
        
        foreach ($timelineKeywords as $keyword) {
            if (strpos($topicsLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine conversation stage
     */
    private function determineConversationStage($conversations)
    {
        $messageCount = $conversations->count();
        $hasCustomerResponse = $conversations->where('sender_type', 'customer')->count() > 0;
        
        if ($messageCount <= 2 && !$hasCustomerResponse) return 'initial';
        if ($messageCount > 2 && $hasCustomerResponse) return 'engaged';
        if ($messageCount > 5) return 'advanced';
        
        return 'basic';
    }

    /**
     * Create contextual message based on analysis - TRULY PERSONALIZED
     */
    private function createContextualMessage(Lead $lead, $context, $language)
    {
        $name = $lead->getContactName();
        $lastMessage = $context['last_customer_message'];
        $daysSince = $context['days_since_last_contact'];
        $stage = $context['conversation_stage'];
        $hasInterest = $context['has_shown_interest'];
        $mentionedBudget = $context['mentioned_budget'];
        $mentionedTimeline = $context['mentioned_timeline'];
        
        // Get specific product they were interested in
        $primaryProduct = $this->getPrimaryProductFromLead($lead);
        $productName = $primaryProduct ?? 'our solution';
        
        // Analyze their specific concerns or interests from last message
        $concerns = $this->extractConcerns($lastMessage);
        $interests = $this->extractInterests($lastMessage);
        
        // Generate contextual message based on their specific situation
        $message = $this->buildContextualMessage([
            'name' => $name,
            'product' => $productName,
            'last_message' => $lastMessage,
            'days_since' => $daysSince,
            'concerns' => $concerns,
            'interests' => $interests,
            'stage' => $stage,
            'has_budget_discussion' => $mentionedBudget,
            'has_timeline' => $mentionedTimeline,
            'language' => $language,
            'lead_score' => $this->calculateLeadScore($lead),
            'is_new_contact' => $context['is_new_contact'] ?? false
        ]);
        
        return $message;
    }

    /**
     * Build contextual message based on specific customer situation
     */
    private function buildContextualMessage($data)
    {
        $templates = $this->getContextualTemplates($data['language']);
        $name = $data['name'];
        $product = $data['product'] ?? 'our solution';
        $lastMsg = strtolower($data['last_message'] ?? '');
        $days = $data['days_since'];
        $concerns = $data['concerns'];
        $interests = $data['interests'];
        $isNewContact = $data['is_new_contact'] ?? false;
        
        // 0. FIRST CONTACT - Not a followup, this is initial outreach
        if ($isNewContact) {
            return str_replace(['{name}', '{product}'], 
                [$name, $product], 
                $templates['first_contact']);
        }
        
        // 1. RESPOND TO SPECIFIC CONCERNS
        if (!empty($concerns)) {
            if (in_array('price', $concerns) || in_array('cost', $concerns)) {
                return str_replace(['{name}', '{product}', '{concern}'], 
                    [$name, $product, 'pricing'], 
                    $templates['address_price_concern']);
            }
            
            if (in_array('time', $concerns) || in_array('busy', $concerns)) {
                return str_replace(['{name}', '{product}'], 
                    [$name, $product], 
                    $templates['address_time_concern']);
            }
        }
        
        // 2. REFERENCE SPECIFIC INTERESTS
        if (!empty($interests)) {
            $interest = $interests[0]; // Take first interest
            return str_replace(['{name}', '{product}', '{interest}'], 
                [$name, $product, $interest], 
                $templates['follow_interest']);
        }
        
        // 3. REFERENCE THEIR LAST MESSAGE DIRECTLY
        if (strpos($lastMsg, 'think') !== false || strpos($lastMsg, 'consider') !== false) {
            return str_replace(['{name}', '{product}'], 
                [$name, $product], 
                $templates['thinking_response']);
        }
        
        if (strpos($lastMsg, 'later') !== false || strpos($lastMsg, 'busy') !== false) {
            return str_replace(['{name}', '{product}', '{days}'], 
                [$name, $product, $days], 
                $templates['timing_response']);
        }
        
        // 4. VALUE-BASED FOLLOWUP (not generic)
        if ($data['lead_score'] > 60) {
            return str_replace(['{name}', '{product}'], 
                [$name, $product], 
                $templates['high_intent']);
        }
        
        // 5. DEFAULT - Still personalized, not generic
        return str_replace(['{name}', '{product}', '{days}'], 
            [$name, $product, $days], 
            $templates['personalized_default']);
    }

    /**
     * Extract specific concerns from customer's message
     */
    private function extractConcerns($message)
    {
        $concerns = [];
        $msg = strtolower($message);
        
        if (strpos($msg, 'expensive') !== false || strpos($msg, 'cost') !== false || 
            strpos($msg, 'price') !== false || strpos($msg, 'budget') !== false) {
            $concerns[] = 'price';
        }
        
        if (strpos($msg, 'time') !== false || strpos($msg, 'busy') !== false || 
            strpos($msg, 'schedule') !== false) {
            $concerns[] = 'time';
        }
        
        if (strpos($msg, 'competitor') !== false || strpos($msg, 'other') !== false) {
            $concerns[] = 'competition';
        }
        
        return $concerns;
    }

    /**
     * Extract specific interests from customer's message
     */
    private function extractInterests($message)
    {
        $interests = [];
        $msg = strtolower($message);
        
        $interestMap = [
            'features' => ['features', 'functionality', 'capabilities'],
            'demo' => ['demo', 'show', 'see', 'preview'],
            'integration' => ['integration', 'connect', 'api', 'sync'],
            'support' => ['support', 'help', 'training', 'onboarding'],
            'pricing' => ['pricing', 'plans', 'packages', 'options'],
            'security' => ['security', 'safe', 'protection', 'privacy'],
            'scalability' => ['scale', 'growth', 'expand', 'users']
        ];
        
        foreach ($interestMap as $interest => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($msg, $keyword) !== false) {
                    $interests[] = $interest;
                    break;
                }
            }
        }
        
        return array_unique($interests);
    }

    /**
     * Get primary product from lead
     */
    private function getPrimaryProductFromLead(Lead $lead)
    {
        $products = $lead->getActiveProducts();
        return $products->first()?->name ?? null;
    }

    /**
     * Calculate lead score for personalization
     */
    private function calculateLeadScore(Lead $lead)
    {
        return method_exists($lead, 'calculateLeadScore') ? $lead->calculateLeadScore() : 50;
    }

    /**
     * Contextual message templates - MUCH MORE PERSONALIZED
     */
    private function getContextualTemplates($language = 'english')
    {
        $templates = [
            'english' => [
                'first_contact' => "Hi {name}! I noticed you recently joined us. I'm here to help with {product}. What specific challenges are you looking to solve? Let me know how I can assist!",
                
                'address_price_concern' => "Hi {name}! I understand pricing is important for {product}. We actually have flexible options that might work better than you think. Could I share a quick cost breakdown that shows the ROI?",
                
                'address_time_concern' => "Hi {name}! I know you're busy. That's exactly why {product} saves 5+ hours weekly for teams like yours. Worth a 10-minute chat to see the time savings?",
                
                'follow_interest' => "Hi {name}! You mentioned {interest} for {product}. I found some examples of how this works for similar companies. Want me to send them over?",
                
                'thinking_response' => "Hi {name}! Take your time with {product}. When you're ready, I have answers to the top 3 questions most people ask. No pressure - just here when you need me.",
                
                'timing_response' => "Hi {name}! Perfect timing - it's been {days} days. Quick update: we just added new features to {product} that solve the exact issue you mentioned. 2-minute update?",
                
                'high_intent' => "Hi {name}! Based on our chat, {product} seems like a perfect fit. I can fast-track you with our implementation team. Ready to see how quickly we can get you started?",
                
                'personalized_default' => "Hi {name}! Hope your week is going well. I was thinking about your {product} needs and found something that might interest you. Quick question - is this still a priority?"
            ],
            
            'swahili' => [
                'first_contact' => "Habari {name}! Nimeona umejiunga nasi hivi karibuni. Niko hapa kukusaidia na {product}. Ni changamoto gani maalum unazotafuta kusuluhisha? Nijulishe jinsi ninavyoweza kukusaidia!",
                
                'address_price_concern' => "Habari {name}! Naelewa bei ni muhimu kwa {product}. Kwa kweli tuna chaguo rahisi zaidi kuliko unavyofikiri. Naweza kushiriki maelezo ya gharama na faida?",
                
                'address_time_concern' => "Habari {name}! Najua una kazi nyingi. Ndio maana {product} inaokoa masaa 5+ kwa wiki kwa timu kama yenu. Je tunaweza zungumza dakika 10 kuona jinsi inavyookoa muda?",
                
                'follow_interest' => "Habari {name}! Ulitaja {interest} kwa {product}. Nimepata mifano ya jinsi hii inavyofanya kazi kwa makampuni kama yenu. Natume?",
                
                'thinking_response' => "Habari {name}! Chukua muda wako na {product}. Utakapo kuwa tayari, nina majibu ya maswali 3 makuu watu wengi hauliza. Hakuna haraka - niko hapa utakaponihitaji.",
                
                'timing_response' => "Habari {name}! Wakati mzuri - zimepita siku {days}. Habari mpya: tumeongeza vipengele vipya vya {product} vinavyosuluhisha tatizo ulilotaja. Taarifa ya dakika 2?",
                
                'high_intent' => "Habari {name}! Kulingana na mazungumzo yetu, {product} inaonekana ni sahihi kabisa. Naweza kuharakisha utaratibu na timu yetu ya utekelezaji. Tayari kuona tunaweza kuanza haraka kiasi gani?",
                
                'personalized_default' => "Habari {name}! Natumai wiki yako inaenda vizuri. Nilikuwa nikifikiri kuhusu mahitaji yako ya {product} na nimepata kitu kinachoweza kukuvutia. Swali la haraka - hii bado ni kipaumbele?"
            ]
        ];
        
        return $templates[$language] ?? $templates['english'];
    }

    /**
     * English message templates
     */
    private function getEnglishMessages($name, $context)
    {
        return [
            'follow_up' => "Hi {$name}! I wanted to follow up on our conversation. Have you had a chance to consider our discussion? I'm here if you have any questions.",
            'interested' => "Hello {$name}! Since you showed interest in our solution, I wanted to check if there's anything specific you'd like to know more about or if you're ready to move forward.",
            'closing' => "Hi {$name}! Based on our conversations, it seems like our solution could be a great fit for your needs. Would you like to schedule a time to finalize the details?",
            're_engage' => "Hello {$name}! It's been a while since we last spoke. I hope you're doing well. I wanted to reach out in case you still need assistance with your requirements."
        ];
    }

    /**
     * Swahili message templates
     */
    private function getSwahiliMessages($name, $context)
    {
        return [
            'follow_up' => "Habari {$name}! Nataka kufuatilia mazungumzo yetu. Je, umepata nafasi ya kufikiri kuhusu yale tuliyojadili? Niko hapa kama una maswali yoyote.",
            'interested' => "Habari {$name}! Kwa kuwa ulionyesha nia katika suluhisho letu, nilitaka kuangalia kama kuna kitu maalum ungependa kujua zaidi au kama uko tayari kuendelea.",
            'closing' => "Habari {$name}! Kulingana na mazungumzo yetu, inaonekana suluhisho letu linaweza kuwa la kufaa kwa mahitaji yako. Je, ungependa kupanga wakati wa kumaliza maelezo?",
            're_engage' => "Habari {$name}! Imekuwa muda tangu tulipozungumza mwisho. Natumai upo vizuri. Nilitaka kuwasiliana nawe kwa nia ya kukusaidia ikiwa bado unahitaji msaada."
        ];
    }

    /**
     * French message templates  
     */
    private function getFrenchMessages($name, $context)
    {
        return [
            'follow_up' => "Bonjour {$name}! Je voulais faire un suivi de notre conversation. Avez-vous eu l'occasion de considérer notre discussion? Je suis là si vous avez des questions.",
            'interested' => "Bonjour {$name}! Puisque vous avez montré de l'intérêt pour notre solution, je voulais vérifier s'il y a quelque chose de spécifique que vous aimeriez savoir ou si vous êtes prêt à aller de l'avant.",
            'closing' => "Bonjour {$name}! Basé sur nos conversations, il semble que notre solution pourrait être parfaite pour vos besoins. Souhaitez-vous programmer un moment pour finaliser les détails?",
            're_engage' => "Bonjour {$name}! Cela fait un moment que nous n'avons pas parlé. J'espère que vous allez bien. Je voulais vous contacter au cas où vous auriez encore besoin d'aide."
        ];
    }

    /**
     * Arabic message templates
     */
    private function getArabicMessages($name, $context)
    {
        return [
            'follow_up' => "مرحبا {$name}! أردت متابعة محادثتنا. هل أتيحت لك الفرصة للنظر في نقاشنا؟ أنا هنا إذا كان لديك أي أسئلة.",
            'interested' => "مرحبا {$name}! بما أنك أظهرت اهتماما بحلولنا، أردت التحقق مما إذا كان هناك شيء محدد تود معرفة المزيد عنه أو إذا كنت مستعدا للمتابعة.",
            'closing' => "مرحبا {$name}! بناء على محادثاتنا، يبدو أن حلولنا قد تكون مناسبة تماما لاحتياجاتك. هل تود تحديد وقت لإنهاء التفاصيل؟",
            're_engage' => "مرحبا {$name}! لقد مر وقت منذ آخر مرة تحدثنا فيها. أتمنى أن تكون بخير. أردت التواصل معك في حال كنت لا تزال بحاجة إلى مساعدة."
        ];
    }

    /**
     * Portuguese message templates
     */
    private function getPortugueseMessages($name, $context)
    {
        return [
            'follow_up' => "Olá {$name}! Queria fazer um acompanhamento da nossa conversa. Teve chance de considerar nossa discussão? Estou aqui se tiver alguma pergunta.",
            'interested' => "Olá {$name}! Como mostrou interesse na nossa solução, queria verificar se há algo específico que gostaria de saber mais ou se está pronto para seguir em frente.",
            'closing' => "Olá {$name}! Baseado nas nossas conversas, parece que nossa solução pode ser perfeita para suas necessidades. Gostaria de agendar um momento para finalizar os detalhes?",
            're_engage' => "Olá {$name}! Faz tempo desde a última vez que conversamos. Espero que esteja bem. Queria entrar em contato caso ainda precise de assistência com seus requisitos."
        ];
    }

    /**
     * Spanish message templates
     */
    private function getSpanishMessages($name, $context)
    {
        return [
            'follow_up' => "¡Hola {$name}! Quería hacer un seguimiento de nuestra conversación. ¿Has tenido la oportunidad de considerar nuestra discusión? Estoy aquí si tienes alguna pregunta.",
            'interested' => "¡Hola {$name}! Como mostraste interés en nuestra solución, quería verificar si hay algo específico que te gustaría saber más o si estás listo para seguir adelante.",
            'closing' => "¡Hola {$name}! Basado en nuestras conversaciones, parece que nuestra solución podría ser perfecta para tus necesidades. ¿Te gustaría programar un momento para finalizar los detalles?",
            're_engage' => "¡Hola {$name}! Ha pasado tiempo desde la última vez que hablamos. Espero que estés bien. Quería contactarte en caso de que aún necesites ayuda con tus requisitos."
        ];
    }

    /**
     * Send smart followup message
     */
    private function sendSmartFollowup(Lead $lead, $message)
    {
        try {
            // Create conversation record
            Conversation::create([
                'lead_id' => $lead->id,
                'ai_sales_agent_id' => $lead->ai_sales_agent_id,
                'message_content' => $message,
                'sender_type' => 'ai_agent', // Valid values: customer, ai_agent, user_manual, ai_agent_followup
                'created_at' => now()
            ]);

            // Get WhatsApp instance and send
            $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', $lead->aiSalesAgent->user_id)
                                                           ->where('status', 'connected')
                                                           ->first();

            if ($whatsappInstance && $lead->contact && $lead->contact->guest_phone) {
                \App\Jobs\SendWhatsAppMessage::dispatch(
                    $message,
                    $lead->contact->guest_phone,
                    'whatsapp',
                    $lead->aiSalesAgent->user_id,
                    null,
                    $whatsappInstance->instance_id
                );

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Error sending smart followup for lead {$lead->id}: " . $e->getMessage());
            return false;
        }
    }
}