<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorScheduleHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_schedule_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
        
            $table->string('hour_start', 50)->default('00:00:00');
            $table->string('hour_end', 50)->default('00:00:00');
            $table->string('hour', 20)->default('00:00');
            
            $table->unsignedBigInteger('doctor_schedule_day_id')->nullable(); // Add foreign key for doctor_schedule_day and allow null values
            $table->foreign('doctor_schedule_day_id')->references('id')->on('doctor_schedule_days')->onDelete('cascade');
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
        Schema::dropIfExists('doctor_schedule_hours');
    }
}
