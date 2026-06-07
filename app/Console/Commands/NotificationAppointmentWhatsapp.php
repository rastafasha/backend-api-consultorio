<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\Http; // Obligatorio para el disparo a Node

class NotificationAppointmentWhatsapp extends Command
{
    protected $signature = 'command:notification-appointment-whatsapp';
    protected $description = 'Envía el lote de citas por WhatsApp al microservicio Node.js';

    public function handle()
    {
        date_default_timezone_set('America/Caracas');

        // 1. Volvemos a la fecha REAL del servidor (eliminamos la simulación de 2023)
        $appointments = Appointment::whereDate("date_appointment", now()->format("Y-m-d"))
            ->where("status", 1)
            ->where("cron_state", 1) // Procesamos solo los pendientes
            ->get();

        $now_time_number = strtotime(now()->format("Y-m-d H:i:s"));
        $whatsappQueue = collect([]); // Lote masivo

        foreach ($appointments as $key => $appointment) {

            $hour_start_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hour_end_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;

            // Calculamos la ventana en tiempo real
            $hour_start = strtotime(Carbon::parse(date("Y-m-d") . " " . $hour_start_raw)->subHour());
            $hour_end = strtotime(Carbon::parse(date("Y-m-d") . " " . $hour_end_raw)->subHour());

            if ($hour_start <= $now_time_number && $hour_end >= $now_time_number) {

                $hour_start_format = Carbon::parse(date("Y-m-d") . " " . $hour_start_raw)->format("h:i A");
                $hour_end_format = Carbon::parse(date("Y-m-d") . " " . $hour_end_raw)->format("h:i A");
                $doctor_full_name = $appointment->doctor->name . ' ' . $appointment->doctor->surname;

                // Armamos el bloque de datos para este paciente
                $whatsappQueue->push([
                    'doctor_id' => (string) $appointment->doctor_id, // Match con klyntic_consultorios (_id)
                    'telefono' => $appointment->patient->phone,     // CORREGIDO: Dinámico por paciente
                    'mensaje' => "Hola *{$appointment->patient->name} {$appointment->patient->surname}*, le recordamos su cita médica de *{$appointment->speciality->name}* programada para hoy. Horario: {$hour_start_format} hasta {$hour_end_format}. Profesional: {$doctor_full_name}."
                ]);

                // Actualizamos el cron_state para sacarla del siguiente ciclo
                $appointment->update(["cron_state" => 2]);
            }
        }

        // 🚀 UN SOLO DISPARO HTTP (Ideal para Shared Hosting)
        // 🚀 DISPARO EN LOTE HACIA TU RUTA DE KLYNTIC INYECTADA EN NODE
        if ($whatsappQueue->count() > 0) {
            try {
                $response = Http::post('https://back-klyntic-envios.onrender.com', [
                    'recordatorios' => $whatsappQueue->toArray()
                ]);

                if ($response->successful()) {
                    $this->info($whatsappQueue->count() . ' recordatorios enviados al microservicio Node.');
                } else {
                    $this->error('Node (Klyntic) rechazó el lote de mensajes.');
                }
            } catch (\Exception $e) {
                $this->error('Error conectando con Render: ' . $e->getMessage());
            }

        } else {
            $this->info('No hay citas en este rango de hora para enviar por WhatsApp.');
        }

        return 0;
    }
}
