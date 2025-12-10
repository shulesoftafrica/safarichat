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
        Schema::table('admin_bookings', function (Blueprint $table) {
            // Currency and amount fields
            $table->char('base_currency', 3)->default('TZS')->after('amount')->comment('Base currency code (e.g., TZS)');
            $table->bigInteger('base_amount')->nullable()->after('base_currency')->comment('Amount in base currency (whole units)');
            $table->char('display_currency', 3)->nullable()->after('base_amount')->comment('Currency shown to user (e.g., USD)');
            $table->decimal('display_amount', 12, 2)->nullable()->after('display_currency')->comment('Amount shown/charged in display currency');
            
            // Foreign exchange fields
            $table->decimal('fx_rate', 18, 8)->nullable()->after('display_amount')->comment('Base units per display unit (e.g., TZS per USD)');
            $table->decimal('fx_markup', 6, 4)->nullable()->after('fx_rate')->comment('FX markup multiplier for fees/margin');
            
            // Payment timing and status
            $table->timestamp('locked_at')->nullable()->after('fx_markup')->comment('When exchange rate was locked');
            $table->timestamp('expires_at')->nullable()->after('locked_at')->comment('When payment offer expires');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled'])
                  ->default('pending')
                  ->after('expires_at')
                  ->comment('Current payment status');
            
            // Add indexes for performance
            $table->index(['payment_status', 'created_at'], 'idx_payment_status_created');
            $table->index(['expires_at'], 'idx_expires_at');
            $table->index(['base_currency', 'display_currency'], 'idx_currency_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_bookings', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_payment_status_created');
            $table->dropIndex('idx_expires_at');
            $table->dropIndex('idx_currency_pair');
            
            // Drop columns
            $table->dropColumn([
                'base_currency',
                'base_amount',
                'display_currency', 
                'display_amount',
                'fx_rate',
                'fx_markup',
                'locked_at',
                'expires_at',
                'payment_status'
            ]);
        });
    }
};
