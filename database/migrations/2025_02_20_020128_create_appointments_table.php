<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('amount', 250)->nullable();
            $table->tinyInteger('cron_state')->default(1);
            $table->tinyInteger('confimation')->default(1);
            $table->timestamp('date_appointment')->nullable();
            $table->timestamp('date_attention')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('status_pay')->default(1);
            $table->tinyInteger('laboratory')->default(1);

            
            // Provider IDs
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();
            $table->unsignedBigInteger('doctor_schedule_join_hour_id')->nullable();


            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            // $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
