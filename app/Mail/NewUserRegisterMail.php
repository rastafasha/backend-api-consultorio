<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $user = $this->user;
        return $this->view('emails.new_user_register',['user' => $this->user]);
        
        // return $this->from('citasmedicas@malcolmcordova.com', 'Sistema Automatizado de Envio de Notificaciones por correo')->subject('Registro de un nuevo usuario')
        //     ->markdown('emails.admin.new_user_register' , ['user' => $this->user]);
    }
}
