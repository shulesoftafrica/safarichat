<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = 45;
$user = \App\Models\User::find($userId);

if (!$user) {
    echo "Error: User with ID {$userId} not found.\n";
    exit(1);
}

$token = $user->createToken('crm-test')->plainTextToken;

echo "\n=== API Token for User 45 ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n";
echo "Token: {$token}\n\n";
