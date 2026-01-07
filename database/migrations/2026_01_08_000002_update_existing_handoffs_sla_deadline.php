<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Handoff;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update existing handoffs that don't have SLA deadlines
        $handoffs = Handoff::whereNull('sla_deadline')->get();
        
        foreach ($handoffs as $handoff) {
            $hours = match($handoff->priority_level) {
                Handoff::PRIORITY_URGENT => 0.5,  // 30 minutes
                Handoff::PRIORITY_HIGH => 2,      // 2 hours
                Handoff::PRIORITY_MEDIUM => 4,    // 4 hours
                Handoff::PRIORITY_LOW => 24,      // 24 hours
                default => 4                      // Default 4 hours
            };
            
            // Set SLA deadline based on creation time + priority hours
            $slaDeadline = $handoff->created_at->addHours($hours);
            $handoff->update(['sla_deadline' => $slaDeadline]);
        }
        
        echo "Updated " . $handoffs->count() . " handoffs with SLA deadlines.\n";
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optionally clear SLA deadlines
        Handoff::whereNotNull('sla_deadline')->update(['sla_deadline' => null]);
    }
};