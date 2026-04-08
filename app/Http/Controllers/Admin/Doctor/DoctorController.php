<?php

namespace App\Http\Controllers\Admin\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Mail\NewUserRegisterMail;
use App\Mail\UpdateStatusMail;
use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleHour;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\Specialitie;
use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // $this->authorize('viewAny', User::class);
        // dd(!auth('api')->user()->can('list_appointment'));
        // if(!auth('api')->user()->can('list_doctor')){
        //     return response()->json(["message"=>"El usuario no esta autenticado"],403);
        //    }


        $search = $request->search;
        $users = User::where(DB::raw("CONCAT(users.name,' ',IFNULL(users.surname,''),' ',users.email)"), "like", "%" . $search . "%")
            // "name", "like", "%".$search."%"
            // ->orWhere("surname", "like", "%".$search."%")
            // ->orWhere("email", "like", "%".$search."%")
            ->orderBy("id", "desc")
            ->whereHas("roles", function ($q) {
                $q->where("name", "like", "%DOCTOR%");
            })
            ->get();

        return response()->json([
            "users" => UserCollection::make($users),

        ]);
    }
    public function config()
    {
        $roles = Role::where("name", "like", "%DOCTOR%")->get();

        $specialities = Specialitie::where("state", 1)->get();
        $countries = Country::get();

        $hours_days = collect([]);

        $doctor_schedule_hours = DoctorScheduleHour::all();
        foreach ($doctor_schedule_hours->groupBy("hour") as $key => $schedule_hour) {
            // dd($schedule_hour);
            $hours_days->push([
                "hour" => $key,
                "format_hour" => Carbon::parse(date("Y-m-d") . ' ' . $key . ":00:00")->format("h:i A"),
                "items" => $schedule_hour->map(function ($hour_item) {
                    return [
                        "id" => $hour_item->id,
                        "hour_start" => $hour_item->hour_start,
                        "hour_end" => $hour_item->hour_end,
                        "format_hour_start" => Carbon::parse(date("Y-m-d") . ' ' . $hour_item->hour_start)->format("h:i A"),
                        "format_hour_end" => Carbon::parse(date("Y-m-d") . ' ' . $hour_item->hour_end)->format("h:i A"),
                        "hour" => $hour_item->hour,

                    ];
                }),
            ]);

        }
        return response()->json([
            "roles" => $roles,
            "specialities" => $specialities,
            "countries" => $countries,
            "hours_days" => $hours_days,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile($id)
    {
        // if(!auth('api')->user()->can('profile_doctor')){
        //     return response()->json(["message"=>"El usuario no esta autenticado"],403);
        //    }
        //con redis    
        // $cachedRecord = Redis::get('profile_doctor_#'.$id);
        // $data_doctor = [];
        // if(isset($cachedRecord)) {
        //     $data_doctor = json_decode($cachedRecord, FALSE);
        // }else{
        //     $user = User::findOrFail($id);

        // $num_appointment = Appointment::where("doctor_id",$id)->count();
        // $money_of_appointments = Appointment::where("doctor_id",$id)->sum("amount");
        // $num_appointment_pendings = Appointment::where("doctor_id",$id)->where("status",1)->count();
        // $appointment_pendings = Appointment::where("doctor_id",$id)->where("status",1)->get();
        // $appointments = Appointment::where("doctor_id",$id)->get();
        // $data_doctor = [
        //     "num_appointment"=>$num_appointment,
        //     "money_of_appointments"=> $money_of_appointments,
        //     "num_appointment_pendings"=>$num_appointment_pendings,
        //     "doctor" => UserResource::make($user),
        //     "appointment_pendings"=> AppointmentCollection::make($appointment_pendings),
        //     "appointments"=>$appointments->map(function($appointment){
        //         return [
        //             "id"=> $appointment->id,
        //             "patient"=> [
        //                 "id"=> $appointment->patient->id,
        //                 "full_name"=> $appointment->patient->name.' '.$appointment->patient->surname,
        //                 "avatar"=> $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : 'https://cdn-icons-png.flaticon.com/512/1430/1430453.png',
        //             ],
        //             "doctor"=> [
        //                 "id"=> $appointment->doctor->id,
        //                 "full_name"=> $appointment->doctor->name.' '.$appointment->doctor->surname,
        //                 "avatar"=> $appointment->doctor->avatar ? env("APP_URL")."storage/".$appointment->doctor->avatar : NULL,
        //             ],
        //             "date_appointment" =>$appointment->date_appointment,
        //             "date_appointment_format" =>Carbon::parse($appointment->date_appointment)->format("d M Y"),
        //             "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
        //             "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
        //             "appointment_attention"=> $appointment->attention ?[
        //                 "id"=>$appointment->attention->id,
        //                 "description"=>$appointment->attention->description,
        //                 "receta_medica"=>$appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
        //                 "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
        //             ]: NULL,
        //             "amount" =>$appointment->amount,
        //             "status_pay" =>$appointment->status_pay,
        //             "status" =>$appointment->status,
        //         ];
        //     }),
        // ];

        //     Redis::set('profile_doctor_#'.$id, json_encode($data_doctor),'EX', 3600);
        // }
        //con redis    
        //sin redis   
        $data_doctor = [];


        // $user = User::findOrFail($id);
        $user = User::with(['schedule_days.schedule_hours.doctor_schedule_hour'])->findOrFail($id);

        $num_appointment = Appointment::where("doctor_id", $id)->count();
        $money_of_appointments = Appointment::where("doctor_id", $id)->sum("amount");
        $num_appointment_pendings = Appointment::where("doctor_id", $id)->where("status", 1)->count();
        $appointment_pendings = Appointment::where("doctor_id", $id)
            ->where("status", 1)
            ->paginate(10);
        // ->get();
        $appointments = Appointment::where("doctor_id", $id)->get();
        $data_doctor = [
            "num_appointment" => $num_appointment,
            "money_of_appointments" => $money_of_appointments,
            "num_appointment_pendings" => $num_appointment_pendings,
            "doctor" => UserResource::make($user),
            "appointment_pendings" => AppointmentCollection::make($appointment_pendings),
            "schedule_selecteds" => $user->schedule_days->flatMap(function ($day) {
                return $day->schedule_hours->map(function ($pivot) use ($day) {
                    return [
                        "day_name" => $day->day,
                        "item" => [
                            "id" => $pivot->doctor_schedule_hour_id,
                            "hour_start" => optional($pivot->doctor_schedule_hour)->hour_start,
                            "hour_end" => optional($pivot->doctor_schedule_hour)->hour_end,
                        ]
                    ];
                });
            })->unique(function ($item) {
                // Esto asegura que la combinación Día + ID de Hora sea única
                return $item['day_name'] . $item['item']['id'];
            })->values(), // values() resetea los índices del array para que Angular no reciba un objeto
            "appointments" => $appointments->map(function ($appointment) {
                return [
                    "id" => $appointment->id,
                    "patient" => [
                        "id" => $appointment->patient->id,
                        "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                        "avatar" => $appointment->patient->avatar ? env("APP_URL") . "storage/" . $appointment->patient->avatar : 'https://cdn-icons-png.flaticon.com/512/1430/1430453.png',
                    ],
                    "doctor" => [
                        "id" => $appointment->doctor->id,
                        "full_name" => $appointment->doctor->name . ' ' . $appointment->doctor->surname,
                        "avatar" => $appointment->doctor->avatar ? env("APP_URL") . "storage/" . $appointment->doctor->avatar : NULL,
                    ],
                    "date_appointment" => $appointment->date_appointment,
                    "date_appointment_format" => Carbon::parse($appointment->date_appointment)->format("d M Y"),
                    // "format_hour_start" => Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                    // "format_hour_end" => Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                    "appointment_attention" => $appointment->attention ? [
                        "id" => $appointment->attention->id,
                        "description" => $appointment->attention->description,
                        "receta_medica" => $appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
                        "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
                    ] : NULL,
                    "amount" => $appointment->amount,
                    "status_pay" => $appointment->status_pay,
                    "status" => $appointment->status,
                ];
            }),
        ];

        //sin redis    

        return response()->json($data_doctor);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // if(!auth('api')->user()->can('create_doctor')){
        //     return response()->json(["message"=>"El usuario no esta autenticado"],403);
        //    }
        $schedule_hours = json_decode($request->schedule_hours, 1);

        $user_is_valid = User::where("email", $request->email)->first();

        if ($user_is_valid) {
            return response()->json([
                "message" => 403,
                "message_text" => 'el usuario con este email ya existe'
            ]);
        }

        if ($request->hasFile('imagen')) {
            $path = Storage::putFile("staffs", $request->file('imagen'));
            $request->request->add(["avatar" => $path]);
        }

        if ($request->password) {
            $request->request->add(["password" => Hash::make($request->password)]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);

        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);

        $user = User::create($request->all());
        // error_log($user);

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        Mail::to($user->email)->send(new NewUserRegisterMail($user));

        //almacenar la disponibilidad de horario del doctor
        foreach ($schedule_hours as $key => $schedule_hour) {
            if (sizeof($schedule_hour["children"]) > 0) {
                $schedule_day = DoctorScheduleDay::create([
                    "user_id" => $user->id,
                    "day" => $schedule_hour["day_name"],
                ]);

                foreach ($schedule_hour["children"] as $children) {
                    DoctorScheduleJoinHour::create([
                        "doctor_schedule_day_id" => $schedule_day->id,
                        "doctor_schedule_hour_id" => $children["item"]["id"],
                    ]);
                }
            }
        }
        return response()->json([
            "message" => 200,
            "user" => $user
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
        $user = User::findOrFail($id);

        return response()->json([
            "user" => UserResource::make($user),
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {

        $schedule_hours = json_decode($request->schedule_hours, 1);

        $user_is_valid = User::where("id", "<>", $id)->where("email", $request->email)->first();

        if ($user_is_valid) {
            return response()->json([
                "message" => 403,
                "message_text" => 'el usuario con este email ya existe'
            ]);
        }

        $user = User::findOrFail($id);

        if ($request->hasFile('imagen')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("staffs", $request->file('imagen'));
            $request->request->add(["avatar" => $path]);
        }

        if ($request->password) {
            $request->request->add(["password" => Hash::make($request->password)]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);

        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);

        //uso de redis
        // $cachedRecord = Redis::get('profile_doctor_#'.$id);
        // if(isset($cachedRecord)) {
        //     Redis::del('profile_doctor_#'.$id);
        // }

        if ($request->role_id) {
            $role_new = Role::findOrFail($request->role_id);

            // Si el usuario ya tiene roles, comprobamos si es diferente
            $currentRole = $user->roles->first();

            if (!$currentRole || $currentRole->id != $request->role_id) {
                // En Spatie, es más limpio usar syncRoles para reemplazar
                $user->syncRoles([$role_new->name]);
            }
        }

        $user->update($request->all());

        // 1. Decodificar el JSON (Asegúrate de que sea schedule_hours)
        $schedule_hours_raw = json_decode($request->schedule_hours, true);

        if (is_array($schedule_hours_raw)) {
            $keep_join_ids = []; // Aquí guardaremos los IDs que el doctor quiere mantener

            foreach ($schedule_hours_raw as $segment) {
                $dayName = $segment['day_name'] ?? null;
                $hour_id = $segment['item']['id'] ?? null;

                if ($dayName && $hour_id) {
                    // 1. Buscamos o creamos el día
                    $db_day = DoctorScheduleDay::firstOrCreate([
                        "user_id" => $user->id,
                        "day" => $dayName,
                    ]);

                    // 2. Buscamos o creamos la relación de la hora (Join)
                    $join = DoctorScheduleJoinHour::firstOrCreate([
                        "doctor_schedule_day_id" => $db_day->id,
                        "doctor_schedule_hour_id" => $hour_id,
                    ]);

                    // 3. Guardamos este ID para NO borrarlo
                    $keep_join_ids[] = $join->id;
                }
            }

            // --- LÓGICA DE LIMPIEZA SEGURA ---

            // Obtenemos todos los IDs actuales del doctor que NO están en la nueva lista
            $joins_to_remove = DoctorScheduleJoinHour::whereHas('doctor_schedule_day', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->whereNotIn('id', $keep_join_ids)
                ->get();

            foreach ($joins_to_remove as $old_join) {
                // IMPORTANTE: Solo borramos si el horario NO tiene citas asociadas
                $has_appointments = Appointment::where('doctor_schedule_join_hour_id', $old_join->id)->exists();

                if (!$has_appointments) {
                    $old_join->delete();
                }
                // Si tiene citas, no lo borramos para que segment_hour no sea null en el perfil
            }

            // Limpieza de días que quedaron vacíos (sin horas)
            DoctorScheduleDay::where('user_id', $user->id)
                ->doesntHave('schedule_hours')
                ->delete();
        }
        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        // if(!auth('api')->user()->can('delete_doctor')){
        //     return response()->json(["message"=>"El usuario no esta autenticado"],403);
        //    }
        $user = User::findOrFail($id);
        //uso de redis
        // $cachedRecord = Redis::get('profile_doctor_#'.$id);
        // if(isset($cachedRecord)) {
        //     Redis::del('profile_doctor_#'.$id);
        // }
        $user->delete();
        return response()->json([
            "message" => 200
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrfail($id);
        $user->status = $request->status;
        $user->update();
        if ($request->status === 2) {
            Mail::to($user->email)->send(new UpdateStatusMail($user));
        }

        return $user;

    }
}
