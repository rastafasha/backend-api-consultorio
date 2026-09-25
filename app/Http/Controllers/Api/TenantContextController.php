<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use Illuminate\Http\Request;

class TenantContextController extends Controller
{
    public function obtenerContextoExpress(Request $request)
    {
        // 1. Capturamos el subdominio que Angular envía en los Headers HTTP
        $subdomain = $request->header('X-Tenant-Subdomain');

        // Seguro de vida: Si no viene ninguna cabecera, evitamos que rompa y respondemos por defecto
        if (!$subdomain) {
            return response()->json(['error' => 'Cabecera de subdominio ausente.'], 400);
        }

        // 2. Si el subdominio es 'consultorio', blindamos el Modo Pro tradicional
        if ($subdomain === 'consultorio') {
            return response()->json([
                'tipoClinica' => 'consultorio',
                'configuracion' => [
                    'nombre' => 'Klyntic Pro',
                    'modo' => 'médico_independiente'
                ]
            ], 200);
        }

        // 3. Si es cualquier otro subdominio (Enterprise), lo buscamos en la base de datos
        $clinica = Clinica::where('subdominio', $subdomain)->first();

        // Si la clínica no existe o su switch no está marcado como 'clinica', arrojamos un 404 limpio
        if (!$clinica || $clinica->tipoClinica !== 'clinica') {
            return response()->json([
                'error' => 'La institución médica solicitada no se encuentra registrada o activa.'
            ], 404);
        }

        // 4. RETORNO PREMIUM ENTERPRISE: Devolvemos la configuración y la lista de médicos de la tabla pivote
        return response()->json([
            'tipoClinica' => 'clinica',
            'detalles' => [
                'id' => $clinica->id,
                'nombre' => $clinica->nombre,
                'direccion' => $clinica->direccion_unica,
                'banner' => $clinica->banner_url,
                'logo' => $clinica->logo_url,
            ],
            // Jalamos los médicos usando la relación Muchos a Muchos que mapeamos en el modelo
            'medicos' => $clinica->medicos()
                                 ->select('users.id', 'users.name', 'users.surname', 'users.speciality_id')
                                 ->with('speciality:id,name') // Incluimos la especialidad de forma optimizada si ya existe la relación
                                 ->get()
        ], 200);
    }
}
