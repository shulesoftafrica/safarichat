<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Models\BusinessContact;

echo "=== Lead and Agent Analysis ===\n\n";

// Check leads with their contacts
$leads = Lead::with(['contact', 'aiSalesAgent'])->get();

echo "Leads with Contact Info:\n";
foreach($leads as $lead) {
    echo "Lead ID: {$lead->id}\n";
    echo "  Status: {$lead->status}\n";
    echo "  Name: " . ($lead->name ?: 'No name') . "\n";
    echo "  Contact ID: " . ($lead->events_guest_id ?: 'No contact ID') . "\n";
    
    if($lead->contact) {
        echo "  Contact Phone: " . ($lead->contact->guest_phone ?: 'No phone') . "\n";
        echo "  Contact Name: " . ($lead->contact->guest_name ?: 'No name') . "\n";
    } else {
        echo "  Contact: Not found\n";
    }
    
    echo "  AI Agent ID: " . ($lead->ai_sales_agent_id ?: 'No agent') . "\n";
    echo "  User ID: " . ($lead->user_id ?: 'No user') . "\n";
    echo "  Last Contact: " . ($lead->last_contact_at ?: 'Never') . "\n";
    echo "  Created: " . $lead->created_at . "\n";
    echo "\n";
}

// Check AI agents
echo "Active AI Sales Agents:\n";
$agents = AiSalesAgent::where('status', 'active')->get();
foreach($agents as $agent) {
    echo "Agent ID: {$agent->id}\n";
    echo "  Name: {$agent->assistant_name}\n";
    echo "  User ID: {$agent->user_id}\n";
    echo "  Status: {$agent->status}\n";
    echo "  Always Available: " . ($agent->always_available ? 'Yes' : 'No') . "\n";
    echo "\n";
}

// Check WhatsApp instances
echo "WhatsApp Instances:\n";
$instances = \App\Models\WhatsappInstance::get();
foreach($instances as $instance) {
    echo "Instance ID: {$instance->id}\n";
    echo "  Instance Name: {$instance->instance_name}\n";
    echo "  User ID: {$instance->user_id}\n";
    echo "  Status: {$instance->status}\n";
    echo "  Phone: " . ($instance->phone_number ?: 'No phone') . "\n";
    echo "\n";
}