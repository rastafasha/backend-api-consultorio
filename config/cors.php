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
        'https://onrender.com', 
        'https://klyntic.com', 
        'https://klyntic.com',
        'https://klyntic.com', // 👈 Añádelo explícitamente para asegurar que Render lo tome sí o sí
    ],

    // 🔥 Expresión regular corregida y estricta para subdominios multi-tenant con guiones
    'allowed_origins_patterns' => [
        '/^https:\/\/[a-zA-Z0-9\-_]+\.klyntic\.com$/', // 👈 Soporta letras, números, guiones bajos y guiones medios
        '/^https:\/\/[a-zA-Z0-9\-_]+\.vercel\.app$/',
    ],

    // Forzamos a aceptar todos los headers, incluyendo x-token
    'allowed_headers' => ['*'], 

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
