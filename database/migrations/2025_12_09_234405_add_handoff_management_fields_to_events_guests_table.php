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
        Schema::table('events_guests', function (Blueprint $table) {
            // Handoff Management Fields
            $table->enum('handoff_status', ['ai', 'pending_handoff', 'handed_off', 'completed'])
                  ->default('ai')
                  ->after('guest_pledge')
                  ->comment('Customer service handoff status');
            
            $table->unsignedBigInteger('assigned_agent_id')->nullable()
                  ->after('handoff_status')
                  ->comment('ID of assigned human agent');
            
            $table->text('handoff_reason')->nullable()
                  ->after('assigned_agent_id')
                  ->comment('Reason for handoff request');
            
            $table->text('handoff_notes')->nullable()
                  ->after('handoff_reason')
                  ->comment('Internal notes about handoff');
            
            $table->integer('priority_level')->default(3)
                  ->after('handoff_notes')
                  ->comment('Priority level: 1=High, 2=Medium, 3=Low, 4=Urgent, 5=Critical');
            
            $table->timestamp('handoff_requested_at')->nullable()
                  ->after('priority_level')
                  ->comment('When handoff was first requested');
            
            $table->timestamp('handoff_assigned_at')->nullable()
                  ->after('handoff_requested_at')
                  ->comment('When agent was assigned');
            
            $table->timestamp('handoff_completed_at')->nullable()
                  ->after('handoff_assigned_at')
                  ->comment('When handoff was completed');
            
            $table->timestamp('last_ai_interaction')->nullable()
                  ->after('handoff_completed_at')
                  ->comment('Last interaction with AI system');
            
            $table->timestamp('last_human_interaction')->nullable()
                  ->after('last_ai_interaction')
                  ->comment('Last interaction with human agent');
            
            // Add foreign key for assigned agent (assuming users table for agents)
            $table->foreign('assigned_agent_id')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for performance
            $table->index('handoff_status');
            $table->index('assigned_agent_id');
            $table->index('priority_level');
            $table->index('handoff_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events_guests', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['assigned_agent_id']);
            
            // Drop indexes
            $table->dropIndex(['handoff_status']);
            $table->dropIndex(['assigned_agent_id']);
            $table->dropIndex(['priority_level']);
            $table->dropIndex(['handoff_requested_at']);
            
            // Drop columns
            $table->dropColumn([
                'handoff_status',
                'assigned_agent_id', 
                'handoff_reason',
                'handoff_notes',
                'priority_level',
                'handoff_requested_at',
                'handoff_assigned_at',
                'handoff_completed_at',
                'last_ai_interaction',
                'last_human_interaction'
            ]);
        });
    }
};
