<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presupuesto\PresupuestoCollection;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Mail\Confirmationpresupuesto;
use App\Mail\NewpresupuestoRegisterMail;
use App\Mail\Registerpresupuesto;
use App\Mail\UpdatedPresupuestoMail;
use App\Models\Doctor\Specialitie;
use App\Models\Patient\Patient;
use App\Models\Presupuesto;
use App\Models\User;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PresupuestoController extends Controller
{
    /**
     * Display a listing of the resource.
     * (Optimizado para la Recepción Centralizada Corporativa)
     */
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $name_doctor = $request->search;
        $date = $request->date;

        $userLogueado = auth()->user();

        // 1. Iniciamos la query (El Global Scope 'TenantScoped' filtra la clínica en automático)
        $query = Presupuesto::query();

        // 2. DISCRIMINACIÓN DE PODERES POR ROL DE SPATIE (Req. A y B)
        if ($userLogueado && $userLogueado->hasRole('DOCTOR', 'api')) {
            // 🔒 Si es un Médico, lo encerramos estrictamente en sus propios presupuestos
            $query->where('doctor_id', $userLogueado->id);
        }
        // Si el usuario es 'RECEPCION' o 'ASISTENTE', no ingresa al IF anterior,
        // otorgándole el poder de listar los presupuestos de todos los especialistas de la clínica.

        // 3. Ejecutamos tu filtro avanzado y paginación original intacta
        $presupuestos = $query->filterAdvance($speciality_id, $name_doctor, $date)
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $presupuestos->total(),
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ]);
    }

    public function config()
    {
        $specialities = Specialitie::where('state', 1)
            ->whereHas('activeDoctors', function ($query) {
                $query->where('status', 2);
            })
            ->with([
                'activeDoctors' => function ($query) {
                    $query->where('status', 2);
                }
            ])
            ->get();

        return response()->json([
            "specialities" => $specialities,
        ]);
    }

    public function query_patient(Request $request)
    {
        $n_doc = $request->get("n_doc");
        $patient = Patient::where("n_doc", $n_doc)->first();

        if (!$patient) {
            return response()->json(["message" => 403]);
        }

        return response()->json([
            "message" => 200,
            "id" => $patient->id,
            "name" => $patient->name,
            "surname" => $patient->surname,
            "phone" => $patient->phone,
            "n_doc" => $patient->n_doc,
        ]);
    }

       /**
     * Store a newly created resource in storage.
     * (Optimizado con Alertas de Presupuestos Sincronizadas y Alineadas)
     */
    public function storePresupuesto(Request $request)
    {
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        
        // Leemos prioritariamente el doctor_id del request enviado por el selector de la clínica o consultorio
        $doctor = User::where("id", $request->doctor_id)->first();

        if (!$doctor) {
            return response()->json([
                "message" => 400,
                "message_text" => 'Error de contexto: No se identificó un médico especialista válido para este presupuesto.'
            ], 400);
        }

        $request->request->add(["medical" => json_encode($request->medical)]);

        if (!$patient) {
            $patient = Patient::create([
                "name" => $request->name,
                "surname" => $request->surname,
                "email" => $request->email,
                "n_doc" => $request->n_doc,
                "phone" => $request->phone,
            ]);
        }

        // 🏢 GESTIÓN POLIMÓRFICA DE CONTEXTO INSTITUCIONAL (Aislamiento Multi-Tenant)
        $ownerClinicaId = $request->clinica_id ?? ($doctor->clinica_id ?? 1);

        $presupuesto = Presupuesto::create([
            "doctor_id"     => $doctor->id, 
            "patient_id"    => $patient->id,
            "clinica_id"    => $ownerClinicaId, 
            "speciality_id" => $request->speciality_id ?? $doctor->speciality_id,
            "description"   => $request->description,
            "diagnostico"   => $request->diagnostico,
            "amount"        => $request->amount,
            "medical"       => $request->medical,
        ]);

        // 🛡️ DESPACHO DE CORREO DIRECTO AL PACIENTE
        if ($patient && !empty($patient->email)) {
            try {
                Mail::to($patient->email)->send(new NewpresupuestoRegisterMail($presupuesto));
            } catch (\Exception $e) {
                Log::warning("Fallo al enviar correo de presupuesto al paciente: " . $e->getMessage());
            }
        }

        // =========================================================================
        // 📋 MOTOR DE NOTIFICACIONES SANEADO: Envío alineado a la firma del Servicio
        // =========================================================================
        try {
            if (class_exists('NotificacionService')) {
                
                // 🟢 RECTIFICACIÓN MAESTRA: Los parámetros caen exactamente en su posición nativa
                NotificacionService::enviar(
                    $presupuesto->patient_id,   // 1. $usuarioId (El receptor de la campana en Angular es el PACIENTE)
                    'PACIENTE',                 // 2. $rol (Enum válido para Mongoose)
                    $ownerClinicaId,            // 3. $consultorioId
                    $patient->phone ?? '',      // 4. $telefonoPaciente
                    "Hola " . $patient->name . ", se ha generado un nuevo presupuesto médico para tu tratamiento por un monto de $" . $presupuesto->amount . ". Ya puedes revisarlo detalladamente ingresando a tu portal.", // 5. $mensajeTexto
                    '📋 Nuevo Presupuesto Disponible', // 6. $tituloToastr
                    'PRESUPUESTO_NUEVO',        // 7. $tipoEnum (Enciende el Toastr del Paciente)
                    $presupuesto->id            // 8. $refId
                );
            }
        } catch (\Exception $e) {
            Log::error("Aviso: Notificación externa de presupuesto en espera: " . $e->getMessage());
        }
        // =========================================================================

        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $costo = $presupuesto->amount;

        return response()->json([
            "costo" => $costo,
            "presupuesto" => PresupuestoResource::make($presupuesto),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric',
            'medical' => 'required|array',
        ]);

        $request->request->add(["medical" => json_encode($request->medical)]);

        $presupuesto->update([
            "doctor_id" => $request->doctor_id,
            "patient_id" => $request->patient_id,
            "speciality_id" => $request->speciality_id,
            "description" => $request->description,
            "diagnostico" => $request->diagnostico,
            "amount" => $request->amount,
            "medical" => $request->medical,
        ]);

        // Limpieza inteligente de caché en Redis para el listado del médico afectado
        Cache::forget("presupuestos:doctor:{$presupuesto->doctor_id}:page:1:limit:10");

        return response()->json([
            "message" => 200,
            "presupuesto" => PresupuestoResource::make($presupuesto),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $doctor_id = $presupuesto->doctor_id;

        $presupuesto->delete();

        Cache::forget("presupuestos:doctor:{$doctor_id}:page:1:limit:10");

        return response()->json(["message" => 200]);
    }

    public function atendidas()
    {
        $presupuestos = Presupuesto::where('status', 2)->orderBy("id", "desc")->paginate(10);
        return response()->json([
            "total" => $presupuestos->total(),
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ]);
    }

  
    public function updateConfirmation(Request $request, $id)
{
    // 1. Buscamos el presupuesto cargando relaciones esenciales
    $presupuesto = Presupuesto::with(['patient', 'speciality', 'doctor'])->findOrFail($id);
    $doctor = $presupuesto->doctor;

    // 🔥 LOG DE RASTREO: Registra en laravel.log qué datos exactos están llegando al controlador
    Log::info("📥 [PRESUPUESTO UPDATE] ID: #{$id} - Datos recibidos en Request:", $request->all());

    // 🟢 BLINDAJE DE ENTRADA: Leemos la variable sin importar si viene como número, string o nulo
    $valorEstatus = $request->confimation;
    
    // Evaluamos de forma flexible (==) para aceptar tanto 2 como "2"
    $isAprobado  = ($valorEstatus == 2);
    $isRechazado = ($valorEstatus == 3);

    // Asignamos el valor entero correspondiente para persistir en MySQL
    if ($isAprobado)  $presupuesto->confimation = 2;
    if ($isRechazado) $presupuesto->confimation = 3;
    
    $presupuesto->update();

    Log::info("📊 [PRESUPUESTO ESTADO]: Banderas de control calculadas -> Aprobado: " . ($isAprobado ? 'SÍ' : 'NO') . " | Rechazado: " . ($isRechazado ? 'SÍ' : 'NO'));

    // 2. CONTROL DE SEGURIDAD CORREOS: Envío al paciente según la acción
    if ($presupuesto->patient && !empty($presupuesto->patient->email)) {
        try {
            if ($isAprobado) {
                // Mail::to($presupuesto->patient->email)->send(new Confirmationpresupuesto($presupuesto));
            }
        } catch (\Exception $e) {
            Log::warning("Fallo al enviar correo al paciente: " . $e->getMessage());
        }
    }

    // =========================================================================
    // ⚡ MOTOR DE DOBLE CANAL DE NOTIFICACIONES CORREGIDO (MÉDICO + RECEPCIÓN)
    // =========================================================================
    if (($isAprobado || $isRechazado) && class_exists('App\Services\NotificacionService')) {
        try {
            $accionTexto  = $isAprobado ? 'APROBADO' : 'RECHAZADO';
            $accionVerbo  = $isAprobado ? 'aprobó' : 'rechazó';
            $emoji        = $isAprobado ? '🎉' : '❌';
            $doctorNombre = $doctor->name ?? '';
            $telefonoMedico = $doctor->mobile ?? '';

            $mensajeMedico = $emoji . " El paciente " . $presupuesto->patient->name . " " . $presupuesto->patient->surname . " ha " . $accionTexto . " el presupuesto por un monto de $" . $presupuesto->amount . ".";
            $tituloMedico  = $emoji . " ¡Presupuesto " . $accionTexto . " por Paciente!";

            $mensajeRecepcion = $emoji . " [Presupuesto " . $accionTexto . "] El paciente " . $presupuesto->patient->name . " " . $accionVerbo . " el presupuesto de $" . $presupuesto->amount . " (Dr. " . $doctorNombre . ").";
            $tituloRecepcion  = "🏢 Presupuesto " . $accionTexto . " Clínica";

            $clinicaIdTarget = $presupuesto->clinica_id ?? ($doctor->clinica_id ?? 1);

            Log::info("📡 [PRESUPUESTO DISPARO]: Invocando microservicio de alertas para el Médico ID: " . $presupuesto->doctor_id);

            // Canal A: Alerta al buzón privado del MÉDICO
            NotificacionService::enviar(
                $presupuesto->doctor_id,    
                'DOCTOR',                   
                $clinicaIdTarget,           
                $telefonoMedico,            
                $mensajeMedico,             
                $tituloMedico,              
                "PRESUPUESTO_" . $accionTexto, 
                $presupuesto->id            
            );

            // Canal B: Duplicación en espejo para RECEPCIÓN de la Clínica
            if (!empty($clinicaIdTarget)) {
                $recepcionistas = User::where('clinica_id', $clinicaIdTarget)
                    ->where('role', 'RECEPCION')
                    ->get();

                foreach ($recepcionistas as $recepcionista) {
                    NotificacionService::enviar(
                        $recepcionista->id,         
                        'RECEPCION',                
                        $clinicaIdTarget,           
                        $recepcionista->mobile ?? '', 
                        $mensajeRecepcion,          
                        $tituloRecepcion,           
                        "PRESUPUESTO_" . $accionTexto . "_CLINICA", 
                        $presupuesto->id            
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("❌ Error enviando notificación de presupuesto: " . $e->getMessage());
        }
    } else {
        Log::warning("⚠️ [ALERTA OMITIDA]: No se cumplió la condición para notificar. ¿Estatus inválido o Servicio inexistente?");
    }

    // Limpieza de caché optimizada
    Cache::forget("presupuestos:doctor:{$presupuesto->doctor_id}:page:1:limit:10");

    return response()->json([
        "message" => 200,
        "presupuesto" => $presupuesto,
        "amount" => $presupuesto->amount,
        "patient" => $presupuesto->patient_id ? [
            "id" => $presupuesto->patient->id,
            "full_name" => $presupuesto->patient->name . ' ' . $presupuesto->patient->surname,
        ] : NULL,
        "doctor" => $presupuesto->doctor_id && $doctor ? [
            "id" => $doctor->id,
            "full_name" => $doctor->name . ' ' . $doctor->surname,
        ] : NULL,
    ]);
}


    public function presupuestoByDoctor(Request $request, $doctor_id)
    {
        $doctor_exists = User::where("id", $doctor_id)->exists();
        if (!$doctor_exists) {
            return response()->json(["message" => '403'], 403);
        }
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $cacheKey = "presupuestos:doctor:{$doctor_id}:page:{$page}:limit:{$perPage}";
        $data = Cache::remember($cacheKey, 180, function () use ($doctor_id, $perPage) {
            $presupuestos = Presupuesto::with(['patient'])
                ->where('doctor_id', $doctor_id)
                ->orderBy('id', 'desc')
                ->paginate($perPage);
            return [
                "presupuestos" => PresupuestoCollection::make($presupuestos)->resolve(),
                "meta" => [
                    "current_page" => $presupuestos->currentPage(),
                    "last_page" => $presupuestos->lastPage(),
                    "total" => $presupuestos->total(),
                ]
            ];
        });
        return response()->json($data);
    }
    public function bypatient(Request $request, $n_doc)
    {
        $patient = Patient::where("n_doc", $n_doc)->first();
        if (!$patient) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Patient not found',
            ], 404);
        }
        $presupuestos = Presupuesto::where("patient_id", '=', $patient->id)
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json([
            'code' => 200,
            'status' => 'success',
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ], 200);
    }
}
