<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Overall System Lead Analysis ===\n";

// Total leads
$totalLeads = App\Models\Lead::count();
echo "Total Leads in system: $totalLeads\n";

// Total lead-product relationships
$totalRelationships = DB::table('lead_products')->count();
echo "Total Lead-Product relationships: $totalRelationships\n";

// Users with leads
$usersWithLeads = DB::table('leads')->distinct('user_id')->count('user_id');
echo "Users with leads: $usersWithLeads\n";

// Users with products
$usersWithProducts = DB::table('products')->distinct('user_id')->count('user_id');  
echo "Users with products: $usersWithProducts\n";

// Cross-user relationships
$crossUserRelations = DB::table('lead_products as lp')
    ->join('leads as l', 'lp.lead_id', '=', 'l.id')
    ->join('products as p', 'lp.product_id', '=', 'p.id')
    ->whereColumn('l.user_id', '!=', 'p.user_id')
    ->count();
echo "Cross-user lead-product relationships: $crossUserRelations\n";

// Sample of users and their counts
echo "\n=== User-specific Analysis ===\n";
$users = DB::table('users')->select('id', 'name')->take(5)->get();
foreach($users as $user) {
    $leadCount = App\Models\Lead::where('user_id', $user->id)->count();
    $productCount = App\Models\Product::where('user_id', $user->id)->count();
    $relationshipCount = DB::table('lead_products as lp')
        ->join('products as p', 'lp.product_id', '=', 'p.id')
        ->where('p.user_id', $user->id)
        ->count();
    
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "  Leads: $leadCount\n";
    echo "  Products: $productCount\n";
    echo "  Lead-Product relationships: $relationshipCount\n";
    echo "\n";
}

echo "\n=== Specific Lead Count Comparison ===\n";

// Find users who actually have data
$activeUsers = DB::table('users as u')
    ->leftJoin('leads as l', 'u.id', '=', 'l.user_id')
    ->leftJoin('products as p', 'u.id', '=', 'p.user_id')
    ->select('u.id', 'u.name')
    ->where(function($query) {
        $query->whereNotNull('l.id')
              ->orWhereNotNull('p.id');
    })
    ->distinct()
    ->get();

foreach($activeUsers as $user) {
    echo "=== Analysis for {$user->name} (ID: {$user->id}) ===\n";
    
    // Customer list method (Guest controller)
    $customerListCount = App\Models\Lead::where('user_id', $user->id)->count();
    
    // Product management method (Product controller)
    $products = App\Models\Product::where('user_id', $user->id)->withCount('leadProducts')->get();
    $productManagementCount = $products->sum('lead_products_count');
    
    // Distinct leads through products
    $distinctLeadsThroughProducts = DB::table('lead_products as lp')
        ->join('products as p', 'lp.product_id', '=', 'p.id')
        ->where('p.user_id', $user->id)
        ->distinct('lp.lead_id')
        ->count();
    
    echo "Customer List (direct leads): $customerListCount\n";
    echo "Product Management (relationships): $productManagementCount\n";
    echo "Distinct leads through products: $distinctLeadsThroughProducts\n";
    echo "Discrepancy: " . ($customerListCount - $productManagementCount) . "\n";
    echo "\n";
}