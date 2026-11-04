<?php

namespace Tests\Unit;

use App\Http\Controllers\WaSenderController;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * /api/wasender/webhook/{instanceId} used to trust anything posted to it. The sender is now checked against
 * the webhook secret stored for the instance (rejecting only once WASENDER_ENFORCE_WEBHOOK_SIGNATURE is on).
 */
class WaSenderWebhookSignatureTest extends TestCase
{
    private const SECRET = 'zzz-test-secret';
    private const BODY = '{"event":"messages.received","data":{}}';

    private function waInstance(?string $secret): WhatsappInstance
    {
        $instance = new WhatsappInstance();
        $instance->forceFill(['instance_id' => '999', 'webhook_secret' => $secret]);

        return $instance;
    }

    private function webhookRequest(array $headers = []): Request
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return Request::create('/api/wasender/webhook/999', 'POST', [], [], [], $server, self::BODY);
    }

    private function signatureStatus(Request $request, WhatsappInstance $instance): string
    {
        return app(WaSenderController::class)->webhookSignatureStatus($request, $instance);
    }

    private function authentic(Request $request, WhatsappInstance $instance): bool
    {
        $method = new ReflectionMethod(WaSenderController::class, 'webhookIsAuthentic');
        $method->setAccessible(true);

        return $method->invoke(app(WaSenderController::class), $request, $instance);
    }

    public function test_status_for_each_kind_of_request(): void
    {
        $hmac = hash_hmac('sha256', self::BODY, self::SECRET);

        $this->assertSame('valid', $this->signatureStatus($this->webhookRequest(['X-Webhook-Signature' => self::SECRET]), $this->waInstance(self::SECRET)));
        $this->assertSame('valid', $this->signatureStatus($this->webhookRequest(['X-Webhook-Signature' => $hmac]), $this->waInstance(self::SECRET)));
        $this->assertSame('invalid', $this->signatureStatus($this->webhookRequest(['X-Webhook-Signature' => 'forged']), $this->waInstance(self::SECRET)));
        $this->assertSame('missing', $this->signatureStatus($this->webhookRequest(), $this->waInstance(self::SECRET)));
        $this->assertSame('no_secret', $this->signatureStatus($this->webhookRequest(), $this->waInstance(null)));
        $this->assertSame('no_secret', $this->signatureStatus($this->webhookRequest(), $this->waInstance('')));
    }

    public function test_enforcement_rejects_forged_and_unsigned_requests_but_not_valid_ones(): void
    {
        config(['services.wasender.enforce_webhook_signature' => true]);

        $this->assertTrue($this->authentic($this->webhookRequest(['X-Webhook-Signature' => self::SECRET]), $this->waInstance(self::SECRET)));
        $this->assertFalse($this->authentic($this->webhookRequest(['X-Webhook-Signature' => 'forged']), $this->waInstance(self::SECRET)));
        $this->assertFalse($this->authentic($this->webhookRequest(), $this->waInstance(self::SECRET)));
        // nothing stored for the instance: cannot be verified, so it is let through
        $this->assertTrue($this->authentic($this->webhookRequest(), $this->waInstance(null)));
    }

    public function test_without_enforcement_nothing_is_rejected(): void
    {
        config(['services.wasender.enforce_webhook_signature' => false]);

        $this->assertTrue($this->authentic($this->webhookRequest(['X-Webhook-Signature' => 'forged']), $this->waInstance(self::SECRET)));
        $this->assertTrue($this->authentic($this->webhookRequest(), $this->waInstance(self::SECRET)));
    }
}
