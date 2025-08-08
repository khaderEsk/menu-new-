<?php

use App\Http\Controllers\Api\Admin\UserTakeoutController;
use App\Http\Controllers\Api\User\DistanceController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

Route::get('/questions', [Controller::class, 'question']);
Route::post('/login', [Controller::class, 'LoginUser']);
Route::post('/register', [UserTakeoutController::class, 'create']);
Route::post('/forget_password', [Controller::class, 'modifyPassword']);
Route::post('/check_code', [Controller::class, 'codeVerification']);

Route::middleware(['auth:sanctum', 'role:takeout'])->group(function () {
    Route::post('/reset_password', [Controller::class, 'resetPassword']);
    Route::get('/show_address', [UserController::class, 'getAddress']);
    Route::get('/show_orders', [OrderController::class, 'showAll']);
    Route::delete('/delete_address', [UserController::class, 'deleteAddress']);
    Route::post('/add_address', [UserController::class, 'addAddress']);
    Route::delete('/delete_order', [OrderController::class, 'delete']);
    Route::post('/update_profile', [UserController::class, 'update']);
    Route::get('/show_profile', [UserTakeoutController::class, 'showProfile']);
    Route::post('/change_password', [UserController::class, 'changePassword']);
    Route::post('/check_coupon', [UserController::class, 'checkCoupon']);
});

// Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
// Route::post('reset-password', [ResetPasswordController::class, 'reset']);
// Route::post('/distance', [DistanceController::class, 'getDistance']);
