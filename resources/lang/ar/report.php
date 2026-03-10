<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reports & Analytics Language Lines
    |--------------------------------------------------------------------------
    |
    | Translation keys for WhatsApp Business Analytics Dashboard
    |
    */

    // Page Header
    'buttons' => [
        'export_report' => 'Export Report',
    ],

    // Metrics - Primary KPIs
    'metrics' => [
        'whatsapp_sent' => [
            'label' => 'WhatsApp Messages Sent',
            'total' => 'Total messages',
        ],
        'responses' => [
            'label' => 'Customer Responses',
            'rate_suffix' => 'response rate',
        ],
        'conversations' => [
            'label' => 'Active Conversations',
        ],
        'success_rate' => [
            'label' => 'Message Success Rate',
            'trend' => 'Delivery success',
        ],
        'time' => [
            'this_week' => 'this week',
            'last_30_days' => 'Last 30 days',
        ],
    ],

    // Business Impact Insights
    'insights' => [
        'section_title' => 'Business Impact Insights',
        
        'conversations' => [
            'active_this_month' => 'active customer conversations this month',
            'ready_to_start' => 'Ready to start engaging customers via WhatsApp',
        ],
        
        'response' => [
            'excellent_prefix' => 'Excellent response rate of',
            'excellent_suffix' => 'shows customers love WhatsApp communication',
            'good_prefix' => 'Good',
            'good_suffix' => 'response rate - customers are engaging with your messages',
            'general_benefit' => 'WhatsApp typically gets 10x better response rates than email marketing',
        ],
        
        'messages_today' => [
            'sent_today' => 'messages sent today',
            'ready' => 'Ready to send instant messages to customers',
            'read_time_comparison' => 'WhatsApp messages are typically read within 3 minutes vs 6+ hours for email',
        ],
        
        'cost' => [
            'estimated_cost' => 'Estimated messaging cost:',
            'cost_comparison' => 'WhatsApp typically costs 75% less than traditional advertising per customer reached',
        ],
        
        'roi' => [
            'excellent_prefix' => 'Excellent ROI of',
            'excellent_suffix' => 'WhatsApp is generating strong returns',
            'positive_prefix' => 'Positive ROI of',
            'positive_suffix' => 'your WhatsApp investment is paying off',
        ],
        
        'contacts' => [
            'total_ready' => 'total contacts ready for messaging',
            'reached_prefix' => "You've reached",
            'reached_suffix' => 'unique customers via WhatsApp',
            'start_engaging' => 'Start engaging your contacts to build stronger customer relationships',
        ],
    ],

    // Comparison Card
    'comparison' => [
        'section_title' => 'WhatsApp vs Traditional Channels',
        
        'read_rate' => [
            'label' => 'Read Rate',
            'value' => '98% vs 20%',
        ],
        'response_rate' => [
            'label' => 'Response Rate',
            'value_suffix' => 'vs 2%',
        ],
        'cost_per_message' => [
            'label' => 'Cost per Message',
            'value' => 'TSh 50 vs TSh 200',
        ],
        'delivery_speed' => [
            'label' => 'Delivery Speed',
            'value' => 'Instant vs 24hrs',
        ],
        'customer_preference' => [
            'label' => 'Customer Preference',
            'value_suffix' => 'vs 2.8/5',
        ],
        'roi' => [
            'label' => 'ROI:',
            'message' => 'Your WhatsApp investment is generating excellent returns!',
        ],
    ],

    // Customer Engagement Performance
    'performance' => [
        'section_title' => 'Customer Engagement Performance',
        
        'success_rate' => [
            'label' => 'Message Success Rate',
            'trend' => 'Delivery success',
        ],
        'response_rate' => [
            'label' => 'Customer Response Rate',
        ],
        'auto_replies' => [
            'label' => 'Auto-Replies Sent',
            'trend' => 'Automated',
        ],
        'customers_reached' => [
            'label' => 'Customers Reached',
            'of_total' => 'of total',
            'ready' => 'Ready to start',
        ],
        'rating' => [
            'excellent' => 'Excellent!',
            'good' => 'Good',
            'growing' => 'Growing',
        ],
    ],

    // Engagement Metrics
    'engagement' => [
        'estimated_leads' => 'Estimated Leads',
        'active_instances' => 'Active WhatsApp Instances',
        'text_messages' => 'Text Messages',
        'media_messages' => 'Media Messages',
        'total_cost' => 'Total Messaging Cost',
    ],

    // Charts
    'charts' => [
        'engagement_trends' => [
            'title' => 'Message Engagement Trends',
            'y_axis_label' => 'Messages',
            'series_whatsapp' => 'WhatsApp Messages',
            'series_responses' => 'Customer Responses',
            'tooltip_suffix' => 'messages',
        ],
        'no_data' => 'No Data',
    ],

    // Growth Recommendations
    'recommendations' => [
        'section_title' => 'Growth Recommendations',
        
        'immediate' => [
            'title' => '📈 Immediate Actions',
            'start_sending_prefix' => 'Start sending WhatsApp messages to your',
            'start_sending_suffix' => 'contacts',
            'setup_welcome' => 'Set up automated welcome messages for new customers',
            'improve_content_prefix' => 'Improve message content to increase your',
            'improve_content_suffix' => 'response rate',
            'personalize' => 'Send more personalized messages using customer names',
            'excellent_prefix' => 'Your',
            'excellent_suffix' => 'response rate is excellent! Keep engaging',
            'expand_segments' => 'Consider expanding to more customer segments',
            'setup_auto_replies' => 'Set up auto-replies to handle customer inquiries 24/7',
            'try_media' => 'Try sending images and documents for better engagement',
        ],
        
        'growth' => [
            'title' => '💡 Growth Opportunities',
            'excellent_roi_prefix' => 'Your excellent',
            'excellent_roi_suffix' => 'ROI shows WhatsApp is very profitable',
            'increase_budget' => 'Consider increasing your messaging budget for more growth',
            'positive_roi_prefix' => 'Your',
            'positive_roi_suffix' => 'ROI is positive - scale up messaging',
            'measure_conversions' => 'Start measuring conversions to track your ROI',
            'nurture_relationships' => 'customers are engaged - nurture these relationships',
            'spend_comparison' => 'WhatsApp customers typically spend 3x more than email customers',
            'exclusive_promotions' => 'Consider offering exclusive WhatsApp-only promotions',
        ],
    ],

    // Success Score
    'success_score' => [
        'title' => '🎯 Your WhatsApp Success Score',
        
        'rating' => [
            'excellent' => "Excellent! You're maximizing WhatsApp's potential",
            'great' => 'Great progress! A few more optimizations will boost your results',
            'good' => 'Good start! Focus on engagement and automation',
            'ready' => "Ready to unlock WhatsApp's full potential for your business",
        ],
    ],

    // Export Report
    'export' => [
        'document_title' => 'WhatsApp Business Analytics Report',
        'generated_label' => 'Generated:',
        'performance_summary' => 'PERFORMANCE SUMMARY:',
        'messages_sent' => 'Messages Sent:',
        'customer_responses' => 'Customer Responses:',
        'response_rate' => 'Response Rate:',
        'active_conversations' => 'Active Conversations:',
        'business_impact' => 'BUSINESS IMPACT:',
        'total_cost' => 'Total Messaging Cost:',
        'estimated_revenue' => 'Estimated Revenue:',
        'roi' => 'ROI:',
        'success_score' => 'Success Score:',
        'recommendations_header' => 'RECOMMENDATIONS:',
        'recommendation_improve_content' => 'Focus on improving message content and personalization',
        'recommendation_scale_up' => 'Excellent response rate - consider scaling up',
        'recommendation_strong_roi' => 'Strong ROI - increase messaging budget for growth',
        'recommendation_track_roi' => 'Track conversions to measure ROI better',
        'fact_engagement' => 'WhatsApp provides 10x better engagement than email',
        'recommendation_consistency' => 'Continue building customer relationships through consistent messaging',
        'footer' => 'Report generated by SafariChat WhatsApp Business Platform',
        'success_message' => "✅ Report exported successfully!\n\nYour comprehensive WhatsApp business analytics report has been downloaded.",
    ],

    // Debug/Console Messages
    'debug' => [
        'data_refreshed' => 'Data refreshed for period:',
        'analytics_refreshed' => 'Analytics data refreshed...',
        'dashboard_initialized' => 'WhatsApp Business Analytics Dashboard initialized successfully',
    ],

    // Dialog Messages
    'dialog' => [
        'welcome_first_time' => "Welcome to SafariChat WhatsApp Business Analytics!\n\nWould you like to start sending your first messages to engage customers?",
    ],

    // Celebration Messages
    'celebration' => [
        'exceptional_engagement' => '🎉 Congratulations! Your WhatsApp engagement is exceptional!',
    ],

];
