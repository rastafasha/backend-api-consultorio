<?php

namespace App\Http\Controllers\Patient;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\NewPatientRegisterMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Patient\PatientPerson;
use Illuminate\Support\Facades\Redis;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\Patient\PatientCollection;
use App\Http\Resources\Appointment\AppointmentCollection;

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
            DB::raw("CONCAT(patients.name,' ', IFNULL(patients.surname,''),' ',patients.email)"),
            "like",
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

        $search = $request->search;

        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        // $patients = Patient::Where('doctor_id', $doctor_id)

        $patients = Patient::where(
            DB::raw("CONCAT(patients.name,' ', IFNULL(patients.surname,''),' ',patients.email)"),
            "like",
            "%" . $search . "%"
        )
        ->Where('doctor_id', $doctor_id)
        ->orderBy("id", "desc")
        ->paginate(10);

        return response()->json([
            // "patients"=> $patients,
            "total" => $patients->total(),
            "patients" => PatientCollection::make($patients)
            // "pa_assessments"=>$patient->pa_assessments ? json_decode($patient->pa_assessments) : [],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile($id)
    {
        //uso de redis
        // $cachedRecord = Redis::get('profile_patient_#'.$id);
        // $data_patient = [];
        // if(isset($cachedRecord)) {
        //     $data_patient = json_decode($cachedRecord, FALSE);
        // }else{

        //     $patient = Patient::findOrFail($id);

        //     $num_appointment = Appointment::where("patient_id",$id)->count();
        //     $money_of_appointments = Appointment::where("patient_id",$id)->sum("amount");
        //     $num_appointment_pendings = Appointment::where("patient_id",$id)->where("status",1)->count();
        //     $appointment_pendings = Appointment::where("patient_id",$id)->where("status",1)->get();
        //     $appointments = Appointment::where("patient_id",$id)->get();

        //     $data_patient = [
        //         "num_appointment"=>$num_appointment,
        //         "money_of_appointments"=> $money_of_appointments,
        //         "num_appointment_pendings"=>$num_appointment_pendings,
        //         "patient" => PatientResource::make($patient),
        //         "appointment_pendings"=> AppointmentCollection::make($appointment_pendings),
        //         "appointments"=>$appointments->map(function($appointment){
        //             return [
        //                 "id"=> $appointment->id,
        //                 "patient"=> [
        //                     "id"=> $appointment->patient->id,
        //                     "full_name"=> $appointment->patient->name.' '.$appointment->patient->surname,
        //                     "avatar"=> $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : null,
        //                 ],
        //                 "doctor"=> [
        //                     "id"=> $appointment->doctor->id,
        //                     "full_name"=> $appointment->doctor->name.' '.$appointment->doctor->surname,
        //                     "avatar"=> $appointment->doctor->avatar ? env("APP_URL")."storage/".$appointment->doctor->avatar : null,
        //                 ],
        //                 "date_appointment" =>$appointment->date_appointment,
        //                 "date_appointment_format" =>Carbon::parse($appointment->date_appointment)->format("d M Y"),
        //                 "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
        //                 "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
        //                 "appointment_attention"=> $appointment->attention ?[
        //                     "id"=>$appointment->attention->id,
        //                     "description"=>$appointment->attention->description,
        //                     "receta_medica"=>$appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
        //                     "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
        //                 ]: NULL,
        //                 "amount" =>$appointment->amount,
        //                 "status_pay" =>$appointment->status_pay,
        //                 "status" =>$appointment->status,
        //             ];
        //         }),
        //     ];

        //     Redis::set('profile_patient_#'.$id, json_encode($data_patient),'EX', 3600);
        // }
        //uso de redis

        //sin redis
        $data_patient = [];
        $patient = Patient::findOrFail($id);

            $num_appointment = Appointment::where("patient_id", $id)->count();
            $money_of_appointments = Appointment::where("patient_id", $id)->sum("amount");
            $num_appointment_pendings = Appointment::where("patient_id", $id)->where("status", 1)->count();
            $num_appointment_checkeds = Appointment::where("patient_id", $id)->where("status", 2)->count();
            $appointment_checkeds = Appointment::where("patient_id", $id)->where("status", 2)->orderby("id", "desc")->get();
            $appointment_pendings = Appointment::where("patient_id", $id)->where("status", 1)->orderby("id", "desc")->get();
            $appointments = Appointment::where("patient_id", $id)->orderBy("date_appointment", "desc")->get();

            $data_patient = [
                "num_appointment" => $num_appointment,
                "num_appointment_checkeds" => $num_appointment_checkeds,
                "appointment_checkeds" => AppointmentCollection::make($appointment_checkeds),
                "money_of_appointments" => $money_of_appointments,
                "num_appointment_pendings" => $num_appointment_pendings,
                "patient" => PatientResource::make($patient),
                "appointment_pendings" => AppointmentCollection::make($appointment_pendings),
                "appointments" => $appointments->map(function ($appointment) {
                    return [
                        "id" => $appointment->id,
                        "patient" => [
                            "id" => $appointment->patient->id,
                            "full_name" => $appointment->patient->name . ' ' . $appointment->patient->surname,
                            // "avatar"=> $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : null,
                            "avatar" => $appointment->patient->avatar ? env("APP_URL") . $appointment->patient->avatar : null,
                        ],
                        "doctor" => [
                            "id" => $appointment->doctor->id,
                            "full_name" => $appointment->doctor->name . ' ' . $appointment->doctor->surname,
                            // "avatar"=> $appointment->doctor->avatar ? env("APP_URL")."storage/".$appointment->doctor->avatar : null,
                            "avatar" => $appointment->doctor->avatar ? env("APP_URL") . $appointment->doctor->avatar : null,
                            "speciality_id" => $appointment->doctor->speciality_id,
                            "speciality" => $appointment->doctor->speciality ? [
                                "id" => $appointment->doctor->speciality->id,
                                "name" => $appointment->doctor->speciality->name,
                                "price" => $appointment->doctor->speciality->price,
                            ] : null,
                        ],
                        "date_appointment" => $appointment->date_appointment,
                        "date_appointment_format" => Carbon::parse($appointment->date_appointment)->format("d M Y"),
                        "format_hour_start" => Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
                        "format_hour_end" => Carbon::parse(date("Y-m-d") . ' ' . $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "appointment_attention" => $appointment->attention ? [
                            "id" => $appointment->attention->id,
                            "description" => $appointment->attention->description,
                            "receta_medica" => $appointment->attention->receta_medica ? json_decode($appointment->attention->receta_medica) : [],
                            "created_at" => $appointment->attention->created_at->format("Y-m-d h:i A"),
                        ] : null,
                        "amount" => $appointment->amount,
                        "status_pay" => $appointment->status_pay,
                        "status" => $appointment->status,
                        "speciality_id" => $appointment->speciality_id,
                            "speciality" => $appointment->speciality ? [
                                "id" => $appointment->speciality->id,
                                "name" => $appointment->speciality->name,
                                "price" => $appointment->speciality->price,
                            ] : null,
                    ];
                }),
            ];
        //sin redis

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
        $patient_is_valid = Patient::where("n_doc", $request->n_doc)->first();

        if ($patient_is_valid) {
            return response()->json([
                "message" => 403,
                "message_text" => 'el paciente ya existe'
            ]);
        }

        if ($request->hasFile('imagen')) {
            $path = Storage::putFile("patients", $request->file('imagen'));
            $request->request->add(["avatar" => $path]);
        }

        if ($request->birth_date) {
            $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
            $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);
        }

        $patient = Patient::create($request->all());

        $request->request->add([
            "patient_id" => $patient->id
        ]);
        PatientPerson::create($request->all());

        Mail::to($patient->email)->send(new NewPatientRegisterMail($patient));

        return response()->json([
            "message" => 200,
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
        $patient_is_valid = Patient::where("id", "<>", $id)->where("n_doc", $request->n_doc)->first();

        if ($patient_is_valid) {
            return response()->json([
                "message" => 403,
                "message_text" => 'el paciente ya existe'
            ]);
        }

        $patient = Patient::findOrFail($id);
        if ($request->hasFile('imagen')) {
            if ($patient->avatar) {
                Storage::delete($patient->avatar);
            }
            $path = Storage::putFile("patients", $request->file('imagen'));
            $request->request->add(["avatar" => $path]);
        }

        if ($request->birth_date) {
            $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
            $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);
        }
        //uso de redis
        // $cachedRecord = Redis::get('profile_patient_#'.$id);
        // if(isset($cachedRecord)) {
        //     Redis::del('profile_patient_#'.$id);
        // }
        $patient->update($request->all());

        if ($patient->person) {
            $patient->person->update($request->all());
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

            // "patients" => $patients,
            "patients" => PatientCollection::make($patients),
            "doctors" => $doctors,



        ]);
    }
}
