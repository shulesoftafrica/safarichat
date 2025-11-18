# Queue System Implementation Guide

## Overview
This guide covers the complete implementation of Laravel Queues with Redis, Horizon, Sentry, and Telescope for handling asynchronous message processing at scale (100,000+ messages/day).

## Architecture Components

### 1. Queue System
- **Driver**: Redis (high-performance queue backend)
- **Monitoring**: Laravel Horizon (real-time queue management)
- **Error Tracking**: Sentry (production error monitoring)
- **Debugging**: Laravel Telescope (development debugging)

### 2. Queue Types
- `messages` - Standard message queue
- `high_priority` - Urgent messages (user-triggered sends)
- `bulk_messages` - Large batch processing (100+ recipients)

## Installation Steps

### 1. Install Dependencies
```bash
composer install
```

### 2. Install Redis Server
**Windows (using Chocolatey):**
```bash
choco install redis-64
```

**Windows (manual):**
1. Download Redis from: https://github.com/microsoftarchive/redis/releases
2. Extract and run `redis-server.exe`

**Linux/Ubuntu:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### 3. Run Database Migrations
```bash
php artisan migrate
```

### 4. Install Horizon Assets
```bash
php artisan horizon:install
php artisan horizon:publish
```

### 5. Install Telescope (Development Only)
```bash
php artisan telescope:install
php artisan migrate
```

## Configuration

### 1. Environment Variables
Update your `.env` file with the following:

```env
# Queue Configuration
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Horizon Configuration
HORIZON_REDIS_CONNECTION=horizon
HORIZON_PREFIX=horizon:

# Sentry Configuration (Production)
SENTRY_LARAVEL_DSN=your_sentry_dsn_here
SENTRY_RELEASE=1.0.0
SENTRY_ENVIRONMENT=production
SENTRY_SAMPLE_RATE=1.0
SENTRY_TRACES_SAMPLE_RATE=0.1

# Telescope Configuration (Development)
TELESCOPE_ENABLED=true
TELESCOPE_DRIVER=database
```

### 2. Production Environment
For production, update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
TELESCOPE_ENABLED=false
SENTRY_ENVIRONMENT=production
```

## Usage

### 1. Start Queue Workers

**Development (with Horizon):**
```bash
php artisan horizon
```

**Production (with Supervisor):**
Create `/etc/supervisor/conf.d/horizon.conf`:
```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/your/project/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/horizon.log
stopwaitsecs=3600
```

### 2. Message Processing Flow

**Small batches (< 100 recipients):**
- Individual jobs sent to `messages` or `high_priority` queue
- 2-second delays between jobs to respect API rate limits

**Large batches (100+ recipients):**
- Automatically routed to `ProcessBulkMessages` job
- Split into 50-message chunks with 10-second delays
- Processed via `bulk_messages` queue

### 3. Monitoring

**Horizon Dashboard:**
- Visit: `/horizon` (development)
- Real-time queue status, worker metrics, failed jobs

**Telescope Dashboard:**
- Visit: `/telescope` (development only)
- Request debugging, query analysis, job monitoring

**Sentry Dashboard:**
- Production error tracking and alerts
- Performance monitoring

## Key Features

### 1. Intelligent Routing
The system automatically routes messages based on recipient count:
- < 100 recipients: Individual queue processing
- 100+ recipients: Bulk batch processing

### 2. Message Personalization
Automatic hashtag replacement:
- `#name` → User's name
- `#pledge` → User's pledge amount
- `#paid_amount` → Amount paid
- `#balance` → Outstanding balance
- `#days_remain` → Days remaining

### 3. Error Handling
- 3 retry attempts with exponential backoff
- Detailed error logging
- Automatic failed job tracking
- Dead letter queue for manual review

### 4. Performance Optimization
- Redis caching for high-speed queue operations
- Auto-scaling workers (2-20 processes)
- Memory-efficient batch processing
- Rate limiting to prevent API throttling

## Troubleshooting

### 1. Redis Connection Issues
```bash
# Check Redis status
redis-cli ping

# Restart Redis (Linux)
sudo systemctl restart redis-server

# Restart Redis (Windows)
# Stop and start redis-server.exe
```

### 2. Queue Worker Issues
```bash
# Clear failed jobs
php artisan queue:clear

# Restart Horizon
php artisan horizon:terminate
php artisan horizon

# Check queue status
php artisan queue:work --once
```

### 3. Migration Issues
```bash
# Rollback and re-run migrations
php artisan migrate:rollback --step=4
php artisan migrate
```

## Scaling Considerations

### 1. High Volume Processing
For 100,000+ messages/day:
- Monitor Redis memory usage
- Adjust worker counts in `config/horizon.php`
- Consider Redis cluster for extreme volumes
- Implement message priority queues

### 2. Error Rate Monitoring
- Set up Sentry alerts for high error rates
- Monitor API response times
- Track delivery success rates
- Implement circuit breakers for API failures

### 3. Database Optimization
- Add indexes on frequently queried columns
- Archive old outgoing_messages records
- Monitor database connection pool
- Use read replicas for analytics queries

## Security Notes

1. **Sentry DSN**: Keep production DSN secret
2. **Redis Access**: Secure Redis with authentication in production
3. **Horizon Access**: Restrict `/horizon` access in production
4. **Telescope**: Never enable in production (`TELESCOPE_ENABLED=false`)

## Maintenance

### Daily Tasks
- Monitor queue length via Horizon
- Check error rates in Sentry
- Review failed jobs

### Weekly Tasks
- Clean up old telescope entries
- Archive processed messages
- Review worker performance metrics

### Monthly Tasks
- Update dependencies
- Review error patterns
- Optimize queue configurations based on usage patterns
