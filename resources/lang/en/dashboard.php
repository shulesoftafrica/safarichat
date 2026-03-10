<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the dashboard/home page
    |
    */

    // Page Title
    'title' => 'Dashboard',
    'page_title' => 'Dashboard - SafariChat',

    // Welcome Section
    'welcome' => [
        'title' => 'Ready to connect with your customers?',
        'subtitle' => 'You have <strong>:contacts</strong> contacts and <strong>:conversations</strong> active conversations',
        'greeting' => 'Welcome back, :name!',
        'good_morning' => 'Good morning, :name!',
        'good_afternoon' => 'Good afternoon, :name!',
        'good_evening' => 'Good evening, :name!',
    ],

    // Onboarding Messages
    'onboarding' => [
        'complete_title' => '🎉 Onboarding Complete!',
        'complete_message' => 'Your WhatsApp AI Sales System is ready! You\'ve successfully connected WhatsApp, added products, and configured your AI agent. You\'re all set to start converting leads into sales.',
        'proactive_title' => 'Ready for Proactive Outreach!',
        'proactive_message' => 'You can start importing contacts and sending targeted messages. Your AI will handle all conversations automatically.',
    ],

    // Instance Selector
    'instance_selector' => [
        'title' => 'WhatsApp Line',
        'label' => 'Choose which WhatsApp line to manage',
        'all_lines' => 'All Lines',
        'primary' => 'Primary',
        'configure' => 'Configure',
        'no_instances' => 'No WhatsApp instances found.',
        'create_new' => 'Create New Instance',
        'current_instance' => 'Current Instance',
        'switch_instance' => 'Switch Instance',
        'instance_info' => 'Instance Info',
        'phone_number' => 'Phone: :phone',
        'status' => 'Status: :status',
        'balance' => 'Message Balance: :balance',
    ],

    // Header Quick Actions
    'header_actions' => [
        'send_message' => 'Send Message',
        'manage_contacts' => 'Manage Contacts',
        'view_messages' => 'View Messages',
    ],

    // Engagement Alert
    'engagement_tip' => [
        'title' => 'Engagement Tip',
        'message' => 'You haven\'t sent many messages today. Engage more customers to grow your business!',
        'action' => 'Send Messages',
    ],

    // Metrics
    'metrics' => [
        'subscription_status' => 'Subscription Status',
        'available_credits' => 'Available Credits',
        'credits_remaining' => 'Credits Remaining',
        'whatsapp_contacts' => 'WhatsApp Contacts',
        'active_conversations' => 'Active Conversations',
        'messages_sent' => 'Messages Sent',
        'messages_sent_today' => 'Messages Sent Today',
        'response_rate' => 'Response Rate',
        'current_package' => 'Current Package',
        'manage_subscription' => 'Manage Subscription',
        'settings_billing' => 'Settings & Billing',
        
        // Subscription Status Values
        'subscription_active' => 'Active',
        'subscription_trial' => 'Trial',
        'subscription_inactive' => 'Inactive',
        'subscription_expired' => 'Expired',
        'subscription_cancelled' => 'Cancelled',
        
        // Status Messages
        'all_features_active' => 'All features active',
        'days_left' => ':days days left',
        'reactivate_now' => 'Reactivate now',
        'upgrade' => 'Upgrade',
        'go_to_settings' => 'Go to settings',
        
        // Trends
        'trend_this_month' => '+12% this month',
        'trend_last_30_days' => 'Last 30 days',
        'trend_today_activity' => 'Today\'s activity',
        'trend_last_7_days' => 'Last 7 days',
        'trend_up' => '↑ :percent% from last month',
        'trend_down' => '↓ :percent% from last month',
        'trend_stable' => 'No change',
        
        // Dynamic counts
        'contact_count' => '{0} No contacts|{1} :count contact|[2,*] :count contacts',
        'conversation_count' => '{0} No conversations|{1} :count conversation|[2,*] :count conversations',
        'message_count' => '{0} No messages|{1} :count message|[2,*] :count messages',
    ],

    // Quick Actions
    'quick_actions' => [
        'title' => 'Quick Actions',
        'compose_message' => 'Compose Message',
        'compose_description' => 'Send a message to your contacts or groups',
        'add_contact' => 'Add Contact',
        'add_contact_description' => 'Add a new customer to your contact list',
        'create_campaign' => 'Create Campaign',
        'create_campaign_description' => 'Launch a new sales campaign',
        'view_reports' => 'View Reports',
        'view_reports_description' => 'Analyze your messaging performance',
        'manage_products' => 'Manage Products',
        'manage_products_description' => 'Update your product catalog',
        'configure_agent' => 'Configure AI Agent',
        'configure_agent_description' => 'Set up your AI sales assistant',
        'view_contacts' => 'View Contacts',
        'view_messages' => 'View Messages',
        'settings' => 'Settings',
        'get_help' => 'Get Help',
    ],

    // Action Cards
    'action_cards' => [
        'quick_broadcast' => [
            'title' => 'Quick Broadcast',
            'description' => 'Send instant messages to all your customers about promotions, updates, or reminders.',
            'action' => 'Send Now',
        ],
        'contact_management' => [
            'title' => 'Contact Management',
            'description' => 'Manage your customer contacts, import new ones, and organize your customer database.',
            'action' => 'Manage Contacts',
        ],
    ],

    // Alerts
    'alerts' => [
        'low_credits' => 'Your message credits are running low. Add more credits to continue sending messages.',
        'no_instance' => 'You haven\'t created a WhatsApp instance yet. Create one to start engaging with customers.',
        'instance_disconnected' => 'Your WhatsApp instance is disconnected. Please reconnect to resume messaging.',
        'subscription_expiring' => 'Your subscription expires in :days days. Renew now to avoid interruption.',
        'subscription_expired' => 'Your subscription has expired. Renew to continue using all features.',
        'trial_ending' => 'Your trial ends in :days days. Upgrade to a paid plan to keep all features.',
        'verify_whatsapp' => 'Please verify your WhatsApp number to start sending messages.',
        'add_credits' => 'Add Credits',
        'create_instance' => 'Create Instance',
        'reconnect' => 'Reconnect Now',
        'renew_subscription' => 'Renew Subscription',
        'upgrade_now' => 'Upgrade Now',
    ],

    // Recent Activity
    'recent_activity' => [
        'title' => 'Recent Activity',
        'no_activity' => 'No recent activity',
        'view_all_messages' => 'View All Messages',
        'messages_sent_today' => ':count messages sent today',
        'active_conversations_30_days' => ':count active conversations',
        'today_activity' => 'Today\'s activity',
        'last_30_days' => 'Last 30 days',
        'message_sent' => 'Message sent to :contact',
        'contact_added' => 'New contact added: :name',
        'campaign_started' => 'Campaign started: :name',
        'agent_response' => 'AI Agent responded to :contact',
        'appointment_booked' => 'Appointment booked with :contact',
        'product_inquiry' => 'Product inquiry from :contact',
        'view_all' => 'View All Activity',
    ],

    // Engagement Stats
    'engagement' => [
        'title' => 'Message Engagement Trends',
        'time_filters' => [
            '7_days' => '7 Days',
            '30_days' => '30 Days',
            '3_months' => '3 Months',
        ],
        'chart_label_messages' => 'Messages',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'last_30_days' => 'Last 30 Days',
        'custom_range' => 'Custom Range',
        'messages_sent' => 'Messages Sent',
        'messages_delivered' => 'Messages Delivered',
        'messages_read' => 'Messages Read',
        'replies_received' => 'Replies Received',
        'new_contacts' => 'New Contacts',
        'active_contacts' => 'Active Contacts',
        'engagement_rate' => 'Engagement Rate',
        'delivery_rate' => 'Delivery Rate',
        'read_rate' => 'Read Rate',
        'response_rate' => 'Response Rate',
    ],

    // Navigation
    'menu' => [
        'dashboard' => 'Dashboard',
        'customers' => 'Customers',
        'campaigns' => 'Sales Campaigns',
        'groups' => 'Groups',
        'channels' => 'Channels',
        'schedule' => 'Schedule',
        'products' => 'Products',
        'agents' => 'Sales Agents',
        'appointments' => 'Appointments',
        'reports' => 'Reports',
        'settings' => 'Settings',
    ],

    // Quick Stats Cards
    'stats' => [
        'total_contacts' => 'Total Contacts',
        'total_groups' => 'Total Groups',
        'total_messages' => 'Total Messages',
        'total_campaigns' => 'Total Campaigns',
        'pending_appointments' => 'Pending Appointments',
        'active_agents' => 'Active AI Agents',
        'total_products' => 'Total Products',
        'this_month' => 'This Month',
        'today' => 'Today',
        'this_week' => 'This Week',
    ],

];
