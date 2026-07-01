<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDoctorAddressIdToDoctorScheduleDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('doctor_schedule_days', function (Blueprint $table) {
        // Añadimos la columna de forma segura después de user_id
        $table->foreignId('doctor_address_id')
              ->nullable()
              ->after('user_id')
              ->constrained('doctor_addresses') // Vincula directamente con la tabla anterior
              ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::table('doctor_schedule_days', function (Blueprint $table) {
        $table->dropForeign(['doctor_address_id']);
        $table->dropColumn('doctor_address_id');
    });
}
}
