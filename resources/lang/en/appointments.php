<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Appointments Module Translation Keys
    |--------------------------------------------------------------------------
    |
    | English translations for appointments and booking calendar features
    | Including form labels, status texts, modal content, and validation messages
    |
    */

    // Page Titles & Descriptions
    'page_title' => 'Appointments',
    'page_subtitle' => 'Manage AI-scheduled appointments and booking calendars',
    'details_title' => 'Appointment Details',
    'details_subtitle' => 'View and manage appointment information',

    // Tab Labels
    'tabs' => [
        'appointments' => 'Appointments',
        'booking_calendars' => 'Booking Calendars',
    ],

    // Stats Cards
    'stats' => [
        'upcoming' => 'Upcoming',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'no_show_rate' => 'No-Show Rate',
        'total' => 'Total',
    ],

    // Action Buttons
    'actions' => [
        'book_new' => 'Book New Appointment',
        'view_details' => 'View Details',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'reset' => 'Reset',
        'back_to_list' => 'Back to List',
        'clear_filters' => 'Clear Filters',
        'book_appointment' => 'Book Appointment',
        'confirm_appointment' => 'Confirm Appointment',
        'cancel_appointment' => 'Cancel Appointment',
        'close' => 'Close',
        'reschedule' => 'Reschedule',
        'mark_completed' => 'Mark as Completed',
        'mark_no_show' => 'Mark as No Show',
    ],

    // Filter Section
    'filters' => [
        'status_label' => 'Status',
        'type_label' => 'Type',
        'from_date_label' => 'From Date',
        'to_date_label' => 'To Date',
        'all_statuses' => 'All Statuses',
        'all_types' => 'All Types',
    ],

    // Status Options
    'status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
        'rescheduled' => 'Rescheduled',
    ],

    // Appointment Types
    'types' => [
        'demo' => 'Demo',
        'consultation' => 'Consultation',
        'follow_up' => 'Follow Up',
        'meeting' => 'Meeting',
        'call' => 'Call',
        'presentation' => 'Presentation',
        'training' => 'Training',
    ],

    // Table Headers
    'table' => [
        'title' => 'Appointments List',
        'date_time' => 'Date & Time',
        'customer' => 'Customer',
        'type' => 'Type',
        'duration' => 'Duration',
        'status' => 'Status',
        'booking_slot' => 'Booking Slot',
        'actions' => 'Actions',
    ],

    // Booking Slot Status
    'slot' => [
        'reserved' => 'Slot Reserved',
        'no_slot' => 'No Slot',
        'legacy' => 'Legacy',
        'available' => 'Available',
        'occupied' => 'Occupied',
    ],

    // Modals
    'modals' => [
        'confirm' => [
            'title' => 'Confirm Appointment',
            'message' => 'Are you sure you want to confirm this appointment?',
        ],
        'cancel' => [
            'title' => 'Cancel Appointment',
            'reason_label' => 'Cancellation Reason',
            'reason_placeholder' => 'Enter reason for cancellation...',
        ],
        'booking' => [
            'title' => 'Book New Appointment',
            'note_title' => 'Note:',
            'note_text' => 'The system will check calendar availability and reserve a booking slot automatically.',
        ],
    ],

    // Form Labels
    'form' => [
        'customer_name' => 'Customer Name',
        'customer_name_placeholder' => 'Enter customer name',
        'customer_phone' => 'Customer Phone',
        'customer_phone_placeholder' => '+255...',
        'appointment_date' => 'Appointment Date',
        'appointment_time' => 'Appointment Time',
        'appointment_type' => 'Appointment Type',
        'select_type' => 'Select Type',
        'duration' => 'Duration (minutes)',
        'title' => 'Title',
        'title_placeholder' => 'Meeting title',
        'description' => 'Description/Notes',
        'description_placeholder' => 'Additional notes...',
        'notes' => 'Notes',
        'location' => 'Location',
        'location_placeholder' => 'Meeting location or address',
        'meeting_link' => 'Meeting Link',
        'meeting_link_placeholder' => 'https://meet.google.com/...',
        'required' => 'Required',
    ],

    // Duration Options
    'duration' => [
        '15' => '15 minutes',
        '30' => '30 minutes',
        '45' => '45 minutes',
        '60' => '60 minutes',
        '90' => '90 minutes',
        '120' => '120 minutes',
        'minutes' => ':count min',
    ],

    // Details Page Sections
    'details' => [
        'date' => 'Date',
        'time' => 'Time',
        'type' => 'Type',
        'minutes' => 'minutes',
        'description' => 'Description',
        'location' => 'Location',
        'meeting_link' => 'Meeting Link',
        'notes' => 'Notes',
        'customer_info' => 'Customer Information',
        'customer_name' => 'Customer Name',
        'phone_number' => 'Phone Number',
        'email' => 'Email',
        'lead_status' => 'Lead Status',
        'booking_info' => 'Booking Information',
        'booking_calendar' => 'Booking Calendar',
        'time_slot' => 'Time Slot',
        'created_at' => 'Created At',
        'updated_at' => 'Last Updated',
        'confirmed_at' => 'Confirmed At',
        'cancelled_at' => 'Cancelled At',
        'cancellation_reason' => 'Cancellation Reason',
        'booked_by' => 'Booked By',
        'ai_scheduled' => 'AI Scheduled',
        'manual_booking' => 'Manual Booking',
    ],

    // Empty States
    'empty' => [
        'no_appointments' => 'No appointments found',
        'no_customer_info' => 'No customer information available',
        'create_first' => 'Create your first appointment to get started',
    ],

    // Success Messages
    'success' => [
        'created' => 'Appointment booked successfully!',
        'updated' => 'Appointment updated successfully!',
        'confirmed' => 'Appointment confirmed successfully!',
        'cancelled' => 'Appointment cancelled successfully!',
        'completed' => 'Appointment marked as completed!',
        'no_show' => 'Appointment marked as no-show!',
        'deleted' => 'Appointment deleted successfully!',
    ],

    // Error Messages
    'error' => [
        'not_found' => 'Appointment not found',
        'already_confirmed' => 'Appointment is already confirmed',
        'already_cancelled' => 'Appointment is already cancelled',
        'cannot_confirm' => 'Cannot confirm this appointment',
        'cannot_cancel' => 'Cannot cancel this appointment',
        'slot_unavailable' => 'Selected time slot is not available',
        'past_date' => 'Cannot book appointment in the past',
        'invalid_duration' => 'Invalid appointment duration',
    ],

    // Validation Messages
    'validation' => [
        'customer_name_required' => 'Customer name is required',
        'customer_phone_required' => 'Customer phone is required',
        'date_required' => 'Appointment date is required',
        'time_required' => 'Appointment time is required',
        'type_required' => 'Appointment type is required',
        'invalid_date' => 'Invalid appointment date',
        'invalid_time' => 'Invalid appointment time',
    ],

    // Days of Week
    'days' => [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ],

    // Time-related
    'time' => [
        'minutes_ago' => ':count minutes ago',
        'hours_ago' => ':count hours ago',
        'days_ago' => ':count days ago',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'yesterday' => 'Yesterday',
    ],

];
