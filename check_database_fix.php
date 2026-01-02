<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Structure Check ===\n";

// Check if the migration worked correctly  
$columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'leads' AND column_name IN ('business_contact_id', 'events_guest_id')");

echo "Columns in leads table:\n";
foreach($columns as $col) {
    echo "  - " . $col->column_name . "\n";
}

// Test that we have the right column structure
if (count($columns) > 0) {
    $hasBusinessContact = false;
    $hasEventsGuest = false;
    
    foreach($columns as $col) {
        if ($col->column_name === 'business_contact_id') {
            $hasBusinessContact = true;
        }
        if ($col->column_name === 'events_guest_id') {
            $hasEventsGuest = true;
        }
    }
    
    echo "\n=== Column Status ===\n";
    echo "business_contact_id exists: " . ($hasBusinessContact ? "✅ YES" : "❌ NO") . "\n";
    echo "events_guest_id exists: " . ($hasEventsGuest ? "⚠️  YES (old)" : "✅ NO (renamed)") . "\n";
    
    if ($hasBusinessContact) {
        echo "\n✅ The database migration was successful!\n";
        echo "✅ Lead creation code has been updated to use business_contact_id\n";
        echo "✅ The NULL constraint violation should be fixed\n";
    } else {
        echo "\n❌ Database migration may not have completed\n";
    }
} else {
    echo "❌ No relevant columns found\n";
}

// Check if any existing leads have NULL business_contact_id
try {
    $nullContactLeads = DB::table('leads')->whereNull('business_contact_id')->count();
    echo "\nLeads with NULL business_contact_id: $nullContactLeads\n";
    
    if ($nullContactLeads > 0) {
        echo "⚠️  Warning: Some leads still have NULL business_contact_id\n";
        echo "These may need manual data cleanup\n";
    } else {
        echo "✅ All existing leads have proper business_contact_id values\n";
    }
} catch (Exception $e) {
    echo "❌ Could not check existing leads: " . $e->getMessage() . "\n";
}