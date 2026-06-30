<?php
namespace App\Models\Doctor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorAddress extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name_consultorio', 'address', 'is_active'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación: Un consultorio específico tiene muchos días de horario programados
    public function schedule_days() {
        return $this->hasMany(DoctorScheduleDay::class, 'doctor_address_id');
    }
}

