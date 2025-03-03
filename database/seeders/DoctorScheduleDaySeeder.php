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
