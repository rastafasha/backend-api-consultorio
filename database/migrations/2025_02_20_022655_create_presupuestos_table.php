<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePresupuestosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('amount', 250);
            // $table->string('amount')->nullable();
            $table->text('description')->nullable();
            $table->text('diagnostico')->nullable();
            $table->json('medical')->nullable();
            
            $table->tinyInteger('confimation')->default(1);
            $table->tinyInteger('status')->default(1);
            
            
            // Provider IDs
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();


            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            // $table->foreign('speciality_id')->references('id')->on('specialities')->nullOnDelete();
            // $table->foreign('doctor_schedule_join_hour_id')->references('id')->on('doctor_schedule_join_hours')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('presupuestos');
    }
}
