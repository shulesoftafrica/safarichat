<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\OwnerReportDeliveryService;
use App\Services\WaSenderService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Reports go from the business's OWN WhatsApp number to the manager on its AI agent - never through the shared
 * platform number. No database and no real WhatsApp: the agent lookup, the instance lookup and the sender are faked.
 */
class OwnerReportDeliveryServiceTest extends TestCase
{
    /** @var array<int, array{0:string,1:string,2:array,3:mixed,4:?int}> */
    private array $sends = [];

    private function sender(?Exception $failWith = null): WaSenderService
    {
        $test = $this;

        return new class($test, $failWith) extends WaSenderService {
            public function __construct(private $test, private ?Exception $failWith)
            {
            }

            public function sendMessage(string $phoneNumber, string $message, array $options = [], $instance = null, ?int $userId = null): array
            {
                if ($this->failWith) {
                    throw $this->failWith;
                }

                $this->test->recordSend([$phoneNumber, $message, $options, $instance, $userId]);

                return ['success' => true];
            }
        };
    }

    public function recordSend(array $send): void
    {
        $this->sends[] = $send;
    }

    private function service(?string $agentNumber, ?WhatsappInstance $instance, ?Exception $failWith = null): OwnerReportDeliveryService
    {
        return new class($this->sender($failWith), $agentNumber, $instance) extends OwnerReportDeliveryService {
            public function __construct(WaSenderService $sender, private ?string $agentNumber, private ?WhatsappInstance $instance)
            {
                parent::__construct($sender);
            }

            protected function agentFallbackNumber(User $owner): ?string
            {
                return $this->agentNumber;
            }

            public function ownInstance(User $owner): ?WhatsappInstance
            {
                return $this->instance;
            }
        };
    }

    private function owner(?string $phone = '+255714825469', ?string $whatsapp = null, ?string $email = null): User
    {
        $u = new User();
        $u->forceFill(['name' => 'Ephraim', 'phone' => $phone, 'whatsapp_number' => $whatsapp, 'email' => $email]);
        $u->id = 45;

        return $u;
    }

    private function whatsappInstance(string $number = '+255689353642'): WhatsappInstance
    {
        $i = new WhatsappInstance(['phone_number' => $number, 'status' => 'connected', 'is_system_default' => false]);
        $i->id = 24;

        return $i;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->sends = [];
        Log::spy();
    }

    public function test_the_manager_number_on_the_ai_agent_wins(): void
    {
        $service = $this->service('0788 123 456', $this->whatsappInstance());
        // a local-format number gets the owner's country code (Tanzania by default)
        $this->assertSame('+255788123456', $service->resolveRecipient($this->owner()));

        $service = $this->service('+255 788 123 456', $this->whatsappInstance());
        $this->assertSame('+255788123456', $service->resolveRecipient($this->owner()));
    }

    public function test_number_normalisation(): void
    {
        $this->assertSame('+255788123456', OwnerReportDeliveryService::normalizeNumber('+255 788-123-456'));
        $this->assertSame('+255788123456', OwnerReportDeliveryService::normalizeNumber('0788123456'));
        $this->assertSame('+255788123456', OwnerReportDeliveryService::normalizeNumber('788123456'));
        $this->assertSame('+255788123456', OwnerReportDeliveryService::normalizeNumber('255788123456'));
        $this->assertSame('+255788123456', OwnerReportDeliveryService::normalizeNumber('00255788123456'));
        $this->assertSame('+254712345678', OwnerReportDeliveryService::normalizeNumber('0712345678', '+254'));
        $this->assertNull(OwnerReportDeliveryService::normalizeNumber(''));
        $this->assertNull(OwnerReportDeliveryService::normalizeNumber('abc'));
        $this->assertNull(OwnerReportDeliveryService::normalizeNumber('12345'));
    }

    public function test_without_an_agent_number_the_owners_whatsapp_then_phone_is_used(): void
    {
        $service = $this->service(null, $this->whatsappInstance());

        $this->assertSame('+255700000002', $service->resolveRecipient($this->owner('+255700000001', '+255700000002')));
        $this->assertSame('+255700000001', $service->resolveRecipient($this->owner('+255700000001', null)));
        $this->assertNull($service->resolveRecipient($this->owner(null, null)));
    }

    public function test_report_is_sent_from_the_businesss_own_instance_to_the_manager(): void
    {
        $instance = $this->whatsappInstance('+255689353642');
        $result = $this->service('+255788123456', $instance)->deliverWhatsApp($this->owner(), "📊 *Daily report*");

        $this->assertTrue($result['sent']);
        $this->assertSame('+255788123456', $result['recipient']);
        $this->assertCount(1, $this->sends);

        [$phone, $message, $options, $usedInstance, $userId] = $this->sends[0];
        $this->assertSame('+255788123456', $phone);
        $this->assertSame($instance, $usedInstance, 'must use the business instance, not the system line');
        $this->assertSame(45, $userId);
        $this->assertSame('owner_report', $options['log_message_type'], 'reports must not be counted as customer traffic');
    }

    public function test_no_connected_whatsapp_means_nothing_is_sent_and_the_reason_is_returned(): void
    {
        $result = $this->service('+255788123456', null)->deliverWhatsApp($this->owner(), 'report');

        $this->assertFalse($result['sent']);
        $this->assertSame(OwnerReportDeliveryService::REASON_NO_INSTANCE, $result['reason']);
        $this->assertCount(0, $this->sends);
    }

    public function test_a_manager_number_equal_to_the_connected_number_is_not_messaged(): void
    {
        $service = $this->service('+255 689 353 642', $this->whatsappInstance('+255689353642'));
        $result = $service->deliverWhatsApp($this->owner(), 'report');

        $this->assertFalse($result['sent']);
        $this->assertSame(OwnerReportDeliveryService::REASON_SAME_NUMBER, $result['reason']);
        $this->assertCount(0, $this->sends);
    }

    public function test_a_provider_failure_such_as_an_unpaid_session_is_reported_not_thrown(): void
    {
        $service = $this->service('+255788123456', $this->whatsappInstance(), new Exception("Failed to send message (HTTP 402): You didn't pay your session in time"));
        $result = $service->deliverWhatsApp($this->owner(), 'report');

        $this->assertFalse($result['sent']);
        $this->assertSame(OwnerReportDeliveryService::REASON_SEND_FAILED, $result['reason']);
        $this->assertStringContainsString("didn't pay your session", $result['error']);
    }

    public function test_no_recipient_at_all(): void
    {
        $result = $this->service(null, $this->whatsappInstance())->deliverWhatsApp($this->owner(null, null), 'report');

        $this->assertFalse($result['sent']);
        $this->assertSame(OwnerReportDeliveryService::REASON_NO_RECIPIENT, $result['reason']);
    }

    public function test_same_number_comparison_ignores_formatting(): void
    {
        $this->assertTrue(OwnerReportDeliveryService::sameNumber('+255 689-353-642', '255689353642'));
        $this->assertFalse(OwnerReportDeliveryService::sameNumber('+255689353642', '+255714825469'));
        $this->assertFalse(OwnerReportDeliveryService::sameNumber(null, null));
    }

    public function test_email_backup_needs_an_address_and_strips_whatsapp_formatting(): void
    {
        Mail::fake();
        $service = $this->service('+255788123456', null);

        $this->assertFalse($service->deliverEmail($this->owner(), 'Subject', 'body'), 'owner without an email address');

        $this->assertTrue($service->deliverEmail($this->owner('+255714825469', null, 'owner@example.com'), 'Daily report', '📊 *Daily* report'));
    }
}
