<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('doctor_addresses', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('user_id'); // Enlace al doctor (User)
        $table->string('name_consultorio', 150)->nullable(); // Ej: "Consultorio Clínico Norte"
        $table->text('address'); // Dirección física que antes estaba fija
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();

        // Clave foránea apuntando a tu tabla de usuarios/médicos
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_addresses');
    }
}
