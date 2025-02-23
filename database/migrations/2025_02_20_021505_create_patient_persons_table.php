<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_persons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name_companion', 250)->nullable();
            $table->string('surname_companion', 250)->nullable();
            $table->string('mobile_companion', 250)->nullable();
            $table->string('relationship_companion', 250)->nullable();
            $table->string('name_responsable', 250)->nullable();
            $table->string('surname_responsable', 250)->nullable();
            $table->string('mobile_responsable', 50)->nullable();
            $table->string('relationship_responsable', 150)->nullable();
            $table->text('address')->nullable();

            
            // Provider IDs
            $table->unsignedBigInteger('patient_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_persons');
    }
}
