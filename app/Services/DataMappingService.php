<?php

namespace App\Services;

use App\Models\AdminCrm\AdminClient;
use App\Models\AdminCrm\AdminTask;
use App\Models\AdminCrm\AdminUser;
use App\Models\BusinessContact;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DataMappingService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('admin_crm');
    }

    /**
     * Map admin user data to SafariChat user format
     */
    public function mapUserData(AdminUser $adminUser): array
    {
        return [
            'name' => $adminUser->display_name,
            'email' => $adminUser->email,
            'phone' => $this->normalizePhone($adminUser->phone),
            'password' => Hash::make('temp_password_' . rand(1000, 9999)), // Temporary password
            'user_type_id' => 1, // Default user type
            'created_at' => $adminUser->created_at,
            'updated_at' => $adminUser->updated_at,
            
            // Additional fields if they exist in SafariChat users table
            'subscription_status' => 'active',
            'available_credits' => 1000, // Default credits
            'admin_role' => $adminUser->mapped_role,
            'is_staff' => true,
            
            // Store original admin data as metadata
            'admin_metadata' => json_encode([
                'role_id' => $adminUser->role_id,
                'department' => $adminUser->department,
                'joining_date' => $adminUser->joining_date,
                'status' => $adminUser->status,
                'original_name_parts' => [
                    'firstname' => $adminUser->firstname,
                    'middlename' => $adminUser->middlename,
                    'lastname' => $adminUser->lastname
                ]
            ])
        ];
    }

    /**
     * Map admin client data to SafariChat business contact format
     */
    public function mapClientData(AdminClient $adminClient): array
    {
        return [
            'external_crm_id' => $adminClient->id,
            'guest_name' => $adminClient->full_contact_name,
            'guest_email' => $adminClient->primary_email,
            'guest_phone' => $this->normalizePhone($adminClient->primary_phone),
            'business_id' => $this->config['import_settings']['auto_assign_business'],
            
            // CRM-specific fields
            'crm_status' => $adminClient->mapped_status,
            'imported_from_crm' => true,
            'crm_created_at' => $adminClient->created_at,
            'crm_updated_at' => $adminClient->updated_at,
            
            // Mapped fields
            'address' => $adminClient->address,
            'notes' => $adminClient->note,
            'assigned_user_id' => $this->mapStaffUser($adminClient->created_by),
            
            // Store additional data as JSON
            'custom_data' => json_encode($adminClient->custom_data),
            
            // Standard SafariChat fields
            'created_at' => $adminClient->created_at,
            'updated_at' => $adminClient->updated_at,
            
            // Lead tracking fields
            'lead_stage' => $this->mapLeadStage($adminClient->status),
            'lead_source' => $adminClient->source ?: 'admin_crm_import',
            'lead_score' => $this->calculateLeadScore($adminClient),
            
            // Business-specific fields
            'company_name' => $adminClient->name,
            'industry' => $this->determineIndustry($adminClient),
            'estimated_value' => $adminClient->estimated_students * ($adminClient->price_per_student ?: 50),
        ];
    }

    /**
     * Map admin task data to SafariChat conversation format
     */
    public function mapTaskData(AdminTask $adminTask, ?BusinessContact $contact = null, int $defaultLeadId = 1): array
    {
        return [
            'contact_id' => $contact ? $contact->id : null,
            'business_id' => $contact ? $contact->business_id : $this->config['import_settings']['auto_assign_business'],
            'lead_id' => $contact ? $contact->id : $defaultLeadId,  // Use default lead_id when contact is null
            
            // Message/conversation data
            'message_content' => $adminTask->formatted_activity,
            'sender_type' => 'user_manual',  // Use valid enum value instead of 'staff'
            'staff_user_id' => $this->mapStaffUser($adminTask->user_id),
            
            // Timing
            'timestamp' => $adminTask->full_timestamp,
            'created_at' => $adminTask->created_at,
            'updated_at' => $adminTask->updated_at,
            
            // Task-specific fields
            'interaction_type' => $adminTask->interaction_type,
            'priority_level' => $adminTask->mapped_priority,
            'task_status' => $adminTask->mapped_status,
            
            // Additional context
            'follow_up_notes' => $adminTask->next_action,
            'estimated_value' => $adminTask->budget,
            'has_follow_up' => $adminTask->has_follow_up,
            
            // Import metadata
            'imported_from_crm' => true,
            'original_task_date' => $adminTask->date,
            'original_task_time' => $adminTask->time,
            
            // Store original task data
            'task_metadata' => json_encode([
                'original_action' => $adminTask->action,
                'priority' => $adminTask->priority,
                'to_user_id' => $adminTask->to_user_id,
                'task_type_id' => $adminTask->task_type_id,
                'ticket_no' => $adminTask->ticket_no,
                'client_contact' => [
                    'phone' => $adminTask->client_phone,
                    'email' => $adminTask->client_email,
                    'name' => $adminTask->client_name
                ]
            ])
        ];
    }

    /**
     * Normalize phone number to international format
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (empty($phone)) return null;

        // If already starts with +, return as is
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // If starts with 0, replace with country code
        if (str_starts_with($phone, '0')) {
            return $this->config['phone_normalization']['default_country_code'] . 
                   ltrim($phone, '0');
        }

        // If doesn't start with +, add default country code
        return $this->config['phone_normalization']['default_country_code'] . $phone;
    }

    /**
     * Map admin staff user ID to SafariChat user ID
     */
    public function mapStaffUser(?int $adminUserId): ?int
    {
        if (!$adminUserId) return null;

        // Try to find the user by email from the admin user data
        try {
            $adminUser = \App\Models\AdminCrm\AdminUser::find($adminUserId);
            if ($adminUser && $adminUser->email) {
                $user = \App\Models\User::where('email', $adminUser->email)->first();
                if ($user) {
                    return $user->id;
                }
            }
        } catch (\Exception $e) {
            // Fallback if admin user lookup fails
        }

        // Fallback to default user if staff not found
        return $this->config['import_settings']['auto_assign_business'];
    }

    /**
     * Map client status to lead stage
     */
    protected function mapLeadStage(?int $status): string
    {
        if ($status === null) return 'new_lead';
        
        $stageMapping = [
            0 => 'new_lead',        // lead
            1 => 'qualified',       // prospect
            2 => 'customer',        // customer
            4 => 'lost',           // churned
            5 => 'qualified',       // qualified lead
            6 => 'nurturing'        // low usage clients
        ];

        return $stageMapping[$status] ?? 'new_lead';
    }

    /**
     * Calculate lead score based on admin client data
     */
    protected function calculateLeadScore(AdminClient $client): int
    {
        $score = 0;

        // Base score by status
        $statusScores = [
            0 => 20,  // lead
            1 => 40,  // prospect
            2 => 80,  // customer
            4 => 0,   // churned
            5 => 60,  // qualified lead
            6 => 30   // low usage
        ];

        $score += $statusScores[$client->status] ?? 20;

        // Add points for complete information
        if ($client->email) $score += 10;
        if ($client->phone) $score += 10;
        if ($client->estimated_students > 0) $score += 15;
        if ($client->registration_number) $score += 5;
        if ($client->director_name) $score += 10;

        // Add points for business size
        if ($client->estimated_students >= 1000) $score += 20;
        elseif ($client->estimated_students >= 500) $score += 15;
        elseif ($client->estimated_students >= 100) $score += 10;

        // Add points for recent activity
        if ($client->updated_at && $client->updated_at->diffInDays(now()) <= 30) {
            $score += 10;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Determine industry from client data
     */
    protected function determineIndustry(AdminClient $client): string
    {
        // Since this is an education management system, assume education
        return 'Education';
    }

    /**
     * Validate mapped data before import
     */
    public function validateClientData(array $data): array
    {
        $errors = [];
        $rules = $this->config['validation_rules']['clients'];

        foreach ($rules as $field => $rule) {
            if (str_contains($rule, 'required') && empty($data[$field])) {
                $errors[] = "Field {$field} is required";
            }

            if ($field === 'guest_email' && !empty($data[$field])) {
                if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid email format: {$data[$field]}";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate task data before import
     */
    public function validateTaskData(array $data): array
    {
        $errors = [];
        $rules = $this->config['validation_rules']['tasks'];

        foreach ($rules as $field => $rule) {
            if (str_contains($rule, 'required') && empty($data[$field])) {
                $errors[] = "Field {$field} is required";
            }
        }

        if (empty($data['contact_id'])) {
            $errors[] = "Contact ID is required for task import";
        }

        return $errors;
    }

    /**
     * Get field mapping configuration
     */
    public function getFieldMappings(): array
    {
        return $this->config['field_mappings'];
    }

    /**
     * Get status mappings
     */
    public function getStatusMappings(): array
    {
        return $this->config['status_mappings'];
    }
}