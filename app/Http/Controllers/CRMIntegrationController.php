<?php

namespace App\Http\Controllers;

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
            'phone'              => 'required|string', // Obligatorio para usarlo de contraseña
            'speciality_id'      => 'nullable|integer',
            'plan_suscripcion'   => 'required|string', 
            'status_app'         => 'required|string', 
            'moneda_cobro'       => 'string',
            'password_inicial'   => 'nullable|string' // Captura el teléfono limpio enviado por Node.js
        ]);

        try {
            // 🔄 PASO A: Sincronización en la tabla maestra 'users' de Supabase
            // Buscamos si el médico ya existe por su cédula o correo para evitar duplicados accidentales
            $medico = User::where('n_doc', $validated['n_doc'])
                          ->orWhere('email', strtolower(trim($validated['email'])))
                          ->first();

            $statusMongoose = $validated['status_app'] === 'Activo' ? 1 : 2; // Sintonizado con tu tinyInteger 'status'

            if ($medico) {
                // Si ya existe, actualizamos sus datos comerciales
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
                // 🔥 SI ES NUEVO: Lo creamos y aplicamos TU ESTRATEGIA de contraseña telefónica
                $medico = User::create([
                    'name'          => $validated['nombre'],
                    'surname'       => $validated['apellido'],
                    'n_doc'         => $validated['n_doc'],
                    'mobile'        => $validated['phone'],
                    'email'         => strtolower(trim($validated['email'])),
                    'moneda'        => $validated['moneda_cobro'] ?? 'USD',
                    'status'        => $statusMongoose,
                    'speciality_id' => $validated['speciality_id'] ?? null,
                    // Encriptamos el teléfono recibido, o una clave aleatoria como plan de respaldo
                    'password'      => Hash::make($validated['password_inicial'] ?? $validated['phone']), 
                ]);

                // Asignamos el rol oficial de Spatie
                if (method_exists($medico, 'assignRole')) {
                    $medico->assignRole('DOCTOR'); 
                }
                Log::info("✨ [CRM Sync] Nuevo Médico creado en la tabla users. ID: #" . $medico->id);
            }

            // 🔀 PASO B: Sincronización en tu tabla ligera de subdominios
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

            // 🚀 RETORNO MAGISTRAL: Le respondemos a Node.js con el ID autoincremental número 9, 10, etc.
            return response()->json([
                'ok' => true,
                'message' => 'Médico y subdominio sincronizados con éxito en Laravel Core.',
                'laravel_user_id' => $medico->id // Este campo lo lee Node.js para guardarlo en Mongoose
            ], 200);

        } catch (\Exception $e) {
            Log::error("❌ Error CRM Sync: " . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Error en el Core Central.'], 500);
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
