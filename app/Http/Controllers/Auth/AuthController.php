<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\Patient\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized - Credenciales incorrectas'], 401);
        }

        $user = User::where('email', request('email'))->firstOrFail();

        $permissions = auth('api')->user()->getAllPermissions()->map(function ($perm) {
            return $perm->name;
        });
        return response()->json([
            'message' => "Inicio de sesión exitoso",
            'access_token' => $this->respondWithToken($token),
            'token_type' => 'Bearer',
            // 'user' => $user,
            'user' => [
                "id" => auth('api')->user()->id,
                "name" => auth('api')->user()->name,
                "surname" => auth('api')->user()->surname,
                // "rolename"=>auth('api')->user()->rolename,
                "roles" => auth('api')->user()->getRoleNames(),
                "avatar" => auth('api')->user()->avatar,
                "email" => auth('api')->user()->email,
                "n_doc" => auth('api')->user()->n_doc,
                "permissions" => $permissions,

            ],
        ], 201);

    }

    /**
     * Register a User
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // 1. Validaciones mínimas
        $validator = Validator::make($request->all(), [
            'n_doc' => 'required|exists:patients,n_doc|unique:users,n_doc',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ], [
            'n_doc.exists' => 'No estás registrado en el consultorio.',
            'n_doc.unique' => 'Este documento ya tiene una cuenta activa.',
        ]);

        if ($validator->fails())
            return response()->json($validator->errors(), 422);

        // 2. Buscamos al paciente del consultorio (ID 21)
        $paciente = Patient::where('n_doc', $request->n_doc)->first();

        // 3. Creamos el Usuario (ID 12) usando los datos que ya tenemos en la ficha médica
        $user = User::create([
            'name' => $paciente->name,    // Heredamos del consultorio
            'surname' => $paciente->surname, // Heredamos del consultorio
            'email' => $request->email,
            'n_doc' => $request->n_doc,
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
public function loginPaciente(Request $request): \Illuminate\Http\JsonResponse
{
    // 1. Validamos la entrada desde Angular (puerto 4203)
    $validator = Validator::make($request->all(), [
        'name'  => 'required|string',
        'n_doc' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // 2. Buscamos al paciente por su Nombre y Cédula para extraer su correo registrado
    $user = User::whereRaw('LOWER(name) = ?', [strtolower($request->name)])
                ->where('n_doc', $request->n_doc)
                ->first();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized - Datos del paciente incorrectos'], 401);
    }

    // 3. 🚀 EL TRUCO MAESTRO: Autenticamos usando su correo y su cédula como contraseña
    // Como tu seeder y tu registro automático guardan la contraseña cifrada con el n_doc,
    // esto obliga a JWT a generar el token firmado original sin romper el guard de la API.
    $credentials = [
        'email'    => $user->email,
        'password' => $request->n_doc // Su cédula actúa como su clave secreta
    ];

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized - No se pudo verificar la firma del paciente'], 401);
    }

    // 4. Despachamos la respuesta utilizando nuestro nuevo helper blindado
    return $this->respondWithTokenPaciente($token, $user);
}







    // el nombre del paciente es el usuario para registro y el n_doc seria la contraseña
    public function registerPaciente(Request $request)
    {
        // 1. Validaciones mínimas adaptadas
        $validator = Validator::make($request->all(), [
            'n_doc' => 'required|exists:patients,n_doc|unique:users,n_doc',
        ], [
            'n_doc.exists' => 'El paciente no existe registrado en la base de datos médica.',
            'n_doc.unique' => 'Este documento de identidad ya tiene un usuario activo.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Buscamos la ficha del paciente que acaba de crear el doctor
        // Hacemos un eager loading de 'doctors' para saber qué médico lo atiende
        $paciente = Patient::with('doctors')->where('n_doc', $request->n_doc)->first();

        // 3. Solución para el Email
        $emailFinal = $paciente->email ?? $request->email ?? ($request->n_doc . '@klyntic.local');

        // 4. Creamos el Usuario en MySQL
        $user = User::create([
            'name' => $paciente->name,
            'surname' => $paciente->surname,
            'email' => $emailFinal,
            'n_doc' => $request->n_doc,
            'password' => Hash::make($request->n_doc), // Cédula como contraseña
        ]);

        // 5. Vinculamos la ficha médica con el nuevo usuario
        $paciente->update(['user_id' => $user->id]);
        $user->assignRole(User::GUEST);

        // --- 🚀 CONEXIÓN EN TIEMPO REAL CON NODE.JS (MongoDB) ---
        // Extraemos el ID del doctor asignado. Si no tiene, por defecto dejamos vacío o un ID del sistema.
        $doctorAsignado = $paciente->doctors->first();
        $doctorId = $doctorAsignado ? (string) $doctorAsignado->id : "0";

        try {
            // Le avisamos a tu ruta de Klyntic en Node/Render para que guarde al paciente en Mongo
            // Usamos una petición rápida. Node responderá 200 al tiro y se encargará del guardado
            Http::post('https://tu-node-en-render.com', [
                'nombre_paciente' => $paciente->name . ' ' . $paciente->surname,
                'telefono_paciente' => $paciente->phone,
                'mongo_user_id' => $doctorId, // El ID de MySQL del doctor que usará Node como _id
                'fecha_cita' => now()->timezone('America/Caracas')->toIso8601String() // Fecha de registro o primera cita
            ]);
        } catch (\Exception $e) {
            // Logueamos el error de conectividad de forma interna para no trancar la experiencia del usuario
            Log::error('No se pudo sincronizar el paciente con el microservicio Node: ' . $e->getMessage());
        }
        // --------------------------------------------------------

        return response()->json([
            'message' => 'Usuario médico creado, vinculado y sincronizado correctamente',
            'user' => [
                'name' => $user->name,
                'username' => $user->n_doc,
                'email' => $user->email
            ],
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
        $permissions = auth('api')->user()->getAllPermissions()->map(function ($perm) {
            return $perm->name;
        });
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 180,
            // 'user'=>auth('api')->user(),
            'user' => [
                "name" => auth('api')->user()->name,
                "surname" => auth('api')->user()->surname,
                "rolename" => auth('api')->user()->rolename,
                "email" => auth('api')->user()->email,
                "n_doc" => auth('api')->user()->n_doc,
                "permissions" => $permissions,

            ],
        ]);
    }

    protected function respondWithTokenPaciente($token, $user)
{
    // El paciente de tipo GUEST no maneja permisos dinámicos, dejamos un array vacío
    $permissions = collect([]);

    return response()->json([
        'message' => "Inicio de sesión de paciente exitoso",
        'access_token' => $token, // El token firmado nativamente por la librería
        'token_type' => 'Bearer',
        'user' => [
            "id"          => $user->id,
            "name"        => $user->name,
            "surname"     => $user->surname,
            "roles"       => $user->getRoleNames(), // Devolverá ["GUEST"]
            "avatar"      => $user->avatar,
            "email"       => $user->email,
            "n_doc"       => $user->n_doc,
            "permissions" => $permissions,
        ],
    ], 201);
}


    /**
     * Change password 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword($request)
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