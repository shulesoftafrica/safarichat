<?php

namespace Tests\Unit;

use App\Services\AppointmentTimeResolver;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * No database, no framework: pure parsing of what a customer asked for.
 *
 * The first test replays the production chat: booking for 10:00, then
 * "Sorry please guys can we do it at 11:00am ...". The system kept 10:00 while the AI promised 11:00.
 */
class AppointmentTimeResolverTest extends TestCase
{
    /** Thursday 8 Oct 2026, 16:23 (when the customer asked to move the demo) */
    private function now(): Carbon
    {
        return Carbon::create(2026, 10, 8, 16, 23, 0, 'Africa/Nairobi');
    }

    private function at(string $iso): string
    {
        return Carbon::parse($iso, 'Africa/Nairobi')->toDateTimeString();
    }

    public function test_production_chat_move_from_10_to_11_keeps_the_day_and_changes_the_time(): void
    {
        $existing = Carbon::create(2026, 10, 9, 10, 0, 0, 'Africa/Nairobi');

        $requested = AppointmentTimeResolver::resolve(
            'Sorry please guys can we do it at 11:00am please please because I had forgotten that tomorrow '
            . "I'm supposed to attend at hospital from 8:00am to 10:00am please please",
            "No worries at all! We can reschedule the demonstration for 11:00 AM tomorrow.",
            $existing,
            $this->now()
        );

        // "8:00am to 10:00am" are mentioned too, but the requested slot is the one after "can we do it at"
        $this->assertSame($this->at('2026-10-09 11:00'), $requested->toDateTimeString(),
            'must be 11:00 on the existing day, not 8:00 or 10:00');
    }

    public function test_the_ai_confirmation_is_used_when_the_customer_gives_no_time(): void
    {
        $requested = AppointmentTimeResolver::resolve(
            'ok thanks',
            'Absolutely, we can reschedule the demonstration to 11:00 AM tomorrow.',
            null,
            $this->now()
        );

        $this->assertSame($this->at('2026-10-09 11:00'), $requested->toDateTimeString());
    }

    public function test_a_bare_time_with_no_booking_rolls_to_the_next_time_it_occurs(): void
    {
        // 16:23 now: "at 10" (10am) has passed today, so it means tomorrow
        $requested = AppointmentTimeResolver::resolve('can we meet at 10', null, null, $this->now());
        $this->assertSame($this->at('2026-10-09 10:00'), $requested->toDateTimeString());

        // 17:00 is still ahead today
        $requested = AppointmentTimeResolver::resolve('book me for 5pm', null, null, $this->now());
        $this->assertSame($this->at('2026-10-08 17:00'), $requested->toDateTimeString());
    }

    public function test_dates_and_weekdays(): void
    {
        $this->assertSame($this->at('2026-10-09 15:00'),
            AppointmentTimeResolver::resolve('tomorrow at 3pm please', null, null, $this->now())->toDateTimeString());

        $this->assertSame($this->at('2026-10-09 09:30'),
            AppointmentTimeResolver::resolve('kesho 9:30am', null, null, $this->now())->toDateTimeString());
    }

    public function test_weekday_means_the_next_such_day_after_today(): void
    {
        // Thursday 8 Oct: "Friday" is the very next day
        $this->assertSame($this->at('2026-10-09 14:00'),
            AppointmentTimeResolver::resolve('Friday 2pm works', null, null, $this->now())->toDateTimeString());
    }

    public function test_explicit_calendar_dates(): void
    {
        $this->assertSame($this->at('2026-10-20 11:00'),
            AppointmentTimeResolver::resolve('can we do 20 Oct at 11am', null, null, $this->now())->toDateTimeString());

        $this->assertSame($this->at('2026-11-02 16:00'),
            AppointmentTimeResolver::resolve('November 2nd, 4pm', null, null, $this->now())->toDateTimeString());
    }

    public function test_noon_midnight_and_24h_formats(): void
    {
        $this->assertSame($this->at('2026-10-09 12:00'),
            AppointmentTimeResolver::resolve('tomorrow 12pm', null, null, $this->now())->toDateTimeString());
        $this->assertSame($this->at('2026-10-09 00:00'),
            AppointmentTimeResolver::resolve('tomorrow 12am', null, null, $this->now())->toDateTimeString());
        $this->assertSame($this->at('2026-10-09 14:30'),
            AppointmentTimeResolver::resolve('tomorrow 14:30', null, null, $this->now())->toDateTimeString());
        $this->assertSame($this->at('2026-10-09 10:00'),
            AppointmentTimeResolver::resolve('tomorrow 10h00', null, null, $this->now())->toDateTimeString());
    }

    public function test_no_time_means_null_so_an_existing_booking_is_left_alone(): void
    {
        $existing = Carbon::create(2026, 10, 9, 10, 0, 0, 'Africa/Nairobi');

        foreach (['Do you have a demo available?', 'how much does it cost', 'I will be at the hospital', 'ok'] as $text) {
            $this->assertNull(AppointmentTimeResolver::resolve($text, 'Sure, happy to help.', $existing, $this->now()), $text);
        }
    }

    public function test_the_production_reschedule_message_now_produces_an_action_with_the_new_time(): void
    {
        $existing = Carbon::create(2026, 10, 9, 10, 0, 0, 'Africa/Nairobi');

        // "can we do it at 11:00am" has no booking keyword, so the AI produced NO action before.
        $actions = AppointmentTimeResolver::applyToActions(
            [],
            'Sorry please guys can we do it at 11:00am please please',
            'No worries at all! We can reschedule the demonstration for 11:00 AM tomorrow.',
            $existing,
            $this->now()
        );

        $this->assertArrayHasKey('schedule_appointment', $actions);
        $this->assertSame($this->at('2026-10-09 11:00'), $actions['schedule_appointment']['datetime']);
    }

    public function test_a_keyword_booking_action_gets_the_requested_time_instead_of_the_default(): void
    {
        // the keyword rule produced the action but no time -> the handler used "tomorrow 10:00"
        $actions = AppointmentTimeResolver::applyToActions(
            ['schedule_appointment' => ['detected' => true, 'type' => 'demo', 'urgency' => 'normal']],
            'I want to book a demo tomorrow at 2pm',
            'Great, I have booked you for 2:00 PM tomorrow.',
            null,
            $this->now()
        );

        $this->assertSame($this->at('2026-10-09 14:00'), $actions['schedule_appointment']['datetime']);
        $this->assertSame('demo', $actions['schedule_appointment']['type']); // original fields kept
    }

    public function test_actions_are_untouched_when_no_time_was_given(): void
    {
        $existing = Carbon::create(2026, 10, 9, 10, 0, 0, 'Africa/Nairobi');
        $keywordOnly = ['schedule_appointment' => ['detected' => true, 'type' => 'demo', 'urgency' => 'normal']];

        // "demo" matched a keyword, but there is no time: no datetime must be added, so the handler leaves
        // the existing booking alone instead of resetting it to 10:00
        $result = AppointmentTimeResolver::applyToActions($keywordOnly, 'what does the demo include?', 'It covers...', $existing, $this->now());
        $this->assertSame($keywordOnly, $result);
        $this->assertArrayNotHasKey('datetime', $result['schedule_appointment']);

        // and a message with no booking intent produces no action at all
        $this->assertSame([], AppointmentTimeResolver::applyToActions([], 'how much is it', 'From 20,000.', $existing, $this->now()));
    }

    public function test_a_time_in_an_unrelated_message_does_not_move_a_booking(): void
    {
        $existing = Carbon::create(2026, 10, 9, 10, 0, 0, 'Africa/Nairobi');

        // contains a time but no change intent and no booking keyword: ignore
        $result = AppointmentTimeResolver::applyToActions([], 'we open at 9am every day', 'Thanks for sharing.', $existing, $this->now());
        $this->assertSame([], $result);
    }

    public function test_change_detection(): void
    {
        $this->assertTrue(AppointmentTimeResolver::looksLikeChange('Sorry can we do it at 11:00am please'));
        $this->assertTrue(AppointmentTimeResolver::looksLikeChange('please reschedule'));
        $this->assertTrue(AppointmentTimeResolver::looksLikeChange('thanks', 'We can reschedule the demo for 11:00 AM'));

        $this->assertFalse(AppointmentTimeResolver::looksLikeChange('how much is the premium plan'));
        $this->assertFalse(AppointmentTimeResolver::looksLikeChange('thanks', 'Our pricing starts at 20,000 per month.'));
    }
}
