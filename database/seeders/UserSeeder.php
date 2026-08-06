<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = [
            [
                "name" => "super",
                'surname' => 'Johnson',
                "email" => "superadmin@superadmin.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369874',
                "password" => bcrypt("superadmin"),
                'roles' => [["id" => 1, "name" => "SUPERADMIN"]], // Procesado automático por ID
                "email_verified_at" => now(),
                "created_at" => now(),
            ],
            [
                "name" => "admin",
                'surname' => 'Johnson',
                "email" => "admin@admin.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369871',
                "password" => bcrypt("password"),
                'roles' => [["id" => 2, "name" => "ADMIN"]], // Nota: ID 2 para Administrador regular
                "email_verified_at" => now(),
                "created_at" => now(),
            ],
            // --- 🏥 SECCIÓN: DOCTORES ---
            [
                "name" => "Jhon",
                'surname' => 'Johnson',
                "email" => "doctor@doctor.com",
                'gender' => 1,
                'pais_id' => 1,
                'speciality_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369872',
                "password" => bcrypt("password"),
                'roles' => [["id" => 3, "name" => "DOCTOR"]]
            ],
            [
                "name" => "Jane",
                'surname' => 'Johnson',
                "email" => "doctora@doctora.com",
                'gender' => 2,
                'pais_id' => 2,
                'speciality_id' => 2,
                'mobile' => '1234567893',
                'n_doc' => '5421369850',
                "password" => bcrypt("password"),
                'roles' => [["id" => 3, "name" => "DOCTOR"]]
            ],
            // --- 👔 SECCIÓN: PERSONAL DE APOYO (Filtrados automáticamente de Mongo) ---
            [
                "name" => "laboratorio",
                'surname' => 'Johnson',
                "email" => "laboratorio@laboratorio.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369873',
                "password" => bcrypt("password"),
                'roles' => [["id" => 5, "name" => "LABORATORIO"]]
            ],
            [
                "name" => "recepcion",
                'surname' => 'Johnson',
                "email" => "recepcion@recepcion.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369875',
                "password" => bcrypt("password"),
                'roles' => [["id" => 4, "name" => "RECEPCION"]]
            ],
            [
                "name" => "personal",
                'surname' => 'Johnson',
                "email" => "personal@personal.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369876',
                "password" => bcrypt("password"),
                'roles' => [["id" => 8, "name" => "PERSONAL"]]
            ],
            [
                "name" => "enfermera",
                'surname' => 'Johnson',
                "email" => "enfermera@enfermera.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369878',
                "password" => bcrypt("password"),
                'roles' => [["id" => 7, "name" => "ENFERMERA"]]
            ],
            [
                "name" => "asistente",
                'surname' => 'Johnson',
                "email" => "asistente@asistente.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369877',
                "password" => bcrypt("password"),
                'roles' => [["id" => 6, "name" => "ASISTENTE"]]
            ],
            [
                "name" => "invitado",
                'surname' => 'Johnson',
                "email" => "invitado@invitado.com",
                'gender' => 1,
                'pais_id' => 1,
                'mobile' => '1234567893',
                'n_doc' => '5421369870',
                "password" => bcrypt("password"),
                // Estructura simplificada que procesa tu bucle foreach
                'roles' => [["id" => 9, "name" => "GUEST"]],
                "email_verified_at" => now(),
                "created_at" => now(),
            ]

        ];

        // LIMITAR A LOS PRIMEROS 5 USUARIOS (Ajusta el número según lo que necesites)
        $usersLimitados = array_slice($users, 0, 10);

        foreach ($usersLimitados as $user) {
            // 1. Extraemos los roles antes de crear el usuario para no ensuciar el create
            $roles = $user['roles'] ?? null;
            unset($user['roles']);

            // 2. CORREGIDO: Creamos primero el usuario en MySQL
            $createdUser = User::create($user);

            // 3. Asignamos los roles de forma segura y en el orden correcto
            if ($roles) {
                // Si el JSON traía roles, extraemos los IDs y usamos el método nativo de Spatie
                $roleIds = array_column($roles, 'id');
                $createdUser->roles()->sync($roleIds); // Mantiene tu sincronización por IDs
            } else {
                // Si no tenía roles asignados en el JSON, ahora sí le asignamos el rol de invitado de forma segura
                $createdUser->assignRole(User::GUEST);
            }

            // --- 🚀 SINCRONIZACIÓN AUTOMÁTICA KLYNTIC (Node.js/Render) ---
            if ($createdUser->hasRole('DOCTOR')) {
                try {
                    $nodeUrl = env('KLYNTIC_NODE_URL', 'https://back-klyntic-envios.onrender.com');

                    // SOLUCIÓN: Agregamos timeout() para que Laravel no espere infinitamente
                    \Illuminate\Support\Facades\Http::timeout(5) // Máximo 5 segundos de espera
                        ->post($nodeUrl . '/api/klyntic/consultorios/sync', [
                            'doctor_id' => (string) $createdUser->id
                        ]);

                } catch (\Exception $e) {
                    // Si Render está dormido o da timeout, se registra en el log y el seeder CONTINÚA
                    \Illuminate\Support\Facades\Log::error('Seeder Klyntic: Falló el enlace a Render para el doctor ID ' . $createdUser->id . ': ' . $e->getMessage());

                    // Opcional: Imprime una alerta en tu consola de Railway para que sepas qué pasó
                    $this->command->warn("Aviso: Render tardó en responder para el Doctor ID " . $createdUser->id . ", saltando sincronización externa para continuar seeder.");
                }
            }
            // --------------------------------------------------------------

        }

    }
}
