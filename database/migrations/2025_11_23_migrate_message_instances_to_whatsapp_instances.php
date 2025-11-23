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
        // Migrate data from message_instances to whatsapp_instances
        $messageInstances = DB::table('message_instances')
            ->where('type', 'whatsapp')
            ->whereNotNull('instance_id') // Only migrate instances with valid instance_id
            ->get();

        foreach ($messageInstances as $messageInstance) {
            // Check if this user already has a WhatsApp instance with this instance_id
            $existingInstance = DB::table('whatsapp_instances')
                ->where('user_id', $messageInstance->user_id)
                ->where('instance_id', $messageInstance->instance_id)
                ->first();

            if (!$existingInstance) {
                try {
                    DB::table('whatsapp_instances')->insert([
                        'user_id' => $messageInstance->user_id,
                        'instance_id' => $messageInstance->instance_id,
                        'instance_name' => $messageInstance->name ?? 'WhatsApp Instance',
                        'phone_number' => $messageInstance->phone_number ?? '',
                        'webhook_url' => $messageInstance->webhook_url,
                        'status' => $this->mapStatus($messageInstance->status),
                        'created_at' => $messageInstance->created_at ?? now(),
                        'updated_at' => $messageInstance->updated_at ?? now(),
                    ]);

                    echo "Migrated message instance for user {$messageInstance->user_id}\n";
                } catch (\Exception $e) {
                    echo "Failed to migrate instance for user {$messageInstance->user_id}: {$e->getMessage()}\n";
                }
            } else {
                echo "Skipping existing instance for user {$messageInstance->user_id}\n";
            }
        }

        echo "Migration completed successfully!\n";
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This would be complex to reverse safely, so we'll leave it empty
        // The original message_instances data will still exist until we drop the table
        echo "Reverse migration not implemented for safety reasons.\n";
    }

    /**
     * Map old status values to new status values
     */
    private function mapStatus($oldStatus)
    {
        switch ($oldStatus) {
            case 0:
                return 'pending';
            case 1:
                return 'active';
            default:
                return 'pending';
        }
    }
};