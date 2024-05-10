<?php

namespace App\Mail;

use App\Models\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCurrencyRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $currency;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('soporte@ursigalletti.net', 'Sistema Automatizado de Envio de Notificaciones por correo')->subject('Registro de una nueva divisa')
            ->markdown('emails.admin.new_currency_register' , ['currency' => $this->currency]);
    }
}
