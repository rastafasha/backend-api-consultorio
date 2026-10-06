<?php

namespace App\Models;

use App\Models\Clinica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settingeneral extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'address', 
        'phone', 
        'city',
        'state',
        'zip',
        'country',
        'moneda',
        'avatar',
        'clinica_id',
    ];

    // 🟢 Relación inversa con Clínica
    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }
}
