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
        TasabcvSeeder::class,
        
        // 1. Primero configuramos los horarios del doctor
        SettingeneralSeeder::class,
        DoctorScheduleDaySeeder::class,
        DoctorScheduleHourSeeder::class,
        DoctorScheduleJoinHourSeeder::class, // <--- Este debe ir ANTES
        
        // 2. Ahora que hay horarios en la DB, creamos las citas
        AppointmentSeeder::class, // <--- Este debe ir DESPUÉS
        
        PubSeeder::class,
        PresupuestoSeeder::class,



        ]);
    }
}
