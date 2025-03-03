<?php

namespace Database\Factories\Doctor;

use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\DoctorScheduleHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleJoinHourFactory extends Factory
{
    protected $model = DoctorScheduleJoinHour::class;

    public function definition()
    {
        return [
            'doctor_schedule_day_id' => \App\Models\Doctor\DoctorScheduleDay::factory(), // Create a related schedule day
            'doctor_schedule_hour_id' => DoctorScheduleHour::factory(), // Create a related schedule hour
        ];
    }
}
