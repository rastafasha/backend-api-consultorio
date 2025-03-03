<?php

namespace App\Models\Patient;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'doctor_id',
    ];

    public function setCreateAttribute($value)
    {
        date_default_timezone_set("America/Caracas");
        $this->attribute['created_at'] = Carbon::now();
    }

    public function setUpdateAttribute($value)
    {
        date_default_timezone_set("America/Caracas");
        $this->attribute['updated_at'] = Carbon::now();
    }

    public function person()
    {
        return $this->hasOne(PatientPerson::class, 'patient_id');
    }

    public function location()
    {
        return $this->hasMany(Location::class);
    }
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
