<?php

namespace Database\Factories\Doctor;

use App\Models\Doctor\DoctorScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleDayFactory extends Factory
{
    protected $model = DoctorScheduleDay::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(), // Create a user for the schedule day
            'day' => $this->faker->randomElement(['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
