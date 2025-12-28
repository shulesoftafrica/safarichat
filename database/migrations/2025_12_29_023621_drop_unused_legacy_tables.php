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
        // Drop unused tables that had no data and no active code references
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('page_viewers');
        Schema::dropIfExists('promotions_payments');
        Schema::dropIfExists('promotions_reaches');
        Schema::dropIfExists('telegram_users');
        
        // Drop obsolete tables with broken/unused code references
        Schema::dropIfExists('admin_integration_requests');
        Schema::dropIfExists('discount_requests');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables cannot be restored without their original structure
        // Refer to backup files if restoration is needed
        throw new Exception('Cannot reverse dropping of legacy tables - use database backup for restoration');
    }
};
