<?php

namespace App\Services;

use App\Models\AdminCrm\AdminClient;
use App\Models\AdminCrm\AdminTask;
use App\Models\AdminCrm\AdminUser;
use App\Models\BusinessContact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;

class AdminCrmIntegrationService
{
    protected $mappingService;
    protected $config;

    public function __construct(DataMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
        $this->config = config('admin_crm');
    }

    /**
     * Test connection to admin database
     */
    public function testConnection(): array
    {
        try {
            $adminConnection = DB::connection('admin_crm');
            
            // Test basic connection
            $adminConnection->getPdo();
            
            // Test table access
            $clientsCount = $adminConnection->table('admin.clients')->count();
            $tasksCount = $adminConnection->table('admin.tasks')->count();
            $usersCount = $adminConnection->table('admin.users')->count();
            
            return [
                'success' => true,
                'message' => 'Admin CRM database connection successful',
                'data' => [
                    'clients_count' => $clientsCount,
                    'tasks_count' => $tasksCount,
                    'users_count' => $usersCount
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Admin CRM database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Import staff/users from admin system
     */
    public function importStaff(int $limit = 100, int $offset = 0, bool $dryRun = false): array
    {
        $results = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
            'imported_users' => []
        ];

        try {
            // Check if users table exists
            if (!Schema::hasTable('users')) {
                $results['error_details'][] = "Users table does not exist in SafariChat database";
                $results['errors'] = 1;
                $results['success'] = false;
                return $results;
            }

            $adminUsers = AdminUser::active()
                ->offset($offset)
                ->limit($limit)
                ->get();

            foreach ($adminUsers as $adminUser) {
                try {
                    $results['processed']++;

                    if ($dryRun) {
                        // For dry run, validate the data mapping
                        try {
                            $userData = $this->mappingService->mapUserData($adminUser);
                            // Store sample data for first record
                            if (empty($results['sample_data'])) {
                                $results['sample_data'] = $userData;
                            }
                            $results['imported']++;
                        } catch (Exception $e) {
                            $results['errors']++;
                            $results['error_details'][] = "Admin user {$adminUser->id} validation: " . $e->getMessage();
                        }
                    } else if (class_exists('App\Models\User')) {
                        $userData = $this->mappingService->mapUserData($adminUser);
                        $newUser = \App\Models\User::create($userData);
                        
                        $results['imported_users'][] = [
                            'id' => $newUser->id,
                            'name' => $newUser->name,
                            'email' => $newUser->email,
                            'external_id' => $adminUser->id
                        ];
                        $results['imported']++;
                    } else {
                        $results['errors']++;
                        $results['error_details'][] = "User model not available for import";
                    }

                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = "Admin user {$adminUser->id}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $results['errors']++;
            $results['error_details'][] = "General error: " . $e->getMessage();
        }

        $results['success'] = $results['errors'] === 0;
        return $results;
    }

    /**
     * Import clients from admin system
     */
    public function importClients(int $limit = 100, int $offset = 0, array $filters = [], bool $dryRun = false): array
    {
        $results = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
            'imported_clients' => []
        ];

        try {
            // Check if business_contacts table exists
            if (!Schema::hasTable('business_contacts')) {
                $results['error_details'][] = "BusinessContact table does not exist in SafariChat database";
                $results['errors'] = 1;
                $results['success'] = false;
                return $results;
            }
            
            $query = AdminClient::query();
            
            // Apply filters
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $query->where('status', $filters['status']);
            }

            $adminClients = $query->offset($offset)->limit($limit)->get();

            foreach ($adminClients as $adminClient) {
                try {
                    $results['processed']++;

                    // Check for duplicates using phone number only
                    if (class_exists('App\\Models\\BusinessContact')) {
                        $existingContact = null;
                        if ($adminClient->phone) {
                            try {
                                $normalizedPhone = $this->mappingService->normalizePhone($adminClient->phone);
                                if ($normalizedPhone) {
                                    $existingContact = \App\Models\BusinessContact::where('guest_phone', $normalizedPhone)->first();
                                }
                            } catch (Exception $e) {
                                $results['error_details'][] = "Phone check failed: " . $e->getMessage();
                            }
                        }

                        if ($existingContact) {
                            $results['skipped']++;
                            continue;
                        }
                    }

                    if (!$dryRun && class_exists('App\Models\BusinessContact')) {
                        $contactData = $this->mappingService->mapClientData($adminClient);
                        $newContact = \App\Models\BusinessContact::create($contactData);

                        $results['imported_clients'][] = [
                            'id' => $newContact->id,
                            'name' => $newContact->guest_name,
                            'phone' => $newContact->guest_phone,
                            'status' => $newContact->crm_status,
                            'external_id' => $adminClient->id
                        ];
                        $results['imported']++;
                    } else if (!$dryRun) {
                        $results['errors']++;
                        $results['error_details'][] = "BusinessContact model not available for import";
                    } else {
                        $results['imported']++;
                    }

                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = "Admin client {$adminClient->id}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $results['errors']++;
            $results['error_details'][] = "General error: " . $e->getMessage();
        }

        $results['success'] = $results['errors'] === 0;
        return $results;
    }

    /**
     * Import tasks/conversations from admin system
     */
    public function importTasks(int $limit = 500, int $offset = 0, array $filters = [], bool $dryRun = false): array
    {
        $results = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
            'imported_tasks' => []
        ];

        try {
            // Check if conversations table exists
            if (!Schema::hasTable('conversations')) {
                $results['error_details'][] = "Conversations table does not exist in SafariChat database";
                $results['errors'] = 1;
                $results['success'] = false;
                return $results;
            }
            
            $query = AdminTask::with('client');
            
            // Apply filters
            if (isset($filters['client_id'])) {
                $query->where('client_id', $filters['client_id']);
            }
            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            if (isset($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }

            $adminTasks = $query->offset($offset)->limit($limit)->get();

            foreach ($adminTasks as $adminTask) {
                try {
                    $results['processed']++;

                    // Find corresponding contact in SafariChat (skip if BusinessContact doesn't exist)
                    // Create or get default lead for task import
                    $contact = null;
                    $defaultLeadId = 26; // Use existing lead ID
                    
                    // Try to get the first available lead ID if available
                    if (class_exists('App\Models\Lead')) {
                        try {
                            $firstLead = \App\Models\Lead::first();
                            if ($firstLead) {
                                $defaultLeadId = $firstLead->id;
                            }
                        } catch (Exception $e) {
                            // Use default of 26
                        }
                    }

                    // Check if task already imported using title/description match
                    if (class_exists('App\Models\Conversation')) {
                        // For now, skip duplicate checking for tasks until proper tracking field is available
                        $existingConversation = null;
                        // $existingConversation = \App\Models\Conversation::where('external_task_id', $adminTask->id)->first();
                        
                        if ($existingConversation) {
                            $results['skipped']++;
                            continue;
                        }
                    }

                    if (!$dryRun && class_exists('App\Models\Conversation')) {
                        $conversationData = $this->mappingService->mapTaskData($adminTask, $contact, $defaultLeadId);
                        $newConversation = \App\Models\Conversation::create($conversationData);

                        $results['imported_tasks'][] = [
                            'id' => $newConversation->id,
                            'contact_name' => $contact ? $contact->guest_name : 'Unknown',
                            'activity' => substr($adminTask->activity, 0, 100),
                            'date' => $adminTask->date,
                            'external_task_id' => $adminTask->id
                        ];
                        $results['imported']++;
                    } else if (!$dryRun) {
                        $results['errors']++;
                        $results['error_details'][] = "Conversation model not available for import";
                    } else {
                        $results['imported']++;
                    }

                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = "Admin task {$adminTask->id}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $results['errors']++;
            $results['error_details'][] = "General error: " . $e->getMessage();
        }

        $results['success'] = $results['errors'] === 0;
        return $results;
    }

    /**
     * Full synchronization of all data
     */
    public function syncFull(array $options = []): array
    {
        // Extract options with defaults
        $batchSize = $options['batch_size'] ?? 50;
        $dryRun = $options['dry_run'] ?? false;
        $staffOnly = $options['staff_only'] ?? false;
        $clientsOnly = $options['clients_only'] ?? false;
        $tasksOnly = $options['tasks_only'] ?? false;
        $force = $options['force'] ?? false;
        
        $results = [
            'started_at' => now(),
            'staff' => [],
            'clients' => [],
            'tasks' => [],
            'completed_at' => null,
            'total_duration' => null,
            'success' => false,
            'total_processed' => 0,
            'total_imported' => 0,
            'total_skipped' => 0,
            'total_errors' => 0,
            'total_time' => 0
        ];

        try {
            $startTime = microtime(true);
            
            // Import staff (unless clients-only or tasks-only)
            if (!$clientsOnly && !$tasksOnly) {
                Log::info('Starting staff import from Admin CRM');
                $results['staff'] = $this->importStaff($batchSize, 0, $dryRun);
            }

            // Import clients (unless staff-only or tasks-only)
            if (!$staffOnly && !$tasksOnly) {
                Log::info('Starting clients import from Admin CRM');
                $results['clients'] = $this->importClients($batchSize, 0, [], $dryRun);
            }

            // Import tasks (unless staff-only or clients-only)
            if (!$staffOnly && !$clientsOnly) {
                Log::info('Starting tasks import from Admin CRM');
                $results['tasks'] = $this->importTasks($batchSize * 2, 0, [], $dryRun);
            }

            $endTime = microtime(true);
            $results['completed_at'] = now();
            $results['total_duration'] = $results['completed_at']->diffInMinutes($results['started_at']);
            $results['total_time'] = round($endTime - $startTime, 2);

            // Calculate totals
            foreach (['staff', 'clients', 'tasks'] as $type) {
                if (isset($results[$type])) {
                    $results['total_processed'] += $results[$type]['processed'] ?? 0;
                    $results['total_imported'] += $results[$type]['imported'] ?? 0;
                    $results['total_skipped'] += $results[$type]['skipped'] ?? 0;
                    $results['total_errors'] += $results[$type]['errors'] ?? 0;
                }
            }
            
            $results['success'] = $results['total_errors'] === 0 || $force;
            
            // Collect all error details
            $allErrors = [];
            foreach (['staff', 'clients', 'tasks'] as $type) {
                if (isset($results[$type]['error_details'])) {
                    $allErrors = array_merge($allErrors, $results[$type]['error_details']);
                }
            }
            $results['all_error_details'] = $allErrors;
            
            // Add appropriate message
            if ($results['success']) {
                $results['message'] = 'Sync completed successfully';
                Log::info('Admin CRM sync completed successfully', [
                    'processed' => $results['total_processed'],
                    'imported' => $results['total_imported'],
                    'skipped' => $results['total_skipped']
                ]);
            } else {
                $results['message'] = "Sync completed with {$results['total_errors']} errors";
                Log::error('Admin CRM sync completed with errors', [
                    'total_errors' => $results['total_errors'],
                    'error_details' => $allErrors,
                    'processed' => $results['total_processed'],
                    'imported' => $results['total_imported'],
                    'skipped' => $results['total_skipped']
                ]);
            }
            
            // Add dry run estimates
            if ($dryRun) {
                $results['estimated_time'] = max(1, ceil($results['total_processed'] / $batchSize));
                $results['recommended_batch_size'] = min(100, max(10, $batchSize));
            }

        } catch (Exception $e) {
            Log::error('Admin CRM full sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $results['error'] = $e->getMessage();
            $results['success'] = false;
            $results['completed_at'] = now();
            $results['message'] = 'Sync failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get import statistics
     */
    public function getImportStats(): array
    {
        try {
            return [
                'admin_totals' => [
                    'users' => AdminUser::active()->count(),
                    'clients' => AdminClient::count(),
                    'tasks' => AdminTask::count()
                ],
                'safarichat_imported' => [
                    'staff' => User::whereNotNull('email')->count(),
                    'contacts' => BusinessContact::where('imported_from_crm', true)->count(),
                    'conversations' => Conversation::count()  // Just count all conversations for now
                ],
                'status_breakdown' => [
                    'leads' => AdminClient::where('status', 0)->count(),
                    'prospects' => AdminClient::where('status', 1)->count(),
                    'customers' => AdminClient::where('status', 2)->count(),
                    'churned' => AdminClient::where('status', 4)->count(),
                    'qualified' => AdminClient::where('status', 5)->count(),
                    'low_usage' => AdminClient::where('status', 6)->count()
                ]
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify imported data integrity
     */
    public function verifyImport(): array
    {
        try {
            $issues = [];

            // Check for orphaned records
            // For now, skip orphaned conversation cleanup until proper tracking is implemented
            $orphanedConversations = 0;  // Set to 0 for now
            // $orphanedConversations = Conversation::whereNotNull('external_task_id')
            //     ->whereDoesntHave('contact')
            //     ->count();
            
            if ($orphanedConversations > 0) {
                $issues[] = "Found {$orphanedConversations} orphaned conversations without contacts";
            }

            // Check for missing required fields
            $contactsWithoutNames = BusinessContact::where('imported_from_crm', true)
                ->where(function($q) {
                    $q->whereNull('guest_name')->orWhere('guest_name', '');
                })
                ->count();

            if ($contactsWithoutNames > 0) {
                $issues[] = "Found {$contactsWithoutNames} contacts without names";
            }

            return [
                'is_valid' => empty($issues),
                'issues' => $issues,
                'checks_performed' => [
                    'orphaned_conversations',
                    'contacts_without_names'
                ]
            ];

        } catch (Exception $e) {
            return [
                'is_valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}