<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'id' => 1,
                'title' => 'Venezuela',
                'avatar' => 'locations/4RkuD10qGcTnfKRXHd2jX594dBVu78RdgDADZfRc.jpg',
                'city' => 'Caracas',
                'state' => 'DC',
                'zip' => '1010',
                'address' => 'Centro Comercial Sambil, Local e23',
                'email' => 'AbaThepC@app.com',
                'phone1' => '324432',
                'phone2' => '55665654',
                'user_id' => null,
                'client_id' => null,
                'created_at' => '2024-02-01 20:32:49',
                'updated_at' => '2024-02-01 20:58:42',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'title' => 'USA',
                'avatar' => 'locations/Ysx7n3mCj1a7wZuojaplfMPjXjs68MlyDJ5Usts4.jpg',
                'city' => 'Miami',
                'state' => 'FL',
                'zip' => '234we',
                'address' => 'Centro Comercial Sambil Chacao, Local e23',
                'email' => 'AbaThepCh@app.com',
                'phone1' => '2344432',
                'phone2' => '55665654',
                'user_id' => null,
                'client_id' => null,
                'created_at' => '2024-02-01 20:33:21',
                'updated_at' => '2024-02-01 20:58:06',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'title' => 'Italia',
                'avatar' => 'locations/4RkuD10qGcTnfKRXHd2jX594dBVu78RdgDADZfRc.jpg',
                'city' => 'Viena',
                'state' => 'Distrito Capital',
                'zip' => '1010A',
                'address' => 'Centro Comercial Santa paula, Local e23',
                'email' => 'AbaThep@app.com',
                'phone1' => '3223444',
                'phone2' => '55665654',
                'user_id' => null,
                'client_id' => null,
                'created_at' => '2024-02-01 20:35:26',
                'updated_at' => '2024-02-08 09:28:05',
                'deleted_at' => null
            ]
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['id' => $location['id']],
                $location
            );
        }
    }
}
