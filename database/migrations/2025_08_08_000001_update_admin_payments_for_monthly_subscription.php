<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAdminPaymentsForMonthlySubscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_payments', function (Blueprint $table) {
            $table->datetime('subscription_start')->nullable()->after('date');
            $table->datetime('subscription_end')->nullable()->after('subscription_start');
            $table->integer('months_covered')->default(0)->after('subscription_end');
            $table->decimal('excess_amount', 10, 2)->default(0)->after('months_covered');
            
            // Add indexes for better performance
            $table->index(['user_id', 'subscription_end']);
            $table->index(['subscription_end']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_payments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'subscription_end']);
            $table->dropIndex(['subscription_end']);
            
            $table->dropColumn([
                'subscription_start',
                'subscription_end', 
                'months_covered',
                'excess_amount'
            ]);
        });
    }
}
