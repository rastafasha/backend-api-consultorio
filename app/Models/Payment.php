<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Payment;
use App\Jobs\PaymentRegisterJob;
use App\Mail\NewPaymentRegisterMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Appointment\Appointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | goblan variables
    |--------------------------------------------------------------------------
    */
    protected $fillable = [

        'referencia',
        'metodo',
        'bank_name',
        'monto',
        'nombre',
        'email',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'image',
        'fecha',
        'status'
    ];

    const APPROVED = 'APPROVED';
    const PENDING = 'PENDING';
    const REJECTED = 'REJECTED';

    /*
    |--------------------------------------------------------------------------
    | functions
    |--------------------------------------------------------------------------
    */

    protected static function boot(){

        parent::boot();

        static::created(function($payment){

            // PaymentRegisterJob::dispatch(
            //     $user
            // )->onQueue("high");

        Mail::to('mercadocreativo@gmail.com')->send(new NewPaymentRegisterMail($payment));

        });


    }

    public static function statusTypes()
    {
        return [
            self::APPROVED, self::PENDING, self::REJECTED
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function users()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */


    public function scopefilterAdvancePayment($query,
    // $metodo, 
    $search_referencia, 
    // $bank_name, 
    // $nombre, 
    // $monto,
    // $fecha,
    ){
        
        // if($metodo){
        //     $query->where("metodo", $metodo);
        // }
        if($search_referencia){
            $query->where("referencia", $search_referencia);
        }
        // if($bank_name){
        //     $query->where("bank_name", $bank_name);
        // }
        // if($nombre){
        //     $query->where("nombre", $nombre);
        // }
        // if($monto){
        //     $query->where("monto", $monto);
        // }
        // if($fecha){
        //     $query->whereDate("fecha", Carbon::parse($fecha)->format("Y-m-d"));
        // }
        return $query;
    }
}
