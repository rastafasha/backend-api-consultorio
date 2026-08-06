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
        'https://consultorio.klyntic.com', 
        'https://pconsultorio.klyntic.com'
    ],

    // --- DEJA ESTO COMPLETAMENTE VACÍO PARA EVITAR EL CONFLICTO ---
    'allowed_origins_patterns' => [], 

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
