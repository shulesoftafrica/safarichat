<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmartFollowupService;

class SmartFollowupCommand extends Command
{
    protected $signature = 'followup:smart {--dry-run : Run in dry-run mode}';
    protected $description = 'Send personalized followup messages to non-closed leads based on conversation history and customer language';

    private $smartFollowupService;

    public function __construct(SmartFollowupService $smartFollowupService)
    {
        parent::__construct();
        $this->smartFollowupService = $smartFollowupService;
    }

    public function handle()
    {
        $this->info('🤖 Starting Smart Followup Processing...');
        
        if ($this->option('dry-run')) {
            $this->warn('🧪 Running in DRY RUN mode - no messages will be sent');
        }

        $this->line('📋 Processing requirements:');
        $this->line('   ✓ Only leads NOT closed/lost/converted');
        $this->line('   ✓ Analyzes conversation history');
        $this->line('   ✓ Detects customer language');  
        $this->line('   ✓ Generates personalized messages');
        $this->line('');

        try {
            $this->smartFollowupService->processSmartFollowups();
            $this->info('✅ Smart followup processing completed successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Smart followup processing failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}