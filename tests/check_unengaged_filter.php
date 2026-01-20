<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BusinessContact;
use App\Models\Business;

echo "=== Checking Unengaged Contacts Filter ===\n\n";

// Get contacts without leads and contacted_for_sales = false
$contacts = BusinessContact::whereDoesntHave('leads')
    ->where('contacted_for_sales', false)
    ->where('created_at', '<=', now()->subDays(3))
    ->whereNotNull('guest_phone')
    ->where('guest_phone', '!=', '')
    ->whereNotNull('guest_name')
    ->where('guest_name', '!=', '')
    ->get();

echo "Contacts matching most filters: " . $contacts->count() . "\n\n";

// Group by business_id
$byBusiness = $contacts->groupBy('business_id');

echo "Business breakdown:\n";
foreach ($byBusiness as $businessId => $businessContacts) {
    if ($businessId === '' || $businessId === null) {
        echo "  Business ID EMPTY/NULL: {$businessContacts->count()} contacts - INVALID!\n";
        continue;
    }
    $business = Business::find($businessId);
    $hasUserId = $business && $business->user_id ? 'YES (user_id=' . $business->user_id . ')' : 'NO (user_id=NULL)';
    echo "  Business {$businessId}: {$businessContacts->count()} contacts - Has user_id: {$hasUserId}\n";
}

echo "\n=== Final Query (with business.user_id filter) ===\n";
$final = BusinessContact::whereDoesntHave('leads')
    ->where('contacted_for_sales', false)
    ->where('created_at', '<=', now()->subDays(3))
    ->whereNotNull('guest_phone')
    ->where('guest_phone', '!=', '')
    ->whereNotNull('guest_name')
    ->where('guest_name', '!=', '')
    ->whereHas('business', function($query) {
        $query->whereNotNull('user_id');
    })
    ->count();

echo "Final count with business.user_id NOT NULL filter: {$final}\n";
