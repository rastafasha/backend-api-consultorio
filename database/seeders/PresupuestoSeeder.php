<?php 

namespace Database\Seeders; 

use App\Models\Presupuesto; 
use Faker\Factory as Faker; 
use Illuminate\Database\Seeder; 

class PresupuestoSeeder extends Seeder 
{ 
    /** * Run the database seeds. */ 
    public function run(): void 
    { 
        $faker = Faker::create(); 

        // 1. Wipe old records safely in Postgres if running seeds individually
        \DB::statement('TRUNCATE TABLE presupuestos RESTART IDENTITY CASCADE;');

        // 2. Create specific appointment (Forcing ID 1)
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
                "medical" => json_encode([ 
                    [ "name_medical" => "Consulta", "precio" => 200.00, ], 
                    [ "name_medical" => "Consulta", "precio" => 145.50, ], 
                ]), 
                'amount' => 345.50, 
                'created_at' => '2025-02-16 20:41:51', 
                'updated_at' => '2025-02-16 20:41:51', 
                'deleted_at' => null 
            ] 
        ); 

        // ====================================================================================
        // 🔥 MAGIC LINE: Synchronize Postgres ID sequence so Factory knows to start at ID 2
        // ====================================================================================
        \DB::statement("SELECT setval(pg_get_serial_sequence('presupuestos', 'id'), coalesce(max(id), 0) + 1, false) FROM presupuestos;");

        // 3. Create additional random presupuestos safely
        Presupuesto::factory()->count(2)->create()->each(function($p) use ($faker) { 
            // Loops logic here if you need to generate child records later
        }); 
    } 
}
