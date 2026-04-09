<?php

namespace Database\Seeders;

use App\Models\Tasabcv;
use Illuminate\Database\Seeder;

class TasabcvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasas = [
            [
                'id' => 1,
                'precio_dia' => 36.2,
                'created_at' => '2024-05-01 00:00:00',
                'updated_at' => '2024-05-01 00:00:00'
            ],
            [
                'id' => 2,
                'precio_dia' => 36.5,
                'created_at' => '2024-05-15 00:00:00',
                'updated_at' => '2024-05-15 00:00:00'
            ],
            [
                'id' => 3,
                'precio_dia' => 36.8,
                'created_at' => '2024-05-20 00:00:00',
                'updated_at' => '2024-05-20 00:00:00'
            ]
        ];

        foreach ($tasas as $tasa) {
            Tasabcv::updateOrCreate(
                ['id' => $tasa['id']],
                $tasa
            );
        }
    }
}
