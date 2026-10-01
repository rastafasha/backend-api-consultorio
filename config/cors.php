<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🌍 AGREGAMOS TUS SUBDOMINIOS DE PRUEBAS EXPLÍCITAMENTE AQUÍ
    'allowed_origins' => [
        'http://localhost:4300', 
        'http://localhost:4200',
        'http://localhost:4203',
        'http://localhost:3001',
        'https://localhost:3002',
        'http://localhost:3003',
        'https://consultorio.klyntic.com', 
        'https://pconsultorio.klyntic.com',
    ],

    // 🔥 Expresión regular corregida y estricta para subdominios multi-tenant con guiones
    'allowed_origins_patterns' => [
        '/^https:\/\/(.*\.)?klyntic\.com$/', // 🟢 EL CAMBIO: El (.*\.)? acepta CUALQUIER combinación de subdominios
        '/^https:\/\/(.*\.)?vercel\.app$/',  // Permite todas las ramas de pruebas de Vercel
    ],

    // Forzamos a aceptar todos los headers, incluyendo x-token
    'allowed_headers' => ['*'], 

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
