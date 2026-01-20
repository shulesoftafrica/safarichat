<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Lead;
use App\Models\AiSalesAgent;

echo "Checking leads for outreach:\n\n";

$agent = AiSalesAgent::where('is_active', true)
    ->where('allow_outreach', true)
    ->first();

if (!$agent) {
    echo "No active agent found\n";
    exit;
}

echo "Agent: {$agent->name}\n";
echo "User ID: {$agent->user_id}\n\n";

$leads = Lead::where('ai_sales_agent_id', $agent->id)
    ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_OUTREACHED])
    ->whereNotIn('status', [Lead::STATUS_DO_NOT_CONTACT, Lead::STATUS_CLOSED])
    ->where(function($query) {
        $query->whereNull('last_contact_at')
            ->orWhere('last_contact_at', '<', now()->subDays(1));
    })
    ->where('lead_score', '>', 0)
    ->orderByDesc('lead_score')
    ->orderBy('created_at')
    ->limit(10)
    ->get();

echo "Found {$leads->count()} leads\n\n";

foreach ($leads as $lead) {
    $contact = $lead->contact;
    $phone = $contact ? $contact->guest_phone : 'NO CONTACT';
    
    echo "Lead ID: {$lead->id}\n";
    echo "  Name: {$lead->company_name}\n";
    echo "  Phone: {$phone}\n";
    echo "  Status: {$lead->status}\n";
    echo "  Score: {$lead->lead_score}\n";
    echo "  Last contact: {$lead->last_contact_at}\n";
    
    if ($contact) {
        $incoming = \App\Models\IncomingMessage::where('phone_number', $phone)->count();
        $outgoing = \App\Models\OutgoingMessage::where('phone_number', $phone)->count();
        echo "  Incoming msgs: {$incoming}\n";
        echo "  Outgoing msgs: {$outgoing}\n";
    }
    
    echo "\n";
}
