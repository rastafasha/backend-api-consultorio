<?php

namespace Database\Seeders;

use App\Models\Settingeneral;
use Illuminate\Database\Seeder;

class SettingeneralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'id' => 1,
                'name' => 'Clinica DEMO-HCME',
                'address' => 'AV. Ppal',
                'phone' => '12324354677',
                'city' => 'Caracas',
                'state' => 'Capital',
                'zip' => '1010',
                'country' => 'Venezuela',
                'created_at' => '2024-01-10 22:18:16',
                'updated_at' => '2024-05-10 02:19:50',
                'deleted_at' => null
            ]
        ];

        foreach ($settings as $setting) {
            Settingeneral::updateOrCreate(
                ['id' => $setting['id']],
                $setting
            );
        }
    }
}
