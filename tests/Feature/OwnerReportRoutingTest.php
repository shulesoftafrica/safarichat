<?php

namespace Tests\Feature;

use App\Models\CsMessageLog;
use App\Models\User;
use App\Services\CustomerSuccess\CsMessageRenderer;
use App\Services\OwnerReportDeliveryService;
use App\Services\SystemWhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The evening CS report is delivered through OwnerReportDeliveryService (the business's own number -> its manager),
 * with an email backup - and it never touches the shared platform line.
 *
 * Runs in a rolled-back transaction; the delivery service and the platform line are replaced by fakes.
 */
class OwnerReportRoutingTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->firstOrFail();
    }

    private function fakeDelivery(array $whatsApp, bool $emailOk): object
    {
        $fake = new class($whatsApp, $emailOk) extends OwnerReportDeliveryService {
            public array $emails = [];

            public function __construct(private array $wa, private bool $emailOk)
            {
            }

            public function deliverWhatsApp(User $owner, string $message): array
            {
                return $this->wa;
            }

            public function deliverEmail(User $owner, string $subject, string $message): bool
            {
                $this->emails[] = [$subject, $message];

                return $this->emailOk;
            }
        };

        $this->app->instance(OwnerReportDeliveryService::class, $fake);

        return $fake;
    }

    /** Fails the test if anything is sent through the shared platform number. */
    private function forbidSystemLine(): void
    {
        $this->app->instance(SystemWhatsAppService::class, new class extends SystemWhatsAppService {
            public function __construct()
            {
            }

            public function sendGenericMessage(...$args): bool
            {
                throw new \RuntimeException('the shared platform line must not carry owner reports');
            }
        });
    }

    private function vars(): array
    {
        return [
            'business_name' => 'Test Biz', 'today_date' => '11 Oct 2026', 'total_conversations' => 7, 'conversations_delta' => '',
            'new_prospects' => 2, 'prospects_delta' => '', 'active_leads' => 9, 'closed_today' => 1, 'stage_changes' => 3,
            'lead_new_count' => 1, 'lead_interested_count' => 2, 'lead_engaged_count' => 3, 'lead_converted_count' => 1,
            'lead_churned_count' => 0, 'recommendation' => 'Follow up',
        ];
    }

    public function test_report_is_delivered_by_the_business_own_line_and_logged(): void
    {
        $this->forbidSystemLine();
        $this->fakeDelivery(['sent' => true, 'reason' => null, 'recipient' => '+255788123456', 'instance_id' => 5], false);
        $before = CsMessageLog::where('user_id', $this->owner->id)->where('type', 'daily_summary')->count();

        $result = app(CsMessageRenderer::class)->sendToOwner($this->owner, 'daily_summary', $this->vars(), 1);

        $this->assertTrue($result['sent']);
        $this->assertSame('whatsapp', $result['channel']);
        $this->assertSame($before + 1, CsMessageLog::where('user_id', $this->owner->id)->where('type', 'daily_summary')->count());
    }

    public function test_when_the_business_line_is_unavailable_the_report_goes_by_email_with_the_reason(): void
    {
        $this->forbidSystemLine();
        $fake = $this->fakeDelivery(['sent' => false, 'reason' => OwnerReportDeliveryService::REASON_NO_INSTANCE, 'recipient' => '+255788123456', 'instance_id' => null], true);

        $result = app(CsMessageRenderer::class)->sendToOwner($this->owner, 'daily_summary', $this->vars(), 1);

        $this->assertTrue($result['sent']);
        $this->assertSame('email', $result['channel']);
        $this->assertCount(1, $fake->emails);
        $this->assertStringContainsString('not connected', $fake->emails[0][1]);
    }

    public function test_nothing_logged_as_sent_when_every_channel_fails(): void
    {
        $this->forbidSystemLine();
        $this->fakeDelivery(['sent' => false, 'reason' => OwnerReportDeliveryService::REASON_SEND_FAILED, 'recipient' => '+255788123456', 'instance_id' => 5], false);
        $before = CsMessageLog::where('user_id', $this->owner->id)->where('type', 'daily_summary')->count();

        $result = app(CsMessageRenderer::class)->sendToOwner($this->owner, 'daily_summary', $this->vars(), 1);

        $this->assertFalse($result['sent']);
        $this->assertNull($result['channel']);
        $this->assertSame($before, CsMessageLog::where('user_id', $this->owner->id)->where('type', 'daily_summary')->count());
    }
}
