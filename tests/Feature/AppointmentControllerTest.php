<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\Business;
use App\Models\BusinessContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentControllerTest extends TestCase
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
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_appointments_list()
    {
        // Create appointments
        Appointment::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'booked_by_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('appointments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('appointments.index');
        $response->assertViewHas('appointments');
    }

    /** @test */
    public function user_can_filter_appointments_by_status()
    {
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('appointments.index', ['status' => 'confirmed']));

        $response->assertStatus(200);
        $response->assertViewHas('appointments', function ($appointments) {
            return $appointments->every(fn($appt) => $appt->status === 'confirmed');
        });
    }

    /** @test */
    public function user_can_filter_appointments_by_date_range()
    {
        $tomorrow = now()->addDay();
        $nextWeek = now()->addWeek();

        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'scheduled_at' => $tomorrow,
        ]);

        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'scheduled_at' => $nextWeek,
        ]);

        $response = $this->get(route('appointments.index', [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => $tomorrow->addDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('appointments', function ($appointments) {
            return $appointments->count() === 1;
        });
    }

    /** @test */
    public function user_can_view_single_appointment()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
            'booking_slot_id' => $slot->id,
        ]);

        $response = $this->get(route('appointments.show', $appointment->id));

        $response->assertStatus(200);
        $response->assertViewIs('appointments.show');
        $response->assertViewHas('appointment', function ($appt) use ($appointment) {
            return $appt->id === $appointment->id;
        });
    }

    /** @test */
    public function user_can_confirm_pending_appointment()
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('appointments.confirm', $appointment->id));

        $response->assertRedirect(route('appointments.show', $appointment->id));
        $response->assertSessionHas('success', 'Appointment confirmed successfully');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function confirming_appointment_also_confirms_booking_slot()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'reserved',
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'booking_slot_id' => $slot->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('appointments.confirm', $appointment->id));

        $response->assertRedirect();
        
        // Both should be confirmed
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function user_can_cancel_appointment()
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $response = $this->post(route('appointments.cancel', $appointment->id), [
            'cancellation_reason' => 'Customer requested cancellation'
        ]);

        $response->assertRedirect(route('appointments.show', $appointment->id));
        $response->assertSessionHas('success', 'Appointment cancelled successfully');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation',
        ]);
    }

    /** @test */
    public function cancelling_appointment_also_cancels_booking_slot()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'booking_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        $response = $this->post(route('appointments.cancel', $appointment->id), [
            'cancellation_reason' => 'No longer needed'
        ]);

        $response->assertRedirect();

        // Both should be cancelled
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function user_can_mark_appointment_as_completed()
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->subHour(), // Past appointment
        ]);

        $response = $this->post(route('appointments.complete', $appointment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Appointment marked as completed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function user_can_mark_appointment_as_no_show()
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->post(route('appointments.no-show', $appointment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Appointment marked as no-show');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'no_show',
        ]);
    }

    /** @test */
    public function user_cannot_complete_future_appointment()
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->addHour(), // Future appointment
        ]);

        $response = $this->post(route('appointments.complete', $appointment->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed', // Unchanged
        ]);
    }

    /** @test */
    public function user_can_reschedule_appointment()
    {
        $slot = BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'booking_slot_id' => $slot->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'confirmed',
        ]);

        $newDateTime = now()->addDays(2)->setTime(10, 0);

        $response = $this->post(route('appointments.reschedule', $appointment->id), [
            'new_scheduled_at' => $newDateTime->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Old slot should be cancelled
        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'cancelled',
        ]);

        // Appointment should have new time
        $appointment->refresh();
        $this->assertEquals($newDateTime->format('Y-m-d H:i'), $appointment->scheduled_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function user_cannot_access_another_business_appointment()
    {
        $otherBusiness = Business::factory()->create();
        $otherAppointment = Appointment::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        $response = $this->get(route('appointments.show', $otherAppointment->id));
        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_appointments()
    {
        auth()->logout();

        $response = $this->get(route('appointments.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_shows_pending_count_badge()
    {
        // Create pending appointments
        Appointment::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('appointments.index'));

        $response->assertStatus(200);
        $response->assertSee('3'); // Pending count
    }
}
