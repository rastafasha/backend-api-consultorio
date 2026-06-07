<?php

namespace App\Console;

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
        // 📧 1. Filtra las citas y mete los correos en la cola (Hora de Caracas)
        $schedule->command('command:notification-appointments')
                 ->timezone('America/Caracas')
                 ->everyFifteenMinutes() // Cambiado a 15 min para ser más amigable con el Shared Hosting
                 ->withoutOverlapping(); // Evita que se ejecute dos veces si el hosting se pone lento

        // 🟢 2. NUEVO: Filtra las citas y mete el lote de WhatsApp en la cola para Render
        // Activamos tu comando de Klyntic que unificamos hace un momento
        $schedule->command('command:notification-appointment-whatsapp')
                 ->timezone('America/Caracas')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();
        
        // 🚀 3. El Trabajador de Colas (Procesa tanto correos como los WhatsApps hacia Render)
        // Se ejecuta cada minuto, limpia la cola gracias al '--stop-when-empty' y se apaga limpiamente
        $schedule->command('send:notification')
                 ->everyMinute()
                 ->withoutOverlapping(5); // Bloquea por 5 minutos si se queda colgado procesando
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
