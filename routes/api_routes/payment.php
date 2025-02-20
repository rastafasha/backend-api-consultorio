<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPaymentSoftDeletesController;

//pagos

// Route::get('/pagos', [AdminPaymentController::class, 'index'])
//     ->name('payments.index');

Route::post('/payment/store', [AdminPaymentController::class, 'paymentStore'])
    ->name('payment.store');

Route::get('/payment', [AdminPaymentController::class, 'index'])
    ->name('payment.index');
Route::get('/payment/show/{payment}', [AdminPaymentController::class, 'paymentShow'])
    ->name('payment.show');

Route::put('/payment/update/{id}', [AdminPaymentController::class, 'paymentUpdate'])
    ->name('payment.update');

Route::delete('/payment/destroy/{payment:id}', [AdminPaymentController::class, 'paymentDestroy'])
    ->name('payment.destroy');

Route::get('payment/recientes/', [AdminPaymentController::class, 'recientes'])
    ->name('payment.recientes');
Route::get('payment/pendientes', [AdminPaymentController::class, 'pagosPendientes'])
    ->name('payment.pagosPendientes');


Route::post('/payment/upload', [AdminPaymentController::class, 'upload'])
    ->name('payment.upload');

Route::delete('/payment/delete-foto/{id}', [AdminPaymentController::class, 'deleteFotoPayment'])
    ->name('payment.deleteFotoPayment');

Route::get('/payment/search/', [AdminPaymentController::class, 'search'])
    ->name('payment.search');
    
Route::get('/payment/pagosbyUser/{id}', [AdminPaymentController::class, 'pagosbyUser'])
    ->name('payment.pagosbyUser');

Route::put('/payment/update/status/{payment:id}', [AdminPaymentController::class, 'updateStatus'])
    ->name('payment.updateStatus');

//Admin Pagos Softdeletes
Route::get('/payments/delete', [AdminPaymentSoftDeletesController::class, 'index'])
    ->name('payments.delete.index');

Route::get('/payment/delete/show/{id}', [AdminPaymentSoftDeletesController::class, 'paymentDeleteShow'])
    ->name('payment.delete.show');

Route::put('/payment/delete/restore/{id}', [AdminPaymentSoftDeletesController::class, 'paymentDeleteRestore'])
    ->name('payment.delete.restore');

Route::delete('/payment/destroy/force/{id}', [AdminPaymentSoftDeletesController::class, 'paymentDeleteforce'])
    ->name('payment.delete.force');
