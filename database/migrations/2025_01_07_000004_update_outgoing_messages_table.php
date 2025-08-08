<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOutgoingMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Add only the missing queue tracking fields
            $table->string('job_id')->nullable()->after('id');
            $table->string('batch_id')->nullable()->after('job_id');
            $table->timestamp('queued_at')->nullable()->after('scheduled_at');
            
            // Add indexes for performance
            $table->index(['job_id']);
            $table->index(['batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropIndex(['job_id']);
            $table->dropIndex(['batch_id']);
            
            $table->dropColumn([
                'job_id',
                'batch_id', 
                'queued_at'
            ]);
        });
    }
}
