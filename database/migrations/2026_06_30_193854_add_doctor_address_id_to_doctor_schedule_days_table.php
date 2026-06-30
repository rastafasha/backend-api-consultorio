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
        // Añadimos la columna aceptando null temporalmente para no romper datos viejos
        $table->unsignedBigInteger('doctor_address_id')->nullable()->after('user_id');
        
        $table->foreign('doctor_address_id')->references('id')->on('doctor_addresses')->onDelete('cascade');
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
