<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessContact;
use App\Models\Lead;
use App\Models\AiSalesAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConvertUnengagedContactsCommand extends Command
{
    protected $signature = 'contacts:convert-unengaged {--limit=20} {--days-old=1} {--dry-run}';
    protected $description = 'Convert unengaged business contacts to leads for outreach campaigns';

    public function handle()
    {
        // This command exists purely to seed cold outreach (it converts contacts who
        // have NOT engaged into leads for follow-up). That is the opposite of only
        // messaging people who replied, so it no-ops under either safety setting.
        if (!config('outreach.enabled', true) || config('outreach.reply_required', true)) {
            $this->warn('contacts:convert-unengaged skipped — outreach disabled or reply-required mode is on.');
            \Illuminate\Support\Facades\Log::info('contacts:convert-unengaged skipped (anti-spam settings)');
            return 0;
        }

        $this->info('🔄 Converting Unengaged Business Contacts to Leads');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $daysOld = (int) $this->option('days-old');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No conversions will be made');
            $this->newLine();
        }

        try {
            // Get unengaged business contacts
            $unengagedContacts = $this->getUnengagedContacts($limit, $daysOld);
            
            if ($unengagedContacts->isEmpty()) {
                // Debug: Show why no contacts were found
                $this->warn('⚠️  No unengaged contacts found. Debug info:');
                $this->info("  - Days old filter: {$daysOld}");
                $this->info("  - Total contacts in DB: " . BusinessContact::count());
                $this->info("  - Not contacted for sales: " . BusinessContact::where('contacted_for_sales', false)->count());
                $this->info("  - With valid phone: " . BusinessContact::where('contacted_for_sales', false)->whereNotNull('guest_phone')->where('guest_phone', '!=', '')->count());
                $this->info("  - Without leads: " . BusinessContact::whereDoesntHave('leads')->count());
                $this->info('✅ No unengaged contacts found');
                return 0;
            }

            $this->info("📋 Found {$unengagedContacts->count()} unengaged contacts");
            
            $converted = 0;
            $errors = 0;

            foreach ($unengagedContacts as $contact) {
                try {
                    if ($dryRun) {
                        $this->line("📝 Would convert: {$contact->guest_name} ({$contact->guest_phone})");
                        $converted++;
                        continue;
                    }

                    $success = $this->convertContactToLead($contact);
                    
                    if ($success) {
                        $converted++;
                        $this->line("  ✅ Converted: {$contact->guest_name} ({$contact->guest_phone})");
                    } else {
                        $errors++;
                        $this->error("  ❌ Failed: {$contact->guest_name}");
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  💥 Error converting {$contact->guest_name}: " . $e->getMessage());
                    Log::error('Contact conversion error', [
                        'contact_id' => $contact->id,
                        'contact_name' => $contact->guest_name,
                        'business_id' => $contact->business_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->newLine();
            $this->info("🎉 Contact conversion completed!");
            $this->info("📊 Converted: {$converted}, Errors: {$errors}");
            
            if ($converted > 0) {
                $this->info("💡 These new leads will be picked up by the daily outreach campaign.");
            }
            
            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in contact conversion: " . $e->getMessage());
            Log::error('Contact conversion fatal error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Get business contacts that haven't been engaged yet
     */
    private function getUnengagedContacts(int $limit, int $daysOld)
    {
        $cutoffDate = now()->subDays($daysOld);
        
        // Get all phone numbers that have existing messages
        $phonesWithIncoming = DB::table('incoming_messages')
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->unique();
            
        $phonesWithOutgoing = DB::table('outgoing_messages')
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->unique();
            
        $phonesWithMessages = $phonesWithIncoming->merge($phonesWithOutgoing)->unique()->toArray();
        
        $query = BusinessContact::where(function($query) {
                // Not contacted for sales yet
                $query->where('contacted_for_sales', false)
                      ->orWhereNull('contacted_for_sales');
            })
            ->whereNotNull('business_id') // Must have valid business_id
            ->where('business_id', '>', 0) // Must be a positive integer
            ->whereNotNull('guest_phone')
            ->where('guest_phone', '!=', '')
            ->whereNotNull('guest_name')
            ->where('guest_name', '!=', '')
            ->where('created_at', '<=', $cutoffDate) // At least X days old
            ->where(function($query) {
                // No existing lead record OR lead exists but never messaged
                $query->whereDoesntHave('leads')
                      ->orWhereHas('leads', function($leadQuery) {
                          // Lead exists but has no AI/HUMAN agent messages (never contacted)
                          $leadQuery->whereDoesntHave('conversations', function($convQuery) {
                              $convQuery->whereIn('message_type', ['AI_AGENT', 'HUMAN_AGENT']);
                          });
                      });
            })
            // Exclude contacts whose phone numbers already have messages
            ->whereNotIn('guest_phone', $phonesWithMessages)
            ->whereHas('business', function($query) {
                // Only contacts with active businesses
                $query->whereNotNull('user_id');
            })
            ->with(['business', 'business.user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
        
        // Log the SQL query for debugging
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        Log::info('Unengaged contacts query', [
            'sql' => $sql,
            'bindings' => $bindings,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'days_old' => $daysOld
        ]);
        
        // Print query to console
        $this->newLine();
        $this->info('📊 Query Details:');
        $this->line('SQL: ' . $sql);
        $this->line('Bindings: ' . json_encode($bindings, JSON_PRETTY_PRINT));
        $this->line('Cutoff Date: ' . $cutoffDate->toDateTimeString());
        $this->newLine();
        
        return $query->get();
    }

    /**
     * Convert a business contact to a lead
     */
    private function convertContactToLead(BusinessContact $contact): bool
    {
        try {
            // Get active AI sales agent for this business
            // Note: Using just status='active' as is_active should mirror this
            $aiAgent = AiSalesAgent::where('user_id', $contact->business->user_id)
                                  ->where('status', 'active')
                                  ->first();

            if (!$aiAgent) {
                Log::warning('No active AI agent for contact conversion', [
                    'contact_id' => $contact->id,
                    'contact_name' => $contact->guest_name,
                    'user_id' => $contact->business->user_id,
                    'business_id' => $contact->business_id,
                ]);
                return false;
            }

            // Calculate initial lead score based on contact data
            $leadScore = $this->calculateInitialLeadScore($contact);
            
            // Create lead from business contact
            $result = Lead::safeCreate([
                'business_contact_id' => $contact->id,
                'business_id' => $contact->business_id,
                'ai_sales_agent_id' => $aiAgent->id,
                'user_id' => $contact->business->user_id,
                'company_name' => $contact->guest_name,
                'source' => 'business_contact',
                'status' => Lead::STATUS_NEW,
                'last_interaction_at' => null, // NULL so they're picked up by followup system as new contacts
                'lead_score' => $leadScore,
                'conversion_probability' => (int) round($this->calculateConversionProbability($contact, $leadScore) * 100),
                'is_churned' => false,
                'win_back_attempts' => 0,
                'industry' => $contact->business->business_category ?? 'general',
                'metadata' => json_encode([
                    'source_type' => 'unengaged_contact',
                    'converted_at' => now(),
                    'original_contact_date' => $contact->created_at,
                    'conversion_method' => 'automated',
                    'contact_phone' => $contact->guest_phone,
                    'contact_email' => $contact->guest_email
                ]),
            ]);

            if (!$result['success']) {
                Log::warning('Skipping contact conversion because no product assignment could be resolved', [
                    'contact_id' => $contact->id,
                    'errors' => $result['errors'],
                ]);
                return false;
            }

            $lead = $result['lead'];

            // Mark contact as contacted for sales
            $contact->update([
                'contacted_for_sales' => true,
                'lead_id' => $lead->id
            ]);

            Log::info('Contact converted to lead', [
                'contact_id' => $contact->id,
                'lead_id' => $lead->id,
                'lead_score' => $leadScore,
                'ai_agent_id' => $aiAgent->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to convert contact to lead', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calculate initial lead score based on contact information
     */
    private function calculateInitialLeadScore(BusinessContact $contact): int
    {
        $score = 30; // Base score for all contacts

        // Phone number quality
        if ($contact->guest_phone && strlen(preg_replace('/[^0-9]/', '', $contact->guest_phone)) >= 9) {
            $score += 10;
        }

        // Email availability
        if ($contact->guest_email && filter_var($contact->guest_email, FILTER_VALIDATE_EMAIL)) {
            $score += 15;
        }

        // Name completeness
        if ($contact->guest_name && strlen($contact->guest_name) > 2) {
            $score += 5;
        }

        // Business category (if available)
        if ($contact->contact_category_id) {
            $score += 10;
        }

        // Recency boost for newer contacts
        $daysOld = Carbon::parse($contact->created_at)->diffInDays(now());
        if ($daysOld <= 7) {
            $score += 10;
        } elseif ($daysOld <= 30) {
            $score += 5;
        }

        // Priority level boost
        if ($contact->priority_level <= 2) {
            $score += 15; // High priority contacts
        } elseif ($contact->priority_level == 3) {
            $score += 5; // Normal priority
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Calculate conversion probability
     */
    private function calculateConversionProbability(BusinessContact $contact, int $leadScore): float
    {
        // Base probability from lead score
        $probability = $leadScore / 100 * 0.6; // 60% max from score

        // Business context adjustments
        if ($contact->business && $contact->business->user) {
            // Active business owner
            $probability += 0.2;
        }

        // Contact completeness bonus
        $completeness = 0;
        if ($contact->guest_phone) $completeness += 0.33;
        if ($contact->guest_email) $completeness += 0.33;
        if ($contact->guest_name) $completeness += 0.34;
        
        $probability += $completeness * 0.2;

        return min($probability, 1.0); // Cap at 1.0 (100%)
    }

    /**
     * Determine priority level based on contact data
     */
    private function determinePriorityLevel(BusinessContact $contact, int $leadScore): int
    {
        // Use existing priority if set and reasonable
        if ($contact->priority_level && $contact->priority_level <= 4) {
            return $contact->priority_level;
        }

        // Determine based on lead score
        if ($leadScore >= 80) return 1; // High
        if ($leadScore >= 60) return 2; // Medium-high  
        if ($leadScore >= 40) return 3; // Normal
        return 4; // Low
    }

    /**
     * Format a phone number to E.164.
     *
     * For uploaded contact lists the caller should pass the owning business
     * contact so we can derive the correct country code from the business
     * owner's account — rather than hard-coding a single country.
     *
     * Resolution order:
     *   1. Number already starts with '+' → trust it as-is.
     *   2. Number has ≥ 10 digits         → internationally qualified, just add '+'.
     *   3. Number starts with '0'         → local format; strip leading 0 and use
     *                                        the owner's country code if available.
     *   4. Everything else                → apply the owner's country code.
     *
     * @param string               $phone
     * @param BusinessContact|null $contact  Owning contact (provides country via business.user)
     */
    private function formatPhoneNumber(string $phone, ?\App\Models\BusinessContact $contact = null): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone; // Already E.164 — trust it.
        }

        if (strlen($phone) >= 10) {
            return '+' . $phone; // Enough digits to include a country code.
        }

        // Derive country code from the business owner's profile.
        // Falls back to '+255' only when no owner data is available at all,
        // which should not happen for contacts imported via the platform.
        $ownerCountryCode = null;
        if ($contact?->business?->user?->country_code) {
            $ownerCountryCode = '+' . ltrim($contact->business->user->country_code, '+');
        }
        $countryCode = $ownerCountryCode ?? '+255';

        if (str_starts_with($phone, '0')) {
            return $countryCode . substr($phone, 1);
        }

        return $countryCode . $phone;
    }
}