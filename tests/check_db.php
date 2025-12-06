<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Check ===\n\n";

echo "Users:\n";
$users = \App\Models\User::select('id', 'name', 'email')->get();
foreach ($users as $user) {
    echo "- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
}

echo "\nWhatsApp Instances:\n";
$instances = \App\Models\WhatsappInstance::select('id', 'user_id', 'instance_id', 'api_key', 'status', 'connect_status')->get();
foreach ($instances as $instance) {
    echo "- ID: {$instance->id}, User ID: {$instance->user_id}, Instance: {$instance->instance_id}\n";
    echo "  Status: {$instance->status}, Connect: {$instance->connect_status}\n";
    echo "  API Key: " . (empty($instance->api_key) ? 'EMPTY' : 'SET (' . strlen($instance->api_key) . ' chars)') . "\n\n";
}