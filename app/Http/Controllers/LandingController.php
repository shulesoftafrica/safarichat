<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LandingController extends Controller
{
    /**
     * Display the AI Sales Agent landing page
     * 
     * @param Request $request
     * @param string|null $locale
     * @return \Illuminate\View\View
     */
    public function index(Request $request, $locale = null)
    {
        // Handle language detection and setting
        if ($locale && in_array($locale, ['en', 'es', 'pt-br', 'hi', 'ar', 'fr'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        } else {
            // Auto-detect language from browser or use default
            $detectedLocale = $this->detectLanguage($request);
            App::setLocale($detectedLocale);
            Session::put('locale', $detectedLocale);
        }

        // Get current locale for view
        $currentLocale = App::getLocale();
        
        // Get localized content
        $content = $this->getLocalizedContent($currentLocale);
        
        // Ensure content has all required sections with fallbacks
        $content = $this->ensureContentFallbacks($content);
        
        // Detect user's currency based on locale or IP
        $currency = $this->detectCurrency($request, $currentLocale);
        
        // Get pricing data for the detected currency
        $pricingData = $this->getPricingData($currency);

        return view('landing.index', compact('content', 'currentLocale', 'currency', 'pricingData'));
    }

    /**
     * Handle demo chat requests
     */
    public function demoChat(Request $request)
    {
        $message = $request->input('message');
        $locale = App::getLocale();
        
        // Simple demo responses based on locale
        $responses = [
            'en' => $this->getEnglishDemoResponses(),
            'es' => $this->getSpanishDemoResponses(),
            'pt-br' => $this->getPortugueseDemoResponses(),
            'hi' => $this->getHindiDemoResponses(),
            'ar' => $this->getArabicDemoResponses(),
            'fr' => $this->getFrenchDemoResponses(),
        ];

        $demoResponses = $responses[$locale] ?? $responses['en'];
        
        // Simple keyword matching for demo
        $response = $this->generateDemoResponse($message, $demoResponses);

        return response()->json([
            'success' => true,
            'response' => $response,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Calculate ROI for potential customers
     */
    public function calculateROI(Request $request)
    {
        $teamSize = $request->input('team_size', 1);
        $avgDealSize = $request->input('avg_deal_size', 1000);
        $monthlyLeads = $request->input('monthly_leads', 100);
        $currentConversionRate = $request->input('conversion_rate', 0.1);
        
        // ROI calculation logic
        $aiConversionBoost = 0.35; // 35% improvement
        $hourlyWage = 15; // Average hourly wage
        $hoursPerLead = 0.5; // Hours saved per lead
        
        $costSavings = $teamSize * $monthlyLeads * $hoursPerLead * $hourlyWage * 12; // Annual
        $additionalRevenue = $avgDealSize * $monthlyLeads * $currentConversionRate * $aiConversionBoost * 12;
        $totalROI = $costSavings + $additionalRevenue;
        
        // Calculate AI service cost (annual)
        $monthlyAICost = $this->calculateMonthlyCost($monthlyLeads);
        $annualAICost = $monthlyAICost * 12;
        
        $netROI = $totalROI - $annualAICost;
        $roiPercentage = ($netROI / $annualAICost) * 100;

        return response()->json([
            'success' => true,
            'results' => [
                'cost_savings' => $costSavings,
                'additional_revenue' => $additionalRevenue,
                'total_roi' => $totalROI,
                'annual_ai_cost' => $annualAICost,
                'net_roi' => $netROI,
                'roi_percentage' => $roiPercentage,
                'monthly_ai_cost' => $monthlyAICost
            ]
        ]);
    }

    /**
     * Get pricing for specific currency
     */
    public function getPricing(Request $request, $currency = 'TSH')
    {
        $pricingData = $this->getPricingData(strtoupper($currency));
        
        return response()->json([
            'success' => true,
            'currency' => $currency,
            'pricing' => $pricingData
        ]);
    }

    /**
     * Handle contact form submissions
     */
    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'required|string|max:255',
            'message' => 'required|string|max:1000'
        ]);

        // Store contact request (you can implement your logic here)
        // For now, just return success
        
        return response()->json([
            'success' => true,
            'message' => __('landing.contact_success')
        ]);
    }

    /**
     * Detect user language from browser
     */
    private function detectLanguage($request)
    {
        $acceptedLanguages = ['en', 'es', 'pt-br', 'hi', 'ar', 'fr'];
        $browserLanguage = $request->getPreferredLanguage($acceptedLanguages);
        
        // Map browser locales to our supported locales
        $languageMap = [
            'pt' => 'pt-br',
            'pt-BR' => 'pt-br',
            'hi-IN' => 'hi',
            'ar-SA' => 'ar',
            'ar-EG' => 'ar',
            'fr-FR' => 'fr',
            'es-ES' => 'es',
            'es-MX' => 'es'
        ];

        return $languageMap[$browserLanguage] ?? $browserLanguage ?? 'en';
    }

    /**
     * Detect currency based on locale or IP
     */
    private function detectCurrency($request, $locale)
    {
        $currencyMap = [
            'en' => 'USD',
            'es' => 'USD',
            'pt-br' => 'BRL',
            'hi' => 'INR',
            'ar' => 'USD',
            'fr' => 'EUR'
        ];

        return $currencyMap[$locale] ?? 'TSH'; // Default to TSH as per requirements
    }

    /**
     * Get localized content from language files
     */
    private function getLocalizedContent($locale)
    {
        return trans('landing', [], $locale);
    }

    /**
     * Get pricing data with currency conversion
     */
    private function getPricingData($currency)
    {
        // Base prices in TSH as per requirements
        $basePricing = [
            'starter' => [
                'price' => 49700,
                'messages' => 497,
                'rate' => 100
            ],
            'pro' => [
                'price' => 93700,
                'messages' => 1041,
                'rate' => 90
            ],
            'enterprise' => [
                'price' => 123600,
                'messages' => 1545,
                'rate' => 80
            ],
            'overage_rate' => 75
        ];

        // Simple exchange rates (in production, use real-time API)
        $exchangeRates = [
            'TSH' => 1,
            'USD' => 0.00040,
            'BRL' => 0.0023,
            'INR' => 0.033,
            'NGN' => 0.15,
            'IDR' => 6.2,
            'EUR' => 0.00037
        ];

        $rate = $exchangeRates[$currency] ?? 1;
        
        $convertedPricing = [];
        foreach ($basePricing as $plan => $data) {
            if (is_array($data)) {
                $convertedPricing[$plan] = [
                    'price' => round($data['price'] * $rate, 2),
                    'messages' => $data['messages'],
                    'rate' => round($data['rate'] * $rate, 4)
                ];
            } else {
                $convertedPricing[$plan] = round($data * $rate, 4);
            }
        }

        return [
            'currency' => $currency,
            'symbol' => $this->getCurrencySymbol($currency),
            'plans' => $convertedPricing
        ];
    }

    /**
     * Get currency symbol
     */
    private function getCurrencySymbol($currency)
    {
        $symbols = [
            'TSH' => 'TSh',
            'USD' => '$',
            'BRL' => 'R$',
            'INR' => '₹',
            'NGN' => '₦',
            'IDR' => 'Rp',
            'EUR' => '€'
        ];

        return $symbols[$currency] ?? $currency;
    }

    /**
     * Calculate monthly cost based on message volume
     */
    private function calculateMonthlyCost($monthlyMessages)
    {
        if ($monthlyMessages <= 497) {
            return 49700;
        } elseif ($monthlyMessages <= 1041) {
            return 93700;
        } elseif ($monthlyMessages <= 1545) {
            return 123600;
        } else {
            $overageMessages = $monthlyMessages - 1545;
            return 123600 + ($overageMessages * 75);
        }
    }

    /**
     * Demo response generators for different languages
     */
    private function getEnglishDemoResponses()
    {
        return [
            'hello' => "Hi there! I'm your AI Sales Agent. I help businesses close more deals through intelligent WhatsApp conversations. What can I tell you about my sales capabilities?",
            'price' => "I offer transparent pricing starting from TSh 49,700/month for 497 AI messages. Each message I handle is actual sales work - qualifying leads, answering questions, and closing deals. What's your monthly conversation volume?",
            'help' => "I specialize in complete sales cycles - from lead capture to deal closure. I work 24/7, speak multiple languages, and never miss a follow-up. I can increase your conversion rates by 35% typically. What specific sales challenge are you facing?",
            'default' => "That's a great question! As your AI Sales Agent, I handle everything from lead qualification to price negotiation. I work within your guidelines to close deals professionally. Would you like to see specific examples of how I've helped other businesses?"
        ];
    }

    private function generateDemoResponse($message, $responses)
    {
        $message = strtolower($message);
        
        if (str_contains($message, 'hello') || str_contains($message, 'hi')) {
            return $responses['hello'];
        } elseif (str_contains($message, 'price') || str_contains($message, 'cost')) {
            return $responses['price'];
        } elseif (str_contains($message, 'help') || str_contains($message, 'how')) {
            return $responses['help'];
        } else {
            return $responses['default'];
        }
    }

    private function getSpanishDemoResponses()
    {
        return [
            'hello' => "¡Hola! Soy tu nuevo Agente de Ventas IA. Ayudo a empresas a cerrar más ventas a través de conversaciones inteligentes por WhatsApp. ¿Qué te gustaría saber sobre mis capacidades de ventas?",
            'price' => "Ofrezco precios transparentes desde TSh 49,700/mes por 497 mensajes IA. Cada mensaje que manejo es trabajo real de ventas. ¿Cuál es tu volumen mensual de conversaciones?",
            'help' => "Me especializo en ciclos completos de ventas. Trabajo 24/7, hablo múltiples idiomas y nunca pierdo seguimiento. Típicamente aumento las tasas de conversión en 35%. ¿Qué desafío específico de ventas tienes?",
            'default' => "¡Excelente pregunta! Como tu Agente de Ventas IA, manejo todo desde calificación de leads hasta negociación de precios. ¿Te gustaría ver ejemplos específicos de cómo he ayudado a otras empresas?"
        ];
    }

    private function getPortugueseDemoResponses()
    {
        return [
            'hello' => "Olá! Sou seu novo Agente de Vendas IA. Ajudo empresas a fechar mais negócios através de conversas inteligentes no WhatsApp. O que gostaria de saber sobre minhas capacidades de vendas?",
            'price' => "Ofereço preços transparentes a partir de TSh 49.700/mês por 497 mensagens IA. Cada mensagem que gerencio é trabalho real de vendas. Qual é seu volume mensal de conversas?",
            'help' => "Especializo-me em ciclos completos de vendas. Trabalho 24/7, falo múltiplos idiomas e nunca perco follow-ups. Tipicamente aumento as taxas de conversão em 35%. Que desafio específico de vendas você tem?",
            'default' => "Excelente pergunta! Como seu Agente de Vendas IA, gerencio tudo desde qualificação de leads até negociação de preços. Gostaria de ver exemplos específicos de como ajudei outras empresas?"
        ];
    }

    private function getHindiDemoResponses()
    {
        return [
            'hello' => "नमस्ते! मैं आपका नया AI सेल्स एजेंट हूं। मैं व्यापारों को WhatsApp पर बुद्धिमान बातचीत के जरिए अधिक डील्स बंद करने में मदद करता हूं। मेरी सेल्स क्षमताओं के बारे में क्या जानना चाहेंगे?",
            'price' => "मैं TSh 49,700/महीने से 497 AI संदेशों के लिए पारदर्शी कीमतें देता हूं। हर संदेश जो मैं संभालता हूं वह वास्तविक सेल्स काम है। आपका मासिक बातचीत वॉल्यूम क्या है?",
            'help' => "मैं पूर्ण सेल्स साइकिल्स में विशेषज्ञ हूं। मैं 24/7 काम करता हूं, कई भाषाएं बोलता हूं और कभी फॉलो-अप नहीं चूकता। आमतौर पर मैं कन्वर्जन रेट्स 35% बढ़ाता हूं। आपकी विशिष्ट सेल्स चुनौती क्या है?",
            'default' => "बेहतरीन सवाल! आपके AI सेल्स एजेंट के रूप में, मैं लीड क्वालिफिकेशन से लेकर प्राइस नेगोसिएशन तक सब कुछ संभालता हूं। क्या आप देखना चाहेंगे कि मैंने दूसरे बिजनेसेस की कैसे मदद की है?"
        ];
    }

    private function getArabicDemoResponses()
    {
        return [
            'hello' => "مرحباً! أنا وكيل المبيعات الذكي الجديد. أساعد الشركات في إغلاق المزيد من الصفقات من خلال محادثات ذكية على واتساب. ماذا تود أن تعرف عن قدراتي في المبيعات؟",
            'price' => "أقدم أسعاراً شفافة تبدأ من 49,700 شلن تنزاني شهرياً مقابل 497 رسالة ذكية. كل رسالة أتعامل معها هي عمل مبيعات حقيقي. ما حجم محادثاتك الشهرية؟",
            'help' => "أتخصص في دورات مبيعات كاملة. أعمل 24/7، أتحدث لغات متعددة ولا أفوت أي متابعة أبداً. عادة ما أزيد معدلات التحويل بنسبة 35%. ما التحدي المحدد في المبيعات الذي تواجهه؟",
            'default' => "سؤال ممتاز! كوكيل مبيعات ذكي، أتعامل مع كل شيء من تأهيل العملاء المحتملين إلى التفاوض على الأسعار. هل تود رؤية أمثلة محددة لكيفية مساعدتي للشركات الأخرى؟"
        ];
    }

    private function getFrenchDemoResponses()
    {
        return [
            'hello' => "Salut ! Je suis votre nouvel Agent Commercial IA. J'aide les entreprises à conclure plus d'affaires grâce à des conversations intelligentes sur WhatsApp. Que souhaitez-vous savoir sur mes capacités commerciales ?",
            'price' => "J'offre des prix transparents à partir de 49 700 TSh/mois pour 497 messages IA. Chaque message que je gère est du vrai travail commercial. Quel est votre volume mensuel de conversations ?",
            'help' => "Je me spécialise dans les cycles de vente complets. Je travaille 24h/24 et 7j/7, parle plusieurs langues et ne manque jamais de suivi. J'augmente généralement les taux de conversion de 35%. Quel défi commercial spécifique avez-vous ?",
            'default' => "Excellente question ! En tant qu'Agent Commercial IA, je gère tout, de la qualification des prospects à la négociation des prix. Aimeriez-vous voir des exemples spécifiques de la façon dont j'ai aidé d'autres entreprises ?"
        ];
    }

    /**
     * Ensure content has all required sections with fallbacks
     */
    private function ensureContentFallbacks($content)
    {
        // Set default values for all sections to prevent array access errors
        $content = array_merge([
            'meta' => [
                'title' => 'AI Sales Agent - SafariChat',
                'description' => 'Meet your AI Sales Agent that handles complete sales conversations',
                'keywords' => 'AI sales agent, WhatsApp automation, sales automation'
            ],
            'navigation' => [
                'features' => 'Features',
                'pricing' => 'Pricing',
                'login' => 'Login'
            ],
            'hero' => [
                'title' => 'Hi, I\'m your new AI Sales Agent. I close deals 24/7 while you focus on growing your business.',
                'subtitle' => 'I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals.',
                'cta_primary' => 'Meet Your New Sales Rep',
                'cta_secondary' => 'See How Much I\'ll Earn You',
                'trust_indicators' => 'I\'ve successfully closed deals for 500+ businesses globally. Available 24/7/365.'
            ],
            'track_record' => [
                'title' => 'My Track Record',
                'results' => 'I\'ve helped 500+ organizations increase sales'
            ],
            'problems_solutions' => [
                'title' => 'Problems I Solve → Value I Deliver',
                'problems_title' => 'Your Current Sales Challenges',
                'solutions_title' => 'How I Solve Them Personally',
                'problems' => [],
                'solutions' => []
            ],
            'skills' => [
                'title' => 'My Core Sales Skills'
            ],
            'demo' => [
                'title' => 'See Me In Action',
                'description' => 'Interactive chat where you can talk to me directly',
                'welcome' => 'Hi! I\'m your AI Sales Agent. Ask me anything about how I can help grow your business!'
            ],
            'contact_form' => [
                'title' => 'How to Get Started Working With Me'
            ],
            'pricing' => [
                'header' => 'Simple, transparent pricing — only pay for the AI messages you use.',
                'subheader' => 'Choose a plan based on your monthly message volume. Higher plans include more AI sales messages at a lower cost per message.',
                'footer_note' => 'SafariChat helps you close deals — every AI message is a real sales interaction that moves your customers toward buying.',
                'starter_plan' => 'Starter Plan',
                'pro_plan' => 'Pro Plan',
                'enterprise_plan' => 'Enterprise Plan',
                'per_month' => '/month',
                'includes' => 'Includes',
                'ai_messages' => 'AI messages',
                'effective_rate' => 'Effective rate',
                'per_message' => 'per message',
                'get_started' => 'Get Started',
                'perfect_for' => 'Perfect for',
                'most_popular' => 'Most Popular',
                'best_value' => 'Best Value',
                'overage_rate' => 'Additional messages at'
            ],
            'industries' => [
                'title' => 'Industries Where I Excel',
                'financial' => 'I\'ve helped banks automate loan applications',
                'education' => 'I handle student inquiries expertly',
                'ecommerce' => 'I recommend products and recover sales',
                'professional' => 'I book appointments flawlessly'
            ],
            'footer' => [
                'tagline' => 'Your Personal AI Sales Professional',
                'contact' => 'Contact',
                'enterprise_sales' => 'Enterprise Sales',
                'technical_docs' => 'Technical Documentation',
                'api_documentation' => 'API Documentation',
                'privacy_policy' => 'Privacy Policy',
                'terms_of_service' => 'Terms of Service'
            ]
        ], $content);

        return $content;
    }
}