<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Presupuesto;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use App\Models\PresupuestoItem;
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
            "total"=>$presupuestos->total(),
            "presupuestos"=> PresupuestoCollection::make($presupuestos)
        ]);

    }

    public function config()
    {
        
        $specialities = Specialitie::where("state",1)->get();

        return response()->json([
            "specialities" => $specialities,
        ]);
    }
    
    public function query_patient(Request $request)
    {
        $n_doc =$request->get("n_doc");

        $patient = Patient::where("n_doc", $n_doc)->first();

        if(!$patient){
            return response()->json([
                "message"=>403,
            ]);
        }

        return response()->json([
            "message"=>200,
            "id"=>$patient->id,
            "name"=>$patient->name,
            "surname"=>$patient->surname,
            "phone"=>$patient->phone,
            "n_doc"=>$patient->n_doc,
        ]);

    }

   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $patient = null;
        $patient = Patient::where("n_doc", $request->n_doc)->first();
        $doctor = User::where("id", $request->doctor_id)->first();

        // $request->request->add(["medical" => $request->medical]);
        // $request->request->add(["medical"=>json_encode($request->medical)]);
        
        if(!$patient){
            $patient = Patient::create([
                "name"=>$request->name,
                "surname"=>$request->surname,
                "email"=>$request->email,
                "n_doc"=>$request->n_doc,
                "phone"=>$request->phone,
            ]);
        }
        
            $presupuesto = Presupuesto::create([
                "doctor_id" =>$request->doctor_id,
                "patient_id" =>$patient->id,
                "speciality_id" => $request->speciality_id,
                "description" => $request->description,
                "diagnostico" => $request->diagnostico,
                "amount" =>$request->amount, // Ensure this is updated correctly
            ]);

            if (is_array($request->presupuestoitems)) {
                foreach ($request->presupuestoitems as $item) {
                    $presupuestoitems = PresupuestoItem::create([
                        "presupuesto_id" => $presupuesto->id,
                        'name_medical' => $item['name_medical'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                    ]);
                }
            } else {
                $presupuestoitems = PresupuestoItem::create([
                    "presupuesto_id" => $presupuesto->id,
                    'name_medical' => $request->presupuestoitems['name_medical'],
                    'cantidad' => $request->presupuestoitems['cantidad'],
                    'precio' => $request->presupuestoitems['precio'],
                ]);
            }




        // Mail::to($presupuesto->patient->email)->send(new NewPresupuestoRegisterMail($presupuesto));
        // Mail::to($doctor->email)->send(new NewpresupuestoRegisterMail($presupuesto));

        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
            "presupuestoitems" => $presupuestoitems,
            
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
        $presupuestoitems = PresupuestoItem::where('presupuesto_id', $id)->get();
        // $sum_total_pays = presupuestoPay::where("presupuesto_id",$id)->sum("amount");
        $costo = $presupuesto->amount;
        // $deuda = ($costo - $sum_total_pays); 

        return response()->json([
            "costo" => $costo,
            "presupuestoitems" => $presupuestoitems,
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
        $presupuestoitems = PresupuestoItem::where("presupuesto_id", $id)->get();
        
        $request->validate([
            'amount' => 'required|numeric', // Ensure amount is present and is a number
        ]);

        // $request->request->add(["medical"=>json_encode($request->medical)]);
        
        $presupuesto->update([
            "doctor_id" =>$request->doctor_id,
            "patient_id" =>$request->patient_id,
            "speciality_id" => $request->speciality_id,
            "description" =>$request->description,
            "diagnostico" =>$request->diagnostico,
            "amount" =>$request->amount,
        ]);

        if (is_array($request->presupuestoitems)) {
            foreach ($request->presupuestoitems as $item) {
                $presupuestoitem = PresupuestoItem::findOrFail($item['id']);
                $presupuestoitem->update([
                    "presupuesto_id" => $presupuesto->id,
                    'name_medical' => $item['name_medical'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                ]);
            }
        } else {
            $presupuestoitem = PresupuestoItem::findOrFail($request->presupuestoitems['id']);
            $presupuestoitem->update([
                "presupuesto_id" => $presupuesto->id,
                'name_medical' => $request->presupuestoitems['name_medical'],
                'cantidad' => $request->presupuestoitems['cantidad'],
                'precio' => $request->presupuestoitems['precio'],
            ]);
        }



        return response()->json([
            "message" => 200,
            "presupuesto" => PresupuestoResource::make($presupuesto),
            "presupuestoitems" => $presupuestoitems,
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
    public function destroyitem(request  $presupuesto_id, $presupuestoitem_id)
    {
        $presupuestoitem = PresupuestoItem::where('id', $presupuestoitem_id)->first();
        if (!$presupuestoitem) {
            return response()->json(["message" => "Presupuesto item not found."], 404);
        }
        $presupuestoitem = PresupuestoItem::where('presupuesto_id', $presupuesto_id)
        ->where('id', $presupuestoitem_id)->first();

        //unimos la request presupuesto con el presupuestoitem
        // $presupuesto->presupuestoitem()->detach($presupuestoitem_id);
        $presupuestoitem->delete(); 
        return response()->json([
            "message" => 200,
            ]);


        
    }
    
     public function atendidas()
    {
        
        $presupuestos = Presupuesto::where('status', 2)->orderBy("id", "desc")
                            ->paginate(10);
        return response()->json([
            "total"=>$presupuestos->total(),
            "presupuestos"=> PresupuestoCollection::make($presupuestos)
        ]);

    }

    public function updateConfirmation(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrfail($id);
        $doctor = User::where("id", $request->doctor_id)->first();

        $presupuesto->confimation = $request->confimation;
        $presupuesto->update();
        
        if($request->confimation === '2'){
            // Mail::to($presupuesto->patient->email)->send(new Confirmationpresupuesto($presupuesto));
        }
        
        return response()->json([
            "message" => 200,
            "presupuesto" => $presupuesto,
            "amount" =>$request->amount,
            "paymentmethod" =>$request->method_payment,
            "amountadd" =>$request->amount_add,
            "date_presupuesto" => Carbon::parse($presupuesto->date_presupuesto)->format('d-m-Y'),
            "patient"=>$presupuesto->patient_id ? 
                    [
                        "id"=> $presupuesto->patient->id,
                        "email" =>$presupuesto->patient->email,
                        "full_name" =>$presupuesto->patient->name.' '.$presupuesto->patient->surname,
                    ]: NULL,
            "speciality"=>$presupuesto->speciality ? 
                [
                    "id"=> $presupuesto->speciality->id,
                    "name"=> $presupuesto->speciality->name,
                ]: NULL,
            "doctor_id" => $presupuesto->doctor_id,
            "doctor"=>$presupuesto->doctor_id ? 
                        [
                            "id"=> $doctor->id,
                            "email"=> $doctor->email,
                            "full_name" =>$doctor->name.' '.$doctor->surname,
                        ]: NULL,
        ]);
    }

    public function presupuestoByDoctor(Request $request, $doctor_id)
    {
        $doctor_is_valid = User::where("id", $request->doctor_id)->first();
        if(!$doctor_is_valid){
            return response()->json([
                "message"=>'403',
            ]);
        }
        $presupuestos = Presupuesto::where('doctor_id', $doctor_id)->get();


        return response()->json([
            // "presupuestos"=> $presupuestos,
            "presupuestos"=> PresupuestoCollection::make($presupuestos)
            // "total"=>$appointments->total(),
        ]);
    }
}
