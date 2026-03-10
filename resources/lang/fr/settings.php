<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Settings Page Translations
    |--------------------------------------------------------------------------
    |
    | Translations for the user settings and account management page
    | including user accounts, subscription & billing, and business settings
    |
    */

    // Breadcrumbs
    'breadcrumb' => [
        'home' => 'Home',
        'profile' => 'Profile',
        'settings' => 'Settings',
    ],

    // Page Headers
    'page_title' => [
        'general_settings' => 'General Settings',
    ],

    'page_header' => [
        'list_of_items' => 'List of items to be set',
        'settings_description' => 'Put the correct setting value to get the best out of the system',
    ],

    // Navigation Tabs
    'tabs' => [
        'user_accounts' => 'User Accounts',
        'subscription_billing' => 'Subscription & Billing',
        'business_settings' => 'Business Settings',
    ],

    // User Accounts Section
    'user_accounts' => [
        'title' => 'Manage User Accounts',
        'description' => 'Each user account is able to login, and manage activities, view reports and much more..',
        'table' => [
            'hash' => '#',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'date_registered' => 'Date Registered',
            'action' => 'Action',
        ],
        'action' => [
            'edit' => 'Edit',
        ],
    ],

    // Subscription & Billing Section
    'subscription' => [
        'title' => 'Subscription & Billing',
        'description' => 'Manage your subscription, view usage, and handle billing',
        'current_subscription' => 'Current Subscription',
        'plan_label' => 'Plan',
        'status_label' => 'Status:',
        'started_label' => 'Started:',
        'trial_expires' => 'Trial Expires:',
        'next_billing' => 'Next Billing:',
        'days_left' => 'days left',
        'plan_features' => 'Plan Features',
        'contacts' => 'Contacts',
        'products' => 'Products',
        'whatsapp_lines' => 'WhatsApp Lines',
        'followups' => 'Followups',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // Credits Display
    'credits' => [
        'available_credits' => 'Available AI Credits',
        'conversion_rate' => '1 Credit = 4 AI Tokens',
        'top_up_wallet' => 'Top Up Wallet',
    ],

    // Quick Actions
    'quick_actions' => [
        'title' => 'Quick Actions',
        'upgrade_plan' => 'Upgrade Plan',
        'billing_history' => 'Billing History',
        'reactivate_now' => 'Reactivate Now',
    ],

    // Available Packages
    'packages' => [
        'title' => 'Available Packages',
        'recommended' => 'RECOMMENDED',
        'current' => 'CURRENT',
        'free_trial' => 'Free Trial',
        'per_month' => '/month',
        'description_default' => 'Perfect for getting started',
        'line_singular' => 'Line',
        'line_plural' => 'Lines',
        'ai_credits' => 'AI Credits',
        'current_plan_button' => 'Current Plan',
        'upgrade_now' => 'Upgrade Now',
        'not_available' => 'Not Available',
        'select_button' => 'Select',
        
        // Specific packages (if used dynamically)
        'winga' => [
            'name' => 'Winga',
            'price' => '$29/month',
            'features' => '50 contacts, 3 products',
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => '$149/month',
            'features' => '150 contacts, 50 products',
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => '$399/month',
            'features' => '300 contacts, 200 products',
        ],
    ],

    // Business Settings Section
    'business' => [
        'title' => 'Business Settings',
        'description' => 'Configure your business information and preferences.',
        'redirect_message' => 'Business settings are now managed through the dedicated Business Profile section.',
        'form' => [
            'name_label' => 'Business Name',
            'name_placeholder' => 'Business Name',
            'email_label' => 'Business Email',
            'email_placeholder' => 'Business Email',
            'phone_label' => 'Business Phone',
            'phone_placeholder' => 'Business Phone',
            'description_label' => 'Business Description',
            'description_placeholder' => 'Describe your business',
            'website_label' => 'Website URL',
            'website_placeholder' => 'https://example.com',
            'save_button' => 'Save Business Settings',
        ],
    ],

    // Customer Categories Section
    'categories' => [
        'title' => 'Customer Categories',
        'description' => 'Manage list of Customer categories',
        'add_new_button' => 'Add New Category',
        'table' => [
            'hash' => '#',
            'event_name' => 'Event Name',
            'customer_category' => 'Customer Category',
            'total_customer' => 'Total Customer',
            'action' => 'Action',
        ],
        'legacy_category' => 'Legacy Category',
        'action' => [
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],
        'tooltip_cannot_delete' => 'There are Customers in this category, You cannot delete. Delete first customers in this category if you want to delete it',
    ],

    // Modals
    'modal' => [
        'category' => [
            'title' => 'New Category',
            'name_label' => 'Category Name',
            'name_placeholder' => 'Customer Category Name',
            'close_button' => 'Close',
            'save_button' => 'Save',
        ],
        'user' => [
            'title' => 'Edit your information',
            'name_label' => 'Name',
            'name_placeholder' => 'Name',
            'email_label' => 'Email',
            'email_placeholder' => 'Email',
            'phone_label' => 'Phone',
            'phone_placeholder' => 'Phone',
            'uuid_label' => 'User UUID (for API access)',
            'uuid_help' => 'Use this UUID with your phone number for CRM API authentication',
            'close_button' => 'Close',
            'save_button' => 'Save',
        ],
    ],

    // JavaScript Messages
    'js' => [
        'alert' => [
            'contact_sales' => 'Contact sales@shulesoft.africa for custom Enterprise pricing',
        ],
        'paywall' => [
            'title' => 'Reactivate Your AI Sales Agent',
            'credits_waiting' => 'Your Credits Are Waiting!',
            'credits_available' => 'You still have :count credits available.',
            'missed_opportunities' => 'Missed Opportunities Today:',
            'choose_package' => 'Choose Your Package:',
            'payment_method' => 'Payment Method:',
            'lipa_namba_payment' => 'Lipa Namba Payment',
            'qr_generated_after' => 'QR code will be generated after package selection',
            'lipa_namba_label' => 'Lipa Namba:',
            'international_payment' => 'International Payment',
            'secure_payment_stripe' => 'Secure payment via Stripe',
            'close_button' => 'Close',
            'check_payment_status' => 'Check Payment Status',
        ],
        'payment' => [
            'initiation_failed' => 'Payment initiation failed:',
            'failed_generic' => 'Failed to initiate payment. Please try again.',
            'scan_qr_instruction' => 'Scan QR code or use Lipa Namba number above to complete payment',
            'checking' => 'Checking...',
            'not_received' => 'Payment not yet received. Please complete payment and try again.',
            'check_failed' => 'Failed to check payment status.',
        ],
        'billing_history' => [
            'title' => 'Billing History',
            'loading' => 'Loading...',
            'table' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'status' => 'Status',
                'actions' => 'Actions',
            ],
            'no_history' => 'No billing history yet',
            'transactions_appear_here' => 'Your payment transactions will appear here',
            'load_failed' => 'Failed to load billing history',
        ],
        'credit_topup' => [
            'title' => 'Buy Credits',
            'description' => 'Purchase additional credits for AI conversations',
            'amount_label' => 'Credit Amount',
            'option_100' => '100 Credits - $25',
            'option_500' => '500 Credits - $100',
            'option_1000' => '1000 Credits - $180',
            'option_2000' => '2000 Credits - $320',
            'conversion_note' => '1 Credit = 4 AI Tokens',
            'cancel_button' => 'Cancel',
            'purchase_button' => 'Purchase Credits',
        ],
        'confirm' => [
            'upgrade_package' => 'Upgrade to :package package for $:price/month?',
            'purchase_credits' => 'Purchase :amount credits for $:price?',
        ],
        'uuid' => [
            'copy_failed' => 'Unable to copy UUID. Please select and copy manually.',
        ],
    ],

    // Plan Status Badges
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'expired' => 'Expired',
        'trial' => 'Trial',
    ],

    // Plan Types
    'plan' => [
        'trial' => 'Trial',
        'starter' => 'Starter',
        'pro' => 'Pro',
        'premium' => 'Premium',
    ],
];
