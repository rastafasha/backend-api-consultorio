<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    /**
     * Reset password validate email and send email
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    {

        if (!$this->validateEmail($request->email)) {
            return response()->json([
                'code' => 404,
                'message' => 'El correo no existe o tiene algún error, verifica por favor....'
            ], 404);
        } else {

            $this->sendMail($request->email);

            return response()->json([
                'code' => 201,
                'message' => "Enlace para restablecer su contraseña ha sido enviado a su correo electrónico.",
            ], 201);
        }
    }

    /**
     * Generate token and send mail to user
     *
     * @param [type] $email
     * @return void
     */
    // public function sendMail($email)
    // {
    //     $token = $this->generateToken($email);
    //     Mail::to($email)->send(new PasswordResetMail($token));
    // }

    public function sendMail(Request $request)
    {


        if($this->validateEmail($request->email)){
            return $this->failedResponse();
        }

        $this->send($request->email);
        return $this->successResponse();
    }

    public function send($email)
    {
        $token = $this->createToken($email);
        Mail::to($email)->send(new PasswordResetMail($token));
    }



     public function failedResponse()
    {

        return response()->json([
            'error'=>'Email no existe'
        ], Response::HTTP_NOT_FOUND);
    }

    public function successResponse()
    {

        return response()->json([
            'data'=>'Reset email is send '
        ], Response::HTTP_OK);
    }

    /**
     * Validate email where user
     *
     * @param [type] $email
     * @return void
     */
    public function validateEmail($email)
    {
        return !!User::where('email', $email)->first();
    }

    public function createToken($email){
        $oldToken = DB::table('password_resets')->where('email', $email)->first();
        if($oldToken){
            return $oldToken;
        }

        $token = str_random(80);
        $this->saveToken($token, $email);

        return $token;
    }

    public function saveToken($token, $email){
        DB::table('password_resets')->insert(
            [
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
            );
    }

    /**
     * Generate token for email
     *
     * @param [type] $email
     * @return void
     */
    // public function generateToken($email)
    // {
    //     $isOtherToken = DB::table('password_resets')->where('email', $email)->first();
    //     if ($isOtherToken) {
    //         return $isOtherToken->token;
    //     }
    //     $token = Str::random(80);;
    //     $this->storeToken($token, $email);
    //     return $token;
    // }

    /**
     * Save data in table
     *
     * @param [type] $token
     * @param [type] $email
     * @return void
     */
    // public function storeToken($token, $email)
    // {
    //     DB::table('password_resets')->insert([
    //         'email' => $email,
    //         'token' => $token,
    //         'created_at' => Carbon::now()
    //     ]);
    // }
}
