<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RLaboratory extends Model
{
     use HasFactory;

    protected $fillable =[
        'patient_id',
        'comentario',
        'name_file',
        'size',
        'time',
        'resolution',
        'file',
        'type',

    ];

    public function getSizeAttribute($size)
    {
        $size = (int) $size;
        $base = log($size) / log(1024);
        $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
        return round(pow(1024, $base - floor($base)), 2) . $suffixes[floor($base)];
    }
}
