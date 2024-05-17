<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Patient\Patient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewPatientRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $patient;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $patient = $this->patient;
        return $this->view('emails.new_patient_register',['patient' => $this->patient]);
        
        // return $this->from('citasmedicas@malcolmcordova.com', 'Sistema Automatizado de Envio de Notificaciones por correo')->subject('Registro de un nuevo usuario')
        //     ->markdown('emails.admin.new_user_register' , ['user' => $this->user]);
    }
}
