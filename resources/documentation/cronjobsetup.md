# SafariChat - crontab -e

```cron
# ============================================================
# SafariChat Laravel Scheduler
# Edit with: sudo crontab -u www-data -e
# ============================================================

# Laravel handles ALL task scheduling internally.
# This one line is the only entry needed.
# It fires every minute - Laravel decides what runs next.

* * * * * cd /var/www/safarichat && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## What Laravel runs automatically from that one line:

```
Every 1 min   -> message:processing (incoming/outgoing WhatsApp messages)
Every 1 min   -> messages:send-scheduled --limit=100
Every 1 min   -> scheduled-followups (SmartFollowupService)

Every 5 min   -> ai:process-failed-messages --limit=100
Every 5 min   -> ai-agent:process-conversations --limit=100 --timeout=30
Every 5 min   -> campaigns:personalize --limit=200 --batch=50

Every 10 min  -> notifications:process
Every 10 min  -> system-health-monitor (queue backlog + failure rate alerts)

Every 15 min  -> whatsapp:check-instances
Every 15 min  -> ai-agent:sla-monitor --alert-threshold=15 --escalation-threshold=60  [07:00-20:00 only]
Every 15 min  -> cs:trial-monitor
Every 15 min  -> auto-assign-handoffs  [07:00-19:00 only]

Every 30 min  -> cron:monitor --action=health
Every 30 min  -> overdue-handoffs-check  [06:00-20:00 only]

Hourly        -> ai:manage-agents --agent-health-check
Hourly        -> billing:sync-credits
Hourly        -> appointments:process-reminders  [07:00-20:00 only]
Hourly        -> cs:usage-monitor

Daily 02:00   -> ai:manage-agents --update-lead-scores
Daily 02:30   -> contacts:update-priorities
Daily 07:00   -> summaries:send-daily
Daily 08:00   -> cs:inactivity-monitor
Daily 08:00   -> daily-handoff-summaries
Daily 08:30   -> contacts:convert-unengaged --limit=15 --days-old=1
Daily 09:00   -> cs:trial-reminders
Daily 10:00   -> followup:smart
Daily 11:00   -> ai-agent:chase-no-reply --limit=50 --hours=48 --max-chases=3
Daily 13:30   -> contacts:convert-unengaged --limit=15 --days-old=1
Daily 16:00   -> ai-agent:chase-no-reply --limit=50 --hours=48 --max-chases=3
Daily 20:00   -> cs:daily-summary

Weekly Mon 14:00  -> ai-agent:win-back --limit=20 --days-inactive=14
Weekly Wed 10:00  -> ai-agent:win-back --limit=30 --days-inactive=30
Weekly Fri 14:00  -> ai-agent:win-back --limit=20 --days-inactive=14
Weekly Sun 03:00  -> ai:manage-agents --generate-descriptions
Weekly Sun 04:00  -> ai:manage-agents --cleanup-old-conversations
Weekly Sun 05:00  -> cron:monitor --action=logs --clear-logs
```