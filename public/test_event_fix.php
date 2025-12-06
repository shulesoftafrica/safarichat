<?php
/**
 * Simple test to dispatch a WhatsApp message job and see if the event_id fix works
 */

// Simple test that can be run via web route
if (php_sapi_name() !== 'cli') {
    echo "<h2>Testing Event ID Fix</h2>";
    echo "<p>This test will try to send a WhatsApp message and check if the event_id issue is resolved.</p>";
    
    try {
        // Get the authenticated user
        $user = auth()->user();
        if (!$user) {
            echo "<p style='color: red;'>❌ Please log in to run this test</p>";
            return;
        }
        
        echo "<p>✅ User authenticated: {$user->id}</p>";
        
        // Try to create a test contact using the fixed service
        $contactData = [
            'phone' => '+254700000' . rand(100, 999),
            'name' => 'Event ID Fix Test',
            'user_id' => $user->id
        ];
        
        echo "<p>🧪 Testing contact creation with user ID: {$user->id}</p>";
        
        // Use the UserResolutionService to create a contact
        $contact = \App\Services\UserResolutionService::resolveOrCreateContact($contactData);
        
        if ($contact && $contact->event_id) {
            echo "<p style='color: green;'>✅ SUCCESS: Contact created successfully!</p>";
            echo "<p>Contact ID: {$contact->id}</p>";
            echo "<p>Event ID: {$contact->event_id}</p>";
            echo "<p>Phone: {$contact->guest_phone}</p>";
            echo "<p>Name: {$contact->guest_name}</p>";
            
            // Now try to dispatch a WhatsApp message job
            $messageData = [
                'phone' => $contact->guest_phone,
                'message' => 'Test message to verify event_id fix is working',
                'user_id' => $user->id,
                'priority' => 'normal'
            ];
            
            echo "<p>📱 Attempting to dispatch WhatsApp message job...</p>";
            
            $job = new \App\Jobs\SendWhatsAppMessage($messageData);
            \Illuminate\Support\Facades\Queue::push($job);
            
            echo "<p style='color: green;'>✅ Message job dispatched successfully!</p>";
            echo "<p>The event_id foreign key violation issue appears to be fixed.</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create contact</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Stack trace:</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
} else {
    echo "This test script should be accessed via web interface while logged in.\n";
}
?>