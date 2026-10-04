<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    // 🟢 Aceptamos peticiones únicamente de tus frentes reales y locales
    'allowed_origins' => ['https://pconsultorio.klyntic.com', 'https://consultorio.klyntic.com', 'https://klyntic.com', 'http://localhost:4200', 'http://localhost:4203'], 
    
    'allowed_methods' => ['*'],
    
    // 🛡️ REFUERZO CORS: Declaramos explícitamente las cabeceras personalizadas de Klyntic
    'allowed_headers' => ['*', 'X-Tenant-Slug', 'x-tenant-slug', 'Authorization', 'Content-Type', 'Accept', 'x-token', 'x-uid'], 
    
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
