<?php

require_once 'vendor/autoload.php';

use App\Jobs\SendWhatsAppMessage;
use App\Models\User;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔍 Debugging SendWhatsAppMessage Job Parameters\n";
echo "===============================================\n\n";

// Get system instance
$systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
echo "1. System Instance Details:\n";
echo "   ID: {$systemInstance->id}\n";
echo "   User ID: {$systemInstance->user_id}\n";

// Get the user
$user = User::find($systemInstance->user_id);
echo "\n2. System User Details:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   UUID: " . ($user->uuid ?: 'NULL') . "\n";

// Test the schema name logic from the job
$schemaName = $user ? ($user->uuid ?? $user->id) : 'default';
echo "\n3. Schema Name Logic Test:\n";
echo "   User exists: " . ($user ? 'YES' : 'NO') . "\n";
echo "   User UUID: " . ($user->uuid ?: 'NULL') . "\n";
echo "   User ID: {$user->id}\n";
echo "   Calculated schema_name: {$schemaName}\n";

// Check what parameters would be passed to the job
echo "\n4. Job Parameters Test:\n";
echo "   phoneNumber: +255700999999\n";
echo "   message: Test message\n";
echo "   userId: {$systemInstance->user_id}\n";
echo "   instanceId: {$systemInstance->id}\n";
echo "   messageType: system_notification\n";

// Test User::find with the system user ID
echo "\n5. User::find() Test:\n";
$testUser = User::find($systemInstance->user_id);
if ($testUser) {
    echo "   ✅ User::find({$systemInstance->user_id}) successful\n";
    echo "   UUID: " . ($testUser->uuid ?: 'NULL') . "\n";
} else {
    echo "   ❌ User::find({$systemInstance->user_id}) failed\n";
}

echo "\n✨ Debug Complete!\n";