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
    public function index(Request $request)
{
    // 🟢 FLEXIBILIDAD EN LA BÚSQUEDA:
    // 1. Intentamos obtener el clinica_id enviado explícitamente desde el Frontend (ej. ?clinica_id=5)
    // 2. Si no viene, intentamos ver si hay un usuario logueado que pertenezca a una clínica
    // 3. Si ninguna de las anteriores se cumple, asumimos que es un contexto independiente (null)
    $clinica_id = $request->get('clinica_id') ?? (auth()->user()->clinica_id ?? null);

    $settings = Settingeneral::orderBy('created_at', 'DESC')
        ->where('clinica_id', $clinica_id) 
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
    /**
     * Almacenar configuraciones inyectando el clinica_id del usuario
     */
    public function settingStore(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        if($request->hasFile('imagen')){
            $path = Storage::putFile("settings", $request->file('imagen'));
            $data["avatar"] = $path;
        }

        // 🟢 Forzamos que la configuración pertenezca a la clínica del usuario (o null si es independiente)
        $data['clinica_id'] = $user->clinica_id;

        $setting = Settingeneral::create($data);
        
        return response()->json([
            "message" => 200,
            "setting" => SettingGResource::make($setting),
        ]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Mostrar configuración validando pertenencia
     */
    public function settingShow($clinica_id)
{
    // 🟢 SANEADO: Si el frontend envía el string "null" o viene vacío, lo convertimos a un null real
    $id = ($clinica_id === 'null' || $clinica_id === '') ? null : $clinica_id;

    // Buscamos la configuración directamente por el ID de la clínica
    $setting = Settingeneral::where('clinica_id', $id)
        ->orderBy('created_at', 'DESC')
        ->first(); // Usamos first() porque cada clínica debería tener un solo registro activo de configuración

    // Si no existe una configuración para esa clínica, retornamos un 404 controlado en vez de romper la app
    if (!$setting) {
        return response()->json([
            'code' => 404,
            'status' => 'Not Found',
            'message' => 'No se encontró ninguna configuración para la clínica especificada.'
        ], 404);
    }

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
    /**
     * Actualizar configuraciones validando pertenencia
     */
    public function settingUpdate(Request $request, $id)
    {
        $user = auth()->user();
        
        // 🟢 Aseguramos que solo pueda encontrar y actualizar el setting de su propia clínica/contexto
        $setting = Settingeneral::where('clinica_id', $user->clinica_id)
            ->findOrFail($id);

        $data = $request->all();

        if($request->hasFile('imagen')){
            if($setting->avatar){
                Storage::delete($setting->avatar);
            }
            $path = Storage::putFile("settings", $request->file('imagen'));
            $data["avatar"] = $path;
        }
        
        // Mantenemos la integridad del clinica_id original
        $data['clinica_id'] = $user->clinica_id;
       
        $setting->update($data);
        
        return response()->json([
            "message" => 200,
            "setting" => SettingGResource::make($setting),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Eliminar configuración validando pertenencia
     */
    public function settingDestroy($id)
    {
        $user = auth()->user();

        $setting = Settingeneral::where('clinica_id', $user->clinica_id)
            ->findOrFail($id);
            
        $setting->delete();
        
        return response()->json([
            "message" => 200,
        ]);
    }
}
