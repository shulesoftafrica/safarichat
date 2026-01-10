<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_link',
        'status',
        'appointment_type',
        'notes',
        'reminder_sent',
        'reminder_sent_at',
        'created_by',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $dates = [
        'scheduled_at',
        'reminder_sent_at',
        'confirmed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_NO_SHOW = 'no_show';

    // Appointment type constants
    const TYPE_DEMO = 'demo';
    const TYPE_CONSULTATION = 'consultation';
    const TYPE_FOLLOW_UP = 'follow_up';
    const TYPE_PRESENTATION = 'presentation';
    const TYPE_MEETING = 'meeting';
    const TYPE_CALL = 'call';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('scheduled_at', now()->addDay());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeNeedsReminder($query)
    {
        return $query->upcoming()
            ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_PENDING])
            ->where(function($q) {
                $q->where('reminder_sent', false)
                  ->orWhereNull('reminder_sent');
            });
    }

    // Accessors & Mutators
    public function getEndTimeAttribute()
    {
        if ($this->scheduled_at && $this->duration_minutes) {
            return Carbon::parse($this->scheduled_at)->addMinutes($this->duration_minutes);
        }
        return null;
    }

    public function getIsUpcomingAttribute()
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function getIsOverdueAttribute()
    {
        return $this->scheduled_at && $this->scheduled_at->isPast() && 
               in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_NO_SHOW => 'dark',
            default => 'secondary'
        };
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_minutes) return 'Not specified';
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    // Methods
    public function confirm()
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now()
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED
        ]);
    }

    public function markNoShow()
    {
        $this->update([
            'status' => self::STATUS_NO_SHOW
        ]);
    }

    public function reschedule(Carbon $newDateTime, $notes = null)
    {
        $this->update([
            'scheduled_at' => $newDateTime,
            'status' => self::STATUS_PENDING,
            'reminder_sent' => false,
            'reminder_sent_at' => null,
            'notes' => $notes ? ($this->notes ? $this->notes . "\n\n" . $notes : $notes) : $this->notes
        ]);
    }

    public function getTimeUntilAttribute()
    {
        if (!$this->scheduled_at) return null;
        
        if ($this->scheduled_at->isPast()) {
            return $this->scheduled_at->diffForHumans() . ' (overdue)';
        }
        
        return $this->scheduled_at->diffForHumans();
    }

    /**
     * Create appointment from AI request
     */
    public static function createFromAiRequest(Lead $lead, array $data)
    {
        // Parse natural language date/time
        $scheduledAt = self::parseScheduleDate($data['date'] ?? $data['time'] ?? null);
        
        return self::create([
            'lead_id' => $lead->id,
            'title' => $data['title'] ?? 'Demo/Consultation',
            'description' => $data['description'] ?? 'Appointment scheduled via AI assistant',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $data['duration'] ?? 60,
            'appointment_type' => $data['type'] ?? self::TYPE_DEMO,
            'status' => self::STATUS_PENDING,
            'notes' => $data['notes'] ?? null
        ]);
    }

    /**
     * Parse natural language schedule date
     */
    private static function parseScheduleDate($input): Carbon
    {
        if (!$input) {
            // Default to tomorrow at 10 AM if no time specified
            return now()->addDay()->hour(10)->minute(0)->second(0);
        }
        
        try {
            // Try to parse as a standard date/time
            return Carbon::parse($input);
        } catch (\Exception $e) {
            // Fall back to tomorrow 10 AM if parsing fails
            return now()->addDay()->hour(10)->minute(0)->second(0);
        }
    }

    /**
     * Get available appointment slots for next 30 days
     */
    public static function getAvailableSlots($businessId = null, $days = 30): array
    {
        $slots = [];
        $startDate = now()->addDay(); // Start from tomorrow
        
        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            
            // Skip weekends (optional - can be configured per business)
            if ($currentDate->isWeekend()) {
                continue;
            }
            
            // Generate time slots from 9 AM to 5 PM
            for ($hour = 9; $hour < 17; $hour++) {
                $slotTime = $currentDate->copy()->hour($hour)->minute(0)->second(0);
                
                // Check if slot is already taken
                $isBooked = self::where('scheduled_at', $slotTime)
                    ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_PENDING])
                    ->exists();
                
                if (!$isBooked) {
                    $slots[] = [
                        'datetime' => $slotTime,
                        'formatted' => $slotTime->format('l, M j - g:i A'),
                        'available' => true
                    ];
                }
            }
        }
        
        return $slots;
    }
}