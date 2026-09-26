<?php

namespace Database\Seeders;

use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentAttention;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\DoctorAddress;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // 🟢 1. LIMPIEZA DE SEGURIDAD NEUTRA (Funciona en MySQL/MAMP y Postgres/Supabase)
        Schema::disableForeignKeyConstraints();
        DB::table('appointment_attentions')->truncate();
        DB::table('appointment_pays')->truncate();
        DB::table('appointments')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. SALVAGUARDA DE DIRECCIÓN: Aseguramos que el doctor 3 tenga al menos un consultorio activo
        $doctor_id = 3;
        $address = DoctorAddress::firstOrCreate(
            ['user_id' => $doctor_id],
            [
                'name_consultorio' => 'Consultorio Clínico Central',
                'address' => 'Av. Francisco de Miranda, Edif. Centro, Piso 3',
                'is_active' => 1
            ]
        );

        $firstSchedule = DoctorScheduleJoinHour::first();

        // 3. Forzar que el día de la agenda apunte a este consultorio si no tiene uno asignado
        if ($firstSchedule && $firstSchedule->doctor_schedule_day) {
            $day = $firstSchedule->doctor_schedule_day;
            if (!$day->doctor_address_id) {
                $day->update(['doctor_address_id' => $address->id]);
            }
        }

        // 4. Crear la cita específica (ID: 1)
        $appointment = Appointment::firstOrCreate(
            ['id' => 1],
            [
                'doctor_schedule_join_hour_id' => $firstSchedule ? $firstSchedule->id : null,
                'date_appointment' => '2026-07-02 09:00:00', 
                'date_attention' => null,
                'amount' => 30,
                'cron_state' => 1,
                'status' => 1,
                'status_pay' => 2,
                'confimation' => 1,
                'laboratory' => 1,
                'patient_id' => 9,
                'doctor_id' => $doctor_id,
                'speciality_id' => 1,
                'user_id' => 9,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ]
        );

        // Crear registros médicos vinculados a la cita 1
        if ($appointment->status == 2) {
            AppointmentAttention::create([
                "appointment_id" => $appointment->id,
                "patient_id" => $appointment->patient_id,
                "description" => $faker->text(300),
                "receta_medica" => json_encode([
                    [
                        "name_medical" => $faker->word(),
                        "uso" => $faker->sentence(3),
                        "dosis" => $faker->randomElement(['1x día', '2x día', '3x día']),
                        "duracion" => $faker->randomElement(['7 días', '14 días', '30 días'])
                    ],
                ])
            ]);
        }

        AppointmentPay::create([
            "appointment_id" => $appointment->id,
            "amount" => $appointment->status_pay == 2 ? 50 : $appointment->amount,
            "method_payment" => $faker->randomElement(["Efectivo", "Transferencia", "Pago Movil", "Zelle"]),
        ]);

        // ====================================================================================
        // 🟢 5. REINICIO DE SECUENCIAS INTELIGENTE MULTI-MOTOR (MySQL e iñigo Postgres)
        // ====================================================================================
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('appointments', 'id'), coalesce(max(id), 0) + 1, false) FROM appointments;");
        }

        // 6. Crear las 9 citas aleatorias restantes a través del Factory
        Appointment::factory()->count(9)->create([
            'doctor_schedule_join_hour_id' => $firstSchedule ? $firstSchedule->id : null,
            'doctor_id' => $doctor_id
        ])->each(function ($p) use ($faker) {

            if ($p->status == 2) {
                AppointmentAttention::create([
                    "appointment_id" => $p->id,
                    "patient_id" => $p->patient_id,
                    "description" => $faker->text(300),
                    "receta_medica" => json_encode([
                        [
                            "name_medical" => $faker->word(),
                            "uso" => $faker->sentence(3),
                            "dosis" => $faker->randomElement(['1x día', '2x día', '3x día']),
                            "duracion" => $faker->randomElement(['7 días', '14 días', '30 días'])
                        ],
                    ])
                ]);
            }

            AppointmentPay::create([
                "appointment_id" => $p->id,
                "amount" => $p->status_pay == 2 ? 50 : $p->amount,
                "method_payment" => $faker->randomElement(["Efectivo", "Transferencia", "Pago Movil", "Zelle"]),
            ]);
        });
    }
}