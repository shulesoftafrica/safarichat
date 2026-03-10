<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Language Lines (Swahili)
    |--------------------------------------------------------------------------
    */

    // Page Title
    'title' => 'Dashibodi',
    'page_title' => 'Dashibodi - SafariChat',

    // Welcome Section
    'welcome' => [
        'title' => 'Uko tayari kuwasiliana na wateja wako?',
        'subtitle' => 'Una <strong>:contacts</strong> wawasiliani na <strong>:conversations</strong> mazungumzo hai',
        'greeting' => 'Karibu tena, :name!',
        'good_morning' => 'Habari ya asubuhi, :name!',
        'good_afternoon' => 'Habari ya mchana, :name!',
        'good_evening' => 'Habari ya jioni, :name!',
    ],

    // Onboarding Messages
    'onboarding' => [
        'complete_title' => '🎉 Usanidi Umekamilika!',
        'complete_message' => 'Mfumo wako wa Mauzo wa WhatsApp AI uko tayari! Umeunganisha WhatsApp kwa mafanikio, kuongeza bidhaa, na kusanidi wakala wako wa AI. Uko tayari kabisa kuanza kubadilisha wateja kuwa mauzo.',
        'proactive_title' => 'Tayari kwa Mawasiliano ya Kianzilishi!',
        'proactive_message' => 'Unaweza kuanza kuleta wawasiliani na kutuma meseji zilizolengwa. AI yako itashughulikia mazungumzo yote kiotomatiki.',
    ],

    // Instance Selector
    'instance_selector' => [
        'title' => 'Njia za WhatsApp',
        'label' => 'Chagua njia ya WhatsApp ya kusimamia',
        'all_lines' => 'Njia Zote',
        'primary' => 'Kuu',
        'configure' => 'Sanidi',
        'no_instances' => 'Hakuna akaunti za WhatsApp zilizopatikana.',
        'create_new' => 'Unda Akaunti Mpya',
        'current_instance' => 'Akaunti ya Sasa',
        'switch_instance' => 'Badilisha Akaunti',
        'instance_info' => 'Taarifa za Akaunti',
        'phone_number' => 'Simu: :phone',
        'status' => 'Hali: :status',
        'balance' => 'Salio la Ujumbe: :balance',
    ],

    // Header Quick Actions
    'header_actions' => [
        'send_message' => 'Tuma Ujumbe',
        'manage_contacts' => 'Simamia Wawasiliani',
        'view_messages' => 'Angalia Meseji',
    ],

    // Engagement Alert
    'engagement_tip' => [
        'title' => 'Dokezo la Ushirikiano',
        'message' => 'Hujatuma meseji nyingi leo. Shirikiana zaidi na wateja ili kukuza biashara yako!',
        'action' => 'Tuma Meseji',
    ],

    // Metrics
    'metrics' => [
        'subscription_status' => 'Hali ya Usajili',
        'available_credits' => 'Mikopo Inayopatikana',
        'credits_remaining' => 'Mikopo Iliyobaki',
        'whatsapp_contacts' => 'Wawasiliani wa WhatsApp',
        'active_conversations' => 'Mazungumzo Hai',
        'messages_sent' => 'Meseji Zilizotumwa',
        'messages_sent_today' => 'Meseji Zilizotumwa Leo',
        'response_rate' => 'Kiwango cha Majibu',
        'current_package' => 'Kifurushi cha Sasa',
        'manage_subscription' => 'Simamia Usajili',
        'settings_billing' => 'Mipangilio na Malipo',
        
        // Subscription Status Values
        'subscription_active' => 'Hai',
        'subscription_trial' => 'Jaribio',
        'subscription_inactive' => 'Haijaanzishwa',
        'subscription_expired' => 'Imeisha',
        'subscription_cancelled' => 'Imeghairiwa',
        
        // Status Messages
        'all_features_active' => 'Vipengele vyote viko hai',
        'days_left' => 'siku :days zimebaki',
        'reactivate_now' => 'Anzisha tena sasa',
        'upgrade' => 'Boresha',
        'go_to_settings' => 'Nenda kwa mipangilio',
        
        // Trends
        'trend_this_month' => '+12% mwezi huu',
        'trend_last_30_days' => 'Siku 30 zilizopita',
        'trend_today_activity' => 'Shughuli za leo',
        'trend_last_7_days' => 'Siku 7 zilizopita',
        'trend_up' => '↑ :percent% kutoka mwezi uliopita',
        'trend_down' => '↓ :percent% kutoka mwezi uliopita',
        'trend_stable' => 'Hakuna mabadiliko',
        
        // Dynamic counts
        'contact_count' => '{0} Hakuna wawasiliani|{1} mwasiliani :count|[2,*] wawasiliani :count',
        'conversation_count' => '{0} Hakuna mazungumzo|{1} mazungumzo :count|[2,*] mazungumzo :count',
        'message_count' => '{0} Hakuna meseji|{1} ujumbe :count|[2,*] meseji :count',
    ],

    // Quick Actions
    'quick_actions' => [
        'title' => 'Vitendo vya Haraka',
        'compose_message' => 'Tunga Ujumbe',
        'compose_description' => 'Tuma ujumbe kwa wawasiliani au vikundi vyako',
        'add_contact' => 'Ongeza Mwasiliani',
        'add_contact_description' => 'Ongeza mteja mpya kwenye orodha yako ya wawasiliani',
        'create_campaign' => 'Unda Kampeni',
        'create_campaign_description' => 'Zindua kampeni mpya ya mauzo',
        'view_reports' => 'Angalia Ripoti',
        'view_reports_description' => 'Changanulia utendaji wa meseji zako',
        'manage_products' => 'Simamia Bidhaa',
        'manage_products_description' => 'Sasisha katalogi yako ya bidhaa',
        'configure_agent' => 'Sanidi Wakala wa AI',
        'configure_agent_description' => 'Weka wakala wako wa mauzo wa AI',
        'view_contacts' => 'Angalia Wawasiliani',
        'view_messages' => 'Angalia Meseji',
        'settings' => 'Mipangilio',
        'get_help' => 'Pata Msaada',
    ],

    // Action Cards
    'action_cards' => [
        'quick_broadcast' => [
            'title' => 'Matangazo ya Haraka',
            'description' => 'Tuma meseji papo hapo kwa wateja wako wote kuhusu matangazo, masasisho, au mikumbusho.',
            'action' => 'Tuma Sasa',
        ],
        'contact_management' => [
            'title' => 'Usimamizi wa Wawasiliani',
            'description' => 'Simamia wawasiliani wa wateja, leta wapya, na panga hifadhidata ya wateja wako.',
            'action' => 'Simamia Wawasiliani',
        ],
    ],

    // Quick Actions
    'quick_actions' => [
        'title' => 'Vitendo vya Haraka',
        'compose_message' => 'Tunga Ujumbe',
        'compose_description' => 'Tuma ujumbe kwa wawasiliani au vikundi vyako',
        'add_contact' => 'Ongeza Mwasiliani',
        'add_contact_description' => 'Ongeza mteja mpya kwenye orodha yako',
        'create_campaign' => 'Unda Kampeni',
        'create_campaign_description' => 'Zindua kampeni mpya ya mauzo',
        'view_reports' => 'Tazama Ripoti',
        'view_reports_description' => 'Changanua utendaji wako wa ujumbe',
        'manage_products' => 'Simamia Bidhaa',
        'manage_products_description' => 'Sasisha katalogi yako ya bidhaa',
        'configure_agent' => 'Sanidi Wakala wa AI',
        'configure_agent_description' => 'Weka msaidizi wako wa mauzo wa AI',
    ],

    // Alerts
    'alerts' => [
        'low_credits' => 'Mkopo wako wa meseji unaisha. Ongeza mkopo zaidi ili kuendelea kutuma meseji.',
        'no_instance' => 'Bado hujaunda akaunti ya WhatsApp. Unda moja ili kuanza kushirikiana na wateja.',
        'instance_disconnected' => 'Akaunti yako ya WhatsApp haijaunganishwa. Tafadhali unganisha tena ili kuendelea na ujumbe.',
        'subscription_expiring' => 'Usajili wako unaisha katika siku :days. Fanya upya sasa ili kuepuka mkato.',
        'subscription_expired' => 'Usajili wako umeisha. Fanya upya ili kuendelea kutumia vipengele vyote.',
        'trial_ending' => 'Jaribio lako linaisha katika siku :days. Panda daraja kwenda mpango wa malipo ili kuhifadhi vipengele vyote.',
        'verify_whatsapp' => 'Tafadhali thibitisha namba yako ya WhatsApp ili kuanza kutuma meseji.',
        'add_credits' => 'Ongeza Mkopo',
        'create_instance' => 'Unda Akaunti',
        'reconnect' => 'Unganisha Tena Sasa',
        'renew_subscription' => 'Fanya Upya Usajili',
        'upgrade_now' => 'Panda Daraja Sasa',
    ],

    // Recent Activity
    'recent_activity' => [
        'title' => 'Shughuli za Hivi Karibuni',
        'no_activity' => 'Hakuna shughuli za hivi karibuni',
        'view_all_messages' => 'Angalia Meseji Zote',
        'messages_sent_today' => 'Meseji :count zilizotumwa leo',
        'active_conversations_30_days' => 'Mazungumzo :count yanayoendelea',
        'today_activity' => 'Shughuli za leo',
        'last_30_days' => 'Siku 30 zilizopita',
        'message_sent' => 'Ujumbe ulitumwa kwa :contact',
        'contact_added' => 'Mwasiliani mpya ameongezwa: :name',
        'campaign_started' => 'Kampeni imeanza: :name',
        'agent_response' => 'Wakala wa AI alijibu :contact',
        'appointment_booked' => 'Miadi imewekwa na :contact',
        'product_inquiry' => 'Hoja ya bidhaa kutoka :contact',
        'view_all' => 'Tazama Shughuli Zote',
    ],

    // Engagement Stats
    'engagement' => [
        'title' => 'Mwenendo wa Ushirikiano wa Meseji',
        'time_filters' => [
            '7_days' => 'Siku 7',
            '30_days' => 'Siku 30',
            '3_months' => 'Miezi 3',
        ],
        'chart_label_messages' => 'Meseji',
        'this_week' => 'Wiki Hii',
        'this_month' => 'Mwezi Huu',
        'last_30_days' => 'Siku 30 Zilizopita',
        'custom_range' => 'Kipindi Maalum',
        'messages_sent' => 'Meseji Zilizotumwa',
        'messages_delivered' => 'Meseji Zilizofikishwa',
        'messages_read' => 'Meseji Zilizosomwa',
        'replies_received' => 'Majibu Yaliyopokelewa',
        'new_contacts' => 'Wawasiliani Wapya',
        'active_contacts' => 'Wawasiliani Hai',
        'engagement_rate' => 'Kiwango cha Ushirikiano',
        'delivery_rate' => 'Kiwango cha Ufikiaji',
        'read_rate' => 'Kiwango cha Kusoma',
        'response_rate' => 'Kiwango cha Majibu',
    ],

    // Navigation
    'menu' => [
        'dashboard' => 'Dashibodi',
        'customers' => 'Wateja',
        'campaigns' => 'Kampeni za Mauzo',
        'groups' => 'Vikundi',
        'channels' => 'Njia',
        'schedule' => 'Ratiba',
        'products' => 'Bidhaa',
        'agents' => 'Mawakala wa Mauzo',
        'appointments' => 'Miadi',
        'reports' => 'Ripoti',
        'settings' => 'Mpangilio',
    ],

    // Quick Stats Cards
    'stats' => [
        'total_contacts' => 'Jumla ya Wawasiliani',
        'total_groups' => 'Jumla ya Vikundi',
        'total_messages' => 'Jumla ya Meseji',
        'total_campaigns' => 'Jumla ya Kampeni',
        'pending_appointments' => 'Miadi Inayosubiri',
        'active_agents' => 'Mawakala wa AI Wanaotumika',
        'total_products' => 'Jumla ya Bidhaa',
        'this_month' => 'Mwezi Huu',
        'today' => 'Leo',
        'this_week' => 'Wiki Hii',
    ],

];
