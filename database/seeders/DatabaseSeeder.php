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
            PaisSeeder::class,
            LocationSeeder::class,
            UserSeeder::class,
            SpecialitySeeder::class,
            PatientSeeder::class,
            
            TiposDePagoSeeder::class,
            AppointmentSeeder::class,
            
            SettingeneralSeeder::class,
            DoctorScheduleDaySeeder::class,
            DoctorScheduleHourSeeder::class,
            DoctorScheduleJoinHourSeeder::class,
            PubSeeder::class,
            PresupuestoSeeder::class,



        ]);
    }
}
