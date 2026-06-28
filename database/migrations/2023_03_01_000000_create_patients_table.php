<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable(); 
            $table->string('mongo_user_id')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('name', 250);
            $table->string('surname', 250);
            $table->string('email', 250)->nullable();
            $table->string('n_doc')->nullable();
            $table->string('phone', 25)->nullable();
            
            $table->text('address')->nullable();
            $table->tinyInteger('gender')->default(1);
            $table->timestamp('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->string('education', 150)->nullable();
            $table->text('antecedent_family')->nullable();
            $table->text('antecedent_personal')->nullable();
            $table->text('antecedent_alerg')->nullable();
            $table->text('current_desease')->nullable();
            $table->string('ta', 25)->nullable();
            $table->string('fc', 25)->nullable();
            $table->string('fr', 25)->nullable();
            $table->string('temperature', 25)->nullable();
            $table->string('peso', 250)->nullable();
            $table->string('talla', 250)->nullable();
            $table->string('historia_enfermedad', 250)->nullable();
            $table->string('enfermedad_actual', 250)->nullable();
            $table->string('tratamiento', 250)->nullable();
            $table->string('examen_fisico', 250)->nullable();
            $table->string('reporte_laboratorio', 250)->nullable();
            $table->json('evolucion')->nullable();
            $table->json('vacunas')->nullable();
            $table->string('peso_al_nacer', 250)->nullable();
            $table->string('talla_al_nacer', 250)->nullable();
            

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
