<?php

namespace App\Jobs;


use App\Mail\NewAppointmentRegisterMail;
use App\Mail\NewPaymentRegisterMail;
use App\Mail\RegisterAppointment;
use App\Models\Appointment\Appointment;
use App\Models\Patient\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewAppointmentRegisterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Patient
     */
    // public Payment $payment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
     // Cambiamos la propiedad para que guarde la cita completa
    public $appointment;

    /**
     * Create a new job instance.
     *
     * @param Appointment $appointment
     * @return void
     */
    public function __construct(Appointment $appointment)
    {
        // Guardamos la instancia de la cita recibida
        $this->appointment = $appointment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
{
    try {
        // El comando 'send:notification' ejecutará esto de forma silenciosa cada minuto
        Mail::to($this->appointment->patient->email)->send(new RegisterAppointment($this->appointment));
        Mail::to($this->appointment->doctor->email)->send(new NewAppointmentRegisterMail($this->appointment));
    } catch (\Exception $exception) {
        Log::error('Fallo al enviar correos de la cita desde la cola: ' . $exception->getMessage());
    }
}
}
