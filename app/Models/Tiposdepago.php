<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tiposdepago extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | global variables
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
        'doctor_id',
    ];

    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';

    public static function statusTypes()
    {
        return [
            self::ACTIVE, self::INACTIVE
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
