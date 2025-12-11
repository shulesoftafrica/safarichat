<?php

/**
 * Test CRM Contact Import API
 * 
 * This script demonstrates how to import contacts from an external CRM
 * to the SafariChat system using the API.
 */

// Configuration
$baseUrl = 'https://localhost/safarichat';
$apiToken = '10|IhduIVvtapSzmpvPxXBERDm0RnjT8NHlhRoCZfDd56362e4c';

// Sample contacts data
$contactsData = [
    'contacts' => [
        [
            'crm_id' => 'CRM-CONTACT-001',
            'name' => 'John Doe',
            'phone' => '255712345678',
            'email' => 'john.doe@example.com',
            'company' => 'Acme Corporation',
            'industry' => 'Technology',
            'tags' => ['premium', 'enterprise'],
            'custom_fields' => [
                'account_value' => '50000',
                'contract_end_date' => '2025-12-31',
                'account_manager' => 'Sarah Smith'
            ]
        ],
        [
            'crm_id' => 'CRM-CONTACT-002',
            'name' => 'Jane Smith',
            'phone' => '255723456789',
            'email' => 'jane.smith@example.com',
            'company' => 'Tech Solutions Ltd',
            'industry' => 'Software',
            'tags' => ['prospect', 'hot-lead'],
            'custom_fields' => [
                'lead_score' => '85',
                'last_interaction' => '2025-12-10',
                'interested_in' => 'Enterprise Package'
            ]
        ],
        [
            'crm_id' => 'CRM-CONTACT-003',
            'name' => 'Ahmed Hassan',
            'phone' => '255734567890',
            'email' => 'ahmed.hassan@business.co.tz',
            'company' => 'Hassan Trading',
            'industry' => 'Retail',
            'tags' => ['existing-customer', 'wholesale'],
            'custom_fields' => [
                'annual_revenue' => '25000',
                'payment_terms' => '30 days',
                'preferred_contact_time' => 'morning'
            ]
        ]
    ]
];

// Initialize cURL
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

echo "\n=== Testing CRM Contact Import ===\n";
echo "Endpoint: {$baseUrl}/api/crm/import/contacts\n";
echo "Importing " . count($contactsData['contacts']) . " contacts...\n\n";

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
    echo "✅ Contacts imported successfully!\n";
} elseif ($httpCode === 401) {
    echo "❌ Authentication failed. Please check your API token.\n";
} elseif ($httpCode === 422) {
    echo "❌ Validation failed. Please check the data format.\n";
} else {
    echo "❌ Request failed with status code {$httpCode}\n";
}

echo "\n";
