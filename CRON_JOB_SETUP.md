# SafariChat Laravel Scheduler & Cron Job Setup Guide

## 📋 Overview

SafariChat uses Laravel's built-in task scheduler to manage all automated operations. This guide shows you how to set up cron jobs to ensure all scheduled tasks run properly.

## 🚀 Quick Setup (Recommended)

### **Single Required Cron Entry**

**Linux/Ubuntu Server:**
```bash
* * * * * cd /path/to/your/safarichat && php artisan schedule:run >> /dev/null 2>&1
```

**Windows Server (Command):**
```cmd
* * * * * php C:\xampp\htdocs\safarichat\artisan schedule:run
```

**Shared Hosting (cPanel):**
```bash
* * * * * /usr/local/bin/php /home/yourusername/public_html/safarichat/artisan schedule:run
```

> ⚠️ **Important**: Replace `/path/to/your/safarichat` with your actual project path

---

## 📅 Scheduled Tasks Overview

### **Every Minute (Critical Operations)**
- **Message Processing** - Processes queued WhatsApp messages
- **Scheduled Followups** - Sends due AI sales agent followups  
- **System Heartbeat** - Datetime logging for monitoring

### **Every 5 Minutes**
- **Failed Messages Recovery** 
  ```bash
  php artisan ai:process-failed-messages --limit=100
  ```
- **Conversation Processing**
  ```bash
  php artisan ai-agent:process-conversations --limit=100 --timeout=30
  ```

### **Every 10 Minutes**
- **System Health Monitor** - Checks queue backlogs and failure rates
- **Notification Processing**
  ```bash
  php artisan notifications:process
  ```

### **Every 15 Minutes (Business Hours 7AM-8PM)**
- **Handoff Management** - Overdue checks and auto-assignments
- **SLA Monitoring**
  ```bash
  php artisan ai-agent:sla-monitor --alert-threshold=15 --escalation-threshold=60
  ```

### **Every 30 Minutes**
- **Cron Health Monitoring**
  ```bash
  php artisan cron:monitor --action=health
  ```

### **Hourly Operations**
- **AI Health Checks**
  ```bash
  php artisan ai:manage-agents --agent-health-check
  ```
- **Credit Synchronization**
  ```bash
  php artisan billing:sync-credits
  ```

### **Daily Operations**

**2:00 AM - Lead Scores Update**
```bash
php artisan ai:manage-agents --update-lead-scores
```

**7:00 AM - Daily Summaries**
```bash
php artisan summaries:send-daily
```

**8:00 AM - Handoff Summaries**
- Automated via scheduler

**9:00 AM & 2:00 PM - Daily Outreach**
```bash
php artisan ai-agent:daily-outreach --limit=50
```

**11:00 AM & 4:00 PM - No-Reply Chase**
```bash
php artisan ai-agent:chase-no-reply --limit=50 --hours=48 --max-chases=3
```

### **Weekly Operations**

**Sunday 3:00 AM - Product Descriptions**
```bash
php artisan ai:manage-agents --generate-descriptions
```

**Sunday 4:00 AM - Conversation Cleanup**
```bash
php artisan ai:manage-agents --cleanup-old-conversations
```

**Sunday 5:00 AM - Log Cleanup**
```bash
php artisan cron:monitor --action=logs --clear-logs
```

**Wednesday 10:00 AM - Win-Back Campaigns**
```bash
php artisan ai-agent:win-back --limit=30 --days-inactive=30
```

---

## 🖥️ Platform-Specific Setup Instructions

### **Linux/Ubuntu Server Setup**

1. **Open crontab editor:**
   ```bash
   crontab -e
   ```

2. **Add the scheduler cron job:**
   ```bash
   * * * * * cd /var/www/safarichat && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Verify cron service is running:**
   ```bash
   sudo systemctl status cron
   ```

### **Windows Server (Task Scheduler)**

1. **Open Task Scheduler** (`taskschd.msc`)
2. **Create Basic Task**
3. **Configure Trigger:**
   - Daily
   - Repeat every: 1 minute
   - Duration: Indefinitely
4. **Configure Action:**
   - Program: `php.exe`
   - Arguments: `C:\xampp\htdocs\safarichat\artisan schedule:run`
   - Start in: `C:\xampp\htdocs\safarichat`

### **Shared Hosting (cPanel)**

1. **Navigate to Cron Jobs** in cPanel
2. **Add new cron job:**
   ```bash
   * * * * * /usr/local/bin/php /home/yourusername/public_html/safarichat/artisan schedule:run
   ```
3. **Verify PHP path** with hosting provider if needed

### **Docker Environment**

**Add to your docker-compose.yml:**
```yaml
services:
  scheduler:
    build: .
    volumes:
      - .:/var/www/html
    command: php artisan schedule:work
    depends_on:
      - db
      - redis
```

---

## 🔧 Environment Configuration

### **Required Environment Variables**
```env
APP_ENV=production
DB_CONNECTION=pgsql
DB_HOST=your-database-host
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-database-password

QUEUE_CONNECTION=database
# or QUEUE_CONNECTION=redis for better performance

LOG_CHANNEL=stack
```

### **File Permissions (Linux)**
```bash
# Make sure Laravel can write to logs and storage
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

---

## 📊 Monitoring & Logging

### **Log Files Locations**
- **Cron Activity**: `storage/logs/cron-monitor.log`
- **AI Operations**: `storage/logs/ai-*.log`
- **System Health**: `storage/logs/cron-health.log`
- **Laravel General**: `storage/logs/laravel.log`
- **Credit Sync**: `storage/logs/credit-sync.log`
- **Notifications**: `storage/logs/notifications.log`

### **Real-time Log Monitoring**
```bash
# Monitor all cron activity
tail -f storage/logs/cron-monitor.log

# Monitor failed messages
tail -f storage/logs/ai-failed-messages.log

# Monitor system health
tail -f storage/logs/cron-health.log
```

### **Health Check Commands**
```bash
# Test the scheduler manually
php artisan schedule:run

# List all scheduled tasks
php artisan schedule:list

# Test specific high-priority commands
php artisan ai:process-failed-messages --limit=10
php artisan billing:sync-credits
php artisan notifications:process
```

---

## 🔄 Backup/Fallback Cron Jobs (Optional)

If you want additional safety for critical operations:

```bash
# Critical message processing backup (every 2 minutes)
*/2 * * * * cd /path/to/safarichat && php artisan ai:process-failed-messages --limit=50 >> /dev/null 2>&1

# Daily credit sync backup (1 AM)
0 1 * * * cd /path/to/safarichat && php artisan billing:sync-credits >> /dev/null 2>&1

# Weekly cleanup backup (Sunday 6 AM)
0 6 * * 0 cd /path/to/safarichat && php artisan ai:manage-agents --cleanup-old-conversations >> /dev/null 2>&1
```

---

## 🚨 Troubleshooting

### **Common Issues & Solutions**

**Cron not running:**
```bash
# Check if cron service is active
sudo systemctl status cron

# Restart cron service
sudo systemctl restart cron

# Check cron logs
tail -f /var/log/cron.log
```

**Permission errors:**
```bash
# Fix Laravel permissions
sudo chown -R www-data:www-data /path/to/safarichat
sudo chmod -R 755 /path/to/safarichat/storage
```

**PHP path issues:**
```bash
# Find correct PHP path
which php

# Use full path in cron
* * * * * cd /path/to/safarichat && /usr/bin/php artisan schedule:run
```

**Database connection errors:**
- Verify `.env` database credentials
- Test database connection: `php artisan migrate:status`
- Check if database server is running

### **Testing Commands**
```bash
# Test individual scheduled tasks
php artisan inspire
php artisan ai:process-failed-messages --limit=5
php artisan billing:sync-credits --customer-id=1
php artisan notifications:process

# Debug scheduler
php artisan schedule:run -v
```

---

## ✅ Verification Checklist

- [ ] Single cron job `* * * * * php artisan schedule:run` is set up
- [ ] Cron service is running and active
- [ ] File permissions are correct (775 for storage, www-data owner)
- [ ] Environment variables are properly configured
- [ ] Database connection is working
- [ ] Log files are being created in `storage/logs/`
- [ ] Manual `php artisan schedule:run` executes without errors
- [ ] System can write to log files

---

## 📈 Performance Notes

- **46 scheduled tasks** are managed automatically
- **Overlap protection** prevents concurrent execution
- **Business hours awareness** for customer-facing operations  
- **Comprehensive logging** for monitoring and debugging
- **Graceful error handling** with automatic retries

---

## 🆘 Support

If you encounter issues:

1. **Check logs**: `storage/logs/cron-monitor.log`
2. **Test manually**: `php artisan schedule:run -v`
3. **Verify environment**: `php artisan config:cache`
4. **Check queue status**: `php artisan queue:work --once`

---

## 🎯 Ubuntu 22 Server - Simple Setup Instructions

### **The Answer: Just ONE Command!**

**Yes, you only need ONE cron job entry!** Laravel's scheduler is designed to work with a single cron job that runs every minute. This single command will automatically execute all your scheduled tasks at their designated times.

### **Ubuntu 22 Server Setup (Copy & Paste Ready)**

1. **Open crontab for editing:**
   ```bash
   crontab -e
   ```

2. **Paste this single line** (replace `/var/www/safarichat` with your actual path):
   ```bash
   * * * * * cd /var/www/safarichat && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Save and exit** (Ctrl+X, then Y, then Enter)

4. **Verify cron service is running:**
   ```bash
   sudo systemctl status cron
   ```

### **That's It! Here's What Happens:**

- **Every minute**, cron runs `php artisan schedule:run`
- **Laravel checks** which tasks are due to run at that moment
- **Only due tasks execute** - Laravel handles all the timing logic
- **All 46 scheduled tasks** are managed automatically

### **You Do NOT Need to Add:**
❌ Individual cron entries for each command  
❌ Multiple cron jobs with different schedules  
❌ Manual time calculations  

### **Example: How It Works**
```
12:00 PM - Laravel runs: Message processing, Followups, System heartbeat
12:05 PM - Laravel runs: Failed messages recovery, Conversation processing  
12:10 PM - Laravel runs: System health monitor, Notifications
12:15 PM - Laravel runs: SLA monitoring, Handoff management
...and so on
```

### **Quick Verification**
After setting up the cron job, test it:
```bash
# Test the scheduler manually
php artisan schedule:run

# Check if tasks are being logged
tail -f storage/logs/cron-monitor.log
```

### **How to Monitor All 46 Scheduled Tasks**

#### **1. Real-Time Task Monitoring**
```bash
# Watch all cron activity in real-time
tail -f storage/logs/cron-monitor.log

# Monitor with timestamps
tail -f storage/logs/cron-monitor.log | while read line; do echo "$(date): $line"; done
```

#### **2. Check Task Execution Status**
```bash
# List all scheduled tasks and their next run times
php artisan schedule:list

# Run scheduler in verbose mode to see what's executing
php artisan schedule:run -v

# Test specific tasks manually
php artisan ai:process-failed-messages --limit=5
php artisan billing:sync-credits
php artisan notifications:process
```

#### **3. Log File Analysis**
```bash
# Check recent cron activity (last 50 lines)
tail -50 storage/logs/cron-monitor.log

# Search for errors in the last hour
grep -i error storage/logs/cron-monitor.log | tail -20

# Check specific task success/failure
grep "AI failed messages processing" storage/logs/cron-monitor.log | tail -10
grep "Credit synchronization completed" storage/logs/cron-monitor.log | tail -10
```

#### **4. Task-Specific Log Monitoring**
```bash
# Monitor AI operations
tail -f storage/logs/ai-failed-messages.log
tail -f storage/logs/ai-health-check.log
tail -f storage/logs/daily-outreach.log

# Monitor billing operations
tail -f storage/logs/credit-sync.log

# Monitor notifications
tail -f storage/logs/notifications.log

# Monitor system health
tail -f storage/logs/cron-health.log
```

#### **5. Database Monitoring**
```bash
# Check if messages are being processed
php artisan tinker
# Then in tinker:
# \App\Models\OutgoingMessage::count()
# \App\Models\IncomingMessage::whereDate('created_at', today())->count()
```

#### **6. Create a Health Check Script**
Create `check_scheduler_health.sh`:
```bash
#!/bin/bash
echo "=== SafariChat Scheduler Health Check ==="
echo "Date: $(date)"
echo ""

echo "1. Cron Service Status:"
systemctl is-active cron

echo ""
echo "2. Recent Scheduler Activity (last 10 entries):"
tail -10 /var/www/safarichat/storage/logs/cron-monitor.log

echo ""
echo "3. Error Count in Last Hour:"
grep -c "error" /var/www/safarichat/storage/logs/cron-monitor.log | tail -1

echo ""
echo "4. Critical Task Status:"
echo "   - Message Processing: $(grep -c 'Message processing completed' /var/www/safarichat/storage/logs/cron-monitor.log | tail -1) times today"
echo "   - Followup Processing: $(grep -c 'Scheduled followups processing completed' /var/www/safarichat/storage/logs/cron-monitor.log | tail -1) times today"
echo "   - AI Health Checks: $(grep -c 'AI health check completed' /var/www/safarichat/storage/logs/cron-monitor.log | tail -1) times today"

echo ""
echo "5. Log File Sizes:"
ls -lh /var/www/safarichat/storage/logs/*.log | grep -E "(cron|ai-|credit|notification)"
```

Make it executable and run:
```bash
chmod +x check_scheduler_health.sh
./check_scheduler_health.sh
```

#### **7. Set Up Automated Health Alerts**
Add to your crontab (every hour):
```bash
0 * * * * /var/www/safarichat/check_scheduler_health.sh | mail -s "SafariChat Scheduler Health" your-email@domain.com
```

#### **8. Laravel Telescope (Optional)**
If you have Laravel Telescope installed:
```bash
# Enable Telescope to monitor scheduled tasks
php artisan telescope:install
php artisan migrate
```
Then visit `/telescope/schedule` in your browser.

#### **9. Expected Log Patterns**
Healthy logs should show patterns like:
```
[2026-01-04 12:00:15] info: Message processing completed successfully
[2026-01-04 12:00:15] info: Scheduled followups processing completed  
[2026-01-04 12:05:12] info: AI failed messages processing completed
[2026-01-04 12:10:08] info: System health monitoring completed
[2026-01-04 12:15:05] info: SLA monitoring completed
```

#### **10. Quick Daily Health Check Command**
```bash
# One-liner to check if scheduler ran in last 5 minutes
if [ $(find storage/logs/cron-monitor.log -mmin -5) ]; then 
    echo "✅ Scheduler is active"; 
else 
    echo "❌ Scheduler may be down"; 
fi
```

#### **11. Troubleshooting "No Action" Issues**

If your scheduler runs but does no actual work, follow these debugging steps:

**Step 1: Check if tasks have work to do**
```bash
# Check for pending followups
php artisan tinker
# In tinker, run:
# \App\Models\Conversation::where('followup_scheduled_at', '<=', now())->where('followup_sent', false)->count()
# exit

# Check for failed messages to process
php artisan tinker
# In tinker, run:
# \App\Models\OutgoingMessage::whereIn('status', ['failed', 'pending'])->count()
# exit
```

**Step 2: Force run scheduler with maximum verbosity**
```bash
# Run with full debugging output
php artisan schedule:run -vvv

# Run specific commands manually to see detailed output
php artisan ai:process-failed-messages --limit=5 -v
php artisan billing:sync-credits -v
php artisan summaries:send-daily -v
```

**Step 3: Check environment and database**
```bash
# Verify database connection
php artisan migrate:status

# Check if .env is properly configured
php artisan config:cache
php artisan config:clear

# Verify queue system
php artisan queue:work --once

# Test basic database queries
php artisan tinker
# In tinker, run:
# \App\Models\User::count()
# \App\Models\Conversation::count()
# exit
```

**Step 4: Enable detailed logging temporarily**
Add this to your `.env` file:
```env
LOG_LEVEL=debug
APP_DEBUG=true
```

Then run:
```bash
php artisan config:cache
php artisan schedule:run -v
```

Check the Laravel log:
```bash
tail -50 storage/logs/laravel.log
```

**Step 5: Test individual task components**
```bash
# Test followup system specifically
php artisan tinker
# In tinker, run these commands one by one:
# $conversations = \App\Models\Conversation::where('followup_scheduled_at', '<=', now())->where('followup_sent', false)->with(['lead', 'product'])->limit(5)->get()
# echo "Found " . $conversations->count() . " due followups"
# foreach($conversations as $conv) { echo "Conversation ID: " . $conv->id . ", Due: " . $conv->followup_scheduled_at . "\n"; }
# exit

# Test message processing
php artisan tinker
# In tinker, run:
# $messages = \App\Models\OutgoingMessage::whereIn('status', ['failed', 'pending'])->limit(5)->get()
# echo "Found " . $messages->count() . " messages to process"
# exit
```

**Step 6: Create test data to verify scheduler works**
```bash
# Create a test followup
php artisan tinker
# In tinker, create test data:
# $user = \App\Models\User::first()
# if($user && $user->business) {
#   $lead = \App\Models\Lead::first() ?? \App\Models\Lead::create(['business_id' => $user->business->id, 'name' => 'Test Lead', 'phone' => '+1234567890'])
#   $conversation = \App\Models\Conversation::create(['lead_id' => $lead->id, 'followup_scheduled_at' => now()->subMinutes(5), 'followup_sent' => false, 'followup_message' => 'Test followup message'])
#   echo "Created test conversation ID: " . $conversation->id
# }
# exit

# Now run scheduler and check if it processes the test followup
php artisan schedule:run -v
```

**Step 7: Check for missing dependencies**
```bash
# Verify all required models and services exist
php artisan tinker
# Test if core classes can be loaded:
# app(\App\Services\AiWhatsAppService::class)
# app(\App\Services\BillingService::class)  
# app(\App\Services\SystemWhatsAppService::class)
# \App\Models\WhatsappInstance::count()
# exit
```

**Step 8: Check scheduled task definitions**
```bash
# List what tasks are actually scheduled
php artisan schedule:list --next

# Verify specific schedules are working
php artisan inspire  # This should work if scheduler is functional
```

**Common Causes of "No Action" Issues:**

1. **No Data to Process**: Tasks run but find no work (no due followups, no failed messages)
2. **Database Issues**: Connection problems or missing tables
3. **Missing Dependencies**: Required services or models not loading
4. **Environment Issues**: Wrong APP_ENV or missing configuration  
5. **Permission Issues**: Can't write to logs or access files
6. **WhatsApp Instance Issues**: No active WhatsApp instances configured

**Step 9: Database Schema Issues (Missing Columns/Tables)**

If you see errors like "column does not exist" or "table not found":

```bash
# Check if all migrations have been run
php artisan migrate:status

# Run any pending migrations
php artisan migrate

# If you see specific column errors (e.g., "priority" column missing):
php artisan tinker
# Check table structure:
# \DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='handoffs'")
# \DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='conversations'")
# exit

# Check for missing tables or columns
php artisan tinker
# Test if specific tables exist:
# \Schema::hasTable('handoffs')
# \Schema::hasTable('conversations') 
# \Schema::hasTable('leads')
# \Schema::hasColumn('handoffs', 'priority_level')
# \Schema::hasColumn('conversations', 'followup_scheduled_at')
# exit
```

**Fix Missing Database Columns:**
```bash
# Create migration for missing columns
php artisan make:migration add_missing_columns_to_handoffs_table

# Edit the migration file and add missing columns:
# public function up() {
#     Schema::table('handoffs', function (Blueprint $table) {
#         if (!Schema::hasColumn('handoffs', 'priority_level')) {
#             $table->string('priority_level')->default('medium');
#         }
#         if (!Schema::hasColumn('handoffs', 'status')) {
#             $table->string('status')->default('pending');
#         }
#     });
# }

# Run the migration
php artisan migrate
```

**Verify Database Schema:**
```bash
# Check all tables exist
php artisan tinker
# Run these checks:
# echo "Tables check:\n"
# echo "- handoffs: " . (\Schema::hasTable('handoffs') ? "EXISTS" : "MISSING") . "\n"  
# echo "- conversations: " . (\Schema::hasTable('conversations') ? "EXISTS" : "MISSING") . "\n"
# echo "- leads: " . (\Schema::hasTable('leads') ? "EXISTS" : "MISSING") . "\n"
# echo "- whatsapp_instances: " . (\Schema::hasTable('whatsapp_instances') ? "EXISTS" : "MISSING") . "\n"
# echo "\nColumns check:\n"
# echo "- handoffs.priority_level: " . (\Schema::hasColumn('handoffs', 'priority_level') ? "EXISTS" : "MISSING") . "\n"
# echo "- conversations.followup_scheduled_at: " . (\Schema::hasColumn('conversations', 'followup_scheduled_at') ? "EXISTS" : "MISSING") . "\n"
# echo "- conversations.followup_sent: " . (\Schema::hasColumn('conversations', 'followup_sent') ? "EXISTS" : "MISSING") . "\n"
# exit
```

**Quick Fix Commands:**
```bash
# Reset caches and rebuild
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Fix permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Restart web server (if needed)
sudo systemctl restart nginx
# or
sudo systemctl restart apache2
```

### **Final Ubuntu 22 Server Commands Summary:**
```bash
# 1. Edit crontab
crontab -e

# 2. Add this single line:
* * * * * cd /var/www/safarichat && php artisan schedule:run >> /dev/null 2>&1

# 3. Verify setup
php artisan schedule:run -v
tail -f storage/logs/cron-monitor.log
```

**That's all you need!** Laravel's scheduler handles everything else automatically. 🎉

---

**Last Updated**: January 4, 2026  
**Laravel Version**: 9.x+  
**PHP Version**: 8.0+