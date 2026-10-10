<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Channel;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells the platform owner, by SMS, the moment a customer writes to a WhatsApp number whose WaSender session
 * subscription is unpaid.
 *
 * Why this exists: when the WaSender session is not paid, WaSender answers every send with HTTP 402 ("You didn't
 * pay your session in time...") while its status endpoint still says "connected" and incoming messages keep
 * arriving. The AI generated replies that were never delivered and nobody noticed for hours.
 *
 * The alert goes out as a Phone-SMS from the configured account (default user 45) to the configured phone, i.e.
 * it does not depend on WhatsApp, which is the thing that is down.
 */
class WhatsAppSessionAlertService
{
    /** Text WaSender uses when a session is blocked for non-payment. */
    private const UNPAID_PATTERN = '/didn.?t\s+pay\s+your\s+session|retry\s+your\s+failed\s+payments|payment\s+required|\b402\b/i';

    private const WASENDER_BASE_URL = 'https://www.wasenderapi.com';

    public static function isUnpaidSessionText(?string $text): bool
    {
        return $text !== null && $text !== '' && (bool) preg_match(self::UNPAID_PATTERN, $text);
    }

    private function cfg(string $key, $default = null)
    {
        return config('notifications.wasender_unpaid_alert.' . $key, $default);
    }

    /**
     * Called when a customer message arrives, and when a send has just been rejected.
     * Checks (cached) whether the instance's session is blocked for non-payment and, if so, sends the SMS
     * at most once per cooldown window per instance. Never throws: it must not break message handling.
     *
     * @param string|null $knownError  error text from a send that has just failed (skips the probe when it already
     *                                 says "unpaid")
     * @return bool true when an alert SMS was queued by this call
     */
    public function checkAndAlert(?WhatsappInstance $instance, ?string $customerPhone = null, ?string $knownError = null): bool
    {
        try {
            if (! $this->cfg('enabled', true) || ! $instance) {
                return false;
            }

            $unpaid = self::isUnpaidSessionText($knownError) || $this->sessionIsUnpaid($instance);

            if (! $unpaid) {
                return false;
            }

            $cooldown = max(1, (int) $this->cfg('cooldown_minutes', 30));
            if (! Cache::add('wasender_unpaid_alert:' . $instance->id, now()->toDateTimeString(), now()->addMinutes($cooldown))) {
                return false; // already alerted recently
            }

            $sent = $this->sendAlertSms($this->buildMessage($instance, $customerPhone));

            if (! $sent) {
                // let the next customer message try again instead of waiting out the whole cooldown
                Cache::forget('wasender_unpaid_alert:' . $instance->id);
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::error('WhatsApp unpaid-session alert failed', [
                'instance_id' => $instance?->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Ask WaSender directly (with the instance's own session key) whether the session is blocked for payment.
     * The /api/status endpoint is useless for this - it says "connected" for an unpaid session - but any
     * authenticated call answers HTTP 402. The lookup is read-only and cached for a few minutes.
     */
    public function sessionIsUnpaid(WhatsappInstance $instance): bool
    {
        $key = trim((string) $instance->api_key);
        if ($key === '') {
            return false;
        }

        return (bool) Cache::remember('wasender_unpaid_state:' . $instance->id, now()->addMinutes(max(1, (int) $this->cfg('probe_cache_minutes', 5))), function () use ($key, $instance) {
            try {
                $probeNumber = preg_replace('/\D+/', '', (string) ($instance->phone_number ?: $this->cfg('phone', '')));
                $probeNumber = $probeNumber !== '' ? $probeNumber : '255700000000';

                $response = Http::withToken($key)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->timeout(10)
                    ->get(self::WASENDER_BASE_URL . '/api/on-whatsapp/' . $probeNumber);

                return $response->status() === 402 || self::isUnpaidSessionText((string) ($response->json('message') ?? ''));
            } catch (\Throwable $e) {
                // network trouble is not evidence of non-payment
                return false;
            }
        });
    }

    private function buildMessage(WhatsappInstance $instance, ?string $customerPhone): string
    {
        $owner = User::find($instance->user_id);
        $business = $owner ? Business::where('user_id', $owner->id)->first() : null;
        $who = $business?->name ?: ($owner?->name ?: ('user ' . $instance->user_id));
        $line = $instance->phone_number ? ' (' . $instance->phone_number . ')' : '';

        return 'SAFARICHAT URGENT: WhatsApp session of ' . $who . $line . ' is NOT PAID on WaSender. '
            . 'Customers cannot get replies'
            . ($customerPhone ? ' - ' . $customerPhone . ' wrote at ' . now()->format('H:i') : '')
            . '. Renew at wasenderapi.com now.';
    }

    /**
     * Phone-SMS from the alert account, queued the same way every other Phone-SMS in the app is: a row in
     * notifications.messages carrying the account's connector code and schema (the owner's uuid).
     */
    private function sendAlertSms(string $text): bool
    {
        $userId = (int) $this->cfg('user_id', 45);
        $to = (string) $this->cfg('phone', '+255714825469');

        $owner = User::find($userId);
        if (! $owner) {
            Log::error('WhatsApp unpaid-session alert: alert account not found', ['user_id' => $userId]);
            return false;
        }

        $business = Business::where('user_id', $owner->id)->first();
        $channel = $business
            ? Channel::where('business_id', $business->id)->where('channel_key', 'phone_sms')->first()
            : null;
        $settings = ($channel && is_array($channel->settings)) ? $channel->settings : [];
        $schema = $owner->uuid ?: (string) ($business->id ?? $owner->id);

        $metadata = array_filter([
            'connector_code' => $settings['code'] ?? null,
            'source' => 'safarichat',
            'alert' => 'wasender_unpaid',
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $id = DB::connection('notification')->table('messages')->insertGetId([
                'channel' => 'phone-sms',
                'recipient' => $to,
                'message' => $text,
                'status' => 'pending',
                'priority' => 'urgent',
                'schema_name' => $schema,
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::warning('WhatsApp session unpaid: alert SMS queued', [
                'notifications_id' => $id,
                'to' => $to,
                'from_user_id' => $userId,
                'has_connector_code' => ! empty($metadata['connector_code']),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp unpaid-session alert: could not queue Phone-SMS', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }
}
