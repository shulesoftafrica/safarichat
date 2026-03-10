<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Appointments Module Translation Keys - Swahili
    |--------------------------------------------------------------------------
    |
    | Tafsiri za Kiswahili kwa huduma za miadi na ratiba za uhifadhi
    |
    */

    // Page Titles & Descriptions
    'page_title' => 'Miadi',
    'page_subtitle' => 'Simamia miadi iliyopangwa na AI na ratiba za uhifadhi',
    'details_title' => 'Maelezo ya Miadi',
    'details_subtitle' => 'Tazama na simamia taarifa za miadi',

    // Tab Labels
    'tabs' => [
        'appointments' => 'Miadi',
        'booking_calendars' => 'Ratiba za Uhifadhi',
    ],

    // Stats Cards
    'stats' => [
        'upcoming' => 'Zinazokuja',
        'pending' => 'Zinazosubiri',
        'completed' => 'Zilizokamilika',
        'no_show_rate' => 'Kiwango cha Kutokuja',
        'total' => 'Jumla',
    ],

    // Action Buttons
    'actions' => [
        'book_new' => 'Hifadhi Miadi Mpya',
        'view_details' => 'Tazama Maelezo',
        'confirm' => 'Thibitisha',
        'cancel' => 'Ghairi',
        'reset' => 'Rudisha',
        'back_to_list' => 'Rudi kwenye Orodha',
        'clear_filters' => 'Futa Vichungi',
        'book_appointment' => 'Hifadhi Miadi',
        'confirm_appointment' => 'Thibitisha Miadi',
        'cancel_appointment' => 'Ghairi Miadi',
        'close' => 'Funga',
        'reschedule' => 'Panga Upya',
        'mark_completed' => 'Weka Kama Iliyokamilika',
        'mark_no_show' => 'Weka Kama Hakujitokeza',
    ],

    // Filter Section
    'filters' => [
        'status_label' => 'Hali',
        'type_label' => 'Aina',
        'from_date_label' => 'Kutoka Tarehe',
        'to_date_label' => 'Hadi Tarehe',
        'all_statuses' => 'Hali Zote',
        'all_types' => 'Aina Zote',
    ],

    // Status Options
    'status' => [
        'pending' => 'Inasubiri',
        'confirmed' => 'Imethibitishwa',
        'completed' => 'Imekamilika',
        'cancelled' => 'Imeghairiwa',
        'no_show' => 'Hakujitokeza',
        'rescheduled' => 'Imepangwa Upya',
    ],

    // Appointment Types
    'types' => [
        'demo' => 'Maonyesho',
        'consultation' => 'Ushauri',
        'follow_up' => 'Ufuatiliaji',
        'meeting' => 'Mkutano',
        'call' => 'Simu',
        'presentation' => 'Uwasilishaji',
        'training' => 'Mafunzo',
    ],

    // Table Headers
    'table' => [
        'title' => 'Orodha ya Miadi',
        'date_time' => 'Tarehe & Muda',
        'customer' => 'Mteja',
        'type' => 'Aina',
        'duration' => 'Muda',
        'status' => 'Hali',
        'booking_slot' => 'Nafasi ya Uhifadhi',
        'actions' => 'Vitendo',
    ],

    // Booking Slot Status
    'slot' => [
        'reserved' => 'Nafasi Imehifadhiwa',
        'no_slot' => 'Hakuna Nafasi',
        'legacy' => 'Ya Zamani',
        'available' => 'Inapatikana',
        'occupied' => 'Imechukuliwa',
    ],

    // Modals
    'modals' => [
        'confirm' => [
            'title' => 'Thibitisha Miadi',
            'message' => 'Je, una uhakika unataka kuthibitisha miadi hii?',
        ],
        'cancel' => [
            'title' => 'Ghairi Miadi',
            'reason_label' => 'Sababu ya Kughairi',
            'reason_placeholder' => 'Ingiza sababu ya kughairi...',
        ],
        'booking' => [
            'title' => 'Hifadhi Miadi Mpya',
            'note_title' => 'Kumbuka:',
            'note_text' => 'Mfumo utaangalia upatikanaji wa ratiba na kuhifadhi nafasi kiotomatiki.',
        ],
    ],

    // Form Labels
    'form' => [
        'customer_name' => 'Jina la Mteja',
        'customer_name_placeholder' => 'Ingiza jina la mteja',
        'customer_phone' => 'Simu ya Mteja',
        'customer_phone_placeholder' => '+255...',
        'appointment_date' => 'Tarehe ya Miadi',
        'appointment_time' => 'Muda wa Miadi',
        'appointment_type' => 'Aina ya Miadi',
        'select_type' => 'Chagua Aina',
        'duration' => 'Muda (dakika)',
        'title' => 'Kichwa',
        'title_placeholder' => 'Kichwa cha mkutano',
        'description' => 'Maelezo/Vidokezo',
        'description_placeholder' => 'Vidokezo vya ziada...',
        'notes' => 'Vidokezo',
        'location' => 'Mahali',
        'location_placeholder' => 'Mahali pa mkutano au anwani',
        'meeting_link' => 'Kiungo cha Mkutano',
        'meeting_link_placeholder' => 'https://meet.google.com/...',
        'required' => 'Inahitajika',
    ],

    // Duration Options
    'duration' => [
        '15' => 'Dakika 15',
        '30' => 'Dakika 30',
        '45' => 'Dakika 45',
        '60' => 'Dakika 60',
        '90' => 'Dakika 90',
        '120' => 'Dakika 120',
        'minutes' => 'dak :count',
    ],

    // Details Page Sections
    'details' => [
        'date' => 'Tarehe',
        'time' => 'Muda',
        'type' => 'Aina',
        'minutes' => 'dakika',
        'description' => 'Maelezo',
        'location' => 'Mahali',
        'meeting_link' => 'Kiungo cha Mkutano',
        'notes' => 'Vidokezo',
        'customer_info' => 'Taarifa za Mteja',
        'customer_name' => 'Jina la Mteja',
        'phone_number' => 'Namba ya Simu',
        'email' => 'Barua Pepe',
        'lead_status' => 'Hali ya Mteja Matarajiwa',
        'booking_info' => 'Taarifa za Uhifadhi',
        'booking_calendar' => 'Ratiba ya Uhifadhi',
        'time_slot' => 'Nafasi ya Muda',
        'created_at' => 'Iliundwa',
        'updated_at' => 'Ilisasishwa Mwisho',
        'confirmed_at' => 'Ilithibitishwa',
        'cancelled_at' => 'Ilighairiwa',
        'cancellation_reason' => 'Sababu ya Kughairi',
        'booked_by' => 'Ilihifadhiwa na',
        'ai_scheduled' => 'Ilipangwa na AI',
        'manual_booking' => 'Uhifadhi wa Mkono',
    ],

    // Empty States
    'empty' => [
        'no_appointments' => 'Hakuna miadi iliyopatikana',
        'no_customer_info' => 'Hakuna taarifa za mteja zinazopatikana',
        'create_first' => 'Unda miadi yako ya kwanza ili kuanza',
    ],

    // Success Messages
    'success' => [
        'created' => 'Miadi imehifadhiwa kwa mafanikio!',
        'updated' => 'Miadi imesasishwa kwa mafanikio!',
        'confirmed' => 'Miadi imethibitishwa kwa mafanikio!',
        'cancelled' => 'Miadi imeghairiwa kwa mafanikio!',
        'completed' => 'Miadi imewekwa kama iliyokamilika!',
        'no_show' => 'Miadi imewekwa kama hakujitokeza!',
        'deleted' => 'Miadi imefutwa kwa mafanikio!',
    ],

    // Error Messages
    'error' => [
        'not_found' => 'Miadi haijapatikana',
        'already_confirmed' => 'Miadi tayari imethibitishwa',
        'already_cancelled' => 'Miadi tayari imeghairiwa',
        'cannot_confirm' => 'Haiwezi kuthibitisha miadi hii',
        'cannot_cancel' => 'Haiwezi kughairi miadi hii',
        'slot_unavailable' => 'Nafasi ya muda iliyochaguliwa haipatikani',
        'past_date' => 'Haiwezi kuhifadhi miadi katika wakati uliopita',
        'invalid_duration' => 'Muda wa miadi si sahihi',
    ],

    // Validation Messages
    'validation' => [
        'customer_name_required' => 'Jina la mteja linahitajika',
        'customer_phone_required' => 'Simu ya mteja inahitajika',
        'date_required' => 'Tarehe ya miadi inahitajika',
        'time_required' => 'Muda wa miadi unahitajika',
        'type_required' => 'Aina ya miadi inahitajika',
        'invalid_date' => 'Tarehe ya miadi si sahihi',
        'invalid_time' => 'Muda wa miadi si sahihi',
    ],

    // Days of Week
    'days' => [
        'monday' => 'Jumatatu',
        'tuesday' => 'Jumanne',
        'wednesday' => 'Jumatano',
        'thursday' => 'Alhamisi',
        'friday' => 'Ijumaa',
        'saturday' => 'Jumamosi',
        'sunday' => 'Jumapili',
    ],

    // Time-related
    'time' => [
        'minutes_ago' => 'dakika :count zilizopita',
        'hours_ago' => 'saa :count zilizopita',
        'days_ago' => 'siku :count zilizopita',
        'today' => 'Leo',
        'tomorrow' => 'Kesho',
        'yesterday' => 'Jana',
    ],

];
