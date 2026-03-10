<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mstari wa Lugha ya Malipo na Miamala
    |--------------------------------------------------------------------------
    |
    | Mistari ifuatayo ya lugha inatumika katika moduli ya malipo na miamala
    | ikiwa ni pamoja na uchaguzi wa lango la malipo, usimamizi wa pochi, maelekezo
    | ya malipo ya UCN, na uthibitisho wa miamala.
    |
    */

    // Vichwa vya Kurasa
    'page_titles' => [
        'payment' => 'Kamilisha Usasishaji Wako',
        'wallet' => 'Pochi Yangu na Mikopo',
        'success' => 'Malipo Yamefanikiwa!',
        'cancelled' => 'Malipo Yameghairiwa',
        'ucn_instructions' => 'Maelekezo ya Malipo ya UCN (Lipa Namba)',
    ],

    // Njia za Malipo
    'payment_methods' => [
        'choose' => 'Chagua Njia ya Malipo',
        'ucn' => [
            'name' => 'UCN (Lipa Namba)',
            'description' => 'Lipa kwa UCN (Lipa Namba) Kutoka Benki Yoyote au Pesa za Simu',
            'button' => 'Lipa kwa UCN',
            'mobile_money' => 'Pesa za Simu za UCN',
            'bank_transfer' => 'Uhamishaji wa Benki',
            'tanzania_only' => 'Lipa kupitia benki yoyote au pesa za simu (Tanzania Pekee)',
        ],
        'stripe' => [
            'name' => 'Stripe',
            'description' => 'Lipa kwa Usalama kwa Kadi ya Mkopo/Debit',
            'button' => 'Lipa kwa Kadi',
        ],
        'flutterwave' => [
            'name' => 'Flutterwave',
            'description' => 'Lipa kwa Pesa za Simu, Uhamishaji wa Benki na Kadi',
            'button' => 'Lipa kwa Flutterwave',
        ],
    ],

    // Taarifa za Mpango
    'plan' => [
        'label' => 'Mpango',
        'upgrade' => 'Usasishaji wa Mpango',
        'requested_feature' => 'Kipengele kilichoombwa:',
        'full_upgrade' => 'Usasishaji kamili wa mpango',
        'current_plan' => 'Mpango wa Sasa',
        'upgrade_button' => 'Sasisha Mpango',
        'expires' => 'Inaisha:',
        'trial_mode' => 'Hali ya Jaribio',
        'feature' => 'Kipengele:',
    ],

    // Pochi na Mikopo
    'wallet' => [
        'available_credits' => 'Mikopo ya AI Inayopatikana',
        'loading' => 'Inapakia...',
        'active' => 'Inatumika',
        'top_up' => 'Ongeza Pochi Yako',
        'top_up_description' => 'Chagua njia yako unayopendelea ya malipo kuongeza mikopo kwenye pochi yako',
        'top_up_instruction' => 'Tuma kiasi chochote kuongeza pochi yako papo hapo',
        'send_payment_to' => 'Tuma malipo kwa:',
        'copy_number' => 'Nakili Namba',
    ],

    // Kiasi na Sarafu
    'amount' => [
        'label' => 'Kiasi:',
        'per_month' => '/mwezi',
        'currency' => 'TZS',
    ],

    // Namba za Kumbukumbu
    'reference' => [
        'label' => 'Kumbukumbu:',
        'lipa_namba' => 'Kumbukumbu (Lipa Namba):',
        'copy' => 'Nakili Kumbukumbu',
        'keep' => 'Tunza namba yako ya kumbukumbu ya malipo:',
    ],

    // Vipengele
    'features' => [
        'title' => 'Utapata nini:',
    ],

    // Ujumbe wa Mafanikio
    'success' => [
        'title' => 'Malipo Yamefanikiwa!',
        'message' => 'Usasishaji wako kwa Mpango wa :plan umekamilika kwa mafanikio.',
        'features_active' => 'Vipengele vyako vipya sasa viko hai na viko tayari kutumika!',
    ],

    // Ujumbe wa Kughairi
    'cancelled' => [
        'title' => 'Malipo Yameghairiwa',
        'message' => 'Malipo yako yalighairiwa. Hakuna malipo yaliyofanywa kwenye akaunti yako.',
        'try_again' => 'Unaweza kujaribu kusasisha tena wakati wowote utakapokuwa tayari.',
    ],

    // Maelekezo ya Malipo ya UCN
    'ucn_instructions' => [
        // Hatua za Pesa za Simu
        'mobile_steps' => [
            'step1_title' => 'Fungua Menyu ya Malipo ya Programu ya Simu Yako (Mfano *150*01#)',
            'step1_description' => 'Fungua programu yako ya pesa za simu',
            'step2_title' => 'Nenda kwenye Fanya Malipo (TAN-QR)',
            'step2_description' => 'Chagua "Lipa Bili" au "Malipo ya Bili" au "Lipa Kwa Simu"',
            'step3_title' => 'Ingiza Maelezo ya ucn (LIPA NAMBA)',
            'step4_title' => 'Kamilisha Malipo',
            'step4_description' => 'Thibitisha na kamilisha malipo',
        ],
        
        // Hatua za Uhamishaji wa Benki
        'bank_steps' => [
            'step1_title' => 'Ingia kwenye Benki ya Mtandaoni au Programu ya Simu ya Benki Yako',
            'step1_description' => 'Au tembelea Wakala/tawi lolote linalosaidia Malipo ya Lipa Namba (TAN-QR)',
            'step2_title' => 'Hamisha kwa Akaunti ya SafariChat',
            'step3_title' => 'Kamilisha Malipo',
            'step3_description' => 'Thibitisha na kamilisha malipo:',
        ],
        
        // Lebo za Maelezo ya Malipo
        'payment_details' => [
            'biller' => 'Mlipaji:',
            'account_name' => 'Jina la Akaunti:',
            'account_number' => 'Namba ya Akaunti (Lipa Namba):',
            'bank_channel' => 'Benki/Njia:',
            'tan_qr' => 'TAN-QR',
            'safarichat' => 'SafariChat',
        ],
        
        // Vidokezo Muhimu
        'important_notes' => [
            'title' => 'Vidokezo Muhimu:',
            'tanzania_only' => 'UCN (Lipa Namba) inatumika katika Tanzania PEKEE.',
            'cash_deposit' => 'Kama una pesa taslimu, weka kwenye akaunti yako ya Pesa za Simu au akaunti ya benki kabla ya kufanya malipo',
            'bank_support' => 'Pesa zote za Simu zinasaidia Lipa Namba, lakini baadhi ya benki (wachache tu) bado hazisaidii Lipa Namba',
            'auto_activation' => 'Usajili wako utaamilishwa kiotomatiki mara malipo yatakapothibitishwa',
            'support_contact' => 'Wasiliana na usaidizi ikiwa malipo hayataonekana ndani ya masaa 48',
        ],
    ],

    // Vitendo vya Uabiri
    'actions' => [
        'back_to_agents' => 'Rudi kwa Mawakala wa AI',
        'back_to_settings' => 'Rudi kwa Mipangilio',
        'dashboard' => 'Dashibodi',
        'try_another_method' => 'Jaribu Njia Nyingine ya Malipo',
        'complete_payment' => 'Kamilisha na Thibitisha Malipo',
    ],
];
