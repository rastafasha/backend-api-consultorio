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
     * (Optimizado para Recepción Centralizada Multi-Médico)
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $name_doctor = $request->search;
        $date = $request->date;

        $userLogueado = auth()->user();

        // 1. Iniciamos la query (El Global Scope 'TenantScoped' filtra la clínica actual en el fondo)
        $query = Appointment::query();

        // 2. DISCRIMINACIÓN DE PODERES POR ROL DE SPATIE (Req. A)
        if ($userLogueado && $userLogueado->hasRole('DOCTOR', 'api')) {
            // 🔒 Si es un Médico, lo encerramos estrictamente en sus propias citas
            $query->where('doctor_id', $userLogueado->id);
        }
        // Si el usuario es 'RECEPCION' o 'ASISTENTE', no entra al IF anterior,
        // permitiéndole por herencia listar el pool completo de citas de la clínica.

        // 3. Ejecutamos tu filtro avanzado y paginación original intacta [11]
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
        try {
            if (class_exists('NotificacionService')) {
                NotificacionService::enviar(
                    $appointment->doctor_id,
                    null,
                    "Tienes un nuevo paciente agendado para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
                    (string) $appointment->doctor_id,
                    'MEDICO',
                    '📅 Nueva Cita Agendada',
                    'CONSULTA_NUEVA',
                    $appointment->id
                );
            }
        } catch (\Exception $e) {
            Log::error("Aviso: Notificación interna de cita estándar en espera: " . $e->getMessage());
        }
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
        $appointment = Appointment::findOrFail($id);
        $doctor = User::where("id", $request->doctor_id)->first();
        $appointment->confimation = $request->confimation;
        $appointment->update();
        if ($request->confimation == 2) {
            NotificacionService::enviar(
                $appointment->doctor_id,
                $appointment->patient->phone,
                "Hola " . $appointment->patient->name . ", te confirmamos que tu cita médica para el día " . Carbon::parse($appointment->date_appointment)->format('d-m-Y') . " se encuentra oficialmente CONFIRMADA. ¡Te esperamos!",
                $appointment->patient_id,
                'PACIENTE',
                '📅 Tu Cita ha sido Confirmada',
                'CITA_AGENDADA',
                $appointment->id
            );
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
            "doctor" => $appointment->doctor_id ? [
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
            'MEDICO',
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
            'MEDICO',
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
            'doctor_id' => 'required|integer',
            'date_appointment' => 'required|date',
            'speciality_id' => 'required|integer',
            'doctor_schedule_join_hour_id' => 'required|integer',
            'amount' => 'required|numeric',
            'name' => 'required|string|max:250',
            'surname' => 'required|string|max:250',
            'n_doc' => 'required|string|max:50',
            'phone' => 'required|string|max:50',
            'email' => 'required|email'
        ]);
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::findOrFail($request->doctor_id);
        if (!$patient) {
            $patient = Patient::create([
                "name" => $request->name,
                "surname" => $request->surname,
                "email" => strtolower(trim($request->email)),
                "n_doc" => $request->n_doc,
                "phone" => $request->phone,
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
                "message" => 403,
                "message_text" => "Lo sentimos, este horario acaba de ser reservado por otro paciente. Por favor, seleccione otra hora."
            ], 200);
        }
        $appointment = Appointment::create([
            "doctor_id" => $request->doctor_id,
            'patient_id' => $patient->id,
            "date_appointment" => $date_formatted,
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            'user_id' => $doctor->id,
            "amount" => $request->amount,
            "status_pay" => $request->status_pay ?? 2,
            "status" => $request->status ?? 1,
        ]);
        $appointment->load(['patient', 'speciality']);
        $year_current = Carbon::parse($appointment->date_appointment)->format('Y');
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}");
        Cache::forget("dashboard:doctor:{$appointment->doctor_id}:year:{$year_current}");
        try {
            if (class_exists('NotificacionService')) {
                NotificacionService::enviar(
                    $appointment->doctor_id,
                    null,
                    "📅 Cita Express: El paciente {$patient->name} solicita consulta para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
                    (string) $appointment->doctor_id,
                    'MEDICO',
                    '📅 Nueva Cita Express Solicitada',
                    'CONSULTA_NUEVA',
                    $appointment->id
                );
            }
        } catch (\Exception $e) {
            Log::error("Aviso: Notificación interna al dashboard en espera: " . $e->getMessage());
        }
        return response()->json([
            "message" => 200,
            "appointment" => $appointment,
            "amount" => $appointment->amount,
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
                "full_name" => $doctor->name . ' ' . $doctor->surname,
            ],
        ], 200);
    }
}
