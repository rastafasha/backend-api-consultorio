<?php

namespace App\Models;

use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Odontograma extends Model
{
    use SoftDeletes;

    protected $table = 'odontogramas';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'diente_numero',
        'cara_diente',
        'hallazgo',
        'notas'
    ];

    // Relación: Un registro del odontograma pertenece a un paciente
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    // Relación: Un registro fue hecho por un doctor (user)
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
