# SafariChat Platform - Artisan Commands Reference

This document provides a comprehensive guide to all custom Artisan commands available in the SafariChat platform, their usage, and their importance for system operations.

## 📋 Table of Contents
- [AI Agent Commands](#ai-agent-commands)
- [Data Migration Commands](#data-migration-commands)
- [Communication & Follow-up Commands](#communication--follow-up-commands)
- [System Management Commands](#system-management-commands)
- [Billing & Credits Commands](#billing--credits-commands)
- [Monitoring & Maintenance Commands](#monitoring--maintenance-commands)

---

## 🤖 AI Agent Commands

### `ai-agent:process-conversations`
**Usage:**
```bash
php artisan ai-agent:process-conversations {--limit=100} {--agent=} {--timeout=30}
```

**Description:** Process queued conversations and handle fallback scenarios

**Options:**
- `--limit=100`: Maximum number of conversations to process
- `--agent=`: Specific AI agent ID to process conversations for
- `--timeout=30`: Timeout in seconds for each conversation processing

**Importance:** Core command for AI conversation processing, essential for automated customer interactions and maintaining conversation flow.

**When to use:**
- Scheduled execution every few minutes for real-time conversation handling
- Manual execution when conversation queues build up
- Debugging conversation processing issues

---

### `ai-agent:daily-outreach`
**Usage:**
```bash
php artisan ai-agent:daily-outreach {--limit=50} {--agent=} {--dry-run}
```

**Description:** Execute daily lead outreach campaigns for AI sales agents

**Options:**
- `--limit=50`: Maximum number of leads to contact
- `--agent=`: Specific AI agent to execute outreach
- `--dry-run`: Preview outreach without sending messages

**Importance:** Critical for automated lead nurturing and maintaining engagement with potential customers.

**When to use:**
- Daily scheduled execution for consistent lead outreach
- Manual execution for specific campaigns
- Testing with dry-run before live campaigns

---

### `ai-agent:chase-no-reply`
**Usage:**
```bash
php artisan ai-agent:chase-no-reply {--limit=50} {--agent=} {--hours=48} {--max-chases=3} {--dry-run}
```

**Description:** Follow up with leads who haven't replied to previous messages

**Options:**
- `--limit=50`: Maximum leads to chase
- `--agent=`: Specific AI agent ID
- `--hours=48`: Hours to wait before chasing
- `--max-chases=3`: Maximum number of chase attempts
- `--dry-run`: Preview without sending

**Importance:** Prevents lead abandonment and improves conversion rates by ensuring no potential customer falls through the cracks.

**When to use:**
- Daily or bi-daily scheduled execution
- Manual execution for urgent lead recovery
- Campaign analysis with dry-run mode

---

### `ai-agent:win-back`
**Usage:**
```bash
php artisan ai-agent:win-back {--limit=30} {--agent=} {--days-inactive=30} {--dry-run}
```

**Description:** Execute win-back campaigns for churned or inactive customers

**Options:**
- `--limit=30`: Maximum customers to target
- `--agent=`: Specific AI agent
- `--days-inactive=30`: Days of inactivity threshold
- `--dry-run`: Preview campaign

**Importance:** Reactivates churned customers and recovers lost revenue through targeted re-engagement campaigns.

**When to use:**
- Weekly scheduled execution for churn recovery
- Quarterly major win-back campaigns
- Testing strategies with dry-run

---

### `ai-agent:sla-monitor`
**Usage:**
```bash
php artisan ai-agent:sla-monitor {--alert-threshold=15} {--escalation-threshold=60} {--check-interval=5}
```

**Description:** Monitor handoff response times and SLA compliance

**Options:**
- `--alert-threshold=15`: Minutes before alert
- `--escalation-threshold=60`: Minutes before escalation
- `--check-interval=5`: Check frequency in minutes

**Importance:** Ensures service quality by monitoring response times and triggering alerts for delayed handoffs.

**When to use:**
- Continuous background execution for real-time monitoring
- Manual execution during high-traffic periods
- SLA compliance auditing

---

### `ai:manage-agents`
**Usage:**
```bash
php artisan ai:manage-agents {--action=status} {--agent=} {--batch-size=50}
```

**Description:** Manage AI sales agents and perform maintenance tasks

**Options:**
- `--action=status`: Action to perform (status, activate, deactivate, optimize)
- `--agent=`: Specific agent ID
- `--batch-size=50`: Batch processing size

**Importance:** Central management tool for AI agents, performance optimization, and system health maintenance.

**When to use:**
- Daily agent health checks
- Performance optimization tasks
- Agent lifecycle management

---

## 📊 Data Migration Commands

### `admin:migrate-crm-data`
**Usage:**
```bash
php artisan admin:migrate-crm-data {--user-id=} {--limit=100} {--dry-run}
```

**Description:** Migrate client data from admin_crm database to safarichat with AI-powered conversation context generation

**Options:**
- `--user-id=`: Target SafariChat user ID (required)
- `--limit=100`: Number of clients to process per batch
- `--dry-run`: Preview migration without making changes

**Importance:** Essential for importing legacy CRM data with intelligent context generation, enabling seamless transition to SafariChat.

**When to use:**
- One-time migration from legacy CRM systems
- Incremental data imports
- Data validation with dry-run before production migration

---

## 💬 Communication & Follow-up Commands

### `followup:smart`
**Usage:**
```bash
php artisan followup:smart {--dry-run}
```

**Description:** Send personalized followup messages to non-closed leads based on conversation history and customer language

**Options:**
- `--dry-run`: Preview followups without sending

**Importance:** Automated intelligent follow-ups that maintain engagement and improve conversion rates through personalized messaging.

**When to use:**
- Daily scheduled execution for consistent lead nurturing
- Manual execution for immediate follow-up campaigns
- Strategy testing with dry-run

---

### `ai:process-failed-messages`
**Usage:**
```bash
php artisan ai:process-failed-messages {--limit=50} {--max-age=24} {--dry-run}
```

**Description:** Process failed instant WhatsApp messages through AI system

**Options:**
- `--limit=50`: Maximum messages to process
- `--max-age=24`: Maximum age in hours
- `--dry-run`: Preview processing

**Importance:** Ensures message delivery reliability and prevents communication failures that could impact customer experience.

**When to use:**
- Hourly execution to catch failed messages
- Manual execution during system recovery
- Queue management and cleanup

---

### `notifications:process`
**Usage:**
```bash
php artisan notifications:process
```

**Description:** Process pending notifications in the queue

**Importance:** Maintains notification system functionality, ensuring users receive timely alerts and updates.

**When to use:**
- Continuous background processing
- Manual execution when notification queues build up
- System maintenance and cleanup

---

## 📈 System Management Commands

### `contacts:update-priorities`
**Usage:**
```bash
php artisan contacts:update-priorities {--dry-run}
```

**Description:** Update contact priority levels based on lead scores and other factors

**Options:**
- `--dry-run`: Preview updates without applying changes

**Importance:** Maintains accurate lead prioritization for optimal resource allocation and improved conversion focus.

**When to use:**
- Daily execution for dynamic priority updates
- Manual execution after scoring model changes
- Analysis with dry-run before applying updates

---

### `summaries:send-daily`
**Usage:**
```bash
php artisan summaries:send-daily
```

**Description:** Send daily summaries to inactive users about missed automations

**Importance:** Keeps users informed about system activity and missed opportunities, encouraging platform engagement.

**When to use:**
- Daily scheduled execution
- Manual execution for immediate summary delivery
- User engagement campaigns

---

## 💳 Billing & Credits Commands

### `billing:sync-credits`
**Usage:**
```bash
php artisan billing:sync-credits {--customer-id=}
```

**Description:** Sync pending credit deductions with billing system

**Options:**
- `--customer-id=`: Sync credits for specific customer

**Importance:** Ensures accurate billing and credit management, maintaining financial integrity of the platform.

**When to use:**
- Hourly execution for real-time credit sync
- Manual execution for specific customer billing issues
- End-of-day reconciliation processes

---

## 🔍 Monitoring & Maintenance Commands

### `cron:monitor`
**Usage:**
```bash
php artisan cron:monitor {--job=} {--status} {--cleanup} {--alert-threshold=300}
```

**Description:** Monitor and manage cron jobs with detailed logging

**Options:**
- `--job=`: Monitor specific job
- `--status`: Show job status
- `--cleanup`: Clean old logs
- `--alert-threshold=300`: Alert threshold in seconds

**Importance:** Ensures scheduled tasks run properly and provides visibility into system automation health.

**When to use:**
- Continuous monitoring of scheduled tasks
- Manual execution for system diagnostics
- Maintenance and log cleanup

---

## 🚀 Best Practices & Scheduling

### Recommended Cron Schedule

```bash
# High frequency - every minute
* * * * * php artisan ai-agent:process-conversations --limit=50
* * * * * php artisan notifications:process

# Every 5 minutes
*/5 * * * * php artisan ai-agent:sla-monitor
*/5 * * * * php artisan cron:monitor

# Hourly
0 * * * * php artisan ai:process-failed-messages --limit=100
0 * * * * php artisan billing:sync-credits

# Daily
0 9 * * * php artisan ai-agent:daily-outreach --limit=100
0 10 * * * php artisan followup:smart
0 11 * * * php artisan ai-agent:chase-no-reply --hours=48
0 18 * * * php artisan summaries:send-daily
0 2 * * * php artisan contacts:update-priorities

# Weekly
0 8 * * 1 php artisan ai-agent:win-back --days-inactive=7
0 3 * * 0 php artisan ai:manage-agents --action=optimize
```

### Usage Guidelines

1. **Always test with --dry-run** when available before production execution
2. **Monitor logs** for each command execution to catch issues early
3. **Use appropriate limits** to prevent system overload during peak times
4. **Schedule commands** during low-traffic periods when possible
5. **Keep backups** before running data migration commands
6. **Monitor system resources** when running batch processing commands

### Troubleshooting Common Issues

- **Command timeouts**: Reduce batch limits or increase timeout values
- **Memory issues**: Process data in smaller batches
- **Database locks**: Schedule commands to avoid concurrent execution
- **API rate limits**: Implement delays between API calls
- **Queue backlogs**: Increase processing frequency or batch sizes

---

## 📞 Support & Maintenance

For issues with any command:
1. Check the Laravel logs in `storage/logs/`
2. Verify database connections and credentials
3. Ensure proper environment configuration
4. Test with dry-run mode when available
5. Contact system administrator for critical issues

**Last Updated:** January 8, 2026
**Version:** SafariChat Platform v2.0