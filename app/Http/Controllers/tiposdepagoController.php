<?php

namespace App\Http\Controllers;

use App\Models\Tiposdepago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class tiposdepagoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tiposdepagos = Tiposdepago::orderBy('created_at', 'DESC')
        ->get();


        return response()->json([
            'code' => 200,
            'status' => 'Listar todos los Pagos',
            'tiposdepagos' => $tiposdepagos,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
       
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentStore(Request $request)
    {
        //
        return Tiposdepago::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tiposdepago  $tipodepago
     * @return \Illuminate\Http\Response
     */
    public function paymentShow(Tiposdepago $tipodepago)
    {
        //
        if (!$tipodepago) {
            return response()->json([
                'message' => 'Pago not found.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'tipodepago' => $tipodepago,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PaymentMethod  $tiposdepago
     * @return \Illuminate\Http\Response
     */
    public function edit(Tiposdepago $tiposdepago)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tiposdepago  $tiposdepago
     * @return \Illuminate\Http\Response
     */
    public function paymentUpdate(Request $request, $id)
    {
       $tipodepago = Tiposdepago::findOrfail($id);
        $tipodepago->bankAccount = $request->bankAccount;
        $tipodepago->bankAccountType = $request->bankAccountType;
        $tipodepago->bankName = $request->bankName;
        $tipodepago->ciorif = $request->ciorif;
        $tipodepago->clientId = $request->clientId;
        $tipodepago->email = $request->email;
        $tipodepago->id = $request->id;
        $tipodepago->paypalSecret = $request->paypalSecret;
        $tipodepago->sandoxMode = $request->sandoxMode;
        $tipodepago->telefono = $request->telefono;
        $tipodepago->type = $request->type;
        $tipodepago->user = $request->user;
        
        
        
        $tipodepago->update();
        return $tipodepago;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tiposdepago  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function paymentDestroy(Tiposdepago $tiposdepago, $id)
    {
        $tiposdepago =  Tiposdepago::where('id', $id)
                        ->first();

        if(!empty($tiposdepago)){

            // borrar
            $tiposdepago->delete();
            // devolver respuesta
            $data = [
                'code' => 200,
                'status' => 'success',
                'tiposdepago' => $tiposdepago
            ];
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'el tiposdepago no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function updateStatus(Request $request, $id)
    {
        $tiposdepago = Tiposdepago::findOrfail($id);
        $tiposdepago->status = $request->status;
        $tiposdepago->update();
        return $tiposdepago;
    }

    public function activos()
    {

        $tiposdepagos = Tiposdepago::orderBy('created_at', 'DESC')
                
                ->where('status', $status='ACTIVE')
                ->get();
            return response()->json([
                'code' => 200,
                'status' => 'Listar tiposdepagos activas',
                'tiposdepagos' => $tiposdepagos,
            ], 200);
    }
}
