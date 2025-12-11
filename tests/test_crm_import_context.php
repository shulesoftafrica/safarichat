<?php

/**
 * Test CRM Conversation History Import API
 * 
 * This script demonstrates how to import conversation history/context
 * for contacts from an external CRM to SafariChat.
 */

// Configuration
$baseUrl = 'https://localhost/safarichat';
$apiToken = '10|IhduIVvtapSzmpvPxXBERDm0RnjT8NHlhRoCZfDd56362e4c';

// Sample conversation history data
$conversationData = [
    'conversations' => [
        [
            'crm_id' => 'CRM-CONTACT-001',
            'history' => [
                [
                    'date' => '2025-12-01',
                    'type' => 'email',
                    'subject' => 'Initial Inquiry',
                    'content' => 'Customer inquired about enterprise pricing and features. Expressed interest in AI-powered customer service solutions.',
                    'direction' => 'inbound'
                ],
                [
                    'date' => '2025-12-02',
                    'type' => 'call',
                    'subject' => 'Follow-up Call',
                    'content' => 'Discussed implementation timeline. Customer wants to start in Q1 2026. Mentioned they have 50+ agents and need multi-language support.',
                    'direction' => 'outbound'
                ],
                [
                    'date' => '2025-12-05',
                    'type' => 'meeting',
                    'subject' => 'Demo Session',
                    'content' => 'Conducted live demo of WhatsApp integration and AI agent capabilities. Customer was impressed with the natural language processing. Next step: Send formal proposal.',
                    'direction' => 'meeting'
                ]
            ]
        ],
        [
            'crm_id' => 'CRM-CONTACT-002',
            'history' => [
                [
                    'date' => '2025-11-28',
                    'type' => 'chat',
                    'subject' => 'Website Chat Inquiry',
                    'content' => 'Lead came through website chat asking about pricing for small business package. Interested in WhatsApp Business API integration.',
                    'direction' => 'inbound'
                ],
                [
                    'date' => '2025-12-03',
                    'type' => 'email',
                    'subject' => 'Pricing Information Sent',
                    'content' => 'Sent detailed pricing breakdown and feature comparison. Included case studies from similar-sized businesses in the software industry.',
                    'direction' => 'outbound'
                ],
                [
                    'date' => '2025-12-10',
                    'type' => 'whatsapp',
                    'subject' => 'Quick Question',
                    'content' => 'Customer asked if we support integration with their existing CRM (Salesforce). Confirmed yes and sent integration documentation.',
                    'direction' => 'inbound'
                ]
            ]
        ],
        [
            'crm_id' => 'CRM-CONTACT-003',
            'history' => [
                [
                    'date' => '2025-10-15',
                    'type' => 'email',
                    'subject' => 'New Customer Onboarding',
                    'content' => 'Completed onboarding process. Set up account with basic package. Customer primarily interested in bulk WhatsApp messaging for promotions.',
                    'direction' => 'system'
                ],
                [
                    'date' => '2025-11-20',
                    'type' => 'call',
                    'subject' => 'Monthly Check-in',
                    'content' => 'Monthly success call. Customer satisfied with service. Sending approximately 5000 messages per month. Asked about upgrade options for holiday season.',
                    'direction' => 'outbound'
                ],
                [
                    'date' => '2025-12-08',
                    'type' => 'support',
                    'subject' => 'Technical Support',
                    'content' => 'Opened support ticket regarding message delivery delays. Issue was traced to WhatsApp rate limiting. Advised on best practices for bulk sending. Resolved.',
                    'direction' => 'support'
                ]
            ]
        ]
    ]
];

// Initialize cURL
$ch = curl_init($baseUrl . '/api/crm/import/context');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($conversationData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
]);

echo "\n=== Testing CRM Conversation History Import ===\n";
echo "Endpoint: {$baseUrl}/api/crm/import/context\n";
echo "Importing conversation history for " . count($conversationData['conversations']) . " contacts...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "❌ cURL Error: {$error}\n";
    exit(1);
}

echo "HTTP Status Code: {$httpCode}\n";
echo "Response:\n";
echo str_repeat('-', 80) . "\n";

$responseData = json_decode($response, true);
if ($responseData) {
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}

echo str_repeat('-', 80) . "\n\n";

if ($httpCode === 200) {
    echo "✅ Conversation history imported successfully!\n";
} elseif ($httpCode === 401) {
    echo "❌ Authentication failed. Please check your API token.\n";
} elseif ($httpCode === 422) {
    echo "❌ Validation failed. Please check the data format.\n";
} else {
    echo "❌ Request failed with status code {$httpCode}\n";
}

echo "\n";
