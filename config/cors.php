<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Especificas tus dominios de Angular de forma limpia
    'allowed_origins' => [
        'http://localhost:4300', 
        'http://localhost:4200',
        'http://localhost:4203',
        'https://backend-crmklyntic-mean.onrender.com', 
        'https://consultorio.klyntic.com', 
        'https://pconsultorio.klyntic.com'
    ],
    // 🔥 PATRONES DINÁMICOS MULTI-TENANT (Para Producción en Vercel)
    // Permite que cualquier subdominio de Klyntic o enlaces de pruebas de Vercel consulten la API libremente
    'allowed_origins_patterns' => [
        '#^https://.*\.klyntic\.com$#',
        '#^https://.*\.vercel\.app$#',
    ],


    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
