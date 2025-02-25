<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PresupuestoItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable=[
        "presupuesto_id",
        "name",
        "cantidad",
        "precio",

    ];
    
    

    // relaciones

    public function presupuestos() {
        return $this->belongsTo(Presupuesto::class,"presupuesto_id");
    }

    // relaciones

    
}
