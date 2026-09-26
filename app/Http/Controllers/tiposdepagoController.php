<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    /**
     * Listar todos los Pagos aislados por el contexto de la sucursal.
     */
    public function index()
    {
        $query = Tiposdepago::query();

        // 🏢 CONTROL ESTRICTO ENTERPRISE
        if (app()->has('current_clinica_id')) {
            $clinicaId = app('current_clinica_id');
            
            // Forzamos a que traiga ÚNICAMENTE lo que tenga el ID de tu clínica en MAMP
            $query->where('clinica_id', $clinicaId);
        } else {
            // Si por alguna razón de pruebas no hay cabecera, en local forzamos la clínica 1 
            // para que no se mezcle el desastre de datos heredados
            $query->where('clinica_id', 1);
        }

        $tiposdepagos = $query->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'tiposdepagos' => $tiposdepagos,
        ], 200);
    }

    public function activos()
    {
        $query = Tiposdepago::where('status', 'ACTIVE');

        if (app()->has('current_clinica_id')) {
            $query->where('clinica_id', app('current_clinica_id'));
        } else {
            $query->where('clinica_id', 1); // Salvaguarda local
        }

        $tiposdepagos = $query->orderBy('created_at', 'DESC')->get();
        
        return response()->json([
            'code' => 200,
            'status' => 'Listar tiposdepagos activas',
            'tiposdepagos' => $tiposdepagos,
        ], 200);
    }

    public function byDoctor(Request $request, $doctor_id)
    {
        $query = Tiposdepago::query();

        if (app()->has('current_clinica_id')) {
            $query->where('clinica_id', app('current_clinica_id'));
        } else {
            $query->where('doctor_id', $doctor_id);
        }

        $tiposdepagos = $query->orderBy("id", "desc")->get();

        return response()->json([
            "tiposdepagos" => $tiposdepagos
        ]);
    }

   /**
     * Módulo adaptativo para el perfil público del doctor o selector express.
     */
    public function byDoctorActivo(Request $request, $doctor_id)
    {
        $query = Tiposdepago::where('status', 'ACTIVE');

        // 🏢 Si es un entorno de clínica, el paciente de Instagram debe pagar a la caja central [8]
        if (app()->has('current_clinica_id')) {
            $query->where('clinica_id', app('current_clinica_id'));
        } else {
            // 🟢 Si es consultorio Pro independiente, paga a la cuenta propia del médico [8]
            $query->where('doctor_id', $doctor_id);
        }

        $tiposdepagos = $query->orderBy("id", "desc")->get();

        return response()->json([
            "tiposdepagos" => $tiposdepagos
        ]);
    }

    
   public function paymentStore(Request $request)
    {
        $data = $request->all();

        // 🏢 Si la secretaria está registrando la cuenta desde el panel cerrado de la clínica
        if (app()->has('current_clinica_id')) {
            $data['clinica_id'] = app('current_clinica_id');
            // Opcional: Forzamos doctor_id en null si es cuenta única de la empresa [7]
            $data['doctor_id'] = null; 
        }

        return Tiposdepago::create($data);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tiposdepago  $tipodepago
     * @return \Illuminate\Http\Response
     */
    public function paymentShow(Tiposdepago $tipodepago)
    {
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'tipodepago' => $tipodepago,
        ], 200);
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
        $tipodepago = Tiposdepago::findOrFail($id);
        
        $tipodepago->bankAccount = $request->bankAccount;
        $tipodepago->bankAccountType = $request->bankAccountType;
        $tipodepago->bankName = $request->bankName;
        $tipodepago->ciorif = $request->ciorif;
        $tipodepago->clientId = $request->clientId;
        $tipodepago->email = $request->email;
        $tipodepago->paypalSecret = $request->paypalSecret;
        $tipodepago->sandoxMode = $request->sandoxMode;
        $tipodepago->telefono = $request->telefono;
        $tipodepago->type = $request->type;
        $tipodepago->user = $request->user;

        // Sincronización inteligente de llaves primarias contextuales [7]
        if (app()->has('current_clinica_id')) {
            $tipodepago->clinica_id = app('current_clinica_id');
            $tipodepago->doctor_id = null;
        } else {
            $tipodepago->doctor_id = $request->doctor_id;
            $tipodepago->clinica_id = null;
        }
        
        $tipodepago->update();
        return $tipodepago;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tiposdepago  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function paymentDestroy($id)
    {
        $tiposdepago = Tiposdepago::find($id);

        if (!empty($tiposdepago)) {
            $tiposdepago->delete();
            $data = ['code' => 200, 'status' => 'success', 'tiposdepago' => $tiposdepago];
        } else {
            $data = ['code' => 404, 'status' => 'error', 'message' => 'El tipo de pago no existe.'];
        }

        return response()->json($data, $data['code']);
    }

    public function updateStatus(Request $request, $id)
    {
        $tiposdepago = Tiposdepago::findOrFail($id);
        $tiposdepago->status = $request->status;
        $tiposdepago->update();
        return $tiposdepago;
    }

    
}
