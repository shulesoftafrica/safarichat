# APPLICATION SERVER
H/W path             Device      Class          Description
===========================================================
                                 system         Standard PC (i440FX + PIIX, 1996)
/0                               bus            Motherboard
/0/0                             memory         96KiB BIOS
/0/400                           processor      AMD EPYC 7282 16-Core Processor
/0/1000                          memory         32GiB System Memory
/0/1000/0                        memory         16GiB DIMM RAM
/0/1000/1                        memory         16GiB DIMM RAM
/0/100                           bridge         440FX - 82441FX PMC [Natoma]
/0/100/1                         bridge         82371SB PIIX3 ISA [Natoma/Triton II]
/0/100/1/0                       communication  PnP device PNP0501
/0/100/1/1                       input          PnP device PNP0303
/0/100/1/2                       input          PnP device PNP0f13
/0/100/1/3                       storage        PnP device PNP0700
/0/100/1/4                       system         PnP device PNP0b00
/0/100/1.1                       storage        82371SB PIIX3 IDE [Natoma/Triton II]
/0/100/1.2                       bus            82371SB PIIX3 USB [Natoma/Triton II]
/0/100/1.2/1         usb1        bus            UHCI Host Controller
/0/100/1.3                       bridge         82371AB/EB/MB PIIX4 ACPI
/0/100/2                         display        SVGA II Adapter
/0/100/5                         storage        Virtio SCSI
/0/100/5/0           scsi0       generic        Virtual I/O device
/0/100/5/0/0.0.0     /dev/sda    disk           257GB QEMU HARDDISK
/0/100/5/0/0.0.0/1   /dev/sda1   volume         238GiB EXT4 volume
/0/100/5/0/0.0.0/e   /dev/sda14  volume         4095KiB BIOS Boot partition
/0/100/5/0/0.0.0/f   /dev/sda15  volume         105MiB Windows FAT volume
/0/100/5/0/0.0.0/10  /dev/sda16  volume         913MiB EXT4 volume
/0/100/12                        network        Virtio network device
/0/100/12/0          eth0        network        Ethernet interface
/0/100/1e                        bridge         QEMU PCI-PCI bridge
/0/100/1f                        bridge         QEMU PCI-PCI bridge
/1                   input0      input          Power Button
/2                   input1      input          AT Translated Set 2 keyboard
/3                   input3      input          VirtualPS/2 VMware VMMouse
/4                   input4      input          VirtualPS/2 VMware VMMouse

# DATABASE SERVER
H/W path             Device      Class          Description
===========================================================
                                 system         Standard PC (i440FX + PIIX, 1996)
/0                               bus            Motherboard
/0/0                             memory         96KiB BIOS
/0/400                           processor      AMD EPYC 7282 16-Core Processor
/0/1000                          memory         64GiB System Memory
/0/1000/0                        memory         16GiB DIMM RAM
/0/1000/1                        memory         16GiB DIMM RAM
/0/1000/2                        memory         16GiB DIMM RAM
/0/1000/3                        memory         16GiB DIMM RAM
/0/100                           bridge         440FX - 82441FX PMC [Natoma]
/0/100/1                         bridge         82371SB PIIX3 ISA [Natoma/Triton II]
/0/100/1/0                       communication  PnP device PNP0501
/0/100/1/1                       input          PnP device PNP0303
/0/100/1/2                       input          PnP device PNP0f13
/0/100/1/3                       storage        PnP device PNP0700
/0/100/1/4                       system         PnP device PNP0b00
/0/100/1.1                       storage        82371SB PIIX3 IDE [Natoma/Triton II]
/0/100/1.2                       bus            82371SB PIIX3 USB [Natoma/Triton II]
/0/100/1.2/1         usb1        bus            UHCI Host Controller
/0/100/1.3                       bridge         82371AB/EB/MB PIIX4 ACPI
/0/100/2                         display        VGA compatible controller
/0/100/5                         storage        Virtio SCSI
/0/100/5/0           scsi0       generic        Virtual I/O device
/0/100/5/0/0.0.0     /dev/sda    disk           515GB QEMU HARDDISK
/0/100/5/0/0.0.0/1   /dev/sda1   volume         478GiB EXT4 volume
/0/100/5/0/0.0.0/e   /dev/sda14  volume         4095KiB BIOS Boot partition
/0/100/5/0/0.0.0/f   /dev/sda15  volume         105MiB Windows FAT volume
/0/100/5/0/0.0.0/10  /dev/sda16  volume         913MiB EXT4 volume
/0/100/12                        network        Virtio network device
/0/100/12/0          eth0        network        Ethernet interface
/0/100/1e                        bridge         QEMU PCI-PCI bridge
/0/100/1f                        bridge         QEMU PCI-PCI bridge
/1                   input0      input          Power Button
/2                   input1      input          AT Translated Set 2 keyboard
/3                   input3      input          VirtualPS/2 VMware VMMouse
/4                   input4      input          VirtualPS/2 VMware VMMouse

---

# CAPACITY ANALYSIS & PRODUCTION OPERATIONS GUIDE

> Written: March 27, 2026. Based on the hardware above and how SafariChat actually works
> (Laravel 10, PostgreSQL, Redis, Laravel Horizon, OpenAI GPT-4o, WaSender webhooks).

---

## 1. What Kind of Load Does This App Actually Generate?

SafariChat is **not** a traditional request/response web app. The dominant workload is:

| Traffic type | Description | Volume driver |
|---|---|---|
| WaSender webhooks | Inbound POST per WhatsApp message received | # of active WhatsApp instances |
| OpenAI API calls | One per inbound AI message, dispatched via queue | # of active AI agents |
| Dashboard HTTP | SPA-style requests from business owners | # of logged-in users |
| Outgoing WhatsApp | One HTTP call per AI reply sent | Same as OpenAI calls |

Because all AI processing goes through **Laravel Horizon queues**, the web server is mostly decoupled from the heavy work. Webhooks arrive, get queued in milliseconds, and return 200 OK. The queue workers do the slow lifting asynchronously.

---

## 2. Memory Budget — Application Server (32 GB)

| Process | Count | Per-process | Total |
|---|---|---|---|
| OS kernel + system services | — | — | ~1.0 GB |
| nginx | 16 workers | ~5 MB | ~80 MB |
| PHP-FPM web workers | 28 | ~110 MB avg | ~3.1 GB |
| Laravel Horizon daemon | 1 | ~80 MB | ~80 MB |
| Horizon queue worker: ai_priority | 3 (default) | 512 MB max | ~1.5 GB |
| Horizon queue worker: ai_standard | 5 (default) | 512 MB max | ~2.6 GB |
| Horizon queue worker: ai_maintenance | 1 (default) | 256 MB max | ~256 MB |
| Redis (sessions + cache + queues) | 1 | ~1.5 GB | ~1.5 GB |
| Miscellaneous (cron, supervisord, etc.) | — | — | ~300 MB |
| **Total committed** | | | **~10.4 GB** |
| **Safe headroom** | | | **~21.6 GB** |

You are using roughly **32% of RAM** at the default queue worker settings. Very comfortable.

---

## 3. Maximum Supported Users

### Registered business accounts (dashboard + billing only)
**~10,000 accounts** — PostgreSQL on 64 GB RAM with proper tuning (see Section 6) handles this order of magnitude without any architectural changes. The 478 GB disk is not a constraint for account data at this scale.

### Concurrent dashboard sessions
**200–350 at a time** — 28 PHP-FPM workers each hold a connection for ~100–300 ms on a typical page API call. At 250 ms average, 28 workers serve ~112 requests/second, which maps to roughly 200–350 users browsing simultaneously without noticeable lag.

### Active businesses sending WhatsApp messages
**800–1,200 businesses** comfortably; **up to ~2,000** with the expanded queue workers in Section 5.

The math at default settings — 8 combined ai_priority + ai_standard workers, each OpenAI call averaging ~3 seconds:

```
8 workers × (60 s / 3 s per call) = 160 AI responses/minute
160 × 60 min × 16 business hours = ~153,600 AI responses/day
At 80 messages/day per active business: 153,600 / 80 = ~1,920 active businesses
```

This is conservative. Off-peak hours provide significant headroom for burst traffic.

### The real ceiling: OpenAI API rate limits (not your hardware)
Your servers can push jobs to OpenAI far faster than OpenAI will accept them:

| OpenAI Tier | RPM limit (GPT-4o) | Effective active businesses |
|---|---|---|
| Tier 1 (default, <$50 lifetime spend) | 500 RPM | ~375 |
| Tier 2 ($50+ spend) | 5,000 RPM | ~3,750 |
| Tier 3 ($250+ spend) | 10,000 RPM | ~7,500 |

**Priority action before launch:** Request an OpenAI Tier 2 upgrade immediately. It is automatic once you have $50 in API spend. Your hardware is not the bottleneck — OpenAI's rate limits are.

---

## 4. Concurrent Request Capacity

| Request type | Capacity | Limiting factor |
|---|---|---|
| Webhook arrivals (WaSender) | ~2,000/min burst | PHP-FPM queue-and-release; ~5 ms each |
| AI responses processed | 160–320/min | Queue worker count × OpenAI latency |
| Dashboard HTTP requests | ~6,000–7,000/min | 28 PHP-FPM workers × ~250 ms avg |
| Vision (image) requests | Same pool as standard AI | `detail: low` ≈ 1.5× token cost vs text |
| Outgoing WhatsApp via WaSender | ~500/min | WaSender API rate limit |

---

## 5. Scale Queue Workers Without New Hardware

You have **21 GB of free RAM**. Before buying anything, increase workers in `.env`:

```ini
# Current defaults → Recommended for 1,000–3,000 active businesses
AI_PRIORITY_WORKERS=6        # was 3  (+1.5 GB RAM)
AI_STANDARD_WORKERS=12       # was 5  (+3.5 GB RAM)
AI_MAINTENANCE_WORKERS=2     # was 1  (+256 MB RAM)
```

**Additional RAM used:** ~5.3 GB. Still 16 GB headroom remaining.
**New throughput:** 18 combined workers × 20 calls/min = **360 AI responses/minute**.

---

## 6. PostgreSQL Tuning — Database Server (64 GB RAM, 478 GB disk)

Add to `/etc/postgresql/*/main/postgresql.conf`:

```ini
# Memory — 25% of 64 GB is the standard rule for shared_buffers
shared_buffers             = 16GB
effective_cache_size       = 48GB
work_mem                   = 64MB
maintenance_work_mem       = 2GB

# Connections — keep this low; use PgBouncer in front
max_connections            = 200

# Write performance
wal_buffers                = 256MB
checkpoint_completion_target = 0.9
random_page_cost           = 1.1   # treat Virtio SCSI as near-SSD

# Autovacuum — conversations and incoming_messages tables grow fast
autovacuum_vacuum_scale_factor   = 0.01
autovacuum_analyze_scale_factor  = 0.005
autovacuum_max_workers           = 4
```

**Install PgBouncer** on the database server. At default settings, your app opens ~37 simultaneous DB connections (28 PHP-FPM + 9 Horizon workers). PgBouncer multiplexes efficiently and is mandatory before you exceed 100 businesses.

---

## 7. Application Server Configuration Checklist

### PHP-FPM (`/etc/php/8.x/fpm/pool.d/www.conf`)
```ini
pm                   = dynamic
pm.max_children      = 30       ; 30 × ~110 MB = 3.3 GB, safe on 32 GB
pm.start_servers     = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests      = 500      ; recycle workers to prevent memory creep
```

### Critical `.env` changes — must do before go-live

```ini
# Currently defaulting to 'file' — this does not scale past a single server
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Move all queues to Redis (maintenance currently falls back to database)
QUEUE_CONNECTION=redis
AI_MAINTENANCE_QUEUE_CONNECTION=redis

# Give Horizon a safe memory ceiling
HORIZON_MEMORY_LIMIT=1024
```

### nginx (`/etc/nginx/sites-available/safarichat`)
```nginx
worker_processes        auto;       # uses all 16 EPYC cores automatically
worker_connections      2048;

gzip on;
gzip_types text/plain application/json application/javascript text/css;
keepalive_timeout 65;
client_max_body_size 20M;           # document uploads (RAG feature)

fastcgi_read_timeout 120;           # AI webhook endpoints can run >30 s
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
```

### Supervisor (manages Horizon)
```ini
# /etc/supervisor/conf.d/horizon.conf
[program:horizon]
process_name=%(program_name)s
command=php /var/www/safarichat/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/horizon.log
stopwaitsecs=3600     ; let in-flight AI jobs finish gracefully before kill
```

---

## 8. Disk Capacity Planning

| Data type | Growth rate estimate | Time to fill |
|---|---|---|
| conversations table | ~500 bytes/row × 10,000 rows/day | 200+ years |
| incoming_messages + media_data JSON | ~2 KB/row × 50,000 rows/day | ~13 years |
| PostgreSQL WAL logs | ~1 GB/day normal load | Set `wal_keep_size = 2GB` |
| Laravel logs (`storage/logs`) | ~50 MB/day | Rotate daily, keep 14 days |
| Uploaded documents (RAG) | ~10 MB/business × 1,000 businesses | ~10 GB total |

App server disk (238 GB): logs and uploaded files are the risk. Add a logrotate rule for `storage/logs` from day one. If you expect 500+ businesses uploading RAG documents, move uploads to object storage (S3 or equivalent) before the disk becomes a concern.

---

## 9. Summary

| Metric | At default config | With Section 5–7 applied |
|---|---|---|
| Registered business accounts | 10,000 | 50,000+ |
| Concurrent dashboard users | 200–350 | 400–600 |
| Active businesses (AI messaging) | 800–1,200 | 2,000–3,500 |
| AI responses per minute | 160 | 300–360 |
| Webhooks handled per minute | 2,000+ | 2,000+ (queue absorbs all burst) |
| **Hard external ceiling** | **OpenAI Tier 1: 500 RPM** | **OpenAI Tier 3: 10,000 RPM** |

The servers are well-provisioned. You will hit OpenAI's rate limits long before stressing the hardware. The database server with 64 GB RAM and a 478 GB disk will outlast the application server's needs by a wide margin. The three immediate actions that will have the most impact on a smooth launch are: **(1)** switch `SESSION_DRIVER` and `CACHE_DRIVER` to Redis, **(2)** install PgBouncer on the database server, and **(3)** request an OpenAI Tier 2 upgrade.
