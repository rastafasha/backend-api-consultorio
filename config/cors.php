<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    // 🟢 Aceptamos peticiones únicamente de tus frentes reales y locales
    'allowed_origins' => ['https://klyntic.com', 'https://klyntic.com', 'http://localhost:4200', 'http://localhost:4203'], 
    
    'allowed_origins_patterns' => [],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    
    // 🛡️ EL BLINDAJE MÁXIMO: Permitimos cualquier cabecera personalizada (Borrando el duplicado)
    'allowed_headers' => [
        'Content-Type', 
        'X-Requested-With', 
        'Authorization', 
        'x-token', 
        'X-Token', 
        'x-uid', 
        'X-Uid', 
        'x-tenant-slug', // Variante Fetch
        'X-Tenant-Slug'  // Variante Interceptor
    ],
    
    'exposed_headers' => [],
    'max_age' => 86400, // 24 horas de caché para que el navegador no sature con peticiones OPTIONS
    'supports_credentials' => true,
];
