<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateUsersTableReplaceLocationIdWithPaisId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pais_id')) {
                $table->unsignedBigInteger('pais_id')->nullable()->after('speciality_id');
                $table->index('pais_id');
            }
        });


        // Migrate data: set pais_id = 234 (Venezuela) for users with location_id
        DB::table('users')
            ->whereNotNull('location_id')
            ->update(['pais_id' => 234]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('location_id');
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
            $table->unsignedBigInteger('location_id')->nullable()->after('pais_id');
        });
        DB::table('users')->update(['location_id' => DB::raw('pais_id')]);
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pais_id');
        });
    }
}

