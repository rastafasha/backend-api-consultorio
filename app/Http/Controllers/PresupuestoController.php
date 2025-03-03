<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Presupuesto;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Mail\Registerpresupuesto;
use App\Models\Doctor\Specialitie;
use App\Http\Controllers\Controller;
use App\Mail\UpdatedPresupuestoMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\Confirmationpresupuesto;
use App\Mail\NewpresupuestoRegisterMail;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Http\Resources\Presupuesto\PresupuestoCollection;

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

    public function queryPatient(Request $request)
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

    public function store(Request $request)
    {
        $patient = null;
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::where("id", $request->doctor_id)->first();

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
            "medical" => $request->medical,
        ]);

        Mail::to($presupuesto->patient->email)->send(new NewPresupuestoRegisterMail($presupuesto));

        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
        ]);
    }

    public function show($id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $costo = $presupuesto->amount;

        return response()->json([
            "costo" => $costo,
            "presupuesto" => PresupuestoResource::make($presupuesto),
        ]);
    }

    public function update(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric',
            'medical' => 'required|array',
        ]);

        $request->request->add(["medical" => json_encode($request->medical)]);

        $presupuesto->update([
            "doctor_id" => $request->doctor_id,
            "patient_id" => $request->patient_id,
            "speciality_id" => $request->speciality_id,
            "description" => $request->description,
            "diagnostico" => $request->diagnostico,
            "amount" => $request->amount,
            "medical" => $request->medical,
        ]);

        return response()->json([
            "message" => 200,
            "presupuesto" => PresupuestoResource::make($presupuesto),
        ]);
    }

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

        if ($request->confimation === '2') {
            // Mail::to($presupuesto->patient->email)->send(new Confirmationpresupuesto($presupuesto));
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
                    ] : null,
            "speciality" => $presupuesto->speciality ?
                [
                    "id" => $presupuesto->speciality->id,
                    "name" => $presupuesto->speciality->name,
                ] : null,
            "doctor_id" => $presupuesto->doctor_id,
            "doctor" => $presupuesto->doctor_id ?
                        [
                            "id" => $doctor->id,
                            "email" => $doctor->email,
                            "full_name" => $doctor->name . ' ' . $doctor->surname,
                        ] : null,
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
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ]);
    }

    public function byPatient(Request $request, $n_doc)
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
            "presupuestos" => PresupuestoCollection::make($presupuestos)
        ], 200);
    }
}
