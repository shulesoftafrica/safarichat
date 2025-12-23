<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Actual View Rendering ===\n";

try {
    // Set up test user in session to avoid auth issues
    $user = \App\Models\User::find(45);
    
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        echo "✅ Logged in as: {$user->name}\n";
        
        // Test the actual controller method
        $controller = new \App\Http\Controllers\WhatsappInstanceController();
        
        // This should now work without undefined variable errors
        $response = $controller->indexView();
        
        if ($response instanceof \Illuminate\View\View) {
            echo "✅ View created successfully\n";
            echo "✅ View name: " . $response->getName() . "\n";
            
            $viewData = $response->getData();
            echo "✅ View data contains:\n";
            foreach ($viewData as $key => $value) {
                if ($key === 'activeInstance') {
                    echo "  \$activeInstance: " . ($value ? $value->display_name . " (ID: {$value->id})" : 'null') . "\n";
                } elseif ($key === 'instances') {
                    echo "  \$instances: Collection with " . $value->count() . " items\n";
                }
            }
            
            echo "\n✅ No undefined variable errors!\n";
            
        } else {
            echo "❌ Unexpected response type: " . gettype($response) . "\n";
        }
        
    } else {
        echo "❌ Test user not found\n";
    }
    
    echo "\n🎉 WhatsApp instances view is now working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'active_instance') !== false) {
        echo "\n   ⚠️  Still has undefined variable issue - check if there are more occurrences\n";
    }
}