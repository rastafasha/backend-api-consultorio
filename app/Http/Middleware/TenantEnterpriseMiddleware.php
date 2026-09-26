<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantEnterpriseMiddleware
{
    /**
     * Intercepta la petición y detecta el Tenant por Host o por Cabecera HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Intentamos leer el subdominio desde la cabecera HTTP que envía el Admin de Angular
        $subdomain = $request->header('X-Clinica-Slug');

        // 2. Si no viene en la cabecera, recurrimos al método clásico del Host (Instagram Flow)
        if (!$subdomain) {
            $host = $request->getHost();
            $domainParts = explode('.', $host);
            
            if (count($domainParts) >= 3 && $domainParts[0] !== 'www') {
                $subdomain = $domainParts[0];
            } else {
                // Fallback local por defecto si estás en localhost plano
                $subdomain = 'clinica-prueba'; 
            }
        }

        // 🗺️ DICCIONARIO ESTÁTICO DE SLUGS A IDS (Tu mapeo limpio sin tablas duplicadas)
        $asociacionClinicas = [
            'clinica-prueba'  => 1,
            'clinica-doctora' => 2,
        ];

        // Obtenemos el ID numérico correspondiente
        $clinicaId = $asociacionClinicas[strtolower(trim($subdomain))] ?? 1;

        // Inyectamos de forma estática en el contenedor para que tus Scopes y Controladores filtren en verde
        app()->instance('current_clinica_id', $clinicaId);
        app()->instance('current_clinica_slug', $subdomain);

        return $next($request);
    }
}