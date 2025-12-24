<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAiService;

class TestOpenAiErrorCase extends Command
{
    protected $signature = 'test:openai-error-case';
    protected $description = 'Test the specific OpenAI error case with UTF-8 sanitization';

    public function handle()
    {
        $this->info('🧪 Testing OpenAI Error Case Resolution');
        $this->info('=====================================');
        $this->newLine();

        // The exact message that caused the error
        $problematicMessage = "The system of fee collection and other contributions";

        $this->info('📋 Testing Problematic Message:');
        $this->line("Original: '$problematicMessage'");
        $this->line("Length: " . strlen($problematicMessage) . " bytes");
        $this->line("UTF-8 valid: " . (mb_check_encoding($problematicMessage, 'UTF-8') ? 'Yes' : 'No'));

        // Check for any hidden characters
        $hexDump = bin2hex($problematicMessage);
        $this->line("Hex dump: $hexDump");
        $this->newLine();

        // Test the OpenAI service sanitization
        $openAiService = new OpenAiService();

        // Use reflection to access the private sanitizeText method
        $reflection = new \ReflectionClass($openAiService);
        $sanitizeMethod = $reflection->getMethod('sanitizeText');
        $sanitizeMethod->setAccessible(true);

        $sanitizedMessage = $sanitizeMethod->invoke($openAiService, $problematicMessage);

        $this->info('🧽 After sanitization:');
        $this->line("Cleaned: '$sanitizedMessage'");
        $this->line("Length: " . strlen($sanitizedMessage) . " bytes");
        $this->line("UTF-8 valid: " . (mb_check_encoding($sanitizedMessage, 'UTF-8') ? 'Yes' : 'No'));
        $this->line("Changed: " . ($problematicMessage !== $sanitizedMessage ? 'Yes' : 'No'));
        $this->newLine();

        // Test with various potential encoding issues
        $testCases = [
            "The system of fee collection\x00 and other contributions", // null byte
            "The system of fee collection\x01 and other contributions", // control char
            "The system of fee collection\x1A and other contributions", // substitute char
            "The system\xEF\xBB\xBF of fee collection", // BOM
            "The system of fee collection and other contributions\x7F", // DEL character
        ];

        $this->info('🔍 Testing potential encoding issues:');
        foreach ($testCases as $i => $testCase) {
            $this->line("Test case " . ($i + 1) . ":");
            $this->line("Original: '" . addcslashes($testCase, "\0..\37") . "'");
            $this->line("Original hex: " . bin2hex($testCase));
            
            $cleaned = $sanitizeMethod->invoke($openAiService, $testCase);
            $this->line("Cleaned: '$cleaned'");
            $this->line("Cleaned hex: " . bin2hex($cleaned));
            $this->line("UTF-8 valid: " . (mb_check_encoding($cleaned, 'UTF-8') ? 'Yes' : 'No'));
            $this->newLine();
        }

        $this->info('✅ Error Case Testing Complete!');
        
        return Command::SUCCESS;
    }
}