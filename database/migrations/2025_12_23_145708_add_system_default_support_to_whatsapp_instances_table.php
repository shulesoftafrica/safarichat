<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->boolean('is_system_default')->default(false)->after('is_primary');
            $table->enum('usage_scope', ['user', 'system'])->default('user')->after('is_system_default');
            $table->json('allowed_message_types')->nullable()->after('usage_scope');
            
            // Add indexes for performance
            $table->index(['is_system_default', 'usage_scope'], 'idx_whatsapp_instances_system_default');
        });
        
        // Add partial unique index for PostgreSQL (only one system default allowed)
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX unique_system_default ON whatsapp_instances (is_system_default) WHERE is_system_default = true');
        } else {
            // MySQL syntax
            DB::statement('ALTER TABLE whatsapp_instances ADD CONSTRAINT unique_system_default UNIQUE (is_system_default) WHERE is_system_default = 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            // Drop unique constraint/index
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS unique_system_default');
            } else {
                DB::statement('ALTER TABLE whatsapp_instances DROP INDEX IF EXISTS unique_system_default');
            }
            
            $table->dropIndex('idx_whatsapp_instances_system_default');
            $table->dropColumn(['is_system_default', 'usage_scope', 'allowed_message_types']);
        });
    }
};
