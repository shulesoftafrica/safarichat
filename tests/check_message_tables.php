<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BusinessContact;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;

echo "Checking IncomingMessage table structure:\n";
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'incoming_messages' AND table_schema = 'safarichat'");
foreach ($cols as $c) {
    echo "  - {$c->column_name}\n";
}

echo "\nChecking OutgoingMessage table structure:\n";
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'outgoing_messages' AND table_schema = 'safarichat'");
foreach ($cols as $c) {
    echo "  - {$c->column_name}\n";
}

echo "\n\nChecking contacts with messages:\n";
$contact = BusinessContact::whereNotNull('guest_phone')->first();
if ($contact) {
    echo "Contact ID: {$contact->id}\n";
    echo "Phone: {$contact->guest_phone}\n";
    
    // Check via relationship
    $incomingViaRelation = $contact->incomingMessages()->count();
    $outgoingViaRelation = $contact->outgoingMessages()->count();
    
    // Check via phone number
    $incomingViaPhone = IncomingMessage::where('phone_number', $contact->guest_phone)->count();
    $outgoingViaPhone = OutgoingMessage::where('phone_number', $contact->guest_phone)->count();
    
    echo "Incoming via relationship (business_contact_id): {$incomingViaRelation}\n";
    echo "Incoming via phone number: {$incomingViaPhone}\n";
    echo "Outgoing via relationship (business_contact_id): {$outgoingViaRelation}\n";
    echo "Outgoing via phone number: {$outgoingViaPhone}\n";
}
