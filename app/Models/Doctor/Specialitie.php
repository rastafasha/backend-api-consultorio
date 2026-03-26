<?php

namespace App\Models\Doctor;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Specialitie extends Model
{
    use HasFactory;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'state',
        'price',
    ];

    public function setCreatedAtAttribute($value)
    {
    	date_default_timezone_set('America/Caracas');
        $this->attributes["created_at"]= Carbon::now();
}

    public function setUpdatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Caracas");
        $this->attributes["updated_at"]= Carbon::now();
    }

    /**
     * Doctors associated with this speciality.
     */
    public function doctors()
    {
        return $this->hasMany(User::class, 'speciality_id');
    }

    /**
     * Active doctors with 'doctor' role for this speciality.
     */
    public function activeDoctors()
    {
        return $this->hasMany(User::class, 'speciality_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->where('status', 2); // assuming status=1 means active
    }
}
