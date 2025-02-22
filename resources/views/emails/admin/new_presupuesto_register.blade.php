@component('mail::message')
# Hola
<br>
Presupuesto Creado
<br><br>

* Nombre del Doctor ***{{ $presupuesto->doctor->name}}***
* Email del Doctor ***{{ $presupuesto->doctor->email}}***
* Teléfono del Doctor ***{{ $presupuesto->doctor->mobile}}***
* Nombre del Paciente ***{{ $presupuesto->patient->name}}***
* Hora del registro ***{{ $presupuesto->created_at}}***
<br>
* Descripcion ***{{ $presupuesto->description}}***
* Especialidad ***{{ $presupuesto->speciality->name}}***
* Total ***{{ $presupuesto->amount}}***

<br><br>
@component('mail::button', [
    'url' => env('APP_URL')
])
    Ir a la web
@endcomponent

Notificaciones automatizadas desde la app
***{{ config('app.name') }}***
@endcomponent
