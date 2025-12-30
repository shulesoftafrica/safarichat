<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
        });
        
        // Insert default pricing settings
        DB::table('settings')->insert([
            [
                'key' => 'billing.price_per_message',
                'value' => '100',
                'type' => 'number',
                'description' => 'Cost per AI message in TZS',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'billing.price_per_month',
                'value' => '15000',
                'type' => 'number',
                'description' => 'Legacy monthly subscription base price in TZS',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'billing.free_messages_limit',
                'value' => '100',
                'type' => 'number',
                'description' => 'Free messages for trial users',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'billing.starter_price',
                'value' => '15000',
                'type' => 'number',
                'description' => 'Starter plan monthly price in TZS',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'billing.pro_price',
                'value' => '45000',
                'type' => 'number',
                'description' => 'Pro plan monthly price in TZS',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'billing.premium_price',
                'value' => '85000',
                'type' => 'number',
                'description' => 'Premium plan monthly price in TZS',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
