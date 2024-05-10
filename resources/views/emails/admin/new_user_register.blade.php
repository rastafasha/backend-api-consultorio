@component('mail::message')
# Hola
<br>
Se has registrado un nuevo usuario
<br><br>

* Nombre del usuario ***{{ $user->username}}***
<br>
* Email del usuario ***{{ $user->email}}***
<br>
* Hora del registro ***{{ $user->created_at}}***

<br><br>
@component('mail::button', [
    'url' => env('APP_URL')
])
    Ir a la web
@endcomponent

Notificaciones automatizadas desde la app
***{{ config('app.name') }}***
@endcomponent
