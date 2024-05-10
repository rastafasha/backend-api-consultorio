<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SendMailNotificationCommand extends Command
{
      /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio de correos de notificación';

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
        return Artisan::call('queue:work', [
            '--sleep' => 3,
            '--tries' =>3,
            '--backoff' =>3,
            '--timeout' => 30,
            '--queue' => 'high,emails,low', // remove this if queue is default
            '--stop-when-empty' => null,
        ]);
    }
}
