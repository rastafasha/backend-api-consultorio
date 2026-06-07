<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SendMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio de correos al admin en colas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
   public function handle()
    {
        $this->info('Iniciando el procesador de colas en el Hosting Compartido...');

        // Ejecuta el trabajador de colas, procesa lo que haya en 'high', 'emails' y 'low'
        // Y se apaga inmediatamente cuando la cola quede vacía de forma segura.
        return Artisan::call('queue:work', [
            '--sleep' => 3,
            '--tries' => 3,
            '--backoff' => 3,
            '--timeout' => 20, // Lo bajamos a 20 para proteger el Shared Hosting
            '--queue' => 'high,emails,low', 
            '--stop-when-empty' => true, // En algunas versiones es mejor pasar true en lugar de null
        ]);
    }
}
