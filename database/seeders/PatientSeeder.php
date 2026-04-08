<?php

namespace Database\Seeders;

use App\Models\Appointment\Appointment;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientPerson;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Patient::factory()->count(20)->create()->each(function ($p) {
            $faker = \Faker\Factory::create();

            // 1. ELEGIMOS AL DOCTOR (Siempre debe haber uno)
            $doctor = User::role('DOCTOR')->inRandomOrder()->first();

            if ($doctor) {
                // Vinculamos SIEMPRE al médico con el paciente en la tabla intermedia
                $p->doctors()->attach($doctor->id);
            }

            // 2. Crear el acompañante (Siempre)
            PatientPerson::create([
                "patient_id" => $p->id,
                "name_companion" => $faker->name(),
                "surname_companion" => $faker->lastName(),
                "mobile_companion" => $faker->phoneNumber(),
                "relationship_companion" => $faker->randomElement(["Tio", "Mama", "Papa", "Hermano"]),
                "name_responsable" => $faker->name(),
                "surname_responsable" => $faker->lastName(),
                "mobile_responsable" => $faker->phoneNumber(),
                "relationship_responsable" => $faker->randomElement(["Tio", "Mama", "Papa", "Hermano"]),
            ]);

            // 3. Crear SIEMPRE una CITA (Para que el doctor vea al paciente)
            // El user_id será null si el paciente no tiene cuenta aún
            $appointment = Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $p->id,
                'user_id' => null, // Se actualizará abajo si se crea el usuario
                'date_appointment' => now()->addDays(rand(1, 10)),
                'speciality_id' => $doctor->speciality_id,
                'status' => 1,
                'amount' => $doctor->precio_cita ?? 0
            ]);

            // 4. Registro en App (Solo el 50%)
            if ($faker->boolean()) {
                $user = User::create([
                    'name' => $p->name,
                    'surname' => $p->surname,
                    'email' => $p->email,
                    'password' => bcrypt('password'),
                    'n_doc' => $p->n_doc,
                ]);
                $user->assignRole(User::GUEST);

                // Actualizamos el paciente y la cita con el nuevo user_id (ID 12)
                $p->update(['user_id' => $user->id]);
                $appointment->update(['user_id' => $user->id]);
            }
        });

    }

}
