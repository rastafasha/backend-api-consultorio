<?php

namespace App\Http\Controllers;

use App\Models\Odontograma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OdontogramaController extends Controller
{
    /**
     * Guarda o actualiza el hallazgo de un diente dictado por voz.
     */
    public function guardarHallazgo(Request $request)
    {
        // 1. Validamos los datos estrictamente para proteger Supabase/MySQL
        $validator = Validator::make($request->all(), [
            'patient_id'     => 'required|integer|exists:patients,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'diente_numero'  => 'required|integer|between:11,48', // Rango universal FDI
            'cara_diente'    => 'nullable|string|max:10', // 'O', 'M', 'D', 'V', 'L', 'P'
            'hallazgo'       => 'required|string|max:150', // 'Caries', 'Ausente', etc.
            'notas'          => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 400);
        }

        // El doctor_id lo tomamos automáticamente del token del usuario autenticado por Laravel Sanctum/Passport
        $doctorId = auth()->user() ? auth()->user()->id : $request->get('doctor_id');

        // 2. Buscamos si ya existe un registro idéntico para ese diente y esa cara de ese paciente
        // Si el odontólogo vuelve a dictar sobre el mismo diente, lo actualizamos en vez de duplicarlo
        $odontograma = Odontograma::updateOrCreate(
            [
                'patient_id'    => $request->patient_id,
                'diente_numero' => $request->diente_numero,
                'cara_diente'   => $request->cara_diente,
            ],
            [
                'doctor_id'      => $doctorId,
                'appointment_id' => $request->appointment_id,
                'hallazgo'       => $request->hallazgo,
                'notas'          => $request->notas,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Diente registrado correctamente por el asistente de voz',
            'data'    => $odontograma
        ], 200);
    }

    /**
     * Obtiene todo el odontograma actual de un paciente para pintarlo en Angular.
     */
    public function obtenerPorPaciente($patient_id)
    {
        $historial = Odontograma::where('patient_id', $patient_id)
            ->orderBy('diente_numero', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $historial
        ], 200);
    }
}