<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnviarLoteWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recordatorios;

    /**
     * El constructor recibe el array con todos los mensajes del ciclo
     */
    public function __construct(array $recordatorios)
    {
        $this->recordatorios = $recordatorios;
    }

    /**
     * El comando 'send:notification' ejecutará esta función en segundo plano
     */
    public function handle()
    {
        try {
            // Obtenemos la URL de Render desde el .env de Laravel
            $nodeUrl = env('KLYNTIC_NODE_URL', 'https://back-klyntic-envios.onrender.com');

            // Hacemos el disparo masivo en un solo viaje
            $response = Http::post($nodeUrl . '/api/klyntic/notificaciones/bulk', [
                'recordatorios' => $this->recordatorios
            ]);

            if ($response->successful()) {
                Log::info('Lote de WhatsApp enviado con éxito a Render desde la cola.');
            } else {
                Log::error('Node en Render recibió la cola pero respondió con error.');
            }
        } catch (\Exception $exception) {
            Log::error('Fallo crítico al conectar con Render desde el Job de colas: ' . $exception->getMessage());
        }
    }
}
