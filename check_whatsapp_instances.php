<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking WhatsApp Instance Availability ===\n";

// Check available WhatsApp instances
$instances = App\Models\WhatsappInstance::select('id', 'user_id', 'instance_name', 'instance_id', 'status')
    ->get();

echo sprintf("Total WhatsApp instances: %d\n", $instances->count());

foreach($instances as $instance) {
    echo sprintf("Instance %d: User %d | Name: %s | ID: %s | Status: %s\n",
        $instance->id,
        $instance->user_id,
        $instance->instance_name ?? 'NULL',
        $instance->instance_id ?? 'NULL', 
        $instance->status
    );
}

$connectedInstances = $instances->where('status', 'connected');
echo sprintf("\nConnected instances: %d\n", $connectedInstances->count());

// Check conversation details
$conversation = App\Models\Conversation::with(['lead.contact', 'lead.aiSalesAgent'])
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->first();

if ($conversation) {
    echo "\nChecking conversation {$conversation->id}:\n";
    echo sprintf("  Lead ID: %s\n", $conversation->lead_id ?? 'NULL');
    
    if ($conversation->lead) {
        echo sprintf("  Lead User ID: %s\n", $conversation->lead->user_id ?? 'NULL');
        echo sprintf("  Business Contact ID: %s\n", $conversation->lead->business_contact_id ?? 'NULL');
        
        if ($conversation->lead->contact) {
            echo sprintf("  Contact Phone: %s\n", $conversation->lead->contact->guest_phone ?? 'NULL');
        } else {
            echo "  No business contact found\n";
        }
        
        if ($conversation->lead->aiSalesAgent) {
            echo sprintf("  AI Agent User ID: %s\n", $conversation->lead->aiSalesAgent->user_id ?? 'NULL');
        } else {
            echo "  No AI sales agent found\n";
        }
        
        // Check if there's a connected instance for this user
        $userId = $conversation->lead->aiSalesAgent?->user_id ?? $conversation->lead->user_id ?? null;
        if ($userId) {
            $userInstance = App\Models\WhatsappInstance::where('user_id', $userId)
                                                       ->where('status', 'connected')
                                                       ->first();
            echo sprintf("  User %d connected instance: %s\n", $userId, $userInstance ? "YES (ID: {$userInstance->id})" : 'NO');
        }
    } else {
        echo "  No lead found\n";
    }
} else {
    echo "\nNo conversations with pending followups found\n";
}