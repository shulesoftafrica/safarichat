<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\EventsGuest;
use App\Models\User;

// Get current user data
echo "=== User & Event Information ===\n";

// Get user
$user = User::find(45); // Use user 45 as in previous logs
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "User Name: " . $user->name . "\n";
    echo "User Phone: " . $user->phone . "\n";
    
    // Get user's event
    $userEvent = $user->usersEvents()->orderBy('id', 'desc')->first();
    if ($userEvent) {
        echo "Event ID: " . $userEvent->event_id . "\n";
        
        // Check how many guests in this event
        $totalGuests = EventsGuest::where('event_id', $userEvent->event_id)->count();
        echo "Total Guests in Event: " . $totalGuests . "\n";
        
        if ($totalGuests > 0) {
            echo "\nFirst 3 guests:\n";
            $guests = EventsGuest::where('event_id', $userEvent->event_id)->limit(3)->get();
            foreach ($guests as $guest) {
                echo "- Guest ID: {$guest->id}, Name: {$guest->guest_name}, Phone: {$guest->guest_phone}\n";
            }
        }
        
        // Test criteria 1 (All users)
        echo "\n=== Testing Criteria 1 (All Users) ===\n";
        $allUsers = EventsGuest::where('event_id', $userEvent->event_id)->get();
        echo "Query result count: " . $allUsers->count() . "\n";
        
        // Test criteria 6 (Custom numbers)
        echo "\n=== Testing Criteria 6 (Custom Numbers) ===\n";
        $phones = ['0714825469', '254714825469'];
        $customUsers = [];
        foreach ($phones as $phone) {
            $customUsers[] = ['guest_phone' => $phone, 'guest_email' => '', 'guest_name' => 'Test User', 'guest_pledge' => '', 'custom' => 1];
        }
        echo "Custom users created: " . count($customUsers) . "\n";
        foreach ($customUsers as $user) {
            echo "- Phone: {$user['guest_phone']}\n";
        }
        
    } else {
        echo "No events found for this user!\n";
    }
} else {
    echo "User 45 not found!\n";
}

echo "\n=== Queue Configuration ===\n";
echo "Default Queue: " . config('queue.default') . "\n";
echo "Queue Driver: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n";
