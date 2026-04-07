<?php

namespace Database\Seeders;

use App\Models\Patient\Patient;
use Illuminate\Database\Seeder;
use App\Models\Patient\PatientPerson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    Patient::factory()->count(20)->create()->each(function($p) {
        $faker = \Faker\Factory::create();
        
        // 1. Crear el acompañante (lo que ya tenías)
        PatientPerson::create([ 
            "patient_id" => $p->id,
            "name_companion" => $faker->name(),
            "surname_companion" => $faker->lastName(),
            "mobile_companion" => $faker->phoneNumber(),
            "relationship_companion" => $faker->randomElement(["Tio","Mama","Papa","Hermano"]),
            "name_responsable" => $faker->name(),
            "surname_responsable" => $faker->lastName(),
            "mobile_responsable" => $faker->phoneNumber(),
            "relationship_responsable" => $faker->randomElement(["Tio","Mama","Papa","Hermano"]),
        ]);

        // 2. OPCIONAL: Simular que el 50% de los pacientes ya se registró en la app
        if ($faker->boolean()) {
            $user = \App\Models\User::create([
                'name' => $p->name,
                'surname' => $p->surname,
                'email' => $p->email ?? $faker->unique()->safeEmail(),
                'password' => bcrypt('password'),
                'n_doc' => $p->n_doc,
            ]);

            $user->assignRole(\App\Models\User::GUEST);

            // Vinculamos el ID del usuario (12) al paciente (21)
            $p->update(['user_id' => $user->id]);
        }
    });
}

}
