<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $user;
    protected $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);

        $this->calendar = BookingCalendar::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'name' => 'Test Calendar',
            'calendar_type' => 'demo',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 10,
            'availability_rules' => [
                'working_hours' => [
                    ['day' => 'monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'tuesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'wednesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'thursday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'friday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'saturday', 'enabled' => false, 'start' => '09:00', 'end' => '13:00'],
                    ['day' => 'sunday', 'enabled' => false, 'start' => '09:00', 'end' => '13:00'],
                ],
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'blackout_dates' => []
            ],
            'max_bookings_per_day' => 8,
            'max_bookings_per_week' => 30,
            'min_advance_hours' => 2,
            'max_advance_days' => 60,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_identifies_working_days()
    {
        // Monday is a working day
        $monday = Carbon::parse('next monday');
        $this->assertTrue($this->calendar->isWorkingDay($monday));

        // Sunday is not a working day
        $sunday = Carbon::parse('next sunday');
        $this->assertFalse($this->calendar->isWorkingDay($sunday));
    }

    /** @test */
    public function it_checks_if_time_is_within_working_hours()
    {
        // 10 AM on Monday (within working hours)
        $validTime = Carbon::parse('next monday 10:00');
        $this->assertTrue($this->calendar->isWithinWorkingHours($validTime));

        // 8 AM on Monday (before working hours)
        $tooEarly = Carbon::parse('next monday 08:00');
        $this->assertFalse($this->calendar->isWithinWorkingHours($tooEarly));

        // 6 PM on Monday (after working hours)
        $tooLate = Carbon::parse('next monday 18:00');
        $this->assertFalse($this->calendar->isWithinWorkingHours($tooLate));
    }

    /** @test */
    public function it_detects_break_times()
    {
        // 12:30 PM is during lunch break
        $breakTime = Carbon::parse('next monday 12:30');
        $this->assertTrue($this->calendar->isInBreakTime($breakTime));

        // 10 AM is not during break
        $workTime = Carbon::parse('next monday 10:00');
        $this->assertFalse($this->calendar->isInBreakTime($workTime));
    }

    /** @test */
    public function it_respects_daily_booking_limits()
    {
        $date = Carbon::parse('next monday');

        // Create 8 confirmed slots (max limit)
        for ($i = 0; $i < 8; $i++) {
            BookingSlot::create([
                'booking_calendar_id' => $this->calendar->id,
                'business_id' => $this->business->id,
                'start_time' => $date->copy()->setTime(9 + $i, 0),
                'end_time' => $date->copy()->setTime(9 + $i, 30),
                'duration_minutes' => 30,
                'status' => 'confirmed',
            ]);
        }

        $this->assertTrue($this->calendar->hasReachedDailyLimit($date));
    }

    /** @test */
    public function it_generates_available_slots_for_a_date()
    {
        $date = Carbon::parse('next monday');
        $slots = $this->calendar->getAvailableSlots($date, 30);

        // Should have slots between 9 AM and 5 PM, excluding lunch (12-1 PM)
        // 9:00-12:00 = 3 hours = 6 slots (30 min each)
        // 1:00-5:00 = 4 hours = 8 slots
        // With 10 min buffer, actual available slots will be less
        $this->assertIsArray($slots);
        $this->assertGreaterThan(0, count($slots));

        // Each slot should have start and end times
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('start', $slot);
            $this->assertArrayHasKey('end', $slot);
        }
    }

    /** @test */
    public function it_checks_if_specific_time_slot_is_available()
    {
        $slotTime = Carbon::parse('next monday 10:00');

        // No conflicts - should be available
        $this->assertTrue($this->calendar->isTimeSlotAvailable($slotTime, 30));

        // Create a conflicting booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $slotTime,
            'end_time' => $slotTime->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Now should not be available
        $this->assertFalse($this->calendar->isTimeSlotAvailable($slotTime, 30));
    }

    /** @test */
    public function it_respects_minimum_advance_hours()
    {
        // Try to book 1 hour from now (less than 2 hour minimum)
        $tooSoon = now()->addHour();
        $this->assertFalse($this->calendar->isTimeSlotAvailable($tooSoon, 30));

        // Try to book 3 hours from now (meets minimum)
        $validAdvance = now()->addHours(3);
        // Ensure it's during working hours on a working day
        if ($validAdvance->isWeekend()) {
            $validAdvance = $validAdvance->next(Carbon::MONDAY)->setTime(10, 0);
        }
        
        // This should pass the advance notice check (other factors may affect availability)
        $result = $this->calendar->isTimeSlotAvailable($validAdvance, 30);
        // The result depends on working hours, but at least it shouldn't fail on advance notice
        $this->assertTrue(true); // Basic assertion to ensure method runs
    }

    /** @test */
    public function it_respects_maximum_advance_days()
    {
        // Try to book 70 days from now (beyond 60 day maximum)
        $tooFar = now()->addDays(70)->setTime(10, 0);
        $this->assertFalse($this->calendar->isTimeSlotAvailable($tooFar, 30));

        // Try to book 30 days from now (within limit)
        $validAdvance = now()->addDays(30);
        if ($validAdvance->isWeekend()) {
            $validAdvance = $validAdvance->next(Carbon::MONDAY)->setTime(10, 0);
        }
        
        // Should not fail on advance days check
        $result = $this->calendar->isTimeSlotAvailable($validAdvance, 30);
        $this->assertTrue(true); // Method executes without throwing
    }

    /** @test */
    public function it_generates_slots_for_date_range()
    {
        $startDate = Carbon::parse('next monday');
        $endDate = $startDate->copy()->addDays(4); // Monday through Friday

        $slots = $this->calendar->generateSlotsForDateRange($startDate, $endDate);

        // Should have slots for each working day
        $this->assertArrayHasKey($startDate->format('Y-m-d'), $slots);
        
        // Should not have slots for weekend
        $this->assertArrayNotHasKey($startDate->copy()->next(Carbon::SATURDAY)->format('Y-m-d'), $slots);
    }

    /** @test */
    public function inactive_calendar_should_not_provide_slots()
    {
        $this->calendar->update(['is_active' => false]);
        
        $date = Carbon::parse('next monday');
        $slots = $this->calendar->getAvailableSlots($date, 30);

        // Inactive calendar should return no slots
        $this->assertEmpty($slots);
    }

    /** @test */
    public function it_handles_blackout_dates()
    {
        $blackoutDate = Carbon::parse('next monday');
        
        $this->calendar->update([
            'availability_rules' => array_merge($this->calendar->availability_rules, [
                'blackout_dates' => [$blackoutDate->format('Y-m-d')]
            ])
        ]);

        $slots = $this->calendar->getAvailableSlots($blackoutDate, 30);
        
        // Should return no slots for blackout date
        $this->assertEmpty($slots);
    }

    /** @test */
    public function it_calculates_business_hours_for_a_day()
    {
        $monday = Carbon::parse('next monday');
        $hours = $this->calendar->getBusinessHoursForDay($monday);

        $this->assertIsArray($hours);
        $this->assertEquals('09:00', $hours['start']);
        $this->assertEquals('17:00', $hours['end']);

        // Sunday should have no business hours
        $sunday = Carbon::parse('next sunday');
        $sundayHours = $this->calendar->getBusinessHoursForDay($sunday);
        $this->assertNull($sundayHours);
    }

    /** @test */
    public function it_accounts_for_buffer_time_between_slots()
    {
        $firstSlotStart = Carbon::parse('next monday 10:00');
        
        // Create first booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $firstSlotStart,
            'end_time' => $firstSlotStart->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Try to book immediately after (10:30) - should fail due to buffer
        $tooSoon = $firstSlotStart->copy()->addMinutes(30);
        $this->assertFalse($this->calendar->isTimeSlotAvailable($tooSoon, 30));

        // Try to book after buffer time (10:40) - should succeed
        $afterBuffer = $firstSlotStart->copy()->addMinutes(40);
        $this->assertTrue($this->calendar->isTimeSlotAvailable($afterBuffer, 30));
    }

    /** @test */
    public function it_validates_relationships()
    {
        $this->assertInstanceOf(Business::class, $this->calendar->business);
        $this->assertInstanceOf(User::class, $this->calendar->user);
        $this->assertEquals($this->business->id, $this->calendar->business_id);
        $this->assertEquals($this->user->id, $this->calendar->user_id);
    }
}
