<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Http\Controllers\Controller;
use App\Models\Patient\PatientPerson;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Appointment\AppointmentAttention;

class AppointmentAttentionController extends Controller
{
   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $appointment = Appointment::findOrFail($request->appointment_id);
        $appointment_attention = $appointment->attention;

        $request->request->add(["receta_medica"=>json_encode($request->medical)]);

        if($appointment_attention){
            $appointment_attention->update($request->all());

            if(!$appointment->date_attention){
                $appointment->update(["status"=>2,"date_attention" =>now()]);
                
            }
            $appointment->update(["laboratory" =>$request->laboratory,]);
            
            
        }else{
            AppointmentAttention::create($request->all());

            date_default_timezone_set('America/Caracas');
            $appointment->update(["status"=>2,"date_attention" =>now()]);
            
        }
        
        return response()->json([
            "message"=>200,
        ]);
    }

    public function storeLocal(Request $request)
    {

        $request->request->add(["receta_medica"=>json_encode($request->medical)]);

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


        $appointment = Appointment::create([
            "doctor_id" =>$request->doctor_id,
            "patient_id" =>$request->patient_id,
            "date_appointment" => Carbon::parse($request->date_appointment)->format("Y-m-d h:i:s"),
            "date_attention" => Carbon::parse($request->date_appointment)->format("Y-m-d h:i:s"),
            "speciality_id" => $request->speciality_id,
            "doctor_schedule_join_hour_id" => $request->doctor_schedule_join_hour_id,
            // "user_id" => auth("api")->user()->id, aqui lo comente porque no reconoce el id.. 
            // asi que lo envio desde el front y aqui lo recibo
            "user_id" => $request->user_id,
            "amount" =>$request->amount,
            "status_pay" =>$request->amount != $request->amount_add ? 2 : 1,
        ]);

        AppointmentAttention::create($request->all());

            date_default_timezone_set('America/Caracas');
            $appointment->update(["status"=>2,"date_attention" =>now()]);

        AppointmentPay::create([
            "appointment_id"=>$appointment->id,
            "amount"=> $request->amount_add,
            "method_payment"=>$request->method_payment,
        ]);

            date_default_timezone_set('America/Caracas');
            
            
        
        return response()->json([
            "message"=>200,
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
        $appointment_attention = $appointment->attention;
        if($appointment_attention){
            return response()->json([
                "appointment_attention"=>[
                    "id"=>$appointment_attention->id,
                    "description"=>$appointment_attention->description,
                    "laboratory"=>$appointment_attention->laboratory,
                    "receta_medica"=>$appointment_attention->receta_medica ? json_decode($appointment_attention->receta_medica) : [],
                    "created_at" => $appointment_attention->created_at->format("Y-m-d h:i A"),
                ]
            ]);
        }else{
            return response()->json([
                "appointment_attention"=>[
                    "id"=>NULL,
                    "description"=>NULL,
                    "laboratory"=>1,
                    "receta_medica"=> [],
                    "created_at" => NULL,
                ]
            ]);
        }
        
    }

    
}
