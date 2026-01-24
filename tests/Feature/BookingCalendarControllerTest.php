<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BookingCalendar;
use App\Models\Business;
use App\Models\User;
use App\Models\BillingAccount;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);

        // Set up billing account with Starter plan
        BillingAccount::create([
            'business_id' => $this->business->id,
            'customer_id' => 'test_customer_123',
            'subscription_plan' => 'starter',
            'subscription_status' => 'active',
        ]);
    }

    /** @test */
    public function user_can_view_booking_calendars_list()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('booking-calendars.index'));

        $response->assertStatus(200);
        $response->assertSee($calendar->name);
        $response->assertViewHas('calendars');
        $response->assertViewHas('limitCheck');
    }

    /** @test */
    public function user_can_view_create_form_if_within_limits()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('booking-calendars.create'));

        $response->assertStatus(200);
        $response->assertViewIs('booking-calendars.create');
    }

    /** @test */
    public function user_cannot_create_calendar_beyond_plan_limits()
    {
        $this->actingAs($this->user);

        // Create 1 calendar (Starter plan limit)
        BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
        ]);

        // Try to create another
        $response = $this->post(route('booking-calendars.store'), [
            'name' => 'Second Calendar',
            'calendar_type' => 'consultation',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 10,
            'min_advance_hours' => 2,
            'max_advance_days' => 30,
            'working_hours' => [
                ['day' => 'monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, BookingCalendar::count());
    }

    /** @test */
    public function user_can_create_valid_calendar()
    {
        $this->actingAs($this->user);

        $calendarData = [
            'name' => 'Product Demos',
            'description' => 'Calendar for product demonstrations',
            'calendar_type' => 'demo',
            'default_duration_minutes' => 45,
            'buffer_minutes' => 15,
            'max_bookings_per_day' => 6,
            'max_bookings_per_week' => 25,
            'min_advance_hours' => 4,
            'max_advance_days' => 60,
            'allow_ai_booking' => true,
            'allow_manual_booking' => true,
            'require_confirmation' => false,
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
                ['start' => '12:00', 'end' => '13:00'],
            ],
        ];

        $response = $this->post(route('booking-calendars.store'), $calendarData);

        $response->assertRedirect(route('booking-calendars.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('booking_calendars', [
            'name' => 'Product Demos',
            'calendar_type' => 'demo',
            'default_duration_minutes' => 45,
            'buffer_minutes' => 15,
        ]);
    }

    /** @test */
    public function calendar_requires_valid_data()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('booking-calendars.store'), [
            'name' => '', // Missing required field
            'calendar_type' => 'invalid_type',
            'default_duration_minutes' => 5, // Too short
        ]);

        $response->assertSessionHasErrors(['name', 'calendar_type', 'default_duration_minutes']);
    }

    /** @test */
    public function user_can_view_edit_form_for_own_calendar()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('booking-calendars.edit', $calendar->id));

        $response->assertStatus(200);
        $response->assertViewIs('booking-calendars.edit');
        $response->assertViewHas('calendar', $calendar);
    }

    /** @test */
    public function user_can_update_calendar()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->put(route('booking-calendars.update', $calendar->id), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'calendar_type' => 'consultation',
            'default_duration_minutes' => 60,
            'buffer_minutes' => 10,
            'min_advance_hours' => 2,
            'max_advance_days' => 30,
            'working_hours' => [
                ['day' => 'monday', 'enabled' => true, 'start' => '10:00', 'end' => '18:00'],
            ],
        ]);

        $response->assertRedirect(route('booking-calendars.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('booking_calendars', [
            'id' => $calendar->id,
            'name' => 'Updated Name',
            'default_duration_minutes' => 60,
        ]);
    }

    /** @test */
    public function user_can_toggle_calendar_status()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('booking-calendars.toggle', $calendar->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($calendar->fresh()->is_active);

        // Toggle again
        $response = $this->post(route('booking-calendars.toggle', $calendar->id));
        $this->assertTrue($calendar->fresh()->is_active);
    }

    /** @test */
    public function user_can_delete_calendar_without_bookings()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('booking-calendars.destroy', $calendar->id));

        $response->assertRedirect(route('booking-calendars.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('booking_calendars', ['id' => $calendar->id]);
    }

    /** @test */
    public function user_cannot_delete_calendar_with_upcoming_bookings()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
        ]);

        // Create an upcoming booking
        \App\Models\BookingSlot::factory()->create([
            'booking_calendar_id' => $calendar->id,
            'business_id' => $this->business->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $response = $this->delete(route('booking-calendars.destroy', $calendar->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('booking_calendars', ['id' => $calendar->id]);
    }

    /** @test */
    public function user_cannot_access_another_business_calendar()
    {
        $this->actingAs($this->user);

        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $otherCalendar = BookingCalendar::factory()->create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->get(route('booking-calendars.edit', $otherCalendar->id));
        $response->assertStatus(404);

        $response = $this->put(route('booking-calendars.update', $otherCalendar->id), [
            'name' => 'Hacked Name',
        ]);
        $response->assertStatus(404);
    }

    /** @test */
    public function preview_endpoint_returns_available_slots()
    {
        $this->actingAs($this->user);

        $calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $startDate = now()->addDays(7)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $response = $this->get(route('booking-calendars.preview', [
            'id' => $calendar->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'calendar',
            'slots',
            'total_slots'
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_calendars()
    {
        $response = $this->get(route('booking-calendars.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('booking-calendars.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function trial_plan_user_sees_upgrade_message()
    {
        // Update to trial plan
        $this->business->billingAccount->update(['subscription_plan' => 'trial']);

        $this->actingAs($this->user);

        $response = $this->get(route('booking-calendars.index'));

        $response->assertStatus(200);
        $response->assertSee('upgrade'); // Should see upgrade-related text
    }
}
