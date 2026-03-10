<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reports & Analytics Language Lines (Swahili)
    |--------------------------------------------------------------------------
    |
    | Tafsiri za Kiswahili kwa Dashibodi ya Uchanganuzi wa Biashara ya WhatsApp
    |
    */

    // Page Header
    'buttons' => [
        'export_report' => 'Hamisha Ripoti',
    ],

    // Metrics - Primary KPIs
    'metrics' => [
        'whatsapp_sent' => [
            'label' => 'Ujumbe wa WhatsApp Uliotumwa',
            'total' => 'Jumla ya ujumbe',
        ],
        'responses' => [
            'label' => 'Majibu ya Wateja',
            'rate_suffix' => 'kiwango cha majibu',
        ],
        'conversations' => [
            'label' => 'Mazungumzo Yanayoendelea',
        ],
        'success_rate' => [
            'label' => 'Kiwango cha Mafanikio ya Ujumbe',
            'trend' => 'Mafanikio ya usambazaji',
        ],
        'time' => [
            'this_week' => 'wiki hii',
            'last_30_days' => 'Siku 30 zilizopita',
        ],
    ],

    // Business Impact Insights
    'insights' => [
        'section_title' => 'Mwelekeo wa Athari za Biashara',
        
        'conversations' => [
            'active_this_month' => 'mazungumzo ya wateja yanayoendelea mwezi huu',
            'ready_to_start' => 'Tayari kuanza kushirikiana na wateja kupitia WhatsApp',
        ],
        
        'response' => [
            'excellent_prefix' => 'Kiwango bora cha majibu cha',
            'excellent_suffix' => 'kinaonyesha wateja wanapenda mawasiliano ya WhatsApp',
            'good_prefix' => 'Nzuri',
            'good_suffix' => 'kiwango cha majibu - wateja wanashiriki katika ujumbe wako',
            'general_benefit' => 'WhatsApp kwa kawaida hupata majibu mara 10 zaidi kuliko masoko ya barua pepe',
        ],
        
        'messages_today' => [
            'sent_today' => 'ujumbe uliotumwa leo',
            'ready' => 'Tayari kutuma ujumbe wa papo hapo kwa wateja',
            'read_time_comparison' => 'Ujumbe wa WhatsApp kwa kawaida unasomwa ndani ya dakika 3 dhidi ya saa 6+ kwa barua pepe',
        ],
        
        'cost' => [
            'estimated_cost' => 'Gharama inayokadiriwa ya ujumbe:',
            'cost_comparison' => 'WhatsApp kwa kawaida hugharamia 75% chini kuliko matangazo ya jadi kwa kila mteja aliyefikia',
        ],
        
        'roi' => [
            'excellent_prefix' => 'ROI bora ya',
            'excellent_suffix' => 'WhatsApp inazalisha mapato mazuri',
            'positive_prefix' => 'ROI chanya ya',
            'positive_suffix' => 'uwekezaji wako wa WhatsApp unalipa',
        ],
        
        'contacts' => [
            'total_ready' => 'jumla ya anwani zilizo tayari kwa ujumbe',
            'reached_prefix' => 'Umefikia',
            'reached_suffix' => 'wateja wa pekee kupitia WhatsApp',
            'start_engaging' => 'Anza kushirikiana na anwani zako kujenga uhusiano madhubuti wa wateja',
        ],
    ],

    // Comparison Card
    'comparison' => [
        'section_title' => 'WhatsApp dhidi ya Njia za Kawaida',
        
        'read_rate' => [
            'label' => 'Kiwango cha Kusoma',
            'value' => '98% dhidi ya 20%',
        ],
        'response_rate' => [
            'label' => 'Kiwango cha Majibu',
            'value_suffix' => 'dhidi ya 2%',
        ],
        'cost_per_message' => [
            'label' => 'Gharama kwa Ujumbe',
            'value' => 'TSh 50 dhidi ya TSh 200',
        ],
        'delivery_speed' => [
            'label' => 'Kasi ya Utoaji',
            'value' => 'Papo hapo dhidi ya masaa 24',
        ],
        'customer_preference' => [
            'label' => 'Upendeleo wa Mteja',
            'value_suffix' => 'dhidi ya 2.8/5',
        ],
        'roi' => [
            'label' => 'ROI:',
            'message' => 'Uwekezaji wako wa WhatsApp unazalisha mapato bora!',
        ],
    ],

    // Customer Engagement Performance
    'performance' => [
        'section_title' => 'Utendaji wa Ushiriki wa Wateja',
        
        'success_rate' => [
            'label' => 'Kiwango cha Mafanikio ya Ujumbe',
            'trend' => 'Mafanikio ya usambazaji',
        ],
        'response_rate' => [
            'label' => 'Kiwango cha Majibu ya Wateja',
        ],
        'auto_replies' => [
            'label' => 'Majibu ya Kiotomatiki Yaliyotumwa',
            'trend' => 'Kiotomatiki',
        ],
        'customers_reached' => [
            'label' => 'Wateja Waliofikika',
            'of_total' => 'ya jumla',
            'ready' => 'Tayari kuanza',
        ],
        'rating' => [
            'excellent' => 'Bora kabisa!',
            'good' => 'Nzuri',
            'growing' => 'Inakua',
        ],
    ],

    // Engagement Metrics
    'engagement' => [
        'estimated_leads' => 'Viongozi Wanaokadiriwa',
        'active_instances' => 'Mifano ya WhatsApp Inayofanya Kazi',
        'text_messages' => 'Ujumbe wa Maandishi',
        'media_messages' => 'Ujumbe wa Midia',
        'total_cost' => 'Jumla ya Gharama ya Ujumbe',
    ],

    // Charts
    'charts' => [
        'engagement_trends' => [
            'title' => 'Mwelekeo wa Ushiriki wa Ujumbe',
            'y_axis_label' => 'Ujumbe',
            'series_whatsapp' => 'Ujumbe wa WhatsApp',
            'series_responses' => 'Majibu ya Wateja',
            'tooltip_suffix' => 'ujumbe',
        ],
        'no_data' => 'Hakuna Data',
    ],

    // Growth Recommendations
    'recommendations' => [
        'section_title' => 'Mapendekezo ya Ukuaji',
        
        'immediate' => [
            'title' => '📈 Hatua za Papo Hapo',
            'start_sending_prefix' => 'Anza kutuma ujumbe wa WhatsApp kwa',
            'start_sending_suffix' => 'anwani zako',
            'setup_welcome' => 'Weka ujumbe wa kiotomatiki wa kukaribisha kwa wateja wapya',
            'improve_content_prefix' => 'Boresha maudhui ya ujumbe kuongeza',
            'improve_content_suffix' => 'kiwango cha majibu',
            'personalize' => 'Tuma ujumbe uliobinafsishwa zaidi kwa kutumia majina ya wateja',
            'excellent_prefix' => 'Yako',
            'excellent_suffix' => 'kiwango cha majibu ni bora! Endelea kushirikiana',
            'expand_segments' => 'Fikiria kupanua kwa sehemu zaidi za wateja',
            'setup_auto_replies' => 'Weka majibu ya kiotomatiki kushughulikia maswali ya wateja 24/7',
            'try_media' => 'Jaribu kutuma picha na hati kwa ushiriki bora',
        ],
        
        'growth' => [
            'title' => '💡 Fursa za Ukuaji',
            'excellent_roi_prefix' => 'ROI yako bora ya',
            'excellent_roi_suffix' => 'inaonyesha WhatsApp ina faida sana',
            'increase_budget' => 'Fikiria kuongeza bajeti yako ya ujumbe kwa ukuaji zaidi',
            'positive_roi_prefix' => 'ROI yako',
            'positive_roi_suffix' => 'ni chanya - ongeza ujumbe',
            'measure_conversions' => 'Anza kupima mageuzi kufuatilia ROI yako',
            'nurture_relationships' => 'wateja wanashiriki - lea uhusiano huu',
            'spend_comparison' => 'Wateja wa WhatsApp kwa kawaida hutumia mara 3 zaidi kuliko wateja wa barua pepe',
            'exclusive_promotions' => 'Fikiria kutoa matangazo ya kipekee ya WhatsApp tu',
        ],
    ],

    // Success Score
    'success_score' => [
        'title' => '🎯 Alama Yako ya Mafanikio ya WhatsApp',
        
        'rating' => [
            'excellent' => 'Bora kabisa! Unazidisha uwezo wa WhatsApp',
            'great' => 'Maendeleo mazuri! Maboresho machache zaidi yatapandisha matokeo yako',
            'good' => 'Mwanzo mzuri! Zingatia ushiriki na automation',
            'ready' => 'Tayari kufungua uwezo kamili wa WhatsApp kwa biashara yako',
        ],
    ],

    // Export Report
    'export' => [
        'document_title' => 'Ripoti ya Uchanganuzi wa Biashara ya WhatsApp',
        'generated_label' => 'Iliyoundwa:',
        'performance_summary' => 'MUHTASARI WA UTENDAJI:',
        'messages_sent' => 'Ujumbe Uliotumwa:',
        'customer_responses' => 'Majibu ya Wateja:',
        'response_rate' => 'Kiwango cha Majibu:',
        'active_conversations' => 'Mazungumzo Yanayoendelea:',
        'business_impact' => 'ATHARI ZA BIASHARA:',
        'total_cost' => 'Jumla ya Gharama ya Ujumbe:',
        'estimated_revenue' => 'Mapato Yanayokadiriwa:',
        'roi' => 'ROI:',
        'success_score' => 'Alama ya Mafanikio:',
        'recommendations_header' => 'MAPENDEKEZO:',
        'recommendation_improve_content' => 'Zingatia kuboresha maudhui ya ujumbe na ubinafsishaji',
        'recommendation_scale_up' => 'Kiwango bora cha majibu - fikiria kuongeza',
        'recommendation_strong_roi' => 'ROI imara - ongeza bajeti ya ujumbe kwa ukuaji',
        'recommendation_track_roi' => 'Fuatilia mageuzi kupima ROI vizuri',
        'fact_engagement' => 'WhatsApp hutoa ushiriki mara 10 bora kuliko barua pepe',
        'recommendation_consistency' => 'Endelea kujenga uhusiano wa wateja kupitia ujumbe thabiti',
        'footer' => 'Ripoti iliyotolewa na Jukwaa la Biashara ya WhatsApp la SafariChat',
        'success_message' => "✅ Ripoti imehamishwa kwa mafanikio!\n\nRipoti yako kamili ya uchanganuzi wa biashara ya WhatsApp imepakuliwa.",
    ],

    // Debug/Console Messages
    'debug' => [
        'data_refreshed' => 'Data imeburudishwa kwa kipindi:',
        'analytics_refreshed' => 'Data ya uchanganuzi imeburudishwa...',
        'dashboard_initialized' => 'Dashibodi ya Uchanganuzi wa Biashara ya WhatsApp imeanzishwa kwa mafanikio',
    ],

    // Dialog Messages
    'dialog' => [
        'welcome_first_time' => "Karibu kwenye Uchanganuzi wa Biashara ya WhatsApp ya SafariChat!\n\nUngependa kuanza kutuma ujumbe wako wa kwanza kushirikiana na wateja?",
    ],

    // Celebration Messages
    'celebration' => [
        'exceptional_engagement' => '🎉 Hongera! Ushiriki wako wa WhatsApp ni wa kipekee!',
    ],

];
