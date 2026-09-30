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
    /**
     * Store a newly created resource in storage.
     * (Optimizado para Pagos Centralizados de Clínicas y Directos de Médicos)
     */
public function paymentStore(Request $request)
    {
        // 1. Validar la existencia de la cita en MAMP
        $appointment = Appointment::where("id", $request->appointment_id)->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        // 2. Carga Segura y Directa a Cloudinary
        $path = null;
        if ($request->hasFile('image')) {
            $cloudinaryResponse = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'klyntic/payments']
            );
            $path = $cloudinaryResponse['secure_url'];
        }

        // 3. Formateo de fechas mediante Carbon
        $fecha_formateada = $request->fecha 
            ? Carbon::createFromTimestampMs($request->fecha)->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');

        // 🏢 GESTIÓN POLIMÓRFICA DE CAJA CENTRALIZADA / INDIVIDUAL
        $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : $request->doctor_id;

        // 4. Persistencia del Pago con Aislamiento de Entorno
        $payment = Payment::create([
            "patient_id"     => $request->patient_id,
            "doctor_id"      => $request->doctor_id,
            "appointment_id" => $request->appointment_id,
            "nombre"         => $request->nombre,
            "monto"          => $request->monto,
            "email"          => $request->email,
            "bank_name"      => $request->bank_name,
            "metodo"         => $request->metodo,
            "referencia"     => $request->referencia,
            "status"         => $request->status,
            "tasabcv"        => $request->tasabcv,
            "moneda"         => $request->moneda,
            "image"          => $path,
            "fecha"          => $fecha_formateada,
            "clinica_id"     => $ownerTenantId
        ]);

        // 5. Invalidación de Caché del Dashboard Contable
        $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
        Cache::forget("dashboard:doctor:{$payment->doctor_id}");
        Cache::forget("dashboard:doctor:{$payment->doctor_id}:year:{$year_current}");

        // =========================================================================
        // 📲 MOTOR DE DOBLE CANAL DE NOTIFICACIONES CORREGIDO (MÉDICO + RECEPCIÓN)
        // =========================================================================
        
        // Recuperamos el teléfono del médico para que Node no reciba data corrupta
        $medicoObj = User::find($payment->doctor_id);
        $telefonoMedico = $medicoObj ? $medicoObj->mobile : '';

        // 🟢 Alerta 1: Envío Obligatorio al Buzón Privado del Médico (Orden Alineado)
        NotificacionService::enviar(
            $payment->doctor_id,    // 1. $usuarioId (Receptor físico)
            'DOCTOR',               // 2. $rol (Enum válido en Mongo)
            $ownerTenantId,         // 3. $consultorioId
            $telefonoMedico,        // 4. $telefonoPaciente/Destinatario
            "El paciente " . $payment->nombre . " ha reportado un pago de $" . $payment->monto . " (Ref: " . $payment->referencia . ") para su cita.", // 5. $mensajeTexto
            '💰 Nuevo Pago por Verificar', // 6. $tituloToastr
            'PAGO_RECIBIDO',        // 7. $tipoEnum
            $payment->id            // 8. $refId
        );

        // Alerta 2: Duplicación en tiempo real para el Canal de Recepción de la Clínica
        if (!empty($appointment->clinica_id)) {
            
            $recepcionistas = User::where('clinica_id', $appointment->clinica_id)
                ->where('role', 'RECEPCION')
                ->get();

            foreach ($recepcionistas as $recepcionista) {
                // 🟢 Alerta 2: Envío estructurado para la campana de recepción de la sede
                NotificacionService::enviar(
                    $recepcionista->id,     // 1. $usuarioId
                    'RECEPCION',            // 2. $rol
                    $appointment->clinica_id, // 3. $consultorioId
                    $recepcionista->mobile ?? '', // 4. $telefonoPaciente
                    "🚨 [Caja Clínica] Pago de $" . $payment->monto . " por verificar del paciente " . $payment->nombre . " (Dr. ID: " . $payment->doctor_id . ").", // 5. $mensajeTexto
                    '🏢 Nuevo Pago Clínica', // 6. $tituloToastr
                    'PAGO_RECIBIDO_CLINICA', // 7. $tipoEnum
                    $payment->id            // 8. $refId
                );
            }
        }
        // =========================================================================

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

        // ☁️ LIMPIEZA EN LA NUBE DE CLOUDINARY
        if ($payment->image) {
            
            // 🔍 MOTOR DE EXTRACCIÓN DE PUBLIC ID
            // Ejemplo URL: https://cloudinary.com
            // Queremos extraer estrictamente: "klyntic/payments/abc123xyz"
            
            $pathText = parse_url($payment->image, PHP_URL_PATH); // Obtiene "/demo/image/upload/v1234567/klyntic/payments/abc123xyz.jpg"
            $pathPieces = explode('/', $pathText);
            
            // Buscamos el índice donde arranca tu carpeta en Cloudinary
            $startIndex = array_search('klyntic', $pathPieces);
            
            if ($startIndex !== false) {
                // Unimos las piezas desde "klyntic" en adelante
                $pathWithFolder = implode('/', array_slice($pathPieces, $startIndex)); 
                
                // Removemos la extensión del archivo (.jpg, .png, .jpeg) para obtener el Public ID puro
                $publicId = preg_replace('/\\.[^.\\s]{3,4}$/', '', $pathWithFolder);
                
                Log::info("☁️ [Cloudinary Destroy] Solicitando borrado del Public ID: " . $publicId);
                
                // Ejecutamos la destrucción en los servidores de Cloudinary
                Cloudinary::uploadApi()->destroy($publicId);
            } else {
                // Fallback clásico por si la estructura de la URL cambia o no tiene la carpeta raíz
                $filenameWithExtension = basename($payment->image);
                $filename = pathinfo($filenameWithExtension, PATHINFO_FILENAME);
                Cloudinary::uploadApi()->destroy($filename);
            }
        }

        // Eliminamos el registro de la base de datos MAMP/Supabase
        $payment->delete();

        DB::commit();
        return response()->json([
            'code' => 200,
            'status' => 'Pago delete',
        ], 200);

    } catch (\Throwable $exception) {
        DB::rollBack();
        Log::error("🚨 Error destruyendo pago y capture de Cloudinary: " . $exception->getMessage());
        
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



    public function search(Request $request)
    {
        // return Payment::search($request->buscar);
        return Payment::search($request->query('buscar'));
    }
/**
     * Update status of payment (APPROVED / REJECTED)
     * (Sincronizado con el Blindaje Multi-Tenant de Citas de Caja)
     */
 

public function updateStatus(Request $request, $id)
{
    // 1. Buscamos el pago reportado
    $payment = Payment::findOrFail($id);
    $payment->status = $request->status;
    $payment->motivo_rechazo = $request->motivo_rechazo;
    $payment->save();

    // Localizamos al paciente usando su modelo para recuperar el teléfono
    $pacienteEncontrado = \App\Models\Patient\Patient::find($payment->patient_id);
    $telefonoPaciente = $pacienteEncontrado ? $pacienteEncontrado->phone : '';

    $appointment = Appointment::find($request->appointment_id);
    if (!$appointment) {
        return response()->json(['message' => 'Cita no encontrada'], 404);
    }

    // Inyectamos el dueño real (Clínica o Médico) para el aislamiento contable
    $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : $payment->doctor_id;

    if ($request->status === 'REJECTED') {
        
        // 🟢 RECTIFICACIÓN CANAL DE ALERTAS: Orden alineado a la firma del Servicio
        NotificacionService::enviar(
            $payment->patient_id,    // 1. $usuarioId (El receptor de la campana es el PACIENTE)
            'PACIENTE',              // 2. $rol
            $ownerTenantId,          // 3. $consultorioId
            $telefonoPaciente,       // 4. $telefonoPaciente
            "Hola " . $payment->nombre . ", tu pago reportado por $" . $payment->monto . " no pudo ser verificado...", // 5. $mensajeTexto
            '❌ Pago Rechazado',     // 6. $tituloToastr
            'PAGO_RECHAZADO',        // 7. $tipoEnum
            $payment->id             // 8. $refId
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pago rechazado y notificado correctamente'
        ]);
    }

    $sum_total_pays = AppointmentPay::where("appointment_id", $request->appointment_id)->sum("amount");
    $costo = $appointment->amount;
    $deuda = ($costo - $sum_total_pays);

    $appointmentpay = null;

    if ($request->status === 'APPROVED') {
        if ($request->monto >= $deuda) {
            $appointment->update(["status_pay" => 1]);
        }

        $metodoPagoFinal = $request->metodo ?? ($request->method_payment ?? 'Efectivo');

        $appointmentpay = AppointmentPay::create([
            "appointment_id" => $request->appointment_id,
            "amount"         => $request->monto,
            "method_payment" => $metodoPagoFinal, 
            "clinica_id"     => $ownerTenantId
        ]);

        // 🟢 RECTIFICACIÓN CANAL DE ALERTAS: Orden alineado a la firma del Servicio
        NotificacionService::enviar(
            $payment->patient_id,    // 1. $usuarioId (El receptor de la campana es el PACIENTE)
            'PACIENTE',              // 2. $rol
            $ownerTenantId,          // 3. $consultorioId
            $telefonoPaciente,       // 4. $telefonoPaciente
            "Hola " . $payment->nombre . ", te confirmamos que tu pago de $" . $payment->monto . " ha sido VERIFICADO...", // 5. $mensajeTexto
            '✅ Tu Pago ha sido Verificado', // 6. $tituloToastr
            'PAGO_RECIBIDO',         // 7. $tipoEnum
            $payment->id             // 8. $refId
        );
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

   /**
     * Lista los pagos en espera de verificación para la recepción de la clínica/médico actual
     */
    public function pagosPendientes(Request $request)
    {
        // Capturamos el identificador del Tenant enviado por el motor de Angular
        $clinica_id = $request->clinica_id;

        if (!$clinica_id) {
            return response()->json(["message" => "Falta el identificador de contexto de caja."], 400);
        }

        $payments = Payment::where('status', 'PENDING')
            ->where('clinica_id', $clinica_id) // 🔒 FILTRO TENANT EXCLUSIVO DE ENTORNO
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
