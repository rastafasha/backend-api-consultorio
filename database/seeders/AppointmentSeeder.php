<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Appointment\AppointmentAttention;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific appointment
        $appointment = Appointment::firstOrCreate(
            ['id' => 1],
            [
                'date_appointment' => '2025-02-17 08:00:00',
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
                'doctor_schedule_join_hour_id' => 1,
                'created_at' => '2025-02-16 20:41:51',
                'updated_at' => '2025-02-16 20:41:51',
                'deleted_at' => null
            ]
        );

        // Create related records for the specific appointment
        $faker = Faker::create();
        if($appointment->status == 2){
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
        if($appointment->status_pay == 2){
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
                "method_payment" => $faker->randomElement(["Efectivo","Trasferencia","Pago Movil","Zelle","Square", "T.Debito", "T.Credito"]),
            ]);
        }

        // Create additional random appointments for testing
        Appointment::factory()->count(2)->create()->each(function($p) use ($faker) {
            $faker = Faker::create();
            if($p->status == 2){
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
            if($p->status_pay == 2){
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
            }else{
                AppointmentPay::create([
                    "appointment_id" => $p->id,
                    "amount" => $p->amount,
                    "method_payment" => $faker->randomElement(["Efectivo","Trasferencia","Pago Movil","Zelle","Square", "T.Debito", "T.Credito"]),
                ]);
            }
        });
        // php artisan db:seed --class=AppointmentSeeder
    }
}
