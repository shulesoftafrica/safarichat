<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\OutgoingMessage;
use App\Models\WhatsappInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedMessagesCommand extends Command
{
    protected $signature = 'messages:retry-failed
                            {--reason= : Filter by failure_reason (instance_disconnected,rate_limited,bug,unknown,instance_expired)}
                            {--user=   : Retry only for a specific user_id}
                            {--limit=50 : Max messages to re-queue per run}
                            {--dry-run  : Show what would be retried without dispatching any jobs}';

    protected $description = 'Re-queue failed outgoing messages that are marked as retryable';

    public function handle(): int
    {
        $reason = $this->option('reason');
        $userId = $this->option('user');
        $limit  = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $query = OutgoingMessage::where('status', 'failed')
            ->where('retryable', true)
            // Respect the per-record cap (default 5)
            ->whereColumn('retry_count', '<', 'max_retries')
            // 10-minute cooldown between retries — prevents hammering a still-broken service
            ->where(function ($q) {
                $q->whereNull('last_retry_at')
                  ->orWhere('last_retry_at', '<', now()->subMinutes(10));
            })
            // Only retry messages from the last 48 hours — older ones are stale
            ->where('created_at', '>=', now()->subHours(48))
            ->with(['whatsappInstance'])
            ->orderBy('created_at')
            ->limit($limit);

        if ($reason) {
            $query->where('failure_reason', $reason);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $messages = $query->get();

        if ($messages->isEmpty()) {
            $this->info('No retryable failed messages found.');
            Log::info('messages:retry-failed: nothing to retry', [
                'reason_filter' => $reason ?? 'all',
                'user_filter'   => $userId ?? 'all',
            ]);
            return 0;
        }

        $this->info("Found {$messages->count()} retryable failed message(s).");

        $retried = 0;
        $skipped = 0;

        foreach ($messages as $message) {
            // Invalid numbers are permanent — mark non-retryable and skip
            if ($message->failure_reason === 'invalid_number') {
                if (!$dryRun) {
                    $message->update(['retryable' => false]);
                }
                $skipped++;
                continue;
            }

            // For disconnected-instance failures, check the instance is back online
            // before wasting a retry attempt
            if ($message->failure_reason === 'instance_disconnected') {
                $instance = $message->whatsappInstance
                    ?? ($message->user_id
                        ? WhatsappInstance::where('user_id', $message->user_id)
                            ->where('is_primary', true)
                            ->first()
                            ?? WhatsappInstance::where('user_id', $message->user_id)->first()
                        : null);

                if (!$instance || $instance->status !== 'connected') {
                    $skipped++;
                    continue;
                }
            }

            if ($dryRun) {
                $this->line("  [DRY RUN] #{$message->id} | {$message->phone_number} | reason: {$message->failure_reason} | retries: {$message->retry_count}/{$message->max_retries}");
                $retried++;
                continue;
            }

            // Dispatch a fresh job pointing back at the same OutgoingMessage record
            // so status updates overwrite correctly
            SendWhatsAppMessage::dispatch(
                $message->message_body ?? $message->message,
                $message->phone_number,
                $message->source ?? 'whatsapp',
                $message->user_id,
                null, // files — not stored on the record, media retries not supported
                $message->instance_id,
                [
                    'whatsapp_instance_id' => $message->whatsapp_instance_id,
                    'provider'             => $message->provider ?? 'unified_api',
                    'priority'             => $message->priority ?? 'normal',
                    'batch_id'             => $message->batch_id,
                    'outgoing_message_id'  => $message->id,   // ← ties back to this record
                    'message_type'         => $message->message_type,
                ]
            );

            // Reset status to pending + stamp last_retry_at so the cooldown applies
            $message->update([
                'status'        => 'pending',
                'last_retry_at' => now(),
                'retry_count'   => ($message->retry_count ?? 0) + 1,
                // Keep failure_reason so we can track how many retries this reason needed
            ]);

            Log::info('messages:retry-failed: re-dispatched', [
                'outgoing_message_id' => $message->id,
                'phone'               => $message->phone_number,
                'failure_reason'      => $message->failure_reason,
                'retry_count'         => $message->retry_count,
                'user_id'             => $message->user_id,
            ]);

            $this->line("  ✓ Retried #{$message->id} — {$message->phone_number} ({$message->failure_reason})");
            $retried++;
        }

        $this->info("Done — Retried: {$retried} | Skipped: {$skipped}");

        Log::info('messages:retry-failed completed', [
            'retried'       => $retried,
            'skipped'       => $skipped,
            'reason_filter' => $reason ?? 'all',
            'user_filter'   => $userId ?? 'all',
        ]);

        return 0;
    }
}
