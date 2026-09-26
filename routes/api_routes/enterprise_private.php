<?php
// routes/api_routes/enterprise_private.php

use App\Http\Controllers\Enterprise\ClinicaController;
use Illuminate\Support\Facades\Route;

// 🔒 RUTAS PRIVADAS ENTERPRISE (Requieren login Y subdominio clínico)
Route::middleware(['auth:api', 'tenant.enterprise'])->group(function () {
    
    // Rutas operativas de la recepción centralizada de la clínica
    Route::get('clinica/perfil', [ClinicaController::class, 'show']);
    
    // Aquí meteremos más adelante el módulo corporativo y reportes de pago...
});