<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, let's verify that data migration was successful
        $messageInstancesCount = DB::table('message_instances')
            ->where('type', 'whatsapp')
            ->count();
        
        $whatsappInstancesCount = DB::table('whatsapp_instances')->count();
        
        echo "Message instances (WhatsApp): {$messageInstancesCount}\n";
        echo "WhatsApp instances: {$whatsappInstancesCount}\n";
        
        if ($whatsappInstancesCount >= $messageInstancesCount) {
            echo "Data migration appears successful. Dropping message_instances table...\n";
            
            // Drop the message_instances table
            Schema::dropIfExists('message_instances');
            
            echo "message_instances table dropped successfully!\n";
        } else {
            throw new \Exception("Data migration verification failed. Not safe to drop message_instances table.");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate the message_instances table structure for rollback
        Schema::create('message_instances', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('owner')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('connect_status')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('pairing_code')->nullable();
            $table->text('webhook_url')->nullable();
            $table->text('webhook_events')->nullable();
            $table->integer('status')->default(0);
            $table->integer('is_paid')->default(0);
            $table->timestamps();
            $table->string('file_path')->nullable();
            $table->string('nida')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        echo "message_instances table recreated. Please restore data from backup if needed.\n";
    }
};