<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Module Language Lines
    |--------------------------------------------------------------------------
    |
    | Translation keys for Admin Dashboard and Login
    |
    */

    // Login Page
    'login' => [
        'page_title' => 'SafariChat Admin Login',
        'brand_name' => 'SafariChat',
        'subtitle' => 'Admin Dashboard Access',
        'username_label' => 'Username',
        'password_label' => 'Password',
        'login_button' => 'Login to Dashboard',
        'footer_text' => 'SafariChat Admin Panel',
        'default_credentials' => 'Default:',
    ],

    // Dashboard Header
    'dashboard' => [
        'page_title' => 'SafariChat Admin Dashboard',
        'brand_header' => '🦁 SafariChat Admin Dashboard',
        'logout_link' => 'Logout',
    ],

    // Sidebar Navigation
    'nav' => [
        'overview' => 'System Overview',
        'users' => 'User Management',
        'subscriptions' => 'Subscriptions',
        'billing' => 'Billing & Payments',
        'whatsapp' => 'WhatsApp Instances',
        'health' => 'System Health',
        'settings' => 'Global Settings',
    ],

    // Overview Section
    'overview' => [
        'section_title' => 'System Overview',
        
        'stats' => [
            'total_customers' => 'Total Customers',
            'active_users' => 'Active Users',
            'total_subscriptions' => 'Total Subscriptions',
            'active_subscriptions' => 'Active Subscriptions',
        ],
        
        'revenue' => [
            'section_title' => 'Revenue Metrics',
            'total_collections' => 'Total Collections',
            'this_month' => 'This Month',
            'pending_payments' => 'Pending Payments',
        ],
        
        'activity' => [
            'section_title' => 'Recent Activity',
            'customer' => 'Customer',
            'action' => 'Action',
            'date' => 'Date',
            'no_activity' => 'No recent activity',
        ],
    ],

    // User Management Section
    'users' => [
        'section_title' => 'User Management',
        'search_placeholder' => 'Search users...',
        
        'table' => [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'subscription' => 'Subscription',
            'status' => 'Status',
            'registered' => 'Registered',
            'actions' => 'Actions',
        ],
        
        'actions' => [
            'view' => 'View',
            'edit' => 'Edit',
            'suspend' => 'Suspend',
            'activate' => 'Activate',
        ],
        
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ],
    ],

    // Subscriptions Section
    'subscriptions' => [
        'section_title' => 'Subscription Management',
        
        'summary' => [
            'trial' => 'Trial Users',
            'winga' => 'Winga Plan',
            'pro' => 'Pro Plan',
            'enterprise' => 'Enterprise Plan',
        ],
        
        'table' => [
            'customer' => 'Customer',
            'plan' => 'Plan',
            'status' => 'Status',
            'started' => 'Started',
            'expires' => 'Expires',
            'actions' => 'Actions',
        ],
    ],

    // Billing & Payments Section
    'billing' => [
        'section_title' => 'Billing & Payment Transactions',
        
        'stats' => [
            'total_revenue' => 'Total Revenue',
            'this_month' => 'This Month',
            'pending' => 'Pending',
            'failed' => 'Failed',
        ],
        
        'table' => [
            'transaction_id' => 'Transaction ID',
            'customer' => 'Customer',
            'amount' => 'Amount',
            'method' => 'Method',
            'status' => 'Status',
            'date' => 'Date',
        ],
        
        'status' => [
            'success' => 'Success',
            'pending' => 'Pending',
            'failed' => 'Failed',
        ],
    ],

    // WhatsApp Instances Section
    'whatsapp' => [
        'section_title' => 'WhatsApp Instance Management',
        
        'stats' => [
            'total_instances' => 'Total Instances',
            'active' => 'Active',
            'disconnected' => 'Disconnected',
        ],
        
        'table' => [
            'instance_id' => 'Instance ID',
            'customer' => 'Customer',
            'phone' => 'Phone Number',
            'status' => 'Status',
            'created' => 'Created',
            'last_active' => 'Last Active',
            'actions' => 'Actions',
        ],
        
        'status' => [
            'connected' => 'Connected',
            'disconnected' => 'Disconnected',
            'pending' => 'Pending',
        ],
        
        'actions' => [
            'view_logs' => 'View Logs',
            'disconnect' => 'Disconnect',
            'delete' => 'Delete',
        ],
    ],

    // System Health Section
    'health' => [
        'section_title' => 'System Health & Monitoring',
        
        'metrics' => [
            'api_status' => 'API Status',
            'database_status' => 'Database Status',
            'queue_status' => 'Queue Status',
            'storage_usage' => 'Storage Usage',
        ],
        
        'status' => [
            'healthy' => 'Healthy',
            'warning' => 'Warning',
            'critical' => 'Critical',
            'operational' => 'Operational',
            'down' => 'Down',
        ],
        
        'logs' => [
            'title' => 'Recent System Logs',
            'timestamp' => 'Timestamp',
            'level' => 'Level',
            'message' => 'Message',
        ],
    ],

    // Global Settings Section
    'settings' => [
        'section_title' => 'Global Settings',
        
        'general' => [
            'title' => 'General Settings',
            'app_name' => 'Application Name',
            'app_url' => 'Application URL',
            'timezone' => 'Default Timezone',
            'currency' => 'Default Currency',
        ],
        
        'payment' => [
            'title' => 'Payment Settings',
            'ucn_enabled' => 'UCN Payment Enabled',
            'stripe_enabled' => 'Stripe Payment Enabled',
            'test_mode' => 'Test Mode',
        ],
        
        'whatsapp' => [
            'title' => 'WhatsApp Configuration',
            'api_endpoint' => 'API Endpoint',
            'max_instances' => 'Max Instances per User',
        ],
        
        'notifications' => [
            'title' => 'Notification Settings',
            'email_notifications' => 'Email Notifications',
            'sms_notifications' => 'SMS Notifications',
        ],
        
        'buttons' => [
            'save' => 'Save Settings',
            'reset' => 'Reset to Defaults',
        ],
    ],

];
