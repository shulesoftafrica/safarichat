<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$token = config('services.billing.access_token');

echo "Token Length: " . strlen($token) . "\n";
echo "Token: [$token]\n";
echo "Has newline: " . (str_contains($token, "\n") ? 'YES' : 'NO') . "\n";
echo "Has carriage return: " . (str_contains($token, "\r") ? 'YES' : 'NO') . "\n";
echo "Token (hex): " . bin2hex($token) . "\n";
