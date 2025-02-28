<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Mail\RegisterAppointment;
use App\Models\Doctor\Specialitie;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationAppointment;
use App\Models\Patient\PatientPerson;
use App\Models\Appointment\Appointment;
use App\Mail\NewAppointmentRegisterMail;
use App\Models\Doctor\DoctorScheduleDay;
use App\Mail\CancellationAppointmentMail;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Appointment\AppointmentCollection;

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
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
        ]);

    }

    public function appointmentByDoctor(Request $request, $doctor_id)
    {

        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;
        $search = $request->search;
        $date = $request->date;
        
        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        // $patients = Patient::Where('doctor_id', $doctor_id)

        $appointments = Appointment::filterAdvanceDoc(
            $search_doctor,
            $search_patient, 
            $date,
            $search 
            )
        ->Where('doctor_id', $doctor_id)
        ->orderBy("id", "desc")
        ->paginate(10);

        return response()->json([
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
        ]);

        

    }

    public function filter(Request $request)
    {
        $date_appointment = $request->date_appointment;
        $hour = $request->hour;
        $speciality_id = $request->speciality_id;
        
        date_default_timezone_set('America/Caracas');
        Carbon::setLocale('es');
        DB::statement("SET lc_time_names = 'es_ES'");

        $name_day = Carbon::parse($date_appointment)->dayName;
        //consulta para saber que doctor cumple con la disponibilidad de atencion tendiendo en cuenta
        //el dia, hora y especialidad
        $doctor_query = DoctorScheduleDay::where("day","like","%".$name_day."%")
                        ->whereHas("doctor", function($q) use($speciality_id){
                            $q->where("speciality_id", $speciality_id);
                        })
                        ->whereHas("schedule_hours", function($q)use($hour){
                            $q->whereHas("doctor_schedule_hour",function($qs)use($hour){
                                $qs->where("hour", $hour);
                            });
                        })->get();
        $doctors = collect([]);   
        //iteramos entre los doctores que resultaron de la consulta
        foreach ($doctor_query as $key => $doctor_q) {
            //revisamos su disponibilidad para arrojar los segmentos de la hora, en intervalos de 15 min
            $segments = DoctorScheduleJoinHour::where("doctor_schedule_day_id",$doctor_q->id)
                                                ->whereHas("doctor_schedule_hour",function($q)use($hour){
                                                    $q->where("hour", $hour);
                                                })->get();
             //armamos una lista de doctores con los segmentos de su hora(marcamos cuales se encuentran ocupados)                                   
            $doctors->push([
                //datos del doctor
                "doctor"=>[
                    "id"=> $doctor_q->doctor->id,
                    "full_name"=> $doctor_q->doctor->name.' '.$doctor_q->doctor->surname,
                    "speciality"=>[
                        "id"=> $doctor_q->doctor->speciality->id,
                        "name"=>$doctor_q->doctor->speciality->name,
                    ],
                ],
                //datos del segmento en un formato para el frontend
                "segments" => $segments->map(function($segment)use($date_appointment){
                    //aca podemos averiguar si el segmento ya se encuentra ocupado por otra cita medica
                    $appointment = Appointment::where("doctor_schedule_join_hour_id", $segment->id)
                                                ->whereDate("date_appointment", Carbon::parse($date_appointment)->format("Y-m-d"))
                                                ->first();
                        return[
                            "id" => $segment->id,
                            "doctor_schedule_day_id" => $segment->doctor_schedule_day_id,
                            "doctor_schedule_hour_id" => $segment->doctor_schedule_hour_id,
                            "is_appointment"=> $appointment ? true : false,
                            "format_segment"=>[
                                "id" => $segment->doctor_schedule_hour->id,
                                "hour_start" => $segment->doctor_schedule_hour->hour_start,
                                "hour_end" => $segment->doctor_schedule_hour->hour_end,
                                "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_start)->format("h:i A") ,
                                "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_end)->format("h:i A"),
                                "hour" => $segment->doctor_schedule_hour->hour,
                            ],
                        ];
                    })
                ]);
        }             
        // dd($doctors);

        return response()->json([
            "doctors"=>$doctors
        ]);
    }

    public function filterByDoctor(Request $request, $doctor_id)
    {
        $date_appointment = $request->date_appointment;
        $hour = $request->hour;
        $speciality_id = $request->speciality_id;
        
        date_default_timezone_set('America/Caracas');
        Carbon::setLocale('es');
        DB::statement("SET lc_time_names = 'es_ES'");

        $name_day = Carbon::parse($date_appointment)->dayName;
        $doctor_query = DoctorScheduleDay::where("day","like","%".$name_day."%")
                        ->whereHas("doctor", function($q) use($doctor_id, $speciality_id){
                            $q->where("id", $doctor_id)
                              ->where("speciality_id", $speciality_id);
                        })
                        ->whereHas("schedule_hours", function($q)use($hour){
                            $q->whereHas("doctor_schedule_hour",function($qs)use($hour){
                                $qs->where("hour", $hour);
                            });
                        })->get();
        
        // Get the specific doctor's details
        $doctor = User::find($doctor_id);
        
        // Iterate through the doctor's schedule
        foreach ($doctor_query as $key => $doctor_q) {
            // Get available time segments
            $segments = DoctorScheduleJoinHour::where("doctor_schedule_day_id",$doctor_q->id)
                                                ->whereHas("doctor_schedule_hour",function($q)use($hour){
                                                    $q->where("hour", $hour);
                                                })->get();
             // Build doctor's schedule with available segments
            $doctor = User::find($doctor_id);
            if (!$doctor) {
                return response()->json([
                    "message" => "Doctor not found",
                    "doctors" => []
                ], 404);
            }

            $doctorDetails = [
                // Doctor details
                "doctor"=>[
                    "id"=> $doctor->id,
                    "full_name"=> $doctor->name.' '.$doctor->surname,
                    "speciality"=>[
                        "id"=> $doctor->speciality->id,
                        "name"=>$doctor->speciality->name,
                    ],
                ],
                //datos del segmento en un formato para el frontend
                "segments" => $segments->map(function($segment)use($date_appointment){
                    //aca podemos averiguar si el segmento ya se encuentra ocupado por otra cita medica
                    $appointment = Appointment::where("doctor_schedule_join_hour_id", $segment->id)
                                                ->whereDate("date_appointment", Carbon::parse($date_appointment)->format("Y-m-d"))
                                                ->first();
                        return[
                            "id" => $segment->id,
                            "doctor_schedule_day_id" => $segment->doctor_schedule_day_id,
                            "doctor_schedule_hour_id" => $segment->doctor_schedule_hour_id,
                            "is_appointment"=> $appointment ? true : false,
                            "format_segment"=>[
                                "id" => $segment->doctor_schedule_hour->id,
                                "hour_start" => $segment->doctor_schedule_hour->hour_start,
                                "hour_end" => $segment->doctor_schedule_hour->hour_end,
                                "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_start)->format("h:i A") ,
                                "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_end)->format("h:i A"),
                                "hour" => $segment->doctor_schedule_hour->hour,
                            ],
                        ];
                    })
                ];
        }             

        return response()->json([
            "doctor"=>$doctorDetails,
        ]);


        




    }

    public function config()
    {
        $hours =[
            [
                "id"=>"08",
                "name"=>"8:00 AM"
            ],
            [
                "id"=>"09",
                "name"=>"09:00 AM"
            ],
            [
                "id"=>"10",
                "name"=>"10:00 AM"
            ],
            [
                "id"=>"11",
                "name"=>"11:00 AM"
            ],
            [
                "id"=>"12",
                "name"=>"12:00 PM"
            ],
            [
                "id"=>"13",
                "name"=>"01:00 PM"
            ],
            [
                "id"=>"14",
                "name"=>"02:00 PM"
            ],
            [
                "id"=>"15",
                "name"=>"03:00 PM"
            ],
            [
                "id"=>"16",
                "name"=>"04:00 PM"
            ],
            [
                "id"=>"17",
                "name"=>"05:00 PM"
            ],
        ];
        $specialities = Specialitie::where("state",1)->get();

        return response()->json([
            "specialities" => $specialities,
            "hours" => $hours,
        ]);
    }

    public function query_patient(Request $request)
    {
        $n_doc =$request->get("n_doc");

        $patient = Patient::where("n_doc", $n_doc)->first();

        if(!$patient){
            return response()->json([
                "message"=>403,
            ]);
        }

        return response()->json([
            "message"=>200,
            "id"=>$patient->id,
            "name"=>$patient->name,
            "email"=>$patient->email,
            "surname"=>$patient->surname,
            "phone"=>$patient->phone,
            "n_doc"=>$patient->n_doc,
        ]);

    }

    

    public function calendar(Request $request){
        $speciality_id = $request->speciality_id;
        $search_doctor = $request->search_doctor;
        $search_patient = $request->search_patient;

        $appointments = Appointment::filterAdvancePay(
            $speciality_id, $search_doctor, $search_patient, null, null)->orderBy("id", "desc")
        ->get();

        return response()->json([
            "appointments" => $appointments->map(function($appointment){
                return[
                    "id"=> $appointment->id,
                    "title"=> "Cita Médica - ".($appointment->doctor->name.' '.$appointment->doctor->surname)." - ".$appointment->speciality->name,
                    "start"=> Carbon::parse($appointment->date_appointment)->format("Y-m-d")."T".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                    "end"=> Carbon::parse($appointment->date_appointment)->format("Y-m-d")."T".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                ];
            }),
        ]);
    }

    public function appointmensByDoctor(Request $request, $doctor_id)
    {

        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        $appointmens = Appointment::Where('doctor_id', $doctor_id)
                ->get();

        return response()->json([
            // "patients"=> $patients,
            "appointmens"=> $appointmens,
            "total"=>$appointmens->total(),
            // "pa_assessments"=>$patient->pa_assessments ? json_decode($patient->pa_assessments) : [],
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $patient = null;
        $doctor = User::where("id", $request->doctor_id)->first();
        
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        if(!$patient){
            $patient = Patient::create([
                "name"=>$request->name,
                "surname"=>$request->surname,
                "n_doc"=>$request->n_doc,
                "phone"=>$request->phone,
            ]);
            PatientPerson::create([
                'patient_id' => $patient->id,
                'name_companion' => $request->name_companion,
                'surname_companion' => $request->surname_companion,
            ]);
        }else{
            $patient->person->update([
                'name_companion' => $request->name_companion,
                'surname_companion' => $request->surname_companion,
            ]);
        }

        
        // $user = auth('api')->user();//lo coloco para saber si viene o no
        // error_log($doctor);

        $appointment = Appointment::create([
            "doctor_id" =>$request->doctor_id,
            "patient_id" =>$patient->id,
            "date_appointment" => Carbon::parse($request->date_appointment)->setTimezone('America/Caracas')->format("Y-m-d H:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            // "user_id" => auth("api")->user()->id, aqui lo comente porque no reconoce el id.. 
            // asi que lo envio desde el front y aqui lo recibo
            "user_id" => $request->user_id,
            "amount" =>$request->amount,
            "status_pay" =>$request->amount != $request->amount_add ? 2 : 1,
        ]);

        AppointmentPay::create([
            "appointment_id"=>$appointment->id,
            "amount"=> $request->amount_add,
            "method_payment"=>$request->method_payment,
        ]);
        
        
        // if($request->doctor_id){
        //     $doctor = User::findOrFail($id);

        //     return response()->json([
        //         "doctor"=>[
        //                 "id"=> $doctor->doctor_id,
        //                 "email"=> $doctor->email,
        //                 "full_name" =>$doctor->name.' '.$doctor->user->surname,
        //             ]
        //         ]);
        // }

        Mail::to($appointment->patient->email)->send(new RegisterAppointment($appointment));
        Mail::to($doctor->email)->send(new NewAppointmentRegisterMail($appointment));

        return response()->json([
            "message" => 200,
            "appointment" => $appointment,
            "amount" =>$request->amount,
            "paymentmethod" =>$request->method_payment,
            "amountadd" =>$request->amount_add,
            "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
            "patient"=>$appointment->patient_id ? 
                    [
                        "id"=> $appointment->patient->id,
                        "email" =>$appointment->patient->email,
                        "full_name" =>$appointment->patient->name.' '.$appointment->patient->surname,
                    ]: NULL,
            "speciality"=>$appointment->speciality,
            "speciality"=>$appointment->speciality ? 
                [
                    "id"=> $appointment->speciality->id,
                    "name"=> $appointment->speciality->name,
                ]: NULL,
            "doctor_id" => $appointment->doctor_id,
            "doctor"=>$appointment->doctor_id ? 
                        [
                            "id"=> $doctor->id,
                            "email"=> $doctor->email,
                            "full_name" =>$doctor->name.' '.$doctor->surname,
                        ]: NULL,
            
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
        $appointment = Appointment::findOrFail($id);
        $sum_total_pays = AppointmentPay::where("appointment_id",$id)->sum("amount");
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
       
        $appointment = Appointment::findOrFail($id);

        if($appointment->payments->sum("amount") > $request->amount){
            return response()->json([
                "message" => 403,
                "message_text"=> "Los Pagos ingresados superan al nuevo monto que quiere guardar"
            ]);
        }

        $appointment->update([
            "doctor_id" =>$request->doctor_id,
            "date_appointment" => Carbon::parse($request->date_appointment)->format("Y-m-d h:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            "amount" =>$request->amount,
            "status_pay" =>$appointment->payments->sum("amount") != $request->amount ? 2 : 1,
        ]);

        return response()->json([
            "message" => 200,
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
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
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
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
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
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
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

        if($request->confimation === '2'){
            Mail::to($appointment->patient->email)->send(new ConfirmationAppointment($appointment));

        }
        return response()->json([
            "message" => 200,
            "appointment" => $appointment,
            "amount" =>$request->amount,
            "paymentmethod" =>$request->method_payment,
            "amountadd" =>$request->amount_add,
            "date_appointment" => Carbon::parse($appointment->date_appointment)->format('d-m-Y'),
            "patient"=>$appointment->patient_id ? 
                    [
                        "id"=> $appointment->patient->id,
                        "email" =>$appointment->patient->email,
                        "full_name" =>$appointment->patient->name.' '.$appointment->patient->surname,
                    ]: NULL,
            "speciality"=>$appointment->speciality ? 
                [
                    "id"=> $appointment->speciality->id,
                    "name"=> $appointment->speciality->name,
                ]: NULL,
            "doctor_id" => $appointment->doctor_id,
            "doctor"=>$appointment->doctor_id ? 
                        [
                            "id"=> $doctor->id,
                            "email"=> $doctor->email,
                            "full_name" =>$doctor->name.' '.$doctor->surname,
                        ]: NULL,
            
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
}
