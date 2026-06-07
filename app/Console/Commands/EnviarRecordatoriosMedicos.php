<?php

namespace App\Console\Commands;

use App\Models\Appointment\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnviarRecordatoriosMedicos extends Command
{
    // Agregamos la firma obligatoria para poder ejecutarlo en consola
    protected $signature = 'medicos:enviar-recordatorios';
    protected $description = 'Envía alertas de WhatsApp a través del microservicio Node.js';

    public function handle() 
    {
        // Forzamos la hora de Caracas para la ventana de tiempo del recordatorio
        $ahora = Carbon::now('America/Caracas')->setTimezone('UTC');
        $enUnaHora = Carbon::now('America/Caracas')->addHour()->setTimezone('UTC');

        // 1. Buscamos las citas en la ventana de tiempo
        // Usamos eager loading (with) para Patient y Doctor (User) para no saturar MySQL
        $citas = Appointment::with(['patient', 'doctor'])
                     ->whereBetween('fecha_hora', [$ahora, $enUnaHora])
                     ->where('notificado', false)
                     ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas pendientes por notificar en la próxima hora.');
            return 0;
        }

        foreach ($citas as $cita) {
            $paciente = $cita->patient; 
            $doctor = $cita->doctor; 

            // Validamos que la cita tenga un paciente y doctor asignado para evitar caídas
            if (!$paciente || !$doctor) {
                continue;
            }

            // Convertimos el string de la cita a un objeto Carbon manejable
            $horaCita = Carbon::parse($cita->fecha_hora)->timezone('America/Caracas');

            // 2. Disparamos el payload exacto a tu microservicio Node
            // Nota: Recuerda cambiar 'https://render.com' por tu URL de endpoint real (ej: https://tudominio.com)
            $response = Http::post('https://back-klyntic-envios.onrender.com', [
                'doctor_id' => (string) $doctor->id, // Lo pasamos como string para enlazar con klyntic_consultorios (_id)
                'telefono'  => $paciente->phone,     // CAMBIO: Campo real en tu modelo Patient
                'mensaje'   => "Hola {$paciente->name} {$paciente->surname}, te recordamos tu cita médica hoy a las {$horaCita->format('h:i A')}."
            ]);

            // Marcamos como notificado solo si la petición HTTP al microservicio fue exitosa (código 200)
            if ($response->successful()) {
                $cita->update(['notificado' => true]);
                $this->info("Recordatorio enviado con éxito para el paciente: {$paciente->name}");
            } else {
                $this->error("Error al enviar recordatorio a Node para la cita ID: {$cita->id}");
            }
        }

        return 0;
    }
}
