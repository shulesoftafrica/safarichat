<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\UserResolutionService;

/**
 * @property int $id
 * @property int $event_id
 * @property string $guest_name
 * @property string $guest_email
 * @property string $guest_email_verified_at
 * @property string $guest_phone
 * @property string $guest_category
 * @property float $guest_pledge
 * @property string $created_at
 * @property string $updated_at
 * @property Event $event
 * @property Message[] $messages
 * @property Payment[] $payments
 */
class EventsGuest extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['event_id', 'user_id', 'guest_name', 'guest_email', 'guest_email_verified_at', 'guest_phone', 'event_guest_category_id', 'guest_pledge', 'contacted_for_sales', 'contacted_at', 'created_at', 'updated_at','code'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany('App\Models\Message', 'events_guests_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany('App\Models\Payment', 'events_guests_id');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventGuestCategory()
    {
        return $this->belongsTo('App\Models\EventGuestCategory');
    }

    // ===== NOTIFICATION API METHODS =====

    /**
     * Get outgoing messages for this guest
     */
    public function outgoingMessages()
    {
        return $this->hasMany(OutgoingMessage::class, 'events_guest_id');
    }

    /**
     * Get incoming messages for this guest
     */
    public function incomingMessages()
    {
        return $this->hasMany(IncomingMessage::class, 'events_guest_id');
    }

    /**
     * Get all WhatsApp notifications sent to this guest
     */
    public function notifications()
    {
        return $this->outgoingMessages()->where('provider', 'unified_api');
    }

    /**
     * Find or create EventsGuest by phone number for user
     */
    public static function findOrCreateForNotification($userId, $phoneNumber, $name = null)
    {
        // Get user's event
        $userEvent = \App\Models\UsersEvent::where('user_id', $userId)->first();
        
        if (!$userEvent) {
            throw new \Exception("No event found for user ID: {$userId}");
        }

        // Find or create guest
        return self::firstOrCreate([
            'event_id' => $userEvent->event_id,
            'guest_phone' => $phoneNumber,
        ], [
            'guest_name' => $name ?: 'Auto-created from API',
            'event_guest_category_id' => 1, // Default category
            'guest_pledge' => 0,
        ]);
    }

    /**
     * Enhanced auto-creation with UserResolutionService integration
     */
    public static function findOrCreateWithResolution(array $contactData, ?int $eventId = null): self
    {
        // Use UserResolutionService for intelligent contact resolution
        $userResolutionService = new \App\Services\UserResolutionService();
        
        // Try to find existing contact first
        $existingContact = $userResolutionService->findContactByMultipleMethods([
            'phone' => $contactData['phone'] ?? '',
            'email' => $contactData['email'] ?? '',
            'name' => $contactData['name'] ?? ''
        ]);

        if ($existingContact && ($eventId === null || $existingContact->event_id === $eventId)) {
            return $existingContact;
        }

        // Determine event_id if not provided
        if ($eventId === null) {
            $eventId = self::resolveEventForContact($contactData);
        }

        // Normalize phone number
        $normalizedPhone = $userResolutionService->normalizePhoneNumber($contactData['phone'] ?? '');
        
        // Create new contact with enhanced data
        return self::create([
            'event_id' => $eventId,
            'guest_name' => $contactData['name'] ?? self::generateNameFromContact($contactData),
            'guest_phone' => $normalizedPhone,
            'guest_email' => $contactData['email'] ?? null,
            'event_guest_category_id' => $contactData['category_id'] ?? 1,
            'guest_pledge' => $contactData['pledge'] ?? 0,
            'user_id' => $contactData['user_id'] ?? auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Resolve event for contact based on context
     */
    private static function resolveEventForContact(array $contactData): int
    {
        // If user_id provided, use their active event
        if (!empty($contactData['user_id'])) {
            $userEvent = \App\Models\UsersEvent::where('user_id', $contactData['user_id'])->first();
            if ($userEvent) {
                return $userEvent->event_id;
            }
        }

        // Use current authenticated user's event
        if (auth()->check()) {
            $userEvent = \App\Models\UsersEvent::where('user_id', auth()->id())->first();
            if ($userEvent) {
                return $userEvent->event_id;
            }
        }

        // Default to the most recent event
        $recentEvent = \App\Models\Event::latest()->first();
        return $recentEvent ? $recentEvent->id : 1; // Fallback to event ID 1
    }

    /**
     * Generate name from contact data when name is missing
     */
    private static function generateNameFromContact(array $contactData): string
    {
        if (!empty($contactData['email'])) {
            return 'Contact_' . substr($contactData['email'], 0, strpos($contactData['email'], '@'));
        }
        
        if (!empty($contactData['phone'])) {
            $digits = preg_replace('/[^\d]/', '', $contactData['phone']);
            return 'Contact_' . substr($digits, -4);
        }
        
        return 'Contact_' . uniqid();
    }

    /**
     * Bulk create or update contacts with relationship optimization
     */
    public static function bulkCreateOrUpdate(array $contactsData, int $eventId): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => []];
        
        foreach ($contactsData as $index => $contactData) {
            try {
                // Add event_id to contact data
                $contactData['event_id'] = $eventId;
                
                // Find existing contact
                $existing = self::where('event_id', $eventId)
                    ->where('guest_phone', $contactData['phone'] ?? '')
                    ->first();

                if ($existing) {
                    // Update existing contact
                    $existing->update([
                        'guest_name' => $contactData['name'] ?? $existing->guest_name,
                        'guest_email' => $contactData['email'] ?? $existing->guest_email,
                        'guest_phone' => $contactData['phone'] ?? $existing->guest_phone,
                        'event_guest_category_id' => $contactData['category_id'] ?? $existing->event_guest_category_id,
                        'updated_at' => now()
                    ]);
                    $results['updated']++;
                } else {
                    // Create new contact
                    self::findOrCreateWithResolution($contactData, $eventId);
                    $results['created']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Row {$index}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Auto-link contact to user based on relationship hints
     */
    public function autoLinkToUser(): ?int
    {
        // Try to find user by email
        if (!empty($this->guest_email)) {
            $user = \App\Models\User::where('email', $this->guest_email)->first();
            if ($user) {
                $this->update(['user_id' => $user->id]);
                return $user->id;
            }
        }

        // Try to find user by phone
        if (!empty($this->guest_phone)) {
            $normalizedPhone = (new \App\Services\UserResolutionService())->normalizePhoneNumber($this->guest_phone);
            $user = \App\Models\User::where('phone', $normalizedPhone)->first();
            if ($user) {
                $this->update(['user_id' => $user->id]);
                return $user->id;
            }
        }

        return null;
    }

    /**
     * Get contact merge suggestions based on similar data
     */
    public static function getMergeSuggestions(int $limit = 10): array
    {
        $suggestions = [];
        
        // Find contacts with similar phone numbers
        $phoneGroups = self::selectRaw('guest_phone, COUNT(*) as count, GROUP_CONCAT(id) as ids')
            ->whereNotNull('guest_phone')
            ->where('guest_phone', '!=', '')
            ->groupBy('guest_phone')
            ->having('count', '>', 1)
            ->limit($limit)
            ->get();

        foreach ($phoneGroups as $group) {
            $ids = explode(',', $group->ids);
            $contacts = self::whereIn('id', $ids)->get();
            
            $suggestions[] = [
                'type' => 'duplicate_phone',
                'phone' => $group->guest_phone,
                'contacts' => $contacts->toArray(),
                'suggested_primary' => $contacts->sortByDesc('updated_at')->first()->id
            ];
        }

        return $suggestions;
    }

    /**
     * Get notification statistics for this guest
     */
    public function getNotificationStats($days = 30)
    {
        return $this->notifications()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();
    }

    /**
     * Check if guest can receive notifications
     */
    public function canReceiveNotifications()
    {
        return !empty($this->guest_phone) && 
               strlen(preg_replace('/[^0-9]/', '', $this->guest_phone)) >= 10;
    }

    /**
     * Get last notification sent to this guest
     */
    public function getLastNotification()
    {
        return $this->notifications()->latest()->first();
    }

    /**
     * Mark guest as contacted for sales
     */
    public function markContactedForSales()
    {
        $this->update([
            'contacted_for_sales' => true,
            'contacted_at' => now(),
        ]);
    }

    /**
     * Scope to get guests eligible for notifications
     */
    public function scopeNotificationEligible($query)
    {
        return $query->whereNotNull('guest_phone')
                    ->where('guest_phone', '!=', '');
    }

    /**
     * Scope to get guests for specific event
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Get formatted phone number for API
     */
    public function getFormattedPhoneAttribute()
    {
        $phone = preg_replace('/[^0-9]/', '', $this->guest_phone);
        
        // Add country code if not present (assuming Tanzania +255)
        if (strlen($phone) === 9 && !str_starts_with($phone, '255')) {
            return '+255' . $phone;
        }
        
        if (strlen($phone) === 12 && str_starts_with($phone, '255')) {
            return '+' . $phone;
        }
        
        return '+' . $phone;
    }
}
