<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use App\Services\WaSenderService;

echo "=== Manual Outreach Test ===\n\n";

try {
    // Get a lead and agent
    $lead = Lead::with(['contact', 'aiSalesAgent'])->where('status', 'NEW')->first();
    $agent = $lead->aiSalesAgent;
    
    echo "Testing with:\n";
    echo "  Lead ID: {$lead->id}\n";
    echo "  Phone: {$lead->contact->guest_phone}\n";
    echo "  Agent: {$agent->assistant_name}\n";
    echo "  User ID: {$agent->user_id}\n\n";
    
    // Check WhatsApp instance
    $instance = \App\Models\WhatsappInstance::where('user_id', $agent->user_id)
                                           ->where('status', 'connected')
                                           ->first();
    
    if (!$instance) {
        echo "❌ No connected WhatsApp instance found for user {$agent->user_id}\n";
        exit;
    }
    
    echo "WhatsApp Instance: {$instance->instance_name} ({$instance->status})\n\n";
    
    // Create services
    $openAiService = new OpenAiService();
    $waSenderService = new WaSenderService();
    $aiWhatsAppService = new AiWhatsAppService($openAiService, $waSenderService);
    
    // Test message generation
    $message = "Hi! This is a test outreach message from {$agent->assistant_name}.";
    echo "Test Message: {$message}\n\n";
    
    // Test sending
    echo "Attempting to send outreach message...\n";
    $result = $aiWhatsAppService->sendOutreachMessage($lead, $message, $agent);
    
    if ($result['success']) {
        echo "✅ SUCCESS!\n";
        echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        
        // Check if lead status was updated
        $lead->refresh();
        echo "Lead status after outreach: {$lead->status}\n";
        echo "Last contact: {$lead->last_contact_at}\n";
    } else {
        echo "❌ FAILED!\n";
        echo "Error: " . $result['error'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}