<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\AuthRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\AuthLoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ChangePasswordRequest;
use Symfony\Component\HttpFoundation\Response;

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

        $data = $request->only('name', 'email', 'password', 'n_doc');

        $validator = Validator::make($data, [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:5',
            'n_doc' => 'required',
            'role' => Rule::in([User::GUEST]),
        ]);
        

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'n_doc' => $request->n_doc,
            'password' => Hash::make($request->password),
            'role' => User::GUEST,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
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