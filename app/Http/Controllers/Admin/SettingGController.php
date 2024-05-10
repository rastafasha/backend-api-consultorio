<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Settingeneral;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\SettingGeneral\SettingGResource;
use App\Http\Resources\SettingGeneral\SettingGCollection;

class SettingGController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $settings = Settingeneral::orderBy('created_at', 'DESC')
        ->get();


        return response()->json([
            'code' => 200,
            'status' => 'Listar configuraciones',
            "settings" => SettingGCollection::make($settings),
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->hasFile('imagen')){
            $path = Storage::putFile("settings", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }

        $setting = Settingeneral::create($request->all());
        
        
        return response()->json([
            "message"=>200,
        ]);
        
        // return Settingeneral::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show( Settingeneral $id)
    {
        $setting = Settingeneral::findOrFail($id);
        return response()->json([
            "setting" => SettingGResource::make($setting),
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

        $setting = Settingeneral::findOrFail($id);

        if($request->hasFile('imagen')){
            if($setting->avatar){
                Storage::delete($setting->avatar);
            }
            $path = Storage::putFile("settings", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }
        
        
       
        $setting->update($request->all());
        
        
        return response()->json([
            "message"=>200,
            "setting"=>$setting,
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
        $setting = Settingeneral::findOrFail($id);
        $setting->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
