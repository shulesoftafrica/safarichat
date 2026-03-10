<?php

return [
    // Page Titles
    'page_title' => 'Messages',
    'compose_title' => 'WhatsApp Message Composer',
    'compose_subtitle' => 'Send personalized WhatsApp messages to your contacts instantly',
    'sent_title' => 'Sent Messages',
    'sent_subtitle' => 'Manage list of messages sent', 
    'schedule_title' => 'Message Schedules',
    'schedule_subtitle' => 'Create a message that you want to be sent later to your users',
    'report_title' => 'Message Reports',
    'report_subtitle' => 'Analytics and insights for your messaging campaigns',
    'channel_title' => 'Message Channels',
    'channel_subtitle' => 'Manage your WhatsApp instances and channels',
    'group_title' => 'WhatsApp Group Management',
    'group_subtitle' => 'Create and manage WhatsApp groups',

    // Actions
    'actions' => [
        'send_now' => 'Send Now',
        'schedule_send' => 'Schedule Send',
        'save_draft' => 'Save Draft',
        'create_schedule' => 'Schedule a Message',
        'view_details' => 'View Details',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'resend' => 'Resend',
        'cancel_schedule' => 'Cancel Schedule',
        'create_group' => 'Create Group',
        'delete_group' => 'Delete Group',
        'add_channel' => 'Add Channel',
        'remove_channel' => 'Remove Channel',
    ],

    // Compose Message Section
    'compose' => [
        'recipient_selection' => 'Who do you want to message?',
        'all_contacts' => 'All Contacts',
        'all_contacts_desc' => 'Send to everyone in your contact list',
        'lead_status' => 'Select Lead Status',
        'lead_status_desc' => 'Choose contacts by their lead status',
        'custom_numbers' => 'Custom Numbers',
        'custom_numbers_desc' => 'Enter specific phone numbers manually',
        'upload_excel' => 'Upload Excel',
        'upload_excel_desc' => 'Upload an Excel file with phone numbers',
        
        // Form Fields
        'select_lead_status' => 'Select Lead Status',
        'choose_lead_status' => 'Choose a lead status...',
        'enter_phone_numbers' => 'Enter Phone Numbers',
        'phone_placeholder' => 'Type phone numbers separated by comma or space...',
        'phone_help' => 'Enter numbers with country code (e.g., +255712345678)',
        'upload_excel_label' => 'Upload Excel File',
        'excel_help' => 'Upload an Excel file (.xls, .xlsx, .csv) with a column containing phone numbers',
        
        // Message Composer
        'your_message' => 'Your Message',
        'message_placeholder' => 'Type your message here... Use #name to personalize with customer name',
        'hashtag_name_desc' => 'Customer\'s full name',
        'attach_files' => 'Attach files',
        'take_photo' => 'Take photo',
        'add_emoji' => 'Add emoji',
        'record_audio' => 'Record audio',
        'send_message' => 'Send message',
        
        // Stats
        'word_count' => 'words',
        'sms_count' => 'SMS',
        'recipient_count' => 'recipients',
        'whatsapp_connected' => 'WhatsApp Connected',
        'whatsapp_disconnected' => 'WhatsApp Disconnected',
    ],

    // Compliance Notice
    'compliance' => [
        'notice_title' => 'WhatsApp Compliance Notice',
        'notice_text' => 'Do NOT use this page for BULK-SMS or mass promotional messages. Only send messages to numbers belonging to people who know you. This tool is strictly for WhatsApp messaging. Using it for bulk SMS can result in account flagging or blocking.',
        'read_more' => 'Read More',
        'modal_title' => 'WhatsApp Messaging Compliance Guidance',
        'modal_intro' => 'Never use this tool for SMS or mass promotional messaging. WhatsApp has strict policies to prevent spam and protect user privacy. Violating these can result in your account being flagged or permanently blocked.',
        'rules' => [
            'opt_in' => 'Only send messages to users who have opted in to receive WhatsApp communications from you.',
            'no_bulk' => 'Do not send unsolicited or bulk promotional messages.',
            'personalize' => 'Personalize your messages and avoid generic mass content.',
            'monitor' => 'Monitor your account for warnings or restrictions from WhatsApp.',
            'review_guide' => 'Review the full compliance guide here:',
        ],
        'failure_warning' => 'Failure to comply may result in your WhatsApp account being flagged, restricted, or banned.',
        'read_terms' => 'Read WhatsApp Terms & Compliance Guide',
        'close' => 'Close',
    ],

    // Sent Messages Table
    'sent' => [
        'table_title' => 'Sent Messages',
        'select_channel' => 'Select Channel',
        'quick_sms' => 'Quick SMS',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Phone',
        'body' => 'Message Body',
        'type' => 'Type',
        'status' => 'Status',
        'actions' => 'Actions',
        'no_messages' => 'No messages sent yet',
    ],

    // Schedule Section
    'schedule' => [
        'title' => 'Title',
        'message' => 'Message',
        'day_date' => 'Day/Date',
        'time' => 'Time',
        'end_date' => 'End Date',
        'send_to' => 'Send To',
        'type' => 'Type',
        'channels' => 'Channels',
        'action' => 'Action',
        'all_users' => 'All Users',
        'specific_segment' => 'Specific Segment',
        'recurring' => 'Recurring',
        'one_time' => 'One Time',
        'no_schedules' => 'No scheduled messages',
    ],

    // Reports Section
    'report' => [
        'overview' => 'Overview',
        'period_selector' => [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'custom' => 'Custom Range',
        ],
        
        // Metrics
        'metrics' => [
            'total_sent' => 'Total Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'replied' => 'Replied',
            'failed' => 'Failed',
            'pending' => 'Pending',
            'delivery_rate' => 'Delivery Rate',
            'read_rate' => 'Read Rate',
            'reply_rate' => 'Reply Rate',
            'engagement_rate' => 'Engagement Rate',
        ],
        
        // Trends
        'trend_up' => 'Increase from last period',
        'trend_down' => 'Decrease from last period',
        'trend_neutral' => 'No change',
        
        // Charts
        'chart_title' => 'Message Analytics',
        'engagement_metrics' => 'Engagement Metrics',
        'message_volume' => 'Message Volume',
        'response_times' => 'Response Times',
        'peak_hours' => 'Peak Messaging Hours',
        
        // Insights
        'insights_title' => 'Key Insights',
        'best_time' => 'Best Time to Send',
        'avg_response_time' => 'Average Response Time',
        'top_contacts' => 'Top Engaged Contacts',
        'improvement_suggestions' => 'Improvement Suggestions',
    ],

    // Channels Section
    'channel' => [
        'instances' => 'WhatsApp Instances',
        'add_instance' => 'Add New Instance',
        'instance_name' => 'Instance Name',
        'phone_number' => 'Phone Number',
        'status' => 'Status',
        'connected' => 'Connected',
        'disconnected' => 'Disconnected',
        'qr_code' => 'QR Code',
        'scan_qr' => 'Scan QR Code',
        'reconnect' => 'Reconnect',
        'delete_instance' => 'Delete Instance',
        'no_instances' => 'No WhatsApp instances configured',
    ],

    // Groups Section
    'group' => [
        'create_new' => 'Create New Group',
        'group_name' => 'Group Name',
        'group_name_placeholder' => 'Enter group name',
        'participants' => 'Participants',
        'add_participants' => 'Add Participants',
        'your_groups' => 'Your Groups',
        'group_id' => 'Group ID',
        'delete_confirm' => 'Are you sure you want to delete this group?',
        'group_created' => 'Group created successfully!',
        'group_deleted' => 'Group deleted successfully!',
        'error_creating' => 'Error creating group',
        'error_deleting' => 'Error deleting group',
        'no_groups' => 'No groups found.',
    ],

    // Validation Messages
    'validation' => [
        'fix_errors' => 'Please fix the following errors:',
        'select_recipient' => 'Please select who you want to message',
        'select_lead_status' => 'Please select a lead status',
        'enter_phone_number' => 'Please enter at least one valid phone number',
        'invalid_phone' => 'Invalid phone number format. Use country code (e.g., +255712345678)',
        'upload_excel' => 'Please upload an Excel file',
        'invalid_file_type' => 'Invalid file type. Only Excel files (.xls, .xlsx, .csv) are allowed',
        'file_too_large' => 'File size too large. Maximum 5MB allowed',
        'message_required' => 'Please enter a message or attach files',
        'message_too_long' => 'Message is too long. Maximum 1000 characters allowed',
        'files_too_many' => 'Too many files attached. Maximum 5 files allowed',
        'file_size_exceeded' => 'Total file size exceeds 8MB limit',
    ],

    // Success Messages
    'success' => [
        'message_sent' => 'Message sent successfully',
        'message_scheduled' => 'Message scheduled successfully',
        'draft_saved' => 'Draft saved successfully',
        'schedule_deleted' => 'Schedule deleted successfully',
        'channel_added' => 'Channel added successfully',
        'channel_deleted' => 'Channel deleted successfully',
    ],

    // Error Messages
    'error' => [
        'send_failed' => 'Failed to send message',
        'schedule_failed' => 'Failed to schedule message',
        'no_whatsapp_instance' => 'No WhatsApp instance connected',
        'invalid_recipients' => 'Invalid recipients selected',
        'connection_error' => 'Connection error. Please try again',
    ],
];
