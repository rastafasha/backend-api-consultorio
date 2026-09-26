<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeyFromUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
    // 1. Rompemos el candado físico que te hace rebotar las actualizaciones
    $table->dropForeign('users_clinica_id_foreign');
    
    // 2. Lo dejamos como un índice simple e independiente para mantener la velocidad
    $table->index('clinica_id');
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
            //
        });
    }
}
