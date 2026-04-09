<?php

namespace App\Http\Controllers;

use App\Models\Tasabcv;
use Illuminate\Http\Request;

class TasabcbController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tasas = Tasabcv::orderBy('id', 'DESC')
        ->get();

        return response()->json([
            'code' => 200,
            'status' => 'Listar todos los paises',
            'tasas' => $tasas,
        ], 200);
    }

    public function ultimatasa()
    {
        $tasabcv = Tasabcv::orderBy('created_at', 'desc')->first();
        return response()->json([
            "tasabcv" => $tasabcv
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

        $tasabcv = Tasabcv::create($request->all());
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
        $tasabcv = Tasabcv::findOrFail($id);

        return response()->json([
            "tasabcv" => $tasabcv
            
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
        
        $tasabcv = Tasabcv::findOrFail($id);
        $tasabcv->update($request->all());
        return response()->json([
            "message"=>200,
            "tasabcv"=>$tasabcv,
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
        $tasabcv = Tasabcv::findOrFail($id);
        
        $tasabcv->delete();
        return response()->json([
            "message"=>200
        ]);
    }
}
