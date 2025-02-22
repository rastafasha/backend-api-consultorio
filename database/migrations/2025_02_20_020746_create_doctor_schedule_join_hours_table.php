<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorScheduleJoinHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_schedule_join_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('doctor_schedule_day_id')->nullable();
            $table->unsignedBigInteger('doctor_schedule_hour_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('doctor_schedule_day_id')->references('id')->on('doctor_schedule_days')->nullOnDelete();
            $table->foreign('doctor_schedule_hour_id')->references('id')->on('doctor_schedule_hours')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_schedule_join_hours');
    }
}
