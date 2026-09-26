<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Specialitie;
use Illuminate\Http\Request;

class ClinicaController extends Controller
{
    /**
     * Obtiene los especialistas de la clínica actual agrupados por Especialidad.
     * Alimenta el selector estilo Apple en Angular (Sin depender de tablas clínicas extras).
     */
    public function getSelectorEspecialistas()
    {
        // Recuperamos el ID numérico de la clínica actual inyectado por el Middleware
        $clinicaId = app('current_clinica_id');

        // 1. Buscamos las especialidades que tienen médicos asignados a ESTA clínica
        $especialidades = Specialitie::whereHas('doctors', function ($query) use ($clinicaId) {
            $query->where('users.clinica_id', $clinicaId)
                  ->where('users.status', 1); // Médicos activos
        })
        ->with(['doctors' => function ($query) use ($clinicaId) {
            // 2. Traemos únicamente los médicos adscritos a este ID clínico
            $query->where('users.clinica_id', $clinicaId)
                  ->where('users.status', 1)
                  ->select('users.id', 'users.name', 'users.surname', 'users.avatar', 'users.speciality_id', 'users.precio_cita', 'users.moneda');
        }])
        ->where('state', 1) // Especialidades activas
        ->get();

        // 3. Estructuramos la respuesta exacta que tu Angular ya sabe renderizar
        $resultado = $especialidades->map(function ($especialidad) {
            return [
                'especialidad_id'   => $especialidad->id,
                'especialidad_name' => $especialidad->name,
                'precio_base'       => $especialidad->price,
                'doctores'          => $especialidad->doctors->map(function ($doctor) {
                    return [
                        'id'          => $doctor->id,
                        'full_name'   => 'Dr(a). ' . $doctor->name . ' ' . $doctor->surname,
                        'avatar'      => $doctor->avatar ? $doctor->avatar : null,
                        'precio_cita' => $doctor->precio_cita,
                        'moneda'      => $doctor->moneda ?? 'USD'
                    ];
                })
            ];
        });

        return response()->json([
            'status'  => 'success',
            'tenant'  => $clinicaId,
            'results' => $resultado
        ], 200);
    }
}