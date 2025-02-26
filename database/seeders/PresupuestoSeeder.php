<?php

namespace Database\Seeders;

use App\Models\Presupuesto;
use Faker\Factory as Faker;
use App\Models\PresupuestoItem;
use Illuminate\Database\Seeder;

class PresupuestoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific appointment
        $faker = Faker::create();
        $presupuesto = Presupuesto::firstOrCreate(
            ['id' => 1],
            [
                'status' => 1,
                'confimation' => 1,
                'patient_id' => 9,
                'doctor_id' => 3,
                'speciality_id' => 1,
                'description'=> 'Presupuesto para la atención del paciente 9',
                'diagnostico'=> 'Presupuesto para la atención del paciente 9',
                'amount' => 345.50,
                'created_at' => '2025-02-16 20:41:51',
                'updated_at' => '2025-02-16 20:41:51',
                'deleted_at' => null
            ]
        );

        // Create related records for the specific presupuesto
        if ($presupuesto) {
            PresupuestoItem::create([
                "presupuesto_id" => $presupuesto->id,
                "name_medical" => 'Presupuesto para la atención del paciente 9',
                'cantidad' => 4,
                'precio' => 345.50,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        } 

        // Create additional random presupuestos for testing
        $presupuestos = Presupuesto::factory()->count(2)->create()->each(function($p) use ($faker) {
            // Create related PresupuestoItems for each presupuesto
            for ($i = 0; $i < 3; $i++) {
                PresupuestoItem::create([
                    "presupuesto_id" => $p->id,
                    "name_medical" => $faker->text(50),
                    'cantidad' => $faker->numberBetween(1, 10),
                    'precio' => $faker->randomFloat(2, 10, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }
        });
    }
}
