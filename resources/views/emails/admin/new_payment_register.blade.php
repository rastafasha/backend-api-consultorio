@component('mail::message')
# Hola
<br>
Se has registrado un nuevo pago
<br><br>

* Tipo de Pago ***{{ $payment->metodo}}***
<br>
* Método de pago ***{{ $payment->referencia}}***
<br>
* Nombre del banco ***{{ $payment->bank_name}}***
<br>
* Monto ***{{ $payment->monto}}***
<br>
* Paciente ***{{ $payment->nombre}}***
<br>
* Email ***{{ $payment->email}}***

<br><br>
@component('mail::button', [
    'url' => env('APP_URL')
])
    Ir a la web
@endcomponent

Notificaciones automatizadas desde la app
***{{ config('app.name') }}***
@endcomponent
