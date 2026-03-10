<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mstari wa Lugha ya Ufuatiliaji wa Hali ya WhatsApp
    |--------------------------------------------------------------------------
    |
    | Mistari ifuatayo ya lugha inatumika katika ukurasa wa kufuatilia hali ya
    | mfano wa WhatsApp kwa ajili ya kufuatilia ujumuishaji wa WaSender na afya ya muunganisho.
    |
    */

    // Kichwa cha Ukurasa
    'page_title' => 'Hali ya Mfano wa WhatsApp',
    'page_subtitle' => 'Fuatilia miunganisho yako ya WhatsApp na ujumuishaji wa WaSender',

    // Vitendo
    'actions' => [
        'refresh' => 'Onyesha Upya',
        'test_wasender' => 'Jaribu WaSender',
        'send' => 'Tuma',
        'try_again' => 'Jaribu Tena',
    ],

    // Takwimu
    'stats' => [
        'total_instances' => 'Jumla ya Mifano',
        'connected' => 'Zilizounganishwa',
        'connecting' => 'Zinazounganisha',
        'errors' => 'Makosa',
    ],

    // Lebo za Hali
    'status' => [
        'connected' => 'IMEUNGANISHWA',
        'connecting' => 'INAUNGANISHA',
        'disconnected' => 'IMETENGANISHWA',
        'error' => 'KOSA',
    ],

    // Hali za Kupakia
    'loading' => [
        'default' => 'Inapakia...',
        'instances' => 'Inapakia mifano...',
    ],

    // Sehemu ya Majaribio
    'test' => [
        'title' => 'Tuma Ujumbe wa Jaribio',
        'select_instance' => 'Chagua Mfano',
        'chat_id_placeholder' => 'Kitambulisho cha Mazungumzo (mfano, 255700000000@c.us)',
        'message_placeholder' => 'Ujumbe wa jaribio',
        'fill_all_fields' => 'Tafadhali jaza sehemu zote',
        'success' => 'Ujumbe wa jaribio umetumwa kwa mafanikio!',
        'failed' => 'Imeshindwa kutuma ujumbe: :message',
        'error' => 'Kosa kutuma ujumbe: :error',
    ],

    // Hali Tupu
    'empty' => [
        'title' => 'Hakuna mifano ya WhatsApp iliyopatikana',
        'description' => 'Nenda kwenye <a href=":url">Ukurasa wa Usanidi</a> kuunganisha WhatsApp yako',
    ],

    // Maelezo ya Mfano
    'instance' => [
        'created' => 'Imeundwa:',
        'last_seen' => 'Ilionekana mwisho:',
        'never' => 'Kamwe',
        'id' => 'Kitambulisho:',
        'webhook_configured' => 'Webhook imesanidiwa',
    ],

    // Arifa na Ujumbe
    'alerts' => [
        'load_failed' => 'Imeshindwa kupakia mifano: :message',
        'load_error' => 'Kosa kupakia mifano: :error',
        'wasender_success' => 'Muunganisho wa WaSender umefanikiwa!',
        'wasender_failed' => 'Muunganisho wa WaSender umeshindwa: :message',
        'wasender_error' => 'Kosa la muunganisho wa WaSender: :error',
    ],

    // Ukurasa wa Kosa
    'error' => [
        'title' => 'Kosa',
    ],
];
