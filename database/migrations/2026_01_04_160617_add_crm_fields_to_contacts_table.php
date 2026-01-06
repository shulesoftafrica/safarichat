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
        // Check if contacts table exists, if not skip this migration
        if (!Schema::hasTable('contacts')) {
            return;
        }
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('crm_id')->nullable()->after('id');
            $table->string('company')->nullable()->after('email');
            $table->string('industry')->nullable()->after('company');
            $table->text('custom_data')->nullable()->after('industry');
            $table->boolean('imported_from_crm')->default(false)->after('custom_data');
            $table->timestamp('crm_created_at')->nullable()->after('imported_from_crm');
            $table->timestamp('crm_updated_at')->nullable()->after('crm_created_at');
            
            $table->index(['crm_id']);
            $table->index(['imported_from_crm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('contacts')) {
            return;
        }
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['crm_id']);
            $table->dropIndex(['imported_from_crm']);
            
            $table->dropColumn([
                'crm_id',
                'company',
                'industry',
                'custom_data',
                'imported_from_crm',
                'crm_created_at',
                'crm_updated_at'
            ]);
        });
    }
};
