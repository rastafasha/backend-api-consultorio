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
        //    }

        $search = $request->search;
        $users = User::where(DB::raw("CONCAT(users.name,' ',IFNULL(users.surname,''),' ',users.email)"),"like","%".$search."%")
                    // "name", "like", "%".$search."%"
                    // ->orWhere("surname", "like", "%".$search."%")
                    // ->orWhere("email", "like", "%".$search."%")
                    ->orderBy("id", "desc")
                    ->whereHas("roles", function($q){
                        $q->where("name","not like","%DOCTOR%");
                    })
                    ->get();
                    
        return response()->json([
            "users" => UserCollection::make($users) ,
            
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
                "message"=>403,
                "message_text"=> 'el usuario con este email ya existe'
            ]);
        }

        // if($request->hasFile('imagen')){
        //     $path = Storage::putFile("staffs", $request->file('imagen'));
        //     $request->request->add(["avatar"=>$path]);
        // }

        // 3. Procesamos el Avatar con Cloudinary (Compatible con v3)
        if ($request->hasFile('imagen')) {
            // Sube la imagen utilizando el uploadApi nativo del SDK
            $cloudinaryResponse = Cloudinary::uploadApi()->upload(
                $request->file('imagen')->getRealPath(),
                ['folder' => 'klyntic/staffs']
            );

            // Obtenemos la URL de manera directa desde el arreglo de respuesta
            $path = $cloudinaryResponse['secure_url'];

            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
             $request->request->add(["password"=>Hash::make($request->password)]);
        }

        //para el error:
        //Could not parse 'Fri Dec 08 2023 00:00:00 GMT-0400 (Venezuela Time)
        //colocamos:
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '',$request->birth_date );

        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format('Y-m-d h:i:s')]);

        $user = User::create($request->all());

        $role=  Role::findOrFail($request->role_id);
        $user->assignRole($role);

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
        
        // if($request->hasFile('imagen')){
        //     if($user->avatar){
        //         Storage::delete($user->avatar);
        //     }
        //     $path = Storage::putFile("staffs", $request->file('imagen'));
        //     $request->request->add(["avatar"=>$path]);
        // }

        //upload a cloudinary
        if ($request->hasFile('imagen')) {
            // 1. Si el usuario ya tiene un avatar en Cloudinary, lo borramos de la nube
            if ($user->avatar) {
                // Extraemos el public_id de la URL completa (ej: staffs/nombre_archivo)
                $publicId = 'klyntic/staffs/' . pathinfo($user->avatar, PATHINFO_FILENAME);

                // Eliminamos la imagen vieja de Cloudinary
                Cloudinary::uploadApi()->destroy($publicId);
            }

            // 2. Subimos la nueva imagen utilizando el método compatible con tu versión
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
    
        if($request->role_id && $request->role_id != $user->roles()->first()->id){
            // error_log($user->roles()->first()->id);
            $role_old = Role::findOrFail($user->roles()->first()->id);
            $user->removeRole($role_old);
            // error_log($request->role_id);
            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);
        }
        
        $user->update($request->all());
        
        Mail::to($user)->send(new NewUserRegisterMail($user));
        // Mail::to('mercadocreativo@gmail.com')->send(new NewUserRegisterMail($user));

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
