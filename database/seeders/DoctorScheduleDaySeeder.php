<?php

namespace Database\Seeders;

use App\Models\Doctor\DoctorScheduleDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DoctorScheduleDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a doctor user
        $doctor = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%DOCTOR%');
        })->first();

        if (!$doctor) {
            // Create a new doctor user if none exists
            $doctor = User::factory()->create();
            $doctorRole = Role::firstOrCreate(['name' => 'DOCTOR']);
            $doctor->assignRole($doctorRole);
        }

        $scheduleDays = [
            [
                'id' => 1,
                'user_id' => $doctor->id,
                'day' => 'Lunes',
                'created_at' => '2023-12-09 16:13:30',
                'updated_at' => '2023-12-09 16:13:30',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'user_id' => $doctor->id,
                'day' => 'Lunes',
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'user_id' => $doctor->id,
                'day' => 'Martes',
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 4,
                'user_id' => $doctor->id,
                'day' => 'Miercoles',
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'user_id' => $doctor->id,
                'day' => 'Jueves',
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 6,
                'user_id' => $doctor->id,
                'day' => 'Viernes',
                'created_at' => '2023-12-10 01:35:40',
                'updated_at' => '2023-12-10 01:35:40',
                'deleted_at' => null
            ],
            [
                'id' => 7,
                'user_id' => $doctor->id,
                'day' => 'Lunes',
                'created_at' => '2023-12-10 01:37:54',
                'updated_at' => '2023-12-10 01:37:54',
                'deleted_at' => null
            ]
        ];

        foreach ($scheduleDays as $scheduleDay) {
            DoctorScheduleDay::updateOrCreate(
                ['id' => $scheduleDay['id']],
                $scheduleDay
            );
        }
    }
}
