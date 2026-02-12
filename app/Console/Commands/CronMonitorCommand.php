<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CronMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:monitor 
                            {--action=status : Action to perform (status, health, logs)}
                            {--clear-logs : Clear old log files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and manage cron jobs with detailed logging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->logCronActivity('Cron monitor started', 'info');

        $action = $this->option('action');

        switch ($action) {
            case 'status':
                $this->showCronStatus();
                break;
            case 'health':
                $this->checkCronHealth();
                break;
            case 'logs':
                $this->showRecentLogs();
                break;
            default:
                $this->error('Invalid action. Use: status, health, or logs');
                return 1;
        }

        if ($this->option('clear-logs')) {
            $this->clearOldLogs();
        }

        $this->logCronActivity('Cron monitor completed', 'info');
        return 0;
    }

    /**
     * Show current cron job status
     */
    protected function showCronStatus()
    {
        $this->info('=== Cron Job Status ===');
        $this->logCronActivity('Checking cron status', 'info');

        $cronJobs = [
            'inspire' => ['frequency' => 'hourly', 'last_run' => $this->getLastLogEntry('laravel.log', 'inspire')],
            'message_processing' => ['frequency' => 'every minute', 'last_run' => $this->getLastLogEntry('laravel.log', 'process')],
            'ai_failed_messages' => ['frequency' => 'every 5 minutes', 'last_run' => $this->getLastLogEntry('ai-failed-messages.log')],
            'ai_health_check' => ['frequency' => 'hourly', 'last_run' => $this->getLastLogEntry('ai-health-check.log')],
            'daily_outreach' => ['frequency' => 'twice daily', 'last_run' => $this->getLastLogEntry('daily-outreach.log')],
            'conversation_engine' => ['frequency' => 'every 5 minutes', 'last_run' => $this->getLastLogEntry('conversation-engine.log')],
            'sla_monitor' => ['frequency' => 'every 15 minutes (business hours)', 'last_run' => $this->getLastLogEntry('sla-monitor.log')],
        ];

        $table = [];
        foreach ($cronJobs as $job => $details) {
            $status = $this->determineCronStatus($job, $details);
            $table[] = [
                $job,
                $details['frequency'],
                $details['last_run'] ?: 'Never',
                $status
            ];
        }

        $this->table(['Job', 'Frequency', 'Last Run', 'Status'], $table);
        
        // Log the status check
        $this->logCronActivity('Cron status check completed', 'info', ['total_jobs' => count($cronJobs)]);
    }

    /**
     * Check cron health and identify issues
     */
    protected function checkCronHealth()
    {
        $this->info('=== Cron Health Check ===');
        $this->logCronActivity('Starting cron health check', 'info');

        $issues = [];
        
        // Check if Laravel scheduler is running
        if (!$this->isSchedulerRunning()) {
            $issues[] = 'Laravel scheduler may not be running (no recent activity)';
            $this->logCronActivity('Laravel scheduler not running', 'warning');
        }

        // Check log file permissions
        $logPath = storage_path('logs');
        if (!is_writable($logPath)) {
            $issues[] = 'Log directory is not writable: ' . $logPath;
            $this->logCronActivity('Log directory not writable', 'error', ['path' => $logPath]);
        }

        // Check for recent failures
        $recentFailures = $this->getRecentFailures();
        if (!empty($recentFailures)) {
            $issues[] = 'Recent failures detected in logs';
            $this->logCronActivity('Recent failures detected', 'warning', ['failures' => count($recentFailures)]);
        }

        // Check disk space for logs
        $logSize = $this->getLogDirectorySize();
        $freeSpace = disk_free_space(storage_path());
        if ($freeSpace < (100 * 1024 * 1024)) { // Less than 100MB free
            $issues[] = 'Low disk space may affect logging';
            $this->logCronActivity('Low disk space warning', 'warning', ['free_space' => $freeSpace]);
        }

        if (empty($issues)) {
            $this->info('✅ All cron jobs appear healthy');
            $this->logCronActivity('Health check passed', 'info');
        } else {
            $this->warn('⚠️  Issues detected:');
            foreach ($issues as $issue) {
                $this->line("   - $issue");
            }
            $this->logCronActivity('Health check found issues', 'warning', ['issues' => $issues]);
        }

        // Show resource usage
        $this->info("\n📊 Resource Usage:");
        $this->line("Log directory size: " . $this->formatBytes($logSize));
        $this->line("Available disk space: " . $this->formatBytes($freeSpace));
        
        $this->logCronActivity('Health check completed', 'info');
    }

    /**
     * Show recent log entries
     */
    protected function showRecentLogs()
    {
        $this->info('=== Recent Cron Activity ===');
        
        $cronLogFile = storage_path('logs/cron-monitor.log');
        if (!file_exists($cronLogFile)) {
            $this->warn('No cron monitor logs found');
            return;
        }

        $lines = $this->getLastNLines($cronLogFile, 50);
        foreach ($lines as $line) {
            $this->line($line);
        }
    }

    /**
     * Clear old log files
     */
    protected function clearOldLogs()
    {
        $this->info('🗑️  Clearing old log files...');
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $deletedCount = 0;
        $cutoffDate = Carbon::now()->subDays(30);

        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoffDate)) {
                if (str_contains($file->getFilename(), '.log') && 
                    !str_contains($file->getFilename(), 'laravel.log')) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            }
        }

        $this->info("Deleted $deletedCount old log files");
        $this->logCronActivity('Old logs cleared', 'info', ['deleted_count' => $deletedCount]);
    }

    /**
     * Log cron activity to dedicated log file
     */
    protected function logCronActivity($message, $level = 'info', $context = [])
    {
        $logFile = storage_path('logs/cron-monitor.log');
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        
        $logEntry = "[$timestamp] $level: $message$contextStr" . PHP_EOL;
        
        File::append($logFile, $logEntry);
        
        // Also log to Laravel's default logger with cron prefix
        Log::channel('daily')->$level("[CRON] $message", $context);
    }

    /**
     * Get last log entry for a specific log file
     */
    protected function getLastLogEntry($logFile, $search = null)
    {
        $logPath = storage_path('logs/' . $logFile);
        
        if (!file_exists($logPath)) {
            return null;
        }

        $lines = $this->getLastNLines($logPath, 100);
        
        foreach (array_reverse($lines) as $line) {
            if ($search) {
                if (stripos($line, $search) !== false && preg_match('/\[([\d\-\s:]+)\]/', $line, $matches)) {
                    return $matches[1];
                }
            } else {
                if (preg_match('/\[([\d\-\s:]+)\]/', $line, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * Determine cron job status based on last run time
     */
    protected function determineCronStatus($job, $details)
    {
        if (!$details['last_run']) {
            return '❌ Never Run';
        }

        $lastRun = Carbon::parse($details['last_run']);
        $now = Carbon::now();
        
        // Define expected intervals
        $intervals = [
            'inspire' => 3600, // 1 hour
            'message_processing' => 120, // 2 minutes (allowing for delays)
            'ai_failed_messages' => 360, // 6 minutes 
            'ai_health_check' => 3900, // 65 minutes
            'daily_outreach' => 86400, // 24 hours (daily)
            'conversation_engine' => 360, // 6 minutes
            'sla_monitor' => 1800, // 30 minutes
        ];

        $expectedInterval = $intervals[$job] ?? 3600; // Default to 1 hour
        $timeSinceLastRun = $now->diffInSeconds($lastRun);

        if ($timeSinceLastRun > ($expectedInterval * 2)) {
            return '❌ Overdue';
        } elseif ($timeSinceLastRun > $expectedInterval) {
            return '⚠️ Late';
        } else {
            return '✅ OK';
        }
    }

    /**
     * Check if Laravel scheduler is running by looking for recent activity
     */
    protected function isSchedulerRunning()
    {
        $logFiles = [
            'laravel.log',
            'ai-failed-messages.log',
            'conversation-engine.log'
        ];

        foreach ($logFiles as $logFile) {
            $lastEntry = $this->getLastLogEntry($logFile);
            if ($lastEntry) {
                $lastRun = Carbon::parse($lastEntry);
                if ($lastRun->gt(Carbon::now()->subMinutes(10))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get recent failures from log files
     */
    protected function getRecentFailures()
    {
        $failures = [];
        $logFiles = File::files(storage_path('logs'));
        $cutoffTime = Carbon::now()->subHours(24);

        foreach ($logFiles as $file) {
            if (str_contains($file->getFilename(), '.log')) {
                $lines = $this->getLastNLines($file->getPathname(), 100);
                foreach ($lines as $line) {
                    if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
                        // Match datetime pattern only: [YYYY-MM-DD HH:MM:SS]
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                            try {
                                $logTime = Carbon::parse($matches[1]);
                                if ($logTime->gt($cutoffTime)) {
                                    $failures[] = [
                                        'file' => $file->getFilename(),
                                        'time' => $matches[1],
                                        'message' => substr($line, 0, 100)
                                    ];
                                }
                            } catch (\Exception $e) {
                                // Skip lines with invalid timestamps
                                continue;
                            }
                        }
                    }
                }
            }
        }

        return $failures;
    }

    /**
     * Get log directory size
     */
    protected function getLogDirectorySize()
    {
        $size = 0;
        $files = File::allFiles(storage_path('logs'));
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Get last N lines from a file efficiently
     */
    protected function getLastNLines($file, $n = 10)
    {
        if (!file_exists($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($lines, -$n);
    }

    /**
     * Format bytes into human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
