<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    // 🟢 Aceptamos peticiones únicamente de tus frentes reales y locales
    'allowed_origins' => ['https://pconsultorio.klyntic.com', 'https://consultorio.klyntic.com', 'https://klyntic.com', 'http://localhost:4200', 'http://localhost:4203'], 
    
    'allowed_methods' => ['*'],
    
    // 🛡️ BLINDAJE MÁXIMO: Permitimos cualquier cabecera personalizada para el login del paciente
    'allowed_headers' => ['*'], 
    
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
