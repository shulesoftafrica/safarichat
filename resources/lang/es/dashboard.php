<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the dashboard/home page
    | TODO: Professional Spanish translation required
    |
    */

    // Page Title
    'title' => 'Dashboard',
    'page_title' => 'Dashboard - SafariChat',

    // Welcome Section
    'welcome' => [
        'title' => 'Hello! Ready to connect with your customers?',
        'subtitle' => 'Your WhatsApp engagement hub - manage contacts, send campaigns, and grow your business.',
        'greeting' => 'Welcome back, :name!',
        'good_morning' => 'Good morning, :name!',
        'good_afternoon' => 'Good afternoon, :name!',
        'good_evening' => 'Good evening, :name!',
    ],

    // Instance Selector
    'instance_selector' => [
        'title' => 'Select WhatsApp Instance',
        'label' => 'Active Instance',
        'no_instances' => 'No WhatsApp instances found.',
        'create_new' => 'Create New Instance',
        'current_instance' => 'Current Instance',
        'switch_instance' => 'Switch Instance',
        'instance_info' => 'Instance Info',
        'phone_number' => 'Phone: :phone',
        'status' => 'Status: :status',
        'balance' => 'Message Balance: :balance',
    ],

    // Metrics
    'metrics' => [
        'subscription_status' => 'Subscription Status',
        'credits_remaining' => 'Credits Remaining',
        'whatsapp_contacts' => 'WhatsApp Contacts',
        'active_conversations' => 'Active Conversations',
        'messages_sent' => 'Messages Sent',
        'response_rate' => 'Response Rate',
        'current_package' => 'Current Package',
        'manage_subscription' => 'Manage Subscription',
        
        // Subscription Status Values
        'subscription_active' => 'Active',
        'subscription_trial' => 'Trial',
        'subscription_expired' => 'Expired',
        'subscription_cancelled' => 'Cancelled',
        
        // Trends
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
        'title' => 'Engagement Overview',
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
