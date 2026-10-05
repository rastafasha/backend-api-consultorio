<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Specialitie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Settingeneral;

class ClinicaController extends Controller
{
    /**
     * Obtiene los especialistas de la clínica actual agrupados por Especialidad.
     * Alimenta el selector estilo Apple en Angular (Sin depender de tablas clínicas extras).
     */
   
  public function getSelectorEspecialistas()
{
    // 1. Recuperamos el ID y lo forzamos explícitamente a cadena de texto (String)
    // 🟢 SOLUCIÓN CRÍTICA PARA POSTGRESQL: Evita el error de operador varchar = integer
    $clinicaId = (string) app('current_clinica_id');

    // 2. 🧽 INYECCIÓN INSTITUCIONAL DIRECTA
    $configuracion = Settingeneral::orderBy('created_at', 'DESC')->first(); 

    // 3. Buscamos las especialidades con médicos activos
    $especialidades = Specialitie::whereHas('activeDoctors', function ($query) use ($clinicaId) {
        $query->where('users.clinica_id', $clinicaId); // Postgres recibirá '1' en lugar de 1
    })
    ->with(['activeDoctors' => function ($query) use ($clinicaId) {
        $query->where('users.clinica_id', $clinicaId)
              ->select('users.id', 'users.name', 'users.surname', 'users.avatar', 'users.speciality_id');
    }])
    ->where('state', 1) 
    ->get();

    // 4. Consumo de precios centralizados (Microservicio Node.js / MongoDB Atlas)
    $urlMicroservicio = env('NODE_CRM_API_URL', 'http://localhost:3000/api') . '/aranceles/clinica/' . $clinicaId;
    $preciosCentralizados = [];
    try {
        $response = Http::timeout(3)->get($urlMicroservicio);
        if ($response->successful()) {
            $preciosCentralizados = $response->json()['aranceles'] ?? [];
        }
    } catch (\Exception $e) {
        \Log::error("Error conectando con Microservicio de Aranceles: " . $e->getMessage());
    }

    // 5. Estructuramos el catálogo forzando los costos centralizados por especialidad
    $resultado = $especialidades->map(function ($especialidad) use ($preciosCentralizados) {
        $precioEspecialidad = $preciosCentralizados[$especialidad->id]['costo'] ?? $especialidad->price;
        $monedaEspecialidad = $preciosCentralizados[$especialidad->id]['moneda'] ?? 'USD';

        return [
            'especialidad_id'   => $especialidad->id,
            'especialidad_name' => $especialidad->name,
            'precio_base'       => $precioEspecialidad,
            'moneda'            => $monedaEspecialidad,
            
            'doctores'          => $especialidad->activeDoctors->map(function ($doctor) use ($precioEspecialidad, $monedaEspecialidad) {
                return [
                    'id'          => $doctor->id,
                    'full_name'   => 'Dr(a). ' . $doctor->name . ' ' . $doctor->surname,
                    'avatar'      => $doctor->avatar ? $doctor->avatar : null,
                    'precio_cita' => $precioEspecialidad, 
                    'moneda'      => $monedaEspecialidad  
                ];
            })
        ];
    });

    // 6. 🔥 RETORNO COMPACTO Y SEGURO
    return response()->json([
        'status'  => 'success',
        'tenant'  => $clinicaId,
        'clinica' => [
            'name'    => $configuracion->name ?? 'Centro Clínico Enterprise',
            'address' => $configuracion->address ?? 'Dirección no configurada',
            'phone'   => $configuracion->phone ?? 'Teléfono no configurada',
            'moneda'  => $configuracion->moneda ?? 'USD'
        ],
        'results' => $resultado
    ], 200);
}

}