<?php

namespace App\Http\Controllers\Admin\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Mail\NewUserRegisterMail;
use App\Models\User;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class StaffsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
{
    // if(!auth('api')->user()->can('list_staff')){
    //     return response()->json(["message"=>"El usuario no esta autenticado"],403);
    // }

    $search = $request->search;
    
    // 🚀 SANEADO ENTERPRISE: Exclusión estricta de Pacientes (GUEST) y Médicos (DOCTOR)
    $users = User::where(DB::raw("CONCAT_WS(' ', users.name, users.surname, users.email)"), "like", "%".$search."%")
                ->whereHas("roles", function($q){
                    // 🛡️ Lista negra de roles que NO deben aparecer en el listado de personal administrativo
                    $q->whereNotIn("name", [
                        "DOCTOR", 
                        "GUEST", 
                        "PACIENTE",
                        "guest",
                        "paciente"
                    ]);
                })
                ->orderBy("id", "desc")
                ->get();
                
    return response()->json([
        "users" => UserCollection::make($users),
    ]);         
}

    public function config()
    {
        // $roles = Role::where("name","not like","%DOCTOR%")->get();
        $roles = Role::get();

        return response()->json([
            "roles" => $roles,
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
    // $this->authorize('index', User::class); 
    $user_is_valid = User::where("email", $request->email)->first();

    if($user_is_valid){
        return response()->json([
            "message" => 403,
            "message_text" => 'el usuario con este email ya existe'
        ]);
    }

    // 🚀 PROCESAMIENTO MULTIMEDIA: Cloudinary (Compatible con v3)
    if ($request->hasFile('imagen')) {
        $cloudinaryResponse = Cloudinary::uploadApi()->upload(
            $request->file('imagen')->getRealPath(),
            ['folder' => 'klyntic/staffs']
        );
        $path = $cloudinaryResponse['secure_url'];
        $request->request->add(["avatar" => $path]);
    }

    if($request->password){
         $request->request->add(["password" => Hash::make($request->password)]);
    }

    // 🛠️ PARSEO ANTIGUO DE FECHAS: Limpieza de strings de zona horaria de navegadores
    if($request->birth_date) {
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d H:i:s')]);
    }

    // 🏢 VÍNCULO ENTERPRISE: Relacionamos el usuario a la clínica actual
    // Capturamos el identificador enviado por Angular (ya sea por Header o parámetro de formulario)
    $clinicaId = $request->header('X-Clinica-Id') ?? $request->input('clinica_id');

    if (!$clinicaId) {
        return response()->json([
            "message" => 400,
            "message_text" => 'Error de contexto: No se especificó el identificador de la clínica'
        ], 400);
    }

    // Inyectamos el ID de la clínica de forma segura antes del Mass Assignment
    $request->request->add(["clinica_id" => $clinicaId]);

    // Creación masiva segura incluyendo el contexto de la clínica
    $user = User::create($request->all());

    // Asignación de Roles mediante Spatie
    $role = Role::findOrFail($request->role_id);
    $user->assignRole($role);

    return response()->json([
        "message" => 200,
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
        $user = User::findOrFail($id);

        return response()->json([
            "user" => UserResource::make($user),
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
    $user_is_valid = User::where("id", "<>", $id)->where("email", $request->email)->first();

    if($user_is_valid){
        return response()->json([
            "message"=>403,
            "message_text"=> 'el usuario con este email ya existe'
        ]);
    }
    
    $user = User::findOrFail($id);
    
    // Upload a Cloudinary
    if ($request->hasFile('imagen')) {
        if ($user->avatar) {
            $publicId = 'klyntic/staffs/' . pathinfo($user->avatar, PATHINFO_FILENAME);
            Cloudinary::uploadApi()->destroy($publicId);
        }

        $uploadedFile = $request->file('imagen')->storeOnCloudinary('klyntic/staffs');
        $path = $uploadedFile->getSecurePath();
        $request->request->add(["avatar" => $path]);
    }
    
    if($request->password){
         $request->request->add(["password"=>Hash::make($request->password)]);
    }

    if($request->birth_date){
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '',$request->birth_date );
        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);
    }

    // =========================================================================
    // 🛡️ SANEAMIENTO Y BLINDAJE DE ROLES (Evita el error 'undefined' en Postgres)
    // =========================================================================
    if($request->role_id && $request->role_id !== 'undefined') {
        
        // Obtenemos el rol actual de forma segura sin romper si es nulo
        $currentRole = $user->roles()->first();
        
        // Si no tiene rol previo, o si el rol enviado es diferente al actual
        if (!$currentRole || $request->role_id != $currentRole->id) {
            
            // Si tenía un rol viejo, lo removemos con seguridad
            if ($currentRole) {
                $user->removeRole($currentRole);
            }
            
            // Buscamos y asignamos el nuevo rol unificado
            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);
        }
    }
    // =========================================================================
    
    $user->update($request->all());
    
    return response()->json([
        "message"=>200,
        "user"=>UserResource::make($user)
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
        $user = User::findOrFail($id);
        if($user->avatar){
            Storage::delete($user->avatar);
        }
        $user->delete();
        return response()->json([
            "message"=>200
        ]);
    }
}
