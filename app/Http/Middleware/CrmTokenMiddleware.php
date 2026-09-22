<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CrmTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Buscamos el token en la cabecera 'Authorization'
        $token = $request->header('Authorization');

        if (!$token || $token !== env('CRM_INTERNAL_TOKEN')) {
            return response()->json([
                'ok' => false,
                'message' => 'Acceso denegado: Token de integración inválido o ausente.'
            ], 401);
        }

        return $next($request);
    }
}
