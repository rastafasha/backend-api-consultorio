<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker; // Add Faker import
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

     public function run(): void
     
     {
         $faker = Faker::create(); // Create a new Faker instance
         $users = [
             [
                 // "rolename" => User::SUPERADMIN,
                 "name" => "super",
                 'surname' => 'Johnson',
                 "email" => "superadmin@superadmin.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("superadmin"),
                 'roles' => [
                     [
                         "id"=> 1,
                         "name"=> "SUPERADMIN",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 1,
                             "role_id"=> 1,  
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::ADMIN,
                 "name" => "admin",
                 'surname' => 'Johnson',
                 "email" => "admin@admin.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 1,
                         "name"=> "ADMIN",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 2,
                             "role_id"=> 2,  
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::DOCTOR,
                 "name" => "Jhon",
                 'surname' => 'Johnson',
                 "email" => "doctor@doctor.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'speciality_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 'roles' => [
                     [
                         "id"=> 3,
                         "name"=> "DOCTOR",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 2,
                             "role_id"=> 3,
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "password" => bcrypt("password"),
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::DOCTOR,
                 "name" => "Jane",
                 'surname' => 'Johnson',
                 "email" => "doctora@doctora.com",
                 'gender' => 1,
                 'location_id' => 2,
                 'speciality_id' => 2,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 'roles' => [
                     [
                         "id"=> 3,
                         "name"=> "DOCTOR",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 2,
                             "role_id"=> 3,
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "password" => bcrypt("password"),
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::LABORATORIO,
                 "name" => "laboratorio",
                 'surname' => 'Johnson',
                 "email" => "laboratorio@laboratorio.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 5,
                         "name"=> "LABORATORIO",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 3,
                             "role_id"=> 5,    
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::RECEPCION,
                 "name" => "recepcion",
                 'surname' => 'Johnson',
                 "email" => "recepcion@recepcion.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 4,
                         "name"=> "RECEPCION",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 4,
                             "role_id"=> 4,    
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::PERSONAL,
                 "name" => "personal",
                 'surname' => 'Johnson',
                 "email" => "personal@personal.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 8,
                         "name"=> "PERSONAL",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 5,
                             "role_id"=> 8, 
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::ENFERMERA,
                 "name" => "enfermera",
                 'surname' => 'Johnson',
                 "email" => "enfermera@enfermera.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 7,
                          "name"=> "ENFERMERA",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 6,
                             "role_id"=> 7,  
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::ASISTENTE,
                 "name" => "asistente",
                 'surname' => 'Johnson',
                 "email" => "asistente@asistente.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 6,
                         "name"=> "ASISTENTE",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 7,
                             "role_id"=> 6,   
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ],
             [
                 // "rolename" => User::GUEST,
                 "name" => "invitado",
                 'surname' => 'Johnson',
                 "email" => "invitado@invitado.com",
                 'gender' => 1,
                 'location_id' => 1,
                 'mobile' => '1234567893',
                 'n_doc' => $faker->unique()->numerify('54213698##'),
                 "password" => bcrypt("password"),
                 'roles' => [
                     [
                         "id"=> 9,
                         "name"=> "GUEST",
                         "guard_name"=> "api",
                         "created_at"=> "2025-02-16T06:49:18.000000Z",
                         "updated_at"=> "2025-02-16T06:49:18.000000Z",
                     ],
                     'pivot' => [
                         [
                             "model_id"=> 8,
                             "role_id"=> 9,   
                             "model_type"=> "App\\Models\\User"
                         ]
                     ],
                 ],
                 "email_verified_at" => now(),
                 "created_at" => now(),
             ]
         ];
 
         foreach ($users as $user) {
             // Extract roles before creating user
             $roles = $user['roles'] ?? null;
             unset($user['roles']);
             
             // Create user
             //si no tiene asignado un rol, se le asigna el rol de invitado
             if (!$roles) {
                 $createdUser->assignRole(User::GUEST);
                     } 
             $createdUser = User::create($user);
             
             // Attach roles if they exist
             if ($roles) {
                 $roleIds = array_column($roles, 'id');
                 $createdUser->roles()->sync($roleIds);
             }
         }
     }
}
