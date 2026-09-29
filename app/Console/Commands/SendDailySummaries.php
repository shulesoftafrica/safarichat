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
    protected $signature = 'summaries:send-daily';

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
        $sentCount = 0;
        
        // Get all business owners/admins (users with businesses)
        $businessOwners = User::whereHas('business')
            ->get();
        
        foreach ($businessOwners as $user) {
            try {
                // Check if user has any activity to report
                $stats = $this->getDailySummaryStats($user, $yesterday);
                
                if ($stats['has_activity'] || $stats['has_issues']) {
                    $notificationService->sendDailySummary($user, $stats);
                    $sentCount++;
                    
                    $this->info("✅ Sent daily summary to: {$user->email} ({$user->business->name})");
                } else {
                    $this->line("⏭️ No activity for: {$user->email} - skipping");
                }
            } catch (\Exception $e) {
                $this->error("❌ Failed to send summary to {$user->email}: {$e->getMessage()}");
            }
        }
        
        $this->info("📊 Daily summaries sent to {$sentCount} business owners");
        
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
