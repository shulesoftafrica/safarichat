<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAiService;
use App\Services\WebhookProcessorService;
use App\Jobs\ProcessWebhookNotification;
use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\EventsGuest;
use Illuminate\Support\Facades\Log;

class TestCompleteUtf8Flow extends Command
{
    protected $signature = 'test:complete-utf8-flow';
    protected $description = 'Test complete UTF-8 flow from webhook to OpenAI';

    public function handle()
    {
        $this->info('🧪 Testing Complete UTF-8 Message Flow');
        $this->info('=====================================');
        $this->newLine();

        // Test messages - including the problematic one
        $testMessages = [
            "The system of fee collection and other contributions",
            "Hello, I'm interested in your product",
            "Can you tell me about pricing?",
            // Potentially problematic messages
            "Message with\x00null byte",
            "Control\x01characters\x02here",
            "UTF-8: café naïve résumé",
            "Emoji test: 🚀💬✅",
        ];

        foreach ($testMessages as $index => $message) {
            $this->info("Test " . ($index + 1) . ": Processing message");
            $this->line("Original: '" . addcslashes($message, "\0..\37") . "'");
            
            try {
                // Step 1: Test WebhookProcessorService sanitization
                $webhookService = app(WebhookProcessorService::class);
                
                // Use reflection to test the sanitizeMessageText method
                $reflection = new \ReflectionClass($webhookService);
                if ($reflection->hasMethod('sanitizeMessageText')) {
                    $sanitizeMethod = $reflection->getMethod('sanitizeMessageText');
                    $sanitizeMethod->setAccessible(true);
                    $webhookCleaned = $sanitizeMethod->invoke($webhookService, $message);
                    $this->line("Webhook cleaned: '$webhookCleaned'");
                } else {
                    $webhookCleaned = $message;
                    $this->line("Webhook: No sanitization method found");
                }
                
                // Step 2: Test OpenAI service sanitization  
                $openAiService = new OpenAiService();
                $reflection = new \ReflectionClass($openAiService);
                $sanitizeMethod = $reflection->getMethod('sanitizeText');
                $sanitizeMethod->setAccessible(true);
                $openaiCleaned = $sanitizeMethod->invoke($openAiService, $webhookCleaned);
                $this->line("OpenAI cleaned: '$openaiCleaned'");
                
                // Step 3: Check UTF-8 validity at each stage
                $this->line("Original UTF-8 valid: " . (mb_check_encoding($message, 'UTF-8') ? 'Yes' : 'No'));
                $this->line("Webhook UTF-8 valid: " . (mb_check_encoding($webhookCleaned, 'UTF-8') ? 'Yes' : 'No'));
                $this->line("OpenAI UTF-8 valid: " . (mb_check_encoding($openaiCleaned, 'UTF-8') ? 'Yes' : 'No'));
                
                // Step 4: Test with real AI agent if available
                $agent = AiSalesAgent::first();
                if ($agent) {
                    $lead = Lead::first() ?? EventsGuest::first();
                    if ($lead) {
                        $this->line("Testing with real AI agent...");
                        
                        // This should not throw UTF-8 errors now
                        $response = $openAiService->generateSalesResponse(
                            $openaiCleaned,
                            $agent,
                            $lead,
                            []
                        );
                        
                        $this->line("✅ OpenAI response generated successfully");
                        $this->line("Response length: " . strlen($response['response'] ?? ''));
                    } else {
                        $this->line("⚠️ No leads found for AI testing");
                    }
                } else {
                    $this->line("⚠️ No AI agents found for testing");
                }
                
                $this->line("✅ Message processed successfully");
                
            } catch (\Exception $e) {
                $this->error("❌ Error processing message:");
                $this->error("Error: " . $e->getMessage());
                $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            }
            
            $this->newLine();
        }

        $this->info('🔍 Testing Error Scenarios:');
        
        // Test specific encoding issues
        $errorScenarios = [
            "Latin-1: " . chr(0xE9),  // é in Latin-1
            "Invalid UTF-8: \xFF\xFE",
            "Mixed encoding with BOM: \xEF\xBB\xBFHello",
        ];
        
        foreach ($errorScenarios as $index => $scenario) {
            $this->line("Error scenario " . ($index + 1) . ":");
            $this->line("Test: " . bin2hex($scenario));
            
            try {
                $openAiService = new OpenAiService();
                $reflection = new \ReflectionClass($openAiService);
                $sanitizeMethod = $reflection->getMethod('sanitizeText');
                $sanitizeMethod->setAccessible(true);
                $cleaned = $sanitizeMethod->invoke($openAiService, $scenario);
                
                $this->line("Cleaned: '$cleaned'");
                $this->line("UTF-8 valid: " . (mb_check_encoding($cleaned, 'UTF-8') ? 'Yes' : 'No'));
                $this->line("✅ Handled successfully");
                
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
            }
            
            $this->newLine();
        }

        $this->info('✅ Complete UTF-8 Flow Test Complete!');
        
        return Command::SUCCESS;
    }
}