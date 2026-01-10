<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Lead;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessAppointmentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:process-reminders {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Process and send appointment reminders via WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $this->info('🗓️ Processing appointment reminders...');
        
        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No messages will be sent');
        }
        
        $now = now();
        $remindersProcessed = 0;
        $remindersSent = 0;
        $errorsCount = 0;
        
        try {
            // Get appointments that need reminders
            $appointments = $this->getAppointmentsNeedingReminders($now);
            
            $this->info("📋 Found {$appointments->count()} appointments requiring reminders");
            
            foreach ($appointments as $appointment) {
                $remindersProcessed++;
                
                try {
                    if ($isDryRun) {
                        $this->line("📅 Would send reminder for: {$appointment->title} to {$appointment->lead->name}");
                        $remindersSent++;
                    } else {
                        $sent = $this->sendAppointmentReminder($appointment);
                        if ($sent) {
                            $remindersSent++;
                            $this->line("✅ Reminder sent: {$appointment->title} to {$appointment->lead->name}");
                        } else {
                            $errorsCount++;
                            $this->error("❌ Failed to send reminder for appointment #{$appointment->id}");
                        }
                    }
                } catch (\Exception $e) {
                    $errorsCount++;
                    $this->error("💥 Error processing appointment #{$appointment->id}: " . $e->getMessage());
                    Log::error('Appointment reminder error', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Summary
            $this->info("📊 Summary:");
            $this->info("   • Processed: {$remindersProcessed}");
            $this->info("   • Sent: {$remindersSent}");
            if ($errorsCount > 0) {
                $this->error("   • Errors: {$errorsCount}");
            }
            
        } catch (\Exception $e) {
            $this->error("🚨 Command failed: " . $e->getMessage());
            Log::error('Appointment reminder command failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get appointments that need reminders
     */
    private function getAppointmentsNeedingReminders(Carbon $now)
    {
        // Get appointments where:
        // - Status is confirmed or pending
        // - Scheduled time is within next 24 hours
        // - Reminder hasn't been sent yet
        // - Appointment is in the future
        
        return Appointment::with(['lead.businessContact'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $now->copy()->addHours(24))
            ->where(function($query) {
                $query->where('reminder_sent', false)
                      ->orWhereNull('reminder_sent');
            })
            ->orderBy('scheduled_at')
            ->get();
    }
    
    /**
     * Send appointment reminder
     */
    private function sendAppointmentReminder(Appointment $appointment): bool
    {
        try {
            $whatsappService = app(WhatsAppService::class);
            $lead = $appointment->lead;
            $businessContact = $lead->businessContact;
            
            if (!$businessContact || !$businessContact->guest_phone) {
                Log::warning('No phone number for appointment reminder', [
                    'appointment_id' => $appointment->id,
                    'lead_id' => $lead->id
                ]);
                return false;
            }
            
            // Generate reminder message
            $message = $this->generateReminderMessage($appointment);
            
            // Send WhatsApp message
            $result = $whatsappService->sendMessage(
                $businessContact->guest_phone,
                $message,
                $businessContact->business_id
            );
            
            if ($result['success']) {
                // Mark reminder as sent
                $appointment->update([
                    'reminder_sent' => true,
                    'reminder_sent_at' => now()
                ]);
                
                Log::info('Appointment reminder sent', [
                    'appointment_id' => $appointment->id,
                    'lead_id' => $lead->id,
                    'phone' => $businessContact->guest_phone
                ]);
                
                return true;
            } else {
                Log::error('Failed to send appointment reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Appointment reminder send error', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Generate reminder message text
     */
    private function generateReminderMessage(Appointment $appointment): string
    {
        $lead = $appointment->lead;
        $scheduledDate = Carbon::parse($appointment->scheduled_at);
        $businessName = $lead->businessContact->business->name ?? 'SafariChat';
        
        // Calculate time until appointment
        $timeUntil = $scheduledDate->diffForHumans(now());
        $formattedDate = $scheduledDate->format('l, M j, Y');
        $formattedTime = $scheduledDate->format('g:i A');
        
        $message = "🗓️ *Appointment Reminder*\n\n";
        $message .= "Hi {$lead->name}! 👋\n\n";
        $message .= "This is a friendly reminder about your upcoming appointment:\n\n";
        $message .= "📋 *{$appointment->title}*\n";
        $message .= "🗓️ {$formattedDate}\n";
        $message .= "⏰ {$formattedTime}\n";
        $message .= "⏳ {$timeUntil}\n\n";
        
        if ($appointment->location) {
            $message .= "📍 Location: {$appointment->location}\n\n";
        }
        
        if ($appointment->meeting_link) {
            $message .= "🔗 Meeting Link: {$appointment->meeting_link}\n\n";
        }
        
        if ($appointment->notes) {
            $message .= "📝 Notes: {$appointment->notes}\n\n";
        }
        
        $message .= "If you need to reschedule or have any questions, please let us know!\n\n";
        $message .= "See you soon! 😊\n\n";
        $message .= "Best regards,\n{$businessName}";
        
        return $message;
    }
}