<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Payment;
use App\Helpers\Uploader;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\NewPaymentRegisterMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationAppointment;
use App\Models\Appointment\Appointment;
use App\Http\Requests\PaymentStoreRequest;
use App\Models\Appointment\AppointmentPay;
use App\Http\Requests\PaymentUpdateRequest;
use App\Http\Resources\Appointment\Payment\PaymentResource;
use App\Http\Resources\Appointment\Payment\PaymentCollection;
use Illuminate\Support\Facades\Storage;
class AdminPaymentController extends Controller
{
    // /**
    //  * Create a new AuthController instance.
    //  *
    //  * @return void
    //  */
    // public function __construct()
    // {
    //     $this->middleware('jwt.verify');
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $metodo = $request->metodo;
        $search_referencia = $request->search_referencia;
        $bank_name = $request->bank_name;
        $nombre = $request->nombre;
        $monto = $request->monto;
        $fecha = $request->fecha;

        // $payments = Payment::where("referencia","like","%".$referencia."%")
        // ->orderBy("id","desc")
        // ->paginate(10);
        // // ->get();

        $payments = Payment::filterAdvancePayment($search_referencia)->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $payments->total(),
            "payments" => PaymentCollection::make($payments),

        ]);
    }

    public function paymentsByDoctor(Request $request, $doctor_id)
    {

        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $date_start = $request->date_start;
        $date_end = $request->date_end;
        $search_referencia = $request->search_referencia;


        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        // $patients = Patient::Where('doctor_id', $doctor_id)

        $payments = Payment::filterAdvancePaymentDoctor(
            $search_doctor,
            $search_patient,
            $date_start,
            $date_end,
            $search_referencia
        )
            ->Where('doctor_id', $doctor_id)
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $payments->total(),
            "payments" => PaymentCollection::make($payments)
        ]);

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentStore(Request $request)
    {
        //reviso si viene el id del appointment
        $appointment = Appointment::
            where("id", $request->appointment_id)
            ->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        if ($request->hasFile('image')) {
            $path = Storage::putFile("payments", $request->file('image'));
            $request->request->add(["image" => $path]);
        }


        $payment = Payment::create([
            "patient_id" => $request->patient_id,
            "doctor_id" => $request->doctor_id,
            "appointment_id" => $request->appointment_id,
            "nombre" => $request->nombre,
            "monto" => $request->monto,
            "email" => $request->email,
            "bank_name" => $request->bank_name,
            "metodo" => $request->metodo,
            "referencia" => $request->referencia,
            "status" => $request->status,
            "tasabcv" => $request->tasabcv,
            "image" => $path,
            // "status_pay" =>$request->amount != $request->amount_add ? 2 : 1,
        ]);
        //envio de correo al doctor
        // Mail::to($appointment->doctor->email)->send(new NewPaymentRegisterMail($payment));

        return response()->json([
            "message" => 200,
            "payment"=>$payment,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function paymentShow(Payment $payment)
    {


        if (!$payment) {
            return response()->json([
                'message' => 'Pago not found.'
            ], 404);
        }


        return response()->json([
            'code' => 200,
            'status' => 'success',
            "payment" => PaymentResource::make($payment),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function paymentUpdate(Payment $request, $id)
    {
        try {
            DB::beginTransaction();

            $request = $request->all();
            $payment = Payment::find($id);
            $payment->update($request->all());


            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Update payment success',
                'payment' => $payment,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error no update' . $exception,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function paymentDestroy(Payment $payment)
    {
        $this->authorize('paymentDestroy', Payment::class);

        try {
            DB::beginTransaction();

            if ($payment->image) {
                Uploader::removeFile("public/payments", $payment->image);
            }

            $payment->delete();

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Pago delete',
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Borrado fallido. Conflicto',
            ], 409);
        }
    }

   

    public function recientes()
    {
        $payments = Payment::orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'payments' => $payments
        ], 200);
    }



    public function deleteFotoPayment($id)
    {
        $payment = Payment::findOrFail($id);
        \Storage::delete('payments/' . $payment->image);
        $payment->image = '';
        $payment->save();
        return response()->json([
            'data' => $payment,
            'msg' => [
                'summary' => 'Archivo eliminado',
                'detail' => '',
                'code' => ''
            ]
        ]);
    }

    public function search(Request $request)
    {
        // return Payment::search($request->buscar);
        return Payment::search($request->query('buscar'));
    }

    public function updateStatus(Request $request, $id)
    {
        // 1. Buscamos el pago (siempre viene el ID)
        $payment = Payment::findOrFail($id);
        $payment->status = $request->status;
        $payment->motivo_rechazo = $request->motivo_rechazo;
        $payment->save();

        // 2. Si es RECHAZADO, terminamos aquí para evitar errores de null
        if ($request->status === 'REJECTED') {
            return response()->json([
                'status' => 'success',
                'message' => 'Pago rechazado y notificado correctamente'
            ]);
        }

        // 3. Si llega aquí, es porque es APPROVED o PENDIENTE
        // Buscamos la cita usando el appointment_id que SI enviaste en el JSON
        $appointment = Appointment::find($request->appointment_id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        // Cálculos solo para aprobaciones
        $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");
        $costo = $appointment->amount;
        $deuda = ($costo - $sum_total_pays);

        if ($request->status === 'APPROVED') {
            // Marcamos pagada si el monto actual completa la deuda
            if ($request->monto >= $deuda) {
                $appointment->update(["status_pay" => 1]);
            }

            // Registramos el pago en la tabla de pagos de citas
            AppointmentPay::create([
                "appointment_id" => $request->appointment_id,
                "amount" => $request->monto,
                "method_payment" => "TRANSFERENCIA", // O el campo que uses
            ]);
            $appointmentpay = AppointmentPay::create([
                "appointment_id" => $request->appointment_id,
                "amount" => $request->monto,
                "method_payment" => $request->bank_name,
            ]);
        }

        if ($request->status === 'APPROVED') {
            Mail::to($appointment->patient->email)->send(new ConfirmationAppointment($appointment));

        }

        return response()->json([
            "message" => 200,
            "payment" => $payment,
            "appointment" => $appointment,
            "appointmentpay" => $appointmentpay,

        ]);



    }


    public function pagosbyUser(Request $request, $patient_id)
    {

        $payments = Payment::where("patient_id", $patient_id)->orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            "payments" => PaymentCollection::make($payments),
        ], 200);

    }

    public function pagosPendientes()
    {

        $payments = Payment::
            where('status', 'PENDING')
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $payments->total(),
            "payments" => PaymentCollection::make($payments)
        ]);

    }
    public function pagosPendientesShowId(Request $request, $doctor_id)
    {

        $payments = Payment::where('status', 'PENDING')
            ->where("doctor_id", $doctor_id)
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $payments->total(),
            "payments" => PaymentCollection::make($payments)
        ]);

    }
}
