<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentAttentionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointment_attentions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id')->nullable(); 
            $table->unsignedBigInteger('appointment_id')->nullable(); 
            $table->text('description')->nullable();
            $table->json('receta_medica')->nullable();

            $table->tinyInteger('laboratory')->default(1);

            
            // Provider IDs
           
            // Foreign keys for provider relationships
            $table->foreign('patient_id')
              ->references('id')
              ->on('patients')
              ->onDelete('set null');
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            
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
        Schema::dropIfExists('appointment_attentions');
    }
}
