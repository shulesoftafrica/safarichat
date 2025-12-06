<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Message;

// Authenticate as user 45
Auth::loginUsingId(45);

echo "=== FINAL TEST: Complete Message Flow ===\n";

$controller = new Message();

// Test with custom numbers
$requestData = [
    'criteria' => '6',
    'custom_numbers' => '0714825469,254700000000',
    'message' => 'Test message - custom numbers',
    'source' => ['whatsapp'],
];

$request = Request::create('/', 'POST', $requestData);
app()->instance('request', $request);

echo "Testing Custom Numbers (Criteria 6):\n";
$users = $controller->getUserByCriteria(6, Auth::user()->event->id, $request);
echo "Users returned: " . count($users) . "\n";
foreach ($users as $user) {
    echo "- Phone: " . $user['guest_phone'] . "\n";
}

echo "\nTesting All Contacts (Criteria 1):\n";
$users1 = $controller->getUserByCriteria(1, Auth::user()->event->id, $request);
echo "Users returned: " . $users1->count() . "\n";
foreach ($users1->take(3) as $user) {
    echo "- Name: " . $user->guest_name . ", Phone: " . $user->guest_phone . "\n";
}

echo "\n=== Testing Message Queue Logic ===\n";
// Check if queue logic works with these users
$userCount = count($users);
echo "Custom numbers user count: $userCount\n";

if ($userCount > 0) {
    echo "✅ Custom numbers will now work!\n";
} else {
    echo "❌ Still has issues\n";
}

echo "\n=== Testing Phone Validation ===\n";
foreach ($users as $user) {
    $user = (object) $user;
    $phoneNumber = validate_phone_number($user->guest_phone);
    if (is_array($phoneNumber)) {
        echo "✅ Phone {$user->guest_phone} validated to: {$phoneNumber[1]}\n";
    } else {
        echo "❌ Phone {$user->guest_phone} validation failed\n";
    }
}