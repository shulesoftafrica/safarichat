<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Message;

// Authenticate as user 45
Auth::loginUsingId(45);

echo "=== Testing Form Submission Simulation ===\n";
echo "User ID: " . Auth::id() . "\n";
echo "User Event: " . Auth::user()->event->id . "\n";

$controller = new Message();

// Test different criteria
echo "\n=== Testing Criteria 1 (All Users) ===\n";
$users1 = $controller->getUserByCriteria(1, Auth::user()->event->id);
echo "Result type: " . gettype($users1) . "\n";
echo "Result count: " . (is_array($users1) ? count($users1) : $users1->count()) . "\n";

echo "\n=== Testing Criteria 6 (Custom Numbers) ===\n";
// Simulate request with custom numbers
$_REQUEST['custom_numbers'] = '0714825469,254700000000';
$users6 = $controller->getUserByCriteria(6, Auth::user()->event->id);
echo "Result type: " . gettype($users6) . "\n";
if (is_object($users6)) {
    // Convert object to array to count
    $arrayUsers = json_decode(json_encode($users6), true);
    echo "Result count: " . count($arrayUsers) . "\n";
    echo "First user: " . json_encode($arrayUsers[0] ?? []) . "\n";
} else {
    echo "Result count: " . count($users6) . "\n";
}