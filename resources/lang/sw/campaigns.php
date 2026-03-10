<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Campaigns Language Lines - Swahili
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the campaigns module
    |
    */

    // Page Title & Navigation
    'page_title' => 'Kampeni za Mauzo',
    'page_subtitle' => 'Simamia na fuatilia kampeni zako za uuzaji wa WhatsApp mahali pamoja',
    'breadcrumb_home' => 'Nyumbani',
    'breadcrumb_campaigns' => 'Kampeni',

    // Actions
    'actions' => [
        'create_new' => 'Unda Kampeni Mpya',
        'create_first' => 'Unda Kampeni Yako ya Kwanza',
        'view_report' => 'Ripoti',
        'pause' => 'Simamisha Kampeni',
        'resume' => 'Endelea na Kampeni',
        'clone' => 'Nakili Kampeni',
        'delete' => 'Futa Kampeni',
        'filter' => 'Chuja Kampeni',
    ],

    // Table Headers
    'table' => [
        'title' => 'Kampeni Zako',
        'campaign_name' => 'Jina la Kampeni',
        'recipients' => 'Wapokeaji',
        'status' => 'Hali',
        'progress' => 'Maendeleo',
        'metrics' => 'Vipimo',
        'actions' => 'Vitendo',
        'contacts' => 'wawasiliani',
        'sent' => 'zimetumwa',
        'no_data_yet' => 'Hakuna data bado',
    ],

    // Status Labels
    'status' => [
        'all' => 'Hali Zote',
        'completed' => 'Imekamilika',
        'sending' => 'Inatuma',
        'active' => 'Inatumika',
        'scheduled' => 'Imepangwa',
        'paused' => 'Imesimamishwa',
        'failed' => 'Imeshindwa',
        'staging' => 'Inaandaliwa',
        'draft' => 'Rasimu',
    ],

    // Metrics
    'metrics' => [
        'read_rate' => 'Kiwango cha Kusoma',
        'reply_rate' => 'Kiwango cha Kujibu',
        'delivery_rate' => 'Kiwango cha Uwasilishaji',
        'engagement_rate' => 'Kiwango cha Ushiriki',
    ],

    // Empty State
    'empty' => [
        'title' => 'Hakuna Kampeni Bado',
        'subtitle' => 'Unda kampeni yako ya kwanza ya mauzo ili kuanza kuwafikia wateja kupitia WhatsApp',
        'action' => 'Unda Kampeni Yako ya Kwanza',
    ],

    // Campaign Creation
    'create' => [
        'title' => 'Unda Kampeni Mpya',
        'subtitle' => 'Buni na uzindua kampeni yako ya uuzaji wa WhatsApp',
        'save_draft' => 'Hifadhi kama Rasimu',
        'launch' => 'Zindua Kampeni',
        'schedule' => 'Panga Kampeni',
        
        // Form Fields
        'campaign_name' => 'Jina la Kampeni',
        'campaign_name_placeholder' => 'Ingiza jina la kampeni...',
        'campaign_description' => 'Maelezo',
        'campaign_description_placeholder' => 'Maelezo ya hiari...',
        
        // Recipient Selection
        'recipients_title' => 'Chagua Wapokeaji',
        'all_contacts' => 'Wawasiliani Wote',
        'all_contacts_desc' => 'Tuma kwa kila mtu kwenye orodha yako ya mawasiliano',
        'lead_status' => 'Chagua Hali ya Msukumizi',
        'lead_status_desc' => 'Chagua wawasiliani kulingana na hali yao ya msukumizi',
        'custom_numbers' => 'Nambari Maalum',
        'custom_numbers_desc' => 'Ingiza nambari za simu kwa mkono',
        'upload_excel' => 'Pakia Excel',
        'upload_excel_desc' => 'Pakia faili ya Excel yenye nambari za simu',
        'lead_status_placeholder' => 'Chagua hali ya msukumizi...',
        'enter_phone_numbers' => 'Ingiza Nambari za Simu',
        'contact_input_placeholder' => 'Andika nambari za simu zilizotengwa kwa mkato au nafasi...',
        'phone_help_text' => 'Ingiza nambari pamoja na msimbo wa nchi (mfano, +255712345678)',
        'excel_help_text' => 'Pakia faili ya Excel (.xls, .xlsx, .csv) yenye safu wizi yenye jina (hiari) kama name, na nambari ya simu kama phone (Lazima).',
        'hashtag_name_desc' => 'Jina kamili la mteja',
        
        // Message Composer
        'message_title' => 'Tunga Ujumbe',
        'message_placeholder' => 'Andika ujumbe wako hapa... Tumia #name kwa hashtag jina la mteja',
        'attach_files' => 'Ambatanisha faili',
        'take_photo' => 'Piga picha',
        'add_emoji' => 'Ongeza emoji',
        'record_audio' => 'Rekodi sauti',
        'send_message' => 'Tuma ujumbe',
        
        // AI Personalization
        'ai_personalization' => 'Ubinafsishaji wa AI',
        'ai_enabled' => 'Washa Ubinafsishaji wa Ujumbe Unaotumia AI',
        'ai_benefits_title' => 'Ubinafsishaji wa AI Unafanya Nini',
        'ai_benefits' => [
            'analyzes_history' => 'Inachambua historia ya mazungumzo kuelewa muktadha',
            'detects_language' => 'Inagundua lugha na mtindo wa mapendeleo kiotomatiki',
            'personalizes_message' => 'Inabinafsisha kila ujumbe kulingana na hatua ya uhusiano',
            'schedules_times' => 'Inapanga nyakati bora za kutuma kwa kila mpokeaji',
            'filters_sentiment' => 'Inachuja hisia hasi kwa ukaguzi wa binadamu',
            'increases_engagement' => 'Inaongeza ushiriki mara 2-3 ikilinganishwa na ujumbe wa jumla',
        ],
        'ai_how_it_works' => 'Jinsi inavyofanya kazi: Ujumbe wako wa kiolezo unachambuliwa na kubinafsishwa kwa kila mwasiliani kulingana na historia yao ya mazungumzo, maslahi, na mtindo wa mawasiliano—kufanya kila ujumbe uhisiwe wa kibinafsi na husika.',
        
        // Message Stats
        'word_count' => 'maneno',
        'sms_count' => 'SMS',
        'recipient_count' => 'wapokeaji',
        'whatsapp_connected' => 'WhatsApp Imeunganishwa',
        'whatsapp_disconnected' => 'WhatsApp Haijaunganishwa',
    ],

    // Validation & Errors
    'validation' => [
        'fix_errors' => 'Tafadhali rekebisha makosa yafuatayo:',
        'name_required' => 'Jina la kampeni linahitajika',
        'message_required' => 'Ujumbe unahitajika',
        'recipients_required' => 'Angalau mpokeaji mmoja anahitajika',
        'phone_invalid' => 'Muundo wa nambari ya simu si sahihi',
        'excel_invalid' => 'Muundo wa faili ya Excel si sahihi',
    ],

    // Messages
    'messages' => [
        'created' => 'Kampeni imeundwa',
        'updated' => 'Kampeni imesasishwa',
        'deleted' => 'Kampeni imefutwa',
        'paused' => 'Kampeni imesimamishwa',
        'resumed' => 'Kampeni imeendelea',
        'cloned' => 'Kampeni imenakiliwa',
        'launched' => 'Kampeni imezinduliwa',
        'scheduled' => 'Kampeni imepangwa',
        'processing' => 'Tafadhali subiri tunapochakata ombi lako',
        'delete_confirm' => 'Je, una uhakika unataka kufuta kampeni hii?',
    ],

    // Reports
    'report' => [
        'title' => 'Ripoti ya Kampeni',
        'subtitle' => 'Uchanganuzi wa kina na vipimo vya utendaji',
        'overview' => 'Muhtasari',
        'performance' => 'Vipimo vya Utendaji',
        'recipients_breakdown' => 'Mgawanyiko wa Wapokeaji',
        'engagement' => 'Uchanganuzi wa Ushiriki',
        'timeline' => 'Ratiba ya Kampeni',
        'export' => 'Hamisha Ripoti',
        
        // Header
        'created' => 'Imeundwa',
        'at' => 'saa',
        'completed' => 'Imekamilika',
        'back_to_campaigns' => 'Rudi kwa Kampeni',
        
        // Metrics
        'messages_sent' => 'Ujumbe Uliotumwa',
        'of_total' => 'ya jumla',
        'delivered' => 'Zimewasilishwa',
        'delivery_rate' => 'kiwango cha uwasilishaji',
        'read' => 'Zimesomwa',
        'read_rate' => 'kiwango cha kusoma',
        'replied' => 'Zimejibiwa',
        'reply_rate' => 'kiwango cha majibu',
        'failed' => 'Zimeshindwa',
        'failure_rate' => 'kiwango cha kushindwa',
        'credits_spent' => 'Mikopo Iliyotumika',
        'per_message' => 'kwa ujumbe',
        
        // Sentiment Analysis
        'reply_sentiment_analysis' => 'Uchanganuzi wa Hisia za Majibu',
        'positive_replies' => 'Majibu Mazuri',
        'neutral_replies' => 'Majibu ya Wastani',
        'negative_replies' => 'Majibu Mabaya',
        
        // Message Recipients Table
        'message_recipients' => 'Wapokeaji wa Ujumbe',
        'all_statuses' => 'Hali Zote',
        
        // Table Headers
        'contact' => 'Mwasiliani',
        'phone' => 'Simu',
        'status' => 'Hali',
        'sent_at' => 'Ilitumwa',
        'delivered_at' => 'Iliwasilishwa',
        'read_at' => 'Ilisomwa',
        'reply' => 'Jibu',
        'actions' => 'Vitendo',
        
        // Table Content
        'unknown' => 'Haijulikani',
        'replied_badge' => 'Amejibu',
        'view_contact' => 'Angalia Mwasiliani',
        'no_messages_found' => 'Hakuna ujumbe uliopatikana',
        
        // Campaign Actions
        'campaign_actions' => 'Vitendo vya Kampeni',
        'clone_campaign' => 'Nakili Kampeni Hii',
        'pause_campaign' => 'Simamisha Kampeni',
        'resume_campaign' => 'Endelea na Kampeni',
        
        // Stats (legacy compatibility)
        'total_sent' => 'Jumla Zilizotumwa',
        'pending' => 'Zinasubiri',
    ],

];
