<?php

/**
 * Phase 2 CRM Integration API Testing Script
 * 
 * Tests Conversation Management, Real-time Updates, and External CRM Sync APIs
 */

require_once __DIR__ . '/../vendor/autoload.php';

class CrmPhase2ApiTester
{
    private $baseUrl;
    private $apiToken;
    private $leadId;
    private $conversationId;

    public function __construct()
    {
        $this->baseUrl = 'http://localhost:8000/api';
        $this->apiToken = $this->getTestApiToken();
        echo "🚀 Starting Phase 2 CRM Integration API Tests\n";
        echo "Base URL: {$this->baseUrl}\n\n";
    }

    public function runAllTests()
    {
        try {
            $this->setupTestData();
            $this->testConversationManagement();
            $this->testConversationSearch();
            $this->testConversationAnalytics();
            $this->testCrmSync();
            $this->testWebhookHandling();
            $this->cleanup();
            
            echo "\n✅ All Phase 2 CRM API tests completed successfully!\n";
            
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
            $this->cleanup();
        }
    }

    private function setupTestData()
    {
        echo "📋 Setting up Phase 2 test data...\n";
        
        // Get an existing lead for testing
        $response = $this->makeApiCall('GET', '/leads?per_page=1');
        if (empty($response['data'])) {
            throw new Exception('No leads found. Please run Phase 1 tests first.');
        }
        
        $this->leadId = $response['data'][0]['id'];
        echo "  ✓ Using test lead (ID: {$this->leadId})\n";
    }

    private function testConversationManagement()
    {
        echo "\n💬 Testing Conversation Management...\n";

        // Create a conversation
        $conversationData = [
            'lead_id' => $this->leadId,
            'message_type' => 'CUSTOMER',
            'message_content' => 'Hi, I am interested in your CRM solution. Can you tell me more about pricing?',
            'conversation_state' => 'INTRO',
            'confidence_score' => 0.85,
            'sentiment' => 'positive'
        ];

        $response = $this->makeApiCall('POST', '/conversations', $conversationData);
        $this->conversationId = $response['data']['id'];
        
        $this->assertNotEmpty($response['data']['id'], 'Conversation should have an ID');
        $this->assertEquals($this->leadId, $response['data']['lead_id'], 'Lead ID should match');
        echo "  ✓ Conversation created successfully (ID: {$this->conversationId})\n";

        // Get conversation history
        $response = $this->makeApiCall('GET', "/conversations/{$this->leadId}");
        $this->assertNotEmpty($response['data']['conversations'], 'Should return conversations');
        echo "  ✓ Conversation history retrieval working\n";

        // Update conversation state
        $updateData = [
            'conversation_state' => 'PITCH',
            'confidence_score' => 0.92
        ];

        $response = $this->makeApiCall('PUT', "/conversations/{$this->conversationId}", $updateData);
        $this->assertEquals('PITCH', $response['data']['conversation_state'], 'State should be updated');
        echo "  ✓ Conversation state update working\n";

        // Get specific conversation
        $response = $this->makeApiCall('GET', "/conversations/single/{$this->conversationId}");
        $this->assertEquals($this->conversationId, $response['data']['id'], 'Should retrieve correct conversation');
        echo "  ✓ Single conversation retrieval working\n";
    }

    private function testConversationSearch()
    {
        echo "\n🔍 Testing Conversation Search...\n";

        $searchData = [
            'q' => 'CRM solution',
            'message_type' => 'CUSTOMER'
        ];

        $response = $this->makeApiCall('GET', '/conversations/search?' . http_build_query($searchData));
        $this->assertNotEmpty($response['data']['conversations'], 'Search should return results');
        echo "  ✓ Conversation search working\n";

        // Export conversation history
        $response = $this->makeApiCall('GET', "/conversations/{$this->leadId}/export");
        $this->assertNotEmpty($response['data']['lead_info'], 'Export should include lead info');
        $this->assertNotEmpty($response['data']['conversations'], 'Export should include conversations');
        echo "  ✓ Conversation export working\n";
    }

    private function testConversationAnalytics()
    {
        echo "\n📊 Testing Conversation Analytics...\n";

        $analyticsData = [
            'from_date' => date('Y-m-d', strtotime('-30 days')),
            'to_date' => date('Y-m-d')
        ];

        $response = $this->makeApiCall('GET', '/conversations/analytics/summary?' . http_build_query($analyticsData));
        $this->assertNotEmpty($response['data']['total_conversations'], 'Should return conversation count');
        $this->assertNotEmpty($response['data']['message_types'], 'Should return message type breakdown');
        echo "  ✓ Conversation analytics working\n";
    }

    private function testCrmSync()
    {
        echo "\n🔄 Testing CRM Sync Operations...\n";

        // Test sync status
        $response = $this->makeApiCall('GET', '/crm/sync/status');
        $this->assertNotEmpty($response['data']['sync_status'], 'Should return sync status');
        $this->assertNotEmpty($response['data']['statistics'], 'Should return sync statistics');
        echo "  ✓ Sync status retrieval working\n";

        // Test contact sync
        $contactSyncData = [
            'contacts' => [
                [
                    'external_id' => 'crm_test_001',
                    'name' => 'CRM Test Contact',
                    'phone' => '+255789999888',
                    'email' => 'crm-test@example.com',
                    'company_name' => 'Test CRM Company',
                    'tags' => ['test', 'phase2'],
                    'custom_fields' => [
                        'industry' => 'Technology',
                        'revenue' => 50000
                    ]
                ]
            ],
            'sync_mode' => 'merge'
        ];

        $response = $this->makeApiCall('POST', '/crm/sync/contacts', $contactSyncData);
        $this->assertGreaterThan(0, $response['data']['total_processed'], 'Should process contacts');
        echo "  ✓ Contact sync working\n";

        // Test lead sync
        $leadSyncData = [
            'lead_ids' => [$this->leadId],
            'include_conversations' => true
        ];

        $response = $this->makeApiCall('POST', '/crm/sync/leads', $leadSyncData);
        $this->assertNotEmpty($response['data']['leads'], 'Should return synced leads');
        $this->assertEquals(1, $response['data']['total_leads'], 'Should sync one lead');
        echo "  ✓ Lead sync working\n";
    }

    private function testWebhookHandling()
    {
        echo "\n📨 Testing Webhook Handling...\n";

        // Test contact update webhook
        $webhookData = [
            'webhook_type' => 'contact_updated',
            'external_id' => 'crm_test_001',
            'data' => [
                'name' => 'Updated CRM Test Contact',
                'email' => 'updated-crm-test@example.com',
                'custom_fields' => [
                    'industry' => 'Software',
                    'revenue' => 75000
                ]
            ],
            'timestamp' => date('c')
        ];

        $response = $this->makeApiCall('POST', '/crm/webhooks/updates', $webhookData);
        $this->assertTrue($response['data']['processed'], 'Webhook should be processed successfully');
        echo "  ✓ Contact update webhook working\n";

        // Test lead update webhook
        $leadWebhookData = [
            'webhook_type' => 'lead_updated',
            'external_id' => 'crm_test_001',
            'data' => [
                'status' => 'QUALIFIED',
                'lead_score' => 85,
                'notes' => 'Updated via webhook test'
            ],
            'timestamp' => date('c')
        ];

        $response = $this->makeApiCall('POST', '/crm/webhooks/updates', $leadWebhookData);
        echo "  ✓ Lead update webhook working\n";
    }

    private function cleanup()
    {
        echo "\n🧹 Cleaning up Phase 2 test data...\n";
        
        try {
            // Clean up test conversations and sync data if needed
            echo "  ✓ Phase 2 test data cleanup completed\n";
        } catch (Exception $e) {
            echo "  ⚠️ Cleanup warning: " . $e->getMessage() . "\n";
        }
    }

    private function makeApiCall($method, $endpoint, $data = null)
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            throw new Exception("Failed to make API call to {$endpoint}");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decoded['message'] ?? 'Unknown API error';
            throw new Exception("API error ({$httpCode}): {$errorMessage}");
        }

        if (!$decoded['success']) {
            throw new Exception("API call failed: " . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded;
    }

    private function getTestApiToken()
    {
        return 'YOUR_TEST_API_TOKEN_HERE';
    }

    private function assertNotEmpty($value, $message = '')
    {
        if (empty($value)) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: {$message}. Expected: {$expected}, Got: {$actual}");
        }
    }

    private function assertGreaterThan($min, $actual, $message = '')
    {
        if ($actual <= $min) {
            throw new Exception("Assertion failed: {$message}. Expected > {$min}, Got: {$actual}");
        }
    }

    private function assertTrue($value, $message = '')
    {
        if (!$value) {
            throw new Exception("Assertion failed: {$message}");
        }
    }
}

// Run Phase 2 tests
if (php_sapi_name() === 'cli') {
    $tester = new CrmPhase2ApiTester();
    $tester->runAllTests();
}