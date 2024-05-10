<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [

        'name','phone', 'email', 'subject', 'comment',
    ];

    // const PENDING = 'PENDING';
    // const RESOLVED = 'RESOLVED';

    /*
    |--------------------------------------------------------------------------
    | functions
    |--------------------------------------------------------------------------
    */

    // public static function statusTypes()
    // {
    //     return [
    //         self::PENDING, self::RESOLVED
    //     ];
    // }
}
