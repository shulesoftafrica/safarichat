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
        Schema::table('message_queue', function (Blueprint $table) {
            $table->boolean('is_nurture_mode')->default(false)->comment('Was this message reframed for ghosting contact?')->after('ai_metadata');
            $table->unsignedBigInteger('nurture_library_id')->nullable()->comment('Which value nugget was used')->after('is_nurture_mode');
            $table->string('nurture_value_type', 50)->nullable()->comment('case_study, tip, insight, etc.')->after('nurture_library_id');
            $table->text('pre_nurture_message')->nullable()->comment('Original pushy message before AI reframing')->after('nurture_value_type');
            $table->boolean('nurture_success')->nullable()->comment('Did contact reply after nurture message?')->after('pre_nurture_message');
            $table->integer('nurture_reply_time')->nullable()->comment('Minutes until reply (if any)')->after('nurture_success');
            
            // Indexes
            $table->index(['is_nurture_mode', 'nurture_success'], 'idx_nurture_tracking');
            
            // Foreign Keys
            $table->foreign('nurture_library_id')->references('id')->on('nurture_library')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_queue', function (Blueprint $table) {
            $table->dropForeign(['nurture_library_id']);
            $table->dropIndex('idx_nurture_tracking');
            $table->dropColumn([
                'is_nurture_mode',
                'nurture_library_id',
                'nurture_value_type',
                'pre_nurture_message',
                'nurture_success',
                'nurture_reply_time'
            ]);
        });
    }
};
