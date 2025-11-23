<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendWhatsAppMessage;

class TestWhatsAppMessage extends Command
{
    protected $signature = 'test:whatsapp {phone} {message}';
    protected $description = 'Send test WhatsApp message';

    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');
        
        $this->info("Sending WhatsApp message to +{$phone}...");
        
        try {
            // Use the job directly as that's what the system uses
            SendWhatsAppMessage::dispatch(
                $message,
                $phone,
                'whatsapp',
                1  // Default user ID
            );
            
            $this->info("✅ WhatsApp message job dispatched successfully!");
            $this->info("📱 Target: +{$phone}");
            $this->info("💬 Message: {$message}");
            $this->info("📝 Check the queue processing for delivery status");
            
        } catch (\Exception $e) {
            $this->error("❌ Error dispatching message: " . $e->getMessage());
            $this->error("📝 Stack trace: " . $e->getTraceAsString());
        }
    }
}