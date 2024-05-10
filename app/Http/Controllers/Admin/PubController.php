<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Pub\PubResource;
use App\Http\Resources\Pub\PubCollection;

class PubController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pubs = Pub::orderBy('created_at', 'DESC')
        ->get();


        return response()->json([
            'code' => 200,
            'status' => 'Listar pubs',
            "pubs" => PubCollection::make($pubs),
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
            $path = Storage::putFile("pubs", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }

        $pub = Pub::create($request->all());

        return response()->json([
            "message" => 200,
            "pub"=>$pub
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
        $pub = Pub::findOrFail($id);

        return response()->json([
            "pub" => PubResource::make($pub),
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
        $pub = Pub::findOrFail($id);
        if($request->hasFile('imagen')){
            if($pub->avatar){
                Storage::delete($pub->avatar);
            }
            $path = Storage::putFile("pubs", $request->file('imagen'));
            $request->request->add(["avatar"=>$path]);
        }
        $pub->update($request->all());

        return response()->json([
            "message" => 200,
            "pub" => $pub,
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
        $pub = Pub::findOrFail($id);
        $pub->delete();
        return response()->json([
            "message" => 200
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        
        $pub = Pub::findOrfail($id);
        $pub->state = $request->state;
        $pub->update();

        return response()->json([
            "message" => 200,
            "pub" => $pub,
            
        ]);
    }

    public function activos()
    {

        $pubs = Pub::orderBy('created_at', 'DESC')
                
                ->where('state', $state=2)
                ->get();
            return response()->json([
                'code' => 200,
                'state' => 'Listar pubs activas',
                "pubs" => PubCollection::make($pubs),
            ], 200);
    }
}
