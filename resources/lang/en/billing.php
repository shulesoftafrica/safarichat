<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing & Payments Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used throughout the billing and payment
    | module including payment gateway selection, wallet management, UCN payment
    | instructions, and transaction confirmations.
    |
    */

    // Page Titles
    'page_titles' => [
        'payment' => 'Complete Your Upgrade',
        'wallet' => 'My Wallet & Credits',
        'success' => 'Payment Successful!',
        'cancelled' => 'Payment Cancelled',
        'ucn_instructions' => 'UCN (Lipa Namba) Payment Instructions',
    ],

    // Payment Methods
    'payment_methods' => [
        'choose' => 'Choose Payment Method',
        'ucn' => [
            'name' => 'UCN (Lipa Namba)',
            'description' => 'Pay via UCN (Lipa Namba) From Any Bank or Mobile Money',
            'button' => 'Pay with UCN',
            'mobile_money' => 'UCN Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            'tanzania_only' => 'Pay via any bank or mobile money (Tanzania Only)',
        ],
        'stripe' => [
            'name' => 'Stripe',
            'description' => 'Pay securely with Credit/Debit Card',
            'button' => 'Pay with Card',
        ],
        'flutterwave' => [
            'name' => 'Flutterwave',
            'description' => 'Pay with Mobile Money, Bank Transfer & Cards',
            'button' => 'Pay with Flutterwave',
        ],
    ],

    // Plan Information
    'plan' => [
        'label' => 'Plan',
        'upgrade' => 'Plan Upgrade',
        'requested_feature' => 'Requested feature:',
        'full_upgrade' => 'Full plan upgrade',
        'current_plan' => 'Current Plan',
        'upgrade_button' => 'Upgrade Plan',
        'expires' => 'Expires:',
        'trial_mode' => 'Trial Mode',
        'feature' => 'Feature:',
    ],

    // Wallet & Credits
    'wallet' => [
        'available_credits' => 'Available AI Credits',
        'loading' => 'Loading...',
        'active' => 'Active',
        'top_up' => 'Top Up Your Wallet',
        'top_up_description' => 'Choose your preferred payment method to add credits to your wallet',
        'top_up_instruction' => 'Send any amount to top up your wallet instantly',
        'send_payment_to' => 'Send payment to:',
        'copy_number' => 'Copy Number',
    ],

    // Amount & Currency
    'amount' => [
        'label' => 'Amount:',
        'per_month' => '/month',
        'currency' => 'TZS',
    ],

    // Reference Numbers
    'reference' => [
        'label' => 'Reference:',
        'lipa_namba' => 'Reference (Lipa Namba):',
        'copy' => 'Copy Reference',
        'keep' => 'Keep your payment reference number:',
    ],

    // Features
    'features' => [
        'title' => "What you'll get:",
    ],

    // Success Messages
    'success' => [
        'title' => 'Payment Successful!',
        'message' => 'Your upgrade to :plan Plan has been completed successfully.',
        'features_active' => 'Your new features are now active and ready to use!',
    ],

    // Cancellation Messages
    'cancelled' => [
        'title' => 'Payment Cancelled',
        'message' => 'Your payment was cancelled. No charges were made to your account.',
        'try_again' => "You can try upgrading again anytime you're ready.",
    ],

    // UCN Payment Instructions
    'ucn_instructions' => [
        // Mobile Money Steps
        'mobile_steps' => [
            'step1_title' => 'Open Your Mobile App Payment Menu (Eg *150*01#)',
            'step1_description' => 'Launch your mobile money app',
            'step2_title' => 'Go to Make Payments (TAN-QR)',
            'step2_description' => 'Select "Pay Bills" or "Bill Payments" or "Lipa Kwa Simu"',
            'step3_title' => 'Enter ucn (LIPA NAMBA) Details',
            'step4_title' => 'Complete Payment',
            'step4_description' => 'Confirm and complete the payment',
        ],
        
        // Bank Transfer Steps
        'bank_steps' => [
            'step1_title' => 'Login to Online Banking or Mobile App of your Bank',
            'step1_description' => 'Or visit any Wakala/branch that support Lipa Namba Payments (TAN-QR)',
            'step2_title' => 'Transfer to SafariChat Account',
            'step3_title' => 'Complete Payment',
            'step3_description' => 'Confirm and complete the payment:',
        ],
        
        // Payment Details Labels
        'payment_details' => [
            'biller' => 'Biller:',
            'account_name' => 'Account Name:',
            'account_number' => 'Account Number (Lipa Namba):',
            'bank_channel' => 'Bank/Channel:',
            'tan_qr' => 'TAN-QR',
            'safarichat' => 'SafariChat',
        ],
        
        // Important Notes
        'important_notes' => [
            'title' => 'Important Notes:',
            'tanzania_only' => 'UCN (Lipa Namba) is ONLY applicable in Tanzania.',
            'cash_deposit' => 'If you have cash, put it your Mobile Money account or bank account before making the payment',
            'bank_support' => 'All Mobile Money supports Lipa Namba, but some banks (only few) still dont support Lipa Namba',
            'auto_activation' => 'Your subscription will be activated automatically once payment is confirmed',
            'support_contact' => 'Contact support if payment is not reflected within 48 hours',
        ],
    ],

    // Navigation Actions
    'actions' => [
        'back_to_agents' => 'Back to AI Agents',
        'back_to_settings' => 'Back to Settings',
        'dashboard' => 'Dashboard',
        'try_another_method' => 'Try Another Payment Method',
        'complete_payment' => 'Complete & Confirm Payment',
    ],
];
