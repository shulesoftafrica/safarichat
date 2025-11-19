<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('events_guest_id'); // Reference to existing contact
            $table->unsignedBigInteger('ai_sales_agent_id'); // Reference to AI agent configuration
            $table->string('source', 50)->default('manual'); // 'manual', 'import', 'webform', 'api'
            $table->enum('status', [
                'NEW', 'OUTREACHED', 'REPLIED', 'QUALIFIED', 'PITCHED', 'DEMO_SCHEDULED',
                'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST', 'HANDED_OFF', 'DO_NOT_CONTACT'
            ])->default('NEW');
            $table->timestamp('last_interaction_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('company_name')->nullable();
            $table->string('industry', 100)->nullable();
            
            // Churn tracking fields
            $table->boolean('is_churned')->default(false);
            $table->timestamp('churn_date')->nullable();
            $table->string('churn_reason')->nullable();
            $table->text('churn_notes')->nullable();
            $table->timestamp('win_back_eligible_at')->nullable();
            $table->integer('win_back_attempts')->default(0);
            $table->timestamp('last_win_back_at')->nullable();
            
            // Sales tracking
            $table->decimal('final_price', 10, 2)->nullable();
            $table->decimal('deal_value', 10, 2)->nullable();
            $table->integer('conversion_probability')->default(0); // 0-100%
            $table->integer('lead_score')->default(0);
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('events_guest_id')->references('id')->on('events_guests')->onDelete('cascade');
            $table->foreign('ai_sales_agent_id')->references('id')->on('ai_sales_agents')->onDelete('cascade');
            $table->foreign('assigned_agent_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
            $table->index('last_interaction_at');
            $table->index('is_churned');
            $table->index('ai_sales_agent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
}