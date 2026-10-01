<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    // 🟢 Aceptamos peticiones únicamente de tus frentes reales y locales
    'allowed_origins' => ['https://klyntic.com', 'https://klyntic.com', 'http://localhost:4200'], 
    
    'allowed_origins_patterns' => [],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    
    // 🛡️ EL BLINDAJE MÁXIMO: Permitimos cualquier cabecera personalizada (Borrando el duplicado)
    'allowed_headers' => ['*'], 
    
    'exposed_headers' => [],
    'max_age' => 86400, // 24 horas de caché para que el navegador no sature con peticiones OPTIONS
    'supports_credentials' => true,
];
