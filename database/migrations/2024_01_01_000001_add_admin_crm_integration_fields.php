<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add external IDs and CRM tracking fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('external_staff_id')->nullable()->after('id')->comment('Admin CRM staff ID');
            $table->boolean('is_staff')->default(false)->after('email');
            $table->string('admin_role', 50)->nullable()->after('is_staff');
            $table->json('admin_metadata')->nullable()->after('admin_role')->comment('Original Admin CRM data');
            $table->timestamp('crm_imported_at')->nullable()->after('admin_metadata');
            
            $table->index('external_staff_id');
        });

        // Add CRM fields to business_contacts table
        Schema::table('business_contacts', function (Blueprint $table) {
            $table->integer('external_crm_id')->nullable()->after('id')->comment('Admin CRM client ID');
            $table->boolean('imported_from_crm')->default(false)->after('external_crm_id');
            $table->string('crm_status', 50)->nullable()->after('imported_from_crm');
            $table->timestamp('crm_created_at')->nullable()->after('crm_status');
            $table->timestamp('crm_updated_at')->nullable()->after('crm_created_at');
            $table->string('lead_stage', 50)->nullable()->after('crm_updated_at');
            $table->string('lead_source', 100)->nullable()->after('lead_stage');
            $table->integer('lead_score')->default(0)->after('lead_source');
            $table->string('company_name', 255)->nullable()->after('lead_score');
            $table->string('industry', 100)->nullable()->after('company_name');
            $table->decimal('estimated_value', 10, 2)->nullable()->after('industry');
            $table->json('custom_data')->nullable()->after('estimated_value')->comment('Additional CRM data');
            $table->integer('assigned_user_id')->nullable()->after('custom_data');
            
            $table->index('external_crm_id');
            $table->index('imported_from_crm');
            $table->index('crm_status');
            $table->index('lead_stage');
            $table->index(['crm_status', 'lead_stage']);
        });

        // Create conversations table for task history (if doesn't exist)
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->integer('external_task_id')->nullable()->comment('Admin CRM task ID');
                $table->unsignedBigInteger('contact_id');
                $table->unsignedBigInteger('business_id');
                $table->text('message_content');
                $table->enum('sender_type', ['staff', 'client', 'system'])->default('staff');
                $table->unsignedBigInteger('staff_user_id')->nullable();
                $table->timestamp('timestamp');
                $table->string('interaction_type', 50)->nullable();
                $table->string('priority_level', 20)->nullable();
                $table->string('task_status', 50)->nullable();
                $table->text('follow_up_notes')->nullable();
                $table->decimal('estimated_value', 10, 2)->nullable();
                $table->boolean('has_follow_up')->default(false);
                $table->boolean('imported_from_crm')->default(false);
                $table->date('original_task_date')->nullable();
                $table->time('original_task_time')->nullable();
                $table->json('task_metadata')->nullable()->comment('Original Admin CRM task data');
                $table->timestamps();
                
                $table->index('external_task_id');
                $table->index('contact_id');
                $table->index('business_id');
                $table->index('staff_user_id');
                $table->index('imported_from_crm');
                $table->index(['contact_id', 'timestamp']);
                $table->index(['business_id', 'timestamp']);
            });
        } else {
            // Add CRM fields to existing conversations table
            Schema::table('conversations', function (Blueprint $table) {
                $table->integer('external_task_id')->nullable()->after('id')->comment('Admin CRM task ID');
                $table->string('interaction_type', 50)->nullable()->after('external_task_id');
                $table->string('priority_level', 20)->nullable()->after('interaction_type');
                $table->string('task_status', 50)->nullable()->after('priority_level');
                $table->text('follow_up_notes')->nullable()->after('task_status');
                $table->decimal('estimated_value', 10, 2)->nullable()->after('follow_up_notes');
                $table->boolean('has_follow_up')->default(false)->after('estimated_value');
                $table->boolean('imported_from_crm')->default(false)->after('has_follow_up');
                $table->date('original_task_date')->nullable()->after('imported_from_crm');
                $table->time('original_task_time')->nullable()->after('original_task_date');
                $table->json('task_metadata')->nullable()->after('original_task_time')->comment('Original Admin CRM task data');
                
                $table->index('external_task_id');
                $table->index('imported_from_crm');
            });
        }

        // Create admin_crm_import_log table for tracking imports
        Schema::create('admin_crm_import_log', function (Blueprint $table) {
            $table->id();
            $table->string('import_type', 50); // 'staff', 'clients', 'tasks', 'full_sync'
            $table->integer('records_processed')->default(0);
            $table->integer('records_imported')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->integer('records_errors')->default(0);
            $table->json('import_parameters')->nullable()->comment('Filters, batch size, etc.');
            $table->json('error_details')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('executed_by_user_id')->nullable();
            $table->timestamps();
            
            $table->index('import_type');
            $table->index('status');
            $table->index('started_at');
        });

        // Create admin_crm_sync_status table for tracking sync state
        Schema::create('admin_crm_sync_status', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 50); // 'staff', 'clients', 'tasks'
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('last_sync_records')->default(0);
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->json('sync_metadata')->nullable()->comment('Last sync parameters, errors, etc.');
            $table->boolean('auto_sync_enabled')->default(false);
            $table->integer('auto_sync_interval_minutes')->nullable();
            $table->timestamp('next_scheduled_sync')->nullable();
            $table->timestamps();
            
            $table->unique('sync_type');
            $table->index('last_sync_at');
            $table->index('next_scheduled_sync');
        });

        // Create indexes for better query performance
        Schema::table('users', function (Blueprint $table) {
            $table->index(['external_staff_id', 'is_staff']);
            $table->index('crm_imported_at');
        });

        Schema::table('business_contacts', function (Blueprint $table) {
            $table->index(['imported_from_crm', 'crm_status']);
            $table->index(['lead_stage', 'lead_score']);
            $table->index('assigned_user_id');
        });
    }

    public function down()
    {
        // Drop created tables
        Schema::dropIfExists('admin_crm_sync_status');
        Schema::dropIfExists('admin_crm_import_log');
        
        // Remove added columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['external_staff_id']);
            $table->dropIndex(['external_staff_id', 'is_staff']);
            $table->dropIndex(['crm_imported_at']);
            
            $table->dropColumn([
                'external_staff_id',
                'is_staff', 
                'admin_role',
                'admin_metadata',
                'crm_imported_at'
            ]);
        });

        // Remove added columns from business_contacts table
        Schema::table('business_contacts', function (Blueprint $table) {
            $table->dropIndex(['external_crm_id']);
            $table->dropIndex(['imported_from_crm']);
            $table->dropIndex(['crm_status']);
            $table->dropIndex(['lead_stage']);
            $table->dropIndex(['crm_status', 'lead_stage']);
            $table->dropIndex(['imported_from_crm', 'crm_status']);
            $table->dropIndex(['lead_stage', 'lead_score']);
            $table->dropIndex(['assigned_user_id']);
            
            $table->dropColumn([
                'external_crm_id',
                'imported_from_crm',
                'crm_status',
                'crm_created_at',
                'crm_updated_at',
                'lead_stage',
                'lead_source',
                'lead_score',
                'company_name',
                'industry',
                'estimated_value',
                'custom_data',
                'assigned_user_id'
            ]);
        });

        // Remove CRM columns from conversations table (if they were added)
        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropIndex(['external_task_id']);
                $table->dropIndex(['imported_from_crm']);
                
                $table->dropColumn([
                    'external_task_id',
                    'interaction_type',
                    'priority_level',
                    'task_status',
                    'follow_up_notes',
                    'estimated_value',
                    'has_follow_up',
                    'imported_from_crm',
                    'original_task_date',
                    'original_task_time',
                    'task_metadata'
                ]);
            });
        }
    }
};