<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Module Language Lines (Swahili)
    |--------------------------------------------------------------------------
    |
    | Tafsiri za Kiswahili kwa Dashibodi ya Msimamizi na Kuingia
    |
    */

    // Login Page
    'login' => [
        'page_title' => 'Kuingia kwa Msimamizi wa SafariChat',
        'brand_name' => 'SafariChat',
        'subtitle' => 'Ufikiaji wa Dashibodi ya Msimamizi',
        'username_label' => 'Jina la Mtumiaji',
        'password_label' => 'Neno la Siri',
        'login_button' => 'Ingia kwenye Dashibodi',
        'footer_text' => 'Paneli ya Msimamizi wa SafariChat',
        'default_credentials' => 'Chaguo-msingi:',
    ],

    // Dashboard Header
    'dashboard' => [
        'page_title' => 'Dashibodi ya Msimamizi wa SafariChat',
        'brand_header' => '🦁 Dashibodi ya Msimamizi wa SafariChat',
        'logout_link' => 'Toka',
    ],

    // Sidebar Navigation
    'nav' => [
        'overview' => 'Muhtasari wa Mfumo',
        'users' => 'Usimamizi wa Watumiaji',
        'subscriptions' => 'Usajili',
        'billing' => 'Malipo na Ankara',
        'whatsapp' => 'Mifano ya WhatsApp',
        'health' => 'Afya ya Mfumo',
        'settings' => 'Mipangilio ya Kimataifa',
    ],

    // Overview Section
    'overview' => [
        'section_title' => 'Muhtasari wa Mfumo',
        
        'stats' => [
            'total_customers' => 'Jumla ya Wateja',
            'active_users' => 'Watumiaji Wanaotumika',
            'total_subscriptions' => 'Jumla ya Usajili',
            'active_subscriptions' => 'Usajili Unaotumika',
        ],
        
        'revenue' => [
            'section_title' => 'Takwimu za Mapato',
            'total_collections' => 'Jumla ya Ukusanyaji',
            'this_month' => 'Mwezi Huu',
            'pending_payments' => 'Malipo Yanayosubiri',
        ],
        
        'activity' => [
            'section_title' => 'Shughuli za Hivi Karibuni',
            'customer' => 'Mteja',
            'action' => 'Kitendo',
            'date' => 'Tarehe',
            'no_activity' => 'Hakuna shughuli za hivi karibuni',
        ],
    ],

    // User Management Section
    'users' => [
        'section_title' => 'Usimamizi wa Watumiaji',
        'search_placeholder' => 'Tafuta watumiaji...',
        
        'table' => [
            'id' => 'Kitambulisho',
            'name' => 'Jina',
            'email' => 'Barua pepe',
            'phone' => 'Simu',
            'subscription' => 'Usajili',
            'status' => 'Hali',
            'registered' => 'Iliyosajiliwa',
            'actions' => 'Vitendo',
        ],
        
        'actions' => [
            'view' => 'Tazama',
            'edit' => 'Hariri',
            'suspend' => 'Simamisha',
            'activate' => 'Amilisha',
        ],
        
        'status' => [
            'active' => 'Inatumika',
            'inactive' => 'Haifanyi kazi',
            'suspended' => 'Imesimamishwa',
        ],
    ],

    // Subscriptions Section
    'subscriptions' => [
        'section_title' => 'Usimamizi wa Usajili',
        
        'summary' => [
            'trial' => 'Watumiaji wa Jaribio',
            'winga' => 'Mpango wa Winga',
            'pro' => 'Mpango wa Pro',
            'enterprise' => 'Mpango wa Biashara',
        ],
        
        'table' => [
            'customer' => 'Mteja',
            'plan' => 'Mpango',
            'status' => 'Hali',
            'started' => 'Ilianzishwa',
            'expires' => 'Inaisha',
            'actions' => 'Vitendo',
        ],
    ],

    // Billing & Payments Section
    'billing' => [
        'section_title' => 'Malipo na Miamala ya Ankara',
        
        'stats' => [
            'total_revenue' => 'Jumla ya Mapato',
            'this_month' => 'Mwezi Huu',
            'pending' => 'Inasubiri',
            'failed' => 'Imeshindwa',
        ],
        
        'table' => [
            'transaction_id' => 'Kitambulisho cha Muamala',
            'customer' => 'Mteja',
            'amount' => 'Kiasi',
            'method' => 'Njia',
            'status' => 'Hali',
            'date' => 'Tarehe',
        ],
        
        'status' => [
            'success' => 'Mafanikio',
            'pending' => 'Inasubiri',
            'failed' => 'Imeshindwa',
        ],
    ],

    // WhatsApp Instances Section
    'whatsapp' => [
        'section_title' => 'Usimamizi wa Mifano ya WhatsApp',
        
        'stats' => [
            'total_instances' => 'Jumla ya Mifano',
            'active' => 'Inayotumika',
            'disconnected' => 'Imekatishwa',
        ],
        
        'table' => [
            'instance_id' => 'Kitambulisho cha Mfano',
            'customer' => 'Mteja',
            'phone' => 'Nambari ya Simu',
            'status' => 'Hali',
            'created' => 'Iliyoundwa',
            'last_active' => 'Mwisho Inatumika',
            'actions' => 'Vitendo',
        ],
        
        'status' => [
            'connected' => 'Imeunganishwa',
            'disconnected' => 'Imekatishwa',
            'pending' => 'Inasubiri',
        ],
        
        'actions' => [
            'view_logs' => 'Tazama Kumbukumbu',
            'disconnect' => 'Katisha',
            'delete' => 'Futa',
        ],
    ],

    // System Health Section
    'health' => [
        'section_title' => 'Afya ya Mfumo na Ufuatiliaji',
        
        'metrics' => [
            'api_status' => 'Hali ya API',
            'database_status' => 'Hali ya Hifadhidata',
            'queue_status' => 'Hali ya Foleni',
            'storage_usage' => 'Matumizi ya Hifadhi',
        ],
        
        'status' => [
            'healthy' => 'Mzima',
            'warning' => 'Onyo',
            'critical' => 'Muhimu',
            'operational' => 'Inafanya kazi',
            'down' => 'Imeanguka',
        ],
        
        'logs' => [
            'title' => 'Kumbukumbu za Mfumo za Hivi Karibuni',
            'timestamp' => 'Muda',
            'level' => 'Kiwango',
            'message' => 'Ujumbe',
        ],
    ],

    // Global Settings Section
    'settings' => [
        'section_title' => 'Mipangilio ya Kimataifa',
        
        'general' => [
            'title' => 'Mipangilio ya Jumla',
            'app_name' => 'Jina la Programu',
            'app_url' => 'URL ya Programu',
            'timezone' => 'Eneo la Muda la Chaguo-msingi',
            'currency' => 'Sarafu ya Chaguo-msingi',
        ],
        
        'payment' => [
            'title' => 'Mipangilio ya Malipo',
            'ucn_enabled' => 'Malipo ya UCN Yamewezeshwa',
            'stripe_enabled' => 'Malipo ya Stripe Yamewezeshwa',
            'test_mode' => 'Hali ya Majaribio',
        ],
        
        'whatsapp' => [
            'title' => 'Usanidi wa WhatsApp',
            'api_endpoint' => 'Mwisho wa API',
            'max_instances' => 'Mifano ya Juu Zaidi kwa Mtumiaji',
        ],
        
        'notifications' => [
            'title' => 'Mipangilio ya Arifa',
            'email_notifications' => 'Arifa za Barua pepe',
            'sms_notifications' => 'Arifa za SMS',
        ],
        
        'buttons' => [
            'save' => 'Hifadhi Mipangilio',
            'reset' => 'Rejesha kwa Chaguo-msingi',
        ],
    ],

];
