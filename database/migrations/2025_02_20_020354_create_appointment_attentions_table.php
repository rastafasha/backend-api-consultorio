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
        
            $table->text('description')->nullable();
            $table->json('receta_medica')->nullable();

            $table->tinyInteger('laboratory')->default(1);

            
            // Provider IDs
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            // $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            // $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
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
