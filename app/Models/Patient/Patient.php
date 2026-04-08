<?php

namespace App\Models\Patient;

use App\Models\Appointment\Appointment;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'n_doc',
        'birth_date',
        'gender',
        'education',
        'address',
        'avatar',
        'antecedent_family',
        'antecedent_personal',
        'antecedent_alerg',
        'ta',
        'temperature',
        'fc',
        'fr',
        'peso',
        'current_desease',
        'location_id',
        'user_id',
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Caracas");
        $this->attributes['created_at'] = Carbon::now();
    }

    public function setUpdateAttribute($value)
    {
        date_default_timezone_set("America/Caracas");
        $this->attributes['updated_at'] = Carbon::now();
    }

    public function person()
    {
        return $this->hasOne(PatientPerson::class, 'patient_id');
    }

    public function location()
    {
        return $this->hasMany(Location::class);
    }
    public function doctors()
    {
        // Relación muchos a muchos con los médicos
        return $this->belongsToMany(User::class, 'doctor_patient', 'patient_id', 'doctor_id')
            ->withTimestamps();
    }
    public function account()
    {
        // Esta se queda igual: es su cuenta de acceso (User ID 12)
        return $this->belongsTo(User::class, 'user_id');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }
}
