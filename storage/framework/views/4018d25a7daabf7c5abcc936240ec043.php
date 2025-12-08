<!DOCTYPE html>
<html lang="<?php echo e($currentLocale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo e($content['meta']['title'] ?? 'AI Sales Agent - SafariChat'); ?></title>
    <meta name="description" content="<?php echo e($content['meta']['description'] ?? 'Meet your AI Sales Agent that handles complete sales conversations, qualifies prospects, and closes deals 24/7.'); ?>">
    <meta name="keywords" content="<?php echo e($content['meta']['keywords'] ?? 'AI sales agent, WhatsApp automation, sales automation, lead qualification'); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo e($content['meta']['title'] ?? 'AI Sales Agent - SafariChat'); ?>">
    <meta property="og:description" content="<?php echo e($content['meta']['description'] ?? 'Meet your AI Sales Agent that handles complete sales conversations'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e(request()->url()); ?>">
    
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
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
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
                    <a href="#features" class="text-gray-600 hover:text-primary transition-colors"><?php echo e($content['navigation']['features'] ?? 'Features'); ?></a>
                    <a href="#pricing" class="text-gray-600 hover:text-primary transition-colors"><?php echo e($content['navigation']['pricing'] ?? 'Pricing'); ?></a>
                    <a href="#demo" class="text-gray-600 hover:text-primary transition-colors">Demo</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary transition-colors">Contact</a>
                </nav>
                
                <!-- Language Switcher -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <select id="languageSelector" class="appearance-none bg-white border border-gray-200 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="en" <?php echo e($currentLocale === 'en' ? 'selected' : ''); ?>>🇺🇸 EN</option>
                            <option value="es" <?php echo e($currentLocale === 'es' ? 'selected' : ''); ?>>🇪🇸 ES</option>
                            <option value="pt-br" <?php echo e($currentLocale === 'pt-br' ? 'selected' : ''); ?>>🇧🇷 PT</option>
                            <option value="hi" <?php echo e($currentLocale === 'hi' ? 'selected' : ''); ?>>🇮🇳 HI</option>
                            <option value="ar" <?php echo e($currentLocale === 'ar' ? 'selected' : ''); ?>>🇸🇦 AR</option>
                            <option value="fr" <?php echo e($currentLocale === 'fr' ? 'selected' : ''); ?>>🇫🇷 FR</option>
                        </select>
                    </div>
                    
                    <!-- Login Button -->
                    <a href="<?php echo e(route('login')); ?>" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
                        <?php echo e($content['navigation']['login'] ?? 'Login'); ?>

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
                    <?php echo e(($content['hero']['title'] ?? null) ?: 'Hi, I\'m your new AI Sales Agent. I close deals 24/7 while you focus on growing your business.'); ?>

                </h1>
                <p class="text-xl lg:text-2xl mb-8 opacity-90 max-w-4xl mx-auto leading-relaxed">
                    <?php echo e(($content['hero']['subtitle'] ?? null) ?: 'I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals.'); ?>

                </p>
                
                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <button onclick="scrollToDemo()" class="bg-secondary text-dark px-8 py-4 rounded-lg font-semibold hover:bg-secondary/90 transition-colors text-lg">
                        <?php echo e(($content['hero']['cta_primary'] ?? null) ?: 'Meet Your New Sales Rep'); ?>

                    </button>
                    <button onclick="scrollToROI()" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/10 transition-colors text-lg">
                        <?php echo e(($content['hero']['cta_secondary'] ?? null) ?: 'See How Much I\'ll Earn You'); ?>

                    </button>
                </div>
                
                <!-- Trust Indicators -->
                <p class="text-sm opacity-80 max-w-3xl mx-auto">
                    <?php echo e(($content['hero']['trust_indicators'] ?? null) ?: 'I\'ve successfully closed deals for 500+ businesses globally. Available 24/7/365. Proven results guaranteed.'); ?>

                </p>
            </div>
        </div>
    </section>

    <!-- Track Record Section -->
    <section class="py-16 bg-accent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-dark mb-4"><?php echo e($content['track_record']['title'] ?? 'My Track Record'); ?></h2>
            <p class="text-xl text-gray-600 mb-12"><?php echo e($content['track_record']['results'] ?? 'Proven results across industries'); ?></p>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">500+</div>
                    <div class="text-gray-600">Businesses Served</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">2M+</div>
                    <div class="text-gray-600">Conversations Handled</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="text-3xl font-bold text-primary mb-2">$50M+</div>
                    <div class="text-gray-600">Deals Tracked</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problems & Solutions Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16"><?php echo e($content['problems_solutions']['title'] ?? 'Problems I Solve → Value I Deliver'); ?></h2>
            
            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Problems -->
                <div class="bg-red-50 rounded-lg p-8">
                    <h3 class="text-2xl font-bold text-red-700 mb-6"><?php echo e($content['problems_solutions']['problems_title'] ?? 'Your Current Sales Challenges'); ?></h3>
                    <ul class="space-y-4">
                        <?php $__currentLoopData = $content['problems_solutions']['problems'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $problem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-red-500 mr-3">❌</span>
                            <span class="text-gray-700"><?php echo e($problem); ?></span>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                
                <!-- Solutions -->
                <div class="bg-green-50 rounded-lg p-8">
                    <h3 class="text-2xl font-bold text-green-700 mb-6"><?php echo e($content['problems_solutions']['solutions_title'] ?? 'How I Solve Them Personally'); ?></h3>
                    <ul class="space-y-4">
                        <?php $__currentLoopData = $content['problems_solutions']['solutions'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-3">✅</span>
                            <span class="text-gray-700"><?php echo e($solution); ?></span>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Skills Section -->
    <section id="features" class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16"><?php echo e(($content['skills']['title'] ?? null) ?: 'My Core Sales Skills'); ?></h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if(isset($content['skills']['sales_conversations']) && is_array($content['skills']['sales_conversations'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo e($content['skills']['sales_conversations']['title'] ?? 'Sales Conversations'); ?></h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php $__currentLoopData = ($content['skills']['sales_conversations']['points'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            <?php echo e($point); ?>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if(isset($content['skills']['lead_management']) && is_array($content['skills']['lead_management'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo e($content['skills']['lead_management']['title'] ?? 'Lead Management'); ?></h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php $__currentLoopData = ($content['skills']['lead_management']['points'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            <?php echo e($point); ?>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if(isset($content['skills']['campaigns']) && is_array($content['skills']['campaigns'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo e($content['skills']['campaigns']['title'] ?? 'Campaign Management'); ?></h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php $__currentLoopData = ($content['skills']['campaigns']['points'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            <?php echo e($point); ?>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if(isset($content['skills']['collaboration']) && is_array($content['skills']['collaboration'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo e($content['skills']['collaboration']['title'] ?? 'Team Collaboration'); ?></h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php $__currentLoopData = ($content['skills']['collaboration']['points'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            <?php echo e($point); ?>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if(isset($content['skills']['whatsapp_management']) && is_array($content['skills']['whatsapp_management'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo e($content['skills']['whatsapp_management']['title'] ?? 'WhatsApp Management'); ?></h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php $__currentLoopData = ($content['skills']['whatsapp_management']['points'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start">
                            <span class="text-primary mr-2">•</span>
                            <?php echo e($point); ?>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4"><?php echo e(($content['demo']['title'] ?? null) ?: 'See Me In Action'); ?></h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto"><?php echo e(($content['demo']['description'] ?? null) ?: 'Interactive chat where you can talk to me directly'); ?></p>
            </div>
            
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Chat Header -->
                <div class="bg-primary text-white p-4 flex items-center">
                    <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold">AI Sales Agent</div>
                        <div class="text-sm opacity-75">Online • Responds instantly</div>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div id="chatMessages" class="h-96 overflow-y-auto p-4 bg-gray-50">
                    <div class="chat-bubble-ai">
                        <?php echo e(($content['demo']['welcome'] ?? null) ?: 'Hi! I\'m your AI Sales Agent. Ask me anything about how I can help grow your business!'); ?>

                    </div>
                </div>
                
                <!-- Chat Input -->
                <div class="border-t bg-white p-4">
                    <div class="flex space-x-2">
                        <input type="text" id="chatInput" placeholder="Type your message..." 
                               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <button onclick="sendMessage()" 
                                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                            Send
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
                <h2 class="text-4xl font-bold text-dark mb-4">Calculate How Much Money I'll Make You</h2>
                <p class="text-xl text-gray-600">See exact revenue projections based on your business</p>
            </div>
            
            <div class="max-w-4xl mx-auto grid lg:grid-cols-2 gap-8">
                <!-- Input Form -->
                <div class="bg-white rounded-lg p-8 shadow-sm">
                    <h3 class="text-2xl font-bold mb-6">Your Business Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Team Size</label>
                            <input type="number" id="teamSize" value="3" min="1" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Average Deal Size (<?php echo e($currency); ?>)</label>
                            <input type="number" id="avgDealSize" value="1000" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Leads</label>
                            <input type="number" id="monthlyLeads" value="100" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Conversion Rate (%)</label>
                            <input type="number" id="conversionRate" value="10" min="0" max="100" step="0.1"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button onclick="calculateROI()" 
                                class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                            Calculate My Value
                        </button>
                    </div>
                </div>
                
                <!-- Results Display -->
                <div class="bg-white rounded-lg p-8 shadow-sm">
                    <h3 class="text-2xl font-bold mb-6">Your ROI with AI Sales Agent</h3>
                    <div id="roiResults" class="space-y-4">
                        <div class="text-center text-gray-500 py-8">
                            <p>Enter your details and click "Calculate My Value" to see your personalized ROI projection</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

 

    <!-- Industries Section -->
    <section class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-dark mb-16"><?php echo e($content['industries']['title'] ?? 'Industries Where I Excel'); ?></h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🏦</div>
                    <h3 class="text-lg font-semibold mb-2">Financial Services</h3>
                    <p class="text-gray-600 text-sm"><?php echo e($content['industries']['financial'] ?? 'I\'ve helped banks automate loan applications'); ?></p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🎓</div>
                    <h3 class="text-lg font-semibold mb-2">Education</h3>
                    <p class="text-gray-600 text-sm"><?php echo e($content['industries']['education'] ?? 'I handle student inquiries expertly'); ?></p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">🛒</div>
                    <h3 class="text-lg font-semibold mb-2">E-commerce</h3>
                    <p class="text-gray-600 text-sm"><?php echo e($content['industries']['ecommerce'] ?? 'I recommend products and recover sales'); ?></p>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm text-center">
                    <div class="text-4xl mb-4">💼</div>
                    <h3 class="text-lg font-semibold mb-2">Professional Services</h3>
                    <p class="text-gray-600 text-sm"><?php echo e($content['industries']['professional'] ?? 'I book appointments flawlessly'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-dark mb-4"><?php echo e(($content['contact_form']['title'] ?? null) ?: 'How to Get Started Working With Me'); ?></h2>
                <p class="text-xl text-gray-600">Ready to hire your AI Sales Agent? Let's discuss your needs.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-8">
                <form id="contactForm" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                            <input type="text" name="company" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Name</label>
                            <input type="text" name="name" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                            <select name="industry" required 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Select Industry</option>
                                <option value="financial">Financial Services</option>
                                <option value="education">Education</option>
                                <option value="ecommerce">E-commerce</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="real-estate">Real Estate</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">What's your biggest sales challenge?</label>
                        <textarea name="message" rows="4" required 
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                        Get Started with AI Sales Agent
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
                    <p class="text-gray-300 mb-4"><?php echo e($content['footer']['tagline'] ?? 'Your Personal AI Sales Professional'); ?></p>
                </div>
                
                <!-- Links -->
                <div>
                    <h4 class="font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['contact'] ?? 'Contact'); ?></a></li>
                        <li><a href="#" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['enterprise_sales'] ?? 'Enterprise Sales'); ?></a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['technical_docs'] ?? 'Technical Documentation'); ?></a></li>
                        <li><a href="#" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['api_documentation'] ?? 'API Documentation'); ?></a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="/privacy" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['privacy_policy'] ?? 'Privacy Policy'); ?></a></li>
                        <li><a href="/terms" class="hover:text-secondary transition-colors"><?php echo e($content['footer']['terms_of_service'] ?? 'Terms of Service'); ?></a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Compliance Badges -->
            <?php if(isset($content['compliance'])): ?>
            <div class="border-t border-gray-700 pt-8 mb-8">
                <h4 class="font-semibold mb-4"><?php echo e($content['compliance']['title']); ?></h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php $__currentLoopData = $content['compliance']['badges']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $badge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="bg-gray-700 rounded px-3 py-2 text-xs text-center">
                        ✓ <?php echo e($badge); ?>

                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Copyright -->
            <div class="border-t border-gray-700 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo e(date('Y')); ?> SafariChat. All rights reserved.</p>
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
                const currency = '<?php echo e($currency); ?>';
                const symbol = '<?php echo e($pricingData["symbol"]); ?>';
                
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
                    alert('Thank you! We\'ll be in touch soon.');
                    this.reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
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
</html><?php /**PATH C:\xampp\htdocs\safarichat\resources\views/landing/index.blade.php ENDPATH**/ ?>