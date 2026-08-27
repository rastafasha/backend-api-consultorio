<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Http\Controllers\Controller;
use App\Models\Patient\PatientPerson;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Appointment\AppointmentAttention;

class AppointmentAttentionController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 1. Encontrar la cita
        $appointment = Appointment::findOrFail($request->appointment_id);
        $appointment_attention = $appointment->attention;

        // 2. Preparar datos de la atención médica sin el user_id erróneo
        $dataAttention = $request->all();
        $dataAttention["receta_medica"] = json_encode($request->medical);
        unset($dataAttention['user_id']); // Evita enviar el 29 a la atención

        if ($appointment_attention) {
            $appointment_attention->update($dataAttention);
        } else {
            AppointmentAttention::create($dataAttention);
        }

        // 3. Forzar la actualización limpia de la cita en Supabase
        // Usamos el Query Builder ->where() de forma directa para asegurar que ejecute un UPDATE SQL real
        Appointment::where('id', $appointment->id)->update([
            "status" => 2,
            "date_attention" => now(),
            "laboratory" => $request->laboratory
        ]);

        return response()->json([
            "message" => 200,
        ]);
    }



    public function storeLocal(Request $request)
    {
        $request->request->add(["receta_medica" => json_encode($request->medical)]);

        $doctor = User::where("id", $request->doctor_id)->first();

        $patient = Patient::where("n_doc", $request->n_doc)->first();
        if (!$patient) {
            $patient = Patient::create([
                "name" => $request->name,
                "surname" => $request->surname,
                "n_doc" => $request->n_doc,
                "phone" => $request->phone,
            ]);
            PatientPerson::create([
                'patient_id' => $patient->id,
                'name_companion' => $request->name_companion,
                'surname_companion' => $request->surname_companion,
            ]);
        } else {
            if ($patient->person) {
                $patient->person->update([
                    'name_companion' => $request->name_companion,
                    'surname_companion' => $request->surname_companion,
                ]);
            } else {
                // Create a new PatientPerson if it doesn't exist
                PatientPerson::create([
                    'patient_id' => $patient->id,
                    'name_companion' => $request->name_companion,
                    'surname_companion' => $request->surname_companion,
                ]);
            }
        }

        $appointment = Appointment::create([
            "doctor_id" => $request->doctor_id,                     // ID 11 (Sí existe en users)
            'patient_id' => $patient->id,                           // ID 29 (Existe en patients)
                                      // ID 29 (Existe en patients)
            "date_appointment" => Carbon::parse($request->date_appointment)->format("Y-m-d H:i:s"), // 'H' mayúscula para formato 24h
            "date_attention" => Carbon::parse($request->date_appointment)->format("Y-m-d H:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,

            // SOLUCIÓN AL ERROR:
            // Si tienes un usuario logueado en el sistema (ej. recepcionista), usa auth()->id(). 
            // Si no hay login, usamos el ID del doctor ($request->doctor_id) para no romper la base de datos.
            // "user_id" => auth()->id() ?? $request->doctor_id,
            
            "amount" => $request->amount,
            "status_pay" => $request->amount != $request->amount_add ? 2 : 1,
        ]);

        // AppointmentAttention::create($request->all());

        // 1. Clonamos los datos del request en un array limpio
        $attentionData = $request->all();

        // 2. FORZAMOS el ID real de la cita recién creada, ignorando el '0' del frontend
        $attentionData['appointment_id'] = $appointment->id;

        // 3. Limpiamos campos basura del request que no pertenecen a la atención si es necesario
        unset($attentionData['user_id']);

        // 4. Creamos la atención médica con los datos corregidos
        AppointmentAttention::create($attentionData);

        date_default_timezone_set('America/Caracas');
        $appointment->update(["status" => 1, "date_attention" => now()]);

        AppointmentPay::create([
            "appointment_id" => $appointment->id,
            "amount" => $request->amount_add,
            "method_payment" => $request->method_payment,
        ]);

        date_default_timezone_set('America/Caracas');

        return response()->json([
            "message" => 200,
            "appointment" => $appointment,
            "amount" => $request->amount,
            "paymentmethod" => $request->method_payment,
            "amountadd" => $request->amount_add,
            "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
            "patient" => $appointment->patient_id ?
                [
                    "id" => $appointment->patient->id,
                    "email" => $appointment->patient->email,
                    "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                ] : NULL,
            "speciality" => $appointment->speciality,
            "speciality" => $appointment->speciality ?
                [
                    "id" => $appointment->speciality->id,
                    "name" => $appointment->speciality->name,
                ] : NULL,
            "doctor_id" => $appointment->doctor_id,
            "doctor" => $appointment->doctor_id ?
                [
                    "id" => $doctor->id,
                    "email" => $doctor->email,
                    "full_name" => $doctor->name . ' ' . $doctor->surname,
                ] : NULL,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment_attention = $appointment->attention;
        if ($appointment_attention) {
            return response()->json([
                "appointment_attention" => [
                    "id" => $appointment_attention->id,
                    "description" => $appointment_attention->description,
                    "laboratory" => $appointment_attention->laboratory,
                    "receta_medica" => $appointment_attention->receta_medica ? json_decode($appointment_attention->receta_medica) : [],
                    "created_at" => $appointment_attention->created_at->format("Y-m-d h:i A"),
                ]
            ]);
        } else {
            return response()->json([
                "appointment_attention" => [
                    "id" => NULL,
                    "description" => NULL,
                    "laboratory" => 1,
                    "receta_medica" => [],
                    "created_at" => NULL,
                ]
            ]);
        }

    }
}
