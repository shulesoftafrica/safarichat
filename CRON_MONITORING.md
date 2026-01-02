# SafariChat Cron Job Monitoring System

## Overview
This document explains the comprehensive cron job monitoring system implemented for SafariChat's automated tasks.

## Components

### 1. Enhanced Laravel Scheduler (`app/Console/Kernel.php`)
- **Comprehensive Logging**: All scheduled tasks now include success/failure logging
- **Error Tracking**: Failed tasks are logged with detailed error messages
- **Health Monitoring**: Built-in system health checks every 10 minutes
- **Resource Monitoring**: Automatic log cleanup and disk space monitoring

### 2. Cron Monitor Command (`app/Console/Commands/CronMonitorCommand.php`)
- **Status Checking**: View all cron jobs and their last run times
- **Health Analysis**: Detect issues with scheduler, permissions, and failures
- **Log Management**: View recent activity and clean old logs
- **Resource Tracking**: Monitor log sizes and disk space

### 3. Monitoring Scripts
- **Linux/Mac**: `scripts/cron-monitor.sh`
- **Windows**: `scripts/cron-monitor.bat`

## Scheduled Tasks

| Task | Frequency | Purpose | Log File |
|------|-----------|---------|----------|
| `inspire` | Hourly | Laravel test command | `laravel.log` |
| `message-processing` | Every minute | Process WhatsApp messages | `laravel.log` |
| `ai:process-failed-messages` | Every 5 minutes | Retry failed AI messages | `ai-failed-messages.log` |
| `ai:manage-agents --agent-health-check` | Hourly | Check AI agent health | `ai-health-check.log` |
| `ai:manage-agents --update-lead-scores` | Daily at 2 AM | Update lead scoring | `ai-lead-scores.log` |
| `ai:manage-agents --generate-descriptions` | Weekly (Sunday 3 AM) | Generate product descriptions | `ai-descriptions.log` |
| `ai:manage-agents --cleanup-old-conversations` | Weekly (Sunday 4 AM) | Clean old conversations | `ai-cleanup.log` |
| `ai-agent:daily-outreach` | Twice daily (9 AM, 2 PM) | Daily customer outreach | `daily-outreach.log` |
| `ai-agent:process-conversations` | Every 5 minutes | Process conversation queue | `conversation-engine.log` |
| `ai-agent:win-back` | Weekly (Wednesday 10 AM) | Win-back campaigns | `win-back.log` |
| `ai-agent:chase-no-reply` | Twice daily (11 AM, 4 PM) | Follow up no-replies | `chase-no-reply.log` |
| `ai-agent:sla-monitor` | Every 15 minutes (business hours) | SLA monitoring | `sla-monitor.log` |
| `overdue-handoffs-check` | Every 30 minutes (6 AM-8 PM) | Check overdue handoffs | `cron-monitor.log` |
| `auto-assign-handoffs` | Every 15 minutes (7 AM-7 PM) | Auto-assign handoffs | `cron-monitor.log` |
| `daily-handoff-summaries` | Daily at 8 AM | Send handoff summaries | `cron-monitor.log` |
| `system-health-monitor` | Every 10 minutes | System health monitoring | `cron-monitor.log` |
| `scheduled-followups` | Every minute | Process scheduled followups | `cron-monitor.log` |
| `cron:monitor --action=health` | Every 30 minutes | Cron health monitoring | `cron-health.log` |
| `cron:monitor --clear-logs` | Weekly (Sunday 5 AM) | Clean old logs | `cron-cleanup.log` |

## Usage Guide

### Starting the Scheduler

#### Windows:
```bash
# Start scheduler as background job
Start-Job -ScriptBlock { php artisan schedule:work }

# Or use the batch script
scripts\cron-monitor.bat start
```

#### Linux/Mac:
```bash
# Start scheduler in background
php artisan schedule:work &

# Or use the shell script
./scripts/cron-monitor.sh start
```

### Monitoring Commands

```bash
# Check status of all cron jobs
php artisan cron:monitor --action=status

# Run health check
php artisan cron:monitor --action=health

# View recent logs
php artisan cron:monitor --action=logs

# Clear old log files
php artisan cron:monitor --clear-logs
```

### Using the Helper Scripts

#### Windows:
```cmd
scripts\cron-monitor.bat status     # Check status
scripts\cron-monitor.bat health     # Health check
scripts\cron-monitor.bat logs       # View logs
scripts\cron-monitor.bat start      # Start scheduler
scripts\cron-monitor.bat stop       # Stop scheduler
scripts\cron-monitor.bat restart    # Restart scheduler
scripts\cron-monitor.bat test       # Test configuration
```

#### Linux/Mac:
```bash
./scripts/cron-monitor.sh status    # Check status
./scripts/cron-monitor.sh health    # Health check
./scripts/cron-monitor.sh logs      # View logs
./scripts/cron-monitor.sh start     # Start scheduler
./scripts/cron-monitor.sh stop      # Stop scheduler
./scripts/cron-monitor.sh restart   # Restart scheduler
./scripts/cron-monitor.sh test      # Test configuration
./scripts/cron-monitor.sh install-cron  # Install system cron
./scripts/cron-monitor.sh remove-cron   # Remove system cron
```

## Log Files

### Main Log Files:
- `storage/logs/laravel.log` - Main Laravel application log
- `storage/logs/cron-monitor.log` - Dedicated cron monitoring log
- `storage/logs/cron-health.log` - Health check results
- `storage/logs/cron-cleanup.log` - Log cleanup activities

### Task-Specific Logs:
- `storage/logs/ai-failed-messages.log` - Failed message processing
- `storage/logs/ai-health-check.log` - AI agent health checks
- `storage/logs/daily-outreach.log` - Daily outreach campaigns
- `storage/logs/conversation-engine.log` - Conversation processing
- `storage/logs/sla-monitor.log` - SLA monitoring alerts

## Status Indicators

- ✅ **OK** - Task running within expected timeframe
- ⚠️ **Late** - Task is running but behind schedule
- ❌ **Overdue** - Task hasn't run for more than 2x expected interval
- ❌ **Never Run** - Task has never executed

## Health Check Items

1. **Scheduler Status** - Checks if Laravel scheduler is running
2. **Log Permissions** - Verifies log directory is writable
3. **Recent Failures** - Scans for recent error messages
4. **Disk Space** - Monitors available disk space for logs
5. **Resource Usage** - Tracks log directory size

## Troubleshooting

### Common Issues:

1. **"Laravel scheduler may not be running"**
   - Solution: Start the scheduler with `php artisan schedule:work`

2. **"Log directory is not writable"**
   - Solution: Fix permissions on `storage/logs` directory

3. **High failure rates**
   - Check individual task log files for specific errors
   - Verify database connections and API endpoints

4. **Tasks showing "Never Run"**
   - Ensure scheduler is properly started
   - Check task timing and conditions

### Manual Task Execution:
```bash
# Run scheduler once manually
php artisan schedule:run -v

# Run specific commands manually
php artisan ai:process-failed-messages
php artisan ai-agent:daily-outreach
```

## Automated Alerts

The system automatically:
- Logs all task executions with timestamps
- Captures success and failure states
- Monitors system health every 10 minutes
- Cleans old log files weekly
- Provides detailed error context

## Best Practices

1. **Monitor Regularly**: Check status at least daily
2. **Review Health**: Run health checks weekly
3. **Clean Logs**: Allow automatic cleanup or run manually
4. **Check Failures**: Investigate any recurring failures
5. **Backup Logs**: Keep important logs for troubleshooting
6. **Resource Monitoring**: Ensure adequate disk space for logs

This monitoring system ensures reliable operation of all SafariChat's automated processes with comprehensive visibility into their performance and health.