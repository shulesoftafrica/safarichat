<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EventsGuest;
use App\Models\Business;
use App\Models\User;

class FixMissingBusinessIds extends Command
{
    protected $signature = 'fix:missing-business-ids';
    protected $description = 'Fix EventsGuest records that have null business_id';

    public function handle()
    {
        $this->info('🔧 Fixing Missing Business IDs in EventsGuest');
        $this->info('=============================================');
        $this->newLine();

        // Find all EventsGuest records with null business_id
        $guestsWithoutBusiness = EventsGuest::whereNull('business_id')
            ->whereNotNull('user_id')
            ->get();

        $this->line("Found {$guestsWithoutBusiness->count()} guests with missing business_id");
        $this->newLine();

        $fixed = 0;
        $errors = 0;

        foreach ($guestsWithoutBusiness as $guest) {
            try {
                // Get user's business
                $userBusiness = Business::where('user_id', $guest->user_id)->first();
                
                if ($userBusiness) {
                    $guest->update(['business_id' => $userBusiness->id]);
                    $this->line("✅ Fixed guest ID {$guest->id} - assigned business ID {$userBusiness->id}");
                    $fixed++;
                } else {
                    $this->warn("⚠️ No business found for guest ID {$guest->id} (user ID {$guest->user_id})");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Error fixing guest ID {$guest->id}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("📊 Results:");
        $this->line("Fixed: {$fixed}");
        $this->line("Errors: {$errors}");
        
        // Also check for guests without user_id
        $guestsWithoutUser = EventsGuest::whereNull('user_id')->count();
        if ($guestsWithoutUser > 0) {
            $this->warn("⚠️ Found {$guestsWithoutUser} guests without user_id - these need manual review");
        }

        $this->newLine();
        $this->info('✅ Business ID Fix Complete!');
        
        return Command::SUCCESS;
    }
}