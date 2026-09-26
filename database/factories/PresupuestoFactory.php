<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Presupuesto;
use App\Models\Patient\Patient;
use App\Models\Doctor\Specialitie;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresupuestoFactory extends Factory
{
    protected $model = Presupuesto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 🟢 LIMPIEZA EXTRA: Eliminamos la consulta muerta con 'ilike' que rompía el motor de MAMP
        $date_presupuesto = $this->faker->dateTimeBetween("2024-01-01 00:00:00", "2024-12-25 23:59:59");
        $status = $this->faker->randomElement([1, 2]);

        // Buscamos un doctor usando el método limpio de Spatie compatible con ambos motores
        $doctor = User::role('DOCTOR')->inRandomOrder()->first();

        return [
            // Si por alguna razón no hay doctores en el seeder, usamos una factoría o el ID 3 como salvaguarda
            "doctor_id" => $doctor ? $doctor->id : 3,
            "patient_id" => Patient::count() > 0 ? Patient::inRandomOrder()->first()->id : null,
            "description" => $this->faker->text(300),
            "diagnostico" => $this->faker->text(300),
            "medical" => json_encode([
                [
                    "name_medical" => $this->faker->word(),
                    "precio" => $this->faker->randomElement([100.00, 150.00, 200.00, 250.00, 80.00, 120.00, 95.00, 75.00, 160.00, 230.00, 110.00]),
                ],
            ]),
            "speciality_id" => Specialitie::count() > 0 ? Specialitie::all()->random()->id : null,
            "amount" => $this->faker->randomNumber(2),
            "status" => $status,
            "created_at" => $date_presupuesto,
            "updated_at" => $date_presupuesto,
        ];
    }
}