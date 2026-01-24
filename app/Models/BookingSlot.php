<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_calendar_id',
        'business_id',
        'business_contact_id',
        'lead_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'appointment_id',
        'booked_by_user_id',
        'booking_method',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $dates = [
        'start_time',
        'end_time',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
    ];

    // Status Constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    // Booking Method Constants
    const METHOD_AI_AGENT = 'ai_agent';
    const METHOD_MANUAL = 'manual';
    const METHOD_API = 'api';
    const METHOD_SELF_SERVICE = 'self_service';

    /**
     * Get the booking calendar
     */
    public function bookingCalendar()
    {
        return $this->belongsTo(BookingCalendar::class);
    }

    /**
     * Get the business contact
     */
    public function businessContact()
    {
        return $this->belongsTo(\App\Models\BusinessContact::class, 'business_contact_id');
    }

    /**
     * Get the lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the linked appointment
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user who booked
     */
    public function bookedByUser()
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /**
     * Reserve this booking slot
     *
     * @param int $contactId Business contact ID
     * @param int|null $leadId Lead ID
     * @param int $bookedByUserId User making the reservation
     * @param string $method Booking method
     * @param string|null $notes Optional notes
     * @return $this
     */
    public function reserve($contactId, $leadId, $bookedByUserId, $method = self::METHOD_AI_AGENT, $notes = null)
    {
        $this->update([
            'business_contact_id' => $contactId,
            'lead_id' => $leadId,
            'booked_by_user_id' => $bookedByUserId,
            'booking_method' => $method,
            'status' => self::STATUS_RESERVED,
            'booked_at' => now(),
            'notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Confirm the booking slot
     *
     * @return $this
     */
    public function confirm()
    {
        if ($this->status !== self::STATUS_RESERVED && $this->status !== self::STATUS_CONFIRMED) {
            throw new \Exception("Cannot confirm slot with status: {$this->status}");
        }

        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Complete the booking slot
     *
     * @return $this
     */
    public function complete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);

        // Also mark appointment as completed if linked
        if ($this->appointment) {
            $this->appointment->complete();
        }

        return $this;
    }

    /**
     * Cancel the booking slot
     *
     * @param string|null $reason Cancellation reason
     * @return $this
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Also cancel appointment if linked
        if ($this->appointment) {
            $this->appointment->cancel();
        }

        return $this;
    }

    /**
     * Mark as no-show
     *
     * @return $this
     */
    public function markNoShow()
    {
        $this->update([
            'status' => self::STATUS_NO_SHOW,
        ]);

        // Also mark appointment as no-show if linked
        if ($this->appointment) {
            $this->appointment->markNoShow();
        }

        return $this;
    }

    /**
     * Check for conflicting booking slots
     *
     * @param int $calendarId
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param int|null $excludeSlotId Slot ID to exclude (for updates)
     * @return bool True if conflicts exist
     */
    public static function checkConflicts($calendarId, $startTime, $endTime, $excludeSlotId = null)
    {
        $query = self::where('booking_calendar_id', $calendarId)
            ->whereIn('status', [self::STATUS_RESERVED, self::STATUS_CONFIRMED])
            ->where(function($q) use ($startTime, $endTime) {
                $q->where(function($query) use ($startTime, $endTime) {
                    // New slot starts during existing slot
                    $query->where('start_time', '<=', $startTime)
                          ->where('end_time', '>', $startTime);
                })->orWhere(function($query) use ($startTime, $endTime) {
                    // New slot ends during existing slot
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>=', $endTime);
                })->orWhere(function($query) use ($startTime, $endTime) {
                    // New slot completely contains existing slot
                    $query->where('start_time', '>=', $startTime)
                          ->where('end_time', '<=', $endTime);
                });
            });

        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return $query->exists();
    }

    /**
     * Check if this slot is available for booking
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Check if this slot can be modified
     *
     * @return bool
     */
    public function canBeModified()
    {
        return in_array($this->status, [
            self::STATUS_AVAILABLE,
            self::STATUS_RESERVED,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Create appointment from this booking slot
     *
     * @param array $appointmentData
     * @return Appointment
     */
    public function createAppointment($appointmentData)
    {
        $appointment = Appointment::create(array_merge([
            'lead_id' => $this->lead_id,
            'scheduled_at' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => 'pending',
        ], $appointmentData));

        $this->linkToAppointment($appointment->id);

        return $appointment;
    }

    /**
     * Link this slot to an existing appointment
     *
     * @param int $appointmentId
     * @return $this
     */
    public function linkToAppointment($appointmentId)
    {
        $this->update([
            'appointment_id' => $appointmentId,
        ]);

        return $this;
    }

    /**
     * Scope to get only available slots
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope to get reserved or confirmed slots
     */
    public function scopeBooked($query)
    {
        return $query->whereIn('status', [self::STATUS_RESERVED, self::STATUS_CONFIRMED]);
    }

    /**
     * Scope to get upcoming slots
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
                    ->whereIn('status', [self::STATUS_RESERVED, self::STATUS_CONFIRMED]);
    }

    /**
     * Scope to get slots for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    /**
     * Scope to get slots for a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Get formatted start time
     */
    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time->format('M d, Y g:i A');
    }

    /**
     * Get formatted end time
     */
    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time->format('g:i A');
    }

    /**
     * Check if slot is in the past
     */
    public function getIsPastAttribute()
    {
        return $this->start_time < now();
    }

    /**
     * Check if slot is today
     */
    public function getIsTodayAttribute()
    {
        return $this->start_time->isToday();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_RESERVED => 'info',
            self::STATUS_CONFIRMED => 'primary',
            self::STATUS_COMPLETED => 'secondary',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_NO_SHOW => 'warning',
            default => 'secondary',
        };
    }
}
