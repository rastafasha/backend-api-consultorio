<?php

namespace App\Console;

use App\Models\Appointment\Appointment;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        commands\SendMailNotificationCommand::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // =========================================================================
// ⏰ TAREA AUTOMÁTICA: Recordatorio Diario de Citas Médicas (Klyntic)
// =========================================================================
        Schedule::call(function () {

            // 1. Buscamos todas las citas de mañana que estén confirmadas (status o confirmation = 2)
            $manana = Carbon::tomorrow()->format('Y-m-d');
            $citasDeManana = Appointment::whereDate('date_appointment', $manana)
                ->where('confimation', 2)
                ->get();

            // 2. Iteramos y disparamos el recordatorio a Node.js
            foreach ($citasDeManana as $cita) {
                NotificacionService::enviar(
                    $cita->consultorio_id,
                    $cita->patient->phone, // WhatsApp automático
                    "Hola " . $cita->patient->name . ", te recordamos que tienes una cita médica programada para el día de mañana " . Carbon::parse($cita->date_appointment)->format('d-m-Y') . " a las " . Carbon::parse($cita->date_appointment)->format('h:i A') . ". Por favor, asiste con 15 minutos de anticipación.",
                    $cita->patient_id,    // Campana interna en Angular Paciente
                    'PACIENTE',
                    '⏰ Recordatorio de Cita',
                    'RECORDATORIO',
                    $cita->id
                );
            }

        })->dailyAt('07:00'); // Se ejecuta solo todas las mañanas a las 7:00 AM
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
