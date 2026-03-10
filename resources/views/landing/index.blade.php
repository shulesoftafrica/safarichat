<!DOCTYPE html>
<html lang="{{ $currentLocale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title>{{ $content['meta']['title'] ?? 'AI Sales Agent - SafariChat' }}</title>
    <meta name="description" content="{{ $content['meta']['description'] ?? 'Meet your AI Sales Agent that handles complete sales conversations, qualifies prospects, and closes deals 24/7.' }}">
    <meta name="keywords" content="{{ $content['meta']['keywords'] ?? 'AI sales agent, WhatsApp automation, sales automation, lead qualification' }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $content['meta']['title'] ?? 'AI Sales Agent - SafariChat' }}">
    <meta property="og:description" content="{{ $content['meta']['description'] ?? 'Meet your AI Sales Agent that handles complete sales conversations' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1F7A8C',
                        secondary: '#FFBB33',
                        accent: '#E5F3F5',
                        dark: '#1A365D',
                        light: '#F8FAFC'
                    },
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1F7A8C 0%, #2C5AA0 100%);
        }
        
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .chat-bubble-user {
            @apply bg-blue-500 text-white rounded-lg p-3 mb-2 ml-auto max-w-xs;
        }
        
        .chat-bubble-ai {
            @apply bg-gray-100 text-gray-800 rounded-lg p-3 mb-2 mr-auto max-w-xs;
        }
        
        .pricing-card {
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(31, 122, 140, 0.15);
        }
        
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="font-inter bg-white">
    
    <!-- Header Navigation -->
    <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="/images/logo.png" alt="SafariChat" class="h-8 w-auto mr-3" onerror="this.style.display='none'">
                    <span class="text-xl font-bold text-primary">SafariChat</span>
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-primary transition-colors">{{ __('landing.navigation.features') }}</a>
                    <a href="#pricing" class="text-gray-600 hover:text-primary transition-colors">{{ __('landing.navigation.pricing') }}</a>
                    <a href="#demo" class="text-gray-600 hover:text-primary transition-colors">{{ __('landing.navigation.demo') }}</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary transition-colors">{{ __('landing.navigation.contact') }}</a>
                </nav>
                
                <!-- Language Switcher -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <select id="languageSelector" class="appearance-none bg-white border border-gray-200 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="en" {{ $currentLocale === 'en' ? 'selected' : '' }}>🇺🇸 EN</option>
                            <option value="es" {{ $currentLocale === 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
                            <option value="pt-br" {{ $currentLocale === 'pt-br' ? 'selected' : '' }}>🇧🇷 PT</option>
                            <option value="hi" {{ $currentLocale === 'hi' ? 'selected' : '' }}>🇮🇳 HI</option>
                            <option value="ar" {{ $currentLocale === 'ar' ? 'selected' : '' }}>🇸🇦 AR</option>
                            <option value="fr" {{ $currentLocale === 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                        </select>
                    </div>
                    
                    <!-- Login Button -->
                    <a href="{{ route('login') }}" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
                        {{ __('landing.navigation.login') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg hero-pattern text-white py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center fade-in">
                <h1 class="text-4xl lg:text-6xl font-bold mb-6 leading-tight">
                    {{ __('landing.hero.title') }}
                </h1>
                <p class="text-xl lg:text-2xl mb-8 opacity-90 max-w-4xl mx-auto leading-relaxed">
                    {{ __('landing.hero.subtitle') }}
                </p>
                
                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <button onclick="scrollToDemo()" class="bg-secondary text-dark px-8 py-4 rounded-lg font-semibold hover:bg-secondary/90 transition-colors text-lg">
                        {{ __('landing.hero.cta_primary') }}
                    </button>
                    <button onclick="scrollToROI()" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/10 transition-colors text-lg">
                        {{ __('landing.hero.cta_secondary') }}
                    </button>
                </div>
                
                <!-- Trust Indicators -->
                <p class="text-sm opacity-80 max-w-3xl mx-auto">
                    {{ __('landing.hero.trust_indicators') }}
                </p>
            </div>
        </div>
    </section>

    <!-- Track Record Section -->
    <section class="py-16 bg-accent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-dark mb-4">{{ __('landing.track_record.title') }}</h2>
            <p class="text-xl text-gray-600 mb-12">{{ __('landing.track_record.results') }}</p>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">{{ __('landing.track_record.stats.businesses.number') }}</div>
                    <div class="text-gray-600">{{ __('landing.track_record.stats.businesses.label') }}</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">{{ __('landing.track_record.stats.conversations.number') }}</div>
                    <div class="text-gray-600">{{ __('landing.track_record.stats.conversations.label') }}</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">{{ __('landing.track_record.stats.deals.number') }}</div>
                    <div class="text-gray-600">{{ __('landing.track_record.stats.deals.label') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problems & Solutions Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16">{{ __('landing.problems_solutions.title') }}</h2>
            
            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Problems -->
                <div class="bg-red-50 rounded-lg p-8">
                    <h3 class="text-2xl font-bold text-red-700 mb-6">{{ __('landing.problems_solutions.problems_title') }}</h3>
                    <ul class="space-y-4">
                        @foreach(__('landing.problems_solutions.problems') as $problem)
                        <li class="flex items-start">
                            <span class="text-red-500 mr-3">❌</span>
                            <span class="text-gray-700">{{ $problem }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                
                <!-- Solutions -->
                <div class="bg-green-50 rounded-lg p-8">
                    <h3 class="text-2xl font-bold text-green-700 mb-6">{{ __('landing.problems_solutions.solutions_title') }}</h3>
                    <ul class="space-y-4">
                        @foreach(__('landing.problems_solutions.solutions') as $solution)
                        <li class="flex items-start">
                            <span class="text-green-500 mr-3">✅</span>
                            <span class="text-gray-700">{{ $solution }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Skills Section -->
    <section id="features" class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16">{{ __('landing.skills.title') }}</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4">{{ __('landing.skills.sales_conversations.title') }}</h3>
                    <ul class="space-y-2 text-gray-600">
                        @foreach(__('landing.skills.sales_conversations.points') as $point)
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4">{{ __('landing.skills.lead_management.title') }}</h3>
                    <ul class="space-y-2 text-gray-600">
                        @foreach(__('landing.skills.lead_management.points') as $point)
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4">{{ __('landing.skills.campaigns.title') }}</h3>
                    <ul class="space-y-2 text-gray-600">
                        @foreach(__('landing.skills.campaigns.points') as $point)
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4">{{ __('landing.skills.collaboration.title') }}</h3>
                    <ul class="space-y-2 text-gray-600">
                        @foreach(__('landing.skills.collaboration.points') as $point)
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4">{{ __('landing.skills.whatsapp_management.title') }}</h3>
                    <ul class="space-y-2 text-gray-600">
                        @foreach(__('landing.skills.whatsapp_management.points') as $point)
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4">{{ __('landing.demo.title') }}</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">{{ __('landing.demo.description') }}</p>
            </div>
            
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Chat Header -->
                <div class="bg-primary text-white p-4 flex items-center">
                    <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold">{{ __('landing.demo.chat_header') }}</div>
                        <div class="text-sm opacity-75">{{ __('landing.demo.chat_status') }}</div>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div id="chatMessages" class="h-96 overflow-y-auto p-4 bg-gray-50">
                    <div class="chat-bubble-ai">
                        {{ __('landing.demo.welcome') }}
                    </div>
                </div>
                
                <!-- Chat Input -->
                <div class="border-t bg-white p-4">
                    <div class="flex space-x-2">
                        <input type="text" id="chatInput" placeholder="{{ __('landing.demo.chat_placeholder') }}" 
                               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <button onclick="sendMessage()" 
                                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                            {{ __('landing.demo.chat_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ROI Calculator Section -->
    <section id="roi" class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4">{{ __('landing.roi_calculator.title') }}</h2>
                <p class="text-xl text-gray-600">{{ __('landing.roi_calculator.description') }}</p>
            </div>
            
            <div class="max-w-4xl mx-auto grid lg:grid-cols-2 gap-8">
                <!-- Input Form -->
                <div class="bg-white rounded-lg p-8 shadow-sm">
                    <h3 class="text-2xl font-bold mb-6">{{ __('landing.roi_calculator.section_title') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.roi_calculator.form.team_size') }}</label>
                            <input type="number" id="teamSize" value="3" min="1" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.roi_calculator.form.deal_size') }} ({{ $currency }})</label>
                            <input type="number" id="avgDealSize" value="1000" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.roi_calculator.form.monthly_leads') }}</label>
                            <input type="number" id="monthlyLeads" value="100" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.roi_calculator.form.conversion_rate') }}</label>
                            <input type="number" id="conversionRate" value="10" min="0" max="100" step="0.1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button onclick="calculateROI()" 
                                class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                            {{ __('landing.roi_calculator.form.calculate_button') }}
                        </button>
                    </div>
                </div>
                
                <!-- Results Display -->
                <div class="bg-white rounded-lg p-8 shadow-sm">
                    <h3 class="text-2xl font-bold mb-6">{{ __('landing.roi_calculator.results_title') }}</h3>
                    <div id="roiResults" class="space-y-4">
                        <div class="text-center text-gray-500 py-8">
                            <p>{{ __('landing.roi_calculator.results_placeholder') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4">{{ __('landing.pricing.header') }}</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">{{ __('landing.pricing.subheader') }}</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Starter Plan -->
                <div class="pricing-card bg-white rounded-lg p-8 border-2 border-gray-200 relative">
                    <h3 class="text-2xl font-bold text-dark mb-2">{{ __('landing.pricing.starter_plan') }}</h3>
                    <div class="text-4xl font-bold text-primary mb-4">
                        {{ $pricingData['symbol'] }}{{ number_format($pricingData['plans']['starter']['price'], 2) }}
                        <span class="text-lg font-normal text-gray-600">{{ __('landing.pricing.per_month') }}</span>
                    </div>
                   
                    <p class="text-gray-600 mb-6">{{ __('landing.pricing.includes') }} {{ number_format($pricingData['plans']['starter']['messages'] ?? 0) }} {{ __('landing.pricing.ai_messages') }}</p>
                    <div class="text-sm text-gray-500 mb-6">
                        {{ __('landing.pricing.effective_rate') }}: {{ $pricingData['symbol'] }}{{ $pricingData['plans']['starter']['rate'] }} {{ __('landing.pricing.per_message') }}
                    </div>
                    
                    <button class="w-full bg-gray-600 text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                        {{ __('landing.pricing.get_started') }}
                    </button>
                    <p class="text-sm text-gray-500 mt-4">{{ __('landing.pricing.perfect_for') }}: Small businesses, startups</p>
                </div>
                
                <!-- Pro Plan -->
                <div class="pricing-card bg-white rounded-lg p-8 border-2 border-primary relative transform scale-105">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-secondary text-dark px-4 py-1 rounded-full text-sm font-semibold">{{ __('landing.pricing.most_popular') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-2">{{ __('landing.pricing.pro_plan') }}</h3>
                    <div class="text-4xl font-bold text-primary mb-4">
                        {{ $pricingData['symbol'] }}{{ number_format($pricingData['plans']['pro']['price'], 2) }}
                        <span class="text-lg font-normal text-gray-600">{{ __('landing.pricing.per_month') }}</span>
                    </div>
                    <p class="text-gray-600 mb-6">{{ __('landing.pricing.includes') }} {{ is_array($pricingData['plans']['pro']['messages']) ? number_format($pricingData['plans']['pro']['messages'][0] ?? 0) : number_format($pricingData['plans']['pro']['messages']) }} {{ __('landing.pricing.ai_messages') }}</p>
                    <p class="text-gray-600 mb-6">{{ __('landing.pricing.includes') }} {{ number_format($pricingData['plans']['pro']['messages'] ?? 0) }} {{ __('landing.pricing.ai_messages') }}</p>
                    <div class="text-sm text-gray-500 mb-6">
                    </div>
                    <button class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                        {{ __('landing.pricing.get_started') }}
                    </button>
                    <p class="text-sm text-gray-500 mt-4">{{ __('landing.pricing.perfect_for') }}: Growing businesses, schools</p>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="pricing-card bg-white rounded-lg p-8 border-2 border-gray-200 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">{{ __('landing.pricing.best_value') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-2">{{ __('landing.pricing.enterprise_plan') }}</h3>
                    <div class="text-4xl font-bold text-primary mb-4">
                        {{ $pricingData['symbol'] }}{{ number_format($pricingData['plans']['enterprise']['price'], 2) }}
                        <span class="text-lg font-normal text-gray-600">{{ __('landing.pricing.per_month') }}</span>
                    </div>
                    <p class="text-gray-600 mb-6">{{ __('landing.pricing.includes') }} {{ is_array($pricingData['plans']['enterprise']['messages']) ? number_format($pricingData['plans']['enterprise']['messages'][0] ?? 0) : number_format($pricingData['plans']['enterprise']['messages']) }} {{ __('landing.pricing.ai_messages') }}</p>
                    <p class="text-gray-600 mb-6">{{ __('landing.pricing.includes') }} {{ number_format($pricingData['plans']['enterprise']['messages'] ?? 0) }} {{ __('landing.pricing.ai_messages') }}</p>
                    <div class="text-sm text-gray-500 mb-6">
                    </div>
                    <button class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                        {{ __('landing.pricing.get_started') }}
                    </button>
                    <p class="text-sm text-gray-500 mt-4">{{ __('landing.pricing.perfect_for') }}: High-volume organizations</p>
                </div>
            </div>
            
            <p class="text-center text-gray-600 mt-8 max-w-3xl mx-auto">
                {{ __('landing.pricing.footer_note') }}
            </p>
            <p class="text-center text-sm text-gray-500 mt-4">
                {{ __('landing.pricing.overage.description') }} {{ $pricingData['symbol'] }}{{ $pricingData['plans']['overage_rate'] }} {{ __('landing.pricing.per_message') }}
            </p>
        </div>
    </section>

    <!-- Industries Section -->
    <section class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16">{{ __('landing.industries.title') }}</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🏦</div>
                    <h3 class="text-lg font-semibold mb-2">Financial Services</h3>
                    <p class="text-gray-600 text-sm">{{ __('landing.industries.financial') }}</p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🎓</div>
                    <h3 class="text-lg font-semibold mb-2">Education</h3>
                    <p class="text-gray-600 text-sm">{{ __('landing.industries.education') }}</p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🛒</div>
                    <h3 class="text-lg font-semibold mb-2">E-commerce</h3>
                    <p class="text-gray-600 text-sm">{{ __('landing.industries.ecommerce') }}</p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">💼</div>
                    <h3 class="text-lg font-semibold mb-2">Professional Services</h3>
                    <p class="text-gray-600 text-sm">{{ __('landing.industries.professional') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4">{{ __('landing.contact_form.title') }}</h2>
                <p class="text-xl text-gray-600">{{ __('landing.contact_form.subtitle') }}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-8">
                <form id="contactForm" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.contact_form.fields.company_name') }}</label>
                            <input type="text" name="company" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.contact_form.fields.your_name') }}</label>
                            <input type="text" name="name" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.contact_form.fields.email') }}</label>
                            <input type="email" name="email" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.contact_form.fields.industry') }}</label>
                            <select name="industry" required 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">{{ __('landing.contact_form.fields.select_industry') }}</option>
                                <option value="financial">{{ __('landing.contact_form.industries.financial') }}</option>
                                <option value="education">{{ __('landing.contact_form.industries.education') }}</option>
                                <option value="ecommerce">{{ __('landing.contact_form.industries.ecommerce') }}</option>
                                <option value="healthcare">{{ __('landing.contact_form.industries.healthcare') }}</option>
                                <option value="real-estate">{{ __('landing.contact_form.industries.real_estate') }}</option>
                                <option value="other">{{ __('landing.contact_form.industries.other') }}</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('landing.contact_form.fields.message') }}</label>
                        <textarea name="message" rows="4" required 
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                        {{ __('landing.contact_form.button_submit') }}
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center mb-4">
                        <span class="text-xl font-bold text-secondary">SafariChat</span>
                    </div>
                    <p class="text-gray-300 mb-4">{{ __('landing.footer.tagline') }}</p>
                </div>
                
                <!-- Links -->
                <div>
                    <h4 class="font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-secondary transition-colors">{{ __('landing.footer.contact') }}</a></li>
                        <li><a href="#" class="hover:text-secondary transition-colors">{{ __('landing.footer.enterprise_sales') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-secondary transition-colors">{{ __('landing.footer.technical_docs') }}</a></li>
                        <li><a href="#" class="hover:text-secondary transition-colors">{{ __('landing.footer.api_documentation') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="/privacy" class="hover:text-secondary transition-colors">{{ __('landing.footer.privacy_policy') }}</a></li>
                        <li><a href="/terms" class="hover:text-secondary transition-colors">{{ __('landing.footer.terms_of_service') }}</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Compliance Badges -->
            <div class="border-t border-gray-700 pt-8 mb-8">
                <h4 class="font-semibold mb-4">{{ __('landing.compliance.title') }}</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach(__('landing.compliance.badges') as $badge)
                    <div class="bg-gray-700 rounded px-3 py-2 text-xs text-center">
                        ✓ {{ $badge }}
                    </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="border-t border-gray-700 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} SafariChat. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Language Switcher
        document.getElementById('languageSelector').addEventListener('change', function() {
            const selectedLang = this.value;
            window.location.href = '/' + selectedLang;
        });

        // Smooth Scrolling
        function scrollToDemo() {
            document.getElementById('demo').scrollIntoView({ behavior: 'smooth' });
        }

        function scrollToROI() {
            document.getElementById('roi').scrollIntoView({ behavior: 'smooth' });
        }

        // Chat Demo
        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const messagesContainer = document.getElementById('chatMessages');
            
            // Add user message
            const userMessage = document.createElement('div');
            userMessage.className = 'chat-bubble-user';
            userMessage.textContent = message;
            messagesContainer.appendChild(userMessage);
            
            // Clear input
            input.value = '';
            
            // Show typing indicator
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'chat-bubble-ai';
            typingIndicator.innerHTML = 'Typing...';
            typingIndicator.id = 'typing';
            messagesContainer.appendChild(typingIndicator);
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Send to backend
            fetch('/demo-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                // Remove typing indicator
                document.getElementById('typing').remove();
                
                // Add AI response
                const aiMessage = document.createElement('div');
                aiMessage.className = 'chat-bubble-ai';
                aiMessage.textContent = data.response;
                messagesContainer.appendChild(aiMessage);
                
                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('typing').remove();
            });
        }

        // Enter key for chat
        document.getElementById('chatInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // ROI Calculator
        function calculateROI() {
            const teamSize = parseInt(document.getElementById('teamSize').value);
            const avgDealSize = parseInt(document.getElementById('avgDealSize').value);
            const monthlyLeads = parseInt(document.getElementById('monthlyLeads').value);
            const conversionRate = parseFloat(document.getElementById('conversionRate').value) / 100;
            
            fetch('/calculate-roi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    team_size: teamSize,
                    avg_deal_size: avgDealSize,
                    monthly_leads: monthlyLeads,
                    conversion_rate: conversionRate
                })
            })
            .then(response => response.json())
            .then(data => {
                const results = data.results;
                const currency = '{{ $currency }}';
                const symbol = '{{ $pricingData["symbol"] }}';
                
                document.getElementById('roiResults').innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-700">${symbol}${results.additional_revenue.toLocaleString()}</div>
                            <div class="text-sm text-green-600">Additional Annual Revenue</div>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-700">${symbol}${results.cost_savings.toLocaleString()}</div>
                            <div class="text-sm text-blue-600">Annual Cost Savings</div>
                        </div>
                        
                        <div class="bg-primary/10 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-primary">${Math.round(results.roi_percentage)}%</div>
                            <div class="text-sm text-primary">ROI Percentage</div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-lg font-bold text-gray-700">${symbol}${results.monthly_ai_cost.toLocaleString()}/month</div>
                            <div class="text-sm text-gray-600">AI Service Cost</div>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg border-2 border-yellow-300">
                            <div class="text-3xl font-bold text-yellow-700">${symbol}${results.net_roi.toLocaleString()}</div>
                            <div class="text-sm text-yellow-600 font-semibold">Net Annual Profit Increase</div>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Contact Form
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/contact-submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('{{ __('landing.contact_form.success_message') }}');
                    this.reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('landing.contact_form.error_message') }}');
            });
        });

        // Fade in animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observe all sections
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>