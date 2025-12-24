<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAiService;
use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\User;

class TestUtf8Sanitization extends Command
{
    protected $signature = 'test:utf8-sanitization';
    protected $description = 'Test UTF-8 message sanitization to prevent OpenAI encoding errors';

    public function handle()
    {
        $this->info('🧪 Testing UTF-8 Message Sanitization');
        $this->info('=====================================');
        $this->newLine();
        
        // Test problematic messages that caused the original error
        $testMessages = [
            'Normal message',
            'The system of fee collection and other contributions',
            "Message with null bytes\0and control chars\x00\x01\x02",
            'Mixed encoding: café naïve résumé',
            "Control chars: \x08\x0B\x0C\x0E\x1F\x7F",
            'Emoji test: 🚀 💬 ✅ ❌',
            'Non-UTF8 bytes: ' . chr(128) . chr(129) . chr(130),
            '',
            '   ',
        ];
        
        $this->info('📋 Testing Message Sanitization:');
        
        foreach ($testMessages as $index => $message) {
            $this->testMessageSanitization($index + 1, $message);
        }
        
        $this->newLine();
        $this->info('🤖 Testing OpenAI Integration:');
        
        $this->testOpenAiIntegration();
        
        $this->newLine();
        $this->info('✅ UTF-8 Sanitization Test Complete!');
    }
    
    private function testMessageSanitization(int $testNum, string $message)
    {
        $this->line("Test {$testNum}: " . ($message ?: '[empty]'));
        
        // Display original message info
        $originalLength = strlen($message);
        $originalValid = mb_check_encoding($message, 'UTF-8');
        $this->line("  📊 Original: {$originalLength} bytes, UTF-8 valid: " . ($originalValid ? 'Yes' : 'No'));
        
        // Test the sanitization using reflection to access private method
        $openAiService = new OpenAiService();
        $reflection = new \ReflectionClass($openAiService);
        $sanitizeMethod = $reflection->getMethod('sanitizeText');
        $sanitizeMethod->setAccessible(true);
        
        $sanitized = $sanitizeMethod->invoke($openAiService, $message);
        
        // Display sanitized message info
        $cleanLength = strlen($sanitized);
        $cleanValid = mb_check_encoding($sanitized, 'UTF-8');
        $this->line("  ✨ Cleaned: {$cleanLength} bytes, UTF-8 valid: " . ($cleanValid ? 'Yes' : 'No'));
        
        if ($sanitized !== $message) {
            $this->line("  📝 Result: '" . substr($sanitized, 0, 50) . ($cleanLength > 50 ? '...' : '') . "'");
        } else {
            $this->line("  📝 Result: [unchanged]");
        }
        
        $this->newLine();
    }
    
    private function testOpenAiIntegration()
    {
        // Get first user and agent for testing
        $user = User::first();
        if (!$user) {
            $this->warn('⚠️  No users found, skipping OpenAI integration test');
            return;
        }
        
        $agent = AiSalesAgent::where('user_id', $user->id)->where('status', 'active')->first();
        if (!$agent) {
            $this->warn('⚠️  No active AI sales agent found, skipping OpenAI integration test');
            return;
        }
        
        $lead = Lead::where('ai_sales_agent_id', $agent->id)->first();
        if (!$lead) {
            $this->warn('⚠️  No leads found for AI agent, skipping OpenAI integration test');
            return;
        }
        
        // Test with the original problematic message
        $problematicMessage = "The system of fee collection and other contributions\0\x08\x1F";
        
        $this->line('📤 Testing OpenAI with sanitized message...');
        
        try {
            $openAiService = new OpenAiService();
            
            // This should now work without UTF-8 errors
            $result = $openAiService->generateSalesResponse(
                $problematicMessage,
                $agent,
                $lead
            );
            
            if ($result['success']) {
                $this->line('✅ OpenAI processing: Success');
                $this->line('📝 Response length: ' . strlen($result['response']));
                if (isset($result['tokens_used'])) {
                    $this->line('🔢 Tokens used: ' . $result['tokens_used']);
                }
            } else {
                $this->line('❌ OpenAI processing failed: ' . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception during OpenAI test: ' . $e->getMessage());
        }
    }
}