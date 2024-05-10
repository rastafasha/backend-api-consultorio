<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
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
