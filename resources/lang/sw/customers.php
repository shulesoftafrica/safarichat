<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer & Contacts Language Lines (Swahili)
    |--------------------------------------------------------------------------
    |
    | Mistari ya lugha yafuatayo yanatumika katika moduli ya usimamizi wa wateja/wawasiliani
    |
    */

    // Page Title & Navigation
    'page_title' => 'Wateja',
    'page_subtitle' => 'Simamia Orodha ya Wateja',
    'breadcrumb_home' => 'Nyumbani',
    'breadcrumb_category' => 'Kampeni',
    'breadcrumb_customers' => 'Watu',

    // List View
    'list' => [
        'title' => 'Orodha ya Wateja',
        'subtitle' => 'Simamia Orodha ya Wateja',
        'total_contacts' => 'Jumla ya Wawasiliani',
        'no_contacts' => 'Hakuna wateja waliopatikana',
        'showing' => 'Inaonyesha :from hadi :to wa wateja :total',
    ],

    // Actions
    'actions' => [
        'add_new' => 'Ongeza Mwasiliani wa Mteja',
        'upload_excel' => 'Pakia Faili la Excel/CSV',
        'sync_whatsapp' => 'Sawazisha kutoka WhatsApp',
        'sync_google' => 'Sawazisha kutoka Google',
        'export' => 'Hamisha Wateja',
        'import' => 'Leta Wateja',
        'send_message' => 'Tuma Ujumbe',
        'delete_selected' => 'Futa Zilizochaguliwa',
        'clear_selection' => 'Futa Uchaguzi',
        'bulk_actions' => 'Vitendo vya Wingi',
        'filter' => 'Chuja',
        'search' => 'Tafuta wateja...',
        'refresh' => 'Sasisha',        'view_contact' => 'Angalia Mwasiliani',
        'edit' => 'Hariri',
        'delete' => 'Futa',
        'manage_handoff' => 'Simamia Ukabidhaji',
        'unassigned' => 'Haijapewa',    ],

    // Bulk Actions
    'bulk' => [
        'selected' => 'Wawasiliani :count wamechaguliwa',
        'select_all' => 'Chagua Wote',
        'deselect_all' => 'Ondoa Uchaguzi Wote',
        'action_required' => 'Tafadhali chagua kitendo',
        'confirm_delete' => 'Una uhakika unataka kufuta wawasiliani :count?',
        'delete_success' => 'Wawasiliani :count wamefutwa kwa mafanikio',
        'delete_error' => 'Imeshindwa kufuta wawasiliani',
        'send_message_to' => 'Tuma ujumbe kwa wawasiliani :count',
    ],

    // Form Fields
    'fields' => [
        'name' => 'Jina',
        'phone' => 'Namba ya Simu',
        'email' => 'Barua pepe',
        'address' => 'Anwani',
        'city' => 'Jiji',
        'country' => 'Nchi',
        'type' => 'Aina',
        'status' => 'Hali',
        'tags' => 'Lebo',
        'notes' => 'Maelezo',
        'created_at' => 'Tarehe Iliongezwa',
        'updated_at' => 'Ilisasishwa Mwisho',
        'lead_status' => 'Hali ya Msukumizi',
        'handoff_status' => 'Hali ya Kukabidhiwa',
        'assigned_to' => 'Imegawiwa',
        'source' => 'Chanzo',
        'last_contact' => 'Mawasiliano ya Mwisho',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'Ingiza jina la mteja',
        'phone' => 'Ingiza namba ya simu',
        'email' => 'Ingiza anwani ya barua pepe',
        'address' => 'Ingiza anwani',
        'city' => 'Ingiza jiji',
        'country' => 'Chagua nchi',
        'notes' => 'Ongeza maelezo au maoni...',
        'search' => 'Tafuta kwa jina, simu au barua pepe...',
        'tags' => 'Ongeza lebo zilizotengwa na mikato',
    ],

    // Handoff Management
    'handoff' => [
        'title' => 'Usimamizi wa Ukabidhi',
        'all' => 'Wote',
        'ai_handling' => 'AI Inashughulikia',
        'pending_handoff' => 'Inasubiri Ukabidhi',
        'handed_off' => 'Imekabidhi',
        'completed' => 'Imekamilika',
        'urgent' => 'Ya Haraka',
        'status_ai' => 'AI',
        'status_human' => 'Mtu',
        'status_pending' => 'Inasubiri',
        'assign_to_human' => 'Gawa kwa Mtu',
        'return_to_ai' => 'Rudisha kwa AI',
    ],

    // Lead Status
    'lead_status' => [
        'new' => 'Msukumizi Mpya',
        'contacted' => 'Amewasiliana',
        'outreached' => 'Amefikiliwa',
        'replied' => 'Amejibu',
        'engaged' => 'Ameshiriki',
        'qualified' => 'Amestahili',
        'pitched' => 'Pendekezo Limetolewa',
        'demo_scheduled' => 'Onyesho Limepangwa',
        'proposal' => 'Pendekezo Limetumwa',
        'negotiating' => 'Mazungumzo',
        'won' => 'Imefungwa (Ameshinda)',
        'lost' => 'Amepoteza',
        'handed_off' => 'Amekabidiwa',
        'do_not_contact' => 'Usiwasiliane',
        'churned' => 'Ameacha',
        'cold' => 'Msukumizi Baridi',
        'hot' => 'Msukumizi Moto',
        'warm' => 'Msukumizi wa Wastani',
    ],

    // Summary Section
    'summary' => [
        'title' => 'Muhtasari wa Hali ya Msukumizi',
        'total_contacts' => 'Jumla ya Wawasiliani',
        'contact_details' => 'Maelezo ya Mwasiliani',
        'conversation_summary' => 'Muhtasari wa Mazungumzo',
    ],

    // Table Headers
    'table' => [
        'select' => 'Chagua',
        'name' => 'Jina',
        'phone' => 'Simu',
        'email' => 'Barua pepe',
        'status' => 'Hali',
        'lead_status' => 'Hali ya Msukumizi',
        'handoff_status' => 'Ukabidhi',
        'last_message' => 'Ujumbe wa Mwisho',
        'created_at' => 'Iliongezwa',
        'actions' => 'Vitendo',
        'no_data' => 'Hakuna wateja waliopatikana',
        'loading' => 'Inapakia...',
    ],

    // Filters
    'filters' => [
        'all' => 'Wateja Wote',
        'active' => 'Wanaotumika',
        'inactive' => 'Wasiotumika',
        'new' => 'Wapya Mwezi Huu',
        'recent' => 'Walioongezwa Hivi Karibuni',
        'unread' => 'Meseji Zisizosomwa',
        'favorites' => 'Unazopenda',
        'by_status' => 'Kwa Hali',
        'by_source' => 'Kwa Chanzo',
        'by_date' => 'Kwa Kipindi cha Tarehe',
        'clear_filters' => 'Futa Vichujio',
    ],

    // Upload
    'upload' => [
        'title' => 'Pakia Maelezo ya Wateja',
        'subtitle' => 'Ingiza wateja kutoka faili la Excel au CSV',
        'download_sample' => 'Pakua Faili la Mfano',
        'sample_excel' => 'Faili la Mfano la Excel',
        'sample_file_info' => 'Pakua faili letu la mfano kuona muundo unaohitajika',
        'select_file' => 'Bonyeza hapa kupakia faili la Excel au VCF',
        'click_to_upload' => 'Bonyeza hapa kupakia faili la excel',
        'drag_drop' => 'Buruta na uache faili lako hapa au bofya ili kutafuta',
        'supported_formats' => 'Muundo unaotumika',
        'max_file_size' => 'Ukubwa wa juu wa faili: 10MB',
        'uploading' => 'Inapakia...',
        'processing' => 'Inachakata faili...',
        'success' => 'Wateja :count wameingizwa kwa mafanikio',
        'partial_success' => ':success wameingizwa, :failed wameshindwa',
        'error' => 'Imeshindwa kuingiza wateja',
        'invalid_file' => 'Muundo wa faili si sahihi',
        'file_too_large' => 'Faili ni kubwa sana',
        'required_columns' => 'Safu zinazohitajika: Jina, Simu',
        'optional_columns' => 'Safu za hiari: Barua pepe, Anwani, Jiji, Maelezo',
        'vcf_help' => 'Jinsi ya kuhamisha VCF kutoka simu yako?',
        'vcf_instructions' => 'Maelekezo ya Hatua kwa Hatua ya Kuhamisha VCF',
        'vcf_step_1' => 'Fungua programu ya Anwani kwenye simu yako',
        'vcf_step_2' => 'Nenda kwenye Mipangilio au Simamia Anwani',
        'vcf_step_3' => 'Tafuta chaguo la Hamisha na uchague "Hamisha kwenye faili la VCF"',
        'vcf_step_4' => 'Hifadhi faili la VCF kwenye hifadhi ya simu yako',
        'vcf_step_5' => 'Hamisha faili la VCF kwenye kompyuta yako ikiwa inahitajika',
        'vcf_step_6' => 'Bonyeza Pitia na uchague faili la VCF la kupakia',
        'vcf_note' => 'Kumbuka: Hatua za kuhamisha VCF zinaweza kutofautiana kulingana na chapa/muundo wa simu yako',
    ],

    // WhatsApp Sync
    'whatsapp_sync' => [
        'title' => 'Sawazisha Wawasiliani wa WhatsApp',
        'subtitle' => 'Unganisha WhatsApp yako ili kuingiza wawasiliani',
        'description' => 'Ingiza wawasiliani wako wote wa WhatsApp kiotomatiki',
        'start_sync' => 'Anza Usawazishaji',
        'syncing' => 'Inasawazisha wawasiliani, tafadhali subiri...',
        'success' => 'Wawasiliani :count wamesawazishwa kutoka WhatsApp',
        'error' => 'Imeshindwa kusawazisha wawasiliani wa WhatsApp',
        'no_instance' => 'Hakuna akaunti ya WhatsApp iliyounganishwa',
        'connect_first' => 'Tafadhali unganisha WhatsApp kwanza',
        'select_instance' => 'Chagua Akaunti ya WhatsApp',
        'sync_now' => 'Sawazisha Sasa',
        'last_sync' => 'Ilisawazishwa mwisho: :date',
        'never_synced' => 'Haijawahi kusawazishwa',
        'instance_not_connected' => 'Akaunti ya WhatsApp haijaunganishwa. Tafadhali unganisha kwanza.',
        'no_instance_found' => 'Hakuna akaunti ya WhatsApp iliyopatikana. Tafadhali sanidi kwanza.',
        'contacts_synced_successfully' => 'Wawasiliani wamesawazishwa kwa mafanikio',
        'contacts_imported' => 'wawasiliani wameingizwa',
        'failed_to_import' => 'Imeshindwa kuingiza wawasiliani',
        'no_contacts_found' => 'Hakuna wawasiliani waliopatikana kwenye WhatsApp',
        'auth_failed' => 'Uthibitisho umeshindwa. Angalia tokeni ya WAAPI.',
        'instance_not_found' => 'Akaunti haipatikani au haijaunganishwa',
        'method_not_allowed' => 'Njia hairuhusiwi. Tatizo la API endpoint.',
    ],

    // Google Sync
    'google_sync' => [
        'title' => 'Sawazisha Wawasiliani wa Google',
        'subtitle' => 'Ingiza wawasiliani kutoka akaunti yako ya Google',
        'description' => 'Sawazisha wawasiliani kutoka akaunti yako ya Google',
        'secure_process' => 'Tunatumia mchakato salama wa uthibitisho wa OAuth 2.0',
        'sign_in' => 'Ingia kwa Google',
        'sign_in_button' => 'Ingia kwa Google',
        'connecting' => 'Inaunganisha na Google...',
        'syncing' => 'Inasawazisha wawasiliani...',
        'success' => 'Wawasiliani :count wamesawazishwa kutoka Google',
        'error' => 'Imeshindwa kusawazisha wawasiliani wa Google',
        'auth_required' => 'Uthibitisho wa Google unahitajika',
        'secure_oauth' => 'Tunatumia mchakato salama wa OAuth',
        'info' => 'Maelezo ya Usawazishaji wa Wawasiliani wa Google',
        'benefits_title' => 'Unachopaswa kujua',
        'benefits' => [
            'secure_oauth' => 'Uthibitisho salama wa OAuth 2.0',
            'read_only' => 'Ufikiaji wa kusoma tu kwa wawasiliani wako',
            'no_passwords' => 'Hatuhifadhi nenosiri lako kamwe',
            'auto_dedupe' => 'Kuzuia nakala zinazojitokeza kiotomatiki',
        ],
        'initializing' => 'Inaanzisha uthibitisho wa Google...',
        'init_failed' => 'Imeshindwa kuanzisha API ya Google',
        'auth_start_failed' => 'Imeshindwa kuanza uthibitisho wa Google',
        'auth_failed' => 'Uthibitisho wa Google umeshindwa',
        'auth_success' => 'Uthibitisho wa Google umefanikiwa! Inachukua wawasiliani...',
        'fetching' => 'Inachukua wawasiliani kutoka Google...',
        'fetch_failed' => 'Imeshindwa kuchukua wawasiliani wa Google',
        'processing' => 'Inachakata wawasiliani kwa ajili ya kuingiza...',
        'no_contacts' => 'Hakuna wawasiliani waliopatikana kwenye akaunti ya Google',
        'no_phone_contacts' => 'Hakuna wawasiliani wenye namba za simu waliopatikana',
        'import_success' => 'Wawasiliani wa Google wameingizwa kwa mafanikio',
        'import_failed' => 'Imeshindwa kuingiza wawasiliani wa Google',
        'loading_apis' => 'Inapakia APIs za Google...',
    ],

    // Modals
    'modals' => [
        'add_title' => 'Ongeza Mwasiliani wa Mteja',
        'edit_title' => 'Hariri Maelezo ya Mteja',
        'delete_title' => 'Futa Mteja',
        'delete_message' => 'Una uhakika unataka kufuta mteja huyu?',
        'delete_confirm' => 'Ndiyo, Futa',
        'delete_cancel' => 'Ghairi',
        'view_title' => 'Maelezo ya Mteja',
        'close' => 'Funga',
        'save' => 'Hifadhi',
        'save_changes' => 'Hifadhi Mabadiliko',
        'cancel' => 'Ghairi',
    ],

    // Messages
    'messages' => [
        'created' => 'Mteja ameongezwa kwa mafanikio',
        'updated' => 'Mteja amesasishwa kwa mafanikio',
        'deleted' => 'Mteja amefutwa kwa mafanikio',
        'delete_error' => 'Imeshindwa kufuta mteja',
        'not_found' => 'Mteja hajapatikana',
        'duplicate_phone' => 'Mteja mwenye namba hii ya simu tayari yupo',
        'invalid_phone' => 'Muundo wa namba ya simu si sahihi',
        'phone_format' => 'Weka namba ya simu pamoja na nambari ya nchi (mfano +255 712345678)',
        'lead_status_help' => 'Chagua hadhi inayofaa ya uongozi kulingana na hatua ya sasa ya mazungumzo',
        'required_fields' => 'Tafadhali jaza mashamba yote yanayohitajika',
        'no_selection' => 'Tafadhali chagua angalau mteja mmoja',
    ],

    // Status Labels
    'status_labels' => [
        'active' => 'Anatumika',
        'inactive' => 'Hatumiki',
        'blocked' => 'Amezuiwa',
        'pending' => 'Inasubiri',
        'verified' => 'Amethibitishwa',
        'unverified' => 'Hajathibitishwa',
    ],

    // Sort Options
    'sort' => [
        'newest' => 'Mpya Kwanza',
        'oldest' => 'Za Zamani Kwanza',
        'name_asc' => 'Jina (A-Z)',
        'name_desc' => 'Jina (Z-A)',
        'recent_activity' => 'Shughuli za Hivi Karibuni',
        'most_messages' => 'Meseji Nyingi',
    ],

    // Export
    'export' => [
        'title' => 'Hamisha Wateja',
        'format' => 'Chagua Muundo',
        'excel' => 'Excel (.xlsx)',
        'csv' => 'CSV (.csv)',
        'pdf' => 'PDF (.pdf)',
        'all_contacts' => 'Wawasiliani Wote',
        'selected_contacts' => 'Wawasiliani Waliochaguliwa',
        'filtered_contacts' => 'Wawasiliani Waliochujwa',
        'exporting' => 'Inahamisha...',
        'success' => 'Wateja wamehamishwa kwa mafanikio',
        'error' => 'Imeshindwa kuhamisha wateja',
    ],

    // Empty States
    'empty' => [
        'title' => 'Hakuna Wateja Bado',
        'subtitle' => 'Anza kwa kuongeza mteja wako wa kwanza',
        'action' => 'Ongeza Mteja',
        'import_action' => 'Au ingiza kutoka faili',
    ],

];
