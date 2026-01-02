<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\BusinessContact;

echo "=== Lead Count Inconsistency Analysis ===\n\n";

// Get authenticated user (simulate authentication for the first user)
$user = \App\Models\User::first();
if (!$user) {
    echo "No users found!\n";
    exit;
}

echo "Analyzing data for User: {$user->name} (ID: {$user->id})\n\n";

// 1. Check leads table directly
$totalLeads = Lead::where('user_id', $user->id)->count();
echo "📊 CUSTOMER LIST PAGE (Guest Controller):\n";
echo "   Direct Lead count: {$totalLeads}\n\n";

// 2. Check lead_products relationships
$totalLeadProducts = LeadProduct::whereHas('lead', function($query) use ($user) {
    $query->where('user_id', $user->id);
})->count();
echo "📦 PRODUCT MANAGEMENT PAGE (Product Controller):\n";
echo "   Lead-Product relationships: {$totalLeadProducts}\n\n";

// 3. Check product-specific counts
echo "📋 DETAILED BREAKDOWN:\n";
$products = Product::where('user_id', $user->id)
                  ->withCount('leadProducts')
                  ->get();

$totalProductLeads = 0;
foreach ($products as $product) {
    echo "   Product: {$product->name} = {$product->lead_products_count} leads\n";
    $totalProductLeads += $product->lead_products_count;
}
echo "   Total from products: {$totalProductLeads}\n\n";

// 4. Check if same leads are associated with multiple products
echo "🔍 RELATIONSHIP ANALYSIS:\n";
$leadsWithMultipleProducts = Lead::where('user_id', $user->id)
                                 ->withCount('leadProducts')
                                 ->get();

$leadsWithMultiple = 0;
foreach ($leadsWithMultipleProducts as $lead) {
    if ($lead->lead_products_count > 1) {
        $leadsWithMultiple++;
        echo "   Lead ID {$lead->id}: {$lead->lead_products_count} products\n";
    }
}

echo "   Leads with multiple products: {$leadsWithMultiple}\n\n";

// 5. Check for orphaned data
echo "🚨 DATA INTEGRITY CHECK:\n";

// Check for leads without products
$leadsWithoutProducts = Lead::where('user_id', $user->id)
                           ->whereDoesntHave('leadProducts')
                           ->count();
echo "   Leads without products: {$leadsWithoutProducts}\n";

// Check for lead_products without valid leads
$orphanedLeadProducts = LeadProduct::whereDoesntHave('lead')->count();
echo "   Orphaned lead-products: {$orphanedLeadProducts}\n";

// Check for lead_products with leads from other users
$crossUserLeadProducts = LeadProduct::whereHas('lead', function($query) use ($user) {
    $query->where('user_id', '!=', $user->id);
})->count();
echo "   Cross-user lead-products: {$crossUserLeadProducts}\n\n";

// 6. Check business contacts vs leads
echo "📞 CONTACT vs LEAD COMPARISON:\n";
if ($user->business) {
    $totalContacts = BusinessContact::where('business_id', $user->business->id)->count();
    echo "   Total contacts (BusinessContact): {$totalContacts}\n";
    echo "   Total leads (Lead): {$totalLeads}\n";
    echo "   Difference: " . ($totalContacts - $totalLeads) . "\n\n";
}

// 7. Summary and recommendation
echo "📝 SUMMARY:\n";
echo "   Customer List shows: {$totalLeads} leads\n";
echo "   Product Management shows: {$totalProductLeads} lead relationships\n";
echo "   Discrepancy: " . abs($totalLeads - $totalProductLeads) . "\n\n";

if ($totalLeads != $totalProductLeads) {
    echo "❌ INCONSISTENCY FOUND!\n";
    if ($totalProductLeads > $totalLeads) {
        echo "   Product page shows MORE because leads can have multiple products.\n";
        echo "   Each lead-product relationship is counted separately.\n";
    } else {
        echo "   Product page shows LESS - possible data integrity issue.\n";
    }
} else {
    echo "✅ COUNTS ARE CONSISTENT\n";
}

echo "\n💡 SOLUTION:\n";
echo "   Product management should count DISTINCT leads, not lead-product relationships.\n";