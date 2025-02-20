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
        });;
        // php artisan db:seed --class=PatientSeeder
    }
}
