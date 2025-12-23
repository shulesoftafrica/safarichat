# System-Level Default WhatsApp Instance Implementation Requirements

## Overview
This document outlines the implementation requirements for creating a system-level default WhatsApp instance to handle user registration, OTP verification, and system notifications for users who haven't configured their own WhatsApp instances yet.

## Problem Statement
In a multi-instance WhatsApp system, new users face a bootstrapping problem:
- **Registration requires OTP verification via WhatsApp**
- **New users don't have any WhatsApp instances configured yet**
- **System cannot send messages without a WhatsApp instance**

**Solution**: Implement a shared system-level default WhatsApp instance owned by the system administrator.

## System Architecture Requirements

### 1. Default Instance Configuration

#### A. Database Schema Updates
```sql
-- Migration: create_system_default_whatsapp_instance.php
ALTER TABLE whatsapp_instances 
ADD COLUMN is_system_default BOOLEAN DEFAULT false,
ADD COLUMN usage_scope ENUM('user', 'system') DEFAULT 'user',
ADD COLUMN allowed_message_types JSON NULL; -- ['otp', 'welcome', 'payment_reminder', 'system_notification']

-- Create index for quick system default lookup
CREATE INDEX idx_whatsapp_instances_system_default ON whatsapp_instances(is_system_default, usage_scope);

-- Only one system default instance allowed
ALTER TABLE whatsapp_instances 
ADD CONSTRAINT unique_system_default 
UNIQUE KEY system_default_unique (is_system_default) 
WHERE is_system_default = true;
```

#### B. System Default Instance Creation
```php
// Database Seeder: database/seeders/SystemDefaultWhatsappInstanceSeeder.php
class SystemDefaultWhatsappInstanceSeeder extends Seeder
{
    public function run()
    {
        // Create system default instance owned by admin user
        WhatsappInstance::create([
            'user_id' => 1, // Admin user ID
            'schema_name' => 'system_default',
            'phone_number' => env('SYSTEM_WHATSAPP_NUMBER', '+255700000000'),
            'display_name' => 'SafariChat System',
            'purpose' => 'system_notifications',
            'description' => 'System-level WhatsApp instance for user registration, OTP verification, and system notifications',
            'is_primary' => false,
            'is_active' => true,
            'is_system_default' => true,
            'usage_scope' => 'system',
            'uuid' => Str::uuid(),
            'allowed_message_types' => json_encode([
                'otp_verification',
                'welcome_message', 
                'payment_reminder',
                'system_notification',
                'account_verification',
                'password_reset'
            ])
        ]);
    }
}
```

### 2. Model Enhancements

#### A. WhatsappInstance Model Updates
```php
// app/Models/WhatsappInstance.php
class WhatsappInstance extends Model
{
    protected $fillable = [
        // ... existing fields ...
        'is_system_default',
        'usage_scope',
        'allowed_message_types'
    ];
    
    protected $casts = [
        'allowed_message_types' => 'array'
    ];
    
    /**
     * Get the system default WhatsApp instance
     */
    public static function getSystemDefault(): ?WhatsappInstance
    {
        return static::where('is_system_default', true)
            ->where('usage_scope', 'system')
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Check if this instance can send specific message type
     */
    public function canSendMessageType(string $messageType): bool
    {
        if ($this->usage_scope === 'user') {
            return true; // User instances can send any message
        }
        
        return in_array($messageType, $this->allowed_message_types ?? []);
    }
    
    /**
     * Scope for system instances only
     */
    public function scopeSystemOnly($query)
    {
        return $query->where('usage_scope', 'system');
    }
    
    /**
     * Scope for user instances only
     */
    public function scopeUserOnly($query)
    {
        return $query->where('usage_scope', 'user');
    }
}
```

### 3. Service Layer Implementation

#### A. System WhatsApp Service
```php
// app/Services/SystemWhatsAppService.php
class SystemWhatsAppService
{
    protected WhatsappInstance $systemInstance;
    
    public function __construct()
    {
        $this->systemInstance = WhatsappInstance::getSystemDefault();
        
        if (!$this->systemInstance) {
            throw new Exception('System default WhatsApp instance not configured');
        }
    }
    
    /**
     * Send OTP verification message to new user
     */
    public function sendOtpVerification(string $phoneNumber, string $otpCode, string $userName = null): bool
    {
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
        if (!$this->systemInstance->canSendMessageType('system_notification')) {
            return false;
        }
        
        $message = $this->buildSystemNotificationMessage($title, $content);
        
        return $this->sendSystemMessage($phoneNumber, $message, 'system_notification');
    }
    
    /**
     * Core method to send system messages
     */
    private function sendSystemMessage(string $phoneNumber, string $message, string $messageType): bool
    {
        try {
            // Log system message for audit trail
            \Log::info('System WhatsApp Message Sent', [
                'phone_number' => $phoneNumber,
                'message_type' => $messageType,
                'instance_id' => $this->systemInstance->id,
                'timestamp' => now()
            ]);
            
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
                'status' => 'queued'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            \Log::error('Failed to send system WhatsApp message', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'message_type' => $messageType
            ]);
            
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
}
```

#### B. Enhanced User Registration Service
```php
// app/Services/UserRegistrationService.php
class UserRegistrationService
{
    protected SystemWhatsAppService $systemWhatsApp;
    
    public function __construct(SystemWhatsAppService $systemWhatsApp)
    {
        $this->systemWhatsApp = $systemWhatsApp;
    }
    
    /**
     * Send OTP for user registration
     */
    public function sendRegistrationOtp(string $phoneNumber, string $name = null): array
    {
        // Generate OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP in cache/database
        Cache::put("registration_otp:{$phoneNumber}", $otpCode, now()->addMinutes(10));
        
        // Send OTP via system WhatsApp instance
        $sent = $this->systemWhatsApp->sendOtpVerification($phoneNumber, $otpCode, $name);
        
        if (!$sent) {
            // Fallback to SMS if WhatsApp fails
            return $this->sendOtpViaSms($phoneNumber, $otpCode);
        }
        
        return [
            'success' => true,
            'message' => 'OTP sent via WhatsApp',
            'method' => 'whatsapp'
        ];
    }
    
    /**
     * Complete user registration after OTP verification
     */
    public function completeRegistration(array $userData, string $otpCode): User
    {
        // Verify OTP
        $storedOtp = Cache::get("registration_otp:{$userData['phone']}");
        
        if ($storedOtp !== $otpCode) {
            throw new Exception('Invalid or expired OTP code');
        }
        
        // Create user account
        $user = User::create($userData);
        
        // Send welcome message via system instance
        $this->systemWhatsApp->sendWelcomeMessage($user->phone, $user->name);
        
        // Clear OTP from cache
        Cache::forget("registration_otp:{$userData['phone']}");
        
        return $user;
    }
    
    /**
     * Fallback SMS sending (if WhatsApp fails)
     */
    private function sendOtpViaSms(string $phoneNumber, string $otpCode): array
    {
        // Implement SMS fallback using your SMS provider
        // This ensures registration always works even if WhatsApp is down
        
        return [
            'success' => true,
            'message' => 'OTP sent via SMS (WhatsApp unavailable)',
            'method' => 'sms'
        ];
    }
}
```

### 4. Database Schema Enhancements

#### A. Message Table Updates
```sql
-- Migration: add_system_message_support.php
ALTER TABLE outgoing_messages 
ADD COLUMN message_type VARCHAR(50) DEFAULT 'user_message',
ADD COLUMN is_system_message BOOLEAN DEFAULT false;

-- Add indexes for system message queries
CREATE INDEX idx_outgoing_messages_system ON outgoing_messages(is_system_message, message_type);
CREATE INDEX idx_outgoing_messages_type ON outgoing_messages(message_type);
```

#### B. System Message Audit Log
```sql
-- Migration: create_system_message_logs_table.php
CREATE TABLE system_message_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    whatsapp_instance_id BIGINT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message_type VARCHAR(50) NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('sent', 'failed', 'delivered', 'read') DEFAULT 'sent',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (whatsapp_instance_id) REFERENCES whatsapp_instances(id),
    INDEX idx_system_message_logs_phone (phone_number),
    INDEX idx_system_message_logs_type (message_type),
    INDEX idx_system_message_logs_status (status),
    INDEX idx_system_message_logs_sent_at (sent_at)
);
```

### 5. Configuration Management

#### A. Environment Configuration
```bash
# .env additions for system WhatsApp instance
SYSTEM_WHATSAPP_NUMBER="+255700000000"
SYSTEM_WHATSAPP_INSTANCE_NAME="SafariChat System"
SYSTEM_WHATSAPP_ENABLED=true

# Fallback SMS configuration (if WhatsApp fails)
SMS_FALLBACK_ENABLED=true
SMS_PROVIDER=twilio
SMS_FROM_NUMBER="+255700000001"
```

#### B. Configuration Service
```php
// app/Services/SystemConfigService.php
class SystemConfigService
{
    public static function isSystemWhatsAppEnabled(): bool
    {
        return config('system.whatsapp.enabled', env('SYSTEM_WHATSAPP_ENABLED', true));
    }
    
    public static function getSystemWhatsAppNumber(): string
    {
        return config('system.whatsapp.number', env('SYSTEM_WHATSAPP_NUMBER', '+255700000000'));
    }
    
    public static function isSmsFallbackEnabled(): bool
    {
        return config('system.sms.fallback_enabled', env('SMS_FALLBACK_ENABLED', true));
    }
}
```

### 6. Admin Panel Integration

#### A. System Instance Management
```php
// Admin controller for managing system default instance
// app/Http/Controllers/Admin/SystemWhatsAppController.php
class SystemWhatsAppController extends Controller
{
    public function showSystemInstance()
    {
        $systemInstance = WhatsappInstance::getSystemDefault();
        $messageStats = $this->getSystemMessageStats();
        
        return view('admin.system-whatsapp', compact('systemInstance', 'messageStats'));
    }
    
    public function updateSystemInstance(Request $request)
    {
        $systemInstance = WhatsappInstance::getSystemDefault();
        
        $systemInstance->update([
            'phone_number' => $request->phone_number,
            'display_name' => $request->display_name,
            'allowed_message_types' => $request->allowed_message_types
        ]);
        
        return redirect()->back()->with('success', 'System WhatsApp instance updated successfully');
    }
    
    private function getSystemMessageStats(): array
    {
        $systemInstance = WhatsappInstance::getSystemDefault();
        
        if (!$systemInstance) {
            return [];
        }
        
        return [
            'total_sent' => OutgoingMessage::where('whatsapp_instance_id', $systemInstance->id)
                ->where('is_system_message', true)
                ->count(),
            'otp_messages' => OutgoingMessage::where('whatsapp_instance_id', $systemInstance->id)
                ->where('message_type', 'otp_verification')
                ->count(),
            'welcome_messages' => OutgoingMessage::where('whatsapp_instance_id', $systemInstance->id)
                ->where('message_type', 'welcome_message')
                ->count(),
            'payment_reminders' => OutgoingMessage::where('whatsapp_instance_id', $systemInstance->id)
                ->where('message_type', 'payment_reminder')
                ->count(),
        ];
    }
}
```

### 7. Integration Points

#### A. User Registration Controller Updates
```php
// app/Http/Controllers/Auth/RegisterController.php
class RegisterController extends Controller
{
    protected UserRegistrationService $registrationService;
    
    public function __construct(UserRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }
    
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|phone:INTERNATIONAL',
            'name' => 'required|string|max:255'
        ]);
        
        $result = $this->registrationService->sendRegistrationOtp(
            $request->phone, 
            $request->name
        );
        
        return response()->json($result);
    }
    
    public function verifyAndRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|phone:INTERNATIONAL|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'otp_code' => 'required|string|size:6'
        ]);
        
        try {
            $user = $this->registrationService->completeRegistration(
                $request->only(['name', 'phone', 'email', 'password']),
                $request->otp_code
            );
            
            Auth::login($user);
            
            return redirect()->route('home')->with('success', 'Registration completed successfully!');
            
        } catch (Exception $e) {
            return back()->withErrors(['otp_code' => $e->getMessage()]);
        }
    }
}
```

#### B. Payment Service Integration
```php
// app/Services/PaymentReminderService.php
class PaymentReminderService
{
    protected SystemWhatsAppService $systemWhatsApp;
    
    public function __construct(SystemWhatsAppService $systemWhatsApp)
    {
        $this->systemWhatsApp = $systemWhatsApp;
    }
    
    public function sendPaymentReminder(User $user, array $paymentDetails): bool
    {
        return $this->systemWhatsApp->sendPaymentReminder($user, $paymentDetails);
    }
    
    public function sendPaymentConfirmation(User $user, array $paymentDetails): bool
    {
        $message = "✅ Payment Confirmed!\n\n" .
                  "Thank you {$user->name}! Your payment of {$paymentDetails['amount']} has been received.\n\n" .
                  "Your SafariChat subscription is now active.\n\n" .
                  "Transaction ID: {$paymentDetails['transaction_id']}";
                  
        return $this->systemWhatsApp->sendSystemNotification(
            $user->phone, 
            'Payment Confirmation', 
            $message
        );
    }
}
```

## Implementation Timeline

### Phase 1: Foundation Setup (Week 1)
1. **Database migrations** for system instance support
2. **Create system default instance** via seeder
3. **Update WhatsappInstance model** with system methods
4. **Environment configuration** setup

### Phase 2: Service Layer (Week 2)
1. **SystemWhatsAppService implementation**
2. **UserRegistrationService updates**
3. **Message routing enhancements**
4. **System message logging**

### Phase 3: Integration (Week 3)
1. **Registration controller updates**
2. **Payment service integration**
3. **Admin panel for system instance management**
4. **Testing and validation**

### Phase 4: Monitoring & Optimization (Week 4)
1. **System message analytics**
2. **Performance optimization**
3. **Error handling refinement**
4. **Documentation completion**

## Security Considerations

### Access Control
- System default instance only accessible by admin users
- Message type restrictions enforced at service level
- Audit logging for all system messages
- Rate limiting for OTP messages to prevent abuse

### Data Privacy
- System messages don't store user personal data unnecessarily
- OTP codes expire after 10 minutes
- Audit logs include only essential information
- GDPR compliance for system message storage

### Reliability
- Fallback to SMS if WhatsApp system instance fails
- Queue-based message sending for reliability
- Automatic retry mechanism for failed system messages
- Health checks for system instance connectivity

## Testing Requirements

### Unit Tests
- SystemWhatsAppService message building
- WhatsappInstance model system methods
- UserRegistrationService OTP verification
- Configuration service validation

### Integration Tests
- End-to-end registration flow
- OTP sending and verification
- Payment reminder workflows
- System message audit logging

### Performance Tests
- System instance message throughput
- OTP generation and validation speed
- Database query optimization
- Queue processing efficiency

## Monitoring & Alerts

### Key Metrics
- System message delivery rates
- OTP verification success rates
- Registration completion rates
- System instance uptime

### Alert Conditions
- System instance offline/disconnected
- High OTP verification failure rate
- System message queue backlog
- Database connection issues

This implementation provides a robust solution for handling new user registration and system notifications while maintaining the multi-instance architecture integrity.