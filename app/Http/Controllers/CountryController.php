<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $countries = Country::orderBy('id', 'DESC')
        
        ->get();

        return response()->json([
            'code' => 200,
            'status' => 'Listar todos los paises',
            'countries' => $countries,
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
        $pais_is_valid = Country::where("user_id", $request->user_id)->first();
        $request->request->add(["ciudades"=>json_encode($request->ciudades)]);

        $pais = Country::create($request->all());

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
        $pais = Country::findOrFail($id);

        return response()->json([
            "pais" => $pais
            
        ]);
    }
    public function showCode($code)
    {
        $pais = Country::where("code",  $code)->first();

        if ($pais) {

           

            return response()->json([
                "pais" => $pais
            ]);
        } else {
            return response()->json([
                "message" => "Pais not found",
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pais = Country::findOrFail($id);
        
        $pais->delete();
        return response()->json([
            "message"=>200
        ]);
    }

    
    public function search(Request $request){
        return Country::search($request->buscar);
    }
}
