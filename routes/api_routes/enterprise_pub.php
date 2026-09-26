<?php
// routes/api_routes/enterprise_pub.php

use App\Http\Controllers\Enterprise\ClinicaController;
use Illuminate\Support\Facades\Route;

// 🟢 RUTAS PÚBLICAS ENTERPRISE (Libres de Login pero aisladas por subdominio)
Route::middleware(['tenant.enterprise'])->group(function () {
    
    //  El Selector estilo Apple libre para pacientes anónimos
    Route::get('clinica/selector-especialistas', [ClinicaController::class, 'getSelectorEspecialistas']);
});