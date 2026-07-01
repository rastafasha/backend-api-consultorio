<?php

namespace Database\Factories\Appointment;

use App\Models\User;
use App\Models\Patient\Patient;
use App\Models\Doctor\Specialitie;
use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

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
        // 1. Buscamos un horario que YA exista en la base de datos junto con su día
        $schedule = DoctorScheduleJoinHour::with('doctor_schedule_day')->inRandomOrder()->first();
        
        // 2. Extraer el doctor y el ID de horario con salvaguarda
        $doctor_id = $schedule ? $schedule->doctor_schedule_day->user_id : 3;
        $schedule_id = $schedule ? $schedule->id : 1;

        // 3. Generar una fecha base aleatoria entre 2024 y 2026
        $randomDate = $this->faker->dateTimeBetween("2024-01-01", "2026-12-31");
        $carbonDate = Carbon::instance($randomDate);

        // COHERENCIA CRÍTICA: Ajustamos la fecha para que caiga EXACTAMENTE en el día de la semana del horario
        if ($schedule && $schedule->doctor_schedule_day) {
            $targetDayName = strtolower($schedule->doctor_schedule_day->day); // ej: "jueves" o "viernes"
            
            // Diccionario para convertir el string de tu BD al ID de día de Carbon
            $daysOfWeek = [
                'lunes' => Carbon::MONDAY,
                'martes' => Carbon::TUESDAY,
                'miercoles' => Carbon::WEDNESDAY,
                'jueves' => Carbon::THURSDAY,
                'viernes' => Carbon::FRIDAY,
                'sabado' => Carbon::SATURDAY,
                'domingo' => Carbon::SUNDAY,
            ];

            if (array_key_exists($targetDayName, $daysOfWeek)) {
                // Forzamos a Carbon a moverse al día de la semana correspondiente
                $carbonDate->next($daysOfWeek[$targetDayName]);
            }
        }

        $date_appointment = $carbonDate->format('Y-m-d H:i:s');
        $status = $this->faker->randomElement([1, 2]);

        return [
            'doctor_id' => $doctor_id, 
            "patient_id" => Patient::inRandomOrder()->first()?->id ?? 1,
            "date_appointment" => $date_appointment,
            "speciality_id" => Specialitie::inRandomOrder()->first()?->id ?? 1,
            'doctor_schedule_join_hour_id' => $schedule_id, 
            "user_id" => User::role('ADMIN')->inRandomOrder()->first()?->id ?? 1,
            "amount" => $this->faker->randomElement([100, 150, 200, 250, 80, 120, 95, 75, 160, 230, 110]),
            "status" => $status,
            "status_pay" => $this->faker->randomElement([1, 2]),
            "date_attention" => $status == 2 ? Carbon::parse($date_appointment)->addMinutes(30)->format('Y-m-d H:i:s') : NULL,
        ];
    }
}
