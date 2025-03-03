<?php

namespace App\Http\Controllers\Appointment;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Http\Resources\Appointment\Pay\AppointmentPayCollection;

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
            $speciality_id,
            $search_doctor,
            $search_patient,
            $date_start,
            $date_end
        )->orderBy("status_pay", "desc")
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

        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        // $patients = Patient::Where('doctor_id', $doctor_id)

        $appointmentpays = Appointment::filterAdvanceDoctorPay(
            $search_doctor,
            $search_patient,
            $date_start,
            $date_end
        )
        ->Where('doctor_id', $doctor_id)
        ->orderBy("id", "desc")
        ->paginate(10);

        return response()->json([
            "total" => $appointmentpays->total(),
            "appointmentpays" => AppointmentPayCollection::make($appointmentpays)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");


        if (($sum_total_pays + $request->amount) > $request->appointment_total) {
            return response()->json([
                "message" => 403,
                "message_text" => "El monto que se quiere registrar supera el costo de la cita"
            ]);
        }
        $appointmentpay = AppointmentPay::create([
            "appointment_id" => $request->appointment_id,
            "amount" => $request->amount,
            "method_payment" => $request->method_payment,
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $is_total_payment = false;
        if (($appointment->amount) == ($sum_total_pays + $request->amount)) {
            $appointment->update(["status_pay" => 1]);
            $is_total_payment = true;
        }

        return response()->json([
            "message" => 200,
            "appointmentpay" => [
                "is_total_payment" => $is_total_payment,
                "id" => $appointmentpay->id,
                    "appointment_id" => $appointmentpay->appointment_id,
                    "amount" => $appointmentpay->amount,
                    "deuda" => $appointmentpay->deuda,
                    "method_payment" => $appointmentpay->method_payment,
                    "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
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
        $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");
        $deuda = AppointmentPay::where("appointment_id", $request->appointment_id)->min("amount");

        error_log($deuda);

        $appointmentpay = AppointmentPay::findOrFail($id);

        $old_amount = $appointmentpay->amount;
        $new_amount = $request->amount;

        if ((($sum_total_pays - $old_amount) + $new_amount) > $request->appointment_total) {
            return response()->json([
                "message" => 403,
                "message_text" => "El monto que se quiere editar supera el costo de la cita"
            ]);
        }
        $appointmentpay->update([
            "amount" => $request->amount,
            "method_payment" => $request->method_payment,
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $is_total_payment = false;
        if (($appointment->amount) == (($sum_total_pays - $old_amount) + $new_amount)) {
            $appointment->update(["status_pay" => 1]);
            $is_total_payment = true;
        } else {
            $appointment->update(["status_pay" => 2]);
        }

        return response()->json([
            "message" => 200,
            "appointmentpay" => [
                "is_total_payment" => $is_total_payment,
                "id" => $appointmentpay->id,
                    "appointment_id" => $appointmentpay->appointment_id,
                    "amount" => $appointmentpay->amount,
                    "method_payment" => $appointmentpay->method_payment,
                    "created_at" => $appointmentpay->created_at->format("Y-m-d h:i A"),
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
