# Database Migration: Event-Based to Business-Based Logic

## Overview
This document outlines the database schema changes required to migrate from event-based logic to business-centric logic for the WhatsApp business communication platform.

## Database Schema Changes Required

### 1. Table Structure Updates

#### events_guests table
```sql
-- Add business_id column
ALTER TABLE events_guests ADD COLUMN business_id INT;

-- Add foreign key constraint
ALTER TABLE events_guests ADD CONSTRAINT fk_events_guests_business 
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

-- Create index for performance
CREATE INDEX idx_events_guests_business_id ON events_guests(business_id);

-- Eventually drop event_id column (after data migration)
-- ALTER TABLE events_guests DROP COLUMN event_id;
```

#### event_guest_categories table
```sql
-- Add business_id column
ALTER TABLE event_guest_categories ADD COLUMN business_id INT;

-- Add foreign key constraint
ALTER TABLE event_guest_categories ADD CONSTRAINT fk_event_guest_categories_business 
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

-- Create index for performance
CREATE INDEX idx_event_guest_categories_business_id ON event_guest_categories(business_id);

-- Eventually drop event_id column (after data migration)
-- ALTER TABLE event_guest_categories DROP COLUMN event_id;
```

#### budgets table
**REMOVED** - Budget tables have been completely removed as they were not being used.
- Dropped `budgets` table
- Dropped `budget_payments` table  
- Removed Budget and BudgetPayment models
- Updated references in controllers and views

### 2. Data Migration Scripts

#### Step 1: Map users to their businesses
```sql
-- Create business records for users who don't have them
INSERT INTO businesses (user_id, name, address, descriptions, ward_id, created_at, updated_at)
SELECT 
    u.id,
    CONCAT(u.name, ' Business') as name,
    COALESCE(u.address, 'Default Address') as address,
    'Auto-created during migration' as descriptions,
    1 as ward_id, -- Default ward
    NOW() as created_at,
    NOW() as updated_at
FROM users u 
LEFT JOIN businesses b ON b.user_id = u.id
WHERE b.id IS NULL;
```

#### Step 2: Migrate events_guests data
```sql
-- Update events_guests with business_id based on user association
UPDATE events_guests eg
JOIN events e ON eg.event_id = e.id
JOIN users_events ue ON ue.event_id = e.id
JOIN businesses b ON b.user_id = ue.user_id
SET eg.business_id = b.id
WHERE eg.business_id IS NULL;

-- Handle orphaned records (assign to first available business)
UPDATE events_guests 
SET business_id = (SELECT MIN(id) FROM businesses LIMIT 1)
WHERE business_id IS NULL;
```

#### Step 3: Migrate event_guest_categories data
```sql
-- Update event_guest_categories with business_id
UPDATE event_guest_categories egc
JOIN events e ON egc.event_id = e.id
JOIN users_events ue ON ue.event_id = e.id
JOIN businesses b ON b.user_id = ue.user_id
SET egc.business_id = b.id
WHERE egc.business_id IS NULL;

-- Handle orphaned records
UPDATE event_guest_categories 
SET business_id = (SELECT MIN(id) FROM businesses LIMIT 1)
WHERE business_id IS NULL;
```

### 3. Verification Queries

```sql
-- Verify all records have business_id
SELECT 'events_guests' as table_name, COUNT(*) as total_records, 
       COUNT(business_id) as with_business_id,
       COUNT(*) - COUNT(business_id) as missing_business_id
FROM events_guests
UNION ALL
SELECT 'event_guest_categories', COUNT(*), COUNT(business_id), COUNT(*) - COUNT(business_id)
FROM event_guest_categories;
```

### 4. Cleanup (Execute after verification)

```sql
-- Make business_id NOT NULL
ALTER TABLE events_guests MODIFY business_id INT NOT NULL;
ALTER TABLE event_guest_categories MODIFY business_id INT NOT NULL;
ALTER TABLE budgets MODIFY business_id INT NOT NULL;

-- Drop old event_id columns
ALTER TABLE events_guests DROP COLUMN event_id;
ALTER TABLE event_guest_categories DROP COLUMN event_id;
ALTER TABLE budgets DROP COLUMN event_id;
```

## Code Changes Summary

### Models Updated
- ✅ **EventsGuest**: Changed `event_id` to `business_id`, updated relationships and methods
- ✅ **EventGuestCategory**: Changed `event_id` to `business_id`, updated relationships  
- ✅ **Budget**: **REMOVED** - Budget functionality completely removed as unused
- ✅ **Business**: Added new relationships for guests and categories

### Controllers Updated
- ✅ **Home**: Updated dashboard logic to use business instead of events
- ✅ **Home::settings()**: Modified to handle business settings instead of event settings

### Views Updated
- ✅ **settings.blade.php**: 
  - Removed event settings tab
  - Added business settings tab
  - Updated customer categories to use business relationship
  - Updated forms to submit business data

### Key Method Changes
- ✅ `EventsGuest::findOrCreateForNotification()` - Now uses business lookup
- ✅ `EventsGuest::findOrCreateWithResolution()` - Uses business_id parameter
- ✅ `EventsGuest::resolveBusinessForContact()` - Replaces event resolution with business resolution
- ✅ `EventsGuest::bulkCreateOrUpdate()` - Updated to use business_id
- ✅ `EventsGuest::scopeForBusiness()` - Replaces scopeForEvent

## Migration Checklist

- [x] Update model fillable arrays and relationships
- [x] Update controller logic to use business instead of events
- [x] Update view templates to remove event references
- [x] Update settings page to include business management
- [ ] Execute database migration scripts
- [ ] Test all functionality with new business-centric logic
- [ ] Update API endpoints to use business_id instead of event_id
- [ ] Update any remaining views that reference events
- [ ] Remove unused event-related code and files

## Benefits of Migration

1. **Better Alignment**: The platform is now aligned with its core purpose as a WhatsApp business communication tool
2. **Simplified Logic**: Removed complex event management that wasn't being used
3. **Improved User Experience**: Users now manage business information directly rather than through event proxies
4. **Scalability**: Business-centric approach allows for better feature expansion
5. **Clearer Data Model**: Business relationships are more intuitive than event relationships for this use case

## Notes

- All existing event-related data will be preserved during migration
- The migration maps event ownership through user relationships to business ownership
- After successful migration and testing, event-related tables can be optionally removed
- The settings page now provides a foundation for comprehensive business management features