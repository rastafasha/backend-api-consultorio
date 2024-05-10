<?php

namespace App\Models;

use App\Models\User;
use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable=[
        'title',
        'address',
        'phone1',
        'phone2',
        'city',
        'state',
        'zip',
        'email',
        'avatar',
    ];

    //relations
    public function client()
    {
        return $this->hasMany(Patient::class, 'client_id');
    }

    public function doctor() {
        return $this->hasMany(User::class,"doctor_id");
    }

    //filtro buscador
    public function scopefilterAdvanceLocation(
        $query,
        $client_id, $name_client, $email_client,
        $doctor_id, $name_doctor, $email_doctor,
        ){
        
        if($client_id){
            $query->where("client_id", $client_id);
        }
        
        if($name_client){
            $query->whereHas("patient", function($q)use($name_client){
                $q->where(DB::raw("CONCAT(patients.first_name,' ',IFNULL(patients.last_name,''),' ',IFNULL(patients.email,''))"),"like","%".$name_patient."%");
                
            });
        }
        if($email_client){
            $query->whereHas("patient", function($q)use($email_client){
                $query->where("patientID", $patientID);
                   
            });
        }
        if($doctor_id){
            $query->where("doctor_id", $doctor_id);
        }
        
        if($name_doctor){
            $query->whereHas("doctor", function($q)use($name_doctor){
                $q->where(DB::raw("CONCAT(doctors.first_name,' ',IFNULL(doctors.last_name,''),' ',IFNULL(doctors.email,''))"),"like","%".$name_doctor."%");
                
            });
        }
        if($email_doctor){
            $query->whereHas("doctor", function($q)use($email_doctor){
                $query->where("doctor_id", $doctor_id);
                   
            });
        }
        

        // if($date_start && $date_end){
        //     $query->whereBetween("date_appointment", [
        //         Carbon::parse($date_start)->format("Y-m-d"),
        //         Carbon::parse($date_end)->format("Y-m-d"),
        //     ]);
        // }
        return $query;
    }

}
