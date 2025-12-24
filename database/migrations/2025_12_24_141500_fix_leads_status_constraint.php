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
        // For PostgreSQL, we need to drop and recreate the enum constraint
        DB::statement("ALTER TABLE leads DROP CONSTRAINT IF EXISTS leads_status_check");
        
        // Add the updated constraint with all status values from Lead model
        DB::statement("ALTER TABLE leads ADD CONSTRAINT leads_status_check CHECK (status IN ('NEW', 'OUTREACHED', 'REPLIED', 'ENGAGED', 'QUALIFIED', 'PITCHED', 'DEMO_SCHEDULED', 'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST', 'HANDED_OFF', 'DO_NOT_CONTACT', 'NEEDS_ATTENTION', 'CONVERTED', 'CHURNED'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE leads DROP CONSTRAINT IF EXISTS leads_status_check");
        DB::statement("ALTER TABLE leads ADD CONSTRAINT leads_status_check CHECK (status IN ('NEW', 'OUTREACHED', 'REPLIED', 'QUALIFIED', 'PITCHED', 'DEMO_SCHEDULED', 'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST', 'HANDED_OFF', 'DO_NOT_CONTACT'))");
    }
};