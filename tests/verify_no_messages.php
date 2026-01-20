<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$testPhones = [
    '+255752840884',
    '+255767588820',
    '+255746384569',
    '+255754041875',
    '+255762559781'
];

echo "Checking if selected leads have existing messages:\n\n";

foreach ($testPhones as $phone) {
    $count = DB::table('outgoing_messages')->where('phone_number', $phone)->count();
    echo "{$phone}: {$count} outgoing messages\n";
}
