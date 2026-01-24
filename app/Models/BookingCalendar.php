<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookingCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'description',
        'calendar_type',
        'default_duration_minutes',
        'buffer_minutes',
        'availability_rules',
        'max_bookings_per_day',
        'max_bookings_per_week',
        'min_advance_hours',
        'max_advance_days',
        'allow_ai_booking',
        'allow_manual_booking',
        'require_confirmation',
        'is_active',
    ];

    protected $casts = [
        'availability_rules' => 'array',
        'allow_ai_booking' => 'boolean',
        'allow_manual_booking' => 'boolean',
        'require_confirmation' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Calendar Types Constants
    const TYPE_DEMO = 'demo';
    const TYPE_CONSULTATION = 'consultation';
    const TYPE_FOLLOW_UP = 'follow_up';
    const TYPE_MEETING = 'meeting';
    const TYPE_CALL = 'call';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get the business that owns the calendar
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who created the calendar
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all booking slots for this calendar
     */
    public function bookingSlots()
    {
        return $this->hasMany(BookingSlot::class);
    }

    /**
     * Get appointments through booking slots
     */
    public function appointments()
    {
        return $this->hasManyThrough(Appointment::class, BookingSlot::class, 'booking_calendar_id', 'id', 'id', 'appointment_id');
    }

    /**
     * Get available time slots for a specific date
     *
     * @param Carbon $date
     * @param int|null $duration Duration in minutes (uses default if null)
     * @return array Array of available time slots
     */
    public function getAvailableSlots($date, $duration = null)
    {
        $duration = $duration ?? $this->default_duration_minutes;
        $availableSlots = [];
        
        // Check if it's a working day
        if (!$this->isWorkingDay($date)) {
            return [];
        }
        
        // Check if within advance booking limits
        $now = Carbon::now();
        $hoursUntil = $now->diffInHours($date, false);
        
        if ($hoursUntil < $this->min_advance_hours || $hoursUntil > ($this->max_advance_days * 24)) {
            return [];
        }
        
        // Check daily limit
        if ($this->hasReachedDailyLimit($date)) {
            return [];
        }
        
        // Get working hours for this day
        $workingHours = $this->getBusinessHoursForDay($date);
        if (!$workingHours) {
            return [];
        }
        
        // Generate slots
        $currentTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['start']);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['end']);
        
        while ($currentTime->copy()->addMinutes($duration) <= $endTime) {
            // Check if slot is available
            if ($this->isTimeSlotAvailable($currentTime, $duration)) {
                // Check against breaks
                if (!$this->isInBreakTime($currentTime, $duration)) {
                    $availableSlots[] = [
                        'start_time' => $currentTime->copy(),
                        'end_time' => $currentTime->copy()->addMinutes($duration),
                        'duration' => $duration,
                    ];
                }
            }
            
            // Move to next slot (duration + buffer)
            $currentTime->addMinutes($duration + $this->buffer_minutes);
        }
        
        return $availableSlots;
    }

    /**
     * Check if a specific time slot is available
     *
     * @param Carbon $startTime
     * @param int $duration Duration in minutes
     * @return bool
     */
    public function isTimeSlotAvailable($startTime, $duration)
    {
        $endTime = $startTime->copy()->addMinutes($duration);
        
        // Check for conflicting booking slots
        $conflicts = BookingSlot::where('booking_calendar_id', $this->id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($q) use ($startTime, $endTime) {
                    // New slot starts during existing slot
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // New slot ends during existing slot
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // New slot completely contains existing slot
                    $q->where('start_time', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();
        
        return !$conflicts;
    }

    /**
     * Get the next available slot after a given date/time
     *
     * @param Carbon $fromDate
     * @param int $duration Duration in minutes
     * @param int $maxDaysToCheck Maximum days to look ahead
     * @return array|null Array with slot info or null if none found
     */
    public function getNextAvailableSlot($fromDate, $duration, $maxDaysToCheck = 30)
    {
        $currentDate = $fromDate->copy()->startOfDay();
        $endDate = $fromDate->copy()->addDays($maxDaysToCheck);
        
        while ($currentDate <= $endDate) {
            $slots = $this->getAvailableSlots($currentDate, $duration);
            
            foreach ($slots as $slot) {
                // Only return slots that are actually after the requested time
                if ($slot['start_time'] > $fromDate) {
                    return $slot;
                }
            }
            
            $currentDate->addDay();
        }
        
        return null;
    }

    /**
     * Check if datetime is within working hours
     *
     * @param Carbon $datetime
     * @return bool
     */
    public function isWithinWorkingHours($datetime)
    {
        $workingHours = $this->getBusinessHoursForDay($datetime);
        
        if (!$workingHours) {
            return false;
        }
        
        $time = $datetime->format('H:i');
        return $time >= $workingHours['start'] && $time < $workingHours['end'];
    }

    /**
     * Check if a date is a working day
     *
     * @param Carbon $date
     * @return bool
     */
    public function isWorkingDay($date)
    {
        $dayName = strtolower($date->format('l'));
        $rules = $this->availability_rules;
        
        if (!isset($rules['working_hours'][$dayName])) {
            return false;
        }
        
        return $rules['working_hours'][$dayName] !== null;
    }

    /**
     * Check if daily booking limit has been reached
     *
     * @param Carbon $date
     * @return bool
     */
    public function hasReachedDailyLimit($date)
    {
        if (!$this->max_bookings_per_day) {
            return false;
        }
        
        $count = BookingSlot::where('booking_calendar_id', $this->id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereDate('start_time', $date->format('Y-m-d'))
            ->count();
        
        return $count >= $this->max_bookings_per_day;
    }

    /**
     * Check if weekly booking limit has been reached
     *
     * @param Carbon $weekStart Start of week
     * @return bool
     */
    public function hasReachedWeeklyLimit($weekStart)
    {
        if (!$this->max_bookings_per_week) {
            return false;
        }
        
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        $count = BookingSlot::where('booking_calendar_id', $this->id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->count();
        
        return $count >= $this->max_bookings_per_week;
    }

    /**
     * Generate time slots for a date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateSlotsForDateRange($startDate, $endDate)
    {
        $allSlots = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $slots = $this->getAvailableSlots($currentDate);
            if (!empty($slots)) {
                $allSlots[$currentDate->format('Y-m-d')] = $slots;
            }
            $currentDate->addDay();
        }
        
        return $allSlots;
    }

    /**
     * Get business hours for a specific day
     *
     * @param Carbon $date
     * @return array|null ['start' => '09:00', 'end' => '17:00'] or null
     */
    public function getBusinessHoursForDay($date)
    {
        $dayName = strtolower($date->format('l'));
        $rules = $this->availability_rules;
        
        if (!isset($rules['working_hours'][$dayName]) || $rules['working_hours'][$dayName] === null) {
            return null;
        }
        
        return $rules['working_hours'][$dayName];
    }

    /**
     * Check if time slot falls within a break period
     *
     * @param Carbon $startTime
     * @param int $duration
     * @return bool
     */
    protected function isInBreakTime($startTime, $duration)
    {
        $rules = $this->availability_rules;
        
        if (!isset($rules['breaks']) || empty($rules['breaks'])) {
            return false;
        }
        
        $endTime = $startTime->copy()->addMinutes($duration);
        $dayOfWeek = (int) $startTime->format('N'); // 1 = Monday, 7 = Sunday
        
        foreach ($rules['breaks'] as $break) {
            // Check if break applies to this day
            if (!in_array($dayOfWeek, $break['days'])) {
                continue;
            }
            
            $breakStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $break['start']);
            $breakEnd = Carbon::parse($startTime->format('Y-m-d') . ' ' . $break['end']);
            
            // Check for overlap
            if ($startTime < $breakEnd && $endTime > $breakStart) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Scope to get only active calendars
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get calendars by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('calendar_type', $type);
    }

    /**
     * Scope to get calendars that allow AI booking
     */
    public function scopeAiEnabled($query)
    {
        return $query->where('allow_ai_booking', true);
    }
}
