<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Lead;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use App\Models\BusinessContact;

echo "Checking leads with existing messages:\n\n";

// Get recent leads
$leads = Lead::where('status', 'new')
    ->whereNotNull('business_contact_id')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($leads as $lead) {
    $contact = $lead->contact;
    if (!$contact || !$contact->guest_phone) continue;
    
    $phone = $contact->guest_phone;
    $incoming = IncomingMessage::where('phone_number', $phone)->count();
    $outgoing = OutgoingMessage::where('phone_number', $phone)->count();
    $conversations = $lead->conversations()->count();
    
    if ($incoming > 0 || $outgoing > 0) {
        echo "Lead ID: {$lead->id}\n";
        echo "Phone: {$phone}\n";
        echo "Name: {$lead->company_name}\n";
        echo "Status: {$lead->status}\n";
        echo "Incoming Messages: {$incoming}\n";
        echo "Outgoing Messages: {$outgoing}\n";
        echo "Conversations: {$conversations}\n";
        echo "Created: {$lead->created_at}\n";
        echo "---\n\n";
    }
}

echo "\n\nChecking if contacts have messages BEFORE lead creation:\n\n";

$contacts = BusinessContact::whereDoesntHave('leads')
    ->whereNotNull('guest_phone')
    ->limit(10)
    ->get();

foreach ($contacts as $contact) {
    $phone = $contact->guest_phone;
    $incoming = IncomingMessage::where('phone_number', $phone)->count();
    $outgoing = OutgoingMessage::where('phone_number', $phone)->count();
    
    if ($incoming > 0 || $outgoing > 0) {
        echo "Contact ID: {$contact->id}\n";
        echo "Phone: {$phone}\n";
        echo "Name: {$contact->guest_name}\n";
        echo "Incoming Messages: {$incoming}\n";
        echo "Outgoing Messages: {$outgoing}\n";
        echo "Created: {$contact->created_at}\n";
        echo "---\n\n";
    }
}
