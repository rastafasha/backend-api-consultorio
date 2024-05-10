<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (User $user) {
    return view('welcome');
});


// Route::get('/cronsStartToWorkEmailSend', function () {
//     \Artisan::call('emailsending:cron');
//     return true;
// });

// Route::get('/send-notification', function () {
//         Artisan::call('command:notification-appointments');
//         return "Send All notifications";
//     });

Route::get('/send-notification', function () {
        Artisan::call('command:notification-appointments');
        // return "Send All notifications";
        return true;
    });



