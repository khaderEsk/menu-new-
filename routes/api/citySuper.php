<?php

use App\Http\Controllers\Api\CitySuper\CityController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [Controller::class, 'Login']);

Route::middleware(['auth:sanctum','role:citySuperAdmin'])->group(function () {
    Route::get('/show_cities', [CityController::class, 'showAll']);

});

