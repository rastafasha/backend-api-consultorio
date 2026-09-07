<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOdontogramasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('odontogramas', function (Blueprint $table) {
        $key = 'id';
        $table->bigIncrements($key);
        
        // Relaciones con tus tablas reales de Klyntic
        $table->unsignedBigInteger('patient_id');
        $table->unsignedBigInteger('doctor_id')->nullable();
        $table->unsignedBigInteger('appointment_id')->nullable();
        
        // Campos específicos del Odontograma
        $table->integer('diente_numero');
        $table->string('cara_diente', 10)->nullable(); // 'O', 'M', 'D', 'V', 'L', 'P'
        $table->string('hallazgo', 150); // 'Caries', 'Resina', 'Ausente', etc.
        $table->text('notas')->nullable();
        
        $table->timestamps();
        $table->softDeletes(); // Por si el doctor borra un hallazgo por error

        // Cables invisibles (Foreign Keys) amarrados a tus tablas actuales
        $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
        $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
        
        // Índice para que Angular cargue el odontograma en milisegundos
        $table->index('patient_id');
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('odontogramas');
    }
}
