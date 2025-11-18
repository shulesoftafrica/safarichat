# SafariChat Queue System Configuration Guide

## Overview
This guide provides instructions for configuring the SafariChat queue system for both development (database) and production (Redis) environments.

## Current Configuration (Development)
The system is currently configured to use the database driver for queue processing, which is perfect for development and testing.

### Database Configuration
```env
QUEUE_CONNECTION=database
```

## Production Configuration (Redis)

### 1. Install Redis
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install redis-server

# CentOS/RHEL
sudo yum install redis

# Start Redis service
sudo systemctl start redis
sudo systemctl enable redis
```

### 2. Install PHP Redis Extension
```bash
# Ubuntu/Debian
sudo apt install php-redis

# Or using PECL
sudo pecl install redis
```

### 3. Update .env for Production
```env
# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Redis Specific Queue Settings
REDIS_QUEUE=default
```

### 4. Configure Redis Cache (Optional but recommended)
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Queue Architecture

### Queue Priorities
1. **high_priority** - Incoming messages, urgent responses (processed first)
2. **messages** - Regular outgoing messages 
3. **bulk_messages** - Large batch operations (processed last)
4. **default** - System operations

### Queue Workers for Production

#### Supervisor Configuration (Recommended)
Create `/etc/supervisor/conf.d/safarichat-worker.conf`:

```ini
[program:safarichat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/safarichat/artisan queue:work redis --queue=high_priority,messages,bulk_messages,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/safarichat-worker.log
stopwaitsecs=3600
```

#### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start safarichat-worker:*
```

#### Manual Queue Worker (Alternative)
```bash
# Single worker
php artisan queue:work redis --queue=high_priority,messages,bulk_messages,default

# Multiple workers (in separate terminals)
php artisan queue:work redis --queue=high_priority --timeout=300
php artisan queue:work redis --queue=messages --timeout=300  
php artisan queue:work redis --queue=bulk_messages --timeout=600
```

## Testing the Queue System

### 1. Using Artisan Command
```bash
# Test all queue functionality
php artisan queue:test-system

# Test only outgoing messages
php artisan queue:test-system --type=outgoing

# Test only incoming messages  
php artisan queue:test-system --type=incoming
```

### 2. Using Web Interface
Visit: `http://your-domain.com/test-queue`

### 3. Manual Testing

#### Test Outgoing Message
```bash
curl -X POST http://your-domain.com/api/waapi/test-queue-message \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d "phone_number=+255123456789&message=Test message"
```

#### Test Incoming Message Processing
```bash
curl -X POST http://your-domain.com/api/waapi/test-incoming-message \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d "instance_id=test&phone_number=+255987654321&message=Hello test"
```

## Monitoring & Maintenance

### Queue Monitoring Commands
```bash
# Check queue status
php artisan queue:monitor database:default,database:high_priority

# Restart all queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all

# Check queue statistics
php artisan queue:work --once --timeout=1
```

### Logs
- Queue worker logs: `/storage/logs/laravel.log`
- Supervisor logs: `/var/log/safarichat-worker.log`
- Redis logs: `/var/log/redis/redis-server.log`

## Performance Optimization

### Redis Configuration (`/etc/redis/redis.conf`)
```conf
# Memory optimization
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence (optional for queue data)
save ""
appendonly no

# Network
bind 127.0.0.1
port 6379
timeout 300

# Performance
tcp-keepalive 60
tcp-backlog 511
```

### Laravel Queue Configuration
```php
// config/queue.php adjustments for high-volume
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 300,
    'block_for' => 5, // Reduce blocking time
    'after_commit' => false,
],
```

## Troubleshooting

### Common Issues

1. **Jobs not processing**
   - Check if queue worker is running: `ps aux | grep queue:work`
   - Verify Redis connection: `redis-cli ping`
   - Check Laravel logs for errors

2. **Memory issues**
   - Add `--memory=512` to queue:work command
   - Restart workers periodically with `queue:restart`

3. **Failed jobs accumulating**
   - Review failed job exceptions: `php artisan queue:failed`
   - Fix underlying issues and retry: `php artisan queue:retry all`

4. **Slow processing**
   - Increase number of workers
   - Optimize job payload size
   - Use appropriate queue priorities

### Health Checks
```bash
# Check Redis status
redis-cli ping

# Check queue workers
ps aux | grep queue:work

# Check recent jobs
php artisan tinker
>>> DB::table('jobs')->count()
>>> DB::table('failed_jobs')->count()
```

## Security Considerations

1. **Redis Security**
   - Use password authentication: `requirepass your-strong-password`
   - Bind to localhost only: `bind 127.0.0.1`
   - Disable dangerous commands: `rename-command FLUSHDB ""`

2. **Queue Data**
   - Avoid storing sensitive data in job payloads
   - Use encryption for sensitive parameters
   - Implement proper access controls

## Backup & Recovery

### Queue Data Backup
```bash
# Redis backup
redis-cli BGSAVE

# Database jobs backup (if using database driver)
mysqldump -u user -p database jobs failed_jobs > queue_backup.sql
```

This configuration ensures robust, scalable message processing for the SafariChat application with proper fallbacks and monitoring capabilities.
