<?php

namespace Database\Seeders;

use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentAttention;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Esto borra las citas viejas con 'null' y resetea el contador de IDs
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Appointment::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $firstSchedule = DoctorScheduleJoinHour::first();
        // Create specific appointment
        $appointment = Appointment::firstOrCreate(
            ['id' => 1],
            [
                'doctor_schedule_join_hour_id' => $firstSchedule ? $firstSchedule->id : null,
                'date_appointment' => '2026-02-17 08:00:00',
                'date_attention' => null,
                'amount' => 30,
                'cron_state' => 1,
                'status' => 1,
                'status_pay' => 2,
                'confimation' => 1,
                'laboratory' => 1,
                'patient_id' => 9,
                'doctor_id' => 3,
                'speciality_id' => 1,
                'user_id' => 9,
                'created_at' => '2025-02-16 20:41:51',
                'updated_at' => '2025-02-16 20:41:51',
                'deleted_at' => null
            ]
        );

        // Create related records for the specific appointment
        $faker = Faker::create();
        if ($appointment->status == 2) {
            AppointmentAttention::create([
                "appointment_id" => $appointment->id,
                "patient_id" => $appointment->patient_id,
                "description" => $faker->text($maxNbChars = 300),
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
        if ($appointment->status_pay == 2) {
            AppointmentPay::create([
                "appointment_id" => $appointment->id,
                "amount" => 50,
                "method_payment" => $faker->randomElement([
                    "Efectivo",
                    "Transferencia",
                    "Pago Movil",
                    "Zelle",
                    "Square",
                    "T.Debito",
                    "T.Credito"
                ]),
            ]);
        } else {

            AppointmentPay::create([
                "appointment_id" => $appointment->id,
                "amount" => $appointment->amount,
                "method_payment" => $faker->randomElement(["Efectivo", "Trasferencia", "Pago Movil", "Zelle", "Square", "T.Debito", "T.Credito"]),
            ]);
        }

        // Create additional random appointments for testing
        Appointment::factory()->count(9)->create([
            'doctor_schedule_join_hour_id' => $firstSchedule ? $firstSchedule->id : null
        ])->each(function ($p) use ($faker) {


            $faker = Faker::create();
            if ($p->status == 2) {
                AppointmentAttention::create([
                    "appointment_id" => $p->id,
                    "patient_id" => $p->patient_id,
                    "description" => $faker->text($maxNbChars = 300),

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
            if ($p->status_pay == 2) {
                AppointmentPay::create([
                    "appointment_id" => $p->id,
                    "amount" => 50,
                    "method_payment" => $faker->randomElement([
                        "Efectivo",
                        "Transferencia",
                        "Pago Movil",
                        "Zelle",
                        "Square",
                        "T.Debito",
                        "T.Credito"
                    ]),
                ]);
            } else {
                AppointmentPay::create([
                    "appointment_id" => $p->id,
                    "amount" => $p->amount,
                    "method_payment" => $faker->randomElement(["Efectivo", "Trasferencia", "Pago Movil", "Zelle", "Square", "T.Debito", "T.Credito"]),
                ]);
            }
        });
        // php artisan db:seed --class=AppointmentSeeder
    }
}
