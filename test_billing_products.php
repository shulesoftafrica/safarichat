<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BillingService;

echo "Testing BillingService::getProducts()...\n";
$result = BillingService::getProducts();

echo "Result:\n";
print_r($result);

echo "\n\nTesting BillingService::getSafariChatProduct()...\n";
$safariChatResult = BillingService::getSafariChatProduct();

echo "SafariChat Product Result:\n";
print_r($safariChatResult);