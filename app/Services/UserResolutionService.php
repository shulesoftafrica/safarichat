<?php

namespace App\Services;

use App\Models\User;
use App\Models\EventsGuest;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

/**
 * UserResolutionService handles identification and resolution of users/contacts
 * across different systems and provides unified contact management
 */
class UserResolutionService
{
    /**
     * Phone number normalization patterns
     */
    private static array $phonePatterns = [
        'kenyan' => [
            'pattern' => '/^(\+255|255|0)?([17]\d{8})$/',
            'format' => '+255{number}',
            'length' => 9
        ],
        'international' => [
            'pattern' => '/^\+(\d{1,4})(\d{6,14})$/',
            'format' => '+{country}{number}',
            'length' => [6, 14]
        ]
    ];

    /**
     * Contact identification sources and priorities
     */
    private static array $identificationSources = [
        'phone' => ['priority' => 1, 'required' => true],
        'email' => ['priority' => 2, 'required' => false],
        'name' => ['priority' => 3, 'required' => false],
        'user_id' => ['priority' => 4, 'required' => false]
    ];

    /**
     * Resolve user by multiple identification methods
     */
    public static function resolveUser(array $identifiers): ?User
    {
        // Sort identifiers by priority
        $sortedIdentifiers = self::sortIdentifiersByPriority($identifiers);
        
        foreach ($sortedIdentifiers as $type => $value) {
            if (empty($value)) continue;
            
            $user = match($type) {
                'user_id' => User::find($value),
                'email' => User::where('email', $value)->first(),
                'phone' => self::findUserByPhone($value),
                'name' => self::findUserByName($value),
                default => null
            };
            
            if ($user) {
                Log::info("User resolved by {$type}", ['user_id' => $user->id, 'identifier' => $value]);
                return $user;
            }
        }

        return null;
    }

    /**
     * Resolve or create EventsGuest contact
     */
    public static function resolveOrCreateContact(array $contactData): EventsGuest
    {
        // Normalize phone number first
        $normalizedPhone = self::normalizePhoneNumber($contactData['phone'] ?? '');
        
        // Try to find existing contact
        $contact = self::findContactByMultipleMethods([
            'phone' => $normalizedPhone,
            'email' => $contactData['email'] ?? null,
            'name' => $contactData['name'] ?? null
        ]);

        if ($contact) {
            // Update contact with any new information
            $contact = self::updateContactWithNewData($contact, $contactData);
            return $contact;
        }

        // Create new contact
        return self::createNewContact($contactData, $normalizedPhone);
    }

    /**
     * Find contact by multiple identification methods
     */
    public static function findContactByMultipleMethods(array $identifiers): ?EventsGuest
    {
        $queries = [];
        
        if (!empty($identifiers['phone'])) {
            $queries[] = ['guest_phone', 'LIKE', '%' . self::extractPhoneDigits($identifiers['phone']) . '%'];
        }
        
        if (!empty($identifiers['email'])) {
            $queries[] = ['guest_email', '=', $identifiers['email']];
        }

        foreach ($queries as $query) {
            $contact = EventsGuest::where($query[0], $query[1], $query[2])->first();
            if ($contact) {
                return $contact;
            }
        }

        // Try fuzzy name matching if no exact matches
        if (!empty($identifiers['name'])) {
            return self::findContactByFuzzyName($identifiers['name']);
        }

        return null;
    }

    /**
     * Normalize phone number to international format
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Clean the phone number
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Try Kenyan format first
        if (preg_match(self::$phonePatterns['kenyan']['pattern'], $cleaned, $matches)) {
            return '+255' . $matches[2];
        }
        
        // Try international format
        if (preg_match(self::$phonePatterns['international']['pattern'], $cleaned, $matches)) {
            return '+' . $matches[1] . $matches[2];
        }
        
        // If no pattern matches, return cleaned version with + prefix if not present
        return (str_starts_with($cleaned, '+') ? '' : '+') . $cleaned;
    }

    /**
     * Extract just the digits from phone number for flexible matching
     */
    public static function extractPhoneDigits(string $phone): string
    {
        return preg_replace('/[^\d]/', '', $phone);
    }

    /**
     * Find user by phone number with flexible matching
     */
    private static function findUserByPhone(string $phone): ?User
    {
        $normalizedPhone = self::normalizePhoneNumber($phone);
        $phoneDigits = self::extractPhoneDigits($phone);
        
        // Try exact match first
        $user = User::where('phone', $normalizedPhone)->first();
        if ($user) return $user;
        
        // Try flexible matching on just digits
        return User::where('phone', 'LIKE', '%' . $phoneDigits . '%')->first();
    }

    /**
     * Find user by name with fuzzy matching
     */
    private static function findUserByName(string $name): ?User
    {
        // Try exact match first
        $user = User::where('name', $name)->first();
        if ($user) return $user;
        
        // Try case-insensitive match
        $user = User::whereRaw('LOWER(name) = LOWER(?)', [$name])->first();
        if ($user) return $user;
        
        // Try partial match
        return User::where('name', 'LIKE', '%' . $name . '%')->first();
    }

    /**
     * Find contact by fuzzy name matching
     */
    private static function findContactByFuzzyName(string $name): ?EventsGuest
    {
        // Try exact match first
        $contact = EventsGuest::where('guest_name', $name)->first();
        if ($contact) return $contact;
        
        // Try case-insensitive match
        $contact = EventsGuest::whereRaw('LOWER(guest_name) = LOWER(?)', [$name])->first();
        if ($contact) return $contact;
        
        // Try partial match on each word
        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (strlen($word) >= 3) { // Only search words with 3+ characters
                $contact = EventsGuest::where('guest_name', 'LIKE', '%' . $word . '%')->first();
                if ($contact) return $contact;
            }
        }
        
        return null;
    }

    /**
     * Update existing contact with new data
     */
    private static function updateContactWithNewData(EventsGuest $contact, array $newData): EventsGuest
    {
        $updated = false;
        
        // Update phone if new one is more complete
        if (!empty($newData['phone'])) {
            $normalizedPhone = self::normalizePhoneNumber($newData['phone']);
            if (empty($contact->guest_phone) || strlen($normalizedPhone) > strlen($contact->guest_phone)) {
                $contact->guest_phone = $normalizedPhone;
                $updated = true;
            }
        }
        
        // Update email if empty
        if (!empty($newData['email']) && empty($contact->guest_email)) {
            $contact->guest_email = $newData['email'];
            $updated = true;
        }
        
        // Update name if empty or new one is more complete
        if (!empty($newData['name']) && (empty($contact->guest_name) || strlen($newData['name']) > strlen($contact->guest_name))) {
            $contact->guest_name = $newData['name'];
            $updated = true;
        }

        if ($updated) {
            $contact->save();
            Log::info('Contact updated with new data', ['contact_id' => $contact->id]);
        }
        
        return $contact;
    }

    /**
     * Create new contact with normalized data
     */
    private static function createNewContact(array $contactData, string $normalizedPhone): EventsGuest
    {
        // Get the user ID for whom we're creating the contact
        $userId = $contactData['created_by'] ?? $contactData['user_id'] ?? auth()->id();
        
        // Get or create an event for this user
        $eventId = self::getUserEventId($userId, $contactData['event_id'] ?? null);
        
        $contact = EventsGuest::create([
            'guest_name' => $contactData['name'] ?? self::generateNameFromPhone($normalizedPhone),
            'guest_phone' => $normalizedPhone,
            'guest_email' => $contactData['email'] ?? null,
            'type' => $contactData['type'] ?? 'notification_contact',
            'event_id' => $eventId,
            'event_guest_category_id' => 1, // Default category
            'guest_pledge' => 0,
            'created_by' => $userId,
        ]);
        
        Log::info('New contact created', ['contact_id' => $contact->id, 'phone' => $normalizedPhone, 'event_id' => $eventId]);
        
        return $contact;
    }

    /**
     * Generate a name from phone number when name is not provided
     */
    private static function generateNameFromPhone(string $phone): string
    {
        $digits = self::extractPhoneDigits($phone);
        return 'Contact_' . substr($digits, -4);
    }

    /**
     * Sort identifiers by priority
     */
    private static function sortIdentifiersByPriority(array $identifiers): array
    {
        $sorted = [];
        
        foreach (self::$identificationSources as $type => $config) {
            if (isset($identifiers[$type]) && !empty($identifiers[$type])) {
                $sorted[$type] = $identifiers[$type];
            }
        }
        
        return $sorted;
    }

    /**
     * Get WhatsApp session for user
     */
    public static function getUserWhatsappSession(?User $user, string $fallbackPhone = ''): ?WhatsappInstance
    {
        if ($user) {
            // Try to find active session for user
            $session = WhatsappInstance::where('user_id', $user->id)
                ->where('sessionActive', true)
                ->first();
            
            if ($session) {
                return $session;
            }
        }
        
        // Try to find session by phone if no user session
        if (!empty($fallbackPhone)) {
            $normalizedPhone = self::normalizePhoneNumber($fallbackPhone);
            $phoneDigits = self::extractPhoneDigits($fallbackPhone);
            
            return WhatsappInstance::where(function($query) use ($normalizedPhone, $phoneDigits) {
                $query->where('phone', $normalizedPhone)
                      ->orWhere('phone', 'LIKE', '%' . $phoneDigits . '%');
            })
            ->where('sessionActive', true)
            ->first();
        }
        
        return null;
    }

    /**
     * Resolve contact with comprehensive data merging
     */
    public static function resolveContactWithMerging(array $sources): EventsGuest
    {
        $mergedData = self::mergeContactDataFromSources($sources);
        return self::resolveOrCreateContact($mergedData);
    }

    /**
     * Merge contact data from multiple sources
     */
    private static function mergeContactDataFromSources(array $sources): array
    {
        $merged = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'type' => 'notification_contact'
        ];
        
        foreach ($sources as $source) {
            // Take the most complete/longest value for each field
            foreach (['name', 'phone', 'email'] as $field) {
                if (!empty($source[$field]) && strlen($source[$field]) > strlen($merged[$field])) {
                    $merged[$field] = $source[$field];
                }
            }
        }
        
        return array_filter($merged); // Remove empty values
    }

    /**
     * Validate phone number format
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        $normalized = self::normalizePhoneNumber($phone);
        $digits = self::extractPhoneDigits($normalized);
        
        // Check minimum length
        if (strlen($digits) < 8) {
            return false;
        }
        
        // Check if matches any known pattern
        foreach (self::$phonePatterns as $pattern) {
            if (preg_match($pattern['pattern'], $phone)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get contact resolution statistics
     */
    public static function getResolutionStats(): array
    {
        return [
            'total_contacts' => EventsGuest::count(),
            'contacts_with_phone' => EventsGuest::whereNotNull('phone')->count(),
            'contacts_with_email' => EventsGuest::whereNotNull('email')->count(),
            'contacts_with_both' => EventsGuest::whereNotNull('phone')
                ->whereNotNull('email')->count(),
            'duplicate_phones' => self::getDuplicatePhoneCount(),
            'invalid_phones' => self::getInvalidPhoneCount()
        ];
    }

    /**
     * Get count of contacts with duplicate phone numbers
     */
    private static function getDuplicatePhoneCount(): int
    {
        return EventsGuest::selectRaw('phone, COUNT(*) as count')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->having('count', '>', 1)
            ->count();
    }

    /**
     * Get count of contacts with invalid phone numbers
     */
    private static function getInvalidPhoneCount(): int
    {
        $contacts = EventsGuest::whereNotNull('phone')->get();
        $invalidCount = 0;
        
        foreach ($contacts as $contact) {
            if (!self::isValidPhoneNumber($contact->phone)) {
                $invalidCount++;
            }
        }
        
        return $invalidCount;
    }
    
    /**
     * Get or create an event ID for the specified user
     */
    private static function getUserEventId(?int $userId, ?int $specificEventId = null): int
    {
        // If a specific event ID is provided, validate it exists
        if ($specificEventId) {
            $eventExists = DB::table('events')->where('id', $specificEventId)->exists();
            if ($eventExists) {
                return $specificEventId;
            }
        }
        
        // If no user ID provided, try to get from auth
        if (!$userId) {
            $userId = auth()->id();
            if (!$userId) {
                // If still no user, create a system default event
                return self::createSystemDefaultEvent();
            }
        }
        
        // Check if user has any existing events
        $userEvent = DB::table('users_events')
            ->where('users_events.user_id', $userId)
            ->join('events', 'events.id', '=', 'users_events.event_id')
            ->first();
            
        if ($userEvent) {
            return $userEvent->event_id;
        }
        
        // Create a default event for this user
        return self::createDefaultEventForUser($userId);
    }
    
    /**
     * Create a default business for a specific user (Phase 2: Business-centric approach)
     */
    private static function createDefaultBusinessForUser(int $userId): int
    {
        DB::beginTransaction();
        
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            
            // Create default business instead of event
            $businessId = DB::table('businesses')->insertGetId([
                'user_id' => $userId,
                'name' => ($user->name ?? 'User') . ' Business',
                'campaign_name' => 'Default Campaign for Notifications',
                'business_type_id' => 1, // Default business type
                'campaign_start_date' => now()->format('Y-m-d'),
                'ward_id' => 1, // Default ward
                'address' => 'System Generated',
                'descriptions' => 'Auto-created business for notifications',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('Default business created for user', ['user_id' => $userId, 'business_id' => $businessId]);
            
            return $businessId;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create default business for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Legacy: Create a default event for a specific user (keeping for backward compatibility)
     */
    private static function createDefaultEventForUser(int $userId): int
    {
        // For new implementation, create business instead
        $businessId = self::createDefaultBusinessForUser($userId);
        
        // Still create event for backward compatibility but link to business
        DB::beginTransaction();
        
        try {
            // Create default event
            $eventId = DB::table('events')->insertGetId([
                'name' => 'Default Event for Notifications',
                'event_type_id' => 1, // Assuming 1 is a default event type
                'date' => now()->format('Y-m-d'),
                'location' => 'System Generated',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Link user to this event
            DB::table('users_events')->insert([
                'user_id' => $userId,
                'event_id' => $eventId,
                'created_at' => now(),
                'updated_at' => now(),
                'is_legacy' => true,
                'migration_notes' => 'Auto-created for backward compatibility - business-centric approach preferred'
            ]);
            
            DB::commit();
            
            Log::info('Default event created for user', ['user_id' => $userId, 'event_id' => $eventId]);
            
            return $eventId;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create default event for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Fall back to system default
            return self::createSystemDefaultEvent();
        }
    }
    
    /**
     * Create or get system-wide default event (fallback)
     */
    private static function createSystemDefaultEvent(): int
    {
        // Check if system default event already exists
        $systemEvent = DB::table('events')
            ->where('name', 'System Default Event')
            ->first();
            
        if ($systemEvent) {
            return $systemEvent->id;
        }
        
        // Create system default event
        try {
            $eventId = DB::table('events')->insertGetId([
                'name' => 'System Default Event',
                'event_type_id' => 1, // Assuming 1 is a default event type
                'date' => now()->format('Y-m-d'),
                'location' => 'System',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('System default event created', ['event_id' => $eventId]);
            
            return $eventId;
            
        } catch (Exception $e) {
            Log::error('Failed to create system default event', ['error' => $e->getMessage()]);
            
            // If all else fails, find any existing event
            $anyEvent = DB::table('events')->first();
            if ($anyEvent) {
                return $anyEvent->id;
            }
            
            throw new Exception('No events exist and cannot create default event');
        }
    }
}