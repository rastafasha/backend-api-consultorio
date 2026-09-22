<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // 📋 Base de datos semilla unificada
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
                'roles' => [["id" => 1, "name" => "SUPERADMIN"]],
                "email_verified_at" => now(),
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
                'roles' => [["id" => 2, "name" => "ADMIN"]],
                "email_verified_at" => now(),
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
            // --- 👔 SECCIÓN: PERSONAL DE APOYO ---
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
                'roles' => [["id" => 9, "name" => "GUEST"]],
                "email_verified_at" => now(),
            ]
        ];

        // Procesamos el lote masivo completo
        foreach ($users as $userData) {
            
            // 1. Extraemos los roles de forma limpia
            $roles = $userData['roles'] ?? null;
            unset($userData['roles']);

            // 2. Extraemos campos bloqueados por el $fillable para inyectarlos directo por modelo
            $emailVerifiedAt = $userData['email_verified_at'] ?? null;
            unset($userData['email_verified_at']);

            // 3. Creamos el usuario en PostgreSQL de forma higiénica
            $createdUser = User::create($userData);

            // 4. 🔥 CORRECCIÓN FORZADA: Inyectamos campos protegidos saltándonos el mass assignment
            if ($emailVerifiedAt) {
                $createdUser->email_verified_at = $emailVerifiedAt;
                $createdUser->save();
            }

            // 5. 🔥 CORRECCIÓN DE SPATIE: Asignación de roles nativa y segura
            if ($roles) {
                // Sincronizamos usando el método oficial de Spatie 'syncRoles' pasándole los nombres de texto planos
                $roleNames = array_column($roles, 'name');
                $createdUser->syncRoles($roleNames); 
            } else {
                $createdUser->assignRole(User::GUEST);
            }

            // --- 🚀 SINCRONIZACIÓN AUTOMÁTICA CON EL ENTORNO DE ENVIOS ---
            if ($createdUser->hasRole('DOCTOR')) {
                try {
                    // Limpiamos la URL base para evitar el error de doble barra 'api/api' en Render
                    $nodeUrlBase = rtrim(env('KLYNTIC_NODE_URL', 'https://back-klyntic-envios.onrender.com'), '/');
                    
                    // Si tu URL del .env no incluye el prefijo, lo colocamos dinámicamente de forma higiénica
                    $endpointFinal = str_contains($nodeUrlBase, '/api') ? $nodeUrlBase : $nodeUrlBase . '/api';
                    $urlSync = $endpointFinal . '/klyntic/consultorios/sync';

                    // Disparo HTTP asíncrono blindado con cortafuegos de 4 segundos
                    Http::timeout(4)->post($urlSync, [
                        'doctor_id' => (string) $createdUser->id
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ [Seeder Klyntic] Saltando sincronización externa por inactividad en Render: ' . $e->getMessage());
                    $this->command->warn("Aviso: Microservicio en Render en reposo. Doctor ID #" . $createdUser->id . " sembrado con éxito en Supabase.");
                }
            }
            // --------------------------------------------------------------
        }
        
        $this->command->info('🎯 ¡UserSeeder completado al 100% con roles indexados con éxito!');
    }
}
