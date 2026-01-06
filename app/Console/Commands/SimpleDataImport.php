<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimpleDataImport extends Command
{
    protected $signature = 'admin:simple-import {--users=10} {--clients=10} {--tasks=10}';
    protected $description = 'Simple direct import from Admin CRM to SafariChat';

    public function handle()
    {
        $userLimit = $this->option('users');
        $clientLimit = $this->option('clients');
        $taskLimit = $this->option('tasks');

        $this->info("Starting simple import: {$userLimit} users, {$clientLimit} clients, {$taskLimit} tasks");

        // IMPORT USERS
        $this->info("Importing users...");
        $userResults = $this->importUsers($userLimit);
        $this->displayResults('Users', $userResults);

        // IMPORT CLIENTS
        $this->info("Importing clients...");
        $clientResults = $this->importClients($clientLimit);
        $this->displayResults('Clients', $clientResults);

        // IMPORT TASKS
        $this->info("Importing tasks...");
        $taskResults = $this->importTasks($taskLimit);
        $this->displayResults('Tasks', $taskResults);
    }

    private function importUsers($limit)
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $adminUsers = DB::connection('admin_crm')->table('users')
            ->where('status', 1)  // Use integer for active status
            ->limit($limit)
            ->get();

        foreach ($adminUsers as $adminUser) {
            try {
                DB::table('users')->insert([
                    'name' => $adminUser->name ?? 'Unknown User',
                    'email' => $adminUser->email,
                    'phone' => $adminUser->phone ?? '',
                    'password' => bcrypt('password123'),
                    'user_type_id' => 1,
                    'subscription_status' => 'active',
                    'available_credits' => 1000,
                    'uuid' => \Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $imported++;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'unique') !== false) {
                    $skipped++;
                } else {
                    $errors++;
                    $this->error("User import error: " . $e->getMessage());
                }
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    private function importClients($limit)
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $adminClients = DB::connection('admin_crm')->table('clients')
            ->limit($limit)
            ->get();

        foreach ($adminClients as $adminClient) {
            try {
                DB::table('business_contacts')->insert([
                    'guest_name' => $adminClient->guest_name ?? 'Unknown',
                    'guest_phone' => $adminClient->phone ?? '',
                    'guest_email' => $adminClient->email ?? '',
                    'business_id' => 4, // Default business
                    'user_id' => 45,    // Default user
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $imported++;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'unique') !== false) {
                    $skipped++;
                } else {
                    $errors++;
                    $this->error("Client import error: " . $e->getMessage());
                }
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    private function importTasks($limit)
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        // Get first available lead ID
        $leadId = DB::table('leads')->value('id') ?? 26;

        $adminTasks = DB::connection('admin_crm')->table('tasks')
            ->limit($limit)
            ->get();

        foreach ($adminTasks as $adminTask) {
            try {
                DB::table('conversations')->insert([
                    'lead_id' => $leadId,
                    'message_content' => $adminTask->activity ?? 'Imported task',
                    'sender_type' => 'user_manual',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $imported++;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'unique') !== false) {
                    $skipped++;
                } else {
                    $errors++;
                    $this->error("Task import error: " . $e->getMessage());
                }
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    private function displayResults($type, $results)
    {
        $this->info("$type Results:");
        $this->info("  Imported: {$results['imported']}");
        $this->info("  Skipped: {$results['skipped']}");
        $this->info("  Errors: {$results['errors']}");
        $this->newLine();
    }
}