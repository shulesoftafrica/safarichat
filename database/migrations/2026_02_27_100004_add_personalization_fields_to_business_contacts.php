<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_contacts', function (Blueprint $table) {
            // AI-learned preferences
            $table->string('preferred_language', 10)->nullable()->after('last_contacted_at'); // en, sw, mixed
            $table->string('preferred_tone', 20)->nullable()->after('preferred_language'); // formal, casual
            $table->string('last_message_sentiment', 20)->nullable()->after('preferred_tone'); // positive, neutral, negative
            
            // Opt-out management
            $table->boolean('opt_out_status')->default(false)->after('last_message_sentiment');
            $table->timestamp('opt_out_at')->nullable()->after('opt_out_status');
            
            // Engagement patterns
            $table->integer('avg_reply_hour')->nullable()->after('opt_out_at'); // 0-23
            $table->decimal('engagement_score', 5, 2)->default(0)->after('avg_reply_hour'); // 0-100
            
            // Indexes for performance
            $table->index('opt_out_status');
            $table->index('engagement_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_contacts', function (Blueprint $table) {
            $table->dropIndex(['opt_out_status']);
            $table->dropIndex(['engagement_score']);
            
            $table->dropColumn([
                'preferred_language',
                'preferred_tone',
                'last_message_sentiment',
                'opt_out_status',
                'opt_out_at',
                'avg_reply_hour',
                'engagement_score'
            ]);
        });
    }
};
