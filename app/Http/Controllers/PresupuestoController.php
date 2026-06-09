<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presupuesto\PresupuestoCollection;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Mail\Confirmationpresupuesto;
use App\Mail\NewpresupuestoRegisterMail;
use App\Mail\Registerpresupuesto;
use App\Mail\UpdatedPresupuestoMail;
use App\Models\Doctor\Specialitie;
use App\Models\Patient\Patient;
use App\Models\Presupuesto;
use App\Models\User;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PresupuestoController extends Controller
{
    public function index(Request $request)
    {
        $speciality_id = $request->speciality_id;
        $name_doctor = $request->search;
        $date = $request->date;

        $presupuestos = Presupuesto::filterAdvance($speciality_id, $name_doctor, $date)->orderBy("id", "desc")
            ->paginate(10);
        return response()->json([
            "total" => $presupuestos->total(),
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ]);

    }

    public function config()
    {

        $specialities = Specialitie::where("state", 1)->get();

        return response()->json([
            "specialities" => $specialities,
        ]);
    }

    public function query_patient(Request $request)
    {
        $n_doc = $request->get("n_doc");

        $patient = Patient::where("n_doc", $n_doc)->first();

        if (!$patient) {
            return response()->json([
                "message" => 403,
            ]);
        }

        return response()->json([
            "message" => 200,
            "id" => $patient->id,
            "name" => $patient->name,
            "surname" => $patient->surname,
            "phone" => $patient->phone,
            "n_doc" => $patient->n_doc,
        ]);

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePresupuesto(Request $request)
    {

        $patient = null;
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::where("id", $request->doctor_id)->first();

        // $request->request->add(["medical" => $request->medical]);
        $request->request->add(["medical" => json_encode($request->medical)]);

        if (!$patient) {
            $patient = Patient::create([
                "name" => $request->name,
                "surname" => $request->surname,
                "email" => $request->email,
                "n_doc" => $request->n_doc,
                "phone" => $request->phone,
            ]);
        }


        $presupuesto = Presupuesto::create([
            "doctor_id" => $request->doctor_id,
            "patient_id" => $patient->id,
            "speciality_id" => $request->speciality_id,
            "description" => $request->description,
            "diagnostico" => $request->diagnostico,
            "amount" => $request->amount,
            "medical" => $request->medical, // Ensure this is updated correctly
        ]);



        // Mail::to($presupuesto->patient->email)->send(new NewPresupuestoRegisterMail($presupuesto));
        // Mail::to($doctor->email)->send(new NewpresupuestoRegisterMail($presupuesto));

        // =========================================================================
        // 🧪 VENENO INYECTADO: NOTIFICACIÓN DE NUEVO PRESUPUESTO AL PACIENTE
        // =========================================================================
        NotificacionService::enviar(
            $presupuesto->doctor_id,                                              // Consultorio ID para WhatsApp
            $patient->phone,                                                      // Teléfono del paciente
            "Hola " . $patient->name . ", el especialista ha generado un nuevo presupuesto médico para tu tratamiento por un monto de $" . $presupuesto->amount . ". Ya puedes revisarlo detalladamente e iniciar tu gestión de pago ingresando a tu aplicación.",
            $patient->id,                                                         // ID del paciente para encender su campana
            'PACIENTE',                                                           // Rol
            '📋 Nuevo Presupuesto Disponible',                                    // Título del Toastr
            'PRESUPUESTO_NUEVO',                                                  // Enum tipo
            $presupuesto->id                                                      // Referencia del presupuesto en MySQL
        );

        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,

        ]);
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        // $sum_total_pays = presupuestoPay::where("presupuesto_id",$id)->sum("amount");
        $costo = $presupuesto->amount;
        // $deuda = ($costo - $sum_total_pays); 

        return response()->json([
            "costo" => $costo,
            // "deuda" => $deuda,
            "presupuesto" => PresupuestoResource::make($presupuesto),
        ]);

    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $e
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric', // Ensure amount is present and is a number
            'medical' => 'required|array', // Ensure medical is present and is an array
        ]);

        $request->request->add(["medical" => json_encode($request->medical)]);

        $presupuesto->update([
            "doctor_id" => $request->doctor_id,
            "patient_id" => $request->patient_id,
            "speciality_id" => $request->speciality_id,
            "description" => $request->description,
            "diagnostico" => $request->diagnostico,
            "amount" => $request->amount,
            "medical" => $request->medical, // Ensure this is updated correctly
        ]);

        return response()->json([
            "message" => 200,
            "presupuesto" => PresupuestoResource::make($presupuesto),
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
        $presupuesto = Presupuesto::findOrFail($id);
        $presupuesto->delete();
        return response()->json([
            "message" => 200,
        ]);
    }

    public function atendidas()
    {

        $presupuestos = Presupuesto::where('status', 2)->orderBy("id", "desc")
            ->paginate(10);
        return response()->json([
            "total" => $presupuestos->total(),
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ]);

    }

    public function updateConfirmation(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrfail($id);
        $doctor = User::where("id", $request->doctor_id)->first();

        $presupuesto->confimation = $request->confimation;
        $presupuesto->update();
        
        // Ejemplo para el futuro: Solo envía el correo si el campo no está vacío
        // if (!empty($appointment->patient->email)) {
        //     NewAppointmentRegisterJob::dispatch($appointment)->onQueue('emails');
        // }
        // if ($request->confimation === '2') {
        //     Mail::to($presupuesto->patient->email)->send(new Confirmationpresupuesto($presupuesto));
        // }

        // =========================================================================
        // 🧪 VENENO INYECTADO: NOTIFICACIÓN DE PRESUPUESTO APROBADO AL MÉDICO (KLYNTIC)
        // =========================================================================
        if ($request->confimation == 2) {
            NotificacionService::enviar(
                $presupuesto->doctor_id,                                              // Consultorio ID para mapeo interno
                null,                                                                 // Teléfono null porque al médico no le enviamos WhatsApp por esto
                "El paciente " . $presupuesto->patient->name . " " . $presupuesto->patient->surname . " ha APROBADO el presupuesto por un monto de $" . $presupuesto->amount . ".",
                $presupuesto->doctor_id,                                              // ID del médico para encender su campana en el CRM
                'MEDICO',                                                             // Rol destinatario
                '🎉 ¡Presupuesto Aprobado por Paciente!',                             // Título del Toastr
                'PRESUPUESTO_APROBADO',                                               // Enum tipo
                $presupuesto->id                                                      // ID del presupuesto en MySQL como referencia
            );
        }

        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
            "amount" => $request->amount,
            "paymentmethod" => $request->method_payment,
            "amountadd" => $request->amount_add,
            "date_presupuesto" => Carbon::parse($presupuesto->date_presupuesto)->format('d-m-Y'),
            "patient" => $presupuesto->patient_id ?
                [
                    "id" => $presupuesto->patient->id,
                    "email" => $presupuesto->patient->email,
                    "full_name" => $presupuesto->patient->name . ' ' . $presupuesto->patient->surname,
                ] : NULL,
            "speciality" => $presupuesto->speciality ?
                [
                    "id" => $presupuesto->speciality->id,
                    "name" => $presupuesto->speciality->name,
                ] : NULL,
            "doctor_id" => $presupuesto->doctor_id,
            "doctor" => $presupuesto->doctor_id ?
                [
                    "id" => $doctor->id,
                    "email" => $doctor->email,
                    "full_name" => $doctor->name . ' ' . $doctor->surname,
                ] : NULL,
        ]);
    }

    public function presupuestoByDoctor(Request $request, $doctor_id)
    {
        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        if (!$doctor_is_valid) {
            return response()->json([
                "message" => '403',
            ]);
        }
        $presupuestos = Presupuesto::where('doctor_id', $doctor_id)->get();


        return response()->json([
            // "presupuestos"=> $presupuestos,
            "presupuestos" => PresupuestoCollection::make($presupuestos)
            // "total"=>$appointments->total(),
        ]);
    }

    public function bypatient(Request $request, $n_doc)
    {
        $patient = Patient::where("n_doc", $n_doc)->first();

        if (!$patient) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Patient not found',
            ], 404);
        }

        $presupuestos = Presupuesto::where("patient_id", '=', $patient->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            // 'presupuestos' => $presupuestos,
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ], 200);
    }
}
