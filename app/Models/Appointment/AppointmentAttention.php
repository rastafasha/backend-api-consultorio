<?php

namespace App\Models\Appointment;

use Carbon\Carbon;
use App\Models\Laboratory\Laboratory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentAttention extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable=[
        "appointment_id",
        "patient_id",
        "description",
        "receta_medica",
        "laboratory",

    ];
    public function setCreatedAtAttribute($value)
    {
    	date_default_timezone_set('America/Caracas');
        $this->attributes["created_at"]= Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Caracas");
        $this->attributes["updated_at"]= Carbon::now();
    }

    public function scopefilterAdvanceAttention($query,$speciality_id, $name_doctor, $date){
        
        if($speciality_id){
            $query->where("speciality_id", $speciality_id);
        }

        if($name_doctor){
            $query->whereHas("doctor", function($q)use($name_doctor){
                $q->where("name", "like","%".$name_doctor."%")
                    ->orWhere("surname", "like","%".$name_doctor."%");
            });
        }

        if($date){
            $query->whereDate("date_appointment", Carbon::parse($date)->format("Y-m-d"));
        }
        return $query;
    }

    public function files(){
        return $this->hasMany(Laboratory::class, "appointment_id");
    }
}
