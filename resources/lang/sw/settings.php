<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Settings Page Translations - Swahili
    |--------------------------------------------------------------------------
    |
    | Tafsiri za ukurasa wa mipangilio na usimamizi wa akaunti ya mtumiaji
    | ikiwa ni pamoja na akaunti za watumiaji, usajili na malipo, na mipangilio ya biashara
    |
    */

    // Breadcrumbs
    'breadcrumb' => [
        'home' => 'Nyumbani',
        'profile' => 'Wasifu',
        'settings' => 'Mipangilio',
    ],

    // Page Headers
    'page_title' => [
        'general_settings' => 'Mipangilio ya Jumla',
    ],

    'page_header' => [
        'list_of_items' => 'Orodha ya vitu vya kuweka',
        'settings_description' => 'Weka thamani sahihi ya mipangilio ili kupata matumizi bora ya mfumo',
    ],

    // Navigation Tabs
    'tabs' => [
        'user_accounts' => 'Akaunti za Watumiaji',
        'subscription_billing' => 'Usajili na Malipo',
        'business_settings' => 'Mipangilio ya Biashara',
    ],

    // User Accounts Section
    'user_accounts' => [
        'title' => 'Simamia Akaunti za Watumiaji',
        'description' => 'Kila akaunti ya mtumiaji inaweza kuingia, na kusimamia shughuli, kuona ripoti na zaidi..',
        'table' => [
            'hash' => '#',
            'name' => 'Jina',
            'email' => 'Barua pepe',
            'phone' => 'Simu',
            'date_registered' => 'Tarehe ya Usajili',
            'action' => 'Kitendo',
        ],
        'action' => [
            'edit' => 'Hariri',
        ],
    ],

    // Subscription & Billing Section
    'subscription' => [
        'title' => 'Usajili na Malipo',
        'description' => 'Simamia usajili wako, angalia matumizi, na shughulikia malipo',
        'current_subscription' => 'Usajili wa Sasa',
        'plan_label' => 'Mpango',
        'status_label' => 'Hali:',
        'started_label' => 'Ilianza:',
        'trial_expires' => 'Jaribio Linaisha:',
        'next_billing' => 'Malipo Yanayofuata:',
        'days_left' => 'siku zimebaki',
        'plan_features' => 'Vipengele vya Mpango',
        'contacts' => 'Anwani',
        'products' => 'Bidhaa',
        'whatsapp_lines' => 'Mistari ya WhatsApp',
        'followups' => 'Ufuatiliaji',
        'yes' => 'Ndio',
        'no' => 'Hapana',
    ],

    // Credits Display
    'credits' => [
        'available_credits' => 'Mikopo ya AI Inayopatikana',
        'conversion_rate' => 'Mikopo 1 = Ishara 4 za AI',
        'top_up_wallet' => 'Jaza Pochi',
    ],

    // Quick Actions
    'quick_actions' => [
        'title' => 'Vitendo vya Haraka',
        'upgrade_plan' => 'Boresha Mpango',
        'billing_history' => 'Historia ya Malipo',
        'reactivate_now' => 'Washa Tena Sasa',
    ],

    // Available Packages
    'packages' => [
        'title' => 'Vifurushi Vinavyopatikana',
        'recommended' => 'INAYOPENDEKEZWA',
        'current' => 'YA SASA',
        'free_trial' => 'Jaribio Bure',
        'per_month' => '/mwezi',
        'description_default' => 'Bora kwa kuanza',
        'line_singular' => 'Mstari',
        'line_plural' => 'Mistari',
        'ai_credits' => 'Mikopo ya AI',
        'current_plan_button' => 'Mpango wa Sasa',
        'upgrade_now' => 'Boresha Sasa',
        'not_available' => 'Haipatikani',
        'select_button' => 'Chagua',
        
        // Specific packages
        'winga' => [
            'name' => 'Winga',
            'price' => '$29/mwezi',
            'features' => 'Anwani 50, bidhaa 3',
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => '$149/mwezi',
            'features' => 'Anwani 150, bidhaa 50',
        ],
        'enterprise' => [
            'name' => 'Kampuni',
            'price' => '$399/mwezi',
            'features' => 'Anwani 300, bidhaa 200',
        ],
    ],

    // Business Settings Section
    'business' => [
        'title' => 'Mipangilio ya Biashara',
        'description' => 'Sanidi taarifa na mapendeleo ya biashara yako.',
        'redirect_message' => 'Mipangilio ya biashara sasa inashughulikiwa kupitia sehemu maalum ya Wasifu wa Biashara.',
        'form' => [
            'name_label' => 'Jina la Biashara',
            'name_placeholder' => 'Jina la Biashara',
            'email_label' => 'Barua pepe ya Biashara',
            'email_placeholder' => 'Barua pepe ya Biashara',
            'phone_label' => 'Simu ya Biashara',
            'phone_placeholder' => 'Simu ya Biashara',
            'description_label' => 'Maelezo ya Biashara',
            'description_placeholder' => 'Eleza biashara yako',
            'website_label' => 'URL ya Tovuti',
            'website_placeholder' => 'https://mfano.com',
            'save_button' => 'Hifadhi Mipangilio ya Biashara',
        ],
    ],

    // Customer Categories Section
    'categories' => [
        'title' => 'Kategoria za Wateja',
        'description' => 'Simamia orodha ya kategoria za Wateja',
        'add_new_button' => 'Ongeza Kategoria Mpya',
        'table' => [
            'hash' => '#',
            'event_name' => 'Jina la Tukio',
            'customer_category' => 'Kategoria ya Mteja',
            'total_customer' => 'Jumla ya Wateja',
            'action' => 'Kitendo',
        ],
        'legacy_category' => 'Kategoria ya Zamani',
        'action' => [
            'edit' => 'Hariri',
            'delete' => 'Futa',
        ],
        'tooltip_cannot_delete' => 'Kuna Wateja katika kategoria hii, Huwezi kufuta. Futa kwanza wateja katika kategoria hii ukitaka kuifuta',
    ],

    // Modals
    'modal' => [
        'category' => [
            'title' => 'Kategoria Mpya',
            'name_label' => 'Jina la Kategoria',
            'name_placeholder' => 'Jina la Kategoria ya Wateja',
            'close_button' => 'Funga',
            'save_button' => 'Hifadhi',
        ],
        'user' => [
            'title' => 'Hariri taarifa zako',
            'name_label' => 'Jina',
            'name_placeholder' => 'Jina',
            'email_label' => 'Barua pepe',
            'email_placeholder' => 'Barua pepe',
            'phone_label' => 'Simu',
            'phone_placeholder' => 'Simu',
            'uuid_label' => 'UUID ya Mtumiaji (kwa ufikiaji wa API)',
            'uuid_help' => 'Tumia UUID hii na nambari yako ya simu kwa uthibitishaji wa API ya CRM',
            'close_button' => 'Funga',
            'save_button' => 'Hifadhi',
        ],
    ],

    // JavaScript Messages
    'js' => [
        'alert' => [
            'contact_sales' => 'Wasiliana na sales@shulesoft.africa kwa bei maalum ya Kampuni',
        ],
        'paywall' => [
            'title' => 'Washa Tena Wakala wako wa Mauzo wa AI',
            'credits_waiting' => 'Mikopo Yako Inasubiri!',
            'credits_available' => 'Bado una mikopo :count inayopatikana.',
            'missed_opportunities' => 'Fursa Zilizokosekana Leo:',
            'choose_package' => 'Chagua Kifurushi Chako:',
            'payment_method' => 'Njia ya Malipo:',
            'lipa_namba_payment' => 'Malipo ya Lipa Namba',
            'qr_generated_after' => 'Msimbo wa QR utatengenezwa baada ya kuchagua kifurushi',
            'lipa_namba_label' => 'Lipa Namba:',
            'international_payment' => 'Malipo ya Kimataifa',
            'secure_payment_stripe' => 'Malipo salama kupitia Stripe',
            'close_button' => 'Funga',
            'check_payment_status' => 'Angalia Hali ya Malipo',
        ],
        'payment' => [
            'initiation_failed' => 'Kuanzisha malipo kumeshindwa:',
            'failed_generic' => 'Imeshindwa kuanzisha malipo. Tafadhali jaribu tena.',
            'scan_qr_instruction' => 'Changanua msimbo wa QR au tumia nambari ya Lipa Namba hapo juu kukamilisha malipo',
            'checking' => 'Inaangalia...',
            'not_received' => 'Malipo bado hayajapokelewa. Tafadhali kamilisha malipo na ujaribu tena.',
            'check_failed' => 'Imeshindwa kuangalia hali ya malipo.',
        ],
        'billing_history' => [
            'title' => 'Historia ya Malipo',
            'loading' => 'Inapakia...',
            'table' => [
                'date' => 'Tarehe',
                'description' => 'Maelezo',
                'amount' => 'Kiasi',
                'status' => 'Hali',
                'actions' => 'Vitendo',
            ],
            'no_history' => 'Hakuna historia ya malipo bado',
            'transactions_appear_here' => 'Shughuli zako za malipo zitaonekana hapa',
            'load_failed' => 'Imeshindwa kupakia historia ya malipo',
        ],
        'credit_topup' => [
            'title' => 'Nunua Mikopo',
            'description' => 'Nunua mikopo ya ziada kwa mazungumzo ya AI',
            'amount_label' => 'Kiasi cha Mikopo',
            'option_100' => 'Mikopo 100 - $25',
            'option_500' => 'Mikopo 500 - $100',
            'option_1000' => 'Mikopo 1000 - $180',
            'option_2000' => 'Mikopo 2000 - $320',
            'conversion_note' => 'Mikopo 1 = Ishara 4 za AI',
            'cancel_button' => 'Ghairi',
            'purchase_button' => 'Nunua Mikopo',
        ],
        'confirm' => [
            'upgrade_package' => 'Boresha hadi kifurushi cha :package kwa $:price/mwezi?',
            'purchase_credits' => 'Nunua mikopo :amount kwa $:price?',
        ],
        'uuid' => [
            'copy_failed' => 'Imeshindwa kunakili UUID. Tafadhali chagua na nakili kwa mkono.',
        ],
    ],

    // Plan Status Badges
    'status' => [
        'active' => 'Inatumika',
        'inactive' => 'Haifanyi kazi',
        'expired' => 'Imeisha',
        'trial' => 'Jaribio',
    ],

    // Plan Types
    'plan' => [
        'trial' => 'Jaribio',
        'starter' => 'Wanzo',
        'pro' => 'Mtaalamu',
        'premium' => 'Bora',
    ],
];
