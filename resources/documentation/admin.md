I have another crm system named admin panel, with these key information tables

a) clients : Store list of all customers, prospects, leads etc just differentiated by a status column, where 
clients.status
1= prospect, 4=churned, 2=customer, 0=lead, 5=qualified lead, 6=low usage clients
b) tasks and task_clients : store list of all activities performed to a particular client
c) users : Store a shulesoft staff that once engaged with this customer

## Integration Workflow: Admin CRM → SafariChat

### Phase 1: Database Connection Setup

**1.1 Database Configuration**
- Add PostgreSQL connection for admin database in `config/database.php`
- Connection name: `admin_crm`
- Schema: `admin`
- Tables: `clients`, `tasks`, `task_clients`, `users`

**1.2 Environment Variables**
```env
ADMIN_DB_CONNECTION=pgsql
ADMIN_DB_HOST=your_admin_db_host
ADMIN_DB_PORT=5432
ADMIN_DB_DATABASE=your_admin_database
ADMIN_DB_USERNAME=your_admin_username
ADMIN_DB_PASSWORD=your_admin_password
ADMIN_DB_SCHEMA=admin
```

### Phase 2: Data Mapping Strategy

**2.1 Client Status Mapping**
```php
Admin Status → SafariChat Lead Stage
0 (lead) → 'lead'
1 (prospect) → 'prospect'  
2 (customer) → 'customer'
4 (churned) → 'churned'
5 (qualified lead) → 'qualified'
6 (low usage clients) → 'low_usage'
```

**2.2 Table Mapping**
```
admin.clients → business_contacts
- id → external_crm_id
- name → guest_name
- email → guest_email  
- phone → guest_phone
- status → crm_status (mapped)
- created_by → assigned_user_id
- note → notes
- estimated_students → custom_data[estimated_students]
- registration_number → custom_data[registration_number]

admin.tasks → conversations + conversation_history
- client_id → contact_external_id
- activity → message_content
- date + time → timestamp
- user_id → staff_user_id
- priority → priority_level
- action → interaction_type
- next_action → follow_up_notes

admin.users → users (staff mapping)
- id → external_staff_id
- firstname + lastname → name
- email → email
- phone → phone
- role_id → role_mapping
```

### Phase 3: Artisan Commands Implementation

**3.1 Import Commands Structure**
```bash
php artisan admin:import-staff [--limit=100] [--dry-run]
php artisan admin:import-clients [--limit=100] [--status=all] [--dry-run]
php artisan admin:import-tasks [--limit=500] [--client-id=X] [--dry-run]
php artisan admin:sync-full [--batch-size=50] [--dry-run]
```

**3.2 Command Features**
- Batch processing with configurable limits
- Dry-run mode for testing
- Progress bars and detailed logging  
- Error handling with rollback capability
- Duplicate detection and handling
- Data validation before import

### Phase 4: Import Process Flow

**4.1 Pre-Import Validation**
1. Test database connectivity
2. Validate table structures
3. Check for required fields
4. Estimate import size and time

**4.2 Staff Import (admin.users)**
1. Import Shulesoft staff members first
2. Create user accounts in SafariChat
3. Map role permissions appropriately
4. Generate API access if needed

**4.3 Clients Import (admin.clients)**
1. Import clients in batches of 100
2. Create business_contacts records
3. Map status to appropriate lead stages
4. Set up business relationships
5. Import custom fields and metadata

**4.4 Tasks/Conversations Import (admin.tasks)**
1. Import task history for each client
2. Create conversation threads
3. Map activities to message types
4. Preserve timestamps and staff assignments
5. Link follow-up actions and notes

### Phase 5: Data Transformation Logic

**5.1 Contact Processing**
```php
// Phone number standardization
$phone = normalizePhoneNumber($adminClient->phone);
$ownerPhone = normalizePhoneNumber($adminClient->owner_phone);

// Business contact creation
$contact = [
    'external_crm_id' => $adminClient->id,
    'guest_name' => $adminClient->name,
    'guest_email' => $adminClient->email,
    'guest_phone' => $phone,
    'business_id' => $targetBusinessId,
    'crm_status' => mapClientStatus($adminClient->status),
    'imported_from_crm' => true,
    'crm_created_at' => $adminClient->created_at,
    'custom_data' => json_encode([
        'estimated_students' => $adminClient->estimated_students,
        'registration_number' => $adminClient->registration_number,
        'region_id' => $adminClient->region_id,
        'ownership' => $adminClient->ownership,
        'director_info' => [
            'name' => $adminClient->director_name,
            'phone' => $adminClient->director_phone,
            'email' => $adminClient->director_email
        ]
    ])
];
```

**5.2 Conversation Processing**
```php
// Task to conversation mapping
$conversation = [
    'contact_external_id' => $task->client_id,
    'staff_user_id' => mapStaffUser($task->user_id),
    'message_content' => $task->activity,
    'interaction_type' => determineInteractionType($task->action),
    'timestamp' => Carbon::parse($task->date . ' ' . $task->time),
    'priority_level' => mapPriority($task->priority),
    'follow_up_notes' => $task->next_action,
    'status' => $task->status,
    'imported_from_crm' => true
];
```

### Phase 6: Error Handling & Logging

**6.1 Import Logging**
- Success/failure counts per batch
- Detailed error logs with record IDs
- Data quality issues identification
- Performance metrics tracking

**6.2 Rollback Mechanism**  
- Transaction-based imports
- Backup before major imports
- Selective rollback by import session
- Data integrity verification

**6.3 Duplicate Handling**
- Check existing external_crm_id
- Phone/email duplicate detection
- Merge vs. skip strategies
- Conflict resolution rules

### Phase 7: Post-Import Verification

**7.1 Data Integrity Checks**
```bash
php artisan admin:verify-import [--type=clients|tasks|staff]
php artisan admin:import-stats
php artisan admin:fix-orphaned-records
```

**7.2 Verification Process**
1. Count imported vs. source records
2. Validate required field completion
3. Check relationship integrity
4. Verify conversation threading
5. Test contact search and filtering

### Phase 8: Ongoing Synchronization

**8.1 Incremental Updates**
```bash
php artisan admin:sync-recent [--since=yesterday] [--type=all]
```

**8.2 Sync Strategy**
- Track last sync timestamp
- Import only modified records
- Handle deletions appropriately
- Maintain data consistency

### Implementation Files Required

**8.3 Configuration Files**
- `config/admin_crm.php` - Import settings and mappings
- `database/migrations/*_add_admin_import_fields.php` - Schema updates

**8.4 Command Files**
- `app/Console/Commands/AdminImportStaff.php`
- `app/Console/Commands/AdminImportClients.php` 
- `app/Console/Commands/AdminImportTasks.php`
- `app/Console/Commands/AdminSyncFull.php`

**8.5 Service Files**
- `app/Services/AdminCrmIntegrationService.php`
- `app/Services/DataMappingService.php`
- `app/Models/AdminClient.php` (for admin DB connection)
- `app/Models/AdminTask.php`
- `app/Models/AdminUser.php`

### Success Metrics

**8.6 Import Success Criteria**
- 100% staff member import
- 95%+ client import success rate  
- 90%+ task/conversation import rate
- Data integrity: 0 orphaned records
- Performance: <30 minutes for full sync

**8.7 Quality Assurance**
- Random sample verification (10%)
- Business owner spot checks
- Lead stage accuracy validation
- Conversation history completeness

Table Structures are as follows



CREATE TABLE IF NOT EXISTS admin.clients
(
    id integer NOT NULL DEFAULT nextval('admin.clients_id_seq'::regclass),
    name character varying COLLATE pg_catalog."default",
    email character varying COLLATE pg_catalog."default",
    phone character varying COLLATE pg_catalog."default",
    address character varying COLLATE pg_catalog."default",
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    lat character varying COLLATE pg_catalog."default",
    "long" character varying COLLATE pg_catalog."default",
    google_map character varying COLLATE pg_catalog."default",
    username character varying COLLATE pg_catalog."default",
    code character varying COLLATE pg_catalog."default",
    email_verified smallint DEFAULT 0,
    phone_verified smallint DEFAULT 0,
    created_by integer,
    estimated_students integer,
    special_trial_code character varying COLLATE pg_catalog."default",
    status integer,
    price_per_student numeric,
    registration_number character varying COLLATE pg_catalog."default",
    region_id integer,
    ward_id integer,
    invoice_start_date date,
    invoice_end_date date,
    start_usage_date date,
    payment_option character varying COLLATE pg_catalog."default",
    trial smallint,
    owner_phone character varying COLLATE pg_catalog."default",
    owner_email character varying COLLATE pg_catalog."default",
    note text COLLATE pg_catalog."default",
    account_name character varying COLLATE pg_catalog."default",
    data_type_id integer,
    is_new_version smallint DEFAULT 1,
    country_id integer DEFAULT 1,
    internet_banking integer DEFAULT 0,
    type smallint DEFAULT 0,
    user_id integer DEFAULT 2,
    source character varying COLLATE pg_catalog."default",
    ownership admin.school_type DEFAULT 'Private'::admin.school_type,
    project_id integer DEFAULT 1,
    director_phone character varying(20) COLLATE pg_catalog."default",
    director_email character varying(255) COLLATE pg_catalog."default",
    director_name character varying COLLATE pg_catalog."default",
    renewal_date date,
    client_referrer integer NOT NULL DEFAULT 1,
    CONSTRAINT clients_id_primary PRIMARY KEY (id),
    CONSTRAINT clients_username_id UNIQUE (username)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS admin.clients
    OWNER to postgres;

COMMENT ON COLUMN admin.clients.type
    IS '0=prospect, 1=lead, 2=customer';

COMMENT ON COLUMN admin.clients.user_id
    IS 'Every school must have a user, either a relationship manager, or CEO but not blank';

COMMENT ON COLUMN admin.clients.client_referrer
    IS 'User Or organization who referred this client to shulesoft.
1 - Shulesoft itself (Default value)
2 - Amana Bank';
-- Index: id_index

-- DROP INDEX IF EXISTS admin.id_index;

CREATE INDEX IF NOT EXISTS id_index
    ON admin.clients USING btree
    (id ASC NULLS LAST)
    WITH (deduplicate_items=True)
    TABLESPACE pg_default;
-- Index: schema_index

-- DROP INDEX IF EXISTS admin.schema_index;

CREATE INDEX IF NOT EXISTS schema_index
    ON admin.clients USING btree
    (username COLLATE pg_catalog."default" ASC NULLS LAST)
    WITH (deduplicate_items=True)
    TABLESPACE pg_default;

CREATE TABLE IF NOT EXISTS admin.tasks
(
    id integer NOT NULL DEFAULT nextval('admin.tasks_id_seq'::regclass),
    client_id integer,
    activity text COLLATE pg_catalog."default",
    date date,
    "time" text COLLATE pg_catalog."default",
    user_id integer,
    priority integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    action character varying COLLATE pg_catalog."default",
    to_user_id integer,
    task_type_id integer,
    next_action character varying COLLATE pg_catalog."default",
    school_id integer,
    status character varying COLLATE pg_catalog."default" DEFAULT 'new'::character varying,
    start_date timestamp without time zone,
    end_date timestamp without time zone,
    slot_id integer,
    ticket_no character varying COLLATE pg_catalog."default",
    budget numeric,
    remainder smallint DEFAULT 1,
    remainder_date date,
    attachment character varying(255) COLLATE pg_catalog."default",
    attachment_type character varying(100) COLLATE pg_catalog."default",
    client_phone character varying COLLATE pg_catalog."default",
    client_email character varying COLLATE pg_catalog."default",
    client_name character varying COLLATE pg_catalog."default",
    uid integer NOT NULL DEFAULT nextval('admin.tasks_uid_seq'::regclass),
    CONSTRAINT tasks_id_primary PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS admin.tasks
    OWNER to postgres;

CREATE TABLE IF NOT EXISTS admin.tasks_clients
(
    id integer NOT NULL DEFAULT nextval('admin.tasks_clients_id_seq'::regclass),
    task_id integer,
    client_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    status character varying COLLATE pg_catalog."default",
    CONSTRAINT tasks_clients_id_primary PRIMARY KEY (id)
)

-- Table: admin.users

-- DROP TABLE IF EXISTS admin.users;

CREATE TABLE IF NOT EXISTS admin.users
(
    id integer NOT NULL DEFAULT nextval('admin.users_id_seq1'::regclass),
    firstname character varying(30) COLLATE pg_catalog."default",
    middlename character varying(30) COLLATE pg_catalog."default",
    lastname character varying COLLATE pg_catalog."default",
    email character varying(90) COLLATE pg_catalog."default",
    password character varying(300) COLLATE pg_catalog."default",
    role_id integer DEFAULT 2,
    type bigint,
    name character varying COLLATE pg_catalog."default",
    remember_token character varying(100) COLLATE pg_catalog."default",
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    dp character varying(45) COLLATE pg_catalog."default" DEFAULT 'default.png'::character varying,
    phone character varying(20) COLLATE pg_catalog."default",
    town character varying COLLATE pg_catalog."default",
    created_by integer,
    photo character varying COLLATE pg_catalog."default",
    status smallint DEFAULT 1,
    salary numeric,
    about text COLLATE pg_catalog."default",
    skills text COLLATE pg_catalog."default",
    sex character varying COLLATE pg_catalog."default",
    marital character varying COLLATE pg_catalog."default",
    date_of_birth date,
    personal_email character varying COLLATE pg_catalog."default",
    tshirt_size character varying COLLATE pg_catalog."default",
    joining_date date,
    contract_end_date date,
    academic_certificates text COLLATE pg_catalog."default",
    medical_report character varying COLLATE pg_catalog."default",
    driving_license character varying COLLATE pg_catalog."default",
    valid_passport character varying COLLATE pg_catalog."default",
    next_kin character varying COLLATE pg_catalog."default",
    national_id character varying COLLATE pg_catalog."default",
    employment_category character varying COLLATE pg_catalog."default",
    address character varying COLLATE pg_catalog."default",
    department integer,
    qr_code character varying COLLATE pg_catalog."default",
    applicant_id integer,
    bank_name character varying COLLATE pg_catalog."default",
    bank_account character varying COLLATE pg_catalog."default",
    company_file_id integer,
    designation_id integer,
    employment_contract character varying COLLATE pg_catalog."default",
    sid integer DEFAULT nextval('unique_identifier_seq'::regclass),
    cv character varying COLLATE pg_catalog."default",
    deleted_at timestamp without time zone,
    signature character varying COLLATE pg_catalog."default",
    signature_path character varying COLLATE pg_catalog."default",
    contract_start_date date,
    experience integer,
    performance text COLLATE pg_catalog."default",
    emergency_contact character varying(255) COLLATE pg_catalog."default",
    emergency_contact_number character varying(255) COLLATE pg_catalog."default",
    identity_document character varying(255) COLLATE pg_catalog."default",
    medical_certificate character varying(255) COLLATE pg_catalog."default",
    passport_photo character varying(255) COLLATE pg_catalog."default",
    birth_certificate character varying(255) COLLATE pg_catalog."default",
    reference_letter character varying(255) COLLATE pg_catalog."default",
    address_proof character varying(255) COLLATE pg_catalog."default",
    next_kin_info text COLLATE pg_catalog."default",
    has_desk character varying(3) COLLATE pg_catalog."default",
    has_chair character varying(3) COLLATE pg_catalog."default",
    introduced_to_staff character varying(3) COLLATE pg_catalog."default",
    introduced_to_clients character varying(3) COLLATE pg_catalog."default",
    nda_signed character varying(3) COLLATE pg_catalog."default",
    contract_signed character varying(3) COLLATE pg_catalog."default",
    hr_policy_received character varying(3) COLLATE pg_catalog."default",
    shulesoft_trained character varying(3) COLLATE pg_catalog."default",
    reading_materials_received character varying(3) COLLATE pg_catalog."default",
    email_setup character varying(3) COLLATE pg_catalog."default",
    admin_panel_access character varying(3) COLLATE pg_catalog."default",
    pc_setup character varying(3) COLLATE pg_catalog."default",
    phone_setup character varying(3) COLLATE pg_catalog."default",
    id_card_issued character varying(3) COLLATE pg_catalog."default",
    business_cards character varying(3) COLLATE pg_catalog."default",
    desk_name_plate character varying(3) COLLATE pg_catalog."default",
    customer_list character varying(3) COLLATE pg_catalog."default",
    partner_list character varying(3) COLLATE pg_catalog."default",
    products_shared character varying(3) COLLATE pg_catalog."default",
    external_software_access character varying(3) COLLATE pg_catalog."default",
    nssf character varying COLLATE pg_catalog."default",
    wcf character varying COLLATE pg_catalog."default",
    paye character varying COLLATE pg_catalog."default",
    nhif character varying COLLATE pg_catalog."default",
    helsb character varying COLLATE pg_catalog."default",
    professional_body character varying COLLATE pg_catalog."default",
    asset_allocation character varying COLLATE pg_catalog."default",
    contract_status character varying COLLATE pg_catalog."default",
    contract_comments text COLLATE pg_catalog."default",
    company_email character varying COLLATE pg_catalog."default",
    CONSTRAINT users_id_primary PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS admin.users
    OWNER to postgres;