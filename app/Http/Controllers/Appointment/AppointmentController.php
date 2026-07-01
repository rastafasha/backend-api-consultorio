<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Jobs\NewAppointmentRegisterJob;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $name_doctor = $request->search;
        $date = $request->date;

        $appointments = Appointment::filterAdvance($speciality_id, $name_doctor, $date)->orderBy("id", "desc")
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

    // Ejecutamos el filtro avanzado y añadimos el Eager Loading de la dirección del consultorio
    $appointments = Appointment::filterAdvanceDoc($search_doctor, $search_patient, $date, $search)
        ->where('doctor_id', $doctor_id)
        ->with([
            'patient',
            'speciality',
            'doctor_schedule_join_hour.doctor_schedule_hour',
            'doctor_schedule_join_hour.doctor_schedule_day.doctor_address' // <-- CARGA LA DIRECCIÓN AQUÍ
        ])
        ->orderBy("id", "desc")
        ->paginate(10);

    return response()->json([
        "total" => $appointments->total(),
        "appointments" => AppointmentCollection::make($appointments)
    ]);
}


  
public function filter(Request $request)
{
    date_default_timezone_set('America/Caracas');
    Carbon::setLocale('es');
    DB::statement("SET lc_time_names = 'es_ES'");

    $date_appointment = Carbon::parse($request->date_appointment)
        ->setTimezone('America/Caracas')
        ->format('Y-m-d');

    $hour = $request->hour; // Ej: "09"
    $speciality_id = $request->speciality_id;

    $name_day = Carbon::parse($date_appointment)->dayName;

    // 1. Consulta optimizada usando comparación exacta (=) para la hora
    $doctor_query = DoctorScheduleDay::where("day", "like", "%" . $name_day . "%")
        ->whereHas("doctor", function ($q) use ($speciality_id) {
            $q->where("speciality_id", $speciality_id);
        })
        ->whereHas("schedule_hours", function ($q) use ($hour) {
            $q->whereHas("doctor_schedule_hour", function ($qs) use ($hour) {
                // CORRECCIÓN: Cambiado de 'like' a '=' para evitar traer minutos/horas erróneas
                $qs->where("hour", $hour); 
            });
        })
        ->with([
            'doctor.speciality', 
            'doctor_address',
            // CORRECCIÓN: Agregamos , $date_appointment aquí para heredarla a la capa intermedia
            'schedule_hours' => function($q) use ($hour, $date_appointment) {
                $q->whereHas("doctor_schedule_hour", function($qs) use ($hour) {
                    $qs->where("hour", $hour);
                })->with(['doctor_schedule_hour', 'appointments' => function($qa) use ($date_appointment) {
                    $qa->whereDate("date_appointment", $date_appointment);
                }]);
            }
        ])
        ->get();

    $doctors = collect([]);

    foreach ($doctor_query as $doctor_q) {
        $doctor = $doctor_q->doctor;
        $address = $doctor_q->doctor_address;

        if (!$doctor) continue; 

        $segmentsData = $doctor_q->schedule_hours->map(function ($segment) use ($date_appointment) {
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
        // Establecer la zona horaria antes de cualquier procesamiento de Carbon
        date_default_timezone_set('America/Caracas');
        Carbon::setLocale('es');
        DB::statement("SET lc_time_names = 'es_ES'");

        // 1. Normalizar la fecha ISO del Frontend a la zona horaria local de Caracas
        // Esto evita que "2026-07-02T04:00:00.000Z" se interprete erróneamente como otro día
        $date_appointment = Carbon::parse($request->date_appointment)
            ->setTimezone('America/Caracas')
            ->format('Y-m-d'); // Resultado: "2026-07-02"

        // 2. Normalizar la hora (Asegurar que si viene "09", busque "09:00:00" o coincida con tu BD)
        $hour = $request->hour;
        $speciality_id = $request->speciality_id;

        // 3. Validar que el doctor exista junto con su especialidad
        $doctor = User::with('speciality')->find($doctor_id);

        if (!$doctor) {
            return response()->json([
                "message" => "Doctor no encontrado",
                "doctor" => null
            ], 404);
        }

        // 4. Obtener el nombre del día basado en la fecha normalizada (Ej: para "2026-07-02" será "jueves")
        $name_day = Carbon::parse($date_appointment)->dayName;

        // 5. Ejecutar la consulta unificada con Eager Loading
        $segments = DoctorScheduleJoinHour::whereHas("doctor_schedule_day", function ($q) use ($doctor_id, $name_day) {
            $q->where("day", "like", "%" . $name_day . "%")
                ->where("user_id", $doctor_id)
                ->whereNull("deleted_at");
        })
            ->whereHas("doctor_schedule_hour", function ($q) use ($hour) {
                // Si en tu BD el campo 'hour' guarda solo el número (ej: 9 o 09), esto funcionará.
                // Si guarda la hora completa (ej: "09:00:00"), usa: $q->where("hour", "like", $hour . "%");
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

        $addresses = collect();

        // 6. Mapear los segmentos para la respuesta
        $segmentsData = $segments->map(function ($segment) use (&$addresses) {
            $is_appointment = $segment->appointments->isNotEmpty();
            $addressRelation = $segment->doctor_schedule_day->doctor_address ?? null;

            if ($addressRelation) {
                $addresses->put($addressRelation->id, [
                    "id" => $addressRelation->id,
                    "name_consultorio" => $addressRelation->name_consultorio,
                    "address" => $addressRelation->address,
                    "is_active" => $addressRelation->is_active,
                ]);
            }

            return [
                "id" => $segment->id,
                "doctor_schedule_day_id" => $segment->doctor_schedule_day_id,
                "doctor_schedule_hour_id" => $segment->doctor_schedule_hour_id,
                "is_appointment" => $is_appointment,
                "doctor_address_id" => $addressRelation->id ?? null,
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
                "speciality" => [
                    "id" => $doctor->speciality->id ?? null,
                    "name" => $doctor->speciality->name ?? null,
                ],
                "addresses" => $addresses->values()->all(),
            ],
            "segments" => $segmentsData
        ]);
    }




    public function config()
    {
        $hours = [
            [
                "id" => "08",
                "name" => "8:00 AM"
            ],
            [
                "id" => "09",
                "name" => "09:00 AM"
            ],
            [
                "id" => "10",
                "name" => "10:00 AM"
            ],
            [
                "id" => "11",
                "name" => "11:00 AM"
            ],
            [
                "id" => "12",
                "name" => "12:00 PM"
            ],
            [
                "id" => "13",
                "name" => "01:00 PM"
            ],
            [
                "id" => "14",
                "name" => "02:00 PM"
            ],
            [
                "id" => "15",
                "name" => "03:00 PM"
            ],
            [
                "id" => "16",
                "name" => "04:00 PM"
            ],
            [
                "id" => "17",
                "name" => "05:00 PM"
            ],
        ];
        // $specialities = Specialitie::where("state",1)->get();

        $specialities = Specialitie::where('state', 1)->has('activeDoctors')->with('activeDoctors')->get();

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
            return response()->json([
                "message" => 403,
            ]);
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
        )->orderBy("id", "desc")
            ->get();

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

    public function appointmensByDoctor(Request $request, $doctor_id)
    {

        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        $appointments = Appointment::where('doctor_id', $doctor_id)
            ->where('status', 1) // o el estado de pendiente
            ->get();

        return response()->json([
            // "patients"=> $patients,
            "appointmens" => $appointments,
            "total" => $appointments->total(),
            // "pa_assessments"=>$patient->pa_assessments ? json_decode($patient->pa_assessments) : [],
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

        $patient = null;
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::where("id", $request->doctor_id)->first();

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
            $patient->person->update([
                'name_companion' => $request->name_companion,
                'surname_companion' => $request->surname_companion,
            ]);
        }

        $appointment = Appointment::create([
            "doctor_id" => $request->doctor_id,
            'patient_id' => $patient->id,
            "date_appointment" => Carbon::parse($request->date_appointment)->format("Y-m-d h:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            // Si auth()->id() es null, podrías asignar el user_id del doctor o el del paciente
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



        // Mail::to($appointment->patient->email)->send(new RegisterAppointment($appointment));
        // Mail::to($doctor->email)->send(new NewAppointmentRegisterMail($appointment));
        // NewAppointmentRegisterJob::dispatch($appointment)->onQueue('emails');

        // =========================================================================
        // 🧪 VENENO INYECTADO: ÚNICAMENTE SE NOTIFICA AL MÉDICO (KLYNTIC)
        // =========================================================================

        // El paciente va a su listado por flujo de Angular. Solo avisamos al Médico.
        NotificacionService::enviar(
            $appointment->doctor_id,
            null, // No WhatsApp
            "Tienes un nuevo paciente agendado para el " . Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
            $appointment->doctor_id,
            'MEDICO',
            '📅 Nueva Cita Agendada',
            'CONSULTA_NUEVA', // Tu enum corregido
            $appointment->id
        );



        return response()->json([
            "message" => 200,
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
            "speciality" => $appointment->speciality ? [ // Dejamos solo la versión formateada limpia
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




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
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




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {

        $appointment = Appointment::findOrFail($id);

        if ($appointment->payments->sum("amount") > $request->amount) {
            return response()->json([
                "message" => 403,
                "message_text" => "Los Pagos ingresados superan al nuevo monto que quiere guardar"
            ]);
        }

        $appointment->update([
            "doctor_id" => $request->doctor_id,
            "date_appointment" => Carbon::parse($request->date_appointment)->format("Y-m-d h:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            "amount" => $request->amount,
            "status_pay" => $appointment->payments->sum("amount") != $request->amount ? 2 : 1,
        ]);

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json([
            "message" => 200,
        ]);
    }

    public function atendidas()
    {

        $appointments = Appointment::where('status', 2)->orderBy("id", "desc")
            ->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);

    }
    public function pendientes()
    {

        $appointments = Appointment::
            where('status', 1)
            // ->orWhere('confimation', 1)
            ->orderBy("id", "desc")
            ->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);

    }

    public function pagosPendientesShowId(Request $request, $doctor_id)
    {

        $appointments = Appointment::
            where("doctor_id", $doctor_id)
            ->where('status', 1)
            ->orderBy("id", "desc")
            ->paginate(10);
        return response()->json([
            "total" => $appointments->total(),
            "appointments" => AppointmentCollection::make($appointments)
        ]);

    }

    public function updateConfirmation(Request $request, $id)
    {
        $appointment = Appointment::findOrfail($id);
        $doctor = User::where("id", $request->doctor_id)->first();

        // Update confirmation status without modifying appointment time
        $appointment->confimation = $request->confimation;
        $appointment->update();

        // error_log($appointment);

        // if($request->confimation === '2'){
        //     Mail::to($appointment->patient->email)->send(new ConfirmationAppointment($appointment));

        // }
        // Ejemplo para el futuro: Solo envía el correo si el campo no está vacío
        // if (!empty($appointment->patient->email)) {
        //     NewAppointmentRegisterJob::dispatch($appointment)->onQueue('emails');
        // }

        // =========================================================================
        // 🧪 VENENO INYECTADO: NOTIFICACIÓN DE CONFIRMACIÓN AL PACIENTE (KLYNTIC)
        // =========================================================================
        if ($request->confimation == 2) {
            NotificacionService::enviar(
                $appointment->doctor_id,                                              // Consultorio ID para WhatsApp
                $appointment->patient->phone,                                         // Teléfono del paciente
                "Hola " . $appointment->patient->name . ", te confirmamos que tu cita médica para el día " . Carbon::parse($appointment->date_appointment)->format('d-m-Y') . " se encuentra oficialmente CONFIRMADA. ¡Te esperamos!",
                $appointment->patient_id,                                             // ID del paciente para la campana de Angular
                'PACIENTE',                                                           // Rol
                '📅 Tu Cita ha sido Confirmada',                                      // Título Toastr
                'CITA_AGENDADA',                                                      // Enum tipo
                $appointment->id                                                      // Referencia de la cita en MySQL
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
            "patient" => $appointment->patient_id ?
                [
                    "id" => $appointment->patient->id,
                    "email" => $appointment->patient->email,
                    "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                ] : NULL,
            "speciality" => $appointment->speciality ?
                [
                    "id" => $appointment->speciality->id,
                    "name" => $appointment->speciality->name,
                ] : NULL,
            "doctor_id" => $appointment->doctor_id,
            "doctor" => $appointment->doctor_id ?
                [
                    "id" => $doctor->id,
                    "email" => $doctor->email,
                    "full_name" => $doctor->name . ' ' . $doctor->surname,
                ] : NULL,

        ]);



    }

    public function cancelarCita($id)
    {
        $appointment = Appointment::findOrFail($id);

        // Send cancellation emails
        Mail::to($appointment->patient->email)
            ->send(new CancellationAppointmentMail($appointment));

        Mail::to($appointment->doctor->email)
            ->send(new CancellationAppointmentMail($appointment));

        $appointment->delete();
        return response()->json([
            "message" => 200,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $reason = $request->input('reason', null);

        // Send cancellation emails
        Mail::to($appointment->patient->email)
            ->send(new CancellationAppointmentMail($appointment, $reason));

        Mail::to($appointment->doctor->email)
            ->send(new CancellationAppointmentMail($appointment, $reason));

        // Update status instead of deleting
        $appointment->update(['status' => 3]); // 3 = cancelled status

        return response()->json([
            "message" => 200,
            "appointment" => $appointment
        ]);
    }

    public function updateCronState($id)
    {
        // Buscamos la cita por su ID
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        // Cambiamos el estado a 2 (Notificado) para que ya no salga en "pendientes"
        $appointment->cron_state = 2;
        $appointment->save();

        return response()->json(['message' => 'Estado del cron actualizado con éxito']);
    }

    public function pendientesCron()
    {
        // 1. Buscamos citas activas (status 1) Y que NO hayan sido notificadas (cron_state 1)
        // 2. Quitamos la paginación para procesarlas todas de golpe (.get() o .take())
        $appointments = Appointment::where('status', 1)
            ->where('cron_state', 1)
            ->orderBy("id", "desc")
            ->get(); // Trae todas las pendientes reales sin paginar

        return response()->json(
            AppointmentCollection::make($appointments)
        );
    }

}
