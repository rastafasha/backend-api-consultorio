<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clinicas';

    protected $fillable = [
        'nombre',
        'subdominio',
        'tipoClinica',
        'direccion_unica',
        'banner_url',
        'logo_url',
        'status'
    ];

    /**
     * RELACIÓN: Médicos asociados a la clínica (Muchos a Muchos)
     */
    public function medicos()
    {
        return $this->belongsToMany(User::class, 'clinica_medico', 'clinica_id', 'medico_id')
                    ->withTimestamps();
    }

    /**
     * RELACIÓN: Personal administrativo/secretarias de la clínica (Uno a Muchos)
     */
    public function personal()
    {
        return $this->hasMany(User::class, 'clinica_id');
    }
}
