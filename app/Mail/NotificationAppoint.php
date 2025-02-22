<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationAppoint extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $patient;
    
    /**
     * Create a new message instance.
     *
     * @param mixed $patient The patient information for the appointment
     */
    public function __construct(mixed $patient = null)
    {
        $this->patient = $patient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope([
            'subject' => 'Recordatorio de Cita Médica',
        ]);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $patient = $this->patient;
        return $this->subject('Notificación de Cita Médica')
        ->view('emails.appointment_notification');
    }
}
