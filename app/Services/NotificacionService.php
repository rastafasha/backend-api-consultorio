<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Envía una notificación al backend de Node.js
     */
    public static function enviar($usuarioId, $rol, $consultorioId, $telefonoPaciente, $mensajeTexto, $tituloToastr, $tipoEnum, $refId = null)
    {
        // 1. Limpiamos la URL para evitar el bug de la doble barra '//' en producción
        $baseNodeUrl = rtrim(env('KLYNTIC_NODE_URL', 'http://localhost:5000'), '/');
        
        // 2. Apuntamos a la ruta real de tu módulo de recursos/notificaciones de Node.js
        $urlNode = $baseNodeUrl . '/api/recursos/webhook-recordatorio';

        try {
            // Enviamos el token secreto interno que definimos en tu .env para comunicación segura
            $response = Http::withHeaders([
                'Authorization' => env('CRM_INTERNAL_TOKEN', 'KlynticCRMSecretToken_2026_vM0MmcxxA4Ih')
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

            if (!$response->successful()) {
                Log::error("❌ Error HTTP en Node.js (Estatus " . $response->status() . "): " . $response->body());
            } else {
                Log::info("🔔 Notificación enviada con éxito a Node.js para el usuario: " . $usuarioId);
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("💥 Fallo crítico de conexión con Node.js en Render: " . $e->getMessage());
            return false;
        }
    }
}
