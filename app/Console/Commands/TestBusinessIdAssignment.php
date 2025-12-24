<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Setup;
use App\Models\EventsGuest;
use App\Models\Business;
use App\Models\User;
use App\Models\Event;
use App\Models\UsersEvent;

class TestBusinessIdAssignment extends Command
{
    protected $signature = 'test:business-id-assignment';
    protected $description = 'Test that new WhatsApp contacts get proper business_id assignment';

    public function handle()
    {
        $this->info('🧪 Testing Business ID Assignment for WhatsApp Contacts');
        $this->info('======================================================');
        $this->newLine();

        // Get a user with a business
        $user = User::whereHas('business')->first();
        if (!$user) {
            $this->error('❌ No user with business found');
            return Command::FAILURE;
        }

        $business = $user->business;
        $this->info("Using User ID: {$user->id}, Business ID: {$business->id}");
        $this->newLine();

        // Ensure user has an event
        $userEvent = UsersEvent::where('user_id', $user->id)->first();
        if (!$userEvent) {
            // Create a default event
            $defaultEvent = Event::firstOrCreate([
                'name' => 'WhatsApp Contacts Test',
                'event_type_id' => 1,
                'date' => now()->format('Y-m-d')
            ]);
            
            $userEvent = UsersEvent::create([
                'user_id' => $user->id,
                'event_id' => $defaultEvent->id
            ]);
            
            $this->line("✅ Created event for user: {$defaultEvent->id}");
        }

        // Test the Setup controller's findOrCreateGuest method
        $setup = new Setup();
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($setup);
        $findOrCreateGuestMethod = $reflection->getMethod('findOrCreateGuest');
        $findOrCreateGuestMethod->setAccessible(true);

        // Test phone numbers
        $testPhones = [
            '+255701234567',
            '255701234568', 
            '0701234569'
        ];

        foreach ($testPhones as $index => $phoneNumber) {
            $testName = "WhatsApp User " . ($index + 1);
            
            $this->info("Test " . ($index + 1) . ": Creating contact with phone $phoneNumber");
            
            try {
                // Call the private method
                $guest = $findOrCreateGuestMethod->invoke($setup, $user->id, $phoneNumber, $testName);
                
                if ($guest) {
                    $this->line("✅ Guest created successfully:");
                    $this->line("   - Guest ID: {$guest->id}");
                    $this->line("   - Name: {$guest->guest_name}");
                    $this->line("   - Phone: {$guest->guest_phone}");
                    $this->line("   - Business ID: " . ($guest->business_id ?: 'NULL'));
                    $this->line("   - User ID: " . ($guest->user_id ?: 'NULL'));
                    $this->line("   - Event ID: " . ($guest->event_id ?: 'NULL'));
                    $this->line("   - Handoff Status: " . ($guest->handoff_status ?: 'NULL'));
                    
                    if ($guest->business_id === $business->id) {
                        $this->info("   ✅ CORRECT: Business ID matches expected ({$business->id})");
                    } else {
                        $this->error("   ❌ WRONG: Business ID is {$guest->business_id}, expected {$business->id}");
                    }
                    
                    // Test that the contact would appear in business filtering
                    $contactsForBusiness = EventsGuest::where('business_id', $business->id)
                        ->where('id', $guest->id)
                        ->count();
                    
                    if ($contactsForBusiness > 0) {
                        $this->info("   ✅ Contact appears in business filter");
                    } else {
                        $this->error("   ❌ Contact does NOT appear in business filter");
                    }
                    
                } else {
                    $this->error("   ❌ Failed to create guest");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Exception: " . $e->getMessage());
            }
            
            $this->newLine();
        }

        // Test findOrCreateForNotification method as well
        $this->info('🔄 Testing EventsGuest::findOrCreateForNotification method');
        
        try {
            $guest = EventsGuest::findOrCreateForNotification($user->id, '+255701234570', 'Notification Test User');
            
            $this->line("✅ Notification guest created:");
            $this->line("   - Guest ID: {$guest->id}");
            $this->line("   - Business ID: " . ($guest->business_id ?: 'NULL'));
            $this->line("   - User ID: " . ($guest->user_id ?: 'NULL'));
            
            if ($guest->business_id === $business->id) {
                $this->info("   ✅ CORRECT: Notification method assigns proper business ID");
            } else {
                $this->error("   ❌ WRONG: Notification method has wrong business ID");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Notification method error: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('📊 Summary: All WhatsApp contacts should now have business_id assigned');
        $this->info('✅ Business ID Assignment Test Complete!');
        
        return Command::SUCCESS;
    }
}