<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            SpecialitySeeder::class,
            PatientSeeder::class,
            
            TiposDePagoSeeder::class,
            AppointmentSeeder::class,
            LocationSeeder::class,
            PubSeeder::class,
            SettingeneralSeeder::class,
            DoctorScheduleDaySeeder::class,
            DoctorScheduleHourSeeder::class,
            DoctorScheduleJoinHourSeeder::class,



        ]);
    }
}
