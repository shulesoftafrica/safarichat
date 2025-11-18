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
        Schema::create('ai_sales_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('assistant_name');
            $table->string('status')->default('draft'); // draft, active, inactive
            
            // Target Group Configuration
            $table->string('target_audience')->nullable();
            $table->json('target_user_types')->nullable(); // Array of user_type IDs
            $table->json('industries')->nullable();
            $table->string('communication_tone')->nullable();
            $table->text('personality_description')->nullable();
            
            // Working Hours Configuration
            $table->boolean('always_available')->default(true);
            $table->json('business_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone')->default('Africa/Nairobi');
            $table->text('out_of_hours_message')->nullable();
            
            // Language Configuration
            $table->string('primary_language')->default('en');
            $table->json('additional_languages')->nullable();
            $table->boolean('auto_detect_language')->default(true);
            $table->text('language_fallback_message')->nullable();
            
            // Negotiation Configuration
            $table->boolean('allow_negotiation')->default(true);
            $table->integer('max_discount_allowed')->default(15);
            $table->boolean('accept_installments')->default(false);
            $table->integer('max_installments')->default(3);
            $table->integer('min_down_payment')->default(50);
            $table->boolean('stop_orders_low_stock')->default(true);
            $table->integer('low_stock_threshold')->default(5);
            $table->text('negotiation_script')->nullable();
            
            // Fallback & Escalation Configuration
            $table->string('fallback_number')->nullable();
            $table->string('fallback_person')->nullable();
            $table->json('escalation_triggers')->nullable();
            $table->decimal('large_order_threshold', 10, 2)->default(1000);
            
            // Follow-up Configuration
            $table->boolean('auto_followup')->default(true);
            $table->integer('followup_delay')->default(24); // hours
            $table->integer('max_followups')->default(1);
            $table->text('followup_message')->nullable();
            
            // Notification Configuration
            $table->boolean('notify_on_deal')->default(true);
            $table->json('notification_methods')->nullable();
            $table->json('additional_notifications')->nullable();
            
            // Terms & Conditions
            $table->boolean('accepted_terms')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('assistant_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_sales_agents');
    }
};
