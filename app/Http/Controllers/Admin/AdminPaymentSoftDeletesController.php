<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminPaymentSoftDeletesController extends Controller
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
        $this->authorize('indexDeletes', Payment::class);
        
        $payments = Payment::select([
            "name",
        ])
        ->orderBy('id', 'desc')
        ->onlyTrashed()
        ->get();
            
        return response()->json([
            'code' => 200,
            'status' => 'Listar todos los pagos borrados',
            'payments' => $payments,
        ], 200);
    }

    /**
     * Brings back a deleted blog post.
     * @param Int $id
     * @return JsonResponse
     */
    public function paymentDeleteShow($id)
    {   
        $this->authorize('paymentDeleteShow', Payment::class);

        try {
            DB::beginTransaction();

                $payment = Payment::select([
                    "id", "name", "created_at", "updated_at", "deleted_at",
                ])
                ->onlyTrashed()
                ->findOrFail($id);;

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Información del pago borrada',
                'payment' => $payment,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'Pago no encontrado en el sistema de borrados lógicos',
            ], 200);
        }
    }


    /**
     * Brings back a deleted blog post.
     * @param Int $id
     * @return JsonResponse
     */
    public function paymentDeleteRestore(int $id)
    {    
        $this->authorize('paymentDeleteRestore', Payment::class);

        try {
            DB::beginTransaction();

            $payment = Payment::onlyTrashed()->findOrFail($id)->restore();

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Pago restaurada',
                'payment' => $payment,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'No se encontra en la lista de pagos borrados lógicos',
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function paymentDeleteForce($id)
    {   
        $this->authorize('paymentDeleteForce', Payment::class);

        try {
            DB::beginTransaction();

            $payment = Payment::onlyTrashed()->findOrFail($id)->forceDelete();

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'Pago fue borrado totalmente del sistema',
                'payment' => $payment,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'Pago no encontrado',
                'Error' =>  $exception->getMessage(),
            ], 200);
        }
    }
}
