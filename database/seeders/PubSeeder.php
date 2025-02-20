<?php

namespace Database\Seeders;

use App\Models\Pub;
use Illuminate\Database\Seeder;

class PubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pubs = [
            [
                'id' => 1,
                'avatar' => 'publicidads/9FjTB5hAHOvGcnizqlDJDxw3z0Hu2x4cdE5WFjen.jpg',
                'state' => 1,
                'created_at' => '2024-01-15 23:01:53',
                'updated_at' => '2024-01-15 23:09:50',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'avatar' => 'publicidads/K4I5kBZ3BZF593DNit2Szh4fNiYvXUQfpe3gYtRm.jpg',
                'state' => 1,
                'created_at' => '2024-01-15 23:04:36',
                'updated_at' => '2024-01-15 23:04:36',
                'deleted_at' => null
            ],
            [
                'id' => 3,
                'avatar' => 'publicidads/sqNEtxj0WBa9vbTPEhk5THYMMEEnwdVvUQFFozio.jpg',
                'state' => 1,
                'created_at' => '2024-01-15 23:06:00',
                'updated_at' => '2024-01-15 23:06:00',
                'deleted_at' => null
            ],
            [
                'id' => 4,
                'avatar' => 'publicidads/EMQZLSdot9yEk6fOgPBlg9ED1umWJizlh53p8wD3.jpg',
                'state' => 2,
                'created_at' => '2024-01-15 23:06:50',
                'updated_at' => '2024-01-15 23:06:50',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'avatar' => 'publicidads/874AfEidDRuJTt0LTELllkat2xxmAGDsRck3FIbb.jpg',
                'state' => 2,
                'created_at' => '2024-01-15 23:10:42',
                'updated_at' => '2024-01-16 02:11:57',
                'deleted_at' => null
            ]
        ];

        foreach ($pubs as $pub) {
            Pub::updateOrCreate(
                ['id' => $pub['id']],
                $pub
            );
        }
    }
}
