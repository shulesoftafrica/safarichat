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
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id'); // Allow null initially
            $table->string('purpose', 50)->default('general')->after('status');
            $table->text('instance_description')->nullable()->after('purpose');
            $table->boolean('is_primary')->default(false)->after('instance_description');
            $table->string('display_name', 100)->nullable()->after('is_primary');
        });
        
        // Generate UUIDs for existing instances
        $instances = \App\Models\WhatsappInstance::whereNull('uuid')->get();
        foreach ($instances as $instance) {
            $instance->uuid = (string) \Illuminate\Support\Str::uuid();
            $instance->save();
        }
        
        // Now make UUID unique and not null
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
            
            // Add indexes for performance
            $table->index('purpose', 'idx_whatsapp_instances_purpose');
            $table->index('uuid', 'idx_whatsapp_instances_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropIndex('idx_whatsapp_instances_purpose');
            $table->dropIndex('idx_whatsapp_instances_uuid');
            $table->dropColumn(['uuid', 'purpose', 'instance_description', 'is_primary', 'display_name']);
        });
    }
};
