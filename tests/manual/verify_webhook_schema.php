<?php

/**
 * Quick schema verification script
 * Run with: php tests/manual/verify_webhook_schema.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Webhook Schema Verification\n";
echo "========================================\n\n";

// Check billing_accounts table columns
echo "[1] Checking billing_accounts table columns...\n";
$billingAccountsColumns = DB::select("
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name = 'billing_accounts' 
    AND column_name IN ('subscription_status', 'last_transaction_id', 'last_payment_at', 'last_payment_amount')
    ORDER BY column_name
");

if (count($billingAccountsColumns) === 4) {
    echo "✓ All 4 webhook fields exist in billing_accounts:\n";
    foreach ($billingAccountsColumns as $col) {
        echo "  - {$col->column_name} ({$col->data_type})\n";
    }
} else {
    echo "✗ FAILED: Expected 4 columns, found " . count($billingAccountsColumns) . "\n";
    foreach ($billingAccountsColumns as $col) {
        echo "  - {$col->column_name} ({$col->data_type})\n";
    }
}

echo "\n";

// Check billing_webhook_events table exists
echo "[2] Checking billing_webhook_events table...\n";
$webhookEventsTable = DB::select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_name = 'billing_webhook_events'
");

if (count($webhookEventsTable) > 0) {
    echo "✓ billing_webhook_events table exists\n";
    
    // Check columns
    $webhookEventsColumns = DB::select("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'billing_webhook_events'
        ORDER BY ordinal_position
    ");
    
    echo "  Columns (" . count($webhookEventsColumns) . "):\n";
    foreach ($webhookEventsColumns as $col) {
        echo "  - {$col->column_name} ({$col->data_type})\n";
    }
} else {
    echo "✗ FAILED: billing_webhook_events table does not exist\n";
}

echo "\n";

// Check indexes
echo "[3] Checking indexes...\n";
$indexes = DB::select("
    SELECT indexname 
    FROM pg_indexes 
    WHERE tablename IN ('billing_accounts', 'billing_webhook_events')
    AND indexname LIKE '%transaction%'
    ORDER BY indexname
");

if (count($indexes) > 0) {
    echo "✓ Transaction-related indexes found:\n";
    foreach ($indexes as $idx) {
        echo "  - {$idx->indexname}\n";
    }
} else {
    echo "⚠ WARNING: No transaction-related indexes found\n";
}

echo "\n========================================\n";
echo "Verification Complete!\n";
echo "========================================\n";
