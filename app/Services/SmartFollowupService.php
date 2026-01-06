<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Conversation;
use App\Models\BusinessContact;
use App\Services\AiWhatsAppService;
use Illuminate\Support\Facades\Log;
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
            // Get leads that are NOT closed and need followup
            $leadsNeedingFollowUp = Lead::whereNotIn('status', [
                                        Lead::STATUS_CLOSED, 
                                        Lead::STATUS_LOST, 
                                        Lead::STATUS_DO_NOT_CONTACT,
                                        Lead::STATUS_CONVERTED
                                    ])
                                    ->where('last_interaction_at', '<', now()->subDays(3))
                                    ->whereNull('follow_up_sent_at')
                                    ->with(['contact', 'conversations', 'aiSalesAgent'])
                                    ->limit(20)
                                    ->get();

            Log::info("Smart followup: Found {$leadsNeedingFollowUp->count()} leads needing followup");

            $successCount = 0;
            $skipCount = 0;
            $errorCount = 0;

            foreach ($leadsNeedingFollowUp as $lead) {
                try {
                    if (!$lead->aiSalesAgent || !$lead->aiSalesAgent->auto_followup) {
                        $skipCount++;
                        continue;
                    }

                    // Generate personalized followup message
                    $followupMessage = $this->generatePersonalizedFollowup($lead);
                    
                    if (!$followupMessage) {
                        Log::warning("Could not generate followup message for lead {$lead->id}");
                        $skipCount++;
                        continue;
                    }

                    // Send the followup
                    $sent = $this->sendSmartFollowup($lead, $followupMessage);
                    
                    if ($sent) {
                        $lead->update(['follow_up_sent_at' => now()]);
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
     * Generate personalized followup message based on conversation history and customer language
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
        
        $context = [
            'last_customer_message' => $lastMessage?->message_content ?? '',
            'days_since_last_contact' => $lastMessage 
                ? Carbon::parse($lastMessage->created_at)->diffInDays(now()) 
                : 7,
            'has_shown_interest' => $this->hasShownInterest($conversationTopics),
            'mentioned_budget' => $this->mentionedBudget($conversationTopics),
            'mentioned_timeline' => $this->mentionedTimeline($conversationTopics),
            'conversation_stage' => $this->determineConversationStage($conversations)
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
     * Create contextual message based on analysis
     */
    private function createContextualMessage(Lead $lead, $context, $language)
    {
        $customerName = $lead->getContactName();
        
        // Base messages in different languages
        $messages = [
            'english' => $this->getEnglishMessages($customerName, $context),
            'swahili' => $this->getSwahiliMessages($customerName, $context),
            'french' => $this->getFrenchMessages($customerName, $context),
            'arabic' => $this->getArabicMessages($customerName, $context),
            'portuguese' => $this->getPortugueseMessages($customerName, $context),
            'spanish' => $this->getSpanishMessages($customerName, $context)
        ];

        // Select appropriate message based on context and stage
        $languageMessages = $messages[$language] ?? $messages['english'];
        
        if ($context['conversation_stage'] === 'advanced' && $context['has_shown_interest']) {
            return $languageMessages['closing'];
        } elseif ($context['has_shown_interest']) {
            return $languageMessages['interested'];
        } elseif ($context['days_since_last_contact'] > 5) {
            return $languageMessages['re_engage'];
        } else {
            return $languageMessages['follow_up'];
        }
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
                'sender_type' => 'ai_agent_followup',
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