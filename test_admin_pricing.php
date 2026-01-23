<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\BillingService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing AdminController pricing methods...\n\n";

// Create AdminController instance
$adminController = new App\Http\Controllers\AdminController();

// Use reflection to call private method
$reflection = new ReflectionClass($adminController);
$getCurrentPricingMethod = $reflection->getMethod('getCurrentPricing');
$getCurrentPricingMethod->setAccessible(true);

$pricing = $getCurrentPricingMethod->invoke($adminController);

echo "Current Pricing from AdminController:\n";
echo "=====================================\n";
echo "Starter Price: TZS " . number_format($pricing['starter_price']) . "\n";
echo "Pro Price: TZS " . number_format($pricing['pro_price']) . "\n";
echo "Premium Price: TZS " . number_format($pricing['premium_price']) . "\n";
echo "Price per message: TZS " . number_format($pricing['price_per_message']) . "\n";
echo "Price per month: TZS " . number_format($pricing['price_per_month']) . "\n";
echo "Free messages limit: " . $pricing['free_messages_limit'] . "\n";
