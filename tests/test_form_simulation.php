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

echo "=== Simulating Actual Form Request ===\n";

$controller = new Message();

// Create a mock request object
$requestData = [
    'criteria' => '6', // Custom numbers
    'custom_numbers' => '0714825469,254700000000',
    'message' => 'Test message from simulation',
    'source' => ['whatsapp'],
];

// Create request instance
$request = Request::create('/', 'POST', $requestData);

echo "Request data:\n";
var_dump($request->all());

echo "\n=== Testing getUserByCriteria with Request ===\n";
$users = $controller->getUserByCriteria(6, Auth::user()->event->id, $request);

echo "Result type: " . gettype($users) . "\n";

if (is_object($users)) {
    // Handle the object returned by criteria 6
    $usersArray = is_array($users) ? $users : (array) $users;
    echo "Users object content:\n";
    var_dump($usersArray);
    
    // Check if it's the expected object structure
    if (isset($usersArray[0])) {
        echo "First user phone: " . ($usersArray[0]['guest_phone'] ?? 'NOT SET') . "\n";
    }
}

echo "\n=== Testing Global Request Function ===\n";
// Set global request data
app()->instance('request', $request);

$users2 = $controller->getUserByCriteria(6, Auth::user()->event->id, $request);
echo "Second test result type: " . gettype($users2) . "\n";
if (is_object($users2)) {
    $users2Array = (array) $users2;
    echo "Second test content:\n";
    var_dump($users2Array);
}