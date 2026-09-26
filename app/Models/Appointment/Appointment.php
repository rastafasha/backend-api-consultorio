<?php

namespace App\Models\Appointment;

// use App\Jobs\AppointmentRegisterJob;
// use App\Mail\NewAppointmentRegisterMail;
use App\Models\Appointment\AppointmentAttention;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Models\Doctor\Specialitie;
use App\Models\Patient\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Traits\TenantScoped; // 👈 1. IMPORTAMOS EL TRAIT DEL ENTORNO EMPRESA

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped; // 👈 2. ACTIVAMOS EL AISLAMIENTO DE DATOS AUTOMÁTICO

    protected $fillable = [
        "clinica_id", // 👈 3. AGREGADO AL FILLABLE PARA EL CONTROL DE SUBDOMINIOS
        "doctor_id",
        "patient_id",
        "user_id",
        'doctor_schedule_join_hour_id',
        "date_appointment",
        "speciality_id",
        "precio_cita",
        "status_pay",
        "deuda",
        "status",
        "laboratory",
        "date_attention",
        "cron_state",
        "confimation",
        "amount",
    ];

    public $incrementing = true;
    protected $keyType = 'int';


    // relaciones

    public function doctor()
    {
        return $this->belongsTo(User::class, "doctor_id");
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function doctor_schedule_join_hour()
    {
        return $this->belongsTo(DoctorScheduleJoinHour::class, 'doctor_schedule_join_hour_id');
    }

    public function payments()
    {
        return $this->hasMany(AppointmentPay::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Specialitie::class);
    }

    public function attention()
    {
        return $this->hasOne(AppointmentAttention::class);
    }

    public function tranferencias()
    {
        return $this->hasMany(Payment::class);
    }

    // filtro buscador
// =====================================================================
    // 🛡️ BLOQUE SANEADO UNIVERSAL: COMPATIBLE CON MAMP (MYSQL) Y SUPABASE (POSTGRESQL)
    // =====================================================================

   // =====================================================================
    // 🚀 FILTRO MAESTRO DE CITAS ADMINISTRATIVAS - REQUERIMIENTO A SANEADO
    // =====================================================================

    public function scopefilterAdvance($query, $speciality_id, $search, $date)
    {
        // 🩺 Validamos basándonos en la especialidad real indexada en el perfil del Doctor
        if (!empty($speciality_id) && (int)$speciality_id > 0) {
            $query->whereHas("doctor", function($q) use($speciality_id) {
                $q->where("speciality_id", (int)$speciality_id);
            });
        }

        if ($date) {
            $query->whereDate("date_appointment", Carbon::parse($date)->format("Y-m-d"));
        }

        if ($search) {
            // Buscador universal inteligente (Doctor o Paciente)
            $query->where(function($mainQuery) use ($search) {
                
                $mainQuery->whereHas("doctor", function ($q) use ($search) {
                    $q->where(DB::raw("CONCAT_WS(' ', name, surname)"), "like", "%" . $search . "%");
                })
                
                ->orWhereHas("patient", function ($q) use ($search) {
                    $q->where(DB::raw("CONCAT_WS(' ', name, surname)"), "like", "%" . $search . "%");
                });
                
            });
        }

        return $query;
    }

    public function scopefilterAdvanceDoctor($query, $date)
    {
        if ($date) {
            $query->whereDate("date_appointment", Carbon::parse($date)->format("Y-m-d"));
        }
        return $query;
    }

    public function scopefilterAdvancePay(
        $query,
        $speciality_id,
        $search_doctor,
        $search_patient,
        $date_start,
        $date_end
    ) {
        // 🚀 SANEADO ENTERPRISE: Si se selecciona una especialidad específica, filtramos por el perfil real del doctor
        if (!empty($speciality_id) && (int)$speciality_id > 0) {
            $query->whereHas("doctor", function($q) use($speciality_id) {
                $q->where("speciality_id", (int)$speciality_id);
            });
        }

        if ($search_doctor) {
            $query->whereHas("doctor", function ($q) use ($search_doctor) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_doctor . "%");
            });
        }
        
        if ($search_patient) {
            $query->whereHas("patient", function ($q) use ($search_patient) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_patient . "%");
            });
        }

        if ($date_start && $date_end) {
            $query->whereBetween("date_appointment", [
                Carbon::parse($date_start)->format("Y-m-d"),
                Carbon::parse($date_end)->format("Y-m-d"),
            ]);
        }
        return $query;
    }

    public function scopefilterAdvanceDoctorPay(
        $query,
        $search_doctor,
        $search_patient,
        $date_start,
        $date_end
    ) {
        if ($search_doctor) {
            $query->whereHas("doctor", function ($q) use ($search_doctor) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_doctor . "%");
            });
        }
        
        if ($search_patient) {
            $query->whereHas("patient", function ($q) use ($search_patient) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_patient . "%");
            });
        }

        if ($date_start && $date_end) {
            $query->whereBetween("date_appointment", [
                Carbon::parse($date_start)->format("Y-m-d"),
                Carbon::parse($date_end)->format("Y-m-d"),
            ]);
        }
        return $query;
    }

   public function scopefilterAdvanceDoc($query, $search_doctor, $search_patient, $date, $search)
    {
        if ($search_doctor) {
            $query->whereHas("doctor", function ($q) use ($search_doctor) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_doctor . "%");
            });
        }

        if ($search_patient) {
            $query->whereHas("patient", function ($q) use ($search_patient) {
                $q->where(DB::raw("CONCAT_WS(' ', name, surname, email)"), "like", "%" . $search_patient . "%");
            });
        }
        
        if ($date) {
            $query->whereDate("date_appointment", Carbon::parse($date)->format("Y-m-d"));
        }
        return $query;
    }

    // 👈 4. ASÍ SE COMPORTA EL BOOT DE LARAVEL 8 CON EL TRAIT DE FORMA SEGURA:
    protected static function boot()
    {
        parent::boot();

        // Laravel 8 ejecutará de forma nativa bootTenantScoped() gracias a las convenciones de Traits.
        // Si necesitas volver a activar los correos, descoméntalo aquí de manera segura:
        // static::created(function($appointment){
        //     Mail::to('mercadocreativo@gmail.com')->send(new NewAppointmentRegisterMail($appointment));
        // });
    }
}