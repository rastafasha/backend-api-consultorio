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
        // Apuntamos a la URL de tu backend de Node.js desde el .env o directa
        // El framework busca la variable en el .env, si no existe usa el puerto 3000 por defecto
        $urlNode = env('NODE_BACKEND_URL', 'http://localhost:3000') . '/webhook-recordatorio';


        try {
            $response = Http::withHeaders([
                'x-token' => 'TU_SECRETO_INTERNO_OPCIONAL' // Por seguridad entre servidores si quieres
            ])->post($urlNode, [
                        'consultorio_id' => $consultorioId,
                        'telefono' => $telefonoPaciente, // Opcional: Si va vacío, el controlador no envía WhatsApp
                        'mensaje' => $mensajeTexto,
                        'usuario' => (string) $usuarioId, // ID de MySQL para la campana de Angular
                        'rolDestinatario' => $rol,               // 'MEDICO' o 'PACIENTE'
                        'titulo' => $tituloToastr,      // Título bonito para el front
                        'tipo' => $tipoEnum,          // 'PAGO_RECIBIDO', 'CITA_AGENDADA', etc.
                        'referenciaId' => $refId ? (string) $refId : null
                    ]);

            if (!$response->successful()) {
                Log::error("Error Node HTTP: " . $response->body());
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("Fallo conexión con Node.js: " . $e->getMessage());
            return false;
        }
    }
}
