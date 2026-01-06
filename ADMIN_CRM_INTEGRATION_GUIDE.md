# Admin CRM Integration Implementation Guide

## Overview

This document outlines the complete implementation of the Admin CRM integration system for SafariChat. The integration allows importing staff, clients, and task history from the external Admin PostgreSQL database.

## Architecture

### Database Structure

**Admin CRM (PostgreSQL):**
- `admin.users` → Staff members with roles and departments
- `admin.clients` → Business clients with status and contact information  
- `admin.tasks` → Task/interaction history with client relationships

**SafariChat (MySQL/PostgreSQL):**
- `users` → Staff members (enhanced with CRM fields)
- `business_contacts` → Client contacts (enhanced with lead management)
- `conversations` → Task history as conversation records

### Key Components

1. **Configuration** - `config/admin_crm.php`
2. **Models** - `app/Models/AdminCrm/` directory
3. **Services** - Integration and data mapping services
4. **Commands** - Artisan commands for import operations
5. **Migration** - Database schema changes for CRM tracking

## Data Mapping

### Staff Mapping (admin.users → users)
```php
admin.users.id → users.external_staff_id
admin.users.display_name → users.name
admin.users.email → users.email
admin.users.phone → users.phone (normalized)
admin.users.role_id → users.admin_role (mapped)
```

### Client Mapping (admin.clients → business_contacts)
```php
admin.clients.id → business_contacts.external_crm_id
admin.clients.name → business_contacts.company_name
admin.clients.contact_name → business_contacts.guest_name
admin.clients.email → business_contacts.guest_email
admin.clients.phone → business_contacts.guest_phone (normalized)
admin.clients.status → business_contacts.lead_stage (mapped)
```

### Task Mapping (admin.tasks → conversations)
```php
admin.tasks.id → conversations.external_task_id
admin.tasks.client_id → conversations.contact_id (mapped)
admin.tasks.action → conversations.message_content
admin.tasks.user_id → conversations.staff_user_id (mapped)
admin.tasks.date/time → conversations.timestamp
```

### Status Mappings
```php
Admin Status → SafariChat Lead Stage
0 (lead) → new_lead
1 (prospect) → qualified  
2 (customer) → customer
4 (churned) → lost
5 (qualified lead) → qualified
6 (low usage) → nurturing
```

## Implementation Steps

### 1. Database Setup

**Run Migration:**
```bash
php artisan migrate
```

This adds:
- CRM tracking fields to existing tables
- New tables for import logging and sync status
- Indexes for query optimization

### 2. Configuration Setup

The system uses `config/admin_crm.php` for:
- Database connection settings
- Field mappings
- Status mappings  
- Validation rules
- Import parameters

### 3. Service Architecture

**AdminCrmIntegrationService** - Main orchestration service
- Connection testing
- Batch import operations
- Error handling and logging
- Import statistics

**DataMappingService** - Data transformation service  
- Field mapping and validation
- Phone number normalization
- Status mapping
- Data integrity checks

### 4. Models Setup

**AdminCrm Models** (read-only):
- `AdminClient` - Client data access
- `AdminTask` - Task data access
- `AdminUser` - Staff data access
- `AdminTaskClient` - Task-client relationships

These models use the `admin_crm` database connection and provide:
- Eloquent relationships
- Data transformation methods
- Status mapping attributes

## Usage Instructions

### Command Overview

1. **Staff Import**
```bash
php artisan admin:import-staff [options]
```

2. **Client Import**  
```bash
php artisan admin:import-clients [options]
```

3. **Task Import**
```bash
php artisan admin:import-tasks [options]
```

4. **Full Synchronization**
```bash
php artisan admin:sync-full [options]
```

### Common Options

- `--dry-run` - Preview import without saving changes
- `--limit=N` - Number of records to process (batch size)
- `--offset=N` - Starting offset for import
- `--force` - Proceed despite validation errors

### Import Workflow

**Recommended Order:**
1. Import staff first (creates user mappings)
2. Import clients (creates contact mappings)
3. Import tasks (requires client mappings)

**Example Full Import:**
```bash
# 1. Test connection and preview
php artisan admin:sync-full --dry-run

# 2. Import staff
php artisan admin:import-staff --limit=100

# 3. Import clients 
php artisan admin:import-clients --limit=50 --with-tasks

# 4. Or full sync everything
php artisan admin:sync-full --batch-size=50
```

### Filtering Options

**Client Import Filters:**
```bash
# Import specific status
php artisan admin:import-clients --status=1

# Import with task history
php artisan admin:import-clients --with-tasks
```

**Task Import Filters:**
```bash
# Import for specific client
php artisan admin:import-tasks --client-id=123

# Import date range
php artisan admin:import-tasks --date-from=2024-01-01 --date-to=2024-12-31

# Import by staff member
php artisan admin:import-tasks --user-id=5
```

## Data Integrity Features

### Duplicate Prevention
- Uses external ID fields to prevent duplicates
- Configurable duplicate handling strategies
- Skip or update existing records

### Validation Rules
- Required field validation
- Email format validation
- Phone number format validation
- Business rule validation

### Error Handling
- Detailed error logging
- Batch processing with rollback
- Validation error reporting
- Import statistics tracking

### Import Tracking
- `admin_crm_import_log` table logs all imports
- `admin_crm_sync_status` table tracks sync state
- Performance metrics and timing
- Error details and resolution

## Monitoring and Maintenance

### Import Logging
Every import operation is logged with:
- Records processed/imported/skipped/errors
- Import parameters and filters
- Execution time and performance
- Error details and stack traces

### Sync Status Tracking
- Last successful sync timestamps
- Auto-sync configuration options
- Scheduled sync planning
- Sync metadata and parameters

### Data Verification
The system includes verification methods:
- Staff mapping verification
- Client mapping verification  
- Task relationship verification
- Data integrity checks

### Performance Optimization
- Batch processing for large datasets
- Database indexes for query optimization
- Memory-efficient data streaming
- Connection pooling and reuse

## Troubleshooting

### Common Issues

**Connection Problems:**
```bash
# Test database connection
php artisan admin:sync-full --dry-run
```

**Phone Format Issues:**
- System normalizes phone numbers automatically
- Configurable country code defaults
- Handles local and international formats

**Missing Relationships:**
- Import staff before clients
- Import clients before tasks
- Use `--force` for partial imports

**Memory Issues:**
- Reduce `--limit` batch size
- Process in smaller chunks
- Monitor memory usage

### Error Resolution

**Validation Errors:**
- Review error details in command output
- Check required fields in Admin CRM
- Verify data format compliance

**Import Failures:**
- Check database connections
- Verify permissions
- Review error logs
- Use `--dry-run` to test

**Data Inconsistencies:**
- Run verification commands
- Check import logs
- Review mapping configurations
- Re-import with corrections

## Security Considerations

### Database Security
- Read-only access to Admin CRM database
- Separate connection credentials
- Network security for PostgreSQL connection

### Data Privacy
- Sensitive data handling
- User consent requirements
- Data retention policies
- GDPR compliance considerations

### Access Control
- Admin-only command access
- User permission verification
- Import operation logging
- Audit trail maintenance

## Future Enhancements

### Automatic Synchronization
- Scheduled sync jobs
- Real-time sync triggers
- Change detection and delta sync
- Conflict resolution strategies

### Advanced Mapping
- Custom field mapping configuration
- Business rule customization
- Advanced data transformations
- Multi-source data aggregation

### Reporting and Analytics
- Import success/failure dashboards
- Data quality metrics
- Business intelligence integration
- Performance analytics

### API Integration
- RESTful API for external access
- Webhook notifications
- Real-time data streaming
- Integration with third-party systems

## Configuration Reference

### Database Connection
```php
'admin_crm' => [
    'driver' => 'pgsql',
    'host' => env('ADMIN_CRM_DB_HOST'),
    'database' => env('ADMIN_CRM_DB_DATABASE'),
    'username' => env('ADMIN_CRM_DB_USERNAME'),
    'password' => env('ADMIN_CRM_DB_PASSWORD'),
    'schema' => 'admin'
]
```

### Environment Variables
```env
ADMIN_CRM_DB_HOST=your_admin_db_host
ADMIN_CRM_DB_DATABASE=your_admin_db_name
ADMIN_CRM_DB_USERNAME=your_admin_db_user
ADMIN_CRM_DB_PASSWORD=your_admin_db_password
```

### Field Mapping Configuration
Review `config/admin_crm.php` for:
- Field mapping definitions
- Status mapping rules
- Validation configuration
- Import default settings

This comprehensive integration provides a robust foundation for synchronizing Admin CRM data with SafariChat, maintaining data integrity, and supporting ongoing business operations.