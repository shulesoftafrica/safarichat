<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SubscriptionNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * No database and no real WhatsApp: the sender and the one DB lookup are faked.
 *
 * Production case: the owner (+255714825469) had NO email, the old summary was email-only, so the 07:00 job
 * logged "Cannot send daily summary - user has no email", printed "Sent" anyway, and delivered nothing.
 */
class DailySummaryWhatsAppTest extends TestCase
{
    private function service(bool $whatsappOk = true, int $missed = 0): object
    {
        return new class($whatsappOk, $missed) extends SubscriptionNotificationService {
            public array $sent = [];

            public function __construct(private bool $ok, private int $missed)
            {
            }

            protected function sendSummaryOnWhatsApp(string $phone, string $message): bool
            {
                $this->sent[] = [$phone, $message];

                return $this->ok;
            }

            protected function missedAutomationsCount(User $user, Carbon $date): int
            {
                return $this->missed;
            }
        };
    }

    private function owner(int $id, ?string $email = null, ?string $phone = '+255714825469', ?string $whatsapp = null): User
    {
        $u = new User();
        $u->forceFill(['name' => 'Ephraim Swilla', 'email' => $email, 'phone' => $phone, 'whatsapp_number' => $whatsapp]);
        $u->id = $id;

        return $u;
    }

    private function stats(array $over = []): array
    {
        return array_merge([
            'has_activity' => true, 'has_issues' => false,
            'total_messages' => 430, 'successful_messages' => 430, 'failed_messages' => 0,
            'replies_received' => 22, 'ai_conversations' => 9, 'new_handoffs' => 2,
            'overdue_handoffs' => 0, 'new_leads' => 5, 'success_rate' => 100,
        ], $over);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'notifications.daily_summary.whatsapp' => true]);
        Cache::flush();
        Log::spy();
    }

    public function test_an_owner_with_no_email_gets_the_summary_on_whatsapp(): void
    {
        $service = $this->service();

        $result = $service->sendDailySummary($this->owner(45), $this->stats());

        $this->assertTrue($result['delivered']);
        $this->assertTrue($result['whatsapp']);
        $this->assertFalse($result['email']);                    // no address: no email, and no early return
        $this->assertCount(1, $service->sent);
        $this->assertSame('+255714825469', $service->sent[0][0]);

        $message = $service->sent[0][1];
        $this->assertStringContainsString('Daily summary', $message);
        $this->assertStringContainsString('Messages sent: *430*', $message);
        $this->assertStringContainsString('Replies received: *22*', $message);
        $this->assertStringContainsString('New leads: *5*', $message);
        $this->assertStringContainsString('Hi Ephraim', $message);
    }

    public function test_whatsapp_number_is_preferred_over_phone(): void
    {
        $service = $this->service();
        $service->sendDailySummary($this->owner(46, null, '+255700000001', '+255700000002'), $this->stats());

        $this->assertSame('+255700000002', $service->sent[0][0]);
    }

    public function test_no_phone_and_no_email_is_reported_as_not_delivered_and_nothing_is_sent(): void
    {
        $service = $this->service();
        $result = $service->sendDailySummary($this->owner(47, null, null, null), $this->stats());

        $this->assertFalse($result['delivered']);
        $this->assertSame('no_phone', $result['reason']);
        $this->assertCount(0, $service->sent);
    }

    public function test_running_the_command_twice_in_a_day_does_not_message_the_owner_twice(): void
    {
        $service = $this->service();
        $owner = $this->owner(48);

        $service->sendDailySummary($owner, $this->stats());
        $second = $service->sendDailySummary($owner, $this->stats());

        $this->assertCount(1, $service->sent);
        $this->assertTrue($second['delivered']);
        $this->assertSame('already_sent_today', $second['reason']);
    }

    public function test_a_failed_send_is_reported_and_can_be_retried_later(): void
    {
        $failing = $this->service(false);
        $owner = $this->owner(49);

        $result = $failing->sendDailySummary($owner, $this->stats());
        $this->assertFalse($result['delivered']);
        $this->assertSame('whatsapp_send_failed', $result['reason']);

        // our own "already sent" marker must not block the retry
        $working = $this->service(true);
        $retry = $working->sendDailySummary($owner, $this->stats());
        $this->assertTrue($retry['delivered']);
        $this->assertCount(1, $working->sent);
    }

    public function test_it_can_be_switched_off_by_config(): void
    {
        config(['notifications.daily_summary.whatsapp' => false]);
        $service = $this->service();

        $result = $service->sendDailySummary($this->owner(50), $this->stats());

        $this->assertFalse($result['delivered']);
        $this->assertSame('whatsapp_disabled', $result['reason']);
        $this->assertCount(0, $service->sent);
    }

    public function test_message_flags_what_needs_attention_only_when_something_does(): void
    {
        $service = $this->service(true, 3);
        $date = Carbon::create(2026, 10, 8, 7, 0, 0);

        $calm = $service->buildDailySummaryWhatsAppMessage($this->owner(51), $this->stats(), 'Acme', $date, 0);
        $this->assertStringNotContainsString('Needs your attention', $calm);
        $this->assertStringContainsString('Thursday, 8 Oct 2026', $calm);
        $this->assertStringContainsString('Daily summary — Acme', $calm);

        $busy = $service->buildDailySummaryWhatsAppMessage(
            $this->owner(51),
            $this->stats(['overdue_handoffs' => 2, 'failed_messages' => 14]),
            'Acme', $date, 3
        );
        $this->assertStringContainsString('Needs your attention', $busy);
        $this->assertStringContainsString('2 customer escalation(s) are overdue', $busy);
        $this->assertStringContainsString('14 messages failed', $busy);
        $this->assertStringContainsString('3 automation(s) were missed', $busy);
    }
}
