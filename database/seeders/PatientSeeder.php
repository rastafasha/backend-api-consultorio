<?php

namespace Database\Seeders;

use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleJoinHour; // Importación obligatoria
use App\Models\Patient\Patient;
use App\Models\Patient\PatientPerson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // Creamos 20 pacientes usando el factory base
        Patient::factory()->count(20)->create()->each(function ($p) {
            $faker = \Faker\Factory::create();

            // 1. ELEGIMOS AL DOCTOR (Filtramos por rol DOCTOR)
            $doctor = User::role('DOCTOR')->inRandomOrder()->first();

            if (!$doctor) {
                return; // Si no hay doctores en el sistema, saltamos para evitar errores
            }

            // Vinculamos al médico con el paciente en la tabla intermedia de MySQL
            $p->doctors()->attach($doctor->id);

            // Actualizamos en la ficha médica el puente hacia MongoDB usando el ID del doctor
            $p->update(['mongo_user_id' => (string) $doctor->id]);

            // 2. Crear el acompañante (Tu lógica intacta)
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

            // 🧠 AJUSTE CLAVE KLYNTIC: Buscamos un horario real que tenga asignado este doctor
            // Esto evita que el comando de notificaciones explote al buscar relaciones nulas
            $horarioDoctor = DoctorScheduleJoinHour::whereHas('doctor_schedule_day', function($query) use ($doctor) {
                $query->where('user_id', $doctor->id);
            })->inRandomOrder()->first();

            // 3. Crear el usuario de acceso para el Paciente (100% obligatorio por tu nueva regla de negocio)
            $user = User::create([
                'name'     => $p->name,
                'surname'  => $p->surname,
                'email'    => $p->email,
                'password' => Hash::make($p->n_doc), // Contraseña por defecto: Su cédula
                'n_doc'    => $p->n_doc,
                'status'   => 1
            ]);
            $user->assignRole(User::GUEST);

            // Vinculamos la ficha médica con la cuenta de acceso recién nacida
            $p->update(['user_id' => $user->id]);

            // 4. Creamos la CITA asignada para el día de HOY para poder probar tus comandos de WhatsApp
            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $p->id,
                'user_id' => $user->id, 
                'date_appointment' => now()->format('Y-m-d'), // Cita para HOY
                'doctor_schedule_join_hour_id' => $horarioDoctor ? $horarioDoctor->id : 1, // Amarramos el horario médico
                'speciality_id' => $doctor->speciality_id,
                'status' => 1,
                'cron_state' => 1, // Estado inicial: Pendiente por notificar
                'amount' => $doctor->precio_cita ?? 0
            ]);
        });
    }
}
