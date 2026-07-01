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
        
        // Sintaxis moderna y segura para la relación con la tabla 'users'
        $table->foreignId('user_id')
              ->constrained('users') // Busca automáticamente la tabla 'users' y su campo 'id'
              ->onDelete('cascade');

        $table->string('name_consultorio', 150)->nullable();
        $table->text('address');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
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
