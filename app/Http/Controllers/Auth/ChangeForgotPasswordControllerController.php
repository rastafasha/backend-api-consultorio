<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ChangeForgotPasswordControllerController extends Controller
{
    /**
     * Reques data
     *
     * @param Request $request
     * @return void
     */
    public function changeForgotPassword(Request $request)
    {
        return $this->updatePasswordRow($request)->count() > 0 ?
        $this->resetPassword($request) : $this->tokenNotFoundError();

    }

   /**
    * Update passwor en table
    *
    * @param [type] $request
    * @return $request
    */
    private function updatePasswordRow($request)
    {
        return DB::table('password_resets')->where([
            'email' => $request->email,
            'token' => $request->resetToken,
            // 'token' => $request->token,
        ]);
    }

    /**
     * token no fount
     *
     * @return \Illuminate\Http\Response
     */
    private function tokenNotFoundError()
    {
        return response()->json([
            'code' => 422,
            'message' => "Su correo electrónico o token es incorrecto",
        ], 422);
    }

    /**
     * Confirm password change
     *
     * @param [type] $request
     * @return \Illuminate\Http\Response
     */
    private function resetPassword($request)
    {

        $userData = User::whereEmail($request->email)->first();

        $userData->update([
            'password' => bcrypt($request->password)
        ]);

        $this->updatePasswordRow($request)->delete();

        return response()->json([
            'code' => 200,
            'message' => " La contraseña ha sido cambiada con éxito",
        ], 200);
    }
}
