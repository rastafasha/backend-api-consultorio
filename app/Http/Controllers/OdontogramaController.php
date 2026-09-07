<?php

namespace App\Http\Controllers;

use App\Models\Appointment\Appointment;
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

    // El doctor_id lo tomamos automáticamente del token del usuario autenticado
    $doctorId = auth()->user() ? auth()->user()->id : $request->get('doctor_id');

    // 2. Guardamos o actualizamos el diente en Supabase
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

    // 🎯 3. SI EL DICTADO VIENE ASOCIADO A UNA CITA, LA MARCAMOS COMO ATENDIDA DE UNA VEZ
    // Usamos el Query Builder directo idéntico a tu método store() para asegurar la ejecución en producción
    if ($request->filled('appointment_id')) {
       Appointment::where('id', $request->appointment_id)->update([
            "status" => 2,
            "date_attention" => now(),
            // Colocamos el laboratorio por defecto en 1 o tomamos el que traiga el request
            "laboratory" => $request->get('laboratory', 1) 
        ]);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Diente registrado y cita marcada como atendida de forma automática',
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