<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingSlotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_calendar_id');
            $table->unsignedBigInteger('business_id');
            
            // Contact/Lead Information
            $table->unsignedBigInteger('business_contact_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            
            // Time Slot
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('duration_minutes');
            
            // Slot Status
            $table->string('status', 50)->default('available'); // 'available', 'reserved', 'confirmed', 'completed', 'cancelled', 'no_show'
            
            // Appointment Link (NULL until appointment confirmed)
            $table->unsignedBigInteger('appointment_id')->nullable();
            
            // Booking Details
            $table->unsignedBigInteger('booked_by_user_id')->nullable();
            $table->string('booking_method', 50)->nullable(); // 'ai_agent', 'manual', 'api', 'self_service'
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('booking_calendar_id')->references('id')->on('booking_calendars')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('business_contact_id')->references('id')->on('business_contacts')->onDelete('set null');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('booked_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('booking_calendar_id');
            $table->index('business_id');
            $table->index(['start_time', 'end_time'], 'idx_booking_slots_time');
            $table->index('status');
            $table->index('business_contact_id');
            $table->index('appointment_id');
            $table->index('lead_id');
        });
        
        // Add check constraints using raw SQL (PostgreSQL)
        DB::statement('ALTER TABLE booking_slots ADD CONSTRAINT chk_end_after_start CHECK (end_time > start_time)');
        DB::statement("ALTER TABLE booking_slots ADD CONSTRAINT chk_valid_status CHECK (status IN ('available', 'reserved', 'confirmed', 'completed', 'cancelled', 'no_show'))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_slots');
    }
}
