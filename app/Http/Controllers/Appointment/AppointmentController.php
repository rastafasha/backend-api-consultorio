<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Http\Resources\Appointment\AppointmentResource;
// use App\Jobs\NewAppointmentRegisterJob;
use App\Mail\CancellationAppointmentMail;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\Specialitie;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientPerson;
use App\Models\User;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    
    /**
     * Display a listing of the resource.
     * (Optimizado para Recepción Centralizada Multi-Médico + Filtro Angular)
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $name_doctor = $request->search;
        $date = $request->date;
        
        // 🚀 NUEVA CAPTURA: Captura la orden del selector <select> reactivo de Angular
        $doctor_id = $request->doctor_id; 

        $userLogueado = auth()->user();

        // 1. Iniciamos la query (El Global Scope filtra la clínica actual en el fondo)
        $query = Appointment::query();

        // 2. DISCRIMINACIÓN DE PODERES POR ROL DE SPATIE (Req. A)
        if ($userLogueado && $userLogueado->hasRole('DOCTOR', 'api')) {
            // 🔒 Si el que consulta es un Médico, lo encerramos estrictamente en sus propias citas
            $query->where('doctor_id', $userLogueado->id);
        } else {
            // 🏢 SI ES RECEPCIÓN / ASISTENTE / ADMIN: Evaluamos el selector dinámico
            if (!empty($doctor_id) && $doctor_id !== 'TODOS') {
                // Si la secretaria aisló la grilla por un médico especialista específico
                $query->where('doctor_id', $doctor_id);
            }
            // Si es 'TODOS' o viene vacío, no introduce el where, mostrando la sábana global de la clínica
        }

        // 3. Ejecutamos tu filtro avanzado y paginación original intacta
        $appointments = $query->filterAdvance($speciality_id, $name_doctor, $date)
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);
    }

    public function appointmentByDoctor(Request $request, $doctor_id)
    {
        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $search = $request->search;
        $date = $request->date;
        $page = $request->input('page', 1);

        $filterHash = md5(json_encode([$search_doctor, $search_patient, $search, $date, $page]));
        $cacheKey = "appointments:doctor:{$doctor_id}:filters:{$filterHash}";

        $data = Cache::remember($cacheKey, 180, function () use ($doctor_id, $search_doctor, $search_patient, $date, $search) {
            $appointments = Appointment::filterAdvanceDoc($search_doctor, $search_patient, $date, $search)
                ->where('doctor_id', $doctor_id)
                ->with([
                    'patient',
                    'speciality',
                    'doctor_schedule_join_hour.doctor_schedule_hour',
                    'doctor_schedule_join_hour.doctor_schedule_day.doctor_address'
                ])
                ->orderBy("id", "desc")
                ->paginate(10);

            return [
                "total" => $appointments->total(),
                "appointments" => AppointmentCollection::make($appointments)->resolve()
            ];
        });

        return response()->json($data);
    }

    public function filter(Request $request)
    {
        date_default_timezone_set('America/Caracas');
        Carbon::setLocale('es');
        DB::statement("SET lc_time_names = 'es_ES'");

        $date_appointment = Carbon::parse($request->date_appointment)
            ->setTimezone('America/Caracas')
            ->format('Y-m-d');

        $hour = $request->hour;
        $speciality_id = $request->speciality_id;
        $name_day = Carbon::parse($date_appointment)->dayName;

        // 🟢 SANEADO COMPATIBILIDAD MAMP: Cambiado 'ilike' por 'like' neutro
        $doctor_query = DoctorScheduleDay::where("day", "like", "%" . $name_day . "%")
            ->whereHas("doctor", function ($q) use ($speciality_id) {
                $q->where("speciality_id", $speciality_id);
            })
            ->whereHas("schedule_hours", function ($q) use ($hour) {
                $q->whereHas("doctor_schedule_hour", function ($qs) use ($hour) {
                    $qs->where("hour", $hour);
                });
            })
            ->with([
                'doctor.speciality',
                'doctor_address',
                'schedule_hours' => function ($q) use ($hour, $date_appointment) {
                    $q->whereHas("doctor_schedule_hour", function ($qs) use ($hour) {
                        $qs->where("hour", $hour);
                    })->with([
                                'doctor_schedule_hour',
                                'appointments' => function ($qa) use ($date_appointment) {
                                    $qa->whereDate("date_appointment", $date_appointment);
                                }
                            ]);
                }
            ])
            ->get();

        $doctors = collect([]);

        foreach ($doctor_query as $doctor_q) {
            $doctor = $doctor_q->doctor;
            $address = $doctor_q->doctor_address;

            if (!$doctor)
                continue;

            $segmentsData = $doctor_q->schedule_hours->map(function ($segment) {
                $is_appointment = $segment->appointments->isNotEmpty();

                return [
                    "id" => $segment->id,
                    "doctor_schedule_day_id" => $segment->doctor_schedule_day_id,
                    "doctor_schedule_hour_id" => $segment->doctor_schedule_hour_id,
                    "is_appointment" => $is_appointment,
                    "format_segment" => [
                        "id" => $segment->doctor_schedule_hour->id,
                        "hour_start" => $segment->doctor_schedule_hour->hour_start,
                        "hour_end" => $segment->doctor_schedule_hour->hour_end,
                        "format_hour_start" => Carbon::parse($segment->doctor_schedule_hour->hour_start)->format("h:i A"),
                        "format_hour_end" => Carbon::parse($segment->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "hour" => $segment->doctor_schedule_hour->hour,
                    ],
                ];
            });

            $doctors->push([
                "doctor" => [
                    "id" => $doctor->id,
                    "full_name" => trim($doctor->name . ' ' . $doctor->surname),
                    "precio_cita" => $doctor->precio_cita,
                    "moneda" => $doctor->moneda,
                    "speciality" => [
                        "id" => $doctor->speciality->id ?? null,
                        "name" => $doctor->speciality->name ?? null,
                    ],
                    "consultorio" => $address ? [
                        "id" => $address->id,
                        "name_consultorio" => $address->name_consultorio,
                        "address" => $address->address,
                        "is_active" => $address->is_active,
                    ] : null,
                ],
                "segments" => $segmentsData
            ]);
        }

        return response()->json([
            "doctors" => $doctors
        ]);
    }


    public function filterByDoctor(Request $request, $doctor_id)
    {
        date_default_timezone_set('America/Caracas');
        Carbon::setLocale('es');

        $pure_date = substr($request->date_appointment, 0, 10);
        $date_appointment = Carbon::parse($pure_date)->format('Y-m-d');

        $hour = $request->hour;
        $doctor = User::with(['speciality', 'addresses'])->find($doctor_id);

        if (!$doctor) {
            return response()->json([
                "message" => "Doctor no encontrado",
                "doctor" => null
            ], 404);
        }

        $raw_day = Carbon::parse($date_appointment)->dayName;
        $name_day = Str::slug($raw_day);

        // 🟢 SANEADO COMPATIBILIDAD MAMP: Cambiados los dos 'ilike' por 'like' neutros universales
        $segments = DoctorScheduleJoinHour::whereHas("doctor_schedule_day", function ($q) use ($doctor_id, $name_day) {
            $q->where("day", "like", "%" . $name_day . "%")
                ->where("user_id", $doctor_id)
                ->whereNull("deleted_at");
        })
            ->whereHas("doctor_schedule_hour", function ($q) use ($hour) {
                $q->where("hour", "like", "%" . $hour . "%");
            })
            ->with([
                'doctor_schedule_hour',
                'doctor_schedule_day.doctor_address',
                'appointments' => function ($q) use ($date_appointment) {
                    $q->whereDate("date_appointment", $date_appointment);
                }
            ])
            ->get();

        $segmentsData = $segments->map(function ($segment) {
            $is_appointment = $segment->appointments->isNotEmpty();
            $addressRelation = $segment->doctor_schedule_day->doctor_address ?? null;

            return [
                "id" => $segment->id,
                "doctor_schedule_day_id" => $segment->doctor_schedule_day_id,
                "doctor_schedule_hour_id" => $segment->doctor_schedule_hour_id,
                "is_appointment" => $is_appointment,
                "doctor_address_id" => $addressRelation->id ?? null,

                "consultorio" => $addressRelation ? [
                    "id" => $addressRelation->id,
                    "name_consultorio" => $addressRelation->name_consultorio,
                    "address" => $addressRelation->address,
                    "is_active" => $addressRelation->is_active,
                ] : null,
                "format_segment" => [
                    "id" => $segment->doctor_schedule_hour->id,
                    "hour_start" => $segment->doctor_schedule_hour->hour_start,
                    "hour_end" => $segment->doctor_schedule_hour->hour_end,
                    "format_hour_start" => Carbon::parse($segment->doctor_schedule_hour->hour_start)->format("h:i A"),
                    "format_hour_end" => Carbon::parse($segment->doctor_schedule_hour->hour_end)->format("h:i A"),
                    "hour" => $segment->doctor_schedule_hour->hour,
                ],
            ];
        });
        return response()->json([
            "doctor" => [
                "id" => $doctor->id,
                "full_name" => trim($doctor->name . ' ' . $doctor->surname),
                "precio_cita" => $doctor->precio_cita,
                "moneda" => $doctor->moneda,
                "speciality" => [
                    "id" => $doctor->speciality->id ?? null,
                    "name" => $doctor->speciality->name ?? null,
                ],
                "addresses" => $doctor->addresses->map(function ($address) {
                    return [
                        "id" => $address->id,
                        "name_consultorio" => $address->name_consultorio,
                        "address" => $address->address,
                        "is_active" => $address->is_active,
                    ];
                }),
            ],
            "segments" => $segmentsData
        ]);
    }
    public function config()
    {
        $hours = [
            ["id" => "08", "name" => "8:00 AM"],
            ["id" => "09", "name" => "09:00 AM"],
            ["id" => "10", "name" => "10:00 AM"],
            ["id" => "11", "name" => "11:00 AM"],
            ["id" => "12", "name" => "12:00 PM"],
            ["id" => "13", "name" => "01:00 PM"],
            ["id" => "14", "name" => "02:00 PM"],
            ["id" => "15", "name" => "03:00 PM"],
            ["id" => "16", "name" => "04:00 PM"],
            ["id" => "17", "name" => "05:00 PM"],
        ];
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
            "hours" => $hours,
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
            "email" => $patient->email,
            "surname" => $patient->surname,
            "phone" => $patient->phone,
            "n_doc" => $patient->n_doc,
        ]);
    }
    public function calendar(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $appointments = Appointment::filterAdvancePay(
            $speciality_id,
            $search_doctor,
            $search_patient,
            null,
            null
        )->orderBy("id", "desc")->get();
        return response()->json([
            "appointments" => $appointments->map(function ($appointment) {
                return [
                    "id" => $appointment->id,
                    "title" => "Cita Médica - " . ($appointment->doctor->name . ' ' . $appointment->doctor->surname) . " - " . $appointment->speciality->name,
                    "start" => Carbon::parse($appointment->date_appointment)->format("Y-m-d") . "T" . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                    "end" => Carbon::parse($appointment->date_appointment)->format("Y-m-d") . "T" . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                ];
            }),
        ]);
    }
    public function store(Request $request): JsonResponse
{
    $patient = Patient::where("n_doc", $request->n_doc)->first();
    $doctor = User::findOrFail($request->doctor_id);
    
    if (!$patient) {
        $patient = Patient::create([
            "name" => $request->name,
            "surname" => $request->surname,
            "email" => $request->email,
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
        }
    }
    
    $date_formatted = Carbon::parse($request->date_appointment)->format("Y-m-d H:i:s");
    
    $appointment = Appointment::create([
        "doctor_id" => $request->doctor_id,
        "clinica_id" => $request->clinica_id,
        'patient_id' => $patient->id,
        "date_appointment" => $date_formatted,
        "speciality_id" => $request->speciality_id,
        "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
        'user_id' => auth()->id() ?? $doctor->id,
        "amount" => $request->amount,
        "status_pay" => $request->status_pay,
        "status" => $request->status,
    ]);
    
    if ($request->status_pay === 1) {
        AppointmentPay::create([
            "appointment_id" => $appointment->id,
            "amount" => $request->amount_add,
            "method_payment" => $request->method_payment,
            "status_pay" => 1,
        ]);
    }
    
    $appointment->load(['patient', 'speciality']);
    $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
    Cache::forget("dashboard:doctor:{$appointment->doctor_id}");
    Cache::forget("dashboard:doctor:{$appointment->doctor_id}:year:{$year_current}");
    
    // =========================================================================
    // 📲 MOTOR DE DOBLE CANAL DE NOTIFICACIONES PARA CITAS (MÉDICO + RECEPCIÓN)
    // =========================================================================
    try {
        if (class_exists('NotificacionService')) {
            
            $medicoObj = User::find($appointment->doctor_id);
            $telefonoMedico = $medicoObj ? $medicoObj->mobile : '';
            $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : 1;

            // 🟢 Alerta 1: Envío Obligatorio al Buzón Privado del Médico (Orden Alineado)
            NotificacionService::enviar(
                $appointment->doctor_id,    // 1. $usuarioId
                'DOCTOR',                   // 2. $rol
                $ownerTenantId,             // 3. $consultorioId
                $telefonoMedico,            // 4. $telefonoPaciente
                "Tienes un nuevo paciente agendado (" . $appointment->patient->name . " " . $appointment->patient->surname . ") para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y \a \l\a\s H:i'), // 5. $mensajeTexto
                '📅 Nueva Cita Agendada',   // 6. $tituloToastr
                'CONSULTA_NUEVA',           // 7. $tipoEnum
                $appointment->id            // 8. $refId
            );

            // 🏢 Alerta 2: Duplicación en espejo para el Canal de Recepción de la Clínica
            if (!empty($appointment->clinica_id)) {
                
                $recepcionistas = \App\Models\User::where('clinica_id', $appointment->clinica_id)
                    ->where('role', 'RECEPCION')
                    ->get();

                foreach ($recepcionistas as $recepcionista) {
                    NotificacionService::enviar(
                        $recepcionista->id,         // 1. $usuarioId
                        'RECEPCION',                // 2. $rol
                        $appointment->clinica_id,   // 3. $consultorioId
                        $recepcionista->mobile ?? '', // 4. $telefonoPaciente
                        "📅 [Cita Clínica] Nueva cita para el Dr. " . $doctor->name . " " . $doctor->surname . " con el paciente " . $appointment->patient->name . " para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y H:i'), // 5. $mensajeTexto
                        '🏢 Nueva Cita en Recepción', // 6. $tituloToastr
                        'CONSULTA_NUEVA_CLINICA',   // 7. $tipoEnum
                        $appointment->id            // 8. $refId
                    );
                }
            }
        }
    } catch (\Exception $e) {
        Log::error("🚨 Error inyectando el doble canal de notificaciones de cita: " . $e->getMessage());
    }
    // =========================================================================

    return response()->json([
        "message" => 200,
        "appointment" => $appointment,
        "amount" => $request->amount,
        "paymentmethod" => $request->method_payment,
        "amountadd" => $request->amount_add,
        "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
        "patient" => [
            "id" => $appointment->patient->id,
            "email" => $appointment->patient->email,
            "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
        ],
        "speciality" => $appointment->speciality ? [
            "id" => $appointment->speciality->id,
            "name" => $appointment->speciality->name,
        ] : NULL,
        "doctor_id" => $appointment->doctor_id,
        "doctor" => [
            "id" => $doctor->id,
            "email" => $doctor->email,
            "full_name" => $doctor->name . ' ' . $doctor->surname,
        ],
    ]);
}
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        $sum_total_pays = AppointmentPay::where("appointment_id", $id)->sum("amount");
        $costo = $appointment->amount;
        $deuda = ($costo - $sum_total_pays);
        return response()->json([
            "costo" => $costo,
            "deuda" => $deuda,
            "appointment" => AppointmentResource::make($appointment),
        ]);
    }
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        if ($appointment->payments->sum("amount") > $request->amount) {
            return response()->json([
                "message" => 403,
                "message_text" => "Los Pagos ingresados superan al nuevo monto que quiere guardar"
            ]);
        }
        $date_formatted = Carbon::parse($request->date_appointment)->format("Y-m-d H:i:s");
        $appointment->update([
            "doctor_id" => $request->doctor_id,
            "date_appointment" => $date_formatted,
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            "amount" => $request->amount,
            "status_pay" => $appointment->payments->sum("amount") != $request->amount ? 2 : 1,
        ]);
        $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}");
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}:year:{$year_current}");
        return response()->json(["message" => 200]);
    }
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(["message" => 200]);
    }
    public function atendidas()
    {
        $appointments = Appointment::where('status', 2)->orderBy("id", "desc")->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);
    }
    public function pendientes()
    {
        $appointments = Appointment::where('status', 1)->orderBy("id", "desc")->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);
    }
    public function pagosPendientesShowId(Request $request, $doctor_id)
    {
        $appointments = Appointment::where("doctor_id", $doctor_id)->where('status', 1)->orderBy("id", "desc")->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);
    }
   public function updateConfirmation(Request $request, $id)
{
    // 1. Buscamos la cita cargando sus relaciones para agilizar la respuesta
    $appointment = Appointment::with(['patient', 'speciality', 'doctor'])->findOrFail($id);
    
    // Candado de seguridad para el médico (evita quiebres si el request no trae doctor_id)
    $doctor = $appointment->doctor; 

    // 2. Actualizamos el estado de confirmación
    $appointment->confimation = $request->confimation;
    $appointment->update();

        // 3. 📲 MOTOR DE NOTIFICACIONES MULTI-CANAL (Si pasa a estatus CONFIRMADA = 2)
    if ($request->confimation == 2) {
        
        $telefonoPaciente = $appointment->patient->phone ?? '';
        $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : 1;

        // 🟢 Canal A: Alerta Obligatoria al buzón privado del PACIENTE (Orden Alineado)
        NotificacionService::enviar(
            $appointment->patient_id,       // 1. $usuarioId (Recibe el PACIENTE)
            'PACIENTE',                     // 2. $rol
            $ownerTenantId,                 // 3. $consultorioId
            $telefonoPaciente,              // 4. $telefonoPaciente
            "Hola " . $appointment->patient->name . ", te confirmamos que tu cita médica para el día " . Carbon::parse($appointment->date_appointment)->format('d-m-Y') . " se encuentra oficialmente CONFIRMADA. ¡Te esperamos!", // 5. $mensajeTexto
            '📅 Tu Cita ha sido Confirmada',// 6. $tituloToastr
            'CITA_AGENDADA',                // 7. $tipoEnum
            $appointment->id                // 8. $refId
        );

        // Canal B: 🏢 Espejo para las secretarias de la RECEPCIÓN
        if (!empty($appointment->clinica_id)) {
            
            $recepcionistas = \App\Models\User::where('clinica_id', $appointment->clinica_id)
                ->where('role', 'RECEPCION')
                ->get();

            foreach ($recepcionistas as $recepcionista) {
                NotificacionService::enviar(
                    $recepcionista->id,             // 1. $usuarioId (Recibe la secretaria)
                    'RECEPCION',                    // 2. $rol
                    $appointment->clinica_id,       // 3. $consultorioId
                    $recepcionista->mobile ?? '',   // 4. $telefonoPaciente
                    "El paciente " . $appointment->patient->name . " " . $appointment->patient->surname . " tiene su cita CONFIRMADA para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y'), // 5. $mensajeTexto
                    '🏢 Cita Confirmada por Clínica', // 6. $tituloToastr
                    'CITA_AGENDADA_CLINICA',        // 7. $tipoEnum
                    $appointment->id                // 8. $refId
                );
            }
        }
    }


    return response()->json([
        "message" => 200,
        "status" => $request->confimation == 2 ? 'Confirmada' : 'Pendiente',
        "appointment" => $appointment,
        "amount" => $request->amount,
        "paymentmethod" => $request->method_payment,
        "amountadd" => $request->amount_add,
        "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
        "patient" => $appointment->patient_id ? [
            "id" => $appointment->patient->id,
            "email" => $appointment->patient->email,
            "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
        ] : NULL,
        "speciality" => $appointment->speciality ? [
            "id" => $appointment->speciality->id,
            "name" => $appointment->speciality->name,
        ] : NULL,
        "doctor_id" => $appointment->doctor_id,
        "doctor" => $appointment->doctor_id && $doctor ? [
            "id" => $doctor->id,
            "email" => $doctor->email,
            "full_name" => $doctor->name . ' ' . $doctor->surname,
        ] : NULL,
    ]);
}
   
    public function cancelarCita($id)
    {
        $appointment = Appointment::findOrFail($id);

        // 🔔 Notificación en el CRM del médico (Usa tu estructura limpia de pagos 💰)
        NotificacionService::enviar(
            $appointment->doctor_id,
            null,
            "La cita del paciente " . $appointment->patient->name . " para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y') . " ha sido eliminada por Recepción.",
            $appointment->doctor_id,
            'DOCTOR',
            '🚨 Cita Eliminada',
            'CITA_CANCELADA',
            $appointment->id
        );

        // 🛡️ CONTROL DE SEGURIDAD: Solo envía el correo si el paciente posee email válido registrado
        if (!empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new CancellationAppointmentMail($appointment));
            } catch (\Exception $e) {
                Log::warning("No se pudo despachar el correo de respaldo al paciente: " . $e->getMessage());
            }
        }

        // El correo al médico se mantiene como respaldo secundario
        if (!empty($appointment->doctor->email)) {
            try {
                Mail::to($appointment->doctor->email)->send(new CancellationAppointmentMail($appointment));
            } catch (\Exception $e) {
                Log::warning("No se pudo despachar el correo de respaldo al médico: " . $e->getMessage());
            }
        }

        $appointment->delete();
        return response()->json(["message" => 200]);
    }

    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $reason = $request->input('reason', null);
        $motivoTexto = $reason ? " Motivo: " . $reason : "";

        // 🔔 Notificación en el CRM del médico (Usa tu estructura limpia de pagos 💰)
        NotificacionService::enviar(
            $appointment->doctor_id,
            null,
            "La cita del paciente " . $appointment->patient->name . " para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y') . " ha sido cancelada por Recepción." . $motivoTexto,
            $appointment->doctor_id,
            'DOCTOR',
            '❌ Cita Cancelada',
            'CITA_CANCELADA',
            $appointment->id
        );

        // 🛡️ CONTROL DE SEGURIDAD: Solo envía el correo si el paciente posee email válido registrado
        if (!empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new CancellationAppointmentMail($appointment, $reason));
            } catch (\Exception $e) {
                Log::warning("No se pudo despachar el correo de respaldo al paciente: " . $e->getMessage());
            }
        }

        // El correo al médico se mantiene como respaldo secundario
        if (!empty($appointment->doctor->email)) {
            try {
                Mail::to($appointment->doctor->email)->send(new CancellationAppointmentMail($appointment, $reason));
            } catch (\Exception $e) {
                Log::warning("No se pudo despachar el correo de respaldo al médico: " . $e->getMessage());
            }
        }

        $appointment->update(['status' => 3]); // 3 = Cancelado

        // Limpieza de caché para el doctor
        $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}");
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}:year:{$year_current}");

        return response()->json([
            "message" => 200,
            "appointment" => $appointment
        ]);
    }
    public function updateCronState($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }
        $appointment->cron_state = 2;
        $appointment->save();
        return response()->json(['message' => 'Estado del cron actualizado con éxito']);
    }
    public function pendientesCron()
    {
        $appointments = Appointment::where('status', 1)->where('cron_state', 1)->orderBy("id", "desc")->get();
        return response()->json(AppointmentCollection::make($appointments));
    }
    public function storeExpress(Request $request): JsonResponse
{
    $request->validate([
        'doctor_id'                    => 'required|integer',
        'date_appointment'             => 'required|date',
        'speciality_id'                => 'required|integer',
        'doctor_schedule_join_hour_id' => 'required|integer',
        'amount'                       => 'required|numeric',
        'name'                         => 'required|string|max:250',
        'surname'                      => 'required|string|max:250',
        'n_doc'                        => 'required|string|max:50',
        'phone'                        => 'required|string|max:50',
        'email'                        => 'required|email',
    ]);

    $patient = Patient::where("n_doc", $request->n_doc)->first();
    $doctor = User::findOrFail($request->doctor_id);

    if (!$patient) {
        $patient = Patient::create([
            "name"    => $request->name,
            "surname" => $request->surname,
            "email"   => strtolower(trim($request->email)),
            "n_doc"   => $request->n_doc,
            "phone"   => $request->phone,
        ]);
        Log::info("✨ [Express] Nuevo paciente registrado silenciosamente. ID: #" . $patient->id);
    } else {
        $patient->update([
            "phone" => $request->phone,
            "email" => strtolower(trim($request->email))
        ]);
        Log::info("🔄 [Express] Paciente existente identificado. Vinculando cita al ID: #" . $patient->id);
    }

    $date_formatted = Carbon::parse($request->date_appointment)->format("Y-m-d H:i:s");
    
    $bloqueOcupado = Appointment::where('doctor_id', $request->doctor_id)
        ->where('date_appointment', $date_formatted)
        ->where('doctor_schedule_join_hour_id', $request->doctor_schedule_join_hour_id)
        ->whereNull('deleted_at')
        ->exists();

    if ($bloqueOcupado) {
        return response()->json([
            "message"      => 403,
            "message_text" => "Lo sentimos, este horario acaba de ser reservado por otro paciente. Por favor, seleccione otra hora."
        ], 200);
    }

    $appointment = Appointment::create([
        "doctor_id"                    => $request->doctor_id,
        'patient_id'                   => $patient->id,
        "clinica_id"                   => $request->clinica_id,
        "date_appointment"             => $date_formatted,
        "speciality_id"                => $request->speciality_id,
        "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
        'user_id'                      => $doctor->id,
        "amount"                       => $request->amount,
        "status_pay"                   => $request->status_pay ?? 2,
        "status"                       => $request->status ?? 1,
    ]);

    $appointment->load(['patient', 'speciality']);
    $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
    Cache::forget("dashboard:doctor:{$appointment->doctor_id}");
    Cache::forget("dashboard:doctor:{$appointment->doctor_id}:year:{$year_current}");

        // =========================================================================
    // 📲 MOTOR DE DOBLE CANAL DE NOTIFICACIONES EXPRESS (MÉDICO + RECEPCIÓN)
    // =========================================================================
    try {
        if (class_exists('NotificacionService')) {
            
            $medicoObj = User::find($appointment->doctor_id);
            $telefonoMedico = $medicoObj ? $medicoObj->mobile : '';
            $ownerTenantId = !empty($appointment->clinica_id) ? $appointment->clinica_id : 1;

            // 🟢 Alerta 1: Notificación al buzón privado del Especialista (Orden Alineado)
            NotificacionService::enviar(
                $appointment->doctor_id,    // 1. $usuarioId
                'DOCTOR',                   // 2. $rol
                $ownerTenantId,             // 3. $consultorioId
                $telefonoMedico,            // 4. $telefonoPaciente
                "📅 Cita Express: El paciente {$patient->name} {$patient->surname} solicita consulta para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y \a \l\a\s H:i'), // 5. $mensajeTexto
                '📅 Nueva Cita Express Solicitada', // 6. $tituloToastr
                'CONSULTA_NUEVA',           // 7. $tipoEnum
                $appointment->id            // 8. $refId
            );

            // 🏢 Alerta 2: Duplicación en tiempo real para el Canal de Recepción de la Clínica
            if (!empty($appointment->clinica_id)) {
                
                $recepcionistas = \App\Models\User::where('clinica_id', $appointment->clinica_id)
                    ->where('role', 'RECEPCION')
                    ->get();

                foreach ($recepcionistas as $recepcionista) {
                    NotificacionService::enviar(
                        $recepcionista->id,         // 1. $usuarioId
                        'RECEPCION',                // 2. $rol
                        $appointment->clinica_id,   // 3. $consultorioId
                        $recepcionista->mobile ?? '', // 4. $telefonoPaciente
                        "⚡ [Express Clínica] El paciente {$patient->name} {$patient->surname} agendó con el Dr. {$doctor->name} {$doctor->surname} para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y H:i'), // 5. $mensajeTexto
                        '🏢 Nueva Cita Express en Recepción', // 6. $tituloToastr
                        'CONSULTA_NUEVA_CLINICA',   // 7. $tipoEnum
                        $appointment->id            // 8. $refId
                    );
                }
            }
        }
    } catch (\Exception $e) {
        Log::error("🚨 Error inyectando el doble canal de notificaciones en cita Express: " . $e->getMessage());
    }

    // =========================================================================

    return response()->json([
        "message"          => 200,
        "appointment"      => $appointment,
        "amount"           => $appointment->amount,
        "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
        "patient"          => [
            "id"        => $appointment->patient->id,
            "email"     => $appointment->patient->email,
            "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
        ],
        "speciality"       => $appointment->speciality ? [
            "id"   => $appointment->speciality->id,
            "name" => $appointment->speciality->name,
        ] : NULL,
        "doctor_id"        => $appointment->doctor_id,
        "doctor"           => [
            "id"        => $doctor->id,
            "full_name" => $doctor->name . ' ' . $doctor->surname,
        ],
    ], 200);
}
}
