<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPerson extends Model
{
    use HasFactory;
    protected $fillable=[
        'patient_id',
        'name_companion',
        'surname_companion',
        'mobile_companion',
        'relationship_companion',
        'name_responsable',
        'surname_responsable',
        'mobile_responsable',
        'relationship_responsable',
    ];

    protected $table = "patien_persons";

    public function setCreateAttribute($value){
        date_default_timezone_set("America/Caracas"); 
        $this->attribute['created_at']= Carbon::now();
    }

    public function setUpdateAttribute($value){
        date_default_timezone_set("America/Caracas"); 
        $this->attribute['updated_at']= Carbon::now();
    }


}
