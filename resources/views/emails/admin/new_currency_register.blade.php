@component('mail::message')
# Hola
<br>
Se has registrado un nuevo usuario
<br><br>

* Nombre del usuario ***{{ $currency->username}}***
<br>
* Hora del registro ***{{ $currency->created_at}}***

<br><br>
@component('mail::button', [
    'url' => env('APP_URL')
])
    Ir a la web
@endcomponent

Notificaciones automatizadas desde la app
***{{ config('app.name') }}***
@endcomponent
