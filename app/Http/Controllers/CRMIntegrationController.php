<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CRMIntegrationController extends Controller
{
    /**
     * 1. Recibe la orden del CRM (Node.js) y crea el subdominio en la tabla de control
     */
    public function syncTenantExpress(Request $request)
    {
        $validated = $request->validate([
            'crm_doctor_id'      => 'required|string',
            'subdomain'          => 'required|string', 
            'nombre_consultorio' => 'required|string',
            'plan_suscripcion'   => 'required|string', 
            'status_app'         => 'required|string', 
            'moneda_cobro'       => 'string'
        ]);

        try {
            // Guardamos en una tabla ligera alejada de los datos clínicos de Supabase
            DB::table('consultorios_express')->updateOrInsert(
                ['crm_id' => $validated['crm_doctor_id']], 
                [
                    'subdomain'  => strtolower(trim($validated['subdomain'])),
                    'nombre'     => $validated['nombre_consultorio'],
                    'plan'       => $validated['plan_suscripcion'],
                    'status'     => $validated['status_app'],
                    'moneda'     => $validated['moneda_cobro'] ?? 'USD',
                    'updated_at' => now(),
                    'created_at' => now(), 
                ]
            );

            return response()->json([
                'ok' => true,
                'message' => 'Subdominio guardado en la tabla de control Express con éxito.'
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
