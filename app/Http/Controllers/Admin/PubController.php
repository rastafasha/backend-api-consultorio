<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pub\PubCollection;
use App\Http\Resources\Pub\PubResource;
use App\Models\Pub;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        // if($request->hasFile('imagen')){
        //     $path = Storage::putFile("pubs", $request->file('imagen'));
        //     $request->request->add(["avatar"=>$path]);
        // }

        // 3. Procesamos el Avatar con Cloudinary (Compatible con v3)
        if ($request->hasFile('imagen')) {
            // Sube la imagen utilizando el uploadApi nativo del SDK
            $cloudinaryResponse = Cloudinary::uploadApi()->upload(
                $request->file('imagen')->getRealPath(),
                ['folder' => 'klyntic/pubs']
            );

            // Obtenemos la URL de manera directa desde el arreglo de respuesta
            $path = $cloudinaryResponse['secure_url'];

            $request->request->add(["avatar" => $path]);
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
        // if($request->hasFile('imagen')){
        //     if($pub->avatar){
        //         Storage::delete($pub->avatar);
        //     }
        //     $path = Storage::putFile("pubs", $request->file('imagen'));
        //     $request->request->add(["avatar"=>$path]);
        // }

        //upload a cloudinary
        if ($request->hasFile('imagen')) {
            // 1. Si el usuario ya tiene un avatar en Cloudinary, lo borramos de la nube
            if ($pub->avatar) {
                // Extraemos el public_id de la URL completa (ej: staffs/nombre_archivo)
                $publicId = 'klyntic/pubs/' . pathinfo($pub->avatar, PATHINFO_FILENAME);

                // Eliminamos la imagen vieja de Cloudinary
                Cloudinary::uploadApi()->destroy($publicId);
            }

            // 2. Subimos la nueva imagen utilizando el método compatible con tu versión
            $uploadedFile = $request->file('imagen')->storeOnCloudinary('klyntic/staffs');
            $path = $uploadedFile->getSecurePath();

            $request->request->add(["avatar" => $path]);
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
