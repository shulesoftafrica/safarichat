<?php

namespace App\Services;

use App\Models\WhatsappInstance;
use App\Models\OutgoingMessage;
use App\Models\SystemMessageLog;
use App\Models\User;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class SystemWhatsAppService
{
    protected ?WhatsappInstance $systemInstance;
    
    public function __construct()
    {
        $this->systemInstance = WhatsappInstance::getSystemDefault();
        
        if (!$this->systemInstance) {
            Log::warning('System default WhatsApp instance not configured');
        }
    }
    
    /**
     * Send OTP verification message to new user
     */
    public function sendOtpVerification(string $phoneNumber, string $otpCode, string $userName = null): bool
    {
        if (!$this->systemInstance) {
            throw new Exception('System default WhatsApp instance not configured');
        }

        if (!$this->systemInstance->canSendMessageType('otp_verification')) {
            throw new Exception('System instance cannot send OTP messages');
        }
        
        $message = $this->buildOtpMessage($otpCode, $userName);
        
        return $this->sendSystemMessage($phoneNumber, $message, 'otp_verification');
    }
    
    /**
     * Send welcome message after successful registration
     */
    public function sendWelcomeMessage(string $phoneNumber, string $userName): bool
    {
        if (!$this->systemInstance) {
            return false;
        }

        if (!$this->systemInstance->canSendMessageType('welcome_message')) {
            return false;
        }
        
        $message = $this->buildWelcomeMessage($userName);
        
        return $this->sendSystemMessage($phoneNumber, $message, 'welcome_message');
    }
    
    /**
     * Send payment reminder to user
     */
    public function sendPaymentReminder(User $user, array $paymentDetails): bool
    {
        if (!$this->systemInstance) {
            return false;
        }

        if (!$this->systemInstance->canSendMessageType('payment_reminder')) {
            return false;
        }
        
        $message = $this->buildPaymentReminderMessage($user, $paymentDetails);
        
        return $this->sendSystemMessage($user->phone, $message, 'payment_reminder');
    }
    
    /**
     * Send system notification to user
     */
    public function sendSystemNotification(string $phoneNumber, string $title, string $content): bool
    {
        if (!$this->systemInstance) {
            return false;
        }

        if (!$this->systemInstance->canSendMessageType('system_notification')) {
            return false;
        }
        
        $message = $this->buildSystemNotificationMessage($title, $content);
        
        return $this->sendSystemMessage($phoneNumber, $message, 'system_notification');
    }

    /**
     * Send password reset message
     */
    public function sendPasswordResetMessage(string $phoneNumber, string $otpCode, string $userName = null): bool
    {
        if (!$this->systemInstance) {
            return false;
        }

        if (!$this->systemInstance->canSendMessageType('otp')) {
            return false;
        }

        $message = $this->buildPasswordResetMessage($otpCode, $userName);

        return $this->sendSystemMessage($phoneNumber, $message, 'otp', [
            'otp_code' => $otpCode,
            'purpose' => 'password_reset'
        ]);
    }

    /**
     * Send account verification message
     */
    public function sendAccountVerification(string $phoneNumber, string $verificationLink, string $userName = null): bool
    {
        if (!$this->systemInstance) {
            return false;
        }

        if (!$this->systemInstance->canSendMessageType('account_verification')) {
            return false;
        }

        $message = $this->buildAccountVerificationMessage($verificationLink, $userName);

        return $this->sendSystemMessage($phoneNumber, $message, 'account_verification');
    }
    
    /**
     * Core method to send system messages
     */
    private function sendSystemMessage(string $phoneNumber, string $message, string $messageType): bool
    {
        try {
            // Log system message for audit trail
            Log::info('System WhatsApp Message Sent', [
                'phone_number' => $phoneNumber,
                'message_type' => $messageType,
                'instance_id' => $this->systemInstance->id,
                'timestamp' => now()
            ]);

            // Create system message log entry
            $systemLog = SystemMessageLog::logMessage(
                $this->systemInstance->id,
                $phoneNumber,
                $messageType,
                $message,
                'queued'
            );
            
            // Queue message using system instance
            SendWhatsAppMessage::dispatch(
                $phoneNumber,
                $message,
                null, // No user_id for system messages
                $this->systemInstance->id,
                $messageType
            );
            
            // Record outgoing message
            OutgoingMessage::create([
                'phone_number' => $phoneNumber,
                'message' => $message,
                'user_id' => $this->systemInstance->user_id,
                'whatsapp_instance_id' => $this->systemInstance->id,
                'message_type' => $messageType,
                'is_system_message' => true,
                'status' => 'queued',
                'created_at' => now()
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send system WhatsApp message', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'message_type' => $messageType,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update system log with error
            if (isset($systemLog)) {
                $systemLog->updateStatus('failed', $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Build OTP verification message
     */
    private function buildOtpMessage(string $otpCode, ?string $userName): string
    {
        $greeting = $userName ? "Hello {$userName}!" : "Hello!";
        
        return "{$greeting}\n\n" .
               "Your SafariChat verification code is: *{$otpCode}*\n\n" .
               "Enter this code to complete your registration.\n" .
               "This code expires in 10 minutes.\n\n" .
               "If you didn't request this code, please ignore this message.\n\n" .
               "Welcome to SafariChat AI Sales System! 🚀";
    }
    
    /**
     * Build welcome message after registration
     */
    private function buildWelcomeMessage(string $userName): string
    {
        return "🎉 Welcome to SafariChat, {$userName}!\n\n" .
               "Your account has been successfully created. You can now:\n\n" .
               "✅ Set up your WhatsApp business lines\n" .
               "✅ Configure your AI Sales Agent\n" .
               "✅ Add your products and services\n" .
               "✅ Start converting leads automatically\n\n" .
               "Get started at: " . url('/') . "\n\n" .
               "Need help? Contact our support team anytime.";
    }
    
    /**
     * Build payment reminder message
     */
    private function buildPaymentReminderMessage(User $user, array $paymentDetails): string
    {
        return "Hello {$user->name},\n\n" .
               "This is a friendly reminder about your SafariChat subscription:\n\n" .
               "💳 Amount Due: {$paymentDetails['amount']}\n" .
               "📅 Due Date: {$paymentDetails['due_date']}\n" .
               "📋 Plan: {$paymentDetails['plan_name']}\n\n" .
               "Please complete your payment to continue enjoying uninterrupted service.\n\n" .
               "Pay now: {$paymentDetails['payment_link']}\n\n" .
               "Questions? Reply to this message for support.";
    }
    
    /**
     * Build system notification message
     */
    private function buildSystemNotificationMessage(string $title, string $content): string
    {
        return "🔔 *{$title}*\n\n{$content}\n\n" .
               "This is an automated message from SafariChat System.";
    }

    /**
     * Build password reset message
     */
    private function buildPasswordResetMessage(string $otpCode, ?string $userName): string
    {
        $greeting = $userName ? "Hello {$userName}!" : "Hello!";
        
        return "{$greeting}\n\n" .
               "🔐 Your SafariChat password reset verification code:\n\n" .
               "*{$otpCode}*\n\n" .
               "This code expires in 10 minutes.\n\n" .
               "Enter this code to reset your password.\n\n" .
               "If you didn't request this reset, please ignore this message.\n\n" .
               "SafariChat Security Team";
    }

    /**
     * Build account verification message
     */
    private function buildAccountVerificationMessage(string $verificationLink, ?string $userName): string
    {
        $greeting = $userName ? "Hello {$userName}!" : "Hello!";
        
        return "{$greeting}\n\n" .
               "Please verify your SafariChat account by clicking the link below:\n\n" .
               "{$verificationLink}\n\n" .
               "This verification link expires in 24 hours.\n\n" .
               "Once verified, you can access all SafariChat features.\n\n" .
               "Welcome aboard! 🚀";
    }
    
    /**
     * Check if system WhatsApp is available
     */
    public function isAvailable(): bool
    {
        return $this->systemInstance !== null && 
               $this->systemInstance->status === 'active';
    }
    
    /**
     * Get system instance statistics
     */
    public function getSystemStats($days = 30): array
    {
        if (!$this->systemInstance) {
            return [];
        }

        $stats = SystemMessageLog::where('whatsapp_instance_id', $this->systemInstance->id)
            ->where('sent_at', '>=', now()->subDays($days))
            ->selectRaw('
                message_type,
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN (\'sent\', \'delivered\', \'read\') THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('message_type')
            ->get()
            ->keyBy('message_type');

        return [
            'instance_id' => $this->systemInstance->id,
            'instance_name' => $this->systemInstance->display_name,
            'phone_number' => $this->systemInstance->phone_number,
            'is_active' => $this->isAvailable(),
            'stats_period_days' => $days,
            'message_types' => $stats->toArray(),
            'total_messages' => $stats->sum('total_sent'),
            'successful_messages' => $stats->sum('successful'),
            'failed_messages' => $stats->sum('failed')
        ];
    }
}