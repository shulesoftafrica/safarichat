<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Replace with actual user ID
$userId = 1;

$user = \App\Models\User::find($userId);

if (!$user) {
    echo "Error: User with ID {$userId} not found.\n";
    exit(1);
}

$token = $user->createToken('postman-test')->plainTextToken;

echo "\n=== API Token Generated ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n";
echo "Token: {$token}\n";
echo "\nAdd this header to Postman:\n";
echo "Authorization: Bearer {$token}\n";
echo "Accept: application/json\n";
echo "Content-Type: application/json\n";
echo "\n";
