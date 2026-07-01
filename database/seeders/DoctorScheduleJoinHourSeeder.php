<?php

namespace Database\Seeders;

use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorAddress;
use Illuminate\Database\Seeder;

class DoctorScheduleJoinHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. SALVAGUARDA: Asegurar que existan las direcciones de consultorio de prueba
        $address1 = DoctorAddress::firstOrCreate(
            ['id' => 1],
            [
                'user_id' => 3, // ID del doctor de pruebas
                'name_consultorio' => 'Consultorio Clínico Norte',
                'address' => 'Avenida Principal Norte, Edificio Médico, Local 4',
                'is_active' => 1
            ]
        );

        $address2 = DoctorAddress::firstOrCreate(
            ['id' => 2],
            [
                'user_id' => 3,
                'name_consultorio' => 'Clínica Integral del Sur',
                'address' => 'Calle Secundaria Sur, Centro Médico San Lucas, Piso 2',
                'is_active' => 1
            ]
        );

        // 2. SALVAGUARDA: Asegurar que existan los días de agenda enlazados a sus consultorios
        // Tu consulta del frontend busca "jueves" (para la fecha 2026-07-02), configuramos los días en base a ello
        DoctorScheduleDay::firstOrCreate(
            ['id' => 1],
            [
                'user_id' => 3,
                'doctor_address_id' => $address1->id, // Vinculado al consultorio 1
                'day' => 'jueves',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        DoctorScheduleDay::firstOrCreate(
            ['id' => 2],
            [
                'user_id' => 3,
                'doctor_address_id' => $address2->id, // Vinculado al consultorio 2
                'day' => 'viernes',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 3. Insertar tus registros de segmentos horarios estructurados
        $joinHours = [
            [
                'id' => 1,
                'doctor_schedule_day_id' => 1, // Jueves - Consultorio Norte
                'doctor_schedule_hour_id' => 1,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'doctor_schedule_day_id' => 1, // Jueves - Consultorio Norte
                'doctor_schedule_hour_id' => 2,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'doctor_schedule_day_id' => 2, // Viernes - Clínica Sur
                'doctor_schedule_hour_id' => 3,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 4,
                'doctor_schedule_day_id' => 2, // Viernes - Clínica Sur
                'doctor_schedule_hour_id' => 4,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'doctor_schedule_day_id' => 2, // Viernes - Clínica Sur
                'doctor_schedule_hour_id' => 1,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ]
        ];

        foreach ($joinHours as $joinHour) {
            DoctorScheduleJoinHour::updateOrCreate(
                ['id' => $joinHour['id']],
                $joinHour
            );
        }
    }
}
