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
                'doctor_schedule_day_id' => 1,
                'doctor_schedule_hour_id' => 1, // Valid ID
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'doctor_schedule_day_id' => 1,
                'doctor_schedule_hour_id' => 2, // Valid ID
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'doctor_schedule_day_id' => 2,
                'doctor_schedule_hour_id' => 3, // Valid ID
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'doctor_schedule_day_id' => 2,
                'doctor_schedule_hour_id' => 4, // Valid ID
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'doctor_schedule_day_id' => 2,
                'doctor_schedule_hour_id' => 1, // Valid ID
                'created_at' => now(),
                'updated_at' => now(),
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
