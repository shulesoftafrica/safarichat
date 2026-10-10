<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\WhatsAppSessionAlertService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * When a customer writes to a WhatsApp number whose WaSender session is unpaid (WaSender answers HTTP 402), the
 * owner must get a Phone-SMS immediately - rate limited, and never when the session is fine.
 *
 * Runs inside rolled-back transactions on both connections; nothing is sent anywhere (Http is faked).
 */
class WhatsAppUnpaidSessionAlertTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'notification'];

    private User $alertUser;
    private WhatsappInstance $instance;
    private const ALERT_PHONE = '+255700999888';

    protected function setUp(): void
    {
        parent::setUp();

        // The notifications schema belongs to another app and may not exist in a developer database. Postgres DDL
        // is transactional, so create a stand-in inside the transaction that is rolled back with it.
        $notification = DB::connection('notification');
        if (! Schema::connection('notification')->hasTable('messages')) {
            $notification->statement('CREATE SCHEMA IF NOT EXISTS notifications');
            $notification->statement('CREATE TABLE notifications.messages (id bigserial primary key, channel varchar(50), recipient varchar(255), message text, status varchar(30), priority varchar(30), schema_name varchar(255), ip_address varchar(64), metadata text, created_at timestamp, updated_at timestamp)');
        }

        $this->alertUser = User::query()->firstOrFail();
        config([
            'notifications.wasender_unpaid_alert.enabled' => true,
            'notifications.wasender_unpaid_alert.user_id' => $this->alertUser->id,
            'notifications.wasender_unpaid_alert.phone' => self::ALERT_PHONE,
            'notifications.wasender_unpaid_alert.cooldown_minutes' => 30,
        ]);

        $this->instance = new WhatsappInstance(['user_id' => $this->alertUser->id, 'api_key' => 'session-key', 'phone_number' => '+255700111222']);
        $this->instance->id = 987654321;

        Cache::forget('wasender_unpaid_state:' . $this->instance->id);
        Cache::forget('wasender_unpaid_alert:' . $this->instance->id);
    }

    protected function tearDown(): void
    {
        Cache::forget('wasender_unpaid_state:' . $this->instance->id);
        Cache::forget('wasender_unpaid_alert:' . $this->instance->id);
        parent::tearDown();
    }

    private function alertRows()
    {
        return DB::connection('notification')->table('messages')
            ->where('channel', 'phone-sms')
            ->where('recipient', self::ALERT_PHONE)
            ->get();
    }

    public function test_unpaid_text_detection(): void
    {
        $this->assertTrue(WhatsAppSessionAlertService::isUnpaidSessionText("You didn't pay your session in time. Please retry your failed payments to restore functionality."));
        $this->assertTrue(WhatsAppSessionAlertService::isUnpaidSessionText('HTTP 402 Payment Required'));
        $this->assertFalse(WhatsAppSessionAlertService::isUnpaidSessionText('Recipient is not on WhatsApp'));
        $this->assertFalse(WhatsAppSessionAlertService::isUnpaidSessionText(null));
        $this->assertFalse(WhatsAppSessionAlertService::isUnpaidSessionText(''));
    }

    public function test_unpaid_session_sends_one_urgent_phone_sms_to_the_owner(): void
    {
        Http::fake(['*wasenderapi.com/*' => Http::response(['success' => false, 'message' => "You didn't pay your session in time."], 402)]);
        $before = $this->alertRows()->count();

        $sent = app(WhatsAppSessionAlertService::class)->checkAndAlert($this->instance, '255658234467');

        $this->assertTrue($sent);
        $rows = $this->alertRows();
        $this->assertSame($before + 1, $rows->count());

        $row = $rows->last();
        $this->assertSame('pending', $row->status);
        $this->assertSame('urgent', $row->priority);
        $this->assertStringContainsString('NOT PAID', $row->message);
        $this->assertStringContainsString('255658234467', $row->message);
        $this->assertStringContainsString('wasenderapi.com', $row->message);
    }

    public function test_repeated_customer_messages_do_not_flood_the_owner(): void
    {
        Http::fake(['*wasenderapi.com/*' => Http::response(['success' => false, 'message' => "You didn't pay your session in time."], 402)]);
        $service = app(WhatsAppSessionAlertService::class);
        $before = $this->alertRows()->count();

        $this->assertTrue($service->checkAndAlert($this->instance, '255700000001'));
        $this->assertFalse($service->checkAndAlert($this->instance, '255700000002'));
        $this->assertFalse($service->checkAndAlert($this->instance, '255700000003'));

        $this->assertSame($before + 1, $this->alertRows()->count());
    }

    public function test_paid_session_sends_nothing(): void
    {
        Http::fake(['*wasenderapi.com/*' => Http::response(['success' => true, 'data' => ['exists' => true]], 200)]);
        $before = $this->alertRows()->count();

        $this->assertFalse(app(WhatsAppSessionAlertService::class)->checkAndAlert($this->instance, '255700000001'));
        $this->assertSame($before, $this->alertRows()->count());
    }

    public function test_a_failed_send_that_says_unpaid_alerts_without_probing(): void
    {
        Http::fake(); // any HTTP call would be recorded
        $before = $this->alertRows()->count();

        $sent = app(WhatsAppSessionAlertService::class)
            ->checkAndAlert($this->instance, '255700000001', "You didn't pay your session in time.");

        $this->assertTrue($sent);
        $this->assertSame($before + 1, $this->alertRows()->count());
        Http::assertNothingSent();
    }

    public function test_network_error_is_not_treated_as_unpaid(): void
    {
        Http::fake(['*wasenderapi.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);
        $before = $this->alertRows()->count();

        $this->assertFalse(app(WhatsAppSessionAlertService::class)->checkAndAlert($this->instance, '255700000001'));
        $this->assertSame($before, $this->alertRows()->count());
    }
}
