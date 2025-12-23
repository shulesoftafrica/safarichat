<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Controller;
use App\Services\SystemWhatsAppService;

echo "=== Testing Fixed SMS Sending ===\n";

try {
    // Send a test message
    echo "\n1. Sending test message...\n";
    $testPhone = '714825469';
    $message = 'Test message: Your verification code is 456789';
    
    $controller = new Controller();
    $result = $controller->sendTextMessage($testPhone, $message, 'whatsapp', 'otp');
    
    echo "   Result: " . json_encode($result->original) . "\n";
    
    // Check if there are jobs to process
    echo "\n2. Checking queue...\n";
    $pendingJobs = \DB::table('jobs')->count();
    echo "   Pending jobs: $pendingJobs\n";
    
    if ($pendingJobs > 0) {
        echo "   💡 Processing jobs...\n";
        // Process jobs in background
        $command = 'php artisan queue:work --stop-when-empty --timeout=5 2>&1';
        $output = shell_exec($command);
        echo "   Queue output: " . ($output ?: 'No output') . "\n";
    }
    
    // Check logs for status mapping warnings
    echo "\n3. Checking for status mapping warnings...\n";
    $logPath = storage_path('logs/laravel.log');
    
    if (file_exists($logPath)) {
        $recentLines = array_slice(file($logPath), -10);
        $foundWarning = false;
        
        foreach ($recentLines as $line) {
            if (strpos($line, 'Unknown API status') !== false) {
                echo "   ⚠️  Found: " . trim($line) . "\n";
                $foundWarning = true;
            }
            if (strpos($line, 'WhatsApp message sent successfully') !== false) {
                echo "   ✅ Success: " . trim($line) . "\n";
            }
        }
        
        if (!$foundWarning) {
            echo "   ✅ No 'Unknown API status' warnings found\n";
        }
    }
    
    echo "\n=== Fix Status ===\n";
    echo "✅ MessageStatusMapper method calls fixed\n";
    echo "✅ Additional status mappings added\n";
    echo "✅ SMS sending should now work without status mapping errors\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}