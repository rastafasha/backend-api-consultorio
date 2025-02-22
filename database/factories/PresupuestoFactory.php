<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Presupuesto;
use App\Models\Patient\Patient;
use App\Models\Doctor\Specialitie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presupuesto>
 */
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
        $doctor = User::whereHas("roles",function($q){
            $q->where("name","like","%DOCTOR%");
        })->inRandomOrder()->first();
        
        $date_presupuesto = $this->faker->dateTimeBetween("2024-01-01 00:00:00", "2024-12-25 23:59:59");
        $status = $this->faker->randomElement([1, 2]);
        

        return [
            "doctor_id" => User::role('DOCTOR')->inRandomOrder()->first()->id,
            "patient_id" => Patient::count() > 0 ? Patient::inRandomOrder()->first()->id : null,
            "description" => $this->faker->text($maxNbChars = 300),
            "diagnostico" => $this->faker->text($maxNbChars = 300),
            "medical" => json_encode([
                    [
                        "name_medical" => $this->faker->word(),
                        "precio" =>  $this->faker->randomElement([100.00,150.00,200.00,250.00,80.00,120.00,95.00,75.00,160.00,230.00,110.00]),
                    ],
                ]),
            "speciality_id" => Specialitie::count() > 0 ? Specialitie::all()->random()->id : null,
            "amount" => $this->faker->randomnumber(2),
            "status" => $status,
        ];
    }
}
