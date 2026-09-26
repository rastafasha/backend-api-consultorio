<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CRMIntegrationController extends Controller
{
    /**
     * 1. Recibe la orden del CRM (Node.js) y da de alta al médico en la tabla 'users'
     *    además de registrar su subdominio de control express.
     */
    public function syncTenantExpress(Request $request)
    {
        // 1. Ampliamos la validación para capturar los datos que exige tu tabla 'users'
        $validated = $request->validate([
            'crm_doctor_id'      => 'required|string',
            'subdomain'          => 'required|string', 
            'nombre'             => 'required|string',
            'apellido'           => 'required|string',
            'email'              => 'required|email',
            'n_doc'              => 'required|string',
            'phone'              => 'required|string',
            'speciality_id'      => 'nullable|integer',
            'plan_suscripcion'   => 'required|string', 
            'status_app'         => 'required|string', 
            'moneda_cobro'       => 'string',
            'password_inicial'   => 'nullable|string'
        ]);

        try {
            // 🔄 PASO A: Sincronización en la tabla maestra 'users' de Supabase
            // Buscamos si el médico ya existe por su cédula o correo para evitar duplicados accidentales
            $medico = User::where('n_doc', $validated['n_doc'])
                          ->orWhere('email', strtolower(trim($validated['email'])))
                          ->first();

            $statusMongoose = $validated['status_app'] === 'Activo' ? 1 : 2;

            if ($medico) {
                $medico->update([
                    'name'          => $validated['nombre'],
                    'surname'       => $validated['apellido'],
                    'mobile'        => $validated['phone'],
                    'moneda'        => $validated['moneda_cobro'] ?? 'USD',
                    'status'        => $statusMongoose,
                    'speciality_id' => $validated['speciality_id'] ?? $medico->speciality_id,
                ]);
                Log::info("🔄 [CRM Sync] Médico existente actualizado. ID: #" . $medico->id);
            } else {
                $medico = User::create([
                    'name'          => $validated['nombre'],
                    'surname'       => $validated['apellido'],
                    'n_doc'         => $validated['n_doc'],
                    'mobile'        => $validated['phone'],
                    'email'         => strtolower(trim($validated['email'])),
                    'moneda'        => $validated['moneda_cobro'] ?? 'USD',
                    'status'        => $statusMongoose,
                    'speciality_id' => $validated['speciality_id'] ?? null,
                    'password'      => Hash::make($validated['password_inicial'] ?? $validated['phone']), 
                ]);

                if (method_exists($medico, 'assignRole')) {
                    $medico->assignRole('DOCTOR'); 
                }
                Log::info("✨ [CRM Sync] Nuevo Médico creado en la tabla users. ID: #" . $medico->id);
            }

            DB::table('consultorios_express')->updateOrInsert(
                ['crm_id' => $validated['crm_doctor_id']], 
                [
                    'subdomain'  => strtolower(trim($validated['subdomain'])),
                    'nombre'     => $validated['nombre'] . ' ' . $validated['apellido'],
                    'plan'       => $validated['plan_suscripcion'],
                    'status'     => $validated['status_app'],
                    'moneda'     => $validated['moneda_cobro'] ?? 'USD',
                    'updated_at' => now(),
                    'created_at' => now(), 
                ]
            );

            return response()->json([
                'ok' => true,
                'message' => 'Médico y subdominio sincronizados con éxito en Laravel Core.',
                'laravel_user_id' => $medico->id
            ], 200);

        } catch (\Exception $e) {
            Log::error("❌ Error CRM Sync: " . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Error en el Core Central.'], 500);
        }
    }
/**
     * 🔥 NUEVO MÉTODO ENTERPRISE: Registra el ADMIN Maestro de una Clínica Institucional
     *    Sincronizado con tus requerimientos multi-tenant y tu UserSeeder.
     */
    public function syncEnterpriseAdmin(Request $request)
    {
        // 🛡️ Filtro de seguridad interno compartido
        if ($request->header('Authorization') !== 'KlynticCRMSecretToken_2026_vM0MmcxxA4Ih') {
            return response()->json(["ok" => false, "message" => "No autorizado"], 401);
        }

        $validated = $request->validate([
            'crm_clinica_id'   => 'required|string',
            'subdomain'        => 'required|string',
            'nombre'           => 'required|string',
            'apellido'         => 'required|string',
            'email'            => 'required|email',
            'n_doc'            => 'required|string',
            'phone'            => 'required|string',
            'status_app'       => 'required|string',
            'moneda_cobro'     => 'string',
            'password_inicial' => 'required|string'
        ]);

        try {
            // Buscamos si el administrador ya existe por cédula o correo
            $admin = User::where('n_doc', $validated['n_doc'])
                         ->orWhere('email', strtolower(trim($validated['email'])))
                         ->first();

            $statusMongoose = $validated['status_app'] === 'Activo' ? 1 : 2; // Alineado a tu TinyInteger

            if ($admin) {
                // Si ya existe, nos aseguramos de amarrar el clinica_id por si acaso
                $admin->update([
                    'clinica_id' => $validated['crm_clinica_id'],
                    'status'     => $statusMongoose
                ]);
                Log::info("🔄 [CRM Enterprise Sync] Administrador existente actualizado. ID: #" . $admin->id);
            } {
                // 🔥 SI ES NUEVO: Creamos el perfil usando la columna mobile y contraseña telefónica
                $admin = User::create([
                    'name'         => $validated['nombre'],
                    'surname'      => $validated['apellido'],
                    'n_doc'        => $validated['n_doc'],
                    'mobile'       => $validated['phone'], // Alineado a tu UserSeeder
                    'email'        => strtolower(trim($validated['email'])),
                    'moneda'       => $validated['moneda_cobro'] ?? 'USD',
                    'status'       => $statusMongoose,
                    'gender'       => 1, // Neutro inicial
                    'pais_id'      => 1, // Default Venezuela
                    'password'     => Hash::make($validated['password_inicial']), // Contraseña telefónica limpia
                    'clinica_id'   => $validated['crm_clinica_id'] // 🏢 Clave Multi-tenant
                ]);

                // Asignamos el rol estricto de Spatie configurado en tu Seeder
                if (method_exists($admin, 'assignRole')) {
                    $admin->assignRole('ADMIN'); 
                }
                Log::info("✨ [CRM Enterprise Sync] Nuevo ADMIN institucional creado para la clínica. ID: #" . $admin->id);
            }

            // 🔀 Registramos el subdominio en la tabla de control (puedes usar la misma o adaptarla)
            DB::table('consultorios_express')->updateOrInsert(
                ['crm_id' => $validated['crm_clinica_id']], 
                [
                    'subdomain'  => strtolower(trim($validated['subdomain'])),
                    'nombre'     => $validated['nombre'] . ' ' . $validated['apellido'],
                    'plan'       => 'PRO',
                    'status'     => $validated['status_app'],
                    'moneda'     => $validated['moneda_cobro'] ?? 'USD',
                    'updated_at' => now(),
                    'created_at' => now(), 
                ]
            );

            // 🚀 RETORNO EXITOSO: Le mandamos el ID autoincremental de Supabase de vuelta a Node.js
            return response()->json([
                'ok' => true,
                'message' => 'Entorno Enterprise y cuenta de ADMIN sincronizados con éxito en Laravel Core.',
                'laravel_user_id' => $admin->id
            ], 200);

        } catch (\Exception $e) {
            Log::error("❌ Error CRM Enterprise Sync: " . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Error en el Core Central Enterprise.'], 500);
        }
    }

    /**
     * 2. Le responde a Angular en Vercel si el subdominio es válido o no
     */
    public function validateSubdomain($subdomain)
    {
        try {
            $consultorio = DB::table('consultorios_express')
                ->where('subdomain', strtolower(trim($subdomain)))
                ->where('status', 'Activo')
                ->first();

            if (!$consultorio) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El consultorio solicitado no existe o está inactivo.'
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'consultorio' => $consultorio
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Error al validar subdominio.'], 500);
        }
    }
}
