<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id');
            
            // Calendar Identity
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('calendar_type', 50); // 'demo', 'consultation', 'follow_up', 'meeting', 'call', 'custom'
            
            // Appointment Settings
            $table->integer('default_duration_minutes')->default(30);
            $table->integer('buffer_minutes')->default(10); // Time between appointments
            
            // Availability Rules (JSON)
            $table->jsonb('availability_rules')->default('{}');
            /* Example structure:
            {
                "working_hours": {
                    "monday": {"start": "09:00", "end": "17:00"},
                    "tuesday": {"start": "09:00", "end": "17:00"},
                    "wednesday": {"start": "09:00", "end": "17:00"},
                    "thursday": {"start": "09:00", "end": "17:00"},
                    "friday": {"start": "09:00", "end": "17:00"},
                    "saturday": null,
                    "sunday": null
                },
                "breaks": [
                    {"start": "12:00", "end": "13:00", "days": [1,2,3,4,5]}
                ],
                "blackout_dates": ["2026-12-25", "2026-01-01"]
            }
            */
            
            // Booking Limits
            $table->integer('max_bookings_per_day')->nullable();
            $table->integer('max_bookings_per_week')->nullable();
            $table->integer('min_advance_hours')->default(2); // Minimum hours before booking
            $table->integer('max_advance_days')->default(60); // Maximum days in future to book
            
            // Integration Settings
            $table->boolean('allow_ai_booking')->default(true);
            $table->boolean('allow_manual_booking')->default(true);
            $table->boolean('require_confirmation')->default(true);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['business_id', 'is_active'], 'idx_booking_calendars_business_active');
            $table->index('calendar_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_calendars');
    }
}
