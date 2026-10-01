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
        // Forzamos la hora de Caracas para calcular la ventana de tiempo del recordatorio
        $ahora = Carbon::now('America/Caracas')->setTimezone('UTC');
        $enUnaHora = Carbon::now('America/Caracas')->addHour()->setTimezone('UTC');

        // 1. Buscamos las citas en la ventana de una hora (Eager loading para evitar saturar MySQL)
        // 🟢 NOTA: Asegúrate de usar las columnas reales de tu base de datos (fecha_hora o date_appointment)
        $citas = Appointment::with(['patient', 'doctor'])
                     ->whereBetween('date_appointment', [$ahora, $enUnaHora]) // Ajustado a la columna de tu AppointmentController
                     ->where('cron_state', 1) // Usamos tu switch de estado '1' para pendientes
                     ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas pendientes por notificar en la próxima hora.');
            return 0;
        }

        // Jalamos la configuración de tus tokens y URLs unificadas del .env de Laravel
        $baseNodeUrl = rtrim(env('KLYNTIC_NODE_URL', 'https://back-klyntic-envios.onrender.com'), '/');
        $urlNodeWebhook = $baseNodeUrl . '/api/klyntic/notificaciones/webhook-recordatorio';
        $tokenSecreto = env('CRM_INTERNAL_TOKEN');

        foreach ($citas as $cita) {
            $paciente = $cita->patient; 
            $doctor = $cita->doctor; 

            if (!$paciente || !$doctor) {
                continue;
            }

            // Convertimos el string de la fecha al huso horario de Caracas para el texto del WhatsApp
            $horaCita = Carbon::parse($cita->date_appointment)->timezone('America/Caracas');
            
            // Determinamos el ID del consultorio o clínica para encolar el WhatsApp corporativo
            $ownerTenantId = !empty($cita->clinica_id) ? $cita->clinica_id : 1;

            // 🚀 2. MAPEO EN ESPEJO PERFECTO: Mandamos las propiedades exactas que tu Node espera recibir
            $response = Http::withHeaders([
                'Authorization' => $tokenSecreto, // 🟢 Sincronizado con tu middleware de anoche (validarWebhookLaravel)
                'Accept'        => 'application/json'
            ])->post($urlNodeWebhook, [
                'consultorio_id'  => $ownerTenantId,
                'telefono'        => $paciente->phone,
                'mensaje'         => "Hola {$paciente->name} {$paciente->surname}, le recordamos su cita médica hoy a las {$horaCita->format('h:i A')}.",
                'usuario'         => (string) $paciente->id, // El paciente recibe el ID de MySQL en string para encender su campana
                'rolDestinatario' => 'PACIENTE',
                'titulo'          => '⏰ Recordatorio de Cita',
                'tipo'            => 'RECORDATORIO',
                'referenciaId'    => (string) $cita->id
            ]);

            // Marcamos como notificado (cron_state = 2) solo si el microservicio de Node aceptó el paquete
            if ($response->successful()) {
                $cita->update(['cron_state' => 2]); // Cambiado a cron_state para hacer juego con tu controlador
                $this->info("✅ Recordatorio encolado en Node para el paciente: {$paciente->name}");
            } else {
                $this->error("❌ Error de comunicación con Node (Estatus " . $response->status() . ") para la cita ID: {$cita->id}");
            }
        }

        return 0;
    }
}
