<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Uploader;
use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\Payment\PaymentCollection;
use App\Http\Resources\Appointment\Payment\PaymentResource;
use App\Mail\ConfirmationAppointment;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificacionService;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
    $page = $request->input('page', 1); // Capturamos la página para la caché

    // 1. Validación rápida y corregida con la variable de la ruta ($doctor_id)
    $doctor_exists = User::where("id", $doctor_id)->exists();
    if (!$doctor_exists) {
        return response()->json(["message" => "Doctor no encontrado"], 404);
    }

    // 2. Hash dinámico para que las búsquedas por referencia o fechas no se pisen en Redis
    $filterHash = md5(json_encode([$search_doctor, $search_patient, $date_start, $date_end, $search_referencia, $page]));
    $cacheKey = "payments_records:doctor:{$doctor_id}:filters:{$filterHash}";

    // Guardamos en caché por 3 minutos (180 segundos)
    $data = Cache::remember($cacheKey, 180, function () use ($doctor_id, $search_doctor, $search_patient, $date_start, $date_end, $search_referencia) {
        
        // CORRECCIÓN N+1: Cargamos previamente las relaciones de la cita y el paciente
        $payments = Payment::filterAdvancePaymentDoctor(
    $search_doctor,
    $search_patient,
    $date_start,
    $date_end,
    $search_referencia
)
->where('doctor_id', $doctor_id)
->with(['appointment.patient']) // <-- Carga la cita y el paciente de esa cita en una sola consulta masiva
->orderBy("id", "desc")
->paginate(10);

        return [
            "total" => $payments->total(),
            "payments" => PaymentCollection::make($payments)->resolve()
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
   public function paymentStore(Request $request)
{
    // Buscamos el appointment de forma segura
    $appointment = Appointment::where("id", $request->appointment_id)->first();
    if (!$appointment) {
        return response()->json(['message' => 'Appointment not found.'], 404);
    }

    // Inicializamos la variable en null por seguridad si no viene imagen
    $path = null;

    // Procesamos la imagen con Cloudinary
    if ($request->hasFile('image')) {
        $cloudinaryResponse = Cloudinary::uploadApi()->upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'klyntic/payments']
        );

        $path = $cloudinaryResponse['secure_url'];
        $request->request->add(["avatar" => $path]);
    }

    // ⚡ CONVERSIÓN DE LA FECHA (Timestamp JS a Formato SQL YYYY-MM-DD)
    $fecha_formateada = null;
    if ($request->fecha) {
        $fecha_formateada = Carbon::createFromTimestampMs($request->fecha)->format('Y-m-d');
    } else {
        // En caso de que por alguna razón no viaje la fecha, usamos la fecha de hoy por defecto
        $fecha_formateada = Carbon::now()->format('Y-m-d');
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
        "moneda" => $request->moneda,
        "image" => $path,
        "fecha" => $fecha_formateada, // 👈 NUEVO CAMPO ENVIADO A LA BASE DE DATOS 🎉
    ]);

    // =========================================================================
    // ⚡ LIMPIEZA DE CACHÉ EN REDIS (Actualización Contable del Médico)
    // =========================================================================
    // Borramos los dashboards para que el nuevo balance en dólares y la lista 
    // de pagos recientes se calculen al instante en la pantalla del doctor.
    $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
    Cache::forget("dashboard:doctor:{$payment->doctor_id}");
    Cache::forget("dashboard:doctor:{$payment->doctor_id}:year:{$year_current}");

    // Notificación en el CRM del médico
    NotificacionService::enviar(
        $payment->doctor_id,
        null,
        "El paciente " . $payment->nombre . " ha reportado un pago de $" . $payment->monto . " (Ref: " . $payment->referencia . ") para su cita.",
        $payment->doctor_id,
        'MEDICO',
        '💰 Nuevo Pago por Verificar',
        'PAGO_RECIBIDO',
        $payment->id
    );

    return response()->json([
        "message" => 200,
        "payment" => $payment,
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

            // =========================================================================
            // 🧪 VENENO INYECTADO: NOTIFICACIÓN DE PAGO RECHAZADO AL PACIENTE
            // =========================================================================
            NotificacionService::enviar(
                $payment->doctor_id,                                              // Consultorio ID para WhatsApp
                $payment->patient->phone,                                         // Teléfono del paciente
                "Hola " . $payment->nombre . ", tu pago reportado por $" . $payment->monto . " (Ref: " . $payment->referencia . ") no pudo ser verificado. Motivo: " . $payment->motivo_rechazo . ". Por favor, verifica los datos e intenta de nuevo.",
                $payment->patient_id,                                             // ID del paciente para la campana de Angular
                'PACIENTE',                                                       // Rol
                '❌ Pago Rechazado',                                               // Título Toastr
                'PAGO_RECHAZADO',                                                 // Enum tipo
                $payment->id                                                      // Referencia del pago en MySQL
            );

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

            // =========================================================================
            // 🧪 VENENO INYECTADO: NOTIFICACIÓN DE PAGO APROBADO AL PACIENTE
            // =========================================================================
            NotificacionService::enviar(
                $payment->doctor_id,                                              // Consultorio ID para WhatsApp
                $payment->patient->phone,                                         // Teléfono del paciente
                "Hola " . $payment->nombre . ", te confirmamos que tu pago de $" . $payment->monto . " (Ref: " . $payment->referencia . ") ha sido VERIFICADO y aprobado con éxito. ¡Gracias!",
                $payment->patient_id,                                             // ID del paciente para la campana de Angular
                'PACIENTE',                                                       // Rol
                '✅ Tu Pago ha sido Verificado',                                  // Título Toastr
                'PAGO_RECIBIDO',                                                  // Enum tipo
                $payment->id                                                      // Referencia del pago en MySQL
            );
        }
        // Ejemplo para el futuro: Solo envía el correo si el campo no está vacío
        // if (!empty($appointment->patient->email)) {
        //     NewAppointmentRegisterJob::dispatch($appointment)->onQueue('emails');
        // }
        // if ($request->status === 'APPROVED') {
        //     Mail::to($appointment->patient->email)->send(new ConfirmationAppointment($appointment));

        // }

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
