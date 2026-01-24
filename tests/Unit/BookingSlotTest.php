<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\Business;
use App\Models\BusinessContact;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingSlotTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $user;
    protected $calendar;
    protected $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->contact = BusinessContact::factory()->create(['business_id' => $this->business->id]);

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
                ]
            ],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_reserve_a_time_slot()
    {
        $startTime = Carbon::parse('next monday 10:00');
        $endTime = $startTime->copy()->addMinutes(30);

        $slot = BookingSlot::reserve(
            $this->calendar->id,
            $startTime,
            $endTime,
            $this->contact->id,
            ['booking_source' => 'manual']
        );

        $this->assertInstanceOf(BookingSlot::class, $slot);
        $this->assertEquals('reserved', $slot->status);
        $this->assertEquals($this->calendar->id, $slot->booking_calendar_id);
        $this->assertEquals($this->contact->id, $slot->business_contact_id);
        $this->assertNotNull($slot->booked_at);
    }

    /** @test */
    public function it_detects_conflicting_slots()
    {
        $startTime = Carbon::parse('next monday 10:00');
        $endTime = $startTime->copy()->addMinutes(30);

        // Create first booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Check for conflicts
        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $startTime, $endTime);

        $this->assertEquals(1, $conflicts->count());
    }

    /** @test */
    public function it_does_not_detect_conflicts_for_cancelled_slots()
    {
        $startTime = Carbon::parse('next monday 10:00');
        $endTime = $startTime->copy()->addMinutes(30);

        // Create cancelled booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => 30,
            'status' => 'cancelled',
        ]);

        // Check for conflicts - cancelled slots should not conflict
        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $startTime, $endTime);

        $this->assertEquals(0, $conflicts->count());
    }

    /** @test */
    public function it_allows_adjacent_slots_without_overlap()
    {
        $firstStart = Carbon::parse('next monday 10:00');
        $firstEnd = $firstStart->copy()->addMinutes(30);

        // Create first booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $firstStart,
            'end_time' => $firstEnd,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Try to book immediately after (no overlap)
        $secondStart = $firstEnd; // 10:30
        $secondEnd = $secondStart->copy()->addMinutes(30); // 11:00

        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $secondStart, $secondEnd);

        // No overlap, but calendar buffer rules may apply separately
        $this->assertEquals(0, $conflicts->count());
    }

    /** @test */
    public function it_can_be_confirmed()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'reserved',
        ]);

        $slot->confirm();

        $this->assertEquals('confirmed', $slot->fresh()->status);
        $this->assertNotNull($slot->fresh()->confirmed_at);
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $slot->cancel('Customer requested cancellation');

        $freshSlot = $slot->fresh();
        $this->assertEquals('cancelled', $freshSlot->status);
        $this->assertNotNull($freshSlot->cancelled_at);
        $this->assertEquals('Customer requested cancellation', $freshSlot->cancellation_reason);
    }

    /** @test */
    public function it_can_be_marked_as_no_show()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $slot->markNoShow();

        $this->assertEquals('no_show', $slot->fresh()->status);
    }

    /** @test */
    public function it_can_be_completed()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $slot->complete();

        $this->assertEquals('completed', $slot->fresh()->status);
    }

    /** @test */
    public function it_can_link_to_an_appointment()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'reserved',
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $slot->linkToAppointment($appointment->id);

        $this->assertEquals($appointment->id, $slot->fresh()->appointment_id);
    }

    /** @test */
    public function it_validates_status_transitions()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'available',
        ]);

        // Available -> Reserved (valid)
        $slot->update(['status' => 'reserved']);
        $this->assertEquals('reserved', $slot->status);

        // Reserved -> Confirmed (valid)
        $slot->confirm();
        $this->assertEquals('confirmed', $slot->fresh()->status);

        // Confirmed -> Completed (valid)
        $slot->complete();
        $this->assertEquals('completed', $slot->fresh()->status);
    }

    /** @test */
    public function it_excludes_self_from_conflict_check()
    {
        $startTime = Carbon::parse('next monday 10:00');
        $endTime = $startTime->copy()->addMinutes(30);

        $slot = BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Check conflicts excluding self
        $conflicts = BookingSlot::checkConflicts(
            $this->calendar->id,
            $startTime,
            $endTime,
            $slot->id
        );

        // Should not find itself as a conflict
        $this->assertEquals(0, $conflicts->count());
    }

    /** @test */
    public function it_detects_partial_overlaps()
    {
        $firstStart = Carbon::parse('next monday 10:00');
        $firstEnd = $firstStart->copy()->addMinutes(60); // 10:00-11:00

        // Create first booking
        BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $firstStart,
            'end_time' => $firstEnd,
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        // Try to book overlapping time (10:30-11:30)
        $overlappingStart = $firstStart->copy()->addMinutes(30);
        $overlappingEnd = $overlappingStart->copy()->addMinutes(60);

        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $overlappingStart, $overlappingEnd);

        // Should detect the partial overlap
        $this->assertEquals(1, $conflicts->count());
    }

    /** @test */
    public function it_stores_metadata()
    {
        $metadata = [
            'booking_source' => 'ai_agent',
            'ai_agent_id' => 123,
            'notes' => 'Customer requested morning slot'
        ];

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'metadata' => $metadata,
        ]);

        $this->assertEquals('ai_agent', $slot->metadata['booking_source']);
        $this->assertEquals(123, $slot->metadata['ai_agent_id']);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
        ]);

        $this->assertInstanceOf(BookingCalendar::class, $slot->bookingCalendar);
        $this->assertInstanceOf(BusinessContact::class, $slot->businessContact);
        $this->assertInstanceOf(Business::class, $slot->business);
    }

    /** @test */
    public function it_scopes_to_active_slots()
    {
        // Create various slots
        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'cancelled',
        ]);

        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        // Only confirmed and reserved slots should be "active"
        $activeSlots = BookingSlot::whereIn('status', ['reserved', 'confirmed'])->count();
        $this->assertEquals(1, $activeSlots);
    }

    /** @test */
    public function cancelled_slot_frees_up_time()
    {
        $startTime = Carbon::parse('next monday 10:00');
        $endTime = $startTime->copy()->addMinutes(30);

        $slot = BookingSlot::create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Initially conflicts
        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $startTime, $endTime);
        $this->assertEquals(1, $conflicts->count());

        // Cancel the slot
        $slot->cancel();

        // No longer conflicts
        $conflicts = BookingSlot::checkConflicts($this->calendar->id, $startTime, $endTime);
        $this->assertEquals(0, $conflicts->count());
    }
}
