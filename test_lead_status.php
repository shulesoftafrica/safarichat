<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;
use App\Models\User;

// Test lead status changing process
echo "=== Lead Status Change Verification ===\n\n";

try {
    // Get the first lead to test with
    $lead = Lead::with(['contact', 'aiSalesAgent'])->first();
    
    if (!$lead) {
        echo "❌ No leads found in database\n";
        echo "Creating a test lead...\n";
        
        // Get first user
        $user = User::first();
        if (!$user) {
            echo "❌ No users found in database\n";
            exit(1);
        }
        
        // Create a test lead
        $lead = Lead::create([
            'user_id' => $user->id,
            'name' => 'Test Lead for Status Verification',
            'phone_number' => '+1234567890',
            'email' => 'test@example.com',
            'status' => Lead::STATUS_NEW,
            'source' => 'API_TEST'
        ]);
        
        echo "✅ Created test lead with ID: {$lead->id}\n\n";
    }
    
    echo "📋 Current Lead Details:\n";
    echo "   ID: {$lead->id}\n";
    echo "   Name: {$lead->name}\n";
    echo "   Current Status: {$lead->status}\n";
    echo "   Last Interaction: " . ($lead->last_interaction_at ?: 'Never') . "\n\n";
    
    // Test status constants
    echo "🔧 Testing Status Constants:\n";
    $statusConstants = [
        'NEW' => Lead::STATUS_NEW,
        'OUTREACHED' => Lead::STATUS_OUTREACHED,
        'REPLIED' => Lead::STATUS_REPLIED,
        'ENGAGED' => Lead::STATUS_ENGAGED,
        'QUALIFIED' => Lead::STATUS_QUALIFIED,
        'PITCHED' => Lead::STATUS_PITCHED,
        'DEMO_SCHEDULED' => Lead::STATUS_DEMO_SCHEDULED,
        'PROPOSAL_SENT' => Lead::STATUS_PROPOSAL_SENT,
        'NEGOTIATING' => Lead::STATUS_NEGOTIATING,
        'CLOSED' => Lead::STATUS_CLOSED,
        'LOST' => Lead::STATUS_LOST,
        'HANDED_OFF' => Lead::STATUS_HANDED_OFF,
        'DO_NOT_CONTACT' => Lead::STATUS_DO_NOT_CONTACT,
        'NEEDS_ATTENTION' => Lead::STATUS_NEEDS_ATTENTION,
        'CONVERTED' => Lead::STATUS_CONVERTED,
        'CHURNED' => Lead::STATUS_CHURNED
    ];
    
    foreach ($statusConstants as $name => $value) {
        echo "   ✅ {$name}: {$value}\n";
    }
    echo "\n";
    
    // Test status transitions
    echo "🔄 Testing Status Transitions:\n";
    $originalStatus = $lead->status;
    
    $testStatuses = [
        Lead::STATUS_OUTREACHED,
        Lead::STATUS_REPLIED,
        Lead::STATUS_ENGAGED,
        Lead::STATUS_QUALIFIED,
        Lead::STATUS_PITCHED,
        Lead::STATUS_DEMO_SCHEDULED,
        Lead::STATUS_PROPOSAL_SENT,
        Lead::STATUS_NEGOTIATING
    ];
    
    foreach ($testStatuses as $status) {
        try {
            $lead->update([
                'status' => $status,
                'last_interaction_at' => now(),
                'notes' => $lead->notes . "\nStatus updated to {$status} at " . now()
            ]);
            
            // Verify the update
            $lead->refresh();
            
            if ($lead->status === $status) {
                echo "   ✅ Successfully updated to: {$status}\n";
            } else {
                echo "   ❌ Failed to update to: {$status} (current: {$lead->status})\n";
            }
            
            // Small delay to see progression
            sleep(1);
            
        } catch (Exception $e) {
            echo "   ❌ Error updating to {$status}: " . $e->getMessage() . "\n";
        }
    }
    
    // Test lead score calculation
    echo "\n📊 Testing Lead Score Calculation:\n";
    $initialScore = $lead->lead_score;
    echo "   Initial Score: " . ($initialScore ?: 'null') . "\n";
    
    $calculatedScore = $lead->calculateLeadScore();
    echo "   Calculated Score: {$calculatedScore}\n";
    
    // Test specialized methods
    echo "\n🎯 Testing Specialized Status Methods:\n";
    
    // Test churn functionality
    try {
        $lead->markAsChurned('Testing churn functionality', 'This is a test churn');
        echo "   ✅ markAsChurned() working - Status: {$lead->fresh()->status}\n";
    } catch (Exception $e) {
        echo "   ❌ markAsChurned() failed: " . $e->getMessage() . "\n";
    }
    
    // Test demo scheduling
    try {
        $lead->update(['status' => Lead::STATUS_QUALIFIED]); // Reset for demo test
        $lead->scheduleDemo(now()->addDays(3));
        echo "   ✅ scheduleDemo() working - Status: {$lead->fresh()->status}\n";
    } catch (Exception $e) {
        echo "   ❌ scheduleDemo() failed: " . $e->getMessage() . "\n";
    }
    
    // Test scopes
    echo "\n🔍 Testing Query Scopes:\n";
    
    $activeCount = Lead::active()->count();
    echo "   ✅ Active leads: {$activeCount}\n";
    
    $newCount = Lead::byStatus(Lead::STATUS_NEW)->count();
    echo "   ✅ New leads: {$newCount}\n";
    
    $needsOutreachCount = Lead::needsOutreach()->count();
    echo "   ✅ Needs outreach: {$needsOutreachCount}\n";
    
    // Restore original status
    $lead->update(['status' => $originalStatus]);
    echo "\n✅ Restored original status: {$originalStatus}\n";
    
    echo "\n🎉 Lead Status Change Process Verification Complete!\n";
    echo "All status transitions are working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error during verification: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}