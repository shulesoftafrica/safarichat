<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔍 Checking System Instance Owner User\n";
echo "=====================================\n\n";

$systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
if ($systemInstance) {
    echo "System Instance User ID: {$systemInstance->user_id}\n";
    $user = \App\Models\User::find($systemInstance->user_id);
    if ($user) {
        echo "User Name: {$user->name}\n";
        echo "User UUID: " . ($user->uuid ?: 'NULL') . "\n";
        echo "User ID: {$user->id}\n";
        echo "User Username: " . ($user->username ?: 'NULL') . "\n";
    } else {
        echo "❌ User not found!\n";
    }
} else {
    echo "❌ System instance not found!\n";
}