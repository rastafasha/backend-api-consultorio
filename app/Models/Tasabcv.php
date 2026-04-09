<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasabcv extends Model
{
    use HasFactory;
    
    protected $table = 'tasabcv';
    
     protected $fillable = [
        'precio_dia',
    ];
}
