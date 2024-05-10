<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tiposdepago extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | goblan variables
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name', 
        'tipo',
        'bankAccount',
        'bankName',
        'email',
        'user',
        'ciorif',
        'telefono',
        'status',
    ];

    const ACTIVE = 'ACTIVE';
    const INACTIVE = 'INACTIVE';

    public static function statusTypes()
    {
        return [
            self::ACTIVE, self::INACTIVE
        ];
    }

}
