<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\Http;

class NotificationAppointmentDayBefore extends Command
{
    // 🟢 FIRMA ÚNICA PARA CRONJOB
    protected $signature = 'command:notification-day-before';
    protected $description = 'Envía el lote de citas por WhatsApp al microservicio un día antes';

    public function handle()
    {
        date_default_timezone_set('America/Caracas');

        // 🚀 LA CLAVE: Buscamos únicamente las citas programadas para el día de MAÑANA
        $manana = Carbon::tomorrow('America/Caracas')->format("Y-m-d");

        $appointments = Appointment::whereDate("date_appointment", $manana)
            ->where("status", 1) // Citas activas/pendientes
            ->get();

        $whatsappQueue = collect([]);

        foreach ($appointments as $appointment) {
            
            // Extraemos las horas formateadas del bloque de la cita
            $hour_start_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hour_end_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;
            
            $hour_start_format = Carbon::parse($manana . " " . $hour_start_raw)->format("h:i A");
            $hour_end_format = Carbon::parse($manana . " " . $hour_end_raw)->format("h:i A");
            $doctor_full_name = $appointment->doctor->name . ' ' . $appointment->doctor->surname;

            // 🟢 TEXTO ADAPTADO: Le avisamos que su cita es MAÑANA
            $whatsappQueue->push([
                'doctor_id' => (string) $appointment->doctor_id,
                'telefono'  => $appointment->patient->phone,
                'mensaje'   => "Hola *{$appointment->patient->name} {$appointment->patient->surname}*, le recordamos que tiene una cita médica de *{$appointment->speciality->name}* programada para *MAÑANA*. Horario: {$hour_start_format} hasta {$hour_end_format}. Profesional: {$doctor_full_name}. Por favor, confirme su asistencia."
            ]);
            
            // 🔥 OJO: Aquí NO actualizamos 'cron_state = 2', porque si lo haces, 
            // el comando de "una hora antes" mañana no la va a procesar. 
            // Dejamos que el flujo de estados siga su curso normal.
        }

        // DISPARO EN LOTE SEGURO A TU MICROSERVICIO
        if ($whatsappQueue->count() > 0) {
            try {
                $baseNodeUrl = rtrim(env('KLYNTIC_NODE_URL', 'https://onrender.com'), '/');
                $urlNodeBulk = $baseNodeUrl . '/api/klyntic/notificaciones/bulk';
                $tokenSecreto = env('CRM_INTERNAL_TOKEN');

                $response = Http::withHeaders([
                    'Authorization' => $tokenSecreto,
                    'Accept'        => 'application/json'
                ])->post($urlNodeBulk, [
                    'recordatorios' => $whatsappQueue->toArray()
                ]);

                if ($response->successful()) {
                    $this->info('✅ Lote de ' . $whatsappQueue->count() . ' recordatorios preventivos enviados a Node.');
                } else {
                    $this->error('Node rechazó el lote preventivo.');
                }
            } catch (\Exception $e) {
                $this->error('Error conectando con el microservicio: ' . $e->getMessage());
            }
        } else {
            $this->info('No hay citas programadas para el día de mañana.');
        }

        return 0;
    }
}