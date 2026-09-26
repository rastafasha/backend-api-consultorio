<?php

namespace App\Http\Controllers\Appointment;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Http\Resources\Appointment\Pay\AppointmentPayCollection;
use Illuminate\Support\Facades\Cache;

class AppointmentPayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $date_start = $request->date_start;
        $date_end = $request->date_end;
        $clinica_id = $request->clinica_id; 

        // 🛡️ DOBLE CANDADO MULTI-TENANT: Filtramos las citas de la clínica y aseguramos que pertenezcan al portafolio
        $appointmentpays = Appointment::filterAdvancePay($speciality_id, $search_doctor, $search_patient, $date_start, $date_end)
            ->where('clinica_id', $clinica_id)
            ->with([
                'patient',
                'doctor.speciality',
                'payments' => function($query) use ($clinica_id) {
                    $query->where('clinica_id', $clinica_id);
                }
            ])
            ->orderBy("status_pay", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $appointmentpays->total(),
            "appointmentpays" => AppointmentPayCollection::make($appointmentpays)
        ]);
    }

    public function paymentsByDoctor(Request $request, $doctor_id)
    {
        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $date_start = $request->date_start;
        $date_end = $request->date_end;
        $page = $request->input('page', 1);

        $doctor_exists = User::where("id", $doctor_id)->exists();
        if (!$doctor_exists) {
            return response()->json(["message" => "Doctor no encontrado"], 404);
        }

        $filterHash = md5(json_encode([$search_doctor, $search_patient, $date_start, $date_end, $page]));
        $cacheKey = "payments:doctor:{$doctor_id}:filters:{$filterHash}";

        $data = Cache::remember($cacheKey, 180, function () use ($doctor_id, $search_doctor, $search_patient, $date_start, $date_end) {
            
            $appointmentpays = Appointment::filterAdvanceDoctorPay($search_doctor, $search_patient, $date_start, $date_end)
                ->where('doctor_id', $doctor_id)
                ->with(['patient', 'payments']) 
                ->orderBy("id", "desc")
                ->paginate(10);

            return [
                "total" => $appointmentpays->total(),
                "appointmentpays" => $appointmentpays->map(function ($appointment) {
                    
                    $subPayments = $appointment->payments->map(function ($pay) {
                        return [
                            "id" => $pay->id,
                            "appointment_id" => $pay->appointment_id,
                            "amount" => $pay->amount,
                            "method_payment" => $pay->method_payment,
                            "created_at" => $pay->created_at ? $pay->created_at->format("Y-m-d h:i A") : null,
                        ];
                    })->toArray();

                    return [
                        "id" => $appointment->id,
                        "amount" => $appointment->amount,
                        "status_pay" => $appointment->status_pay,
                        "date_appointment" => $appointment->date_appointment,
                        "date_appointment_format" => $appointment->date_appointment ? \Carbon\Carbon::parse($appointment->date_appointment)->format("Y-m-d") : null,
                        "patient" => $appointment->patient ? [
                            "id" => $appointment->patient->id,
                            "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                            "n_doc" => $appointment->patient->n_doc,
                            "phone" => $appointment->patient->phone,
                        ] : null,
                        "payments" => $subPayments,
                        "payment"  => $subPayments
                    ];
                })
            ];
        });

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     * (Saneado Multi-Tenant Corporativo)
     */
    public function store(Request $request)
    {
        // 1. Calcular el acumulado de pagos registrados previamente
        $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");

        // 2. Validar que el nuevo abono no supere el precio estipulado de la cita
        if (($sum_total_pays + $request->amount) > $request->appointment_total) {
            return response()->json([
                "message" => 403,
                "message_text" => "El monto que se quiere registrar supera el costo de la cita"
            ]);
        }

        // 3. Extraemos la cita matriz para leer su contexto relacional
        $appointment = Appointment::findOrFail($request->appointment_id);

        // 🏢🩺 DETECCIÓN POLIMÓRFICA EN CALIENTE:
        // Si la cita tiene clinica_id, es una Clínica Enterprise (Usamos ese ID NoSQL).
        // Si no lo tiene, es un Consultorio Médico Pro (Heredamos el doctor_id como dueño de la caja).
        $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : $appointment->doctor_id;

        // 4. Crear el nuevo registro del abono amarrando el Tenant resultante
        $appointmentpay = AppointmentPay::create([
            "appointment_id" => $request->appointment_id,
            "amount"         => $request->amount,
            "method_payment" => $request->method_payment,
            
            // 🚀 Inyección Inteligente: Funciona para Clínicas y Consultorios en la misma línea
            "clinica_id"     => $ownerTenantId 
        ]);

        // 5. Actualizar el estado de cobro en la Cita Padre
        $is_total_payment = false;

        if (($appointment->amount) == ($sum_total_pays + $request->amount)) {
            $appointment->update(["status_pay" => 1]); 
            $is_total_payment = true;
        } else {
            $appointment->update(["status_pay" => 2]); 
        }

        Cache::flush();

        $responseData = [
            "is_total_payment" => $is_total_payment,
            "id" => $appointmentpay->id,
            "appointment_id" => $appointmentpay->appointment_id,
            "amount" => $appointmentpay->amount,
            "method_payment" => $appointmentpay->method_payment,
            "status_pay" => $appointment->status_pay, 
            "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
        ];

        return response()->json([
            "message" => 200,
            "appointmentpay" => $responseData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");
        $appointmentpay = AppointmentPay::findOrFail($id);
        
        $old_amount = $appointmentpay->amount;
        $new_amount = $request->amount;

        if ((($sum_total_pays - $old_amount) + $new_amount) > $request->appointment_total) {
            return response()->json([
                "message" => 403,
                "message_text" => "El monto que se quiere editar supera el costo de la cita"
            ]);
        }

        $appointment = Appointment::findOrFail($request->appointment_id);

        // 🏢🩺 DETECCIÓN POLIMÓRFICA EN EDICIÓN
        $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : $appointment->doctor_id;

        // Actualizar la transacción manteniendo el blindaje
        $appointmentpay->update([
            "amount"         => $request->amount,
            "method_payment" => $request->method_payment,
            "clinica_id"     => $ownerTenantId // 🚀 Garantiza consistencia en re-guardados
        ]);

        $is_total_payment = false;

        if (($appointment->amount) == (($sum_total_pays - $old_amount) + $new_amount)) {
            $appointment->update(["status_pay" => 1]); 
            $is_total_payment = true;
        } else {
            $appointment->update(["status_pay" => 2]); 
        }

        Cache::flush();

        $responseData = [
            "is_total_payment" => $is_total_payment,
            "id" => $appointmentpay->id,
            "appointment_id" => $appointmentpay->appointment_id,
            "amount" => $appointmentpay->amount,
            "method_payment" => $appointmentpay->method_payment,
            "status_pay" => $appointment->status_pay, 
            "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
        ];

        return response()->json([
            "message" => 200,
            "appointmentpay" => $responseData,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $appointmentpay = AppointmentPay::findOrFail($id);
        $appointment_id = $appointmentpay->appointment_id;

        $appointmentpay->delete();

        $appointment = Appointment::findOrFail($appointment_id);
        $sum_restante_pays = AppointmentPay::where("appointment_id", $appointment_id)->sum("amount");

        if ($appointment->amount == $sum_restante_pays) {
            $appointment->update(["status_pay" => 1]); 
        } else {
            $appointment->update(["status_pay" => 2]); 
        }

        Cache::flush();

        return response()->json([
            "message" => 200,
            "status_pay" => $appointment->status_pay 
        ]);
    }
}
