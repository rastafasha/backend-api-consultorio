<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Envía una notificación al backend de Node.js (Ruta Sincronizada con Express)
     */
    public static function enviar($usuarioId, $rol, $consultorioId, $telefonoPaciente, $mensajeTexto, $tituloToastr, $tipoEnum, $refId = null)
    {
        // 1. Jalamos la URL local de tu .env (http://localhost:5000)
        $baseNodeUrl = rtrim(env('KLYNTIC_NODE_URL', 'http://localhost:5000'), '/');
        
        // 2. 🟢 RECTIFICACIÓN MAESTRA: Apuntamos al prefijo legítimo de tu archivo de rutas
        $urlNode = $baseNodeUrl . '/api/klyntic/notificaciones/webhook-recordatorio';

        try {
            Log::info("🚀 [LARAVEL DISPARO]: Despachando alerta hacia Node en: " . $urlNode);

            // 3. Enviamos el token unificado de tu .env (d651c7c2...)
            $tokenSecreto = env('CRM_INTERNAL_TOKEN');

            $response = Http::withHeaders([
                'Authorization' => $tokenSecreto,
                'Accept'        => 'application/json'
            ])->post($urlNode, [
                'consultorio_id' => $consultorioId,
                'telefono'       => $telefonoPaciente, 
                'mensaje'        => $mensajeTexto,
                'usuario'        => (string) $usuarioId, 
                'rolDestinatario'=> $rol,               // 'MEDICO' o 'PACIENTE'
                'titulo'         => $tituloToastr,      
                'tipo'           => $tipoEnum,          // 'PAGO_RECIBIDO', 'CITA_AGENDADA', etc.
                'referenciaId'   => $refId ? (string) $refId : null
            ]);

            Log::info("📡 [LARAVEL RESPUESTA]: Código de estatus recibido: " . $response->status());

            if (!$response->successful()) {
                Log::error("❌ Error HTTP en Node.js (Estatus " . $response->status() . "): " . $response->body());
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("💥 Fallo de conexión con Node.js en MAMP: " . $e->getMessage());
            return false;
        }
    }
}
