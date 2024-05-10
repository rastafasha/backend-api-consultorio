<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminUserSoftDeletesController extends Controller
{
    // /**
    //  * Create a new AuthController instance.
    //  *
    //  * @return void
    //  */
    // public function __construct()
    // {
    //     $this->middleware('jwt.verify');
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('indexDeletes', User::class);

        $users = User::select([
            "id", "username", "email", "is_active",
            "created_at", "updated_at", "deleted_at",
        ])
        ->withCount([
            "payments",
            "directories"
        ])
        ->orderBy('id', 'desc')
        ->onlyTrashed()
        ->get();

        return response()->json([
            'code' => 200,
            'status' => 'Listar todos los Usuarios borrados',
            'users' => $users,
        ], 200);
    }

    /**
     * Brings back a deleted blog post.
     * @param Int $id
     * @return JsonResponse
     */
    public function userDeleteShow($id)
    {
        $this->authorize('userDeleteShow', User::class);

        try {
            DB::beginTransaction();

                $user = User::select([
                    "id", "username", "email", "created_at", "updated_at", "deleted_at",
                ])
                    ->with(["payments",
                            "roles"
                    ])
                    ->onlyTrashed()
                    ->findOrFail($id);;

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Información del usuario borrado',
                'user' => $user,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'Usuario no encontrado en el sistema de borrados lógicos',
            ], 200);
        }
    }


    /**
     * Brings back a deleted blog post.
     * @param Int $id
     * @return JsonResponse
     */
    public function userDeleteRestore(int $id)
    {
        $this->authorize('userDeleteRestore', User::class);

        try {
            DB::beginTransaction();

            $user = User::onlyTrashed()->findOrFail($id)->restore();

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Usuario restaurado',
                'user' => $user,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'Usuario no encontrado en la lista de usuario borrados lógicos',
                'Error' =>  $exception->getMessage(),
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function userDeleteForce($id)
    {
        $this->authorize('userDeleteForce', User::class);

        try {
            DB::beginTransaction();

                $user = User::onlyTrashed()->findOrFail($id)->forceDelete();

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Usuario borrado totalmente del sistema',
                'user' => $user,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'Usuario no encontrado',
                'Error' =>  $exception->getMessage(),
            ], 200);
        }
    }
}
