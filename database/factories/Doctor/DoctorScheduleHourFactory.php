<?php

namespace Database\Factories\Doctor;

use App\Models\Doctor\DoctorScheduleHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleHourFactory extends Factory
{
    protected $model = DoctorScheduleHour::class;

    public function definition()
    {
        $hourStart = $this->faker->time();
        $hourEnd = date('H:i:s', strtotime($hourStart) + 3600); // Add 1 hour to hour_start

        return [
            'hour_start' => $hourStart,
            'hour_end' => $hourEnd,
            'doctor_schedule_day_id' => \App\Models\Doctor\DoctorScheduleDay::factory()->create()->id, // Create a schedule day dynamically
            'hour' => $this->faker->time(),        // Example field for hour
        ];
    }
}
