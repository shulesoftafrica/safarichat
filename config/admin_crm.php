<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin CRM Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for importing data from the external admin CRM
    | system into SafariChat. This includes database connections, field
    | mappings, and import behavior settings.
    |
    */

    'database' => [
        'connection' => 'admin_crm',
        'schema' => 'admin',
        'tables' => [
            'clients' => 'admin.clients',
            'tasks' => 'admin.tasks', 
            'task_clients' => 'admin.task_clients',
            'users' => 'admin.users'
        ]
    ],

    'import_settings' => [
        'batch_size' => env('ADMIN_IMPORT_BATCH_SIZE', 100),
        'max_execution_time' => env('ADMIN_IMPORT_MAX_TIME', 3600), // 1 hour
        'enable_logging' => env('ADMIN_IMPORT_LOGGING', true),
        'auto_assign_business' => env('ADMIN_AUTO_ASSIGN_BUSINESS', 1), // Default business ID
    ],

    'field_mappings' => [
        'clients' => [
            // Direct field mappings
            'id' => 'external_crm_id',
            'name' => 'guest_name',
            'email' => 'guest_email',
            'phone' => 'guest_phone',
            'address' => 'address',
            'created_at' => 'crm_created_at',
            'updated_at' => 'crm_updated_at',
            'created_by' => 'assigned_user_id',
            'note' => 'notes',
            
            // Custom data fields (stored as JSON)
            'custom_fields' => [
                'estimated_students',
                'registration_number',
                'region_id',
                'ward_id',
                'price_per_student',
                'payment_option',
                'owner_phone',
                'owner_email',
                'director_name',
                'director_phone', 
                'director_email',
                'renewal_date',
                'invoice_start_date',
                'invoice_end_date',
                'ownership',
                'project_id'
            ]
        ],

        'tasks' => [
            'id' => 'external_task_id',
            'client_id' => 'contact_external_id', 
            'activity' => 'message_content',
            'date' => 'interaction_date',
            'time' => 'interaction_time',
            'user_id' => 'staff_user_id',
            'priority' => 'priority_level',
            'action' => 'interaction_type',
            'next_action' => 'follow_up_notes',
            'status' => 'task_status',
            'created_at' => 'created_at',
            'budget' => 'estimated_value'
        ],

        'users' => [
            'firstname' => 'first_name',
            'lastname' => 'last_name', 
            'email' => 'email',
            'phone' => 'phone',
            'role_id' => 'admin_role_id',
            'created_at' => 'created_at'
        ]
    ],

    'status_mappings' => [
        'clients' => [
            0 => 'lead',           // lead
            1 => 'prospect',       // prospect 
            2 => 'customer',       // customer
            4 => 'churned',        // churned
            5 => 'qualified',      // qualified lead
            6 => 'low_usage'       // low usage clients
        ],
        
        'priorities' => [
            1 => 'low',
            2 => 'medium', 
            3 => 'high',
            4 => 'urgent'
        ],

        'task_status' => [
            'new' => 'pending',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'on_hold' => 'paused'
        ]
    ],

    'validation_rules' => [
        'clients' => [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|integer|in:0,1,2,4,5,6'
        ],
        
        'tasks' => [
            'client_id' => 'required|integer',
            'activity' => 'required|string',
            'date' => 'required|date',
            'user_id' => 'required|integer'
        ],

        'users' => [
            'firstname' => 'required|string|max:30',
            'lastname' => 'required|string|max:30', 
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|integer'
        ]
    ],

    'duplicate_handling' => [
        'clients' => [
            'strategy' => 'skip', // skip, update, merge
            'match_fields' => ['phone', 'email'],
            'update_existing' => false
        ],
        
        'tasks' => [
            'strategy' => 'skip',
            'match_fields' => ['external_task_id'],
            'update_existing' => false  
        ],

        'users' => [
            'strategy' => 'skip',
            'match_fields' => ['email'],
            'update_existing' => true
        ]
    ],

    'phone_normalization' => [
        'default_country_code' => '+255', // Tanzania
        'remove_prefixes' => ['0'], // Remove leading 0
        'format' => 'international' // international, local, e164
    ],

    'import_order' => [
        'users',    // Import staff first
        'clients',  // Then clients/contacts
        'tasks'     // Finally tasks/conversations
    ],

    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channels' => ['daily', 'admin_import'],
        'include_data' => env('ADMIN_IMPORT_LOG_DATA', false)
    ]
];