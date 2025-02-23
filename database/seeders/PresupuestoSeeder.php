<?php

namespace Database\Seeders;

use App\Models\Presupuesto;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use App\Models\Appointment\AppointmentPay;
use App\Models\Appointment\AppointmentAttention;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PresupuestoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific appointment
        $faker = Faker::create();
        $presupuesto = Presupuesto::updateOrCreate(
            ['id' => 1],
            [
                
                'status' => 1,
                'confimation' => 1,
                'patient_id' => 9,
                'doctor_id' => 3,
                'speciality_id' => 1,
                'description'=> 'Presupuesto para la atención del paciente 9',
                'diagnostico'=> 'Presupuesto para la atención del paciente 9',
                "medical" => json_encode([
                    [
                        "name_medical" => "Consulta",
                        "precio" => 200.00,
                    ]
                ]),
                'amount' => 345.50,
                'created_at' => '2025-02-16 20:41:51',
                'updated_at' => '2025-02-16 20:41:51',
                'deleted_at' => null
            ]
        );

        // Create related records for the specific presupuesto
        $faker = Faker::create();
        

        // Create additional random presupuestos for testing
        Presupuesto::factory()->count(2)->create()->each(function($p) use ($faker) {
            $p->status = $faker->randomElement([1, 0]);
            $p->confimation = $faker->randomElement([1, 0]);
            $p->patient_id = $faker->numberBetween(1, 10);
            $p->doctor_id = $faker->numberBetween(1, 5);
            $p->speciality_id = $faker->numberBetween(1, 3);
            $p->description = $faker->sentence;
            $p->diagnostico = $faker->sentence;
            $p->amount = $faker->randomFloat(2, 100, 500);
            $p->created_at = now();
            $p->updated_at = now();
            $p->save();
            
        });
        // php artisan db:seed --class=presupuestoSeeder
    }
}
