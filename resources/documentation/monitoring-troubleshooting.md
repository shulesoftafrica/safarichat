# System Monitoring and Troubleshooting Guide

## Real-time Monitoring Commands

### 1. Check System Status

```bash
# Check if cron jobs are running
php artisan schedule:list

# Check queue workers status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Check system health
php artisan ai:manage-agents --agent-health-check
```

### 2. Database Monitoring

```sql
-- Check message processing stats (last 24 hours)
SELECT 
    DATE_FORMAT(created_at, '%H:00') as hour,
    COUNT(*) as total_messages,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_messages,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_messages,
    ROUND((SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as success_rate
FROM outgoing_messages 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY DATE_FORMAT(created_at, '%H:00')
ORDER BY hour;

-- Check active conversations
SELECT 
    COUNT(*) as total_conversations,
    COUNT(DISTINCT lead_id) as unique_customers,
    AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_response_time
FROM conversations 
WHERE DATE(created_at) = CURDATE();

-- Check instance status
SELECT 
    instance_id,
    phone_number,
    status,
    connect_status,
    last_active_at,
    TIMESTAMPDIFF(MINUTE, last_active_at, NOW()) as minutes_since_active
FROM whatsapp_instances;
```

### 3. Log Monitoring

```bash
# Monitor real-time logs
tail -f storage/logs/laravel.log

# Filter for specific components
tail -f storage/logs/laravel.log | grep -i "wasender\|webhook\|failed"

# Check AI processing logs
tail -f storage/logs/ai-processing.log

# Monitor queue processing
tail -f storage/logs/queue.log
```

## Common Issues and Solutions

### Issue 1: Messages Not Being Sent

#### Symptoms:
- Outgoing messages stuck in "pending" status
- No webhook responses from WaSender
- Queue jobs failing repeatedly

#### Diagnostic Steps:

```bash
# 1. Check queue status
php artisan queue:work --queue=high_priority,messages --verbose

# 2. Test WaSender connection
php artisan tinker
>>> $service = new App\Services\WaSenderService();
>>> $service->isInstanceReady('your_instance_id');

# 3. Check instance in database
>>> App\Models\WhatsappInstance::where('status', 'connected')->get();
```

#### Solutions:

```php
// Manual instance status update
DB::table('whatsapp_instances')
    ->where('instance_id', 'your_instance_id')
    ->update([
        'status' => 'connected',
        'connect_status' => 'ready',
        'last_active_at' => now()
    ]);

// Retry failed jobs
php artisan queue:retry all

// Clear queue and restart
php artisan queue:clear
php artisan queue:restart
```

### Issue 2: AI Not Responding

#### Symptoms:
- Customer messages received but no AI response generated
- High response times
- OpenAI API errors in logs

#### Diagnostic Steps:

```bash
# Check AI agent configuration
php artisan tinker
>>> App\Models\AiSalesAgent::where('status', 'active')->count();

# Test OpenAI connection
>>> $openai = app(App\Services\OpenAiService::class);
>>> $openai->testConnection();

# Check conversation processing
>>> $message = App\Models\IncomingMessage::latest()->first();
>>> app(App\Services\AiWhatsAppService::class)->processIncomingMessage($message);
```

#### Solutions:

```bash
# Update OpenAI API key
# Edit .env file:
OPENAI_API_KEY=your_new_api_key

# Restart queue workers
php artisan queue:restart

# Process pending messages manually
php artisan ai:process-failed-messages --limit=50
```

### Issue 3: Webhook Not Receiving Messages

#### Symptoms:
- No incoming messages in database
- Webhook endpoint returning 500 errors
- Customer replies not triggering AI responses

#### Diagnostic Steps:

```bash
# Check webhook URL configuration
php artisan route:list | grep webhook

# Test webhook endpoint manually
curl -X POST https://yourdomain.com/api/wasender/webhook/test \
  -H "Content-Type: application/json" \
  -d '{"event":"message","data":{"message":"test"}}'

# Check nginx/apache logs
tail -f /var/log/nginx/access.log | grep webhook
```

#### Solutions:

```bash
# Update webhook URL in WaSender dashboard
# URL should be: https://yourdomain.com/api/wasender/webhook/{instanceId}

# Check file permissions
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Restart web server
sudo service nginx restart
# or
sudo service apache2 restart
```

### Issue 4: High Queue Backlog

#### Symptoms:
- Thousands of jobs in queue
- Slow message delivery
- System performance issues

#### Diagnostic Steps:

```bash
# Check queue sizes
redis-cli LLEN "queues:high_priority"
redis-cli LLEN "queues:messages"
redis-cli LLEN "queues:ai_standard"

# Check worker processes
ps aux | grep "queue:work"
```

#### Solutions:

```bash
# Scale up queue workers
php artisan queue:work --queue=high_priority --sleep=1 --tries=3 &
php artisan queue:work --queue=messages --sleep=1 --tries=3 &
php artisan queue:work --queue=ai_standard --sleep=1 --tries=3 &

# Clear old failed jobs
php artisan queue:prune-failed --hours=48

# Optimize queue processing
# Add to config/queue.php:
'redis' => [
    'block_for' => 2,
    'sleep_for' => 1,
    'retry_after' => 60,
]
```

## Performance Optimization

### Database Optimization

```sql
-- Add missing indexes for better performance
ALTER TABLE incoming_messages ADD INDEX idx_phone_timestamp (phone_number, message_timestamp);
ALTER TABLE outgoing_messages ADD INDEX idx_status_created (status, created_at);
ALTER TABLE conversations ADD INDEX idx_lead_created (lead_id, created_at);
ALTER TABLE leads ADD INDEX idx_status_updated (status, updated_at);
ALTER TABLE whatsapp_instances ADD INDEX idx_user_status (user_id, status);

-- Clean up old data
DELETE FROM incoming_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM outgoing_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND status IN ('sent', 'delivered');
DELETE FROM conversations WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Optimize tables
OPTIMIZE TABLE incoming_messages, outgoing_messages, conversations, leads;
```

### Redis Optimization

```bash
# Redis configuration for better queue performance
# Add to redis.conf:
maxmemory-policy allkeys-lru
timeout 300
tcp-keepalive 60

# Monitor Redis memory usage
redis-cli INFO memory

# Clear expired keys
redis-cli EVAL "return redis.call('del', unpack(redis.call('keys', ARGV[1])))" 0 "queues:*_failed"
```

### Application Caching

```php
// Cache frequently accessed data
Cache::remember('active_agents', 3600, function () {
    return AiSalesAgent::where('status', 'active')->with('user')->get();
});

Cache::remember('instance_status_' . $instanceId, 300, function () use ($instanceId) {
    return WaSenderService::isInstanceReady($instanceId);
});

// Cache product recommendations
Cache::remember('products_for_ai', 7200, function () {
    return Product::where('ai_enabled', true)->get();
});
```

## Alerts and Notifications

### Setting Up Monitoring Alerts

```php
// Add to app/Console/Kernel.php in monitorSystemHealth()

// Alert when delivery rate drops below 80%
$deliveryRate = $this->getDeliveryRate(24); // last 24 hours
if ($deliveryRate < 80) {
    $this->sendAlert('Low Delivery Rate', [
        'current_rate' => $deliveryRate,
        'threshold' => 80,
        'action_required' => 'Check WaSender instance status'
    ]);
}

// Alert when queue backlog exceeds 1000
$queueSize = Queue::size('messages') + Queue::size('ai_standard');
if ($queueSize > 1000) {
    $this->sendAlert('High Queue Backlog', [
        'queue_size' => $queueSize,
        'action_required' => 'Scale up queue workers'
    ]);
}

// Alert when AI response time exceeds 30 seconds
$avgResponseTime = $this->getAverageResponseTime(1); // last hour
if ($avgResponseTime > 30) {
    $this->sendAlert('Slow AI Response', [
        'avg_response_time' => $avgResponseTime,
        'action_required' => 'Check OpenAI API status'
    ]);
}
```

### Email Alert Configuration

```php
// Create AlertService
class AlertService {
    public function sendAlert($title, $data) {
        Mail::to(config('app.admin_email'))->send(
            new SystemAlert($title, $data)
        );
        
        // Also send WhatsApp alert to admin
        $waSender = new WaSenderService();
        $message = "🚨 ALERT: {$title}\n\n" . json_encode($data, JSON_PRETTY_PRINT);
        $waSender->sendTextMessage(config('app.admin_phone'), $message);
    }
}
```

### Slack Integration

```php
// Send alerts to Slack
use Illuminate\Support\Facades\Http;

public function sendSlackAlert($message) {
    Http::post(config('services.slack.webhook_url'), [
        'text' => $message,
        'channel' => '#alerts',
        'username' => 'SafariChat Monitor',
        'icon_emoji' => ':warning:'
    ]);
}
```

## Health Check Endpoints

### Create Monitoring API

```php
// routes/api.php
Route::get('/health', 'HealthCheckController@index');
Route::get('/health/detailed', 'HealthCheckController@detailed');

// HealthCheckController.php
class HealthCheckController extends Controller {
    
    public function index() {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'wasender' => $this->checkWaSender(),
        ];
        
        $status = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 'healthy' : 'unhealthy';
        
        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'checks' => $checks
        ], $status === 'healthy' ? 200 : 503);
    }
    
    private function checkDatabase() {
        try {
            DB::select('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function checkRedis() {
        try {
            Redis::ping();
            return ['status' => 'ok', 'message' => 'Redis connection successful'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function checkQueue() {
        $queueSize = Queue::size('messages');
        $failedJobs = DB::table('failed_jobs')->count();
        
        return [
            'status' => $queueSize > 2000 || $failedJobs > 100 ? 'warning' : 'ok',
            'queue_size' => $queueSize,
            'failed_jobs' => $failedJobs
        ];
    }
    
    private function checkWaSender() {
        try {
            $service = new WaSenderService();
            $instance = WhatsappInstance::where('status', 'connected')->first();
            
            if (!$instance) {
                return ['status' => 'warning', 'message' => 'No connected instances'];
            }
            
            $isReady = $service->isInstanceReady($instance->instance_id);
            return [
                'status' => $isReady ? 'ok' : 'warning',
                'instance_id' => $instance->instance_id,
                'ready' => $isReady
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

## Backup and Recovery

### Database Backup Strategy

```bash
#!/bin/bash
# backup-script.sh

# Create daily backup
mysqldump -u username -p database_name > "backup_$(date +%Y%m%d).sql"

# Compress backup
gzip "backup_$(date +%Y%m%d).sql"

# Upload to cloud storage (AWS S3)
aws s3 cp "backup_$(date +%Y%m%d).sql.gz" s3://your-backup-bucket/daily/

# Keep only last 30 days of backups locally
find /backup/path -name "backup_*.sql.gz" -mtime +30 -delete
```

### Queue State Recovery

```php
// In case of system crash, recover queue state
public function recoverQueueState() {
    // Find messages that were being processed but didn't complete
    $stuckMessages = OutgoingMessage::where('status', 'pending')
        ->where('created_at', '<', now()->subMinutes(10))
        ->get();
    
    foreach ($stuckMessages as $message) {
        SendWhatsAppMessage::dispatch(
            $message->message,
            $message->phone_number,
            'whatsapp',
            $message->user_id,
            null,
            $message->instance_id
        );
        
        $message->update(['status' => 'requeued']);
    }
}
```

This comprehensive monitoring and troubleshooting guide provides the tools and knowledge needed to maintain a healthy SafariChat system and quickly resolve any issues that arise.