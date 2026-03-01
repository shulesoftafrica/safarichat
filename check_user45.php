<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$instances = \App\Models\WhatsappInstance::where('user_id', 45)->get(['id', 'connect_status', 'status']);
echo "Total instances for user 45: " . $instances->count() . "\n\n";
foreach($instances as $i) {
    echo "ID: {$i->id} | connect_status: {$i->connect_status} | status: {$i->status}\n";
}

echo "\n--- Query Test: connect_status != 'ready' ---\n";
$disconnected = \App\Models\WhatsappInstance::where('user_id', 45)->where('connect_status', '!=', 'ready')->get(['id', 'connect_status']);
echo "Disconnected instances: " . $disconnected->count() . "\n";
foreach($disconnected as $d) {
    echo "ID: {$d->id} | connect_status: {$d->connect_status}\n";
}
