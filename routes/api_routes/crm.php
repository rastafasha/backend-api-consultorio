<?php

use App\Http\Controllers\CRMIntegrationController;
use Illuminate\Support\Facades\Route;

// Cuando el paciente digite '://klyntic.com', Angular llamará a esta ruta para validar si existe
Route::get('express/validate-subdomain/{subdomain}', [CRMIntegrationController::class, 'validateSubdomain']);
Route::post('crm/sync-tenant', [CRMIntegrationController::class, 'syncTenantExpress'])->name('appointment-atention.syncTenantExpress');