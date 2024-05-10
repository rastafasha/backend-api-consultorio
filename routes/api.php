<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\Doctor\DoctorController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ChangeForgotPasswordControllerController;


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
    
    // whatsapp
    // require __DIR__ . '/api_routes/whatsapp.php';




    //comandos desde la url del backend

    Route::get('/cache', function () {
        Artisan::call('cache:clear');
        return "Cache";
    });

    Route::get('/optimize', function () {
        Artisan::call('optimize:clear');
        return "Optimización de Laravel";
    });

    Route::get('/storage-link', function () {
        Artisan::call('storage:link');
        return "Storage Link";
    });


    Route::get('/migrate-seed', function () {
        Artisan::call('migrate:refresh --seed');
        return "Migrate seed";
    });
    
    Route::get('/send-notification', function () {
        Artisan::call('command:notification-appointments');
        return "Send All notifications";
    });
    
    Route::get('/send-whatsapp', function () {
        Artisan::call('command:notification-appointment-whatsapp');
        return "Send All whatsapp";
    });




    //rutas libres


    // Route::get('/categories', [CategoryController::class, 'index'])
    //     ->name('category.index');


});
