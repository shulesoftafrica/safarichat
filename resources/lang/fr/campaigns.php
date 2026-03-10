<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Campaigns Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the campaigns module
    |
    */

    // Page Title & Navigation
    'page_title' => 'Sales Campaigns',
    'page_subtitle' => 'Manage and track your WhatsApp marketing campaigns in one place',
    'breadcrumb_home' => 'Home',
    'breadcrumb_campaigns' => 'Campaigns',

    // Actions
    'actions' => [
        'create_new' => 'Create New Campaign',
        'create_first' => 'Create Your First Campaign',
        'view_report' => 'Report',
        'pause' => 'Pause Campaign',
        'resume' => 'Resume Campaign',
        'clone' => 'Clone Campaign',
        'delete' => 'Delete Campaign',
        'filter' => 'Filter Campaigns',
    ],

    // Table Headers
    'table' => [
        'title' => 'Your Campaigns',
        'campaign_name' => 'Campaign Name',
        'recipients' => 'Recipients',
        'status' => 'Status',
        'progress' => 'Progress',
        'metrics' => 'Metrics',
        'actions' => 'Actions',
        'contacts' => 'contacts',
        'sent' => 'sent',
        'no_data_yet' => 'No data yet',
    ],

    // Status Labels
    'status' => [
        'all' => 'All Statuses',
        'completed' => 'Completed',
        'sending' => 'Active',
        'active' => 'Active',
        'scheduled' => 'Scheduled',
        'paused' => 'Paused',
        'failed' => 'Failed',
        'staging' => 'Staging',
        'draft' => 'Draft',
    ],

    // Metrics
    'metrics' => [
        'read_rate' => 'Read Rate',
        'reply_rate' => 'Reply Rate',
        'delivery_rate' => 'Delivery Rate',
        'engagement_rate' => 'Engagement Rate',
    ],

    // Empty State
    'empty' => [
        'title' => 'No Campaigns Yet',
        'subtitle' => 'Create your first sales campaign to start reaching customers via WhatsApp',
        'action' => 'Create Your First Campaign',
    ],

    // Campaign Creation
    'create' => [
        'title' => 'Create New Campaign',
        'subtitle' => 'Design and launch your WhatsApp marketing campaign',
        'save_draft' => 'Save as Draft',
        'launch' => 'Launch Campaign',
        'schedule' => 'Schedule Campaign',
        
        // Form Fields
        'campaign_name' => 'Campaign Name',
        'campaign_name_placeholder' => 'Enter campaign name...',
        'campaign_description' => 'Description',
        'campaign_description_placeholder' => 'Optional description...',
        
        // Recipient Selection
        'recipients_title' => 'Select Recipients',
        'all_contacts' => 'All Contacts',
        'all_contacts_desc' => 'Send to everyone in your contact list',
        'lead_status' => 'Select Lead Status',
        'lead_status_desc' => 'Choose contacts by their lead status',
        'custom_numbers' => 'Custom Numbers',
        'custom_numbers_desc' => 'Enter specific phone numbers manually',
        'upload_excel' => 'Upload Excel',
        'upload_excel_desc' => 'Upload an Excel file with phone numbers',
        'lead_status_placeholder' => 'Choose a lead status...',
        'enter_phone_numbers' => 'Enter Phone Numbers',
        'contact_input_placeholder' => 'Type phone numbers separated by comma or space...',
        'phone_help_text' => 'Enter numbers with country code (e.g., +255712345678)',
        'excel_help_text' => 'Upload an Excel file (.xls, .xlsx, .csv) with a column containing name (optional) as name, and phone number as phone (Mandatory).',
        'hashtag_name_desc' => 'Customer\'s full name',
        
        // Message Composer
        'message_title' => 'Compose Message',
        'message_placeholder' => 'Type your message here... Use #name for hashtag customer name',
        'attach_files' => 'Attach files',
        'take_photo' => 'Take photo',
        'add_emoji' => 'Add emoji',
        'record_audio' => 'Record audio',
        'send_message' => 'Send message',
        
        // AI Personalization
        'ai_personalization' => 'AI Personalization',
        'ai_enabled' => 'Enable AI-Powered Message Personalization',
        'ai_benefits_title' => 'What AI Personalization Does',
        'ai_benefits' => [
            'analyzes_history' => 'Analyzes conversation history to understand context',
            'detects_language' => 'Detects language & tone preferences automatically',
            'personalizes_message' => 'Personalizes each message based on relationship stage',
            'schedules_times' => 'Schedules optimal send times per recipient',
            'filters_sentiment' => 'Filters negative sentiment for human review',
            'increases_engagement' => 'Increases engagement 2-3x vs generic messages',
        ],
        'ai_how_it_works' => 'How it works: Your template message is analyzed and customized for each contact based on their chat history, interests, and communication style—making every message feel personal and relevant.',
        
        // Message Stats
        'word_count' => 'words',
        'sms_count' => 'SMS',
        'recipient_count' => 'recipients',
        'whatsapp_connected' => 'WhatsApp Connected',
        'whatsapp_disconnected' => 'WhatsApp Disconnected',
    ],

    // Validation & Errors
    'validation' => [
        'fix_errors' => 'Please fix the following errors:',
        'name_required' => 'Campaign name is required',
        'message_required' => 'Message is required',
        'recipients_required' => 'At least one recipient is required',
        'phone_invalid' => 'Invalid phone number format',
        'excel_invalid' => 'Invalid Excel file format',
    ],

    // Messages
    'messages' => [
        'created' => 'Campaign created successfully',
        'updated' => 'Campaign updated successfully',
        'deleted' => 'Campaign deleted successfully',
        'paused' => 'Campaign paused successfully',
        'resumed' => 'Campaign resumed successfully',
        'cloned' => 'Campaign cloned successfully',
        'launched' => 'Campaign launched successfully',
        'scheduled' => 'Campaign scheduled successfully',
        'processing' => 'Please wait while we process your request',
        'delete_confirm' => 'Are you sure you want to delete this campaign?',
    ],

    // Reports
    'report' => [
        'title' => 'Campaign Report',
        'subtitle' => 'Detailed analytics and performance metrics',
        'overview' => 'Overview',
        'performance' => 'Performance Metrics',
        'recipients_breakdown' => 'Recipients Breakdown',
        'engagement' => 'Engagement Analytics',
        'timeline' => 'Campaign Timeline',
        'export' => 'Export Report',
        
        // Header
        'created' => 'Created',
        'at' => 'at',
        'completed' => 'Completed',
        'back_to_campaigns' => 'Back to Campaigns',
        
        // Metrics
        'messages_sent' => 'Messages Sent',
        'of_total' => 'of total',
        'delivered' => 'Delivered',
        'delivery_rate' => 'delivery rate',
        'read' => 'Read',
        'read_rate' => 'read rate',
        'replied' => 'Replied',
        'reply_rate' => 'reply rate',
        'failed' => 'Failed',
        'failure_rate' => 'failure rate',
        'credits_spent' => 'Credits Spent',
        'per_message' => 'per message',
        
        // Sentiment Analysis
        'reply_sentiment_analysis' => 'Reply Sentiment Analysis',
        'positive_replies' => 'Positive Replies',
        'neutral_replies' => 'Neutral Replies',
        'negative_replies' => 'Negative Replies',
        
        // Message Recipients Table
        'message_recipients' => 'Message Recipients',
        'all_statuses' => 'All Statuses',
        
        // Table Headers
        'contact' => 'Contact',
        'phone' => 'Phone',
        'status' => 'Status',
        'sent_at' => 'Sent At',
        'delivered_at' => 'Delivered At',
        'read_at' => 'Read At',
        'reply' => 'Reply',
        'actions' => 'Actions',
        
        // Table Content
        'unknown' => 'Unknown',
        'replied_badge' => 'Replied',
        'view_contact' => 'View Contact',
        'no_messages_found' => 'No messages found',
        
        // Campaign Actions
        'campaign_actions' => 'Campaign Actions',
        'clone_campaign' => 'Clone This Campaign',
        'pause_campaign' => 'Pause Campaign',
        'resume_campaign' => 'Resume Campaign',
        
        // Stats (legacy compatibility)
        'total_sent' => 'Total Sent',
        'pending' => 'Pending',
    ],

];
