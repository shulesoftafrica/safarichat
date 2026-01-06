<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_contacts', function (Blueprint $table) {
            // Add missing CRM fields if they don't exist
            if (!Schema::hasColumn('business_contacts', 'company')) {
                $table->string('company')->nullable()->after('guest_email');
            }
            if (!Schema::hasColumn('business_contacts', 'industry')) {
                $table->string('industry')->nullable()->after('company');
            }
            if (!Schema::hasColumn('business_contacts', 'custom_data')) {
                $table->text('custom_data')->nullable()->after('crm_data');
            }
            if (!Schema::hasColumn('business_contacts', 'imported_from_crm')) {
                $table->boolean('imported_from_crm')->default(false)->after('custom_data');
            }
            if (!Schema::hasColumn('business_contacts', 'crm_created_at')) {
                $table->timestamp('crm_created_at')->nullable()->after('imported_from_crm');
            }
            if (!Schema::hasColumn('business_contacts', 'crm_updated_at')) {
                $table->timestamp('crm_updated_at')->nullable()->after('crm_created_at');
            }
            if (!Schema::hasColumn('business_contacts', 'source')) {
                $table->string('source')->nullable()->default('manual')->after('crm_updated_at');
            }
            if (!Schema::hasColumn('business_contacts', 'tags')) {
                $table->text('tags')->nullable()->after('source');
            }
            
            // Add indexes if they don't exist
            $table->index(['imported_from_crm']);
            $table->index(['source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_contacts', function (Blueprint $table) {
            $table->dropIndex(['imported_from_crm']);
            $table->dropIndex(['source']);
            
            $table->dropColumn([
                'company',
                'industry', 
                'custom_data',
                'imported_from_crm',
                'crm_created_at',
                'crm_updated_at',
                'source',
                'tags'
            ]);
        });
    }
};
