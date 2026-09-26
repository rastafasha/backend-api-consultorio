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
        $specialities = Specialitie::where("state", 1)->get();

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
     */
    public function storePresupuesto(Request $request)
    {
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::where("id", $request->doctor_id)->first();

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

        $presupuesto = Presupuesto::create([
            "doctor_id" => $request->doctor_id,
            "patient_id" => $patient->id,
            "speciality_id" => $request->speciality_id,
            "description" => $request->description,
            "diagnostico" => $request->diagnostico,
            "amount" => $request->amount,
            "medical" => $request->medical,
        ]);

        // 🛡️ CONTROL DE CORREO DE RESPALDO: Solo despacha si el paciente posee correo
        if ($patient && !empty($patient->email)) {
            try {
                Mail::to($patient->email)->send(new NewpresupuestoRegisterMail($presupuesto));
            } catch (\Exception $e) {
                Log::warning("Fallo al enviar correo de presupuesto al paciente: " . $e->getMessage());
            }
        }

        if ($doctor && !empty($doctor->email)) {
            try {
                Mail::to($doctor->email)->send(new NewpresupuestoRegisterMail($presupuesto));
            } catch (\Exception $e) {
                Log::warning("Fallo al enviar correo de presupuesto al médico: " . $e->getMessage());
            }
        }

        // =========================================================================
        // 🔔 DISPARO DE NOTIFICACIÓN PUSH EN TIEMPO REAL
        // =========================================================================
        try {
            if (class_exists('NotificacionService')) {
                NotificacionService::enviar(
                    $presupuesto->doctor_id,
                    $patient->phone,
                    "Hola " . $patient->name . ", el establecimiento ha generado un nuevo presupuesto médico para tu tratamiento por un monto de $" . $presupuesto->amount . ". Ya puedes revisarlo detalladamente ingresando a tu portal.",
                    (string) $presupuesto->patient_id,
                    'PACIENTE',
                    '📋 Nuevo Presupuesto Disponible',
                    'PRESUPUESTO_NUEVO',
                    $presupuesto->id
                );
            }
        } catch (\Exception $e) {
            Log::error("Aviso: Notificación interna de presupuesto en espera: " . $e->getMessage());
        }

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
        $presupuesto = Presupuesto::findOrFail($id);
        $doctor = User::where("id", $request->doctor_id)->first();

        $presupuesto->confimation = $request->confimation;
        $presupuesto->update();

        // 🛡️ CONTROL DE SEGURIDAD CORREOS: Solo si el paciente posee correo
        if ($presupuesto->patient && !empty($presupuesto->patient->email) && $request->confimation == 2) {
            try {
                // Mail::to($presupuesto->patient->email)->send(new Confirmationpresupuesto($presupuesto));
            } catch (\Exception $e) {
                Log::warning("Fallo al enviar correo de confirmación al paciente: " . $e->getMessage());
            }
        }

        // =========================================================================
        // ⚡ NOTIFICACIÓN EN TIEMPO REAL AL CRM (Mismo formato de cobros)
        // =========================================================================
        if ($request->confimation == 2) {
            try {
                if (class_exists('NotificacionService')) {
                    NotificacionService::enviar(
                        $presupuesto->doctor_id,
                        null,
                        "El paciente " . $presupuesto->patient->name . " " . $presupuesto->patient->surname . " ha APROBADO el presupuesto por un monto de $" . $presupuesto->amount . ".",
                        (string) $presupuesto->doctor_id,
                        'MEDICO',                                                             // 5. Rol destinatario
                        '🎉 ¡Presupuesto Aprobado por Paciente!',
                        'PRESUPUESTO_APROBADO',
                        $presupuesto->id
                    );
                }
            } catch (\Exception $e) {
                Log::error("Aviso: Notificación de aprobación en espera: " . $e->getMessage());
            }
        }
        // Limpieza de Redis para mantener la sincronía
        Cache::forget("presupuestos:doctor:{$presupuesto->doctor_id}:page:1:limit:10");
        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
            "amount" => $request->amount,
            "paymentmethod" => $request->method_payment,
            "amountadd" => $request->amount_add,
            "date_presupuesto" => Carbon::parse($presupuesto->date_presupuesto)->format('d-m-Y'),
            "patient" => $presupuesto->patient_id ? [
                "id" => $presupuesto->patient->id,
                "email" => $presupuesto->patient->email,
                "full_name" => $presupuesto->patient->name . ' ' . $presupuesto->patient->surname,
            ] : NULL,
            "speciality" => $presupuesto->speciality ? [
                "id" => $presupuesto->speciality->id,
                "name" => $presupuesto->speciality->name,
            ] : NULL,
            "doctor_id" => $presupuesto->doctor_id,
            "doctor" => $presupuesto->doctor_id ? [
                "id" => $doctor->id,
                "email" => $doctor->email,
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
