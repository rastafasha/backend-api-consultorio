<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// 🚀 BYPASS ATÓMICO DE CORS PARA KLYNTIC ENTERPRISE (Remoto Render)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Permitimos cualquier subdominio de klyntic o vercel
    if (preg_match('/klyntic\.com$/', $_SERVER['HTTP_ORIGIN']) || preg_match('/vercel\.app$/', $_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // Caché de 24 horas para el Preflight
    }
}

// Si la petición es OPTIONS (El Preflight que te está trancando el juego)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        // 🟢 FORZAMOS LA ACEPTACIÓN DE TU CABECERA EN MAYÚSCULA Y MINÚSCULA
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, x-token, X-Token, x-uid, X-Uid, x-tenant-slug, X-Tenant-Slug");
    }
    // Matamos la ejecución aquí con un 200 limpio para que no toque a Symfony ni dé el error 405
    exit(0);
}

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
