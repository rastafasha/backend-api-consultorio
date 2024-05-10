<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewAppointmentRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('citasmedicas@malcolmcordova.com', 'Registro de una nueva cita desde Health Connect')
            ->subject('Registro de una nueva cita')
            ->markdown('emails.admin.new_appointment_register' , ['appointment' => $this->appointment]);
    }
}
