<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upgrade Required Language Lines
    |--------------------------------------------------------------------------
    |
    | Translation keys for upgrade/paywall pages
    |
    */

    // Sales Reports Upgrade
    'sales_reports' => [
        'page_title' => 'Sales Reports - Upgrade Required',
        'header' => 'Sales Reports & Analytics',
        
        'breadcrumb' => [
            'dashboard' => 'Dashboard',
            'sales_reports' => 'Sales Reports',
        ],
        
        'lock_title' => 'Advanced Sales Reports',
        'description' => 'Detailed sales analytics and reporting features are available in the',
        'premium_plan' => 'Premium',
        'plan_suffix' => 'plan.',
        
        // Feature Previews
        'features' => [
            'revenue' => [
                'title' => 'Revenue Analytics',
                'description' => 'Track revenue trends and performance metrics',
            ],
            'customers' => [
                'title' => 'Customer Insights',
                'description' => 'Analyze customer behavior and engagement',
            ],
            'time_based' => [
                'title' => 'Time-based Reports',
                'description' => 'Monthly, quarterly, and yearly reports',
            ],
            'export' => [
                'title' => 'Export Reports',
                'description' => 'Export data to PDF and Excel formats',
            ],
        ],
        
        // Plan Comparison
        'plan_comparison' => [
            'current_plan' => 'Current Plan:',
            'required_plan' => 'Required:',
        ],
        
        // Action Buttons
        'actions' => [
            'upgrade_button' => 'Upgrade to Premium',
            'back_button' => 'Back to Dashboard',
        ],
        
        // Footer
        'help' => [
            'question' => 'Need help choosing the right plan?',
            'contact_support' => 'Contact our support team',
        ],
    ],

    // Generic Upgrade Messages
    'generic' => [
        'feature_locked' => 'This feature is locked',
        'upgrade_required' => 'Upgrade Required',
        'available_in_plan' => 'Available in :plan plan',
        'unlock_feature' => 'Unlock this feature',
        'learn_more' => 'Learn More',
        'compare_plans' => 'Compare Plans',
        'contact_sales' => 'Contact Sales',
    ],

];
