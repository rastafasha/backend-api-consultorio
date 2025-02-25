<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelación de Cita</title>
</head>
<body>
    <h2>Notificación de Cancelación de Cita.</h2>
    
    <p>Estimado: {{ $appointment->patient->name }},</p>
    
    <p>
        Lamentamos informarle que su cita ha sido cancelada. A continuación se detallan los datos:</p>
    
    <ul>
        <li><strong>Doctor:</strong> {{ $appointment->doctor->name }} {{ $appointment->doctor->surname }}</li>
        <li><strong>Especialidad:</strong> {{ $appointment->speciality->name }}</li>
        <li><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($appointment->date_appointment)->format('d-m-Y H:i') }}</li>
        @if($reason)
        <li><strong>Razón:</strong> {{ $reason }}</li>
        @endif
    </ul>
    
    <p>
        Si tiene alguna pregunta o desea reprogramar, comuníquese con nosotros al:</p>
    {{-- <p>Phone: +1 234 567 890</p> --}}
    <p>Intente nuevamente mas tarde.</p>
    <p>Visite nuestas redes sociales y le atenderemos</p>
    {{-- <p>Email: support@healthconnect.com</p> --}}
    
    <p>
        Lamentamos cualquier inconveniente que esto pueda haber causado.</p>
    
    <p>
        Saludos cordiales,</p>
    <p>El Equipo de Health Connect </p>
    <p>Algún motivo directo con la clinica o consultorio, healthconnect no se hace responsable, 
        por favor comunicarse directamente con el consultorio o clinica.
    </p>
    <p> La App Health Connect solo es un medio de comunicación entre el paciente y el doctor,
        no somos entidades de salud, no somos entidad financiera.
    </p>
    <p>Gracias por su comprensión.</p>
    <table border="0" cellspacing="0" cellpadding="0">
        <tr>

            <td class="img" width="117"
                style="font-size:0pt; line-height:0pt; text-align:center; ">
                <a href="https://health-connect.me/"
                    target="_blank"><img
                        src="https://health-connect.me/varios/logoHealthConnect-01.png"
                        width="75" height="50"
                        editable="true" border="0"
                        alt="" /></a>

            </td>
        </tr>
    </table>
</body>
</html>
