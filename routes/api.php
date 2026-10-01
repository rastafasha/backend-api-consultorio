<?php
use App\Http\Controllers\Admin\Doctor\DoctorAddressController;
use App\Http\Controllers\Admin\Doctor\DoctorController;
use App\Http\Controllers\Admin\Doctor\SpecialityController;
use App\Http\Controllers\Admin\SettingGController;
use App\Http\Controllers\Api\TenantContextController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Enterprise\ClinicaController;
use App\Http\Controllers\tiposdepagoController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::post('register', [AuthController::class, 'register'])
//     ->name('register');

// Route::post('login', [AuthController::class, 'login'])
//     ->name('login');





Route::group(['middleware' => 'api'], function ($router) {

    // Auth
    require __DIR__ . '/api_routes/auth.php';

    // users
    require __DIR__ . '/api_routes/users.php';

    // roles
    require __DIR__ . '/api_routes/roles.php';

    // staff
    require __DIR__ . '/api_routes/staff.php';

    // specialities
    require __DIR__ . '/api_routes/specialities.php';

    // doctors
    require __DIR__ . '/api_routes/doctors.php';

    // patient
    require __DIR__ . '/api_routes/patient.php';

    // appointment
    require __DIR__ . '/api_routes/appointment.php';

    // appointmentpay
    require __DIR__ . '/api_routes/appointmentpay.php';

    // citamedica
    require __DIR__ . '/api_routes/citamedica.php';

    // dashboard
    require __DIR__ . '/api_routes/dashboard.php';

    // pagos
    require __DIR__ . '/api_routes/payment.php';

    // tipos de pago
    require __DIR__ . '/api_routes/paymentMethod.php';

    // setting
    require __DIR__ . '/api_routes/setting.php';

    // pub
    require __DIR__ . '/api_routes/pub.php';

    // location
    require __DIR__ . '/api_routes/location.php';

    // laboratory
    require __DIR__ . '/api_routes/laboratory.php';
    // laboratory
    require __DIR__ . '/api_routes/reporteLaboratory.php';

    // whatsapp
    // require __DIR__ . '/api_routes/whatsapp.php';

    // presupuesto
    require __DIR__ . '/api_routes/presupuesto.php';

    // odontograma
    require __DIR__ . '/api_routes/odontograma.php';

    // pais
    require __DIR__ . '/api_routes/pais.php';

    // crm
    require __DIR__ . '/api_routes/crm.php';

    // =========================================================================
    // 🏢 MÓDULOS KLYNTIC ENTERPRISE SEPARADOS
    // =========================================================================

    // Rutas Libres Enterprise (Selector Apple)
    require __DIR__ . '/api_routes/enterprise_pub.php';

    // Rutas Protegidas Enterprise (Recepción Centralizada)
    require __DIR__ . '/api_routes/enterprise_private.php';


    // 🌍 RUTAS PÚBLICAS ABIERTAS (Sin middleware de autenticación de login)
    Route::get('appointments/config', [AppointmentController::class, 'config']);
    Route::get('specialities/show/{id}', [SpecialityController::class, 'show']);
    Route::get('doctors/profile/{id}', [DoctorController::class, 'profile']); // El de tu función unificada
    Route::get('doctor-addresses/doctor/{user_id}', [DoctorAddressController::class, 'getByDoctor']);
    Route::get('paymentmethods/bydoctor/{doctor_id}', [tiposdepagoController::class, 'byDoctor']);


    // Endpoint express que guarda al paciente y la cita en un solo paso
    Route::post('appointments/filterbydoctor/{doctor_id}', [AppointmentController::class, 'filterByDoctor']);
    Route::post('appointments/store-express', [AppointmentController::class, 'storeExpress']);


    // Endpoint para el control dinámico de subdominios
    Route::get('/v1/contexto-express', [TenantContextController::class, 'obtenerContextoExpress']);


    // =========================================================================
// 🔓 ZONA DE CONTROL ABSOLUTO (Rutas totalmente vírgenes y libres de Middleware)
// =========================================================================

    Route::get('/klyntic-clear-cache-remoto', function () {
        try {
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            Log::info('Purga remota ejecutada con éxito en la raíz de Render.');

            return response()->json([
                'status' => 'success',
                'message' => '¡Búfer y caché reventados en la raíz sin pasar por middlewares!',
                'opcache_reset' => function_exists('opcache_reset') ? 'Si' : 'No disponible'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    });


    //comandos desde la url del backend

    

    Route::get('/clear-all', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return "✅ Pizarra limpia desde la raíz.";
    });

    Route::get('/optimize', function () {
        Artisan::call('optimize:clear');
        return "Optimización de Laravel";
    });

    Route::get('/storage-link', function () {
        Artisan::call('storage:link');
        return "Storage Link";
    });

    //     Artisan::call('migrate', [
//     '--path' => '/database/migrations/',
//     '--force' => true
// ]);


    Route::get('/migrate-fresh', function () {
        Artisan::call('migrate:refresh');
        return "Migrate: Actualizando sin borrar";
    });


    Route::get('/migrate-seed', function () {
        Artisan::call('migrate:refresh --seed');
        return "Migrate: creacion con datos, para uso";
    });



    Route::get('/migrate-update', function () {
        try {
            // Ejecuta solo las migraciones pendientes sin tocar la data actual
            Artisan::call('migrate', [
                '--force' => true // Necesario si estás en entorno de producción
            ]);

            return "Migración completada: Estructura actualizada sin pérdida de datos.";
        } catch (\Exception $e) {
            return "Error al migrar: " . $e->getMessage();
        }
    });

    Route::get('/route-clear', function () {
        Artisan::call('route:clear');
        return "Route cache cleared successfully.";
    });



});