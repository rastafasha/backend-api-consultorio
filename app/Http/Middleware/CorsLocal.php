<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsLocal
{
    public function handle(Request $request, Closure $next)
    {
        // Listamos todos los puertos locales que usas para tus apps de Angular
        $allowedOrigins = [
            'http://localhost:4200',
            'http://127.0.0.1:4200',
            'http://localhost:4300',
            'http://127.0.0.1:4300',
            'http://localhost:4203',
            'http://127.0.0.1:4203',
        ];

        // Capturamos desde qué puerto viene la petición actual de Angular
        $origin = $request->header('Origin');

        // Si viene de uno de tus puertos permitidos, lo inyectamos dinámicamente
        $response = $next($request);
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        // Inyectamos el resto de las cabeceras obligatorias para el login y interceptores
        return $response
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept, Origin');
    }
}
