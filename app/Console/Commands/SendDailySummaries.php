<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class SendDailySummaries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'summaries:send-daily
                            {--user= : Only this user id}
                            {--dry-run : Show the WhatsApp summary that would be sent, send nothing}';

    /**
     * The console command description.
     */
    protected $description = 'Send daily summaries to inactive users about missed automations';

    /**
     * Execute the console command.
     */
    public function handle(
        SubscriptionNotificationService $notificationService
    ) {
        $this->info('Sending daily summaries to business owners/admins...');
        
        $yesterday = now()->subDay();
        $dryRun = (bool) $this->option('dry-run');
        $delivered = 0;
        $viaWhatsApp = 0;
        $viaEmail = 0;
        $failed = 0;

        // Get all business owners/admins (users with businesses)
        $businessOwners = User::whereHas('business')
            ->when($this->option('user'), fn ($q, $id) => $q->where('id', (int) $id))
            ->get();

        foreach ($businessOwners as $user) {
            // Name the person by what we actually have: most owners have no email address, which is why
            // this used to print "to:  (Business)" with a blank.
            $who = trim(($user->name ?: 'user #' . $user->id) . ' <' . ($user->email ?: ($user->whatsapp_number ?: $user->phone ?: 'no contact')) . '>');
            $business = $user->business->name ?? '-';

            try {
                // Check if user has any activity to report
                $stats = $this->getDailySummaryStats($user, $yesterday);

                if (!($stats['has_activity'] || $stats['has_issues'])) {
                    $this->line("⏭️ No activity for: {$who} ({$business}) - skipping");
                    continue;
                }

                if ($dryRun) {
                    $this->info("🧪 Dry run - would send to {$who} ({$business}):");
                    $this->line($notificationService->buildDailySummaryWhatsAppMessage($user, $stats, $business, $yesterday));
                    $this->newLine();
                    continue;
                }

                // Only count what was really delivered (this used to count every user as "sent" even when
                // the service returned early because the user had no email).
                $result = $notificationService->sendDailySummary($user, $stats);

                if ($result['delivered']) {
                    $delivered++;
                    $viaWhatsApp += $result['whatsapp'] ? 1 : 0;
                    $viaEmail += $result['email'] ? 1 : 0;
                    $channels = implode(' + ', array_filter([$result['whatsapp'] ? 'WhatsApp' : null, $result['email'] ? 'email' : null]));
                    $this->info("✅ Sent daily summary to: {$who} ({$business}) via {$channels}");
                } else {
                    $failed++;
                    $this->warn("⚠️ NOT delivered to: {$who} ({$business}) - " . ($result['reason'] ?? 'no email and no WhatsApp number'));
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("❌ Failed to send summary to {$who}: {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->info('🧪 Dry run finished - nothing was sent.');
        } else {
            $this->info("📊 Daily summaries delivered to {$delivered} business owners ({$viaWhatsApp} WhatsApp, {$viaEmail} email); {$failed} not delivered");
        }

        return Command::SUCCESS;
    }
    
    /**
     * Get daily summary statistics for a user
     */
    private function getDailySummaryStats(User $user, $date): array
    {
        $businessId = $user->business->id ?? null;
        if (!$businessId) {
            return ['has_activity' => false, 'has_issues' => false];
        }
        
        // Message stats come from outgoing_messages (the legacy `messages` table this
        // used before is unused/empty, so has_activity was ALWAYS false and every
        // owner got skipped every day — i.e. no daily report was ever sent).
        $totalMessages = \App\Models\OutgoingMessage::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->count();

        $successfulMessages = \App\Models\OutgoingMessage::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->where('status', 'sent')
            ->count();

        $failedMessages = \App\Models\OutgoingMessage::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->where('status', 'failed')
            ->count();

        // Inbound replies received from customers
        $repliesReceived = \App\Models\IncomingMessage::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->count();

        // AI conversations/engagements logged for this business
        $aiConversations = \App\Models\Conversation::whereHas('lead', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->whereDate('created_at', $date)
            ->count();

        // Get handoff stats
        $newHandoffs = \App\Models\Handoff::whereHas('lead.contact', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->whereDate('created_at', $date)
            ->count();
            
        $overdueHandoffs = \App\Models\Handoff::whereHas('lead.contact', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('status', 'pending')
            ->where('sla_deadline', '<', now())
            ->count();
        
        // Get new leads (leads has business_id directly)
        $newLeads = \App\Models\Lead::where('business_id', $businessId)
            ->whereDate('created_at', $date)
            ->count();

        return [
            'has_activity' => ($totalMessages > 0 || $repliesReceived > 0 || $aiConversations > 0 || $newHandoffs > 0 || $newLeads > 0),
            'has_issues' => ($failedMessages > 5 || $overdueHandoffs > 0),
            'total_messages' => $totalMessages,
            'successful_messages' => $successfulMessages,
            'failed_messages' => $failedMessages,
            'replies_received' => $repliesReceived,
            'ai_conversations' => $aiConversations,
            'new_handoffs' => $newHandoffs,
            'overdue_handoffs' => $overdueHandoffs,
            'new_leads' => $newLeads,
            'success_rate' => $totalMessages > 0 ? round(($successfulMessages / $totalMessages) * 100, 1) : 0
        ];
    }
}
