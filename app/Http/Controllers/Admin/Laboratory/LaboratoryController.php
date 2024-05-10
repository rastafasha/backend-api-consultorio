<?php

namespace App\Http\Controllers\Admin\Laboratory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Laboratory\Laboratory;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\Storage;
use App\Models\Appointment\AppointmentAttention;
use App\Http\Resources\Laboratory\LaboratoryResource;
use App\Http\Resources\Laboratory\LaboratoryCollection;
use App\Http\Resources\Appointment\AppointmentCollection;

class LaboratoryController extends Controller
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
        //
        $appointments = Appointment::filterAdvance($speciality_id, $name_doctor, $date)
                            ->orderBy("id", "desc")
                            ->where("status", 2)
                            ->where("laboratory", 2)
                            ->paginate(10);
        return response()->json([
            "total"=>$appointments->total(),
            "appointments"=> AppointmentCollection::make($appointments)
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
        // $appointment = Appointment::findOrFail($request->appointment_id);

        $user_is_valid = Laboratory::where("appointment_id", "<>", $request->appointment_id)->first();

        // if($user_is_valid){
        //     return response()->json([
        //         "message"=>403,
        //         "message_text"=> 'el Appointment ya existe'
        //     ]);
        // }

        foreach($request->file("files") as $key=>$file){
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $name_file = $file->getClientOriginalName();
            $data = null;
            if(in_array(strtolower($extension), ["jpeg", "bmp","jpg","png" ])){
                $data = getImageSize($file);
                
            }
            $path = Storage::putFile("laboratories", $file);

            $laboratory = Laboratory::create([
                'appointment_id' =>$request->appointment_id,
                'name_file' =>$name_file,
                'size' =>$size,
                'resolution' =>$data ? $data[0]."x".$data[1]: NULL,
                'file' =>$path,
                'type'  =>$extension,
            ]);
        }

        // error_log($clase);
        error_log($laboratory);

        return response()->json([ 'laboratory'=> LaboratoryResource::make($laboratory)]);
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
        $laboratory = Laboratory::findOrFail($request->appointment_id);
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
                "laboratory" => $laboratory,
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

    public function showByAppointment($appointment_id)
    {
        $laboratories = Laboratory::where("appointment_id", $appointment_id)->get();
    
        return response()->json([
            "laboratories" => LaboratoryCollection::make($laboratories),
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
        $laboratory = Laboratory::findOrFail($id);
        $laboratory->update($request ->all());

        return response()->json([
            'laboratory'=> LaboratoryResource::make($laboratory)
        ]);
    }
    public function addFiles(Request $request)
    {
        $laboratory = Laboratory::findOrFail($request->appointment_id);
        foreach($request->file("files") as $key=>$file){
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $name_file = $file->getClientOriginalName();
            $data = null;
            if(in_array(strtolower($extension), ["jpeg", "bmp","jpg","png"])){
                $data = getImageSize($file);
                
            }
            $path = Storage::putFile("laboratories", $file);

            $laboratory = Laboratory::create([
                'appointment_id' =>$request-> appointment_id,
                'name_file' =>$name_file,
                'size' =>$size,
                'resolution' =>$data ? $data[0]."x".$data[1]: NULL,
                'file' =>$path,
                'type'  =>$extension,
            ]);
        }

        return response()->json([ 'laboratory'=> LaboratoryResource::make($laboratory)]);

    }

    public function removeFiles($id)
    {
        $laboratory = Laboratory::findOrFail($id);
        $laboratory->delete();

        return response()->json([ "message"=> 200]);

    }

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $laboratory = Laboratory::findOrFail($id);
        $laboratory->delete();

        return response()->json([
            "message"=> 200
        ]);
    }
}
