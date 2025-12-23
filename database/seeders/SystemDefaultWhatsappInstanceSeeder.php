<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WhatsappInstance;
use App\Models\User;
use Illuminate\Support\Str;

class SystemDefaultWhatsappInstanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if system default instance already exists
        if (WhatsappInstance::where('is_system_default', true)->exists()) {
            $this->command->info('System default WhatsApp instance already exists. Skipping...');
            return;
        }

        // Get or create admin user (ID 1)
        $adminUser = User::find(1);
        if (!$adminUser) {
            $this->command->error('Admin user (ID: 1) not found. Please ensure admin user exists before running this seeder.');
            return;
        }

        // Create system default instance
        $systemInstance = WhatsappInstance::create([
            'user_id' => $adminUser->id,
            'instance_id' => 'system_default', // Unique instance identifier
            'instance_name' => 'SafariChat System', // Display name
            'phone_number' => env('SYSTEM_WHATSAPP_NUMBER', '+255700000000'),
            'display_name' => env('SYSTEM_WHATSAPP_INSTANCE_NAME', 'SafariChat System'),
            'purpose' => 'system_notifications',
            'instance_description' => 'System-level WhatsApp instance for user registration, OTP verification, payment reminders, and system notifications.',
            'is_primary' => false,
            'is_system_default' => true,
            'usage_scope' => 'system',
            'uuid' => Str::uuid(),
            'status' => 'active',
            'connect_status' => 'ready', // Use 'ready' instead of 'connected'
            'platform' => 'wasender',
            'allowed_message_types' => json_encode([
                'otp_verification',
                'welcome_message', 
                'payment_reminder',
                'system_notification',
                'account_verification',
                'password_reset'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info("System default WhatsApp instance created successfully!");
        $this->command->info("Instance ID: {$systemInstance->id}");
        $this->command->info("Phone Number: {$systemInstance->phone_number}");
        $this->command->info("Display Name: {$systemInstance->display_name}");
        $this->command->info("UUID: {$systemInstance->uuid}");
        
        $this->command->warn("Important: Configure the WhatsApp connection for this instance in your admin panel.");
        $this->command->warn("Set SYSTEM_WHATSAPP_NUMBER in your .env file to customize the phone number.");
    }
}
