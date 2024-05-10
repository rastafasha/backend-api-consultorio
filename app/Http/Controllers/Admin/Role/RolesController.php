<?php

namespace App\Http\Controllers\Admin\Role;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //filtro por nombre de rol
        $name = $request->search;

        $roles = Role::where("name","like", "%".$name."%")->orderBy("id","desc")->get();

        return response()->json([
            "roles"=>$roles->map(function($rol){
                return [
                    "id"=>$rol->id,
                    "name"=>$rol->name,
                    "permision"=>$rol->permissions,
                    "permision_pluck"=>$rol->permissions->pluck("name"),
                    "created_at"=>$rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $is_role = Role::where("name", $request->name)->first();
        if($is_role){
            return response()->json([
                "message"=> 403,
                "message_text"=>'El nombre de rol ya existe'
            ]);
        }
        $role = Role::create([
            'guard_name' => 'api',
            'name' => $request->name,
        ]);
        Log::info(json_encode($role));
        //['register_rol', 'usuario']
        foreach($request->permissions  as $key => $permission){
            $role->givePermissionTo($permission);
        }
        return response()->json([
            "message"=> 200,
            "role"=>$role
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return response()->json([
            "id"=>$role->id,
                    "name"=>$role->name,
                    "permision"=>$role->permissions,
                    "permision_pluck"=>$role->permissions->pluck("name"),
                    "created_at"=>$role->created_at->format("Y-m-d h:i:s")
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $is_role = Role::where("id", "<>", $id)->where("name", $request->name)->first();
        if($is_role){
            return response()->json([
                "message"=> 403,
                "message_text"=>'El nombre de rol ya existe'
            ]);
        }

        $role = Role::findOrFail($id);
        $role->update($request->all());
        
        $role->syncPermissions($request->permissions);

        return response()->json([
            "message"=> 200,
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
        $role = Role::findOrFail($id);
        if($role->users->count() > 0){
            return response()->json([
                "message"=>403,
                "message_text"=> 'El Rol seleccionado no se puede eliminar por motivos que ya tiene usuarios relacionados'
            ]);
        }
        $role->delete();

        return response()->json([
            "message"=> 200,
        ]);
    }
}
