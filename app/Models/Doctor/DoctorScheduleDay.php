<?php

namespace App\Models\Doctor;

use App\Models\Doctor\DoctorAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorScheduleDay extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable =[
        "user_id",
        "doctor_address_id",
        "day",
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

    public function schedule_hours(){
        return $this->hasMany(DoctorScheduleJoinHour::class);
    }
    public function doctor(){
        return $this->belongsTo(User::class, "user_id");
    }

   public function user() // Cambiaste de 'doctor' a 'user'
{
    return $this->belongsTo(User::class, "user_id");
}

// Relación inversa: El día de la agenda pertenece a un consultorio específico
public function doctor_address(){
    return $this->belongsTo(DoctorAddress::class, "doctor_address_id");
}
}
