<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerts & Notifications Language Lines (Swahili)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various alerts, notifications,
    | and toast messages throughout the application.
    |
    */

    // Success Alerts
    'success' => [
        'operation_successful' => 'Mchakato umekamilika kwa mafanikio!',
        'contact_created' => 'Mwasiliani ameundwa kwa mafanikio!',
        'contact_updated' => 'Mwasiliani amebadilishwa kwa mafanikio!',
        'contact_deleted' => 'Mwasiliani amefutwa kwa mafanikio!',
        'campaign_created' => 'Kampeni imeundwa kwa mafanikio!',
        'campaign_updated' => 'Kampeni imebadilishwa kwa mafanikio!',
        'campaign_sent' => 'Kampeni imetumwa kwa mafanikio!',
        'campaign_scheduled' => 'Kampeni imepangwa kwa mafanikio!',
        'product_created' => 'Bidhaa imeundwa kwa mafanikio!',
        'product_updated' => 'Bidhaa imebadilishwa kwa mafanikio!',
        'product_deleted' => 'Bidhaa imefutwa kwa mafanikio!',
        'agent_created' => 'Wakala wa mauzo ameundwa kwa mafanikio!',
        'agent_updated' => 'Wakala wa mauzo amebadilishwa kwa mafanikio!',
        'agent_deleted' => 'Wakala wa mauzo amefutwa kwa mafanikio!',
        'appointment_created' => 'Miadi imeundwa kwa mafanikio!',
        'appointment_updated' => 'Miadi imebadilishwa kwa mafanikio!',
        'appointment_cancelled' => 'Miadi imeghairiwa kwa mafanikio!',
        'message_sent' => 'Ujumbe umetumwa kwa mafanikio!',
        'file_uploaded' => 'Faili imepakiwa kwa mafanikio!',
        'file_deleted' => 'Faili imefutwa kwa mafanikio!',
        'settings_saved' => 'Mipangilio imehifadhiwa kwa mafanikio!',
        'password_changed' => 'Nenosiri limebadilishwa kwa mafanikio!',
        'profile_updated' => 'Wasifu umebadilishwa kwa mafanikio!',
        'whatsapp_connected' => 'WhatsApp imeunganishwa kwa mafanikio!',
        'whatsapp_disconnected' => 'WhatsApp imetenganishwa kwa mafanikio!',
        'data_imported' => 'Data imeletwa kwa mafanikio!',
        'data_exported' => 'Data imetolewa kwa mafanikio!',
    ],

    // Error Alerts
    'error' => [
        'operation_failed' => 'Mchakato umeshindikana. Tafadhali jaribu tena.',
        'something_went_wrong' => 'Kuna tatizo limetokea. Tafadhali jaribu tena.',
        'contact_not_found' => 'Mwasiliani hakupatikana.',
        'campaign_not_found' => 'Kampeni haikupatikana.',
        'product_not_found' => 'Bidhaa haikupatikana.',
        'agent_not_found' => 'Wakala wa mauzo hakupatikana.',
        'appointment_not_found' => 'Miadi haikupatikana.',
        'message_failed' => 'Ujumbe haujatumwa. Tafadhali jaribu tena.',
        'file_upload_failed' => 'Upakiaji wa faili umeshindikana. Tafadhali jaribu tena.',
        'file_too_large' => 'Faili ni kubwa sana. Ukubwa wa juu ni :size.',
        'invalid_file_type' => 'Aina ya faili sio sahihi. Aina zinazoruhusiwa: :types.',
        'validation_error' => 'Tafadhali angalia fomu kwa makosa.',
        'unauthorized' => 'Huna ruhusa ya kufanya kitendo hiki.',
        'session_expired' => 'Kipindi chako kimeisha. Tafadhali ingia tena.',
        'network_error' => 'Hitilafu ya mtandao. Tafadhali angalia muunganisho wako.',
        'server_error' => 'Hitilafu ya seva. Tafadhali wasiliana na msaada.',
        'whatsapp_connection_failed' => 'Kushindwa kuunganisha WhatsApp. Tafadhali jaribu tena.',
        'whatsapp_not_connected' => 'WhatsApp haijaunganishwa. Tafadhali unganisha kwanza.',
        'insufficient_credits' => 'Mikopo haitoshi. Tafadhali ongeza mikopo.',
        'duplicate_entry' => 'Ingizo hili tayari lipo.',
        'required_fields' => 'Tafadhali jaza sehemu zote zinazohitajika.',
    ],

    // Warning Alerts
    'warning' => [
        'unsaved_changes' => 'Una mabadiliko ambayo hayajahifadhiwa. Una uhakika unataka kuondoka?',
        'low_credits' => 'Mikopo yako inakaribia kuisha. Fikiria kuongeza.',
        'trial_ending_soon' => 'Kipindi chako cha majaribio kinaisha katika siku :days.',
        'subscription_expired' => 'Usajili wako umeisha.',
        'whatsapp_disconnected' => 'WhatsApp imetenganishwa. Tafadhali unganisha tena.',
        'no_products_defined' => 'Hakuna bidhaa zilizofafanuliwa. Tafadhali ongeza bidhaa kwanza.',
        'no_contacts' => 'Huna wawasiliani. Leta au ongeza wawasiliani kwanza.',
        'campaign_no_recipients' => 'Hakuna wapokeaji waliochaguliwa kwa kampeni hii.',
        'confirm_delete' => 'Una uhakika unataka kufuta hii? Kitendo hiki hakiwezi kufutwa.',
        'confirm_cancel' => 'Una uhakika unataka kughairi?',
        'large_file_warning' => 'Faili kubwa zinaweza kuchukua muda mrefu kupakia.',
        'bulk_operation_warning' => 'Hii itaathiri vitu :count. Endelea?',
    ],

    // Info Alerts
    'info' => [
        'welcome_back' => 'Karibu tena, :name!',
        'first_time_setup' => 'Kamilisha usanidi wako ili uanze.',
        'new_feature' => 'Kipengele kipya kinapatikana! Kiangalie.',
        'update_available' => 'Sasisho jipya linapatikana.',
        'maintenance_scheduled' => 'Matengenezo yaliyopangwa tarehe :date.',
        'no_data_yet' => 'Hakuna data ya kuonyesha bado. Anza kwa kuongeza vitu.',
        'processing_request' => 'Inachakata ombi lako...',
        'campaign_queued' => 'Kampeni imewekwa foleni kwa ajili ya kutuma.',
        'import_in_progress' => 'Uingizaji unaendelea. Hii inaweza kuchukua dakika chache.',
        'export_ready' => 'Utoaji wako uko tayari kwa upakuzi.',
        'whatsapp_qr_scan' => 'Skana msimbo wa QR kwa WhatsApp yako ili kuunganisha.',
        'rate_limit' => 'Unatuma ujumbe haraka sana. Tafadhali subiri.',
    ],

    // Confirmation Messages
    'confirm' => [
        'delete_contact' => 'Una uhakika unataka kufuta mwasiliani huyu?',
        'delete_contacts' => 'Una uhakika unataka kufuta wawasiliani :count?',
        'delete_campaign' => 'Una uhakika unataka kufuta kampeni hii?',
        'delete_product' => 'Una uhakika unataka kufuta bidhaa hii?',
        'delete_agent' => 'Una uhakika unataka kufuta wakala huyu wa mauzo?',
        'cancel_appointment' => 'Una uhakika unataka kughairi miadi hii?',
        'send_campaign' => 'Una uhakika unataka kutuma kampeni hii kwa wapokeaji :count?',
        'disconnect_whatsapp' => 'Una uhakika unataka kutenganisha WhatsApp?',
        'archive_item' => 'Una uhakika unataka kuweka kitu hiki katika kumbukumbu?',
        'restore_item' => 'Una uhakika unataka kurejesha kitu hiki?',
        'irreversible_action' => 'Kitendo hiki hakiwezi kufutwa!',
    ],

    // Toast Notifications
    'toast' => [
        'copied' => 'Imenakiliwa kwenye ubao wa kunakili!',
        'saved' => 'Imehifadhiwa!',
        'deleted' => 'Imefutwa!',
        'updated' => 'Imebadilishwa!',
        'sent' => 'Imetumwa!',
        'loading' => 'Inapakia...',
        'please_wait' => 'Tafadhali subiri...',
        'processing' => 'Inachakata...',
    ],

    // Form Validation Alerts
    'validation' => [
        'required' => 'Sehemu hii inahitajika.',
        'email' => 'Tafadhali ingiza anwani sahihi ya barua pepe.',
        'phone' => 'Tafadhali ingiza nambari sahihi ya simu.',
        'url' => 'Tafadhali ingiza URL sahihi.',
        'min_length' => 'Lazima iwe na angalau herufi :min.',
        'max_length' => 'Haiwezi kuzidi herufi :max.',
        'numeric' => 'Tafadhali ingiza nambari.',
        'alpha' => 'Tafadhali ingiza herufi tu.',
        'alphanumeric' => 'Tafadhali ingiza herufi na nambari tu.',
        'match_password' => 'Nenosiri hazifanani.',
        'invalid_date' => 'Tafadhali ingiza tarehe sahihi.',
        'future_date' => 'Tarehe lazima iwe katika siku zijazo.',
        'past_date' => 'Tarehe lazima iwe katika siku zilizopita.',
    ],

    // System Notifications
    'system' => [
        'backup_complete' => 'Nakala rudufu ya mfumo imekamilika kwa mafanikio.',
        'backup_failed' => 'Nakala rudufu ya mfumo imeshindikana.',
        'maintenance_mode' => 'Mfumo uko katika hali ya matengenezo.',
        'update_available' => 'Sasisho la mfumo linapatikana.',
        'cache_cleared' => 'Akiba imefutwa kwa mafanikio.',
        'logs_cleared' => 'Kumbukumbu zimefutwa kwa mafanikio.',
    ],

];
