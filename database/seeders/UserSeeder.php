<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [

            [
                "rolename" => User::SUPERADMIN,
                "name" => "superadministrador",
                "email" => "superadmin@superadmin.com",
                "password" => bcrypt("password"),
                "email_verified_at" => now(),
                "created_at" => now(),

            ],

            [
                "rolename" => User::ADMIN,
                "name" => "administrador",
                "email" => "admin@admin.com",
                "password" => bcrypt("password"),
                "email_verified_at" => now(),
                "created_at" => now(),

            ],
            [
                "rolename" => User::MEMBER,
                "name" => "miembro",
                "email" => "miembro@miembro.com",
                "password" => bcrypt("password"),
                "email_verified_at" => now(),
                "created_at" => now(),

            ],
            [
                "rolename" => User::GUEST,
                "name" => "invitado",
                "email" => "invitado@invitado.com",
                "password" => bcrypt("password"),
                "email_verified_at" => now(),
                "created_at" => now(),

            ],
        ];

        foreach ($users as $user) {

            $user = User::create($user);
        }
    }
}
