<?php

use App\Http\Controllers\Api\Customer\InvoiceController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

Route::post('/login', [Controller::class, 'LoginDelivery']);

Route::group(['middleware' => ['checkEndDate','auth:sanctum']], function () {
    Route::get('/show_order', [OrderController::class, 'showOrder']);
    Route::get('/show_order_by_id', [OrderController::class, 'showOrderById']);
    Route::post('/accept_order', [OrderController::class, 'acceptOrder']);
    Route::post('/update_order', [OrderController::class, 'updateOrder']);
    Route::get('/not_available', [UserController::class, 'notAvailable']);
    Route::post('/rejected_order', [OrderController::class, 'rejectedOrder']);
    Route::post('/location_tracking', [UserController::class, 'locationTracking']);
    Route::post('/invoices/{invoice}/track', [InvoiceController::class, 'updateLocation']);
});
