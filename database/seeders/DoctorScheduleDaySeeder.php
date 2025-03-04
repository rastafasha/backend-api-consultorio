<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Add this line to import the User model
use App\Models\Doctor\DoctorScheduleDay;

class DoctorScheduleDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
        // Create sample doctor schedule days
        $doctorUser = User::whereHas('roles', function($query) {
            $query->where('name', 'DOCTOR');
        })->first();

        DoctorScheduleDay::create([
            'user_id' => $doctorUser->id, // Use the ID of the first doctor user found
            'day' => '2023-01-01',
        ]);

        DoctorScheduleDay::create([
            'user_id' => $doctorUser->id,
            'day' => '2023-01-02',
        ]);

        // Add more records as needed
    }
}
