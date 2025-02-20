<?php

namespace Database\Seeders;

use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Database\Seeder;

class DoctorScheduleJoinHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $joinHours = [
            [
                'id' => 1,
                'doctor_schedule_day_id' => 1, // Changed from 15 to 1
                'doctor_schedule_hour_id' => 1,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'doctor_schedule_day_id' => 1, // Changed from 15 to 1
                'doctor_schedule_hour_id' => 2,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'doctor_schedule_day_id' => 2, // Changed from 15 to 2
                'doctor_schedule_hour_id' => 3,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 4,
                'doctor_schedule_day_id' => 2, // Changed from 15 to 2
                'doctor_schedule_hour_id' => 4,
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'doctor_schedule_day_id' => 2, // Changed from 16 to 2
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
