<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Payment;
use App\Models\Location;
use App\Traits\HavePermission;
use App\Models\Patient\Patient;
use App\Jobs\NewUserRegisterJob;
use App\Mail\NewUserRegisterMail;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Doctor\Specialitie;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Doctor\DoctorScheduleDay;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HavePermission,  HasRoles;
    use SoftDeletes;
    /*
    |--------------------------------------------------------------------------
    | goblan variables
    |--------------------------------------------------------------------------
    */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'n_doc',
        //
        'surname',
        'mobile',
        'birth_date',
        'gender',
        'education',
        'designation',
        'address',
        'avatar',
        'speciality_id',
        'location_id',
        'precio_cita',
        'status',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    const SUPERADMIN = 'SUPERADMIN';
    const GUEST = 'GUEST';

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


    /*
    |--------------------------------------------------------------------------
    | functions
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin()
    {
        return $this->role === User::SUPERADMIN;
    }

    public function isGuest()
    {
        return $this->role === User::GUEST;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */



    public function speciality()
    {
        return $this->belongsTo(Specialitie::class);
    }

    public function schedule_days()
    {
        return $this->hasMany(DoctorScheduleDay::class);
    }
    public function locations()
    {
        return $this->hasMany(Location::class, 'location_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    //envia los pagos a un solo correo
    protected static function boot(){

        parent::boot();

        static::created(function($user){

            // userRegisterJob::dispatch(
            //     $user
            // )->onQueue("high");

        Mail::to('mercadocreativo@gmail.com')->send(new NewUserRegisterMail($user));

        });


    }

    
    


}
