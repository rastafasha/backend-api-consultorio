<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Http\Resources\Appointment\AppointmentCollection;

class DashboardkpiController extends Controller
{
    public function config(){
        $users= User::orderBy("id", "desc")
        ->whereHas("roles", function($q){
            $q->where("name","like","%DOCTOR%");
        })
        ->get();

        return response()->json([
            "doctors"=>$users->map(function($user){
                return[
                    "id"=> $user->id,
                    "full_name"=> $user->name.' '.$user->surname,
                ];
            })
        ]);
    }
    public function dashboard_admin(Request $request){

        date_default_timezone_set('America/Caracas');
        //mes actual - appointments
        $now = now();
        $num_appointments_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->whereYear("date_appointment",$now->format("Y"))
                            ->whereMonth("date_appointment",$now->format("m"))
                            ->count();
        //mes anterior - appointments
        $before = now()->subMonth();
        $num_appointments_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->whereYear("date_appointment",$before->format("Y"))
                            ->whereMonth("date_appointment",$before->format("m"))
                            ->count();
        // versus % -appointmens
        $porcentajeD = 0;
        if($num_appointments_before > 0){
            $porcentajeD = (($num_appointments_current - $num_appointments_before) / $num_appointments_before)* 100;
        }


        //mes actual - patients
        $now = now();
        $num_patients_current = DB::table("patients")->where("deleted_at", NUll)
                            ->whereYear("created_at",$now->format("Y"))
                            ->whereMonth("created_at",$now->format("m"))
                            ->count();
        //mes anterior - patients
        $before = now()->subMonth();
        $num_patients_before = DB::table("patients")->where("deleted_at", NUll)
                            ->whereYear("created_at",$before->format("Y"))
                            ->whereMonth("created_at",$before->format("m"))
                            ->count();
        // versus % -patients
        $porcentajeDP = 0;
        if($num_patients_before > 0){
            $porcentajeDP = (($num_patients_current - $num_patients_before) / $num_patients_before)* 100;
        }


        //mes actual - appointments-attentions
        $now = now();
        $num_appointments_attention_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->whereYear("date_attention",$now->format("Y"))
                            ->whereMonth("date_attention",$now->format("m"))
                            ->count();
        //mes anterior - appointments-attentions
        $before = now()->subMonth();
        $num_appointments_attention_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->whereYear("date_attention",$before->format("Y"))
                            ->whereMonth("date_attention",$before->format("m"))
                            ->count();
        // versus % -appointmens-attentions
        $porcentajeDA = 0;
        if($num_appointments_attention_before > 0){
            $porcentajeDA = (($num_appointments_attention_current - $num_appointments_attention_before) / $num_appointments_attention_before)* 100;
        }

         //mes actual -  appointement total $ - (ganancias)
         $now = now();
         $num_appointments_total_current = DB::table("appointments")->where("deleted_at", NUll)
                             ->whereYear("date_appointment",$now->format("Y"))
                             ->whereMonth("date_appointment",$now->format("m"))
                             ->sum("appointments.amount");
         //mes anterior -  appointement total $ - (ganancias)
         $before = now()->subMonth();
         $num_appointments_total_before = DB::table("appointments")->where("deleted_at", NUll)
                             ->whereYear("date_appointment",$before->format("Y"))
                             ->whereMonth("date_appointment",$before->format("m"))
                             ->sum("appointments.amount");
         // versus % - appointement total $ - (ganancias)
         $porcentajeDT = 0;
         if($num_appointments_total_before > 0){
             $porcentajeDT = (($num_appointments_total_current - $num_appointments_total_before) / $num_appointments_total_before)* 100;
         }

         $appointments = Appointment::whereYear("date_appointment", $now->format("Y"))
                                    ->whereMonth("date_appointment", $now->format("m"))
                                    ->where("status",1)
                                    ->take(5)
                                    ->orderBy("id", "desc")
                                    ->get();

        return response()->json([
            "appointments"=>AppointmentCollection::make($appointments),
            "num_appointments_current"=>$num_appointments_current,
            "num_appointments_before"=>$num_appointments_before,
            "porcentaje_d"=> round($porcentajeD,2),
            //
            "num_patients_current"=>$num_patients_current,
            "num_patients_before"=>$num_patients_before,
            "porcentaje_dp"=> round($porcentajeDP,2),
            //
            "num_appointments_attention_current"=>$num_appointments_attention_current,
            "num_appointments_attention_before"=>$num_appointments_attention_before,
            "porcentaje_da"=> round($porcentajeDA,2),
             //   
            "num_appointments_total_current"=>$num_appointments_total_current,
            "num_appointments_total_before"=>$num_appointments_total_before,
            "porcentaje_dt"=> round($porcentajeDT,2),
        ]);
    }

    public function dashboard_admin_year(Request $request){
        $year = $request->year;
        $query_patients_by_gender = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->join("patients","appointments.patient_id", "=", "patients.id")
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("MONTH(appointments.date_appointment) as month"),
                            DB::raw("SUM(CASE WHEN patients.gender = 1 THEN 1 ELSE 0 END) as hombre"),
                            DB::raw("SUM(CASE WHEN patients.gender = 2 THEN 1 ELSE 0 END) as mujer"),
                        )->groupBy("year", "month")
                        ->orderBy("year")
                        ->orderBy("month")
                        ->get();
        
        $query_patients_speciality = DB::table("appointments")->where("appointments.deleted_at",NULL)
                                ->whereYear("appointments.date_appointment", $year)
                                ->join("specialities","appointments.speciality_id", "=", "specialities.id")
                                ->select("specialities.name as name", DB::raw("COUNT(appointments.speciality_id) as count"))
                                ->groupBy("specialities.name")
                                ->get();
       //top especialidades
        $query_patients_speciality_porcentaje = collect([]);
        $total_patients_speciality = $query_patients_speciality->sum("count");
        foreach ($query_patients_speciality as $key => $query_speciality){
            $count_by_speciality = $query_speciality->count;

            $percentage = round(($count_by_speciality/$total_patients_speciality)*100,2);
            
            $query_patients_speciality_porcentaje->push([
                "name"=> $query_speciality->name,
                "percentage"=> $percentage,
            ]);
        }
        //ingresos generales del año
        $query_income_year = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->where("appointments.status_pay", 1)
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("MONTH(appointments.date_appointment) as month"),
                            DB::raw("SUM(appointments.amount) as income ")
                        )->groupBy("year", "month")
                        ->orderBy("year")
                        ->orderBy("month")
                        ->get();

        $months_name = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

        return response()->json([
            "query_income_year" => $query_income_year,
            "months_name" => $months_name,
            "query_patients_speciality_porcentaje" => $query_patients_speciality_porcentaje,
            "query_patients_speciality" => $query_patients_speciality,
            "query_patients_by_gender" => $query_patients_by_gender,
        ]);               
    }

    public function dashboard_doctor(Request $request){

        date_default_timezone_set('America/Caracas');

        $doctor_id = $request->doctor_id;

        //mes actual - appointments
        $now = now();
        $num_appointments_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                            ->whereYear("date_appointment",$now->format("Y"))
                            ->whereMonth("date_appointment",$now->format("m"))
                            ->count();

        //mes anterior - appointments
        $before = now()->subMonth();
        $num_appointments_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                            ->whereYear("date_appointment",$before->format("Y"))
                            ->whereMonth("date_appointment",$before->format("m"))
                            ->count();
        // versus % -appointmens
        $porcentajeD = 0;
        if($num_appointments_before > 0){
            $porcentajeD = (($num_appointments_current - $num_appointments_before) / $num_appointments_before)* 100;
        }



        //mes actual - appointments-attentions
        $now = now();
        $num_appointments_attention_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                            ->whereYear("date_attention",$now->format("Y"))
                            ->whereMonth("date_attention",$now->format("m"))
                            ->count();
        //mes anterior - appointments-attentions
        $before = now()->subMonth();
        $num_appointments_attention_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                            ->whereYear("date_attention",$before->format("Y"))
                            ->whereMonth("date_attention",$before->format("m"))
                            ->count();
        // versus % -appointmens-attentions
        $porcentajeDA = 0;
        if($num_appointments_attention_before > 0){
            $porcentajeDA = (($num_appointments_attention_current - $num_appointments_attention_before) / $num_appointments_attention_before)* 100;
        }

         //mes actual -  appointement pago total $ - (ganancias)
         $now = now();
         $num_appointments_total_pay_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                             ->whereYear("date_appointment",$now->format("Y"))
                             ->whereMonth("date_appointment",$now->format("m"))
                             ->where("status_pay",1)
                             ->sum("appointments.amount");
         //mes anterior -  appointement pago total $ - (ganancias)
         $before = now()->subMonth();
         $num_appointments_total_pay_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                             ->whereYear("date_appointment",$before->format("Y"))
                             ->whereMonth("date_appointment",$before->format("m"))
                             ->where("status_pay",1)
                             ->sum("appointments.amount");
         // versus % - appointement total $ - (ganancias)
         $porcentajeDTP = 0;
         if($num_appointments_total_pay_before > 0){
             $porcentajeDTP = (($num_appointments_total_pay_current - $num_appointments_total_pay_before) / $num_appointments_total_pay_before)* 100;
         }

         //mes actual -  appointement pago pendiente $ - (ganancias)
         $now = now();
         $num_appointments_total_pending_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                             ->whereYear("date_appointment",$now->format("Y"))
                             ->whereMonth("date_appointment",$now->format("m"))
                             ->where("status_pay",2)
                             ->sum("appointments.amount");
         //mes anterior -  appointement pago pendiente $ - (ganancias)
         $before = now()->subMonth();
         $num_appointments_total_pending_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("doctor_id", $doctor_id)
                             ->whereYear("date_appointment",$before->format("Y"))
                             ->whereMonth("date_appointment",$before->format("m"))
                             ->where("status_pay",2)
                             ->sum("appointments.amount");
         // versus % - appointement total $ - (ganancias)
         $porcentajeDTPN = 0;
         if($num_appointments_total_pending_before > 0){
             $porcentajeDTPN = (($num_appointments_total_pending_current - $num_appointments_total_pending_before) / $num_appointments_total_pending_before)* 100;
         }

         $appointments = Appointment::whereYear("date_appointment", $now->format("Y"))
                                    ->where("doctor_id", $doctor_id)
                                    ->whereMonth("date_appointment", $now->format("m"))
                                    ->where("status",1)
                                    ->take(5)
                                    ->orderBy("id", "desc")
                                    ->get();

        return response()->json([
            "appointments"=>AppointmentCollection::make($appointments),
            "num_appointments_current"=>$num_appointments_current,
            "num_appointments_before"=>$num_appointments_before,
            "porcentaje_d"=> round($porcentajeD,2),
            //
            "num_appointments_attention_current"=>$num_appointments_attention_current,
            "num_appointments_attention_before"=>$num_appointments_attention_before,
            "porcentaje_da"=> round($porcentajeDA,2),
             // 
            "num_appointments_total_pay_current"=>$num_appointments_total_pay_current,
            "num_appointments_total_pay_before"=>$num_appointments_total_pay_before,
            "porcentaje_dtp"=> round($porcentajeDTP,2),
            //
            "num_appointments_total_pending_current"=>$num_appointments_total_pending_current,
            "num_appointments_total_pending_before"=>$num_appointments_total_pending_before,
            "porcentaje_dtpn"=> round($porcentajeDTPN,2),
        ]);
    }

    public function dashboard_doctor_year(Request $request){

        $year = $request->year;
        $doctor_id = $request->doctor_id;

        $query_patients_by_gender = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->where("appointments.doctor_id", $doctor_id)
                        ->join("patients","appointments.patient_id", "=", "patients.id")
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("SUM(CASE WHEN patients.gender = 1 THEN 1 ELSE 0 END) as hombre"),
                            DB::raw("SUM(CASE WHEN patients.gender = 2 THEN 1 ELSE 0 END) as mujer"),
                        )->groupBy("year")
                        ->orderBy("year")
                        ->get();
        
        
        //ingresos generales del año
        $query_income_year = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->where("appointments.doctor_id", $doctor_id)
                        ->where("appointments.status_pay", 1)
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("MONTH(appointments.date_appointment) as month"),
                            DB::raw("SUM(appointments.amount) as income ")
                        )->groupBy("year", "month")
                        ->orderBy("year")
                        ->orderBy("month")
                        ->get();

        $query_n_appointment_year = DB::table("appointments")->where("appointments.deleted_at",NULL)
                                ->whereYear("appointments.date_appointment", $year)
                                ->where("appointments.doctor_id", $doctor_id)
                                ->select(
                                    DB::raw("YEAR(appointments.date_appointment) as year"),
                                    DB::raw("MONTH(appointments.date_appointment) as month"),
                                    DB::raw("COUNT(*) as count_appointments")
                                )->groupBy("year", "month")
                                ->orderBy("year")
                                ->orderBy("month")
                                ->get();

        $query_n_appointment_year_before = DB::table("appointments")->where("appointments.deleted_at",NULL)
                                ->whereYear("appointments.date_appointment", $year - 1)
                                ->where("appointments.doctor_id", $doctor_id)
                                ->select(
                                    DB::raw("YEAR(appointments.date_appointment) as year"),
                                    DB::raw("MONTH(appointments.date_appointment) as month"),
                                    DB::raw("COUNT(*) as count_appointments")
                                )->groupBy("year", "month")
                                ->orderBy("year")
                                ->orderBy("month")
                                ->get();

        $join_n_appointments_years = collect([]);

        $months_name = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

        foreach($query_n_appointment_year->merge($query_n_appointment_year_before)->groupBy("month") as $key => $month_year){
            $join_n_appointments_years->push([
                "month"=>$key,
                "months_name"=>$months_name[$key - 1],
                "details"=>$month_year
            ]);
        } 

        return response()->json([
            "months_name" => $months_name,
            "join_n_appointments_years" => $join_n_appointments_years,
            "query_n_appointment_year" => $query_n_appointment_year,
            "query_n_appointment_year_before" => $query_n_appointment_year_before,
            "query_income_year" => $query_income_year,
            "query_patients_by_gender" => $query_patients_by_gender,
        ]);               
    }

    public function dashboard_patient(Request $request){

        date_default_timezone_set('America/Caracas');

        $patient_id = $request->patient_id;

        //mes actual - appointments
        $now = now();
        $num_appointments_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                            ->whereYear("date_appointment",$now->format("Y"))
                            ->whereMonth("date_appointment",$now->format("m"))
                            ->count();

        //mes anterior - appointments
        $before = now()->subMonth();
        $num_appointments_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                            ->whereYear("date_appointment",$before->format("Y"))
                            ->whereMonth("date_appointment",$before->format("m"))
                            ->count();
        // versus % -appointmens
        $porcentajeD = 0;
        if($num_appointments_before > 0){
            $porcentajeD = (($num_appointments_current - $num_appointments_before) / $num_appointments_before)* 100;
        }



        //mes actual - appointments-attentions
        $now = now();
        $num_appointments_attention_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                            ->whereYear("date_attention",$now->format("Y"))
                            ->whereMonth("date_attention",$now->format("m"))
                            ->count();
        //mes anterior - appointments-attentions
        $before = now()->subMonth();
        $num_appointments_attention_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                            ->whereYear("date_attention",$before->format("Y"))
                            ->whereMonth("date_attention",$before->format("m"))
                            ->count();
        // versus % -appointmens-attentions
        $porcentajeDA = 0;
        if($num_appointments_attention_before > 0){
            $porcentajeDA = (($num_appointments_attention_current - $num_appointments_attention_before) / $num_appointments_attention_before)* 100;
        }

         //mes actual -  appointement pago total $ - (ganancias)
         $now = now();
         $num_appointments_total_pay_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                             ->whereYear("date_appointment",$now->format("Y"))
                             ->whereMonth("date_appointment",$now->format("m"))
                             ->where("status_pay",1)
                             ->sum("appointments.amount");
         //mes anterior -  appointement pago total $ - (ganancias)
         $before = now()->subMonth();
         $num_appointments_total_pay_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                             ->whereYear("date_appointment",$before->format("Y"))
                             ->whereMonth("date_appointment",$before->format("m"))
                             ->where("status_pay",1)
                             ->sum("appointments.amount");
         // versus % - appointement total $ - (ganancias)
         $porcentajeDTP = 0;
         if($num_appointments_total_pay_before > 0){
             $porcentajeDTP = (($num_appointments_total_pay_current - $num_appointments_total_pay_before) / $num_appointments_total_pay_before)* 100;
         }

         //mes actual -  appointement pago pendiente $ - (ganancias)
         $now = now();
         $num_appointments_total_pending_current = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                             ->whereYear("date_appointment",$now->format("Y"))
                             ->whereMonth("date_appointment",$now->format("m"))
                             ->where("status_pay",2)
                             ->sum("appointments.amount");
         //mes anterior -  appointement pago pendiente $ - (ganancias)
         $before = now()->subMonth();
         $num_appointments_total_pending_before = DB::table("appointments")->where("deleted_at", NUll)
                            ->where("patient_id", $patient_id)
                             ->whereYear("date_appointment",$before->format("Y"))
                             ->whereMonth("date_appointment",$before->format("m"))
                             ->where("status_pay",2)
                             ->sum("appointments.amount");
         // versus % - appointement total $ - (ganancias)
         $porcentajeDTPN = 0;
         if($num_appointments_total_pending_before > 0){
             $porcentajeDTPN = (($num_appointments_total_pending_current - $num_appointments_total_pending_before) / $num_appointments_total_pending_before)* 100;
         }

         $appointments = Appointment::whereYear("date_appointment", $now->format("Y"))
                                    ->where("patient_id", $patient_id)
                                    ->whereMonth("date_appointment", $now->format("m"))
                                    ->where("status",1)
                                    ->take(5)
                                    ->orderBy("id", "desc")
                                    ->get();

        return response()->json([
            "appointments"=>AppointmentCollection::make($appointments),
            "num_appointments_current"=>$num_appointments_current,
            "num_appointments_before"=>$num_appointments_before,
            "porcentaje_d"=> round($porcentajeD,2),
            //
            "num_appointments_attention_current"=>$num_appointments_attention_current,
            "num_appointments_attention_before"=>$num_appointments_attention_before,
            "porcentaje_da"=> round($porcentajeDA,2),
             // 
            "num_appointments_total_pay_current"=>$num_appointments_total_pay_current,
            "num_appointments_total_pay_before"=>$num_appointments_total_pay_before,
            "porcentaje_dtp"=> round($porcentajeDTP,2),
            //
            "num_appointments_total_pending_current"=>$num_appointments_total_pending_current,
            "num_appointments_total_pending_before"=>$num_appointments_total_pending_before,
            "porcentaje_dtpn"=> round($porcentajeDTPN,2),
        ]);
    }

    public function dashboard_patient_year(Request $request){

        $year = $request->year;
        $patient_id = $request->patient_id;

        $query_patients_by_gender = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->where("appointments.patient_id", $patient_id)
                        ->join("patients","appointments.patient_id", "=", "patients.id")
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("SUM(CASE WHEN patients.gender = 1 THEN 1 ELSE 0 END) as hombre"),
                            DB::raw("SUM(CASE WHEN patients.gender = 2 THEN 1 ELSE 0 END) as mujer"),
                        )->groupBy("year")
                        ->orderBy("year")
                        ->get();
        
        
        //ingresos generales del año
        $query_income_year = DB::table("appointments")->where("appointments.deleted_at",NULL)
                        ->whereYear("appointments.date_appointment", $year)
                        ->where("appointments.patient_id", $patient_id)
                        ->where("appointments.status_pay", 1)
                        ->select(
                            DB::raw("YEAR(appointments.date_appointment) as year"),
                            DB::raw("MONTH(appointments.date_appointment) as month"),
                            DB::raw("SUM(appointments.amount) as income ")
                        )->groupBy("year", "month")
                        ->orderBy("year")
                        ->orderBy("month")
                        ->get();

        $query_n_appointment_year = DB::table("appointments")->where("appointments.deleted_at",NULL)
                                ->whereYear("appointments.date_appointment", $year)
                                ->where("appointments.patient_id", $patient_id)
                                ->select(
                                    DB::raw("YEAR(appointments.date_appointment) as year"),
                                    DB::raw("MONTH(appointments.date_appointment) as month"),
                                    DB::raw("COUNT(*) as count_appointments")
                                )->groupBy("year", "month")
                                ->orderBy("year")
                                ->orderBy("month")
                                ->get();

        $query_n_appointment_year_before = DB::table("appointments")->where("appointments.deleted_at",NULL)
                                ->whereYear("appointments.date_appointment", $year - 1)
                                ->where("appointments.patient_id", $patient_id)
                                ->select(
                                    DB::raw("YEAR(appointments.date_appointment) as year"),
                                    DB::raw("MONTH(appointments.date_appointment) as month"),
                                    DB::raw("COUNT(*) as count_appointments")
                                )->groupBy("year", "month")
                                ->orderBy("year")
                                ->orderBy("month")
                                ->get();

        $join_n_appointments_years = collect([]);

        $months_name = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

        foreach($query_n_appointment_year->merge($query_n_appointment_year_before)->groupBy("month") as $key => $month_year){
            $join_n_appointments_years->push([
                "month"=>$key,
                "months_name"=>$months_name[$key - 1],
                "details"=>$month_year
            ]);
        } 

        return response()->json([
            "months_name" => $months_name,
            "join_n_appointments_years" => $join_n_appointments_years,
            "query_n_appointment_year" => $query_n_appointment_year,
            "query_n_appointment_year_before" => $query_n_appointment_year_before,
            "query_income_year" => $query_income_year,
            "query_patients_by_gender" => $query_patients_by_gender,
        ]);               
    }

    
}
