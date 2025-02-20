<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment\Appointment;

class CancellationAppointmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $reason;

    public function __construct(Appointment $appointment, $reason = null)
    {
        $this->appointment = $appointment;
        $this->reason = $reason;
    }

    public function build()
    {
        $appointment = $this->appointment;
        return $this->subject('Appointment Cancellation Notification')
                   ->view('emails.cancellation_appointment');
    }
}
