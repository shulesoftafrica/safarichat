<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a business's own reports (daily summary / daily report) to its manager.
 *
 * Reports are sent from the business's OWN connected WhatsApp number to the manager number configured on its AI
 * agent (falling back to the owner's own number). They are deliberately NOT sent through the shared platform
 * number: one number cannot carry every business's reports (WhatsApp throttling/bans, the notification API's
 * 2-requests-per-second cap, and a single dead session silences everyone - which is exactly what happened).
 * The platform number is kept for low-volume alerts only (e.g. "your WhatsApp is disconnected").
 *
 * When the business's own line cannot be used the result says why, so the caller can use a backup channel
 * (email) and the owner can be alerted about the cause.
 */
class OwnerReportDeliveryService
{
    public const REASON_NO_RECIPIENT = 'no_recipient';
    public const REASON_NO_INSTANCE = 'no_connected_whatsapp';
    public const REASON_SAME_NUMBER = 'recipient_is_the_connected_number';
    public const REASON_SEND_FAILED = 'send_failed';

    public function __construct(private readonly WaSenderService $waSender)
    {
    }

    /**
     * The manager's number: the AI agent's fallback number, else the owner's WhatsApp number, else their phone.
     */
    public function resolveRecipient(User $owner): ?string
    {
        $candidates = [$this->agentFallbackNumber($owner), $owner->whatsapp_number, $owner->phone];

        foreach ($candidates as $candidate) {
            $normalized = self::normalizeNumber((string) $candidate, (string) ($owner->country_code ?? ''));
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * International "+<digits>" form, or null when the value is not a usable number.
     *
     * Local numbers (0788 123 456 or 788123456) get the owner's country code, defaulting to Tanzania (255).
     */
    public static function normalizeNumber(string $raw, string $countryCode = ''): ?string
    {
        $raw = trim($raw);
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }

        $cc = preg_replace('/\D+/', '', $countryCode) ?: '255';

        if (str_starts_with($raw, '+')) {
            $full = $digits;
        } elseif (str_starts_with($digits, '00')) {
            $full = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $full = $cc . substr($digits, 1);
        } elseif (strlen($digits) <= 9) {
            $full = $cc . $digits;
        } else {
            $full = $digits;
        }

        return strlen($full) >= 10 && strlen($full) <= 15 ? '+' . $full : null;
    }

    /** The manager number set on the business's AI agent ("fallback number"), if any. */
    protected function agentFallbackNumber(User $owner): ?string
    {
        try {
            $agent = $owner->aiSalesAgents()
                ->whereNotNull('fallback_number')
                ->where('fallback_number', '!=', '')
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            return $agent?->fallback_number;
        } catch (\Throwable $e) {
            // an unexpected schema difference must never stop the report; the owner's own number is used instead
            Log::debug('OwnerReportDelivery: could not read the agent fallback number', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * The owner's own connected WhatsApp line (never the platform's system line).
     */
    public function ownInstance(User $owner): ?WhatsappInstance
    {
        return WhatsappInstance::where('user_id', $owner->id)
            ->where('is_system_default', false)
            ->where('status', 'connected')
            ->orderByDesc('is_primary')
            ->orderByDesc('connected_at')
            ->first();
    }

    public static function sameNumber(?string $a, ?string $b): bool
    {
        $a = substr(preg_replace('/\D+/', '', (string) $a), -9);
        $b = substr(preg_replace('/\D+/', '', (string) $b), -9);

        return $a !== '' && $a === $b;
    }

    /**
     * Send $message to the manager from the owner's own WhatsApp number.
     *
     * @return array{sent:bool, reason:?string, recipient:?string, instance_id:?int, error?:string}
     */
    public function deliverWhatsApp(User $owner, string $message): array
    {
        $recipient = $this->resolveRecipient($owner);
        if (! $recipient) {
            return ['sent' => false, 'reason' => self::REASON_NO_RECIPIENT, 'recipient' => null, 'instance_id' => null];
        }

        $instance = $this->ownInstance($owner);
        if (! $instance) {
            return ['sent' => false, 'reason' => self::REASON_NO_INSTANCE, 'recipient' => $recipient, 'instance_id' => null];
        }

        // WhatsApp cannot message the number it is logged in as.
        if (self::sameNumber($recipient, $instance->phone_number)) {
            return ['sent' => false, 'reason' => self::REASON_SAME_NUMBER, 'recipient' => $recipient, 'instance_id' => $instance->id];
        }

        try {
            $this->waSender->sendMessage($recipient, $message, ['log_message_type' => 'owner_report'], $instance, $owner->id);

            return ['sent' => true, 'reason' => null, 'recipient' => $recipient, 'instance_id' => $instance->id];
        } catch (\Throwable $e) {
            Log::warning('OwnerReportDelivery: own WhatsApp line could not send the report', [
                'user_id' => $owner->id,
                'instance_id' => $instance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'reason' => self::REASON_SEND_FAILED,
                'recipient' => $recipient,
                'instance_id' => $instance->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Backup channel for a report text: a plain email to the owner. Returns true if handed to the mailer.
     */
    public function deliverEmail(User $owner, string $subject, string $message): bool
    {
        if (empty($owner->email)) {
            return false;
        }

        try {
            Mail::raw(strip_tags(str_replace(['*', '_'], '', $message)), function ($mail) use ($owner, $subject) {
                $mail->to($owner->email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('OwnerReportDelivery: email fallback failed', ['user_id' => $owner->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
