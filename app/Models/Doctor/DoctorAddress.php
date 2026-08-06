<?php
namespace App\Models\Doctor;

use App\Models\Doctor\DoctorScheduleDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorAddress extends Model 
{ 
    use SoftDeletes; 

    protected $fillable = [
        'user_id', 
        'name_consultorio', 
        'address', 
        'is_active'
    ]; 

    // AGREGA ESTO: Fuerza a Laravel a enviar true/false a PostgreSQL
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user() 
    { 
        return $this->belongsTo(User::class, 'user_id'); 
    } 

    // Relación: Un consultorio específico tiene muchos días de horario programados 
    public function schedule_days() 
    { 
        return $this->hasMany(DoctorScheduleDay::class, 'doctor_address_id'); 
    } 
}


