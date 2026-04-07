<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\Patient\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

;

class AuthController extends Controller
{
    // /**
    //  * Create a new AuthController instance.
    //  *
    //  * @return void
    //  */
    // public function __construct()
    // {
    //     $this->middleware('jwt.verify', ['except' => ['login', 'register']]);
    // }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request()->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized - Credenciales incorrectas'], 401);
        }

        $user = User::where('email', request('email'))->firstOrFail();

        $permissions = auth('api')->user()->getAllPermissions()->map(function($perm){
            return $perm->name;
        });
        return response()->json([
            'message' => "Inicio de sesión exitoso",
            'access_token' => $this->respondWithToken($token),
            'token_type' => 'Bearer',
            // 'user' => $user,
            'user'=>[
                "id"=>auth('api')->user()->id,
                "name"=>auth('api')->user()->name,
                "surname"=>auth('api')->user()->surname,
                // "rolename"=>auth('api')->user()->rolename,
                "roles"=>auth('api')->user()->getRoleNames(),
                "avatar"=>auth('api')->user()->avatar,
                "email"=>auth('api')->user()->email,
                "n_doc"=>auth('api')->user()->n_doc,
                "permissions"=>$permissions,

            ],
        ], 201);
        
    }

    /**
     * Register a User
     * @return \Illuminate\Http\JsonResponse
     */
public function register(Request $request) {
    // 1. Validaciones mínimas
    $validator = Validator::make($request->all(), [
        'n_doc'    => 'required|exists:patients,n_doc|unique:users,n_doc',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:6',
    ], [
        'n_doc.exists' => 'No estás registrado en el consultorio.',
        'n_doc.unique' => 'Este documento ya tiene una cuenta activa.',
    ]);

    if($validator->fails()) return response()->json($validator->errors(), 422);

    // 2. Buscamos al paciente del consultorio (ID 21)
    $paciente = Patient::where('n_doc', $request->n_doc)->first();

    // 3. Creamos el Usuario (ID 12) usando los datos que ya tenemos en la ficha médica
    $user = User::create([
        'name'     => $paciente->name,    // Heredamos del consultorio
        'surname'  => $paciente->surname, // Heredamos del consultorio
        'email'    => $request->email,
        'n_doc'    => $request->n_doc,
        'password' => Hash::make($request->password),
    ]);

    // 4. Vinculamos la ficha médica con el nuevo usuario
    $paciente->update(['user_id' => $user->id]);

    $user->assignRole(User::GUEST);

    return response()->json([
        'message' => 'Cuenta activada correctamente',
        'access_token' => JWTAuth::fromUser($user),
    ], 201);
}


    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }
    
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $permissions = auth('api')->user()->getAllPermissions()->map(function($perm){
            return $perm->name;
        });
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 180,
            // 'user'=>auth('api')->user(),
            'user'=>[
                "name"=>auth('api')->user()->name,
                "surname"=>auth('api')->user()->surname,
                "rolename"=>auth('api')->user()->rolename,
                "email"=>auth('api')->user()->email,
                "n_doc"=>auth('api')->user()->n_doc,
                "permissions"=>$permissions,

            ],
        ]);
    }

    /**
     * Change password 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {

            return response()->json([
                'code' => 500,
                'status' => '¡La contraseña actual no coincide!',
                'user' => $user,
            ], 500);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'code' => 200,
            'status' => '¡Contraseña cambiada correctamente!',
            'user' => $user,
        ], 200);
    }
}