<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\Business;
use App\Models\BusinessContact;
use App\Models\User;
use App\Services\AiWhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AiBookingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $business;
    protected $calendar;
    protected $contact;
    protected $aiService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->contact = BusinessContact::factory()->create([
            'business_id' => $this->business->id,
            'phone' => '+255712345678',
        ]);

        $this->calendar = BookingCalendar::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'name' => 'Sales Consultation',
            'type' => 'consultation',
            'duration_minutes' => 30,
            'availability_rules' => [
                'working_hours' => [
                    ['day' => 'monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'tuesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'wednesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'thursday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                    ['day' => 'friday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ],
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'blackout_dates' => []
            ],
        ]);

        $this->aiService = app(AiWhatsAppService::class);
    }

    /** @test */
    public function ai_can_schedule_appointment_successfully()
    {
        // Simulate AI scheduling request
        $requestedDateTime = Carbon::parse('next monday 10:00');

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedDateTime,
            notes: 'Customer interested in product demo'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment', $result);
        $this->assertArrayHasKey('booking_slot', $result);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
            'appointment_type' => 'consultation',
            'status' => 'pending',
        ]);

        // Verify booking slot was reserved
        $this->assertDatabaseHas('booking_slots', [
            'booking_calendar_id' => $this->calendar->id,
            'business_contact_id' => $this->contact->id,
            'status' => 'reserved',
        ]);

        // Verify they are linked
        $appointment = Appointment::where('business_id', $this->business->id)->first();
        $this->assertNotNull($appointment->booking_slot_id);
    }

    /** @test */
    public function ai_detects_conflicting_appointment_and_suggests_alternatives()
    {
        $requestedTime = Carbon::parse('next monday 10:00');

        // Create existing booking at requested time
        BookingSlot::factory()->create([
            'booking_calendar_id' => $this->calendar->id,
            'business_id' => $this->business->id,
            'start_time' => $requestedTime,
            'end_time' => $requestedTime->copy()->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedTime,
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Time slot not available', $result['message']);
        $this->assertArrayHasKey('alternative_slots', $result);
        $this->assertNotEmpty($result['alternative_slots']);
        
        // Alternatives should be on the same day or near future
        $this->assertGreaterThan(0, count($result['alternative_slots']));
    }

    /** @test */
    public function ai_respects_calendar_working_hours()
    {
        // Try to book outside working hours (6 AM, before 9 AM opening)
        $outsideHours = Carbon::parse('next monday 06:00');

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $outsideHours,
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not available', $result['message']);
        $this->assertArrayHasKey('alternative_slots', $result);
    }

    /** @test */
    public function ai_respects_break_times()
    {
        // Try to book during lunch break (12:00-13:00)
        $breakTime = Carbon::parse('next monday 12:30');

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $breakTime,
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('alternative_slots', $result);
    }

    /** @test */
    public function ai_can_find_next_available_slot()
    {
        // Fill up all slots for Monday
        $monday = Carbon::parse('next monday');
        $calendar = BookingCalendar::find($this->calendar->id);
        
        // Book all morning slots
        for ($hour = 9; $hour < 12; $hour++) {
            BookingSlot::factory()->create([
                'booking_calendar_id' => $this->calendar->id,
                'business_id' => $this->business->id,
                'start_time' => $monday->copy()->setTime($hour, 0),
                'end_time' => $monday->copy()->setTime($hour, 0)->addMinutes(30),
                'status' => 'confirmed',
            ]);
        }

        // Request next available
        $result = $this->aiService->findNextAvailableSlot(
            businessId: $this->business->id,
            appointmentType: 'consultation',
            preferredDate: $monday
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('next_available', $result);
        
        // Should suggest afternoon slot (after lunch break 13:00-17:00)
        $suggestedTime = Carbon::parse($result['next_available']['start_time']);
        $this->assertGreaterThanOrEqual(13, $suggestedTime->hour);
    }

    /** @test */
    public function ai_automatically_sends_confirmation_message()
    {
        // Mock WhatsApp service to verify message was sent
        $whatsappMock = Mockery::mock('overload:' . \App\Services\WhatsAppService::class);
        $whatsappMock->shouldReceive('sendMessage')
            ->once()
            ->with(
                Mockery::on(function ($phone) {
                    return $phone === '+255712345678';
                }),
                Mockery::on(function ($message) {
                    return str_contains($message, 'appointment') && 
                           str_contains($message, 'confirmed');
                })
            )
            ->andReturn(['success' => true]);

        $requestedDateTime = Carbon::parse('next monday 10:00');

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedDateTime,
            autoConfirm: true,
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function ai_handles_no_available_calendars()
    {
        // Deactivate calendar
        $this->calendar->update(['is_active' => false]);

        $result = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: Carbon::parse('next monday 10:00'),
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no active calendar', strtolower($result['message']));
    }

    /** @test */
    public function ai_prevents_double_booking_same_contact()
    {
        $requestedTime = Carbon::parse('next monday 10:00');

        // Create first appointment
        $firstResult = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedTime,
        );

        $this->assertTrue($firstResult['success']);

        // Try to book same time for same contact
        $secondResult = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedTime,
        );

        $this->assertFalse($secondResult['success']);
        $this->assertStringContainsString('already have', strtolower($secondResult['message']));
    }

    /** @test */
    public function ai_can_reschedule_existing_appointment()
    {
        $originalTime = Carbon::parse('next monday 10:00');

        // Create original appointment
        $originalResult = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $originalTime,
        );

        $appointmentId = $originalResult['appointment']->id;

        // Reschedule to new time
        $newTime = Carbon::parse('next monday 14:00');

        $rescheduleResult = $this->aiService->rescheduleAppointment(
            appointmentId: $appointmentId,
            newDateTime: $newTime,
            reason: 'Customer requested new time'
        );

        $this->assertTrue($rescheduleResult['success']);

        // Old slot should be cancelled
        $this->assertDatabaseHas('booking_slots', [
            'id' => $originalResult['booking_slot']->id,
            'status' => 'cancelled',
        ]);

        // Appointment should have new time
        $appointment = Appointment::find($appointmentId);
        $this->assertEquals($newTime->format('Y-m-d H:i'), $appointment->scheduled_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function ai_sends_reminder_for_upcoming_appointments()
    {
        // Create appointment for tomorrow
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'business_contact_id' => $this->contact->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        // Mock WhatsApp service
        $whatsappMock = Mockery::mock('overload:' . \App\Services\WhatsAppService::class);
        $whatsappMock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['success' => true]);

        $result = $this->aiService->sendAppointmentReminder($appointment->id);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'reminder_sent_at' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function end_to_end_booking_workflow()
    {
        // Step 1: Customer requests appointment via WhatsApp
        $requestedTime = Carbon::parse('next monday 10:00');
        
        $bookingResult = $this->aiService->scheduleAppointment(
            businessId: $this->business->id,
            contactId: $this->contact->id,
            appointmentType: 'consultation',
            requestedDateTime: $requestedTime,
            notes: 'End-to-end test booking'
        );

        $this->assertTrue($bookingResult['success']);
        $appointment = $bookingResult['appointment'];
        $slot = $bookingResult['booking_slot'];

        // Step 2: Verify appointment is pending
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals('reserved', $slot->status);

        // Step 3: Business confirms appointment
        $appointment->update(['status' => 'confirmed']);
        $slot->update(['status' => 'confirmed']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);

        // Step 4: Reminder sent 24 hours before
        $reminderResult = $this->aiService->sendAppointmentReminder($appointment->id);
        $this->assertTrue($reminderResult['success']);

        // Step 5: Appointment occurs and marked as completed
        $appointment->update(['status' => 'completed']);
        $slot->update(['status' => 'completed']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('booking_slots', [
            'id' => $slot->id,
            'status' => 'completed',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
