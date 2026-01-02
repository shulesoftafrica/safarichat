<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Product Controller Fix ===\n";

// Test for user ID 45 (Ephraim Swilla) who has the data
$userId = 45;

echo "Testing for User ID: $userId\n\n";

// Test the new query structure
$products = App\Models\Product::with('faqs')
    ->withCount([
        'leadProducts as lead_products_count',
        'leadProducts as distinct_leads_count' => function ($query) {
            $query->selectRaw('COUNT(DISTINCT lead_id)');
        }
    ])
    ->where('user_id', $userId)
    ->get();

echo "=== Product Analysis ===\n";
foreach($products as $product) {
    echo "Product: {$product->name}\n";
    echo "  Lead-Product relationships: {$product->lead_products_count}\n";
    echo "  Distinct leads: {$product->distinct_leads_count}\n";
    echo "  Product ID: {$product->id}\n\n";
}

// Verify with direct database query
echo "=== Direct Database Verification ===\n";
$totalProducts = App\Models\Product::where('user_id', $userId)->count();
echo "Total products for user: $totalProducts\n";

$totalLeads = App\Models\Lead::where('user_id', $userId)->count();
echo "Total leads for user: $totalLeads\n";

$totalRelationships = DB::table('lead_products as lp')
    ->join('products as p', 'lp.product_id', '=', 'p.id')
    ->where('p.user_id', $userId)
    ->count();
echo "Total lead-product relationships: $totalRelationships\n";

$distinctLeadsWithProducts = DB::table('lead_products as lp')
    ->join('products as p', 'lp.product_id', '=', 'p.id')
    ->where('p.user_id', $userId)
    ->distinct('lp.lead_id')
    ->count();
echo "Distinct leads with products: $distinctLeadsWithProducts\n";

// Now the counts should be consistent
echo "\n=== Expected Results ===\n";
echo "Product Management should now show: $distinctLeadsWithProducts distinct leads\n";
echo "Customer List shows: $totalLeads total leads\n";
echo "Difference should be: " . ($totalLeads - $distinctLeadsWithProducts) . " (leads without products)\n";