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

// Sample conversation history data for ONE contact
$conversationData = [
    'contact_crm_id' => 'CRM-CONTACT-001',
    'conversations' => [
        [
            'message_content' => 'Hi, I am interested in your enterprise AI customer service solution. We currently have 50+ agents and need to automate responses.',
            'sender_type' => 'customer',
            'timestamp' => '2025-12-01 10:30:00',
            'crm_conversation_id' => 'CONV-001',
            'metadata' => [
                'channel' => 'email',
                'subject' => 'Initial Inquiry'
            ],
            'tags' => ['inquiry', 'enterprise', 'automation']
        ],
        [
            'message_content' => 'Thank you for reaching out! I would love to schedule a call to understand your requirements better. When would be a good time?',
            'sender_type' => 'agent',
            'timestamp' => '2025-12-01 14:00:00',
            'crm_conversation_id' => 'CONV-002',
            'metadata' => [
                'channel' => 'email',
                'agent_name' => 'Sales Team',
                'subject' => 'Re: Initial Inquiry'
            ],
            'tags' => ['response', 'follow-up']
        ],
        [
            'message_content' => 'We discussed implementation timeline. Customer wants to start in Q1 2026. They need multi-language support (English, Swahili, French) and CRM integration.',
            'sender_type' => 'agent',
            'timestamp' => '2025-12-02 11:00:00',
            'crm_conversation_id' => 'CONV-003',
            'metadata' => [
                'channel' => 'call',
                'duration_minutes' => 30,
                'call_outcome' => 'qualified'
            ],
            'tags' => ['call', 'discovery', 'qualified-lead']
        ],
        [
            'message_content' => 'The demo was excellent! Our team is very impressed with the AI capabilities and the natural language processing. What are the next steps for getting a proposal?',
            'sender_type' => 'customer',
            'timestamp' => '2025-12-05 15:30:00',
            'crm_conversation_id' => 'CONV-004',
            'metadata' => [
                'channel' => 'meeting',
                'attendees' => ['CTO', 'IT Director', 'Customer Service Manager'],
                'meeting_duration_minutes' => 60
            ],
            'tags' => ['demo', 'hot-lead', 'proposal-requested']
        ],
        [
            'message_content' => 'I will prepare a detailed proposal and pricing based on your requirements. Expect it by end of day tomorrow.',
            'sender_type' => 'agent',
            'timestamp' => '2025-12-05 16:00:00',
            'crm_conversation_id' => 'CONV-005',
            'metadata' => [
                'channel' => 'meeting',
                'agent_name' => 'Account Executive'
            ],
            'tags' => ['proposal', 'commitment']
        ]
    ],
    'contact_background' => [
        'company_size' => '500+ employees',
        'current_solution' => 'Manual customer service with 50+ agents',
        'pain_points' => [
            'High operational costs',
            'Inconsistent response quality',
            'No multilingual support',
            'Long response times'
        ],
        'budget_range' => '$50,000 - $100,000 annually',
        'decision_makers' => ['CTO', 'IT Director']
    ],
    'previous_interactions' => [
        'total_touchpoints' => 5,
        'first_contact_date' => '2025-12-01',
        'last_contact_date' => '2025-12-05',
        'channels_used' => ['email', 'call', 'meeting'],
        'engagement_level' => 'high'
    ],
    'customer_preferences' => [
        'preferred_contact_method' => 'email',
        'preferred_contact_time' => 'mornings',
        'timezone' => 'Africa/Dar_es_Salaam',
        'decision_timeframe' => 'Q1 2026'
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
echo "Contact CRM ID: {$conversationData['contact_crm_id']}\n";
echo "Importing " . count($conversationData['conversations']) . " conversation messages...\n\n";

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

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Conversation history imported successfully!\n";
} elseif ($httpCode === 401) {
    echo "❌ Authentication failed. Please check your API token.\n";
} elseif ($httpCode === 422) {
    echo "❌ Validation failed. Please check the data format.\n";
} else {
    echo "❌ Request failed with status code {$httpCode}\n";
}

echo "\n";
