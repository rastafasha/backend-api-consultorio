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

        $appointmentpays = Appointment::filterAdvancePay(
            $speciality_id, $search_doctor, $search_patient,
            $date_start,$date_end)->orderBy("status_pay", "desc")
                            ->paginate(10);
        return response()->json([
            "total"=>$appointmentpays->total(),
            "appointmentpays"=> AppointmentPayCollection::make($appointmentpays)
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
        
        // 🚀 MEJORA: Cargamos previamente tanto el paciente como sus abonos relacionados ('payments')
        $appointmentpays = Appointment::filterAdvanceDoctorPay($search_doctor, $search_patient, $date_start, $date_end)
            ->where('doctor_id', $doctor_id)
            ->with(['patient', 'payments']) // <-- Trae la lista de abonos de la base de datos
            ->orderBy("id", "desc")
            ->paginate(10);

        return [
            "total" => $appointmentpays->total(),
            "appointmentpays" => $appointmentpays->map(function ($appointment) {
                
                // Procesamos y limpiamos el listado de abonos para esta cita
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
                    // Si tienes un helper o mutador de formato de fecha en el modelo, úsalo, sino formateamos aquí:
                    "date_appointment_format" => $appointment->date_appointment ? \Carbon\Carbon::parse($appointment->date_appointment)->format("Y-m-d") : null,
                    "patient" => $appointment->patient ? [
                        "id" => $appointment->patient->id,
                        "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                        "n_doc" => $appointment->patient->n_doc,
                        "phone" => $appointment->patient->phone,
                    ] : null,
                    
                    // 🚀 SOLUCIÓN: Inyectamos los abonos en singular y plural para blindar el HTML de Angular
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

    // 3. Crear el nuevo registro del abono
    $appointmentpay = AppointmentPay::create([
        "appointment_id" => $request->appointment_id,
        "amount" => $request->amount,
        "method_payment" => $request->method_payment,
    ]);

    // 4. Actualizar el estado de cobro en la Cita Padre
    $appointment = Appointment::findOrFail($request->appointment_id);
    $is_total_payment = false;

    if (($appointment->amount) == ($sum_total_pays + $request->amount)) {
        $appointment->update(["status_pay" => 1]); // 1 = Pagado por completo
        $is_total_payment = true;
    } else {
        $appointment->update(["status_pay" => 2]); // 2 = Deuda / Pendiente
    }

    // 🚀 5. DETONADOR DE CACHÉ (KLYNTIC BYPASS)
    // Vaciamos la caché de Redis para obligar al sistema a renderizar el cambio al instante
    Cache::flush();

    // 6. Preparar la estructura limpia de datos
    $responseData = [
        "is_total_payment" => $is_total_payment,
        "id" => $appointmentpay->id,
        "appointment_id" => $appointmentpay->appointment_id,
        "amount" => $appointmentpay->amount,
        "method_payment" => $appointmentpay->method_payment,
        "status_pay" => $appointment->status_pay, // Inyectamos el estado real de la cita
        "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
    ];

    // 🚀 7. RETORNO COMPATIBLE: Enviamos el nombre correcto y el typo para el frontend de Angular
    return response()->json([
        "message" => 200,
        "appointmentpay" => $responseData,
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   
    public function update(Request $request, $id)
{
    // 1. Calcular sumatorias usando la base de datos
    $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");
    
    $appointmentpay = AppointmentPay::findOrFail($id);
    
    $old_amount = $appointmentpay->amount;
    $new_amount = $request->amount;

    // 2. Validar que la edición no exceda el costo total configurado para la cita
    if ((($sum_total_pays - $old_amount) + $new_amount) > $request->appointment_total) {
        return response()->json([
            "message" => 403,
            "message_text" => "El monto que se quiere editar supera el costo de la cita"
        ]);
    }

    // 3. Actualizar la transacción del abono
    $appointmentpay->update([
        "amount" => $request->amount,
        "method_payment" => $request->method_payment,
    ]);

    // 4. Actualizar el estado de la Cita Padre y ajustar la bandera de liquidación
    $appointment = Appointment::findOrFail($request->appointment_id);
    $is_total_payment = false;

    // Si lo cobrado es exactamente igual al precio total de la cita
    if (($appointment->amount) == (($sum_total_pays - $old_amount) + $new_amount)) {
        $appointment->update(["status_pay" => 1]); // 🚀 CORREGIDO: 1 = Pagado (Sin Deudas)
        $is_total_payment = true;
    } else {
        $appointment->update(["status_pay" => 2]); // 🚀 CORREGIDO: 2 = Deuda (Saldo Pendiente)
    }

    // 5. Limpieza estratégica de la caché de Redis para que Angular no lea data vieja
    Cache::flush();

    // 6. Estructurar la respuesta
    // Extraemos 'status_pay' directamente desde la Cita ($appointment) que sí posee la columna
    $responseData = [
        "is_total_payment" => $is_total_payment,
        "id" => $appointmentpay->id,
        "appointment_id" => $appointmentpay->appointment_id,
        "amount" => $appointmentpay->amount,
        "method_payment" => $appointmentpay->method_payment,
        "status_pay" => $appointment->status_pay, // 🚀 CORREGIDO: Leído desde la Cita
        "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
    ];

    // Enviamos tanto la nomenclatura correcta como el typo para evitar fallos de Angular
    return response()->json([
        "message" => 200,
        "appointmentpay" => $responseData,
    ]);
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   public function destroy($id)
{
    // 1. Encontrar el pago que se va a eliminar
    $appointmentpay = AppointmentPay::findOrFail($id);
    $appointment_id = $appointmentpay->appointment_id;

    // 2. Eliminar el registro del abono
    $appointmentpay->delete();

    // 3. 🚀 LÓGICA DE RECALCULO FINANCIERO:
    // Buscamos la cita padre y volvemos a sumar los abonos que le quedan vigentes
    $appointment = Appointment::findOrFail($appointment_id);
    $sum_restante_pays = AppointmentPay::where("appointment_id", $appointment_id)->sum("amount");

    // Evaluamos si lo que queda cubierto alcanza para pagar el costo de la cita
    if ($appointment->amount == $sum_restante_pays) {
        // Si por alguna razón milagrosa el acumulado sigue cubriendo el total
        $appointment->update(["status_pay" => 1]); // 1 = Pagado
    } else {
        // 🚀 Si el monto cambia y es menor al costo total, vuelve a estado de Deuda
        $appointment->update(["status_pay" => 2]); // 2 = Deuda (Saldo Pendiente)
    }

    // 🚀 4. APLASTAR LA CACHÉ DE REDIS:
    // Obligamos a Laravel a refrescar todas las consultas en tiempo real para Angular
    Cache::flush();

    return response()->json([
        "message" => 200,
        "status_pay" => $appointment->status_pay // Opcional: le avisamos a Angular el nuevo estado de la cita
    ]);
}

}
