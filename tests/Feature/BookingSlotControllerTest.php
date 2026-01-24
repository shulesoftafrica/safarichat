<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\Business;
use App\Models\BusinessContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $business;
    protected $calendar;
    protected $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->contact = BusinessContact::factory()->create(['business_id' => $this->business->id]);

        $this->calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'availability_rules' => [
                'working_hours' => [
                    ['day' => 'monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'tuesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'wednesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'thursday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'friday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ],
                'breaks' => [],
                'blackout_dates' => []
            ],
        ]);
    }

    /** @test */
    public function it_returns_available_slots_for_a_date()
    {
        Sanctum::actingAs($this->user);

        $date = Carbon::parse('next monday')->format('Y-m-d');

        $response = $this->getJson(route('api.booking-slots.available', [
            'calendarId' => $this->calendar->id,
            'date' => $date,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'calendar' => ['id', 'name', 'type'],
            'date',
            'duration_minutes',
            'available_slots',
            'total_available'
        ]);
    }

    /** @test */
    public function it_validates_date_parameter()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.booking-slots.available', [
            'calendarId' => $this->calendar->id,
            'date' => 'invalid-date',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function it_can_reserve_an_available_slot()
    {
        Sanctum::actingAs($this->user);

        $startTime = Carbon::parse('next monday 10:00');

        $response = $this->postJson(route('api.booking-slots.reserve'), [
            'booking_calendar_id' => $this->calendar->id,
            'business_contact_id' => $this->contact->id,
            'start_time' => $startTime->toISOString(),
            'duration_minutes' => 30,
            'contact_name' => $this->contact->name,
            'contact_phone' => $this->contact->phone,
            'notes' => 'Test booking',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'booking_slot' => ['id', 'status', 'start_time', 'end_time']
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'booking_calendar_id' => $this->calendar->id,
            'business_contact_id' => $this->contact->id,
            'status' => 'reserved',
        ]);
    }

    /** @test */
    public function it_prevents_double_booking()
    {
        Sanctum::actingAs($this->user);

        $startTime = Carbon::parse('next monday 10:00');

        // Create first booking
        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes(30),
            'status' => 'confirmed',
        ]);

        // Try to book the same slot
        $response = $this->postJson(route('api.booking-slots.reserve'), [
            'booking_calendar_id' => $this->calendar->id,
            'start_time' => $startTime->toISOString(),
            'duration_minutes' => 30,
            'contact_name' => 'Test User',
            'contact_phone' => '+255712345678',
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'message' => 'Time slot conflicts with existing booking'
        ]);
    }

    /** @test */
    public function it_can_confirm_a_reserved_slot()
    {
        Sanctum::actingAs($this->user);

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'reserved',
        ]);

        $response = $this->postJson(route('api.booking-slots.confirm', $slot->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Booking slot confirmed'
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_cannot_confirm_already_confirmed_slot()
    {
        Sanctum::actingAs($this->user);

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $response = $this->postJson(route('api.booking-slots.confirm', $slot->id));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Only reserved slots can be confirmed'
        ]);
    }

    /** @test */
    public function it_can_cancel_a_booking()
    {
        Sanctum::actingAs($this->user);

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $response = $this->postJson(route('api.booking-slots.cancel', $slot->id), [
            'cancellation_reason' => 'Customer requested'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Booking slot cancelled'
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested',
        ]);
    }

    /** @test */
    public function it_can_reschedule_a_booking()
    {
        Sanctum::actingAs($this->user);

        $oldSlot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
            'start_time' => Carbon::parse('next monday 10:00'),
            'end_time' => Carbon::parse('next monday 10:30'),
            'status' => 'confirmed',
        ]);

        $newStartTime = Carbon::parse('next monday 14:00');

        $response = $this->postJson(route('api.booking-slots.reschedule', $oldSlot->id), [
            'new_start_time' => $newStartTime->toISOString(),
            'new_duration_minutes' => 30,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Booking slot rescheduled successfully'
        ]);

        // Old slot should be cancelled
        $this->assertDatabaseHas('booking_slots', [
            'id' => $oldSlot->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Rescheduled to new time',
        ]);

        // New slot should exist
        $this->assertDatabaseHas('booking_slots', [
            'booking_calendar_id' => $this->calendar->id,
            'business_contact_id' => $this->contact->id,
            'status' => 'reserved',
        ]);
    }

    /** @test */
    public function it_prevents_rescheduling_to_unavailable_slot()
    {
        Sanctum::actingAs($this->user);

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $conflictingTime = Carbon::parse('next monday 10:00');

        // Create conflicting booking
        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $conflictingTime,
            'end_time' => $conflictingTime->copy()->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $response = $this->postJson(route('api.booking-slots.reschedule', $slot->id), [
            'new_start_time' => $conflictingTime->toISOString(),
            'new_duration_minutes' => 30,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'message' => 'New time slot conflicts with existing booking'
        ]);
    }

    /** @test */
    public function it_can_list_booking_slots_with_filters()
    {
        Sanctum::actingAs($this->user);

        // Create various slots
        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
            'start_time' => now()->addDays(1),
        ]);

        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'pending',
            'start_time' => now()->addDays(2),
        ]);

        $response = $this->getJson(route('api.booking-slots.index', [
            'status' => 'confirmed',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'booking_slots' => [
                'data'
            ]
        ]);
    }

    /** @test */
    public function it_can_get_single_booking_slot_details()
    {
        Sanctum::actingAs($this->user);

        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
        ]);

        $response = $this->getJson(route('api.booking-slots.show', $slot->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'booking_slot' => [
                'id',
                'status',
                'start_time',
                'end_time',
                'booking_calendar',
                'business_contact'
            ]
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_booking_slots()
    {
        $response = $this->getJson(route('api.booking-slots.available', [
            'calendarId' => $this->calendar->id,
            'date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_access_another_business_slots()
    {
        Sanctum::actingAs($this->user);

        $otherBusiness = Business::factory()->create();
        $otherCalendar = BookingCalendar::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        $otherSlot = BookingSlot::factory()->create([
            'booking_calendar_id' => $otherCalendar->id,
            'business_id' => $otherBusiness->id,
        ]);

        $response = $this->getJson(route('api.booking-slots.show', $otherSlot->id));
        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields_for_reservation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.booking-slots.reserve'), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'booking_calendar_id',
            'start_time',
            'duration_minutes',
        ]);
    }

    /** @test */
    public function it_creates_temporary_contact_if_not_provided()
    {
        Sanctum::actingAs($this->user);

        $startTime = Carbon::parse('next monday 10:00');

        $response = $this->postJson(route('api.booking-slots.reserve'), [
            'booking_calendar_id' => $this->calendar->id,
            'start_time' => $startTime->toISOString(),
            'duration_minutes' => 30,
            'contact_name' => 'New Customer',
            'contact_phone' => '+255700000000',
            'contact_email' => 'new@example.com',
        ]);

        $response->assertStatus(201);

        // Should create a business contact
        $this->assertDatabaseHas('business_contacts', [
            'business_id' => $this->business->id,
            'phone' => '+255700000000',
            'name' => 'New Customer',
        ]);
    }
}
