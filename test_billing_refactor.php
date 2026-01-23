<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BillingService;

echo "=== Testing New Billing System ===\n\n";

// Test 1: Get remaining credits
$credits = BillingService::getRemainingCredits(45);
echo "User 45 current credits: {$credits}\n";

// Test 2: Check if has credits
$hasEnough = BillingService::hasCredits(45, 100);
echo "Has 100 credits: " . ($hasEnough ? 'YES' : 'NO') . "\n";

// Test 3: Deduct credits
echo "\nDeducting 100 credits...\n";
$result = BillingService::deductCredits(45, 100, 'Test deduction from new billing system');
echo "Deduction result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Test 4: Check new balance
$newCredits = BillingService::getRemainingCredits(45);
echo "New balance: {$newCredits}\n";
echo "Expected: " . ($credits - 100) . "\n";

// Test 5: Get billing account details
$user = App\Models\User::find(45);
$billingAccount = BillingService::getBillingAccountForUser($user);
echo "\nBilling Account Details:\n";
echo "- ID: {$billingAccount->id}\n";
echo "- Owner: {$billingAccount->owner_type} #{$billingAccount->owner_id}\n";
echo "- Plan: {$billingAccount->subscription_plan}\n";
echo "- Credits: {$billingAccount->ai_credits}\n";
echo "- Credits Used: {$billingAccount->ai_credits_used}\n";
echo "- Status: {$billingAccount->status}\n";

echo "\n=== Test Complete ===\n";
