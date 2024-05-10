<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Location\LocationResource;
use App\Http\Resources\Location\LocationCollection;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $client_id = $request->client_id;
        $name_client = $request->search;
        $email_client = $request->search;
        $doctor_id = $request->doctor_id;
        $name_doctor = $request->search;
        $email_doctor = $request->search;

        $locations = Location::filterAdvanceLocation(
            $client_id, $name_client, $email_client,
            $doctor_id, $name_doctor, $email_doctor,
            )->orderBy("id", "desc")
                            ->paginate(10);
        return response()->json([
            // "total"=>$patients->total(),
            "locations"=> LocationCollection::make($locations)
        ]);

    }

    public function config()
    {
        // $roles = Role::where("name","like","%DOCTOR%")->get();
        // $specialists = User::where("status",'active')->get();
        
        
        
        return response()->json([
            // "specialists" => $specialists,
            // "patients" => $patients,
            
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
        $user_is_valid = User::where("email", $request->email)->first();


        if($user_is_valid){
            return response()->json([
                "message"=>403,
                "message_text"=> 'el usuario con este email ya existe'
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("locations", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }

        
        
        $location = Location::create($request->all());
        
        
        return response()->json([
            "message"=>200,
            // "location" => LocationCollection::make($location),
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
        
        $specialists = User::where("location_id",$id)->get();
        $patients = Patient::where("location_id",$id)->get();

        $location = Location::findOrFail($id);



        return response()->json([
            "location" => LocationResource::make($location),
            "specialists" => $specialists,
            "patients" => $patients,
            // "assesstments"=>$patient->pa_assessments ? json_decode($patient->pa_assessments) : [],
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

        
        $user_is_valid = User::where("email", $request->email)->first();


        // if($user_is_valid){
        //     return response()->json([
        //         "message"=>403,
        //         "message_text"=> 'el usuario con este email ya existe'
        //     ]);
        // }
        
        $request->request->add(["pa_services"=>json_encode($request->services)]);
        $request->request->add(["pa_assessments"=>json_encode($request->pa_assessments)]);

        
        
        $location = Location::findOrFail($id);

        if($request->hasFile('imagen')){
            if($location->avatar){
                Storage::delete($location->avatar);
            }
            $path = Storage::putFile("locations", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }
        
        
       
        $location->update($request->all());
        
        
        return response()->json([
            "message"=>200,
            "location"=>$location,
            // "assesstments"=>$patient->pa_assessments ? json_decode($patient->pa_assessments) : [],
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
        $location = Location::findOrFail($id);
        if($location->avatar){
            Storage::delete($location->avatar);
        }
        $location->delete();
        return response()->json([
            "message"=>200
        ]);
    }
}
