<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorScheduleHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hours = [
            ['hour_start' => '08:00:00', 'hour_end' => '08:15:00', 'hour' => '08'],
            ['hour_start' => '08:15:00', 'hour_end' => '08:30:00', 'hour' => '08'],
            ['hour_start' => '08:30:00', 'hour_end' => '08:45:00', 'hour' => '08'],
            ['hour_start' => '08:45:00', 'hour_end' => '09:00:00', 'hour' => '08'],
            ['hour_start' => '09:00:00', 'hour_end' => '09:15:00', 'hour' => '09'],
            ['hour_start' => '09:15:00', 'hour_end' => '09:30:00', 'hour' => '09'],
            ['hour_start' => '09:30:00', 'hour_end' => '09:45:00', 'hour' => '09'],
            ['hour_start' => '09:45:00', 'hour_end' => '10:00:00', 'hour' => '09'],
            ['hour_start' => '10:00:00', 'hour_end' => '10:15:00', 'hour' => '10'],
            ['hour_start' => '10:15:00', 'hour_end' => '10:30:00', 'hour' => '10'],
            ['hour_start' => '10:30:00', 'hour_end' => '10:45:00', 'hour' => '10'],
            ['hour_start' => '10:45:00', 'hour_end' => '11:00:00', 'hour' => '10'],
            ['hour_start' => '11:00:00', 'hour_end' => '11:15:00', 'hour' => '11'],
            ['hour_start' => '11:15:00', 'hour_end' => '11:30:00', 'hour' => '11'],
            ['hour_start' => '11:30:00', 'hour_end' => '11:45:00', 'hour' => '11'],
            ['hour_start' => '11:45:00', 'hour_end' => '12:00:00', 'hour' => '11'],
            ['hour_start' => '12:00:00', 'hour_end' => '12:15:00', 'hour' => '12'],
            ['hour_start' => '12:15:00', 'hour_end' => '12:30:00', 'hour' => '12'],
            ['hour_start' => '12:30:00', 'hour_end' => '12:45:00', 'hour' => '12'],
            ['hour_start' => '12:45:00', 'hour_end' => '13:00:00', 'hour' => '12'],
            ['hour_start' => '13:00:00', 'hour_end' => '13:15:00', 'hour' => '13'],
            ['hour_start' => '13:15:00', 'hour_end' => '13:30:00', 'hour' => '13'],
            ['hour_start' => '13:30:00', 'hour_end' => '13:45:00', 'hour' => '13'],
            ['hour_start' => '13:45:00', 'hour_end' => '14:00:00', 'hour' => '13'],
            ['hour_start' => '14:00:00', 'hour_end' => '14:15:00', 'hour' => '14'],
            ['hour_start' => '14:15:00', 'hour_end' => '14:30:00', 'hour' => '14'],
            ['hour_start' => '14:30:00', 'hour_end' => '14:45:00', 'hour' => '14'],
            ['hour_start' => '14:45:00', 'hour_end' => '15:00:00', 'hour' => '14'],
            ['hour_start' => '15:00:00', 'hour_end' => '15:15:00', 'hour' => '15'],
            ['hour_start' => '15:15:00', 'hour_end' => '15:30:00', 'hour' => '15'],
            ['hour_start' => '15:30:00', 'hour_end' => '15:45:00', 'hour' => '15'],
            ['hour_start' => '15:45:00', 'hour_end' => '16:00:00', 'hour' => '15'],
            ['hour_start' => '16:00:00', 'hour_end' => '16:15:00', 'hour' => '16'],
            ['hour_start' => '16:15:00', 'hour_end' => '16:30:00', 'hour' => '16'],
            ['hour_start' => '16:30:00', 'hour_end' => '16:45:00', 'hour' => '16'],
            ['hour_start' => '16:45:00', 'hour_end' => '17:00:00', 'hour' => '16'],
            ['hour_start' => '17:00:00', 'hour_end' => '17:15:00', 'hour' => '17'],
            ['hour_start' => '17:15:00', 'hour_end' => '17:30:00', 'hour' => '17'],
            ['hour_start' => '17:30:00', 'hour_end' => '17:45:00', 'hour' => '17'],
            ['hour_start' => '17:45:00', 'hour_end' => '18:00:00', 'hour' => '17'],
        ];

        foreach ($hours as $hour) {
            DB::table('doctor_schedule_hours')->insert([
                'hour_start' => $hour['hour_start'],
                'hour_end' => $hour['hour_end'],
                'hour' => $hour['hour'],
                'created_at' => null,
                'updated_at' => null,
                'deleted_at' => null
            ]);
        }
    }
}
