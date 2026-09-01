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
    $page = $request->input('page', 1); // Capturamos la página para la caché

    // 1. Validación rápida y corregida con la variable correcta ($doctor_id)
    $doctor_exists = User::where("id", $doctor_id)->exists();
    if (!$doctor_exists) {
        return response()->json(["message" => "Doctor no encontrado"], 404);
    }

    // 2. Hash dinámico para que los filtros de fechas y búsquedas no se pisen en Redis
    $filterHash = md5(json_encode([$search_doctor, $search_patient, $date_start, $date_end, $page]));
    $cacheKey = "payments:doctor:{$doctor_id}:filters:{$filterHash}";

    // Guardamos en caché por 3 minutos (180 segundos)
    $data = Cache::remember($cacheKey, 180, function () use ($doctor_id, $search_doctor, $search_patient, $date_start, $date_end) {
        
        // CORRECCIÓN N+1: Añadimos ->with() con las relaciones típicas de cobros (patient)
        $appointmentpays = Appointment::filterAdvanceDoctorPay($search_doctor, $search_patient, $date_start, $date_end)
            ->where('doctor_id', $doctor_id)
            ->with(['patient']) // <-- Evita el colapso de consultas relacionales
            ->orderBy("id", "desc")
            ->paginate(10);

        return [
            "total" => $appointmentpays->total(),
            "appointmentpays" => AppointmentPayCollection::make($appointmentpays)->resolve()
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
        $sum_total_pays = AppointmentPay::where("appointment_id",$request->appointment_id)->sum("amount");
        

        if(($sum_total_pays + $request->amount) > $request->appointment_total){
            return response()->json([
                "message"=>403,
                "message_text"=> "El monto que se quiere registrar supera el costo de la cita"
            ]);
        }
        $appointmentpay = AppointmentPay::create([
            "appointment_id" =>$request->appointment_id,
            "amount"=>$request->amount,
            "method_payment" =>$request->method_payment,
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $is_total_payment = false;
        if(($appointment->amount) == ($sum_total_pays + $request->amount)){
            $appointment->update(["status_pay"=>1]);
            $is_total_payment = true;
        }

        return response()->json([
            "message"=>200,
            "appointmentpay"=> [
                "is_total_payment"=>$is_total_payment,
                "id" =>$appointmentpay->id,
                    "appointment_id" =>$appointmentpay->appointment_id,
                    "amount" =>$appointmentpay->amount,
                    "deuda" =>$appointmentpay->deuda,
                    "method_payment" =>$appointmentpay->method_payment,
                    "created_at"=>$appointmentpay->created_at->format("Y-m-d h:i A"),
            ]
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
        $sum_total_pays = AppointmentPay::where("appointment_id",$request->appointment_id)->sum("amount");
        $deuda = AppointmentPay::where("appointment_id",$request->appointment_id)->min("amount");
        
        error_log($deuda);

        $appointmentpay = AppointmentPay::findOrFail($id);
        
        $old_amount = $appointmentpay->amount;
        $new_amount = $request->amount;

        if((($sum_total_pays - $old_amount) + $new_amount) > $request->appointment_total){
            return response()->json([
                "message"=>403,
                "message_text"=> "El monto que se quiere editar supera el costo de la cita"
            ]);
        }
        $appointmentpay->update([
            "amount"=>$request->amount,
            "method_payment" =>$request->method_payment,
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $is_total_payment = false;
        if(($appointment->amount) == (($sum_total_pays - $old_amount) + $new_amount)){
            $appointment->update(["status_pay"=>1]);
            $is_total_payment = true;
        }else{
            $appointment->update(["status_pay"=>2]);
        }

        return response()->json([
            "message"=>200,
            "appointmentpay"=> [
                "is_total_payment"=>$is_total_payment,
                "id" =>$appointmentpay->id,
                    "appointment_id" =>$appointmentpay->appointment_id,
                    "amount" =>$appointmentpay->amount,
                    "method_payment" =>$appointmentpay->method_payment,
                    "created_at"=>$appointmentpay->created_at->format("Y-m-d h:i A"),
            ]
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
        $appointmentpay = AppointmentPay::findOrFail($id);
        
        $appointmentpay->delete();
        return response()->json([
            "message" => 200
        ]);
    }
}
