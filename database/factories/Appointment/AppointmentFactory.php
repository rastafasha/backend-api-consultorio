<?php

namespace Database\Factories\Appointment;

use App\Models\User;
use App\Models\Patient\Patient;
use App\Models\Doctor\Specialitie;
use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1. Buscamos un horario que YA exista en la base de datos
        // Esto nos asegura que el doctor_id y el horario coincidan perfectamente
       $schedule = DoctorScheduleJoinHour::with('doctor_schedule_day.doctor')->inRandomOrder()->first();
        // 2. Si no hay horarios creados, usamos IDs por defecto (ajusta según tus seeders)
        $doctor_id = $schedule ? $schedule->doctor_schedule_day->user_id : 3;
        $schedule_id = $schedule ? $schedule->id : 1;

        $date_appointment = $this->faker->dateTimeBetween("2024-01-01 00:00:00", "2026-01-01 23:59:59");
        $status = $this->faker->randomElement([1, 2]);

        return [
            'doctor_id' => $schedule ? $schedule->doctor_schedule_day->user_id : 3, // Usamos el doctor que es dueño del horario
            "patient_id" => Patient::inRandomOrder()->first()?->id ?? 1,
            "date_appointment" => $date_appointment,
            "speciality_id" => Specialitie::inRandomOrder()->first()?->id ?? 1,
            'doctor_schedule_join_hour_id' => $schedule_id, // <--- ID dinámico y real
            "user_id" => User::role('ADMIN')->inRandomOrder()->first()?->id ?? 1,
            "amount" => $this->faker->randomElement([100, 150, 200, 250, 80, 120, 95, 75, 160, 230, 110]),
            "status" => $status,
            "status_pay" => $this->faker->randomElement([1, 2]),
            "date_attention" => $status == 2 ? $this->faker->dateTimeBetween($date_appointment, "2026-12-25 23:59:59") : NULL,
        ];
    }

}
