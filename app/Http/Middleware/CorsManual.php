<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsManual
{
    public function handle(Request $request, Closure $next)
    {
        // Intercepta el Preflight OPTIONS de Angular de inmediato
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        }

        // Añade las cabeceras a las peticiones normales como POST/GET
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', '*')
                     ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                     ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        }

        return $response;
    }
}
