<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Request::capture();
$response = $kernel->handle($request);

echo "🔍 Debugging System WhatsApp Message Sending\n";
echo "=============================================\n\n";

try {
    echo "1. Testing system instance basic operations...\n";
    
    $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
    if (!$systemInstance) {
        echo "❌ No system instance found\n";
        exit(1);
    }
    
    echo "✅ System instance found: ID {$systemInstance->id}\n";
    echo "   Phone: {$systemInstance->phone_number}\n";
    echo "   Status: {$systemInstance->status}\n";
    echo "   Connect Status: {$systemInstance->connect_status}\n\n";
    
    echo "2. Testing message type validation...\n";
    $canSendNotification = $systemInstance->canSendMessageType('system_notification');
    echo "Can send system_notification: " . ($canSendNotification ? "✅ YES" : "❌ NO") . "\n\n";
    
    echo "3. Testing SystemMessageLog creation...\n";
    try {
        $testLog = \App\Models\SystemMessageLog::logMessage(
            $systemInstance->id,
            '+255700111222',
            'system_notification',
            'Test message for debugging',
            'queued'
        );
        echo "✅ SystemMessageLog created: ID {$testLog->id}\n";
    } catch (Exception $e) {
        echo "❌ SystemMessageLog creation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Testing OutgoingMessage creation...\n";
    try {
        $testOutgoing = \App\Models\OutgoingMessage::create([
            'phone_number' => '+255700111333',
            'message' => 'Debug test message',
            'user_id' => $systemInstance->user_id,
            'whatsapp_instance_id' => $systemInstance->id,
            'message_type' => 'system_notification',
            'is_system_message' => true,
            'status' => 'queued',
            'created_at' => now()
        ]);
        echo "✅ OutgoingMessage created: ID {$testOutgoing->id}\n";
    } catch (Exception $e) {
        echo "❌ OutgoingMessage creation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Testing job dispatch (dry run)...\n";
    try {
        // Check if SendWhatsAppMessage job exists
        $jobClass = 'App\\Jobs\\SendWhatsAppMessage';
        if (class_exists($jobClass)) {
            echo "✅ SendWhatsAppMessage job class exists\n";
            
            // Try to create job instance (but don't dispatch)
            $job = new $jobClass(
                '+255700111444',
                'Test job message',
                null, // No user_id for system messages
                $systemInstance->id,
                'system_notification'
            );
            echo "✅ SendWhatsAppMessage job instance created successfully\n";
        } else {
            echo "❌ SendWhatsAppMessage job class not found\n";
        }
    } catch (Exception $e) {
        echo "❌ Job creation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Manual sendSystemMessage test...\n";
    try {
        $systemService = app('App\\Services\\SystemWhatsAppService');
        
        // Create a reflection to access private method
        $reflection = new ReflectionClass($systemService);
        $method = $reflection->getMethod('sendSystemMessage');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($systemService, [
            '+255700111555', 
            'Manual test message',
            'system_notification'
        ]);
        
        echo "sendSystemMessage result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        
    } catch (Exception $e) {
        echo "❌ Manual sendSystemMessage failed: " . $e->getMessage() . "\n";
        echo "   Error details: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n7. Check Laravel queue configuration...\n";
    $queueDriver = config('queue.default');
    echo "Queue driver: {$queueDriver}\n";
    
    if ($queueDriver === 'sync') {
        echo "✅ Using sync driver - jobs run immediately\n";
    } else {
        echo "ℹ️  Using {$queueDriver} driver - jobs may be queued\n";
    }
    
    echo "\n✨ Debugging Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Debug Failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}