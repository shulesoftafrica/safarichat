<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\BillingService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing pricing display transformation...\n\n";

// Load pricing plans from billing service (excluding free trial)
$products = BillingService::getProducts();
$pricingPlans = [];

if ($products['success'] && !empty($products['data'])) {
    // Filter out free trial packages (price = 0) and only show packages with price > 0
    // The new BillingService returns products directly in ['data'] array
    $pricingPlans = collect($products['data'])
        ->filter(function($plan) {
            return isset($plan['price']) && floatval($plan['price']) > 0;
        })
        ->map(function($plan) {
            // Transform to format expected by view
            return [
                'name' => $plan['plan_name'] ?? ucfirst($plan['id']),
                'amount' => $plan['price'],
                'billing_interval' => $plan['billing_cycle'] ?? 'monthly',
                'metadata' => [
                    'features' => $plan['limits'] ?? []
                ]
            ];
        })
        ->values()
        ->toArray();
}

echo "Number of pricing plans (excluding trial): " . count($pricingPlans) . "\n\n";

foreach ($pricingPlans as $plan) {
    echo "Plan: " . $plan['name'] . "\n";
    echo "Price: TZS " . number_format($plan['amount']) . "\n";
    echo "Billing: " . $plan['billing_interval'] . "\n";
    echo "AI Credits: " . number_format($plan['metadata']['features']['ai_credits'] ?? 0) . "\n";
    echo "Max Contacts: " . ($plan['metadata']['features']['max_contacts'] ?? 'N/A') . "\n";
    echo "---\n\n";
}
