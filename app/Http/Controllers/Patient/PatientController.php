<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Http\Resources\Patient\PatientCollection;
use App\Http\Resources\Patient\PatientResource;
use App\Mail\NewPatientRegisterMail;
use App\Models\Appointment\Appointment;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientPerson;
use App\Models\User;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $patients = Patient::where(
            DB::raw("CONCAT(patients.name,' ', COALESCE(patients.surname,''),' ',patients.email)"),
            "ilike",
            "%" . $search . "%"
        )->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $patients->total(),
            "patients" => PatientCollection::make($patients),

        ]);
    }

    public function patientsByDoctor(Request $request, $doctor_id)
    {
        // Limpiamos la variable de espacios en blanco y forzamos a que si viene un "null" de Angular se vuelva falso
        $search = trim($request->search);
        if ($search === 'null' || $search === 'undefined') {
            $search = '';
        }

        $patients = Patient::whereHas('doctors', function ($query) use ($doctor_id) {
            $query->where('doctor_patient.doctor_id', $doctor_id);
        })
            ->where(function ($query) use ($search) {
                // 💡 Solo aplicamos los filtros si realmente el usuario escribió algo real en el buscador
                if (!empty($search)) {
                    $query->where('name', 'ilike', "%" . $search . "%")
                        ->orWhere('surname', 'ilike', "%" . $search . "%")
                        ->orWhere('email', 'ilike', "%" . $search . "%")
                        ->orWhere('n_doc', 'ilike', "%" . $search . "%"); // 👈 Agregamos tu validación de número de documento
                }
            })
            ->orderBy("id", "desc")
            ->paginate(10);

        return response()->json([
            "total" => $patients->total(),
            "patients" => PatientCollection::make($patients)
        ]);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function profileRedis($id)
    {
        //uso de redis
        $cachedRecord = Redis::get('profile_patient_#'.$id);
        $data_patient = [];
        if(isset($cachedRecord)) {
            $data_patient = json_decode($cachedRecord, FALSE);
        }else{

            $patient = Patient::findOrFail($id);

            $num_appointment = Appointment::where("patient_id",$id)->count();
            $money_of_appointments = Appointment::where("patient_id",$id)->sum("amount");
            $num_appointment_pendings = Appointment::where("patient_id",$id)->where("status",1)->count();
            $appointment_pendings = Appointment::where("patient_id",$id)->where("status",1)->get();
            $appointments = Appointment::where("patient_id",$id)->get();

            $data_patient = [
                "num_appointment"=>$num_appointment,
                "money_of_appointments"=> $money_of_appointments,
                "num_appointment_pendings"=>$num_appointment_pendings,
                "patient" => PatientResource::make($patient),
                "appointment_pendings"=> AppointmentCollection::make($appointment_pendings),
                "appointments"=>$appointments->map(function($appointment){
                    return [
                        "id"=> $appointment->id,
                        "patient"=> [
                            "id"=> $appointment->patient->id,
                            "full_name"=> $appointment->patient->name.' '.$appointment->patient->surname,
                            "avatar"=> $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : null,
                        ],
                        "doctor"=> [
                            "id"=> $appointment->doctor->id,
                            "full_name"=> $appointment->doctor->name.' '.$appointment->doctor->surname,
                            "avatar"=> $appointment->doctor->avatar ? env("APP_URL")."storage/".$appointment->doctor->avatar : null,
                        ],
                        "date_appointment" =>$appointment->date_appointment,
                        "date_appointment_format" =>Carbon::parse($appointment->date_appointment)->format("d M Y"),
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "appointment_attention"=> $appointment->attention ?[
                            "id"=>$appointment->attention->id,
                            "description"=>$appointment->attention->description,
                            "receta_medica"=>$appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
                            "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
                        ]: NULL,
                        "amount" =>$appointment->amount,
                        "status_pay" =>$appointment->status_pay,
                        "status" =>$appointment->status,
                    ];
                }),
            ];

            Redis::set('profile_patient_#'.$id, json_encode($data_patient),'EX', 3600);
        }
        //uso de redis

        //sin redis
        

        //sin redis

        return response()->json($data_patient);
    }

    public function profile($id)
{
    $data_patient = [];
    $patient = Patient::findOrFail($id);

    // 1. OPTIMIZACIÓN: Añadimos 'withSum' para traer el total pagado de cada cita de un solo golpe
    $all_appointments = Appointment::with([
        'doctor_schedule_join_hour.doctor_schedule_hour',
        'doctor_schedule_join_hour.doctor_schedule_day.doctor_address',
        'patient',
        'doctor.speciality',
        'speciality',
        'attention'
    ])
    // Buscamos en la relación 'payments' (ajusta el nombre si en tu modelo Appointment la relación se llama distinto)
    // y sumamos la columna 'amount'. Esto creará un atributo automático llamado 'payments_sum_amount'
    ->withSum('payments', 'amount') 
    ->where('patient_id', $id)
    ->orderBy("id", "desc")
    ->get();

    // 🔹 EL TRUCO: Modificamos la colección agregando el atributo 'deuda' dinámicamente antes de enviarla al Resource
    $all_appointments->transform(function ($appointment) {
        $total_pagado = $appointment->payments_sum_amount ?? 0;
        
        // Seteamos la propiedad en el objeto del modelo para que el Resource pueda leerla
        $appointment->deuda = $appointment->amount - $total_pagado; 
        
        return $appointment;
    });

    // Ahora que todos los objetos tienen su propiedad ->deuda calculada, filtramos
    $appointment_checkeds = $all_appointments->where('status', 2);
    $appointment_pendings = $all_appointments->where('status', 1);

    $data_patient = [
        "num_appointment" => $all_appointments->count(),
        "num_appointment_checkeds" => $appointment_checkeds->count(),
        "num_appointment_pendings" => $appointment_pendings->count(),
        "money_of_appointments" => $all_appointments->sum("amount"),

        // Al pasar por el Collection, el AppointmentResource ya leerá la deuda real en lugar de null 🎉
        "appointment_checkeds" => AppointmentCollection::make($appointment_checkeds),
        "appointment_pendings" => AppointmentCollection::make($appointment_pendings),
        
        "patient" => PatientResource::make($patient),
        "appointments" => $all_appointments->map(function ($appointment) {

            // 2. CÁLCULO DE LA DEUDA: Restamos lo pagado del costo total de la cita
            $total_pagado = $appointment->payments_sum_amount ?? 0;
            $deuda = $appointment->amount - $total_pagado;

            return [
                "id" => $appointment->id,
                "doctor_schedule_join_hour_id" => $appointment->doctor_schedule_join_hour_id,
                "segment_hour" => $appointment->doctor_schedule_join_hour ? [
                    "id" => $appointment->doctor_schedule_join_hour->id,
                    "format_segment" => $appointment->doctor_schedule_join_hour->doctor_schedule_hour ? [
                        "hour_start" => $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                        "format_hour_start" => Carbon::parse($appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                        "hour_end" => $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                        "format_hour_end" => Carbon::parse($appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                    ] : null,
                ] : null,

                "consultorio" => ($appointment->doctor_schedule_join_hour &&
                    $appointment->doctor_schedule_join_hour->doctor_schedule_day &&
                    $appointment->doctor_schedule_join_hour->doctor_schedule_day->doctor_address)
                    ? [
                        "id" => $appointment->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->id,
                        "name_consultorio" => $appointment->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->name_consultorio,
                        "address" => $appointment->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->address,
                        "is_active" => $appointment->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->is_active,
                    ]
                    : null,

                "patient" => [
                    "id" => $appointment->patient->id,
                    "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                    "avatar" => $appointment->patient->avatar ? env("APP_URL") . $appointment->patient->avatar : null,
                ],
                "doctor" => [
                    "id" => $appointment->doctor->id,
                    "full_name" => $appointment->doctor->name . ' ' . $appointment->doctor->surname,
                    "avatar" => $appointment->doctor->avatar ? env("APP_URL") . $appointment->doctor->avatar : null,
                    "mobile" => $appointment->doctor->mobile,
                    "moneda" => $appointment->doctor->moneda,
                    "speciality_id" => $appointment->doctor->speciality_id,
                    "speciality" => $appointment->doctor->speciality ? [
                        "id" => $appointment->doctor->speciality->id,
                        "name" => $appointment->doctor->speciality->name,
                        "price" => $appointment->doctor->speciality->price,
                    ] : NULL,
                ],
                "date_appointment" => $appointment->date_appointment,
                "date_appointment_format" => Carbon::parse($appointment->date_appointment)->format("d M Y"),

                "format_hour_start" => $appointment->doctor_schedule_join_hour?->doctor_schedule_hour
                    ? Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A")
                    : null,
                "format_hour_end" => $appointment->doctor_schedule_join_hour?->doctor_schedule_hour
                    ? Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A")
                    : null,
                "appointment_attention" => $appointment->attention ? [
                    "id" => $appointment->attention->id,
                    "description" => $appointment->attention->description,
                    "receta_medica" => $appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
                    "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
                ] : NULL,
                
                "amount" => $appointment->amount,
                "deuda" => $deuda, // <--- NUEVO CAMPO ENVIADO AL FRONTEND 🎉
                "status_pay" => $appointment->status_pay,
                "status" => $appointment->status,
            ];
        }),
    ];

    return response()->json($data_patient);
}


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function store(Request $request) 
{
    // 1. Validar que el paciente no exista
    $patient_is_valid = Patient::where("n_doc", $request->n_doc)->first();
    if ($patient_is_valid) {
        return response()->json([
            "message" => 403,
            "message_text" => 'el paciente ya existe'
        ], 403);
    }

    // 2. Procesar el Avatar con Cloudinary
    $path = null;
    if ($request->hasFile('imagen')) {
        $cloudinaryResponse = Cloudinary::uploadApi()->upload(
            $request->file('imagen')->getRealPath(), 
            ['folder' => 'klyntic/patients']
        );
        $path = $cloudinaryResponse['secure_url'];
        // CORRECCIÓN: Guardamos tanto 'avatar' como 'image' para dar compatibilidad a ambas tablas
        $request->merge(["avatar" => $path, "image" => $path]); 
    }

    // 3. Formatear fecha de nacimiento de forma segura
    if ($request->birth_date) {
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
        $request->merge(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d H:i:s')]);
    }

    // 4. Guardar la ficha del paciente de forma explícita o segura
    // Pasamos el $request->all() pero ahora va con el campo 'image' inyectado
    $patient = Patient::create($request->all());

    // 5. Vincular al doctor logueado en la relación de muchos a muchos
    $doctorId = auth()->id() ?? $request->doctor_id; 

    if ($doctorId) {
        $patient->doctors()->attach($doctorId);
        
        // =========================================================================
        // ⚡ LIMPIEZA DE CACHÉ EN REDIS (KLYNTIC)
        // =========================================================================
        // Como el médico ahora tiene un nuevo paciente asociado, borramos la caché 
        // de su dashboard para que la lista de pacientes recientes se actualice al tiro.
        Cache::forget("dashboard:doctor:{$doctorId}");
    } else {
        // CORRECCIÓN: Cambiado a json_encode para evitar que tumbe el servidor
        Log::warning("Paciente creado sin doctor asociado. Request data: " . json_encode($request->all()));
    }

    // 6. Guardar datos complementarios en PatientPerson
    // Para evitar que explote por columnas sobrantes, extraemos solo lo que le pertenece
    PatientPerson::create([
        'patient_id'        => $patient->id,
        'name_companion'    => $request->name_companion,
        'surname_companion' => $request->surname_companion,
        // Agrega aquí cualquier otro campo específico de la tabla personas si te hace falta
    ]);

    return response()->json([
        "message" => 200,
        "patient" => $patient
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
        $patient = Patient::findOrFail($id);

        return response()->json([
            "patient" => PatientResource::make($patient),
        ]);
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
    // 1. Validar que la cédula/documento no la tenga otro paciente
    $patient_is_valid = Patient::where("id", "!=", (int) $id)
        ->where("n_doc", $request->n_doc)
        ->first();

    if ($patient_is_valid) {
        return response()->json([
            "message" => 403,
            "message_text" => 'el paciente ya existe'
        ], 403);
    }

    $patient = Patient::findOrFail($id);

    // 2. Procesar nueva imagen en Cloudinary (Si viene en el request)
    $path = $patient->image; // Conservamos la imagen actual por defecto
    if ($request->hasFile('imagen')) {
        // Si ya tenía foto anterior, la borramos para no acumular basura en Cloudinary
        if ($patient->image) {
            $publicId = 'klyntic/patients/' . pathinfo($patient->image, PATHINFO_FILENAME);
            Cloudinary::uploadApi()->destroy($publicId);
        }

        // Subimos el archivo nuevo a la carpeta correcta
        $cloudinaryResponse = Cloudinary::uploadApi()->upload(
            $request->file('imagen')->getRealPath(), 
            ['folder' => 'klyntic/patients']
        );
        $path = $cloudinaryResponse['secure_url'];
    }

    // Inyectamos de forma segura las variables procesadas al request
    $request->merge(["avatar" => $path, "image" => $path]);

    // 3. Formatear fecha de nacimiento de forma segura (Formato 24 Horas "H")
    if ($request->birth_date) {
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
        $request->merge(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d H:i:s')]);
    }

    // 4. UN SOLO UPDATE LIMPIO: Seteamos los datos de la tabla pacientes (excluyendo doctor_id)
    $patient->update($request->except('doctor_id'));

    // 5. Sincronizamos la relación en la tabla intermedia muchos a muchos
    if ($request->has('doctor_id')) {
        $patient->doctors()->sync($request->doctor_id);
        
        // =========================================================================
        // ⚡ LIMPIEZA DE CACHÉ EN REDIS (KLYNTIC)
        // =========================================================================
        // Al actualizar el paciente o sus médicos asociados, borramos la caché del 
        // dashboard del doctor para garantizar tiempo real exacto en el CRM.
        $doctorId = auth()->id() ?? $request->doctor_id;
        Cache::forget("dashboard:doctor:{$doctorId}");
    }

    // 6. Actualizar de forma segura la tabla complementaria Person
    if ($patient->person) {
        $patient->person->update([
            'name_companion'    => $request->name_companion,
            'surname_companion' => $request->surname_companion,
            'mobile_companion' => $request->mobile_companion,
            'relationship_companion' => $request->relationship_companion,
            'mobile_responsable' => $request->mobile_responsable,
            'name_responsable' => $request->name_responsable,
            'surname_responsable' => $request->surname_responsable,
            'relationship_responsable' => $request->relationship_responsable,
        ]);
    }

    return response()->json([
        "message" => 200,
        "patient" => $patient
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
        $patient = Patient::findOrFail($id);
        if ($patient->avatar) {
            Storage::delete($patient->avatar);
        }
        //uso de redis
        // $cachedRecord = Redis::get('profile_patient_#'.$id);
        // if(isset($cachedRecord)) {
        //     Redis::del('profile_patient_#'.$id);
        // }
        $patient->delete();
        return response()->json([
            "message" => 200
        ]);
    }

    public function showPatientbyLocation($location_id)
    {

        // $doctors = Patient::join('users', 'patients.id', '=', 'users.id')
        // ->where('location_id',$location_id)
        // ->select(

        //     'patients.id as id',
        //     'users.name',
        //     'users.surname',
        //     'users.location_id',
        //     )
        // ->get();


        $doctors = User::where('location_id', $location_id)->get();
        $patients = Patient::where('location_id', $location_id)->get();

        return response()->json([

            "patients" => $patients,
            // "patients" => PatientCollection::make($patients),

            "doctors" => $doctors,


        ]);
    }

    public function verificarDocumento($n_doc)
    {
        $existe = Patient::withTrashed()->where('n_doc', trim($n_doc))->exists();

        return response()->json([
            'existe' => $existe
        ], 200);
    }

}
