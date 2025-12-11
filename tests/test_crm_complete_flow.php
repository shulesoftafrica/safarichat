<?php

/**
 * Complete CRM Import Flow Test
 * 
 * This script tests the complete flow:
 * 1. Import contacts
 * 2. Import conversation history for those contacts
 * 3. Retrieve context for a specific contact
 */

// Configuration
$baseUrl = 'https://localhost/safarichat';
$apiToken = '10|IhduIVvtapSzmpvPxXBERDm0RnjT8NHlhRoCZfDd56362e4c';

echo "\n" . str_repeat('=', 80) . "\n";
echo "COMPLETE CRM IMPORT FLOW TEST\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// STEP 1: Import Contacts
// ============================================================================

echo "STEP 1: Importing Contacts\n";
echo str_repeat('-', 80) . "\n";

$contactsData = [
    'contacts' => [
        [
            'crm_id' => 'TEST-CRM-001',
            'name' => 'Test User Alpha',
            'phone' => '255700000001',
            'email' => 'alpha@testcrm.com',
            'company' => 'Alpha Tech',
            'industry' => 'Technology',
            'tags' => ['test', 'enterprise'],
            'custom_fields' => [
                'account_value' => '100000',
                'priority' => 'high'
            ]
        ],
        [
            'crm_id' => 'TEST-CRM-002',
            'name' => 'Test User Beta',
            'phone' => '255700000002',
            'email' => 'beta@testcrm.com',
            'company' => 'Beta Solutions',
            'industry' => 'Software',
            'tags' => ['test', 'prospect'],
            'custom_fields' => [
                'lead_score' => '90',
                'priority' => 'medium'
            ]
        ]
    ]
];

$ch = curl_init($baseUrl . '/api/crm/import/contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($contactsData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$httpCode}: ";
$responseData = json_decode($response, true);
if ($responseData) {
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "\n❌ Failed to import contacts. Stopping test.\n\n";
    exit(1);
}

echo "✅ Contacts imported successfully!\n\n";
sleep(2); // Wait for transaction to commit

// ============================================================================
// STEP 2: Import Conversation History
// ============================================================================

echo "STEP 2: Importing Conversation History\n";
echo str_repeat('-', 80) . "\n";

$conversationData = [
    'contact_crm_id' => 'TEST-CRM-001',
    'conversations' => [
        [
            'message_content' => 'Customer expressed interest in enterprise AI solutions. Wants to automate customer service for 100+ agents.',
            'sender_type' => 'customer',
            'timestamp' => '2025-12-01 10:00:00',
            'crm_conversation_id' => 'CONV-001',
            'metadata' => ['channel' => 'email', 'subject' => 'Initial Contact'],
            'tags' => ['inquiry', 'enterprise']
        ],
        [
            'message_content' => 'Thank you for your interest! Let me schedule a discovery call to discuss your requirements in detail.',
            'sender_type' => 'agent',
            'timestamp' => '2025-12-01 14:30:00',
            'crm_conversation_id' => 'CONV-002',
            'metadata' => ['channel' => 'email', 'agent_name' => 'Sales Team'],
            'tags' => ['response', 'follow-up']
        ],
        [
            'message_content' => 'During our call, we discussed requirements: multi-language support, CRM integration, custom workflows. Budget: $100k annually.',
            'sender_type' => 'agent',
            'timestamp' => '2025-12-05 11:00:00',
            'crm_conversation_id' => 'CONV-003',
            'metadata' => ['channel' => 'call', 'duration_minutes' => 45],
            'tags' => ['discovery', 'qualified']
        ],
        [
            'message_content' => 'The demo was impressive! We are particularly interested in the AI capabilities and natural language processing. What are the next steps?',
            'sender_type' => 'customer',
            'timestamp' => '2025-12-10 15:00:00',
            'crm_conversation_id' => 'CONV-004',
            'metadata' => ['channel' => 'meeting', 'attendees' => 'CTO, IT Director'],
            'tags' => ['demo', 'hot-lead']
        ]
    ],
    'contact_background' => [
        'company_size' => '500+ employees',
        'current_solution' => 'Manual customer service with 100 agents',
        'pain_points' => ['High operational costs', 'Inconsistent service quality', 'Limited multilingual support']
    ],
    'previous_interactions' => [
        'total_touchpoints' => 4,
        'first_contact' => '2025-12-01',
        'last_contact' => '2025-12-10',
        'engagement_level' => 'high'
    ],
    'customer_preferences' => [
        'preferred_contact_method' => 'email',
        'timezone' => 'Africa/Dar_es_Salaam',
        'decision_timeframe' => 'Q1 2026'
    ]
];

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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$httpCode}: ";
$responseData = json_decode($response, true);
if ($responseData) {
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "\n❌ Failed to import conversation history. Stopping test.\n\n";
    exit(1);
}

echo "✅ Conversation history imported successfully!\n\n";
sleep(1);

// ============================================================================
// STEP 3: Retrieve Contact Context
// ============================================================================

echo "STEP 3: Retrieving Contact Context\n";
echo str_repeat('-', 80) . "\n";

$testCrmId = 'TEST-CRM-001';

$ch = curl_init($baseUrl . '/api/crm/import/contacts/' . urlencode($testCrmId) . '/context');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiToken,
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$httpCode}: ";
$responseData = json_decode($response, true);
if ($responseData) {
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}

if ($httpCode !== 200) {
    echo "\n❌ Failed to retrieve contact context.\n\n";
    exit(1);
}

echo "✅ Contact context retrieved successfully!\n\n";

// ============================================================================
// Summary
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 80) . "\n";
echo "✅ All tests passed successfully!\n";
echo "   - Contacts imported\n";
echo "   - Conversation history imported\n";
echo "   - Contact context retrieved\n";
echo "\nThe CRM import API is working correctly.\n\n";
