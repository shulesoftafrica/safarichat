<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Str;

class AddUuidToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if uuid column already exists
        if (Schema::hasColumn('users', 'uuid')) {
            return;
        }
        
        Schema::table('users', function (Blueprint $table) {
            // First add the uuid column
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid');
        });

        // Generate UUIDs for existing records
        User::whereNull('uuid')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->uuid = (string) Str::uuid();
                $user->save();
            }
        });

        // Make uuid column required and unique
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
}