<?php

namespace Database\Seeders;

use App\Models\Doctor\Specialitie;
use Illuminate\Database\Seeder;

class SpecialitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialities = [
            [
                'id' => 1,
                'name' => 'Anestesiología',
                'state' => 1,
                'price' => '200',
                'created_at' => '2023-10-04 07:18:43',
                'updated_at' => '2023-12-29 22:29:12',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'name' => 'Dermatología',
                'state' => 1,
                'price' => '140',
                'created_at' => '2023-10-04 07:22:58',
                'updated_at' => '2023-12-29 22:28:55',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'name' => 'Odontología',
                'state' => 1,
                'price' => '120',
                'created_at' => '2023-10-04 07:23:05',
                'updated_at' => '2023-12-29 22:29:25',
                'deleted_at' => null
            ],
            [
                'id' => 4,
                'name' => 'Pediatría',
                'state' => 1,
                'price' => '234',
                'created_at' => '2023-10-04 07:23:09',
                'updated_at' => '2023-12-29 22:29:38',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'name' => 'Cirugía General',
                'state' => 1,
                'price' => '100',
                'created_at' => '2023-10-04 07:23:14',
                'updated_at' => '2023-12-29 22:29:56',
                'deleted_at' => null
            ]
        ];

        foreach ($specialities as $speciality) {
            Specialitie::updateOrCreate(
                ['id' => $speciality['id']],
                $speciality
            );
        }
    }
}
