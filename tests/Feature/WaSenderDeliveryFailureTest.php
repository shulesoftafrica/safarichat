<?php

namespace Tests\Feature;

use App\Models\OutgoingMessage;
use App\Models\User;
use App\Services\WaSenderService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Production: the WaSender subscription lapsed (HTTP 402 "You didn't pay your session in time"). The notification
 * API still answered 201 / success:true with "status":"failed", and safarichat logged "AI response sent
 * successfully" and marked the customer replied - nothing was delivered and nobody noticed.
 *
 * Runs inside a rolled-back transaction, so the shared local database is not changed.
 */
class WaSenderDeliveryFailureTest extends TestCase
{
    use DatabaseTransactions;

    private function apiResponse(string $status): array
    {
        return [
            'success' => true,
            'message_id' => 656092,
            'external_id' => 'abc',
            'status' => $status,
            'provider' => 'wasender',
            'data' => ['id' => 656092, 'status' => $status, 'is_failed' => $status === 'failed', 'error_message' => null],
        ];
    }

    public function test_a_message_the_provider_rejected_is_not_reported_as_sent(): void
    {
        Http::fake(['*/notifications/send' => Http::response($this->apiResponse('failed'), 201)]);
        $user = User::query()->firstOrFail();
        $before = OutgoingMessage::where('user_id', $user->id)->where('status', 'failed')->count();

        try {
            app(WaSenderService::class)->sendMessage('255700000001', 'Hello', [], null, $user->id);
            $this->fail('a provider-rejected message must not be reported as sent');
        } catch (Exception $e) {
            $this->assertStringContainsString('provider rejected the message', $e->getMessage());
        }

        $this->assertSame($before + 1, OutgoingMessage::where('user_id', $user->id)->where('status', 'failed')->count());
    }

    public function test_an_accepted_message_is_still_reported_as_sent(): void
    {
        Http::fake(['*/notifications/send' => Http::response($this->apiResponse('sent'), 201)]);
        $user = User::query()->firstOrFail();
        $before = OutgoingMessage::where('user_id', $user->id)->where('status', 'sent')->count();

        $result = app(WaSenderService::class)->sendMessage('255700000002', 'Hello', [], null, $user->id);

        $this->assertTrue($result['success']);
        $this->assertSame($before + 1, OutgoingMessage::where('user_id', $user->id)->where('status', 'sent')->count());
    }
}
